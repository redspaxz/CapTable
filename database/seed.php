<?php

/**
 * Seeds demo data for T&Tech Consulting Group.
 * Usage: php database/seed.php
 * Reads DB_DRIVER (mysql|sqlite) from the environment like the app.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;
use App\Modules\Shares\ShareService;

// Load config from the same file the front controller uses (honors env vars).
App\Core\App::init(BASE_PATH . '/config/config.php');

$driver = App\Core\App::config('db.driver');
if ($driver === 'sqlite') {
    $dbPath = App\Core\App::config('db.sqlite_path');
    if (!is_file($dbPath)) {
        fwrite(STDERR, "SQLite DB missing. Run: sqlite $dbPath < database/schema.sqlite.sql\n");
        exit(1);
    }
}

// Multi-tenant seeding: everything below the demo block belongs to
// tenant 1; the CLI has no session, so force the tenant context.
\App\Core\Tenancy::setForced(1);

Database::execute('DELETE FROM share_movements');
Database::execute('DELETE FROM share_holdings');
Database::execute('DELETE FROM share_issuances');
Database::execute('DELETE FROM share_transfers');
Database::execute('DELETE FROM share_certificates');
Database::execute('DELETE FROM documents');
Database::execute('DELETE FROM shareholders');
Database::execute('DELETE FROM share_classes');
Database::execute('DELETE FROM users');
Database::execute('DELETE FROM settings');
Database::execute('DELETE FROM tenants WHERE id > 1');
Database::execute('DELETE FROM beneficial_owners');
Database::execute('DELETE FROM convertibles');
Database::execute('DELETE FROM option_grants');
Database::execute('DELETE FROM option_exercises');
if ($driver === 'sqlite') {
    Database::execute('DELETE FROM sqlite_sequence');
}

// Users (password: "password" for all demo accounts)
Database::execute(
    'INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)',
    ['Administrateur', 'admin@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'admin']
);
Database::execute(
    'INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)',
    ['Direction financière', 'finance@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'finance']
);
Database::execute(
    'INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)',
    ['Auditeur lecture seule', 'viewer@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'viewer']
);

// Portal employee demo profile (password: "password") — inserted AFTER the
// founders so demo ids stay stable: 1 Edmund, 2 Marie, 3 Holding, 4 Paul.

Database::execute(
    'INSERT INTO settings (company_name, legal_form, rccm, niu, head_office, fmv_per_share, secondary_currency, fx_rate, option_tax_rate) VALUES (?,?,?,?,?,?,?,?,?)',
    ['T&Tech Consulting Group', 'SA', 'RCCM/DLA/2020/B/1234', 'M092511234567X', 'Douala, Cameroun', 10000, 'EUR', 655.957, 0.30]
);

// Share class: 10 000 XAF nominal, 100 000 authorized
Database::execute(
    'INSERT INTO share_classes (code, name, nominal_value, shares_authorized, rights) VALUES (?,?,?,?,?)',
    ['ORD', 'Ordinary shares', 10000, 100000, 'Voting rights, dividends, liquidation bonus']
);

$people = [
    ['individual', 'Edmund Alomepe', 'CNI', '1122334455', 'Douala', 'edmund@ttechgroup.cm', '+237 6 90 00 00 01'],
    ['individual', 'Marie Ngo Bassong', 'CNI', '2233445566', 'Yaoundé', 'marie@ttechgroup.cm', '+237 6 90 00 00 02'],
    ['corporate', 'T&Tech Holding SARL', 'RC', 'RC/DLA/2018/789', 'Douala', 'holding@ttechgroup.cm', '+237 6 90 00 00 03'],
];
$shareholderIds = [];
foreach ($people as [$type, $name, $idType, $idNumber, $city, $email, $phone]) {
    Database::execute(
        'INSERT INTO shareholders (type, name, id_type, id_number, address, email, phone) VALUES (?,?,?,?,?,?,?)',
        [$type, $name, $idType, $idNumber, $city, $email, $phone]
    );
    $shareholderIds[] = (int) Database::lastId();
}
[$edmund, $marie, $holding] = $shareholderIds;

// Link the admin account to the founder profile (stakeholder portal)
Database::execute(
    'UPDATE users SET shareholder_id = ?, stakeholder_role = ? WHERE email = ?',
    [$edmund, 'founder', 'admin@ttechgroup.cm']
);

// Employee shareholder + portal account + demo option grant
Database::execute(
    'INSERT INTO shareholders (type, name, id_type, id_number, address, email, phone) VALUES (?,?,?,?,?,?,?)',
    ['individual', 'Paul Ayissi', 'CNI', '9988776655', 'Douala', 'paul@ttechgroup.cm', '+237 6 90 00 00 04']
);
$employeeId = (int) Database::lastId();
Database::execute(
    'INSERT INTO users (name, email, password_hash, role, shareholder_id, stakeholder_role) VALUES (?,?,?,?,?,?)',
    ['Paul Ayissi (employé)', 'employee@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'viewer', $employeeId, 'employee']
);

$classId = (int) Database::scalar('SELECT id FROM share_classes WHERE code = ?', ['ORD']);
$service = new ShareService();

// Constitutive issuance: capital 100 000 000 XAF = 10 000 shares
$service->issue($classId, $edmund, 5000, 'cash', '2020-03-15', 'AGC-2020-001');
$service->issue($classId, $marie, 3000, 'cash', '2020-03-15', 'AGC-2020-002');
$service->issue($classId, $holding, 2000, 'in_kind', '2020-03-15', 'AGC-2020-003');

// A transfer in 2024: Marie cedes 500 shares to T&Tech Holding
$service->transfer($classId, $marie, $holding, 500, '2024-06-20', 'ACT-20240620-101');

// ESOP demo: 600 options to the employee, granted 2024-01-01, 48-month
// vesting with a 12-month cliff (400 vested as of 2026-09).
(new \App\Modules\Options\OptionService())->grant(
    $employeeId, $classId, 600, 5000, '2024-01-01', 48, 12,
    'Plan d\'intéressement — démonstration'
);

// v3 demo: DFI convertible note + auditor (CAC) read-only account
Database::execute(
    'INSERT INTO convertibles (type, holder, principal_amount, currency, discount_pct, valuation_cap, issue_date, notes) VALUES (?,?,?,?,?,?,?,?)',
    ['OCA', 'Atlantique Ventures Fund', 50000000, 'XAF', 20.0, 750000000, '2025-06-30', 'Obligation convertible — tour d\'amorçage']
);
Database::execute(
    'INSERT INTO users (name, email, password_hash, role, stakeholder_role) VALUES (?,?,?,?,?)',
    ['Commissaire aux Comptes', 'auditor@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'auditor', 'board']
);

// Multi-tenancy: the demo above belongs to tenant 1 (default column value).
// Add the global super-admin, then a second company to prove isolation.
\App\Core\Tenancy::setForced(1);

Database::execute(
    'INSERT INTO users (name, email, password_hash, role, tenant_id) VALUES (?,?,?,?,NULL)',
    ['Super Admin', 'super@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'superadmin']
);

Database::execute('INSERT INTO tenants (id, name) VALUES (2, ?)', ['Ngoola Ventures SARL']);
$tenant2 = (int) Database::lastId();
$tenant2 = 2;
Database::execute(
    'INSERT INTO settings (tenant_id, company_name, legal_form, rccm, head_office) VALUES (?,?,?,?,?)',
    [$tenant2, 'Ngoola Ventures SARL', 'SARL', 'CM/DLA/2026/B/1234', 'Douala']
);
Database::execute(
    'INSERT INTO users (name, email, password_hash, role, tenant_id) VALUES (?,?,?,?,?)',
    ['Admin Ngoola', 'admin2@ttechgroup.cm', password_hash('password', PASSWORD_DEFAULT), 'admin', $tenant2]
);
Database::execute(
    'INSERT INTO share_classes (id, code, name, nominal_value, shares_authorized, rights, tenant_id) VALUES (?,?,?,?,?,?,?)',
    [901, 'ORD', 'Parts sociales ordinaires', 5000, 100000, 'Parts sociales - SARL', $tenant2]
);
$class2 = 901;
Database::execute(
    'INSERT INTO shareholders (id, type, name, id_type, id_number, email, tenant_id) VALUES (?,?,?,?,?,?,?)',
    [901, 'individual', 'Aicha Bello', 'CNI', 'NG-001', 'aicha@ngoola.cm', $tenant2]
);
$shA = 901;
Database::execute(
    'INSERT INTO shareholders (id, type, name, id_type, id_number, email, tenant_id) VALUES (?,?,?,?,?,?,?)',
    [902, 'individual', 'Ibrahim Sali', 'CNI', 'NG-002', 'ibrahim@ngoola.cm', $tenant2]
);
$shB = 902;
Database::execute(
    'INSERT INTO share_issuances (id, share_class_id, shareholder_id, quantity, apport_type, issuance_date, reference, tenant_id) VALUES (?,?,?,?,?,?,?,?)',
    [901, $class2, $shA, 1000, 'cash', '2026-01-10', 'AG-2026-001', $tenant2]
);
Database::execute(
    'INSERT INTO share_movements (id, movement_type, share_class_id, shareholder_id, counterparty_id, quantity, movement_date, reference, tenant_id) VALUES (?,"issuance",?,?,NULL,?,?,?,?)',
    [901, $class2, $shA, 1000, '2026-01-10', 'AG-2026-001', $tenant2]
);
Database::execute(
    'INSERT INTO share_issuances (id, share_class_id, shareholder_id, quantity, apport_type, issuance_date, reference, tenant_id) VALUES (?,?,?,?,?,?,?,?)',
    [902, $class2, $shB, 600, 'cash', '2026-01-10', 'AG-2026-002', $tenant2]
);
Database::execute(
    'INSERT INTO share_movements (id, movement_type, share_class_id, shareholder_id, counterparty_id, quantity, movement_date, reference, tenant_id) VALUES (?,"issuance",?,?,NULL,?,?,?,?)',
    [902, $class2, $shB, 600, '2026-01-10', 'AG-2026-002', $tenant2]
);
Database::execute(
    'INSERT INTO share_holdings (shareholder_id, share_class_id, quantity, tenant_id) VALUES (?,?,?,?)',
    [$shA, $class2, 1000, $tenant2]
);
Database::execute(
    'INSERT INTO share_holdings (shareholder_id, share_class_id, quantity, tenant_id) VALUES (?,?,?,?)',
    [$shB, $class2, 600, $tenant2]
);

// Tenant-2 rows use high explicit ids (901+) so the T&Tech ids UAT relies on
// stay stable; on SQLite, rewind the AUTOINCREMENT sequences to the tenant-1
// max so later app-created T&Tech rows keep their historical numbering.
if ($driver === 'sqlite') {
    foreach (['share_classes', 'shareholders', 'share_issuances', 'share_movements', 'share_holdings'] as $seqTable) {
        Database::execute(
            'UPDATE sqlite_sequence SET seq = (SELECT COALESCE(MAX(id), 0) FROM ' . $seqTable . ' WHERE tenant_id = 1) WHERE name = ?',
            [$seqTable]
        );
    }
}
echo "Seed OK - admin@ttechgroup.cm / password - admin2@ttechgroup.cm / password (Ngoola) - super@ttechgroup.cm / password\n";
