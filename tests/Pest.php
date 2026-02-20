<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit');

function pragmaValue(string $pragma): int
{
    $result = DB::select("PRAGMA {$pragma};");

    return (int) array_values((array) $result[0])[0];
}

function temporaryFile(string $path): string
{
    return sys_get_temp_dir().'/laravel-sqlite-tests/'.mb_ltrim($path, '/');
}
