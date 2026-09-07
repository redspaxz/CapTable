<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private const IDLE_TIMEOUT = 1800; // 30 minutes

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? null) === 443
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
            session_name((string) App::config('session.name', 'captable_session'));
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $isHttps,
            ]);
            session_start();
        }

        // Idle timeout: expire authenticated sessions after inactivity
        $last = $_SESSION['_last_activity'] ?? null;
        if ($last !== null && is_numeric($last) && time() - (int) $last > self::IDLE_TIMEOUT) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
