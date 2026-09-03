<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Database;

/**
 * Computes current holdings, ownership percentages and the fully-diluted
 * capital breakdown from the append-only share register.
 */
class OwnershipService
{
    /**
     * @return array<int, array{shareholder: array, rows: array<int, array{class: array, quantity: int, value: int}>, total: int, percentage: float}>
     */
    public function byShareholder(): array
    {
        // Recompute holdings from the append-only movement register.
        $quantities = [];
        foreach (Database::all('SELECT * FROM share_movements') as $m) {
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

    public function holding(int $shareholderId, int $classId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(CASE
                WHEN movement_type IN ("issuance","transfer_in") AND shareholder_id = :sid THEN quantity
                WHEN movement_type = "transfer_out" AND shareholder_id = :sid THEN -quantity
                WHEN movement_type = "transfer_out" AND counterparty_id = :sid THEN quantity
                ELSE 0 END), 0)
            FROM share_movements WHERE share_class_id = :cid',
            ['sid' => $shareholderId, 'cid' => $classId]
        );
    }

    /** @return array<int, array{class: array, quantity: int, value: int}> */
    public function byClass(): array
    {
        $rows = [];
        foreach (Database::all('SELECT * FROM share_classes ORDER BY code') as $class) {
            $qty = $this->outstanding((int) $class['id']);
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

    public function outstanding(int $classId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(CASE WHEN movement_type = "issuance" THEN quantity ELSE 0 END), 0)
            FROM share_movements WHERE share_class_id = ?',
            [$classId]
        );
    }

    public function totalShares(): int
    {
        return array_sum(array_map(fn($r) => $r['quantity'], $this->byClass()));
    }

    public function totalCapital(): int
    {
        return array_sum(array_map(fn($r) => $r['value'], $this->byClass()));
    }
}
