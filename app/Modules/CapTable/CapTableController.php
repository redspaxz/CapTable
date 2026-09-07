<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Controller;
use App\Core\Database;

class CapTableController extends Controller
{
    private OwnershipService $ownership;

    public function __construct()
    {
        $this->ownership = new OwnershipService();
    }

    public function index(): string
    {
        return $this->view('captable/index', [
            'title' => 'Répartition du capital',
            'holdings' => $this->ownership->byShareholder(),
            'byClass' => $this->ownership->byClass(),
            'totalShares' => $this->ownership->totalShares(),
            'totalCapital' => $this->ownership->totalCapital(),
            'company' => \App\company(),
            'charts' => true,
        ]);
    }

    public function exportCsv(): void
    {
        // Neutralize spreadsheet formula injection in user-controlled cells
        $guard = static function (mixed $cell): string {
            $cell = (string) $cell;
            if ($cell !== '' && strpbrk($cell[0], '=+-@\\') !== false) {
                return "'" . $cell;
            }
            return $cell;
        };
        $rows = [['Actionnaire', 'Type', 'Catégorie', 'Titres', 'Valeur nominale totale', 'Pourcentage']];
        $total = $this->ownership->totalShares();
        foreach ($this->ownership->byShareholder() as $h) {
            foreach ($h['rows'] as $row) {
                $rows[] = array_map($guard, [
                    $h['shareholder']['name'],
                    $h['shareholder']['type'],
                    $row['class']['code'] . ' — ' . $row['class']['name'],
                    (string) $row['quantity'],
                    (string) $row['value'],
                    number_format($total > 0 ? $row['quantity'] / $total * 100 : 0, 2, '.', ''),
                ]);
            }
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="captable.csv"');
        $out = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }

    /** JSON: current holdings of one shareholder, keyed by share class id. */
    public function holdings(int $id): string
    {
        header('Content-Type: application/json; charset=UTF-8');
        $holdings = [];
        foreach (Database::all('SELECT id FROM share_classes') as $class) {
            $qty = $this->ownership->holding($id, (int) $class['id']);
            if ($qty > 0) {
                $holdings[(string) $class['id']] = $qty;
            }
        }
        echo json_encode(['shareholder_id' => $id, 'holdings' => $holdings]);
        return '';
    }

    public function register(): string
    {
        return $this->view('register/index', [
            'title' => 'Registre des mouvements de titres',
            'movements' => Database::all(
                'SELECT m.*, s.name AS shareholder_name, cp.name AS counterparty_name, c.code AS class_code
                 FROM share_movements m
                 LEFT JOIN shareholders s ON s.id = m.shareholder_id
                 LEFT JOIN shareholders cp ON cp.id = m.counterparty_id
                 LEFT JOIN share_classes c ON c.id = m.share_class_id
                 ORDER BY m.movement_date DESC, m.id DESC'
            ),
            'company' => \App\company(),
        ]);
    }
}
