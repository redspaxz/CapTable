<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Base path the app is served under. Explicit APP_BASE_URL wins;
     * otherwise derived from the front controller's location (works when
     * the whole repo is deployed inside a web subdirectory, e.g. /ctms).
     */
    public static function basePath(): string
    {
        $configured = rtrim((string) App::config('app.base_url'), '/');
        if ($configured !== '') {
            return $configured;
        }
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = str_replace('\\', '/', dirname(dirname($script)));
        return rtrim($base, '/');
    }

    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return $uri ?: '/';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $value = self::input($key);
        return is_scalar($value) ? (string) $value : $default;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }
}
