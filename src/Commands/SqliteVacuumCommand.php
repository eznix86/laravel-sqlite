<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite\Commands;

use Eznix86\LaravelSQLite\Concerns\HasSQLiteConnections;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function collect;
use function config;
use function filled;

class SqliteVacuumCommand extends Command
{
    use HasSQLiteConnections;

    protected $signature = 'sqlite:vacuum {--connection= : Optional single connection name}';

    protected $description = 'Run full VACUUM on all SQLite connections';

    public function handle(): int
    {
        if ((bool) config('sqlite.litestream', false)) {
            $this->error('sqlite:vacuum is blocked because sqlite.litestream=true.');

            return self::FAILURE;
        }

        $requestedConnection = $this->option('connection');

        if (filled($requestedConnection)) {
            $connectionName = (string) $requestedConnection;

            if (! $this->hasConnection($connectionName)) {
                $this->error("Connection [{$connectionName}] is not configured.");

                return self::FAILURE;
            }

            if (! $this->isConfiguredAsSqliteConnection($connectionName)) {
                $this->error("Connection [{$connectionName}] is not using sqlite.");

                return self::FAILURE;
            }
        }

        $connectionNames = filled($requestedConnection)
            ? collect([(string) $requestedConnection])
            : $this->sqliteConnections();

        if ($connectionNames->isEmpty()) {
            $this->error('No sqlite connections were found in database.connections.');

            return self::FAILURE;
        }

        $this->touchSQLiteConnections($connectionNames);

        $failed = $connectionNames->contains(function (string $connectionName): bool {
            if (! $this->isConfiguredAsSqliteConnection($connectionName)) {
                $this->error("Connection [{$connectionName}] is not using sqlite.");

                return true;
            }

            $connection = DB::connection($connectionName);

            if ($connection->getDriverName() !== 'sqlite') {
                $this->error("Connection [{$connectionName}] is not using sqlite.");

                return true;
            }

            try {
                $this->info("Running VACUUM on [{$connectionName}]...");
                $connection->unprepared('VACUUM;');
                $this->info("VACUUM completed on [{$connectionName}].");

                return false;
            } catch (Throwable $exception) {
                $this->error("VACUUM failed on [{$connectionName}]: ".$exception->getMessage());

                return true;
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function isConfiguredAsSqliteConnection(string $connectionName): bool
    {
        return (string) config("database.connections.{$connectionName}.driver", '') === 'sqlite';
    }

    private function hasConnection(string $connectionName): bool
    {
        return is_array(config("database.connections.{$connectionName}"));
    }
}
