<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite\Listeners;

use Eznix86\LaravelSQLite\Concerns\HasSQLiteConnections;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

use function collect;
use function config;

class PrepareSQLiteForMigrations
{
    use HasSQLiteConnections;

    private const array HANDLED_COMMANDS = [
        'migrate',
        'migrate:fresh',
        'migrate:install',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
        'db:wipe',
    ];

    public function handle(CommandStarting $event): void
    {
        if (! $this->shouldHandle($event->command)) {
            return;
        }

        $connections = $this->targetConnections($event);

        if ($connections->isEmpty()) {
            return;
        }

        if ($event->command === 'migrate:fresh') {
            $this->deleteSqliteFiles($connections);
        }

        $this->touchSQLiteConnections($connections);
    }

    private function shouldHandle(string $command): bool
    {
        return in_array($command, self::HANDLED_COMMANDS, true);
    }

    private function targetConnections(CommandStarting $event): Collection
    {
        $database = $this->requestedDatabaseConnection($event);

        if ($database === null || $database === '') {
            return $this->sqliteConnections();
        }

        return $this->sqliteConnections()->contains($database)
            ? collect([$database])
            : collect();
    }

    private function requestedDatabaseConnection(CommandStarting $event): ?string
    {
        if (! $event->input->hasOption('database')) {
            return null;
        }

        $database = $event->input->getOption('database');

        return is_string($database) && $database !== '' ? $database : null;
    }

    private function deleteSqliteFiles(Collection $connections): void
    {
        $connections->each(function (string $connection): void {
            $database = (string) config("database.connections.{$connection}.database", '');

            if ($database === '' || $database === ':memory:') {
                return;
            }

            $databasePath = $this->resolveDatabasePath($database);

            File::delete([
                $databasePath,
                $databasePath.'-wal',
                $databasePath.'-shm',
                $databasePath.'-journal',
            ]);
        });
    }
}
