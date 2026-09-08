<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;

class ConvertibleController extends Controller
{
    public function index(): string
    {
        $service = new ConvertibleService();
        return $this->view('convertibles/index', [
            'title' => 'Convertible instruments',
            'instruments' => $service->outstanding(),
            'proforma' => $service->proforma(),
            'currentShares' => (new OwnershipService())->totalShares(),
            'company' => \App\company(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $type = strtoupper(Request::str('type', 'OCA'));
        if (!in_array($type, ['OCA', 'BSA', 'SAFE'], true)) {
            $type = 'OCA';
        }
        $data = [
            'type' => $type,
            'holder' => Request::str('holder'),
            'principal_amount' => Request::int('principal_amount'),
            'currency' => strtoupper(Request::str('currency', 'XAF')),
            'discount_pct' => (float) Request::str('discount_pct', '0'),
            'valuation_cap' => Request::int('valuation_cap') ?: null,
            'issue_date' => Request::str('issue_date', date('Y-m-d')),
            'notes' => Request::str('notes'),
        ];
        $v = new Validator($data);
        $v->required('holder', 'issue_date')->date('issue_date');
        if ($v->fails() || $data['principal_amount'] <= 0 || $data['discount_pct'] < 0 || $data['discount_pct'] >= 100) {
            \App\flash('error', __('Holder, amount > 0 and discount 0-99 % are required.'));
            redirect('/convertibles');
        }
        Database::execute(
            'INSERT INTO convertibles (type, holder, principal_amount, currency, discount_pct, valuation_cap, issue_date, notes)
             VALUES (?,?,?,?,?,?,?,?)',
            [$data['type'], $data['holder'], $data['principal_amount'], $data['currency'],
             $data['discount_pct'], $data['valuation_cap'], $data['issue_date'], $data['notes']]
        );
        \App\flash('success', __('Convertible instrument recorded.'));
        redirect('/convertibles');
    }
}
