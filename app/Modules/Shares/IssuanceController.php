<?php

declare(strict_types=1);

namespace App\Modules\Shares;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Modules\CapTable\OwnershipService;

class IssuanceController extends Controller
{
    public function index(): string
    {
        return $this->view('issuances/index', [
            'title' => 'Share issuances',
            'issuances' => Database::all(
                'SELECT i.*, s.name AS shareholder_name, c.code AS class_code, c.nominal_value
                 FROM share_issuances i
                 JOIN shareholders s ON s.id = i.shareholder_id
                 JOIN share_classes c ON c.id = i.share_class_id
                 ORDER BY i.issuance_date DESC, i.id DESC'
            ),
        ]);
    }

    public function create(): string
    {
        $service = new OwnershipService();
        $classes = Database::all('SELECT * FROM share_classes ORDER BY code');
        foreach ($classes as &$class) {
            $class['remaining'] = (int) $class['shares_authorized'] - $service->outstanding((int) $class['id']);
        }
        return $this->view('issuances/form', [
            'title' => 'New issuance',
            'classes' => $classes,
            'shareholders' => Database::all('SELECT * FROM shareholders ORDER BY name'),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'share_class_id' => Request::int('share_class_id'),
            'shareholder_id' => Request::int('shareholder_id'),
            'quantity' => Request::int('quantity'),
            'apport_type' => Request::str('apport_type', 'cash'),
            'issuance_date' => Request::str('issuance_date', date('Y-m-d')),
            'reference' => Request::str('reference'),
        ];
        $v = new Validator($data);
        $v->required('share_class_id', 'shareholder_id', 'quantity', 'issuance_date')
          ->positive('share_class_id', 'shareholder_id', 'quantity')->date('issuance_date');

        $class = Database::one('SELECT * FROM share_classes WHERE id = ?', [$data['share_class_id']]);
        $ownership = new OwnershipService();
        if ($v->fails() || !$class) {
            \App\flash('error', 'Class, shareholder, quantity and date are required.');
            redirect('/issuances/new');
        }
        $remaining = (int) $class['shares_authorized'] - $ownership->outstanding($data['share_class_id']);
        if ($data['quantity'] > $remaining) {
            \App\flash('error', "Quota exceeded: only {$remaining} share(s) remain available for issuance in this class.");
            redirect('/issuances/new');
        }

        (new ShareService())->issue(
            $data['share_class_id'],
            $data['shareholder_id'],
            $data['quantity'],
            $data['apport_type'],
            $data['issuance_date'],
            $data['reference'] !== '' ? $data['reference'] : 'EM-' . date('Ymd') . '-' . random_int(100, 999)
        );
        \App\flash('success', 'Issuance recorded in the share movement register.');
        redirect('/issuances');
    }
}
