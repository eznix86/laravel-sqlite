<?php

declare(strict_types=1);

use Eznix86\LaravelSQLite\Listeners\PrepareSQLiteForMigrations;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

it('touches missing sqlite files before migrate commands', function (): void {
    $databasePath = temporaryFile('listener/migrate.sqlite');

    File::deleteDirectory(dirname($databasePath));

    config()->set('database.connections.sqlite.database', $databasePath);
    DB::purge('sqlite');

    new PrepareSQLiteForMigrations()->handle(commandStarting('migrate'));

    expect(File::exists($databasePath))->toBeTrue();
});

it('deletes sqlite files and sidecars for migrate fresh then recreates database file', function (): void {
    $databasePath = temporaryFile('listener/fresh.sqlite');

    File::ensureDirectoryExists(dirname($databasePath));
    File::put($databasePath, 'main');
    File::put($databasePath.'-wal', 'wal');
    File::put($databasePath.'-shm', 'shm');
    File::put($databasePath.'-journal', 'journal');

    config()->set('database.connections.sqlite.database', $databasePath);
    DB::purge('sqlite');

    new PrepareSQLiteForMigrations()->handle(commandStarting('migrate:fresh', 'sqlite'));

    expect(File::exists($databasePath))->toBeTrue()
        ->and(File::exists($databasePath.'-wal'))->toBeFalse()
        ->and(File::exists($databasePath.'-shm'))->toBeFalse()
        ->and(File::exists($databasePath.'-journal'))->toBeFalse();
});

it('respects database option during migrate fresh', function (): void {
    $databaseOne = temporaryFile('listener/one.sqlite');
    $databaseTwo = temporaryFile('listener/two.sqlite');

    File::ensureDirectoryExists(dirname($databaseOne));

    foreach ([$databaseOne, $databaseTwo] as $databasePath) {
        File::put($databasePath, 'main');
        File::put($databasePath.'-wal', 'wal');
        File::put($databasePath.'-shm', 'shm');
    }

    config()->set('database.connections.sqlite.database', $databaseOne);
    config()->set('database.connections.sqlite_two.database', $databaseTwo);
    DB::purge('sqlite');
    DB::purge('sqlite_two');

    new PrepareSQLiteForMigrations()->handle(commandStarting('migrate:fresh', 'sqlite_two'));

    expect(File::exists($databaseOne))->toBeTrue()
        ->and(File::exists($databaseOne.'-wal'))->toBeTrue()
        ->and(File::exists($databaseOne.'-shm'))->toBeTrue()
        ->and(File::exists($databaseTwo))->toBeTrue()
        ->and(File::exists($databaseTwo.'-wal'))->toBeFalse()
        ->and(File::exists($databaseTwo.'-shm'))->toBeFalse();
});

it('skips file operations for memory sqlite connections', function (): void {
    config()->set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');

    new PrepareSQLiteForMigrations()->handle(commandStarting('migrate:fresh', 'sqlite'));

    expect(File::exists(base_path(':memory:')))->toBeFalse();
});

it('does not delete sidecars for db wipe', function (): void {
    $databasePath = temporaryFile('listener/wipe.sqlite');

    File::ensureDirectoryExists(dirname($databasePath));
    File::put($databasePath.'-wal', 'wal');
    File::put($databasePath.'-shm', 'shm');

    config()->set('database.connections.sqlite.database', $databasePath);
    DB::purge('sqlite');

    new PrepareSQLiteForMigrations()->handle(commandStarting('db:wipe', 'sqlite'));

    expect(File::exists($databasePath))->toBeTrue()
        ->and(File::exists($databasePath.'-wal'))->toBeTrue()
        ->and(File::exists($databasePath.'-shm'))->toBeTrue();
});

it('does not touch sqlite files for non mutating migration commands', function (): void {
    $databasePath = temporaryFile('listener/status.sqlite');

    File::deleteDirectory(dirname($databasePath));

    config()->set('database.connections.sqlite.database', $databasePath);
    DB::purge('sqlite');

    new PrepareSQLiteForMigrations()->handle(commandStarting('migrate:status'));

    expect(File::exists($databasePath))->toBeFalse();
});

it('blocks path traversal when sqlite path uses windows separators', function (): void {
    config()->set('database.connections.sqlite.database', '..\\listener\\blocked.sqlite');
    DB::purge('sqlite');

    expect(fn () => new PrepareSQLiteForMigrations()->handle(commandStarting('migrate')))
        ->toThrow(InvalidArgumentException::class, 'Path traversal is not allowed for SQLite database paths.');
});

function commandStarting(string $command, ?string $database = null): CommandStarting
{
    $definition = new InputDefinition([
        new InputOption('database', null, InputOption::VALUE_OPTIONAL),
    ]);

    $arguments = [];

    if ($database !== null) {
        $arguments['--database'] = $database;
    }

    return new CommandStarting(
        $command,
        new ArrayInput($arguments, $definition),
        new NullOutput(),
    );
}
