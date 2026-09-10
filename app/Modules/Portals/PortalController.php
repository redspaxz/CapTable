<?php

declare(strict_types=1);

namespace App\Modules\Portals;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Tenancy;
use App\Modules\CapTable\OwnershipService;
use App\Modules\Options\OptionService;

/**
 * Stakeholder portal: a role-aware dashboard (founder, investor, board
 * member, employee) showing holdings, option grant status, exercised
 * shares and vested value for the linked shareholder profile.
 */
class PortalController extends Controller
{
    public function index(): string
    {
        $user = Auth::user();
        $userData = Database::one('SELECT * FROM users WHERE id = ?', [$user['id'] ?? 0]);
        $shareholderId = $userData['shareholder_id'] ?? null;
        $stakeholderRole = $userData['stakeholder_role'] ?? null;

        $ownership = new OwnershipService();
        $options = new OptionService();
        $totalShares = $ownership->totalShares();

        $myHolding = null;
        if ($shareholderId !== null) {
            $shareholder = Database::one('SELECT * FROM shareholders WHERE id = ? AND tenant_id = ?', [$shareholderId, \App\Core\Tenancy::idOrFail()]);
            if ($shareholder) {
                $rows = [];
                $myTotal = 0;
                foreach (Database::all('SELECT * FROM share_classes WHERE tenant_id = ? ORDER BY code', [\App\Core\Tenancy::idOrFail()]) as $class) {
                    $qty = $ownership->holding((int) $shareholderId, (int) $class['id']);
                    if ($qty > 0) {
                        $rows[] = [
                            'class' => $class,
                            'quantity' => $qty,
                            'value' => $qty * (int) $class['nominal_value'],
                        ];
                        $myTotal += $qty;
                    }
                }
                $myHolding = [
                    'shareholder' => $shareholder,
                    'rows' => $rows,
                    'total' => $myTotal,
                    'percentage' => $totalShares > 0 ? $myTotal / $totalShares * 100 : 0.0,
                    'certificates' => (int) Database::scalar(
                        'SELECT COUNT(*) FROM share_certificates WHERE shareholder_id = ? AND tenant_id = ?',
                        [$shareholderId, \App\Core\Tenancy::idOrFail()]
                    ),
                ];
            }
        }

        $myGrants = $shareholderId !== null ? $options->grantsWithVesting((int) $shareholderId) : [];
        $exercisedTotal = array_sum(array_map(fn($g) => (int) $g['exercised_qty'], $myGrants));
        $vestedTotal = array_sum(array_map(fn($g) => $options->vestedQty($g), $myGrants));
        $vestedValue = array_sum(array_map(fn($g) => $options->vestedValue($g), $myGrants));

        return $this->view('portal/index', [
            'title' => 'My space',
            'stakeholderRole' => $stakeholderRole,
            'myHolding' => $myHolding,
            'myGrants' => $myGrants,
            'exercisedTotal' => $exercisedTotal,
            'vestedTotal' => $vestedTotal,
            'vestedValue' => $vestedValue,
            'referencePrice' => $options->referencePrice(),
            'company' => \App\company(),
            'totalShares' => $totalShares,
            'totalCapital' => $ownership->totalCapital(),
            'shareholderCount' => (int) Database::scalar('SELECT COUNT(*) FROM shareholders WHERE tenant_id = ?', [\App\Core\Tenancy::idOrFail()]),
        ]);
    }
}
