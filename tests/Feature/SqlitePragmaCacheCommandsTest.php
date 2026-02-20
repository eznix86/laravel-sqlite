<?php

declare(strict_types=1);

use Eznix86\LaravelSQLite\AppServiceProvider;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('caches computed pragma sql to bootstrap cache', function (): void {
    $cachePath = AppServiceProvider::sqlitePragmasCachePath();

    if (is_file($cachePath)) {
        unlink($cachePath);
    }

    artisan('sqlite:cache-pragmas')
        ->expectsOutputToContain('SQLite pragma SQL cached.')
        ->assertExitCode(0);

    expect($cachePath)->toBeFile();

    $cached = require $cachePath;

    expect($cached)
        ->toBeString()
        ->toContain('PRAGMA cache_size = -65536;')
        ->toContain('PRAGMA temp_store = MEMORY;')
        ->toContain('PRAGMA mmap_size = 67108864;')
        ->toContain('PRAGMA wal_autocheckpoint = 1000;');
});

it('clears cached pragma sql file', function (): void {
    $cachePath = AppServiceProvider::sqlitePragmasCachePath();

    artisan('sqlite:cache-pragmas')->assertExitCode(0);
    expect($cachePath)->toBeFile();

    artisan('sqlite:clear-pragmas-cache')
        ->expectsOutputToContain('SQLite pragma SQL cache cleared.')
        ->assertExitCode(0);

    expect($cachePath)->not->toBeFile();
});

it('fails when pragma sql cache cannot be written', function (): void {
    $cachePath = AppServiceProvider::sqlitePragmasCachePath();

    File::shouldReceive('ensureDirectoryExists')->once();
    File::shouldReceive('put')->once()->withArgs(fn (string $path, mixed $contents): bool => $path === $cachePath && is_string($contents))->andReturn(false);

    artisan('sqlite:cache-pragmas')
        ->expectsOutputToContain("Failed to write SQLite pragma cache file at [{$cachePath}].")
        ->assertExitCode(1);
});

it('fails when pragma sql cache cannot be cleared', function (): void {
    $cachePath = AppServiceProvider::sqlitePragmasCachePath();

    File::shouldReceive('exists')->once()->with($cachePath)->andReturn(true);
    File::shouldReceive('delete')->once()->with($cachePath)->andReturn(false);

    artisan('sqlite:clear-pragmas-cache')
        ->expectsOutputToContain("Failed to clear SQLite pragma SQL cache at [{$cachePath}].")
        ->assertExitCode(1);
});
