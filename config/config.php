<?php

return [
    'app' => [
        'name' => 'CapTable — T&Tech Consulting Group',
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => getenv('APP_DEBUG') === 'true',
        'base_url' => getenv('APP_BASE_URL') ?: '',
    ],
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'captable',
        'user' => getenv('DB_USER') ?: 'captable',
        'pass' => getenv('DB_PASS') ?: '',
        // Used when DB_DRIVER=sqlite (handy for local dev/testing)
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: __DIR__ . '/../database/captable.sqlite',
    ],
    'session' => [
        'name' => 'captable_session',
    ],
];
