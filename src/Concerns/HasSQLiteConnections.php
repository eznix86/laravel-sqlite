<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

use function base_path;
use function collect;
use function config;
use function throw_if;

trait HasSQLiteConnections
{
    protected function sqliteConnections(): Collection
    {
        return collect(config('database.connections', []))
            ->filter(fn ($connection): bool => is_array($connection) && (($connection['driver'] ?? null) === 'sqlite'))
            ->keys()
            ->map(fn ($name): string => (string) $name)
            ->values();
    }

    protected function touchSQLiteConnections(?Collection $connections = null): void
    {
        ($connections ?? $this->sqliteConnections())
            ->each(function (string $connection): void {
                $database = (string) config("database.connections.{$connection}.database", '');

                if ($database === '' || $database === ':memory:') {
                    return;
                }

                $databasePath = $this->resolveDatabasePath($database);
                $directory = dirname($databasePath);

                File::ensureDirectoryExists($directory);

                throw_if(! File::exists($databasePath) && ! touch($databasePath), RuntimeException::class, "Failed to create SQLite database file [{$databasePath}].");
            });
    }

    protected function resolveDatabasePath(string $database): string
    {
        if ($this->isAbsolutePath($database)) {
            return $database;
        }

        $this->ensureSafeRelativePath($database);

        return base_path($database);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\\\\\/]/', $path) === 1;
    }

    private function ensureSafeRelativePath(string $path): void
    {
        throw_if(str_contains($path, "\0"), InvalidArgumentException::class, 'Invalid SQLite database path.');

        $segments = explode('/', str_replace('\\', '/', $path));

        throw_if(collect($segments)->contains('..'), InvalidArgumentException::class, 'Path traversal is not allowed for SQLite database paths.');
    }
}
