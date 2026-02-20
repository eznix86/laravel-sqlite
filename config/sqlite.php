<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable
    |--------------------------------------------------------------------------
    |
    | Master switch. When false, package does nothing.
    |
    */
    'enabled' => env('SQLITE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Litestream Mode
    |--------------------------------------------------------------------------
    |
    | When true, package applies Litestream-safe behavior internally.
    | Current rule: wal_autocheckpoint is forced to 0.
    |
    | false => normal SQLite behavior from configured values
    | true  => Litestream profile (force wal_autocheckpoint=0)
    |
    */
    'litestream' => env('SQLITE_LITESTREAM', false),

    /*
    |--------------------------------------------------------------------------
    | Pragmas
    |--------------------------------------------------------------------------
    */
    'pragmas' => [

        /*
        | cache_path
        |
        | Optional absolute path for the generated PRAGMA SQL cache file.
        | Leave null to use bootstrap/cache/sqlite-pragmas.php.
        */
        'cache_path' => env('SQLITE_PRAGMAS_CACHE_PATH'),

        /*
        | incremental_vacuum
        |
        | Utility:
        | - Reclaims free pages gradually without full VACUUM.
        |
        | Values:
        | - true  => execute PRAGMA incremental_vacuum;
        | - false => skip it
        */
        'incremental_vacuum' => env('SQLITE_INCREMENTAL_VACUUM', true),

        /*
        | temp_store
        |
        | Utility:
        | - Where SQLite stores temporary structures (sorts/temp tables).
        |
        | Values:
        | - DEFAULT => SQLite decides (compile/runtime defaults)
        | - FILE    => temp data on disk (lower RAM, slower)
        | - MEMORY  => temp data in RAM (faster, more RAM usage)
        */
        'temp_store' => env('SQLITE_TEMP_STORE', 'MEMORY'),

        /*
        | cache_size_mb
        |
        | Utility:
        | - Target size of SQLite page cache to reduce disk reads.
        |
        | Meaning:
        | - Integer in MB, e.g. 64 means 64 MB target cache.
        | - Package converts to SQLite format:
        |   PRAGMA cache_size = -(cache_size_mb * 1024);
        |   (negative means KiB units)
        |
        | Examples:
        | - 16  => ~16 MB cache
        | - 64  => ~64 MB cache
        | - 128 => ~128 MB cache
        */
        'cache_size_mb' => (int) env('SQLITE_CACHE_SIZE_MB', 64),

        /*
        | mmap_size_mb
        |
        | Utility:
        | - Enables memory-mapped I/O window for faster reads.
        |
        | Meaning:
        | - Integer in MB, converted to bytes:
        |   PRAGMA mmap_size = mmap_size_mb * 1024 * 1024;
        |
        | Values:
        | - 0   => disable memory-mapped I/O
        | - 64  => 64 MB mapping
        | - 256 => 256 MB mapping
        */
        'mmap_size_mb' => (int) env('SQLITE_MMAP_SIZE_MB', 64),

        /*
        | wal_autocheckpoint
        |
        | Utility:
        | - Number of WAL pages written before auto-checkpoint.
        |
        | Meaning:
        | - Lower value => more frequent checkpoints (smaller WAL, more overhead)
        | - Higher value => less frequent checkpoints (larger WAL, less checkpoint overhead)
        | - 0 => disable auto-checkpoint
        |
        | Note:
        | - If litestream=true, package should force this to 0 internally.
        */
        'wal_autocheckpoint' => (int) env('SQLITE_WAL_AUTOCHECKPOINT', 1000),
    ],
];
