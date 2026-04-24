# Optimize SQLite For Laravel

![Optimize SQLite Banner](./art/optimize-sqlite.png)

Optimize SQLite for Laravel in production.

This package exists to give SQLite-first Laravel apps explicit control over performance PRAGMAs, maintenance commands, and migration-time file behavior.

## Features

- Multi SQLite Connection Support
- Configurable SQLite PRAGMAs
- Supports [Litestream Package](#litestream-support)
- Handles migration command startup to prepare missing SQLite files/directories.

## Requirements

- PHP `^8.3`
- Laravel `13.x` >= v2.0.0
- Laravel `12.x` <= v2.0.0

## Installation

```bash
composer require eznix86/laravel-sqlite
```

Publish the config file:

```bash
php artisan vendor:publish --tag=sqlite-config
```

## Configuration

Configuration lives in `config/sqlite.php`.

Key options:

- `sqlite.enabled` - enable/disable package PRAGMA application.
- `sqlite.litestream` - Litestream-safe mode (forces `wal_autocheckpoint=0`).
- `sqlite.pragmas.incremental_vacuum`
- `sqlite.pragmas.temp_store`
- `sqlite.pragmas.cache_size_mb`
- `sqlite.pragmas.mmap_size_mb`
- `sqlite.pragmas.wal_autocheckpoint`

Environment variable prefix: `SQLITE_...`

## Commands

### Vacuum

Run full `VACUUM` on SQLite connections:

```bash
php artisan sqlite:vacuum
php artisan sqlite:vacuum --connection=sqlite
```

Notes:

- Without `--connection`, it runs on all configured SQLite connections.
- If `sqlite.litestream=true`, the command is blocked by design.

### Show PRAGMAs

Display configuration and runtime PRAGMA values:

```bash
php artisan sqlite:show-pragmas
php artisan sqlite:show-pragmas sqlite
```

### Manually Cache / Clear PRAGMA SQL

```bash
php artisan sqlite:cache-pragmas
php artisan sqlite:clear-pragmas-cache
```

These are also connected to Laravel optimize commands:

- `php artisan optimize` -> caches PRAGMA SQL
- `php artisan optimize:clear` -> clears PRAGMA SQL cache

## Migration Behavior

The package listens for commands and prepares SQLite file connections automatically:

- For `migrate*` and `db:wipe`: ensures database files/directories exist (except `:memory:`).
- For `migrate:fresh`: removes the SQLite file and sidecars (`-wal`, `-shm`, `-journal`) first, then recreates the database file.
- If `--database=...` is provided, only that SQLite connection is targeted.

## Litestream Support

If you want to back up and replicate your SQLite database with Litestream.
Use [`eznix86/laravel-litestream`](https://github.com/eznix86/laravel-litestream):

- GitHub: https://github.com/eznix86/laravel-litestream
- Packagist: https://packagist.org/packages/eznix86/laravel-litestream


## Development

```bash
composer test:types
composer refactor
composer lint
composer lint:fix
composer test
```

Notes:

- This package currently uses feature-style tests; there is no dedicated `test:unit` script.

## License

MIT
