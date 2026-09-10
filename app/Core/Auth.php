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
            // Active company: the user's own tenant, or none yet for the
            // global super-admin (they pick one from the company list).
            Session::set('tenant_id', $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null);
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

    /** Middleware: require one of the given roles (super-admin always passes). */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (self::role() !== 'superadmin' && !in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Access denied']);
            exit;
        }
    }

    /** Write-tier check for views: hide buttons from read-only roles. */
    public static function canWrite(): bool
    {
        return in_array(self::role(), ['superadmin', 'admin', 'finance'], true);
    }

    /** Global platform administrator. */
    public static function isSuperAdmin(): bool
    {
        return self::role() === 'superadmin';
    }
}
