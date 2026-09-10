<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Multi-tenancy context. The active tenant lives in the session: it is set
 * at login from the user's company and can be switched by the global
 * super-admin. All domain queries must scope rows to Tenancy::id().
 */
final class Tenancy
{
    /** CLI/script override (seeding, maintenance) where no session exists. */
    private static ?int $forced = null;

    /** Force the active tenant for command-line scripts. */
    public static function setForced(?int $tenantId): void
    {
        self::$forced = $tenantId;
    }

    /** Active tenant id, or null when the super-admin has not picked a company. */
    public static function id(): ?int
    {
        if (self::$forced !== null) {
            return self::$forced;
        }
        $id = Session::get('tenant_id');
        return is_numeric($id) ? (int) $id : null;
    }

    /** Middleware guard: private pages need an active tenant context. */
    public static function requireActive(): void
    {
        if (self::id() === null) {
            redirect('/tenants');
        }
    }

    /** Active tenant row (memoized), or null. */
    public static function current(): ?array
    {
        static $tenant = null;
        static $loadedFor = 0;
        $id = self::id();
        if ($id === null) {
            return null;
        }
        if ($tenant === null || $loadedFor !== $id) {
            $tenant = Database::one('SELECT * FROM tenants WHERE id = ?', [$id]);
            $loadedFor = $id;
        }
        return $tenant;
    }

    /** All tenants (super-admin company picker). */
    public static function all(): array
    {
        return Database::all('SELECT * FROM tenants WHERE is_active = 1 ORDER BY name');
    }

    /** Switch the active company (super-admin only). */
    public static function switchTo(int $tenantId): void
    {
        Auth::requireRole('superadmin');
        $tenant = Database::one('SELECT * FROM tenants WHERE id = ? AND is_active = 1', [$tenantId]);
        if (!$tenant) {
            throw new \InvalidArgumentException(__('Unknown company.'));
        }
        Session::start();
        session_regenerate_id(true);
        $_SESSION['tenant_id'] = (int) $tenant['id'];
    }

    /** Tenant id to use in queries; falls back to the first tenant. */
    public static function idOrFail(): int
    {
        $id = self::id();
        if ($id === null) {
            throw new \RuntimeException('No active tenant.');
        }
        return $id;
    }
}
