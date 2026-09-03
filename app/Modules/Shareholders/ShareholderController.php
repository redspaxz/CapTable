<?php

declare(strict_types=1);

namespace App\Modules\Shareholders;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;

class ShareholderController extends Controller
{
    public function index(): string
    {
        $search = Request::str('q');
        $sql = 'SELECT s.*,
                (SELECT COALESCE(SUM(CASE WHEN m.movement_type = "issuance" THEN m.quantity ELSE 0 END),0)
                 FROM share_movements m WHERE m.shareholder_id = s.id) AS share_count
                FROM shareholders s';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE s.name LIKE ? OR s.id_number LIKE ?';
            $params = ["%$search%", "%$search%"];
        }
        $sql .= ' ORDER BY s.name';
        return $this->view('shareholders/index', [
            'title' => 'Actionnaires',
            'shareholders' => Database::all($sql, $params),
            'search' => $search,
        ]);
    }

    public function create(): string
    {
        return $this->view('shareholders/form', [
            'title' => 'Nouvel actionnaire',
            'shareholder' => null,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->formData();
        $v = new Validator($data);
        $v->required('name', 'id_number', 'type')->date('birth_date');
        if ($v->fails()) {
            \App\flash('error', implode(' ', $v->errors()));
            $_SESSION['_old'] = $data;
            redirect('/shareholders/new');
        }
        Database::execute(
            'INSERT INTO shareholders (type, name, id_number, id_type, address, email, phone, nationality, notes)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$data['type'], $data['name'], $data['id_number'], $data['id_type'], $data['address'],
             $data['email'], $data['phone'], $data['nationality'], $data['notes']]
        );
        \App\flash('success', 'Actionnaire enregistré.');
        redirect('/shareholders');
    }

    public function edit(int $id): string
    {
        $shareholder = Database::one('SELECT * FROM shareholders WHERE id = ?', [$id]);
        if (!$shareholder) {
            redirect('/shareholders');
        }
        return $this->view('shareholders/form', [
            'title' => 'Modifier l\'actionnaire',
            'shareholder' => $shareholder,
            'errors' => [],
        ]);
    }

    public function update(int $id): void
    {
        Csrf::verify();
        $data = $this->formData();
        $v = new Validator($data);
        $v->required('name', 'id_number', 'type');
        if ($v->fails()) {
            \App\flash('error', implode(' ', $v->errors()));
            redirect('/shareholders/' . $id . '/edit');
        }
        Database::execute(
            'UPDATE shareholders SET type=?, name=?, id_number=?, id_type=?, address=?, email=?, phone=?, nationality=?, notes=?
             WHERE id = ?',
            [$data['type'], $data['name'], $data['id_number'], $data['id_type'], $data['address'],
             $data['email'], $data['phone'], $data['nationality'], $data['notes'], $id]
        );
        \App\flash('success', 'Actionnaire mis à jour.');
        redirect('/shareholders');
    }

    private function formData(): array
    {
        return [
            'type' => Request::str('type', 'individual'),
            'name' => Request::str('name'),
            'id_number' => Request::str('id_number'),
            'id_type' => Request::str('id_type', 'CNI'),
            'address' => Request::str('address'),
            'email' => Request::str('email'),
            'phone' => Request::str('phone'),
            'nationality' => Request::str('nationality', 'Camerounaise'),
            'notes' => Request::str('notes'),
        ];
    }
}
