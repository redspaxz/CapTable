<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

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
            'title' => 'Capital breakdown',
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
        $rows = [['Shareholder', 'Type', 'Class', 'Shares', 'Total par value', 'Percentage']];
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
        echo json_encode(['shareholder_id' => $id, 'holdings' => $this->ownership->holdingsOf($id)]);
        return '';
    }

    /** Cap table as of an arbitrary date, reconstructed from the register. */
    public function history(): string
    {
        $asOf = \App\Core\Request::str('as_of', date('Y-m-d'));
        if (!preg_match('#^\d{4}-\d{2}-\d{2}$#', $asOf)) {
            $asOf = date('Y-m-d');
        }
        return $this->view('captable/history', [
            'title' => 'Capital history',
            'asOf' => $asOf,
            'holdings' => $this->ownership->byShareholder($asOf),
            'totalShares' => $this->ownership->totalShares($asOf),
            'totalCapital' => $this->ownership->totalCapital($asOf),
            'currentShares' => $this->ownership->totalShares(),
            'company' => \App\company(),
            'movements' => Database::all(
                'SELECT m.*, s.name AS shareholder_name, cp.name AS counterparty_name, c.code AS class_code
                 FROM share_movements m
                 LEFT JOIN shareholders s ON s.id = m.shareholder_id
                 LEFT JOIN shareholders cp ON cp.id = m.counterparty_id
                 LEFT JOIN share_classes c ON c.id = m.share_class_id
                 WHERE m.movement_date <= ?
                 ORDER BY m.movement_date DESC, m.id DESC LIMIT 200',
                [$asOf]
            ),
        ]);
    }

    public function register(): string
    {
        $perPage = 50;
        $total = (int) Database::scalar('SELECT COUNT(*) FROM share_movements');
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, Request::int('page', 1)), $pages);
        return $this->view('register/index', [
            'title' => 'Share movement register',
            'movements' => Database::all(
                'SELECT m.*, s.name AS shareholder_name, cp.name AS counterparty_name, c.code AS class_code
                 FROM share_movements m
                 LEFT JOIN shareholders s ON s.id = m.shareholder_id
                 LEFT JOIN shareholders cp ON cp.id = m.counterparty_id
                 LEFT JOIN share_classes c ON c.id = m.share_class_id
                 ORDER BY m.movement_date DESC, m.id DESC
                 LIMIT ' . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage)
            ),
            'company' => \App\company(),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }
}
