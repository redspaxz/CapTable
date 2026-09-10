<?php

/**
 * Benchmark for OwnershipService read paths (cap table, history, waterfall).
 *
 * Usage: php scripts/bench_ownership.php [--movements=100000]
 *
 * Builds a throwaway SQLite database (in the system temp dir) with a
 * synthetic share register, materializes the share_holdings projection the
 * way ShareService maintains it incrementally, and times the read methods,
 * so large registers can be profiled without touching the real database.
 */

declare(strict_types=1);

$movements = 100000;
foreach ($argv as $arg) {
    if (preg_match('#^--movements=(\d+)$#', $arg, $m)) {
        $movements = (int) $m[1];
    }
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

// CLI: no session, pin the demo tenant for OwnershipService.
\App\Core\Tenancy::setForced(1);

use App\Core\App;
use App\Core\Database;
use App\Modules\CapTable\OwnershipService;

$dbPath = sys_get_temp_dir() . '/captable_bench.sqlite';
@unlink($dbPath);
putenv('DB_DRIVER=sqlite');
putenv('DB_SQLITE_PATH=' . $dbPath);
App::init(BASE_PATH . '/config/config.php');

$pdo = Database::pdo();
$pdo->exec(file_get_contents(BASE_PATH . '/database/schema.sqlite.sql'));

// ---- Synthetic register -----------------------------------------------------
$pdo->beginTransaction();
$stmtS = $pdo->prepare(
    'INSERT INTO shareholders (type, name, id_type, id_number, email) VALUES (?,?,?,?,?)'
);
$shareholderIds = [];
for ($i = 1; $i <= 100; $i++) {
    $stmtS->execute(['individual', "SH-$i", 'CNI', "ID$i", "sh$i@example.com"]);
    $shareholderIds[] = (int) $pdo->lastInsertId();
}
$stmtC = $pdo->prepare(
    'INSERT INTO share_classes (code, name, nominal_value, shares_authorized, rights,
        liquidation_multiplier, liquidation_priority, participating) VALUES (?,?,?,?,?,?,?,?)'
);
$classIds = [];
foreach ([
    ['ORD', 'Actions ordinaires', 10000, 1.0, 100, 1],
    ['PREF', 'Actions privilegiees', 15000, 1.5, 1, 0],
    ['FND', 'Actions fondateur', 50000, 1.0, 2, 1],
] as [$code, $name, $nominal, $mult, $priority, $participating]) {
    $stmtC->execute([$code, $name, $nominal, 1000000, 'Droits', $mult, $priority, $participating]);
    $classIds[] = (int) $pdo->lastInsertId();
}
$stmtM = $pdo->prepare(
    'INSERT INTO share_movements (movement_type, share_class_id, shareholder_id, counterparty_id,
        quantity, movement_date, reference) VALUES (?,?,?,?,?,?,?)'
);
$dates = [];
for ($y = 2020; $y <= 2026; $y++) {
    for ($m = 1; $m <= 12; $m++) {
        $dates[] = sprintf('%04d-%02d-15', $y, $m);
    }
}
$count = 0;
$bal = [];
// Initial issuances to every shareholder in every class
foreach ($shareholderIds as $sid) {
    foreach ($classIds as $cid) {
        $qty = 200 + (($sid + $cid) % 1800);
        $stmtM->execute(['issuance', $cid, $sid, null, $qty, '2020-03-15', "ISSUE-$count"]);
        $bal[$sid][$cid] = $qty;
        $count++;
    }
}
// Availability-respecting transfers (ShareService blocks overselling, so
// the register must keep every net holding >= 0 for the projection to
// conserve class totals).
mt_srand(42);
$n = count($shareholderIds);
while ($count < $movements) {
    $seller = $shareholderIds[mt_rand(0, $n - 1)];
    $cid = $classIds[mt_rand(0, 2)];
    $avail = $bal[$seller][$cid] ?? 0;
    if ($avail <= 0) {
        continue;
    }
    do {
        $buyer = $shareholderIds[mt_rand(0, $n - 1)];
    } while ($buyer === $seller);
    $qty = mt_rand(1, min(500, $avail));
    $stmtM->execute([
        'transfer_out', $cid, $seller, $buyer, $qty,
        $dates[mt_rand(0, count($dates) - 1)], "XFER-$count",
    ]);
    $bal[$seller][$cid] -= $qty;
    $bal[$buyer][$cid] = ($bal[$buyer][$cid] ?? 0) + $qty;
    $count++;
}
$pdo->commit();

// Materialize the projection the way ShareService maintains it incrementally
$pdo->exec(
    "INSERT INTO share_holdings (shareholder_id, share_class_id, quantity)
     SELECT shareholder_id, share_class_id, SUM(qty) AS quantity FROM (
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
);

// ---- Timings ---------------------------------------------------------------
$svc = new OwnershipService();
$bench = function (string $name, callable $fn) {
    $t = microtime(true);
    $result = $fn();
    $ms = (microtime(true) - $t) * 1000;
    printf("%-30s %10.1f ms\n", $name, $ms);
    return $result;
};

$holdings = $bench('byShareholder (projection)', fn() => $svc->byShareholder());
$byClass = $bench('byClass (projection)', fn() => $svc->byClass());
$bench('totalShares (memoized)', fn() => $svc->totalShares());
$bench('totalCapital (memoized)', fn() => $svc->totalCapital());
$bench('holding(1, ORD)', fn() => $svc->holding($shareholderIds[0], $classIds[0]));
$bench('holdingsOf(1)', fn() => $svc->holdingsOf($shareholderIds[0]));
$bench('outstanding x3 classes', fn() => array_map(fn($c) => $svc->outstanding($c), $classIds));
$bench('waterfall(2e9)', fn() => $svc->waterfall(2000000000));
$bench('history as-of 2024-06-30', fn() => $svc->byShareholder('2024-06-30'));

printf("%-30s %10s\n", 'movements seeded', number_format($count));
printf("%-30s %10s\n", 'peak memory', round(memory_get_peak_usage(true) / 1048576, 1) . ' MiB');
printf("%-30s %10s\n", 'shareholders in cap table', count($holdings));
printf("%-30s %10s\n", 'classes in cap table', count($byClass));