<?php

// Router script for `php -S localhost:8080 router.php` (development only).
// Serves real static files, everything else goes through the front controller.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // let the built-in server deliver the static asset
}

require __DIR__ . '/index.php';
