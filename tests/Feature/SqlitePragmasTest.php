<?php

declare(strict_types=1);

use Eznix86\LaravelSQLite\AppServiceProvider;
use Illuminate\Support\Facades\DB;

it('forces wal_autocheckpoint to zero when litestream is enabled', function (): void {
    config()->set('sqlite.litestream', true);

    (new AppServiceProvider(app()))->boot();

    expect(pragmaValue('wal_autocheckpoint'))->toBe(0);
});

it('applies configured wal_autocheckpoint when litestream is disabled', function (): void {
    config()->set('sqlite.litestream', false);
    config()->set('sqlite.pragmas.wal_autocheckpoint', 321);

    (new AppServiceProvider(app()))->boot();

    expect(pragmaValue('wal_autocheckpoint'))->toBe(321);
});

it('applies calculated cache size from megabytes', function (): void {
    config()->set('sqlite.pragmas.cache_size_mb', 32);

    (new AppServiceProvider(app()))->boot();

    expect(pragmaValue('cache_size'))->toBe(-32768);
});

it('applies pragmas to non default sqlite connections during boot', function (): void {
    config()->set('sqlite.litestream', false);
    config()->set('sqlite.pragmas.wal_autocheckpoint', 222);
    DB::purge('sqlite_two');

    (new AppServiceProvider(app()))->boot();

    $result = DB::connection('sqlite_two')->selectOne('PRAGMA wal_autocheckpoint');
    $value = (int) array_values((array) $result)[0];

    expect($value)->toBe(222);
});
