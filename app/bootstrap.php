<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

require BASE_PATH . '/app/helpers.php';
require BASE_PATH . '/app/global_helpers.php';

// Application timezone (GMT+1 / Africa/Douala by default; override with
// APP_TIMEZONE in .env). Set explicitly so php.ini and server defaults
// (local dev: Europe/Berlin, production: UTC) never skew recorded dates.
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Douala');

// Load .env if present (shared hosting: no shell environment variables).
// Real environment variables always win over file values.
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}
