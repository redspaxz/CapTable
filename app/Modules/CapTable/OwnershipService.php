<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Database;

/**
 * Computes current (or as-of-date) holdings, ownership percentages and the
 * fully-diluted capital breakdown from the append-only share register,
 * plus the liquidation waterfall.
 */
class OwnershipService
{
    /**
     * @param string|null $asOf ISO date — reconstruct the table as of that
     *                           date (movements after it are ignored).
     * @return array<int, array{shareholder: array, rows: array<int, array{class: array, quantity: int, value: int}>, total: int, percentage: float}>
     */
    public function byShareholder(?string $asOf = null): array
    {
        // Recompute holdings from the append-only movement register.
        $quantities = [];
        $dateFilter = $asOf !== null ? 'WHERE movement_date <= :asof' : '';
        $params = $asOf !== null ? ['asof' => $asOf] : [];
        foreach (Database::all("SELECT * FROM share_movements {$dateFilter}", $params) as $m) {
            $classId = (int) $m['share_class_id'];
            if ($m['movement_type'] === 'issuance' || $m['movement_type'] === 'transfer_in') {
                $quantities[(int) $m['shareholder_id']][$classId] =
                    ($quantities[(int) $m['shareholder_id']][$classId] ?? 0) + (int) $m['quantity'];
            } elseif ($m['movement_type'] === 'transfer_out') {
                $quantities[(int) $m['shareholder_id']][$classId] =
                    ($quantities[(int) $m['shareholder_id']][$classId] ?? 0) - (int) $m['quantity'];
                if ((int) $m['counterparty_id'] > 0) {
                    $quantities[(int) $m['counterparty_id']][$classId] =
                        ($quantities[(int) $m['counterparty_id']][$classId] ?? 0) + (int) $m['quantity'];
                }
            }
        }

        $classes = [];
        foreach (Database::all('SELECT * FROM share_classes') as $c) {
            $classes[(int) $c['id']] = $c;
        }
        $shareholders = [];
        foreach (Database::all('SELECT * FROM shareholders ORDER BY name') as $s) {
            $shareholders[(int) $s['id']] = $s;
        }

        $totalShares = 0;
        $result = [];
        foreach ($quantities as $shareholderId => $byClass) {
            $rows = [];
            $shareholderTotal = 0;
            foreach ($byClass as $classId => $qty) {
                if ($qty <= 0 || !isset($classes[$classId], $shareholders[$shareholderId])) {
                    continue;
                }
                $class = $classes[$classId];
                $rows[] = [
                    'class' => $class,
                    'quantity' => $qty,
                    'value' => $qty * (int) $class['nominal_value'],
                ];
                $shareholderTotal += $qty;
                $totalShares += $qty;
            }
            if ($rows === []) {
                continue;
            }
            $result[] = [
                'shareholder' => $shareholders[$shareholderId],
                'rows' => $rows,
                'total' => $shareholderTotal,
                'percentage' => 0.0,
            ];
        }

        foreach ($result as &$entry) {
            $entry['percentage'] = $totalShares > 0
                ? $entry['total'] / $totalShares * 100 : 0.0;
        }
        usort($result, fn($a, $b) => $b['total'] <=> $a['total']);
        return $result;
    }

    public function holding(int $shareholderId, int $classId, ?string $asOf = null): int
    {
        $dateFilter = $asOf !== null ? 'AND movement_date <= :asof' : '';
        $params = ['sid' => $shareholderId, 'cid' => $classId];
        if ($asOf !== null) {
            $params['asof'] = $asOf;
        }
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(CASE
                WHEN movement_type IN ('issuance','transfer_in') AND shareholder_id = :sid THEN quantity
                WHEN movement_type = 'transfer_out' AND shareholder_id = :sid THEN -quantity
                WHEN movement_type = 'transfer_out' AND counterparty_id = :sid THEN quantity
                ELSE 0 END), 0)
            FROM share_movements WHERE share_class_id = :cid {$dateFilter}",
            $params
        );
    }

    /** @return array<int, array{class: array, quantity: int, value: int}> */
    public function byClass(?string $asOf = null): array
    {
        $rows = [];
        foreach (Database::all('SELECT * FROM share_classes ORDER BY code') as $class) {
            $qty = $this->outstanding((int) $class['id'], $asOf);
            if ($qty > 0) {
                $rows[] = [
                    'class' => $class,
                    'quantity' => $qty,
                    'value' => $qty * (int) $class['nominal_value'],
                ];
            }
        }
        return $rows;
    }

    public function outstanding(int $classId, ?string $asOf = null): int
    {
        $dateFilter = $asOf !== null ? 'AND movement_date <= :asof' : '';
        $params = [$classId];
        if ($asOf !== null) {
            $params['asof'] = $asOf;
        }
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN movement_type = 'issuance' THEN quantity ELSE 0 END), 0)
            FROM share_movements WHERE share_class_id = ? {$dateFilter}",
            $params
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
            'SELECT * FROM share_classes ORDER BY liquidation_priority, id'
        );
        $holdings = $this->byShareholder();
        $remaining = $exitValue;

        // 1. Liquidation preferences, in priority order
        $steps = [];
        $paidRatioByClass = [];
        foreach ($classes as $class) {
            $outstanding = $this->outstanding((int) $class['id']);
            if ($outstanding <= 0) {
                continue;
            }
            $need = (int) floor($outstanding * (int) $class['nominal_value'] * (float) $class['liquidation_multiplier']);
            $paid = max(0, min($need, $remaining));
            $remaining -= $paid;
            $paidRatioByClass[(int) $class['id']] = $need > 0 ? $paid / $need : 0.0;
            $steps[] = ['class' => $class, 'need' => $need, 'paid' => $paid];
        }

        // 2. Residual pro-rata among participating classes (by shares)
        $participatingShares = 0;
        foreach ($classes as $class) {
            if ((int) $class['participating'] === 1) {
                $participatingShares += $this->outstanding((int) $class['id']);
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
