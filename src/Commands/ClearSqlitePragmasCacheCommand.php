<?php

declare(strict_types=1);

namespace Eznix86\LaravelSQLite\Commands;

use Eznix86\LaravelSQLite\AppServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearSqlitePragmasCacheCommand extends Command
{
    protected $signature = 'sqlite:clear-pragmas-cache';

    protected $description = 'Clear cached SQLite pragma SQL';

    public function handle(): int
    {
        $path = AppServiceProvider::sqlitePragmasCachePath();

        if (File::exists($path) && ! File::delete($path)) {
            $this->error("Failed to clear SQLite pragma SQL cache at [{$path}].");

            return self::FAILURE;
        }

        $this->info('SQLite pragma SQL cache cleared.');

        return self::SUCCESS;
    }
}
