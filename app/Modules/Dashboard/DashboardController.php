<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Tenancy;
use App\Modules\CapTable\OwnershipService;

class DashboardController extends Controller
{
    private OwnershipService $ownership;

    public function __construct()
    {
        $this->ownership = new OwnershipService();
    }

    public function index(): string
    {
        $company = \App\company();
        $holdings = $this->ownership->byShareholder();
        return $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'charts' => true,
            'company' => $company,
            'totalShares' => $this->ownership->totalShares(),
            'totalCapital' => $this->ownership->totalCapital(),
            'shareholderCount' => (int) Database::scalar('SELECT COUNT(*) FROM shareholders WHERE tenant_id = ?', [\App\Core\Tenancy::idOrFail()]),
            'movementCount' => (int) Database::scalar('SELECT COUNT(*) FROM share_movements WHERE tenant_id = ?', [\App\Core\Tenancy::idOrFail()]),
            'topHolders' => array_slice($holdings, 0, 6),
            'capitalTimeline' => $this->capitalTimeline(),
            'recentMovements' => Database::all(
                'SELECT m.*, s.name AS shareholder_name, c.code AS class_code
                 FROM share_movements m
                 LEFT JOIN shareholders s ON s.id = m.shareholder_id
                 LEFT JOIN share_classes c ON c.id = m.share_class_id
                 WHERE m.tenant_id = ?
                 ORDER BY m.movement_date DESC, m.id DESC LIMIT 6',
                [\App\Core\Tenancy::idOrFail()]
            ),
        ]);
    }

    /**
     * Cumulative issued capital (XAF) per movement date — issuances add
     * qty × par value; transfers leave the total unchanged.
     * @return array{labels: string[], values: int[]}
     */
    private function capitalTimeline(): array
    {
        $rows = Database::all(
            'SELECT m.movement_date AS d,
                    SUM(CASE WHEN m.movement_type = "issuance" THEN m.quantity ELSE 0 END * c.nominal_value) AS cap
             FROM share_movements m
             JOIN share_classes c ON c.id = m.share_class_id
             WHERE m.tenant_id = :tenant
             GROUP BY m.movement_date
             ORDER BY m.movement_date',
            ['tenant' => \App\Core\Tenancy::idOrFail()]
        );
        $labels = [];
        $values = [];
        $cumulative = 0;
        foreach ($rows as $row) {
            $cumulative += (int) $row['cap'];
            $labels[] = (string) $row['d'];
            $values[] = $cumulative;
        }
        return ['labels' => $labels, 'values' => $values];
    }
}
