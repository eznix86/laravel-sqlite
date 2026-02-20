<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite\Commands;

use Eznix86\LaravelSQLite\Concerns\HasSQLiteConnections;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

use function collect;
use function config;
use function filled;
use function rescue;

class ShowSQLitePragmasCommand extends Command
{
    use HasSQLiteConnections;

    protected $signature = 'sqlite:show-pragmas {connection?}';

    protected $description = 'Display SQLite PRAGMA settings from config and runtime';

    public function handle(): int
    {
        $sqliteConnections = $this->sqliteConnections();

        if ($sqliteConnections->isEmpty()) {
            $this->error('No SQLite connections configured.');

            return self::FAILURE;
        }

        $requestedConnection = $this->argument('connection');

        if (filled($requestedConnection) && $sqliteConnections->doesntContain((string) $requestedConnection)) {
            $this->error("Connection '{$requestedConnection}' is not a SQLite connection.");
            $this->info('Available SQLite connections: '.$sqliteConnections->implode(', '));

            return self::FAILURE;
        }

        $connections = filled($requestedConnection)
            ? collect([(string) $requestedConnection])
            : $sqliteConnections;

        $this->touchSQLiteConnections($connections);

        $this->displayConfigPragmas($connections);
        $this->newLine();
        $this->displayRuntimePragmas($connections);

        return self::SUCCESS;
    }

    private function displayConfigPragmas(Collection $connections): void
    {
        $this->info('Configuration PRAGMA settings');
        $this->newLine();

        $pragmaMap = collect([
            'busy_timeout' => ['pragma' => 'busy_timeout', 'description' => 'Lock wait timeout'],
            'journal_mode' => ['pragma' => 'journal_mode', 'description' => 'Database journaling mode'],
            'synchronous' => ['pragma' => 'synchronous', 'description' => 'Disk synchronization level'],
            'transaction_mode' => ['pragma' => 'Not a PRAGMA', 'description' => 'Connection transaction mode'],
            'foreign_key_constraints' => ['pragma' => 'foreign_keys', 'description' => 'Foreign key constraints'],
        ]);

        $headers = collect(['Config Key', 'PRAGMA Name'])
            ->merge($connections)
            ->push('Description')
            ->all();

        $rows = $pragmaMap
            ->map(function (array $meta, string $configKey) use ($connections): array {
                $row = [
                    'Config Key' => $configKey,
                    'PRAGMA Name' => $meta['pragma'],
                ];

                $connections->each(function (string $connection) use (&$row, $configKey): void {
                    $value = config("database.connections.{$connection}.{$configKey}");
                    $row[$connection] = $this->formatConfigValue($configKey, $value);
                });

                $row['Description'] = $meta['description'];

                return $row;
            })
            ->values()
            ->all();

        $this->table($headers, $rows);
    }

    private function displayRuntimePragmas(Collection $connections): void
    {
        $this->info('Runtime PRAGMA settings');
        $this->newLine();

        $runtimePragmas = collect([
            'cache_size' => 'Memory/page cache size',
            'temp_store' => 'Temporary table storage',
            'mmap_size' => 'Memory-mapped I/O size',
            'journal_mode' => 'Database journaling mode',
            'synchronous' => 'Disk synchronization level',
            'foreign_keys' => 'Foreign key constraints',
            'busy_timeout' => 'Lock wait timeout',
            'wal_autocheckpoint' => 'WAL auto checkpoint pages',
        ]);

        $headers = collect(['PRAGMA'])
            ->merge($connections)
            ->push('Description')
            ->all();

        $rows = $runtimePragmas
            ->map(function (string $description, string $pragma) use ($connections): array {
                $row = ['PRAGMA' => $pragma];

                $connections->each(function (string $connection) use (&$row, $pragma): void {
                    $value = $this->fetchPragmaValue($connection, $pragma);
                    $row[$connection] = $this->formatRuntimeValue($pragma, $value);
                });

                $row['Description'] = $description;

                return $row;
            })
            ->values()
            ->all();

        $this->table($headers, $rows);
    }

    private function fetchPragmaValue(string $connection, string $pragma): mixed
    {
        return rescue(function () use ($connection, $pragma) {
            $result = DB::connection($connection)->selectOne("PRAGMA {$pragma}");

            return match (true) {
                is_object($result) => Arr::first((array) $result),
                is_array($result) => Arr::first($result),
                default => $result,
            };
        }, report: false);
    }

    private function formatConfigValue(string $key, mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        return match (true) {
            is_bool($value) => $value ? 'enabled' : 'disabled',
            $key === 'busy_timeout' => Number::format((int) $value).' ms',
            default => Str::upper((string) $value),
        };
    }

    private function formatRuntimeValue(string $pragma, mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        return match ($pragma) {
            'cache_size' => (int) $value < 0
                ? Number::fileSize(abs((int) $value) * 1024)
                : Number::format((int) $value).' pages',
            'temp_store' => match ((int) $value) {
                0 => 'DEFAULT',
                1 => 'FILE',
                2 => 'MEMORY',
                default => (string) $value,
            },
            'mmap_size' => Number::fileSize((int) $value),
            'synchronous' => match ((int) $value) {
                0 => 'OFF (0)',
                1 => 'NORMAL (1)',
                2 => 'FULL (2)',
                3 => 'EXTRA (3)',
                default => (string) $value,
            },
            'foreign_keys' => (int) $value === 1 ? 'enabled' : 'disabled',
            'busy_timeout' => Number::format((int) $value).' ms',
            'journal_mode' => Str::upper((string) $value),
            default => (string) $value,
        };
    }
}
