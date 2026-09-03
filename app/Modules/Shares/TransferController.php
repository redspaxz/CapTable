<?php

declare(strict_types=1);

namespace App\Modules\Shares;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;

class TransferController extends Controller
{
    public function index(): string
    {
        return $this->view('transfers/index', [
            'title' => 'Cessions de droits sociaux',
            'transfers' => Database::all(
                'SELECT t.*, seller.name AS seller_name, buyer.name AS buyer_name, c.code AS class_code
                 FROM share_transfers t
                 JOIN shareholders seller ON seller.id = t.seller_id
                 JOIN shareholders buyer ON buyer.id = t.buyer_id
                 JOIN share_classes c ON c.id = t.share_class_id
                 ORDER BY t.transfer_date DESC, t.id DESC'
            ),
        ]);
    }

    public function create(): string
    {
        return $this->view('transfers/form', [
            'title' => 'Nouvelle cession',
            'classes' => Database::all('SELECT * FROM share_classes ORDER BY code'),
            'shareholders' => Database::all('SELECT * FROM shareholders ORDER BY name'),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'share_class_id' => Request::int('share_class_id'),
            'seller_id' => Request::int('seller_id'),
            'buyer_id' => Request::int('buyer_id'),
            'quantity' => Request::int('quantity'),
            'transfer_date' => Request::str('transfer_date', date('Y-m-d')),
            'deed_reference' => Request::str('deed_reference'),
        ];
        $v = new Validator($data);
        $v->required('share_class_id', 'seller_id', 'buyer_id', 'quantity', 'transfer_date')
          ->positive('share_class_id', 'seller_id', 'buyer_id', 'quantity')->date('transfer_date');
        if ($v->fails()) {
            \App\flash('error', 'Tous les champs sont obligatoires.');
            redirect('/transfers/new');
        }
        try {
            (new ShareService())->transfer(
                $data['share_class_id'],
                $data['seller_id'],
                $data['buyer_id'],
                $data['quantity'],
                $data['transfer_date'],
                $data['deed_reference'] !== '' ? $data['deed_reference'] : 'ACT-' . date('Ymd') . '-' . random_int(100, 999)
            );
            \App\flash('success', 'Cession enregistrée.');
            redirect('/transfers');
        } catch (\InvalidArgumentException $e) {
            \App\flash('error', $e->getMessage());
            redirect('/transfers/new');
        }
    }
}
