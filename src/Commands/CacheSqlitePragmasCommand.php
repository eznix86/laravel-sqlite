<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite\Commands;

use Eznix86\LaravelSQLite\AppServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CacheSqlitePragmasCommand extends Command
{
    protected $signature = 'sqlite:cache-pragmas';

    protected $description = 'Cache computed SQLite pragma SQL';

    public function handle(): int
    {
        $sql = AppServiceProvider::buildSqlitePragmasSql();
        $path = AppServiceProvider::sqlitePragmasCachePath();

        File::ensureDirectoryExists(dirname($path));

        if (File::put($path, '<?php return '.var_export($sql, true).';') === false) {
            $this->error("Failed to write SQLite pragma cache file at [{$path}].");

            return self::FAILURE;
        }

        $this->info('SQLite pragma SQL cached.');

        return self::SUCCESS;
    }
}
