<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user && password_verify($password, $user['password_hash'])) {
            Session::set('user_id', (int) $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_role', $user['role']);
            session_regenerate_id(true);
            return true;
        }
        return false;
    }

    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) {
            return null;
        }
        return [
            'id' => $id,
            'name' => Session::get('user_name'),
            'role' => Session::get('user_role'),
        ];
    }

    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function role(): ?string
    {
        return Session::get('user_role');
    }

    /** Middleware: require a logged-in user. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    /** Middleware: require one of the given roles. */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Accès refusé']);
            exit;
        }
    }
}
