<?php

declare(strict_types=1);

use Eznix86\LaravelSQLite\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

beforeEach(function (): void {
    $cachePath = AppServiceProvider::sqlitePragmasCachePath();

    if (is_file($cachePath)) {
        File::delete($cachePath);
    }
});

afterEach(function (): void {
    $cachePath = AppServiceProvider::sqlitePragmasCachePath();

    if (is_file($cachePath)) {
        File::delete($cachePath);
    }
});

function pragmaValue(string $pragma): int
{
    $result = DB::select("PRAGMA {$pragma};");

    return (int) array_values((array) $result[0])[0];
}

function temporaryFile(string $path): string
{
    return testsTempBasePath().'/'.mb_ltrim($path, '/');
}

function testsTempBasePath(): string
{
    $token = getenv('TEST_TOKEN');

    return sys_get_temp_dir().'/laravel-sqlite-tests/'.($token !== false && $token !== '' ? $token : 'single');
}
