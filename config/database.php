<?php

use Illuminate\Support\Str;

$sqliteDatabasePath = env('DB_DATABASE', database_path('database.sqlite'));

if (is_string($sqliteDatabasePath)) {
    $trimmedSqliteDatabasePath = trim($sqliteDatabasePath);

    $isAbsoluteWindowsPath = preg_match('/^[A-Za-z]:[\\\\\\/]/', $trimmedSqliteDatabasePath) === 1;
    $isUncPath = str_starts_with($trimmedSqliteDatabasePath, '\\\\');
    $isUnixAbsolutePath = str_starts_with($trimmedSqliteDatabasePath, '/');
    $isSpecialSqlitePath = in_array($trimmedSqliteDatabasePath, [':memory:', ''], true);

    if (! $isAbsoluteWindowsPath && ! $isUncPath && ! $isUnixAbsolutePath && ! $isSpecialSqlitePath) {
        $sqliteDatabasePath = base_path($trimmedSqliteDatabasePath);
    }
}

return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $sqliteDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
    ],
    'migrations' => 'migrations',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel'), '_').'_database_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DB', 0),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_CACHE_DB', 1),
        ],
    ],
];
