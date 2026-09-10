<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Database;
use App\Core\Tenancy;

/**
 * Convertible securities modelling (OCA / BSA / SAFE-like): outstanding
 * instruments and a pro-forma fully-diluted preview if everything
 * converted at the cap and/or discounted price.
 */
class ConvertibleService
{
    /** @return array<int, array> instruments enriched with modelled shares */
    public function outstanding(): array
    {
        $rows = Database::all("SELECT * FROM convertibles WHERE status = 'outstanding' AND tenant_id = ? ORDER BY issue_date", [Tenancy::idOrFail()]);
        $currentShares = (new OwnershipService())->totalShares();
        foreach ($rows as &$row) {
            $row['modelled_shares'] = $this->modelledShares($row, $currentShares);
        }
        return $rows;
    }

    /**
     * Shares the instrument would convert into:
     * price = min(valuation_cap / currentShares, nominal) × (1 - discount).
     */
    public function modelledShares(array $instrument, int $currentShares): int
    {
        if ($currentShares <= 0) {
            return 0;
        }
        $cap = (int) ($instrument['valuation_cap'] ?? 0);
        $price = $cap > 0 ? $cap / $currentShares : 0.0;
        $discount = (float) $instrument['discount_pct'];
        if ($price > 0 && $discount > 0) {
            $price *= (1 - $discount / 100);
        }
        return $price > 0 ? (int) floor((int) $instrument['principal_amount'] / $price) : 0;
    }

    /**
     * Pro-forma cap table if all outstanding convertibles converted.
     * @return array<int, array{holder: string, current: int, modelled: int, pct: float}>
     */
    public function proforma(): array
    {
        $ownership = new OwnershipService();
        $currentShares = $ownership->totalShares();
        $holders = [];
        foreach ($ownership->byShareholder() as $h) {
            $holders[$h['shareholder']['name']] = ['holder' => $h['shareholder']['name'], 'current' => $h['total'], 'modelled' => $h['total']];
        }
        foreach ($this->outstanding() as $instrument) {
            $name = $instrument['holder'] . ' (' . $instrument['type'] . ')';
            $holders[$name] ??= ['holder' => $name, 'current' => 0, 'modelled' => 0];
            $holders[$name]['modelled'] += $instrument['modelled_shares'];
        }
        $total = array_sum(array_map(fn($h) => $h['modelled'], $holders));
        $rows = array_values($holders);
        foreach ($rows as &$row) {
            $row['pct'] = $total > 0 ? $row['modelled'] / $total * 100 : 0.0;
        }
        usort($rows, fn($a, $b) => $b['modelled'] <=> $a['modelled']);
        return $rows;
    }
}
