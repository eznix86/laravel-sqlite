<?php

namespace Tests;

use Eznix86\LaravelSQLite\AppServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AppServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app->make('config');
        $databaseOne = sys_get_temp_dir().'/laravel-sqlite-tests-one.sqlite';
        $databaseTwo = sys_get_temp_dir().'/laravel-sqlite-tests-two.sqlite';

        if (! file_exists($databaseOne)) {
            touch($databaseOne);
        }

        if (! file_exists($databaseTwo)) {
            touch($databaseTwo);
        }

        $config->set('database.default', 'sqlite');
        $config->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $databaseOne,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
            'synchronous' => 'normal',
            'transaction_mode' => 'immediate',
        ]);
        $config->set('database.connections.sqlite_two', [
            'driver' => 'sqlite',
            'database' => $databaseTwo,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 3000,
            'journal_mode' => 'wal',
            'synchronous' => 'normal',
            'transaction_mode' => 'deferred',
        ]);
        $config->set('database.connections.array_store', [
            'driver' => 'array',
            'prefix' => '',
        ]);

        $config->set('sqlite.enabled', true);
        $config->set('sqlite.litestream', false);
        $config->set('sqlite.pragmas.incremental_vacuum', true);
        $config->set('sqlite.pragmas.temp_store', 'MEMORY');
        $config->set('sqlite.pragmas.cache_size_mb', 64);
        $config->set('sqlite.pragmas.mmap_size_mb', 64);
        $config->set('sqlite.pragmas.wal_autocheckpoint', 1000);
    }
}
