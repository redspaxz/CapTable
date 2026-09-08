<?php

declare(strict_types=1);

namespace App\Modules\Compliance;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Modules\CapTable\VotingService;

class ComplianceController extends Controller
{
    public function index(): string
    {
        $service = new ComplianceService();
        $voting = new VotingService();
        return $this->view('compliance/index', [
            'title' => 'OHADA compliance',
            'regulated' => $service->regulatedParties(),
            'ubo' => $service->uboStatus(),
            'threshold' => ComplianceService::REGULATED_THRESHOLD,
            'uboThreshold' => ComplianceService::UBO_THRESHOLD,
            'unit' => ComplianceService::equityUnit(),
            'voting' => $voting->votingPower(),
            'pending' => Database::all(
                "SELECT t.*, seller.name AS seller_name, buyer.name AS buyer_name, c.code AS class_code
                 FROM share_transfers t
                 JOIN shareholders seller ON seller.id = t.seller_id
                 JOIN shareholders buyer ON buyer.id = t.buyer_id
                 JOIN share_classes c ON c.id = t.share_class_id
                 WHERE t.status = 'pending' ORDER BY t.transfer_date"
            ),
            'company' => \App\company(),
        ]);
    }

    public function uboForm(): string
    {
        return $this->view('compliance/ubo_form', [
            'title' => 'Declare a beneficial owner',
            'shareholders' => Database::all('SELECT * FROM shareholders ORDER BY name'),
        ]);
    }

    public function storeUbo(): void
    {
        Csrf::verify();
        $data = [
            'name' => Request::str('name'),
            'id_number' => Request::str('id_number'),
            'nationality' => Request::str('nationality', 'Camerounaise'),
            'ownership_pct' => (float) Request::str('ownership_pct', '0'),
            'control_nature' => Request::str('control_nature'),
            'shareholder_id' => Request::int('shareholder_id') ?: null,
            'declared_at' => Request::str('declared_at', date('Y-m-d')),
            'notes' => Request::str('notes'),
        ];
        $v = new Validator($data);
        $v->required('name', 'id_number')->date('declared_at');
        if ($v->fails() || $data['ownership_pct'] < 0 || $data['ownership_pct'] > 100) {
            \App\flash('error', __('Name, ID document and percentage (0-100) are required.'));
            redirect('/compliance/ubo/new');
        }
        Database::execute(
            'INSERT INTO beneficial_owners (name, id_number, nationality, ownership_pct, control_nature, shareholder_id, declared_at, notes)
             VALUES (?,?,?,?,?,?,?,?)',
            [$data['name'], $data['id_number'], $data['nationality'], $data['ownership_pct'],
             $data['control_nature'], $data['shareholder_id'], $data['declared_at'], $data['notes']]
        );
        \App\flash('success', __('Beneficial owner declared.'));
        redirect('/compliance');
    }
}
