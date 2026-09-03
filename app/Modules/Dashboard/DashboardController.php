<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\Controller;
use App\Core\Database;
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
        return $this->view('dashboard/index', [
            'title' => 'Tableau de bord',
            'company' => $company,
            'totalShares' => $this->ownership->totalShares(),
            'totalCapital' => $this->ownership->totalCapital(),
            'shareholderCount' => (int) Database::scalar('SELECT COUNT(*) FROM shareholders'),
            'movementCount' => (int) Database::scalar('SELECT COUNT(*) FROM share_movements'),
            'recentMovements' => Database::all(
                'SELECT m.*, s.name AS shareholder_name, c.code AS class_code
                 FROM share_movements m
                 LEFT JOIN shareholders s ON s.id = m.shareholder_id
                 LEFT JOIN share_classes c ON c.id = m.share_class_id
                 ORDER BY m.movement_date DESC, m.id DESC LIMIT 8'
            ),
        ]);
    }
}
