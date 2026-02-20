<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('shows config and runtime pragmas for all sqlite connections', function (): void {
    artisan('sqlite:show-pragmas')
        ->expectsOutputToContain('Configuration PRAGMA settings')
        ->expectsOutputToContain('Runtime PRAGMA settings')
        ->expectsOutputToContain('sqlite')
        ->expectsOutputToContain('cache_size')
        ->expectsOutputToContain('journal_mode')
        ->expectsOutputToContain('busy_timeout')
        ->assertExitCode(0);
});

it('can target another sqlite connection', function (): void {
    artisan('sqlite:show-pragmas', ['connection' => 'sqlite_two'])
        ->expectsOutputToContain('sqlite_two')
        ->expectsOutputToContain('Runtime PRAGMA settings')
        ->assertExitCode(0);
});

it('shows a useful error for non sqlite requested connection', function (): void {
    artisan('sqlite:show-pragmas', ['connection' => 'array_store'])
        ->expectsOutputToContain("Connection 'array_store' is not a SQLite connection.")
        ->expectsOutputToContain('Available SQLite connections:')
        ->assertExitCode(1);
});

it('creates missing sqlite file and directory before showing pragmas', function (): void {
    $databasePath = temporaryFile('missing/show.sqlite');
    $testsDirectory = dirname($databasePath, 2);

    File::deleteDirectory($testsDirectory);

    config()->set('database.connections.sqlite_two.database', $databasePath);
    DB::purge('sqlite_two');

    artisan('sqlite:show-pragmas', ['connection' => 'sqlite_two'])
        ->expectsOutputToContain('Configuration PRAGMA settings')
        ->expectsOutputToContain('Runtime PRAGMA settings')
        ->assertExitCode(0);

    expect(File::exists($databasePath))->toBeTrue();
});
