<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Database;
use App\Core\Tenancy;

/**
 * Computes current (or as-of-date) holdings, ownership percentages and the
 * fully-diluted capital breakdown from the append-only share register,
 * plus the liquidation waterfall.
 *
 * The share_movements register (AUSCGIE art. 716) is the source of truth.
 * Current-state reads go through the share_holdings projection - a summary
 * table maintained in the same transaction as every register write - so
 * large registers are never re-folded into PHP (or re-aggregated) on page
 * views. As-of reconstruction still aggregates the register directly, since
 * that operation is explicit, rare, and cannot be served by a current-state
 * projection.
 */
class OwnershipService
{
    /** @var array<string, mixed> per-request memo cache */
    private static array $cache = [];

    private static function cacheKey(string $method, ?string $asOf): string
    {
        return Tenancy::idOrFail() . '|' . $method . '|' . ($asOf ?? 'current');
    }

    private static function remember(string $key, callable $fn): mixed
    {
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = $fn();
        }
        return self::$cache[$key];
    }

    private static ?bool $projectionAvailable = null;

    /**
     * True when the share_holdings projection table exists. On installs that
     * predate the projection (deployed code, not-yet-migrated database) every
     * current-state read falls back to the append-only register, so the app
     * keeps working instead of erroring; once database/migrate_holdings.php
     * has run, the projection takes over automatically. Probed once per
     * request, never inside the memo cache.
     */
    public static function projectionAvailable(): bool
    {
        if (self::$projectionAvailable === null) {
            try {
                Database::one('SELECT 1 FROM share_holdings LIMIT 1');
                self::$projectionAvailable = true;
            } catch (\Throwable) {
                self::$projectionAvailable = false;
            }
        }
        return self::$projectionAvailable;
    }

    /**
     * @param string|null $asOf ISO date - reconstruct the table as of that
     *                           date (movements after it are ignored).
     * @return array<int, array{shareholder: array, rows: array<int, array{class: array, quantity: int, value: int}>, total: int, percentage: float}>
     */
    public function byShareholder(?string $asOf = null): array
    {
        return self::remember(self::cacheKey('byShareholder', $asOf), function () use ($asOf): array {
            $net = $asOf === null
                ? $this->netQuantities()
                : $this->netQuantitiesFromRegister($asOf);

            $tenantId = Tenancy::idOrFail();
            $classes = [];
            foreach (Database::all('SELECT * FROM share_classes WHERE tenant_id = ?', [$tenantId]) as $c) {
                $classes[(int) $c['id']] = $c;
            }
            $shareholders = [];
            foreach (Database::all('SELECT * FROM shareholders WHERE tenant_id = ? ORDER BY name', [$tenantId]) as $s) {
                $shareholders[(int) $s['id']] = $s;
            }

            $totalShares = 0;
            $result = [];
            $index = [];
            foreach ($net as $row) {
                $shareholderId = (int) $row['shareholder_id'];
                $classId = (int) $row['share_class_id'];
                $qty = (int) $row['quantity'];
                if ($qty <= 0 || !isset($classes[$classId], $shareholders[$shareholderId])) {
                    continue;
                }
                $class = $classes[$classId];
                $entry = [
                    'class' => $class,
                    'quantity' => $qty,
                    'value' => $qty * (int) $class['nominal_value'],
                ];
                if (isset($index[$shareholderId])) {
                    $result[$index[$shareholderId]]['rows'][] = $entry;
                    $result[$index[$shareholderId]]['total'] += $qty;
                } else {
                    $index[$shareholderId] = count($result);
                    $result[] = [
                        'shareholder' => $shareholders[$shareholderId],
                        'rows' => [$entry],
                        'total' => $qty,
                        'percentage' => 0.0,
                    ];
                }
                $totalShares += $qty;
            }

            foreach ($result as &$entry) {
                $entry['percentage'] = $totalShares > 0
                    ? $entry['total'] / $totalShares * 100 : 0.0;
            }
            usort($result, fn($a, $b) => $b['total'] <=> $a['total']);
            return $result;
        });
    }

    /**
     * Current net quantities from the share_holdings projection.
     *
     * @return array<int, array{shareholder_id: int, share_class_id: int, quantity: int}>
     */
    private function netQuantities(): array
    {
        if (!self::projectionAvailable()) {
            return $this->netQuantitiesFromRegister();
        }
        return Database::all(
            'SELECT h.shareholder_id, h.share_class_id, h.quantity
             FROM share_holdings h
             JOIN shareholders s ON s.id = h.shareholder_id
             JOIN share_classes c ON c.id = h.share_class_id
             WHERE h.quantity > 0 AND h.tenant_id = :tenant
             ORDER BY h.shareholder_id, h.share_class_id',
            ['tenant' => Tenancy::idOrFail()]
        );
    }

    /**
     * Net quantities per (shareholder, class) folded from the register,
     * optionally up to a date. Same semantics as the projection and the
     * original PHP fold: issuances/transfer_in add to the holder,
     * transfer_out subtracts from the seller and adds to the buyer. One
     * GROUP BY over the register, no row shipping into PHP.
     *
     * @return array<int, array{shareholder_id: int, share_class_id: int, quantity: int}>
     */
    private function netQuantitiesFromRegister(?string $asOf = null): array
    {
        $params = ['tenant' => Tenancy::idOrFail(), 'tenant2' => Tenancy::id()];
        $where1 = '';
        $where2 = '';
        if ($asOf !== null) {
            $where1 = ' AND movement_date <= :asof';
            $where2 = ' AND movement_date <= :asof2';
            $params['asof'] = $asOf;
            $params['asof2'] = $asOf;
        }
        return Database::all(
            "SELECT shareholder_id, share_class_id, SUM(qty) AS quantity
             FROM (
                 SELECT shareholder_id, share_class_id,
                        CASE WHEN movement_type IN ('issuance','transfer_in') THEN CAST(quantity AS SIGNED)
                             WHEN movement_type = 'transfer_out' THEN -CAST(quantity AS SIGNED)
                             ELSE 0 END AS qty
                 FROM share_movements WHERE tenant_id = :tenant{$where1}
                 UNION ALL
                 SELECT counterparty_id AS shareholder_id, share_class_id, CAST(quantity AS SIGNED) AS qty
                 FROM share_movements
                 WHERE movement_type = 'transfer_out' AND counterparty_id > 0 AND tenant_id = :tenant2{$where2}
             ) movements
             GROUP BY shareholder_id, share_class_id
             HAVING SUM(qty) > 0",
            $params
        );
    }

    public function holding(int $shareholderId, int $classId, ?string $asOf = null): int
    {
        if ($asOf === null && !self::projectionAvailable()) {
            $asOf = '9999-12-31'; // no projection yet -> fold the register
        }
        if ($asOf === null) {
            $row = Database::one(
                'SELECT quantity FROM share_holdings WHERE shareholder_id = ? AND share_class_id = ? AND tenant_id = ?',
                [$shareholderId, $classId, Tenancy::idOrFail()]
            );
            return (int) ($row['quantity'] ?? 0);
        }
        $params = ['sid' => $shareholderId, 'cid' => $classId, 'asof' => $asOf, 'tenant' => Tenancy::idOrFail()];
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(CASE
                WHEN movement_type IN ('issuance','transfer_in') AND shareholder_id = :sid THEN quantity
                WHEN movement_type = 'transfer_out' AND shareholder_id = :sid THEN -quantity
                WHEN movement_type = 'transfer_out' AND counterparty_id = :sid THEN quantity
                ELSE 0 END), 0)
            FROM share_movements WHERE share_class_id = :cid AND movement_date <= :asof AND tenant_id = :tenant",
            $params
        );
    }

    /**
     * Current (or as-of) holdings of one shareholder, keyed by share class id.
     *
     * @return array<string, int> class id => net quantity (positive only)
     */
    public function holdingsOf(int $shareholderId, ?string $asOf = null): array
    {
        if ($asOf === null && !self::projectionAvailable()) {
            $asOf = '9999-12-31'; // no projection yet -> fold the register
        }
        $rows = $asOf === null
            ? Database::all(
                'SELECT share_class_id, quantity FROM share_holdings WHERE shareholder_id = ? AND quantity > 0 AND tenant_id = ?',
                [$shareholderId, Tenancy::idOrFail()]
            )
            : Database::all(
                "SELECT share_class_id,
                        SUM(CASE
                            WHEN movement_type IN ('issuance','transfer_in') AND shareholder_id = :sid THEN CAST(quantity AS SIGNED)
                            WHEN movement_type = 'transfer_out' AND shareholder_id = :sid THEN -CAST(quantity AS SIGNED)
                            WHEN movement_type = 'transfer_out' AND counterparty_id = :sid THEN CAST(quantity AS SIGNED)
                            ELSE 0 END) AS quantity
                 FROM share_movements
                 WHERE (shareholder_id = :sid OR counterparty_id = :sid2) AND movement_date <= :asof AND tenant_id = :tenant
                 GROUP BY share_class_id",
                ['sid' => $shareholderId, 'sid2' => $shareholderId, 'asof' => $asOf, 'tenant' => Tenancy::idOrFail()]
            );
        $holdings = [];
        foreach ($rows as $row) {
            $qty = (int) $row['quantity'];
            if ($qty > 0) {
                $holdings[(string) $row['share_class_id']] = $qty;
            }
        }
        return $holdings;
    }

    /** @return array<int, array{class: array, quantity: int, value: int}> */
    public function byClass(?string $asOf = null): array
    {
        return self::remember(self::cacheKey('byClass', $asOf), function () use ($asOf): array {
            $totals = $this->outstandingByClass($asOf);
            $rows = [];
            foreach (Database::all('SELECT * FROM share_classes WHERE tenant_id = ? ORDER BY code', [Tenancy::idOrFail()]) as $class) {
                $qty = $totals[(int) $class['id']] ?? 0;
                if ($qty > 0) {
                    $rows[] = [
                        'class' => $class,
                        'quantity' => $qty,
                        'value' => $qty * (int) $class['nominal_value'],
                    ];
                }
            }
            return $rows;
        });
    }

    /**
     * Issued quantity per class id. Transfers conserve class totals, so the
     * sum of the projection equals the sum of issuances; as-of reads fall
     * back to the register.
     *
     * @return array<int, int>
     */
    public function outstandingByClass(?string $asOf = null): array
    {
        return self::remember(self::cacheKey('outstandingByClass', $asOf), function () use ($asOf): array {
            if ($asOf === null && !self::projectionAvailable()) {
                $asOf = '9999-12-31'; // no projection yet -> fold the register
            }
            $rows = $asOf === null
                ? Database::all('SELECT share_class_id, SUM(quantity) AS quantity FROM share_holdings WHERE tenant_id = :tenant GROUP BY share_class_id', ['tenant' => Tenancy::idOrFail()])
                : Database::all(
                    "SELECT share_class_id, SUM(CASE WHEN movement_type = 'issuance' THEN CAST(quantity AS SIGNED) ELSE 0 END) AS quantity
                     FROM share_movements WHERE movement_date <= :asof AND tenant_id = :tenant
                     GROUP BY share_class_id",
                    ['asof' => $asOf, 'tenant' => Tenancy::idOrFail()]
                );
            $totals = [];
            foreach ($rows as $row) {
                $totals[(int) $row['share_class_id']] = (int) $row['quantity'];
            }
            return $totals;
        });
    }

    public function outstanding(int $classId, ?string $asOf = null): int
    {
        if ($asOf === null && !self::projectionAvailable()) {
            $asOf = '9999-12-31'; // no projection yet -> fold the register
        }
        if ($asOf === null) {
            $row = Database::one(
                'SELECT SUM(quantity) AS quantity FROM share_holdings WHERE share_class_id = ? AND tenant_id = ?',
                [$classId, Tenancy::idOrFail()]
            );
            return (int) ($row['quantity'] ?? 0);
        }
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN movement_type = 'issuance' THEN quantity ELSE 0 END), 0)
            FROM share_movements WHERE share_class_id = ? AND movement_date <= :asof AND tenant_id = :tenant",
            [$classId, 'asof' => $asOf, 'tenant' => Tenancy::idOrFail()]
        );
    }

    public function totalShares(?string $asOf = null): int
    {
        return array_sum(array_map(fn($r) => $r['quantity'], $this->byClass($asOf)));
    }

    public function totalCapital(?string $asOf = null): int
    {
        return array_sum(array_map(fn($r) => $r['value'], $this->byClass($asOf)));
    }

    /**
     * Liquidation waterfall for an exit value (in XAF).
     *
     * Model: classes are paid their liquidation preference
     * (nominal x multiplier per share) in ascending liquidation_priority
     * order; any remaining value is shared pro-rata by shares among
     * participating classes only. Non-participating classes receive their
     * preference (the max(preference, pro-rata) optimization is not modeled).
     * All amounts are whole XAF (floored per beneficiary).
     *
     * @return array{steps: array<int, array{class: array, need: int, paid: int}>,
     *               holders: array<int, array{shareholder: array, preference: int, residual: int, total: int}>,
     *               remainder: int, distributed: int}
     */
    public function waterfall(int $exitValue): array
    {
        $classes = Database::all(
            'SELECT * FROM share_classes WHERE tenant_id = ? ORDER BY liquidation_priority, id',
            [Tenancy::idOrFail()]
        );
        $holdings = $this->byShareholder();
        $outstanding = $this->outstandingByClass();
        $remaining = $exitValue;

        // 1. Liquidation preferences, in priority order
        $steps = [];
        $paidRatioByClass = [];
        foreach ($classes as $class) {
            $outstandingQty = $outstanding[(int) $class['id']] ?? 0;
            if ($outstandingQty <= 0) {
                continue;
            }
            $need = (int) floor($outstandingQty * (int) $class['nominal_value'] * (float) $class['liquidation_multiplier']);
            $paid = max(0, min($need, $remaining));
            $remaining -= $paid;
            $paidRatioByClass[(int) $class['id']] = $need > 0 ? $paid / $need : 0.0;
            $steps[] = ['class' => $class, 'need' => $need, 'paid' => $paid];
        }

        // 2. Residual pro-rata among participating classes (by shares)
        $participatingShares = 0;
        foreach ($classes as $class) {
            if ((int) $class['participating'] === 1) {
                $participatingShares += $outstanding[(int) $class['id']] ?? 0;
            }
        }

        $holders = [];
        foreach ($holdings as $h) {
            $preference = 0;
            $participatingQty = 0;
            foreach ($h['rows'] as $row) {
                $classId = (int) $row['class']['id'];
                $ratio = $paidRatioByClass[$classId] ?? 0.0;
                $need = (int) floor($row['quantity'] * (int) $row['class']['nominal_value'] * (float) $row['class']['liquidation_multiplier']);
                $preference += (int) floor($need * $ratio);
                if ((int) $row['class']['participating'] === 1) {
                    $participatingQty += $row['quantity'];
                }
            }
            $residual = $participatingShares > 0 && $remaining > 0
                ? (int) floor($remaining * $participatingQty / $participatingShares)
                : 0;
            $holders[] = [
                'shareholder' => $h['shareholder'],
                'preference' => $preference,
                'residual' => $residual,
                'total' => $preference + $residual,
            ];
        }
        usort($holders, fn($a, $b) => $b['total'] <=> $a['total']);

        $distributed = array_sum(array_map(fn($s) => $s['paid'], $steps));
        return ['steps' => $steps, 'holders' => $holders, 'remainder' => $remaining, 'distributed' => $distributed];
    }
}