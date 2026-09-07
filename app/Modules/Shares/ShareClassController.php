<?php

declare(strict_types=1);

namespace App\Modules\Shares;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Modules\CapTable\OwnershipService;

class ShareClassController extends Controller
{
    public function index(): string
    {
        $service = new OwnershipService();
        $classes = Database::all('SELECT * FROM share_classes ORDER BY code');
        foreach ($classes as &$class) {
            $class['outstanding'] = $service->outstanding((int) $class['id']);
        }
        return $this->view('classes/index', ['title' => 'Share classes', 'classes' => $classes]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'code' => strtoupper(Request::str('code')),
            'name' => Request::str('name'),
            'nominal_value' => Request::int('nominal_value'),
            'shares_authorized' => Request::int('shares_authorized'),
            'rights' => Request::str('rights'),
            'liquidation_multiplier' => max(0.0, (float) Request::str('liquidation_multiplier', '1')),
            'liquidation_priority' => max(1, Request::int('liquidation_priority', 100)),
            'participating' => Request::str('participating', '1') === '1' ? 1 : 0,
            'category' => in_array(Request::str('category'), ['ordinary', 'preference', 'adpsdv'], true) ? Request::str('category') : 'ordinary',
            'voting_weight' => in_array(Request::int('voting_weight', 1), [0, 1, 2], true) ? Request::int('voting_weight', 1) : 1,
            'requires_approval' => Request::str('requires_approval', '0') === '1' ? 1 : 0,
            'lockup_until' => Request::str('lockup_until') ?: null,
        ];
        $v = new Validator($data);
        $v->required('code', 'name')->positive('nominal_value', 'shares_authorized')->date('lockup_until');
        if ($v->fails() || Database::one('SELECT id FROM share_classes WHERE code = ?', [$data['code']])) {
            \App\flash('error', 'Code, label, par value and authorized count are required (code must be unique).');
            redirect('/classes');
        }
        Database::execute(
            'INSERT INTO share_classes (code, name, nominal_value, shares_authorized, rights, liquidation_multiplier, liquidation_priority, participating, category, voting_weight, requires_approval, lockup_until) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$data['code'], $data['name'], $data['nominal_value'], $data['shares_authorized'], $data['rights'],
             $data['liquidation_multiplier'], $data['liquidation_priority'], $data['participating'],
             $data['category'], $data['voting_weight'], $data['requires_approval'], $data['lockup_until']]
        );
        \App\flash('success', 'Share class created.');
        redirect('/classes');
    }
}
