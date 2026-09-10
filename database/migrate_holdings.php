<?php

/**
 * Migration: add the share_holdings projection to an existing install.
 *
 * Usage: php database/migrate_holdings.php   (reads DB_DRIVER + .env like the app)
 *
 * The share_holdings table was introduced with the ownership read-path
 * optimization. Deployments that predate it must run this once so the app
 * stops falling back to (and never 500s on) the missing table:
 *
 *   1. CREATE TABLE IF NOT EXISTS share_holdings (driver-specific DDL)
 *   2. Add the covering index for as-of reconstructions (best effort)
 *   3. Backfill the projection from the append-only share_movements
 *      register, using the same fold semantics ShareService maintains
 *      incrementally: issuances/transfer_in add to the holder,
 *      transfer_out subtracts from the seller and adds to the buyer.
 *
 * Idempotent: safe to re-run (the backfill replaces the projection).
 * Exits non-zero on real errors so it can be wired into deploys/CI.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Database;

App::init(BASE_PATH . '/config/config.php');

$driver = App::config('db.driver');

// --- 1. Create the projection table ---------------------------------------
if ($driver === 'sqlite') {
    Database::pdo()->exec(
        'CREATE TABLE IF NOT EXISTS share_holdings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            shareholder_id INTEGER NOT NULL REFERENCES shareholders(id),
            share_class_id INTEGER NOT NULL REFERENCES share_classes(id),
            quantity INTEGER NOT NULL DEFAULT 0,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            UNIQUE (shareholder_id, share_class_id)
        )'
    );
} else {
    Database::pdo()->exec(
        'CREATE TABLE IF NOT EXISTS share_holdings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shareholder_id INT NOT NULL,
            share_class_id INT NOT NULL,
            quantity BIGINT UNSIGNED NOT NULL DEFAULT 0,
            tenant_id INT NOT NULL DEFAULT 1,
            UNIQUE KEY uq_shareholder_class (shareholder_id, share_class_id),
            FOREIGN KEY (shareholder_id) REFERENCES shareholders(id),
            FOREIGN KEY (share_class_id) REFERENCES share_classes(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}
echo "share_holdings : table prete\n";

// --- 2. Covering index for as-of reconstructions (best effort) ------------
// MySQL has no CREATE INDEX IF NOT EXISTS, so swallow duplicate-key errors.
foreach (['idx_movements_date', 'idx_movements_class_date', 'idx_movements_cover'] as $idx) {
    $ddl = match ($idx) {
        'idx_movements_date' => 'CREATE INDEX idx_movements_date ON share_movements (movement_date)',
        'idx_movements_class_date' => 'CREATE INDEX idx_movements_class_date ON share_movements (share_class_id, movement_date)',
        'idx_movements_cover' => 'CREATE INDEX idx_movements_cover ON share_movements (movement_date, movement_type, shareholder_id, counterparty_id, share_class_id, quantity)',
    };
    try {
        Database::pdo()->exec($ddl);
        echo "index {$idx} : cree\n";
    } catch (\Throwable $e) {
        // Duplicate key name -> already present, which is what we want.
        $state = $e instanceof \PDOException ? (string) $e->getCode() : '';
        if (str_contains($e->getMessage(), 'already exists') || $state === '42S21' || $state === '42000') {
            echo "index {$idx} : deja present\n";
        } else {
            fwrite(STDERR, "index {$idx} : ERREUR - {$e->getMessage()}\n");
        }
    }
}

// --- 3. Backfill from the register ------------------------------------------
$moved = Database::transaction(function () use ($driver): int {
    // Fresh fold each run: replaces any drift in the stored projection.
    Database::execute('DELETE FROM share_holdings');

    $sql = "
        INSERT INTO share_holdings (shareholder_id, share_class_id, quantity, tenant_id)
        SELECT shareholder_id, share_class_id, SUM(qty) AS quantity, tenant_id
        FROM (
            SELECT shareholder_id, share_class_id, tenant_id,
                   CASE WHEN movement_type IN ('issuance','transfer_in') THEN CAST(quantity AS SIGNED)
                        WHEN movement_type = 'transfer_out' THEN -CAST(quantity AS SIGNED) ELSE 0 END AS qty
            FROM share_movements
            UNION ALL
            SELECT counterparty_id AS shareholder_id, share_class_id, tenant_id, CAST(quantity AS SIGNED) AS qty
            FROM share_movements WHERE movement_type = 'transfer_out' AND counterparty_id > 0
        ) movements
        GROUP BY shareholder_id, share_class_id, tenant_id
        HAVING SUM(qty) > 0";
    Database::pdo()->exec($sql);
    return (int) Database::scalar('SELECT COUNT(*) FROM share_holdings');
});

$total = (int) Database::scalar('SELECT COALESCE(SUM(quantity), 0) FROM share_holdings');
echo "backfill : {$moved} ligne(s) de projection, {$total} titre(s) au total\n";

if ($moved > 0) {
    echo "Migration terminee. La projection est a nouveau coherente avec le registre.\n";
} else {
    echo "Registre vide : aucune projection a construire (rien a migrer).\n";
}