<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('runs vacuum on all sqlite connections by default', function (): void {
    artisan('sqlite:vacuum')
        ->expectsOutputToContain('Running VACUUM on [sqlite]...')
        ->expectsOutputToContain('VACUUM completed on [sqlite].')
        ->expectsOutputToContain('Running VACUUM on [sqlite_two]...')
        ->expectsOutputToContain('VACUUM completed on [sqlite_two].')
        ->assertExitCode(0);
});

it('runs vacuum for one selected connection', function (): void {
    artisan('sqlite:vacuum', ['--connection' => 'sqlite'])
        ->expectsOutputToContain('Running VACUUM on [sqlite]...')
        ->expectsOutputToContain('VACUUM completed on [sqlite].')
        ->doesntExpectOutputToContain('sqlite_two')
        ->assertExitCode(0);
});

it('fails for non sqlite selected connection', function (): void {
    artisan('sqlite:vacuum', ['--connection' => 'array_store'])
        ->expectsOutputToContain('Connection [array_store] is not using sqlite.')
        ->assertExitCode(1);
});

it('fails for unknown selected connection', function (): void {
    artisan('sqlite:vacuum', ['--connection' => 'unknown_connection'])
        ->expectsOutputToContain('Connection [unknown_connection] is not configured.')
        ->assertExitCode(1);
});

it('blocks vacuum when litestream is enabled', function (): void {
    config()->set('sqlite.litestream', true);

    artisan('sqlite:vacuum')
        ->expectsOutputToContain('sqlite:vacuum is blocked because sqlite.litestream=true.')
        ->assertExitCode(1);
});

it('creates missing sqlite file and parent directory before vacuum', function (): void {
    $databasePath = temporaryFile('missing/vacuum.sqlite');
    $testsDirectory = dirname($databasePath, 2);

    File::deleteDirectory($testsDirectory);

    config()->set('database.connections.sqlite_two.database', $databasePath);
    DB::purge('sqlite_two');

    artisan('sqlite:vacuum', ['--connection' => 'sqlite_two'])
        ->expectsOutputToContain('Running VACUUM on [sqlite_two]...')
        ->expectsOutputToContain('VACUUM completed on [sqlite_two].')
        ->assertExitCode(0);

    expect(File::exists($databasePath))->toBeTrue();
});
