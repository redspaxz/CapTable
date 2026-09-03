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

Database::execute('DELETE FROM share_movements');
Database::execute('DELETE FROM share_issuances');
Database::execute('DELETE FROM share_transfers');
Database::execute('DELETE FROM share_certificates');
Database::execute('DELETE FROM documents');
Database::execute('DELETE FROM shareholders');
Database::execute('DELETE FROM share_classes');
Database::execute('DELETE FROM users');
Database::execute('DELETE FROM settings');

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
    'INSERT INTO settings (company_name, legal_form, rccm, niu, head_office) VALUES (?,?,?,?,?)',
    ['T&Tech Consulting Group', 'SA', 'RCCM/DLA/2020/B/1234', 'M092511234567X', 'Douala, Cameroun']
);

// Share class: 10 000 XAF nominal, 100 000 authorized
Database::execute(
    'INSERT INTO share_classes (code, name, nominal_value, shares_authorized, rights) VALUES (?,?,?,?,?)',
    ['ORD', 'Actions ordinaires', 10000, 100000, 'Droit de vote, dividende, boni de liquidation']
);

$people = [
    ['individual', 'Edmund Alomepe', 'CNI', '1122334455', 'Douala', 'edmund@ttechgroup.cm', '+237 6 90 00 00 01'],
    ['individual', 'Marie Ngo Bassong', 'CNI', '2233445566', 'Yaoundé', 'marie@ttechgroup.cm', '+237 6 90 00 00 02'],
    ['corporate', 'T&Tech Holding SARL', 'RC', 'RC/DLA/2018/789', 'Douala', 'holding@ttechgroup.cm', '+237 6 90 00 00 03'],
];
foreach ($people as [$type, $name, $idType, $idNumber, $city, $email, $phone]) {
    Database::execute(
        'INSERT INTO shareholders (type, name, id_type, id_number, address, email, phone) VALUES (?,?,?,?,?,?,?)',
        [$type, $name, $idType, $idNumber, $city, $email, $phone]
    );
}

$classId = (int) Database::scalar('SELECT id FROM share_classes WHERE code = ?', ['ORD']);
$service = new ShareService();

// Constitutive issuance: capital 100 000 000 XAF = 10 000 shares
$service->issue($classId, 1, 5000, 'cash', '2020-03-15', 'AGC-2020-001');
$service->issue($classId, 2, 3000, 'cash', '2020-03-15', 'AGC-2020-002');
$service->issue($classId, 3, 2000, 'in_kind', '2020-03-15', 'AGC-2020-003');

// A transfer in 2024: Marie cedes 500 shares to T&Tech Holding
$service->transfer($classId, 2, 3, 500, '2024-06-20', 'ACT-20240620-101');

echo "Seed OK — admin@ttechgroup.cm / password\n";
