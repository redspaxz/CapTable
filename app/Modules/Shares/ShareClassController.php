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
        return $this->view('classes/index', ['title' => 'Catégories d\'actions', 'classes' => $classes]);
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
        ];
        $v = new Validator($data);
        $v->required('code', 'name')->positive('nominal_value', 'shares_authorized');
        if ($v->fails() || Database::one('SELECT id FROM share_classes WHERE code = ?', [$data['code']])) {
            \App\flash('error', 'Code, libellé, valeur nominale et nombre autorisé sont requis (code unique).');
            redirect('/classes');
        }
        Database::execute(
            'INSERT INTO share_classes (code, name, nominal_value, shares_authorized, rights) VALUES (?,?,?,?,?)',
            [$data['code'], $data['name'], $data['nominal_value'], $data['shares_authorized'], $data['rights']]
        );
        \App\flash('success', 'Catégorie d\'actions créée.');
        redirect('/classes');
    }
}
