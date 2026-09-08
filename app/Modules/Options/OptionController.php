<?php

declare(strict_types=1);

namespace App\Modules\Options;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;

class OptionController extends Controller
{
    public function index(): string
    {
        $service = new OptionService();
        $grants = $service->grantsWithVesting();
        return $this->view('options/index', [
            'title' => 'Options & vesting',
            'grants' => $grants,
            'referencePrice' => $service->referencePrice(),
        ]);
    }

    public function create(): string
    {
        return $this->view('options/form', [
            'title' => 'New option grant',
            'classes' => Database::all('SELECT * FROM share_classes ORDER BY code'),
            'shareholders' => Database::all('SELECT * FROM shareholders ORDER BY name'),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'shareholder_id' => Request::int('shareholder_id'),
            'share_class_id' => Request::int('share_class_id'),
            'quantity' => Request::int('quantity'),
            'strike_price' => Request::int('strike_price'),
            'granted_at' => Request::str('granted_at', date('Y-m-d')),
            'vest_months' => Request::int('vest_months', 48),
            'cliff_months' => Request::int('cliff_months', 12),
            'notes' => Request::str('notes'),
        ];
        $v = new Validator($data);
        $v->required('shareholder_id', 'share_class_id', 'quantity', 'granted_at')
          ->positive('shareholder_id', 'share_class_id', 'quantity', 'vest_months')->date('granted_at');
        if ($v->fails()) {
            \App\flash('error', 'Beneficiary, class, quantity and date are required.');
            redirect('/options/new');
        }
        try {
            (new OptionService())->grant(
                $data['shareholder_id'],
                $data['share_class_id'],
                $data['quantity'],
                $data['strike_price'],
                $data['granted_at'],
                $data['vest_months'],
                $data['cliff_months'],
                $data['notes']
            );
            \App\flash('success', __('Option grant recorded.'));
        } catch (\InvalidArgumentException $e) {
            \App\flash('error', $e->getMessage());
            redirect('/options/new');
        }
        redirect('/options');
    }

    public function show(int $id): string
    {
        $service = new OptionService();
        $grant = Database::one(
            'SELECT g.*, s.name AS beneficiary, c.code AS class_code, c.nominal_value
             FROM option_grants g
             JOIN shareholders s ON s.id = g.shareholder_id
             JOIN share_classes c ON c.id = g.share_class_id
             WHERE g.id = ?',
            [$id]
        );
        if (!$grant) {
            redirect('/options');
        }
        return $this->view('options/show', [
            'title' => 'Grant #' . $id,
            'grant' => $grant,
            'vested' => $service->vestedQty($grant),
            'exercisable' => $service->exercisableQty($grant),
            'schedule' => $service->schedule($grant),
            'vestedValue' => $service->vestedValue($grant),
            'exercises' => Database::all(
                'SELECT e.*, i.reference AS issuance_reference
                 FROM option_exercises e
                 LEFT JOIN share_issuances i ON i.id = e.share_issuance_id
                 WHERE e.grant_id = ? ORDER BY e.exercise_date DESC, e.id DESC',
                [$id]
            ),
        ]);
    }

    public function exercise(int $id): void
    {
        Csrf::verify();
        $quantity = Request::int('quantity');
        $date = Request::str('exercise_date', date('Y-m-d'));
        try {
            (new OptionService())->exercise($id, $quantity, $date, Request::str('reference'));
            \App\flash('success', __('Exercise completed: shares issued and recorded in the movement register.'));
        } catch (\InvalidArgumentException $e) {
            \App\flash('error', $e->getMessage());
        }
        redirect('/options/' . $id);
    }
}
