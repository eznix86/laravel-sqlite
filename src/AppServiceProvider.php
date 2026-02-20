<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite;

use Eznix86\LaravelSQLite\Commands\CacheSqlitePragmasCommand;
use Eznix86\LaravelSQLite\Commands\ClearSqlitePragmasCacheCommand;
use Eznix86\LaravelSQLite\Commands\ShowSQLitePragmasCommand;
use Eznix86\LaravelSQLite\Commands\SqliteVacuumCommand;
use Eznix86\LaravelSQLite\Concerns\HasSQLiteConnections;
use Eznix86\LaravelSQLite\Listeners\PrepareSQLiteForMigrations;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Override;

use function app;
use function collect;
use function config;
use function config_path;
use function rescue;

class AppServiceProvider extends ServiceProvider
{
    use HasSQLiteConnections;

    public static function sqlitePragmasSql(): ?string
    {
        $cacheFile = static::sqlitePragmasCachePath();

        if (is_file($cacheFile)) {
            $cached = require $cacheFile;

            return is_string($cached) && $cached !== '' ? $cached : null;
        }

        return static::buildSqlitePragmasSql();
    }

    public static function buildSqlitePragmasSql(): ?string
    {
        $tempStore = mb_strtoupper((string) config('sqlite.pragmas.temp_store', 'MEMORY'));
        $cacheSizeMb = max(0, (int) config('sqlite.pragmas.cache_size_mb', 64));
        $cacheSize = -($cacheSizeMb * 1024);
        $mmapSizeMb = max(0, (int) config('sqlite.pragmas.mmap_size_mb', 64));
        $mmapSize = $mmapSizeMb * 1024 * 1024;
        $isLitestream = (bool) config('sqlite.litestream', false);
        $walAutoCheckpoint = $isLitestream
            ? 0
            : max(0, (int) config('sqlite.pragmas.wal_autocheckpoint', 1000));

        $statements = collect([
            (bool) config('sqlite.pragmas.incremental_vacuum', true) ? 'PRAGMA incremental_vacuum' : null,
            in_array($tempStore, ['DEFAULT', 'FILE', 'MEMORY'], true) ? "PRAGMA temp_store = {$tempStore}" : null,
            "PRAGMA cache_size = {$cacheSize}",
            "PRAGMA mmap_size = {$mmapSize}",
            "PRAGMA wal_autocheckpoint = {$walAutoCheckpoint}",
        ])->filter()->values();

        if ($statements->isEmpty()) {
            return null;
        }

        return $statements->implode(";\n").';';
    }

    public static function sqlitePragmasCachePath(): string
    {
        return app()->bootstrapPath('cache/sqlite-pragmas.php');
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sqlite.php', 'sqlite');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheSqlitePragmasCommand::class,
                ClearSqlitePragmasCacheCommand::class,
                ShowSQLitePragmasCommand::class,
                SqliteVacuumCommand::class,
            ]);

            $this->optimizes(
                optimize: 'sqlite:cache-pragmas',
                clear: 'sqlite:clear-pragmas-cache',
            );

            $this->app->make('events')->listen(CommandStarting::class, PrepareSQLiteForMigrations::class);
        }

        $this->applySqlitePragmas();

        $this->publishes([
            __DIR__.'/../config/sqlite.php' => config_path('sqlite.php'),
        ], 'sqlite-config');
    }

    private function applySqlitePragmas(): void
    {
        if (! config('sqlite.enabled', true)) {
            return;
        }

        $sql = static::sqlitePragmasSql();

        if ($sql === null) {
            return;
        }

        $this->sqliteConnections()
            ->each(function (string $connectionName) use ($sql): void {
                rescue(function () use ($connectionName, $sql): void {
                    $connection = DB::connection($connectionName);

                    if ($connection->getDriverName() !== 'sqlite') {
                        return;
                    }

                    $this->applySqlToConnection($connection, $sql);
                }, report: false);
            });
    }

    private function applySqlToConnection(ConnectionInterface $connection, string $sql): void
    {
        $connection->unprepared($sql);
    }
}
