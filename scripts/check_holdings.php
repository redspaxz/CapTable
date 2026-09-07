<?php

/**
 * Consistency guard for the share_holdings projection.
 *
 * Usage: DB_DRIVER=sqlite php scripts/check_holdings.php
 *
 * Recomputes the projection from the append-only share_movements register
 * (same fold semantics OwnershipService applies for as-of reads) and
 * asserts it matches the share_holdings table that ShareService maintains
 * incrementally on every write. Exits non-zero on any drift — run it as
 * part of CI/UAT or after manual SQL fixes.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Database;

App::init(BASE_PATH . '/config/config.php');

$projected = [];
foreach (Database::all(
    "SELECT shareholder_id, share_class_id, SUM(qty) AS quantity FROM (
         SELECT shareholder_id, share_class_id,
                CASE WHEN movement_type IN ('issuance','transfer_in') THEN CAST(quantity AS SIGNED)
                     WHEN movement_type = 'transfer_out' THEN -CAST(quantity AS SIGNED) ELSE 0 END AS qty
         FROM share_movements
         UNION ALL
         SELECT counterparty_id AS shareholder_id, share_class_id, CAST(quantity AS SIGNED) AS qty
         FROM share_movements WHERE movement_type = 'transfer_out' AND counterparty_id > 0
     ) movements
     GROUP BY shareholder_id, share_class_id
     HAVING SUM(qty) > 0"
) as $row) {
    $projected[(int) $row['shareholder_id'] . ':' . (int) $row['share_class_id']] = (int) $row['quantity'];
}

$stored = [];
foreach (Database::all('SELECT shareholder_id, share_class_id, quantity FROM share_holdings') as $row) {
    $stored[(int) $row['shareholder_id'] . ':' . (int) $row['share_class_id']] = (int) $row['quantity'];
}

if ($projected == $stored) {
    echo 'OK — share_holdings coherente avec le registre (' . count($stored) . " ligne(s))\n";
    exit(0);
}

echo "DRIFT detecte entre share_holdings et le registre :\n";
$keys = array_unique(array_merge(array_keys($projected), array_keys($stored)));
$shown = 0;
foreach ($keys as $key) {
    $a = $projected[$key] ?? 'ABSENT';
    $b = $stored[$key] ?? 'ABSENT';
    if ($a !== $b) {
        echo "  {$key} : registre={$a} projection={$b}\n";
        if (++$shown >= 10) {
            echo "  … (divergences supplementaires masquees)\n";
            break;
        }
    }
}
exit(1);