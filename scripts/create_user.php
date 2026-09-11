<?php

/**
 * Create (or update) a login account — the safe way to provision users on
 * production, where database/seed.php must never run (it wipes domain data).
 *
 * Usage:
 *   php scripts/create_user.php email "Full name" password [role] [tenant_id]
 *
 *   role       admin (default) | finance | viewer | auditor | superadmin
 *   tenant_id  company id (default: first tenant); "none" for a super-admin
 *
 * Idempotent: an existing e-mail is updated (name, password, role, tenant).
 * Never touches shareholders, movements or any other table.
 *
 * Examples:
 *   php scripts/create_user.php admin@ttechgroup.cm "Administrateur" 'S3cret!' admin 1
 *   php scripts/create_user.php super@ttechgroup.cm "Super Admin" 'S3cret!' superadmin none
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Database;

App::init(BASE_PATH . '/config/config.php');

const ROLES = ['admin', 'finance', 'viewer', 'auditor', 'superadmin'];

if ($argc < 4) {
    fwrite(STDERR, "Usage: php scripts/create_user.php email \"Full name\" password [role] [tenant_id]\n");
    exit(1);
}
[, $email, $name, $password] = $argv;
$role = strtolower($argv[4] ?? 'admin');
if (!in_array($role, ROLES, true)) {
    fwrite(STDERR, "Invalid role '$role'. Valid: " . implode(', ', ROLES) . "\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid e-mail '$email'.\n");
    exit(1);
}

$tenantArg = $argv[5] ?? null;
if ($role === 'superadmin' && $tenantArg === null) {
    $tenantArg = 'none';
}
$tenantId = null;
if ($tenantArg !== null && strtolower($tenantArg) !== 'none') {
    $tenantId = (int) $tenantArg;
    $tenant = Database::one('SELECT id FROM tenants WHERE id = ?', [$tenantId]);
    if (!$tenant) {
        fwrite(STDERR, "Tenant $tenantId not found.\n");
        exit(1);
    }
} elseif ($tenantId === null && $role !== 'superadmin') {
    $first = Database::one('SELECT id FROM tenants ORDER BY id LIMIT 1');
    if (!$first) {
        fwrite(STDERR, "No tenant exists yet — create one first (tenants table).\n");
        exit(1);
    }
    $tenantId = (int) $first['id'];
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = Database::one('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    Database::execute(
        'UPDATE users SET name = ?, password_hash = ?, role = ?, tenant_id = ? WHERE id = ?',
        [$name, $hash, $role, $tenantId, (int) $existing['id']]
    );
    echo "Updated existing user $email (id " . (int) $existing['id'] . ", role $role, tenant " . ($tenantId ?? 'none') . ")\n";
} else {
    Database::execute(
        'INSERT INTO users (name, email, password_hash, role, tenant_id) VALUES (?,?,?,?,?)',
        [$name, $email, $hash, $role, $tenantId]
    );
    echo "Created user $email (id " . Database::lastId() . ", role $role, tenant " . ($tenantId ?? 'none') . ")\n";
}
