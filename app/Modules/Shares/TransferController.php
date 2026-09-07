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
        $class = Database::one('SELECT * FROM share_classes WHERE id = ?', [$data['share_class_id']]);
        if (!$class) {
            \App\flash('error', 'Catégorie d\'actions inconnue.');
            redirect('/transfers/new');
        }
        $compliance = new \App\Modules\Compliance\ComplianceService();
        $reference = $data['deed_reference'] !== '' ? $data['deed_reference'] : 'ACT-' . date('Ymd') . '-' . random_int(100, 999);
        try {
            if ($compliance->isBlocked($class, $data['transfer_date'])) {
                \App\flash('error', 'Cession refusée : ' . implode(' ', $compliance->transferRestrictions($class, $data['transfer_date'])));
                redirect('/transfers/new');
            }
            if ($compliance->needsApproval($class)) {
                $deadline = date('Y-m-d', strtotime($data['transfer_date'] . ' +30 days'));
                (new ShareService())->requestTransfer(
                    $data['share_class_id'], $data['seller_id'], $data['buyer_id'],
                    $data['quantity'], $data['transfer_date'], $reference, $deadline
                );
                \App\flash('success', 'Cession enregistrée en attente d\'approbation (agrément / droit de préemption jusqu\'au ' . $deadline . ').');
                redirect('/transfers');
            }
            (new ShareService())->transfer(
                $data['share_class_id'],
                $data['seller_id'],
                $data['buyer_id'],
                $data['quantity'],
                $data['transfer_date'],
                $reference
            );
            \App\flash('success', 'Cession enregistrée.');
            redirect('/transfers');
        } catch (\InvalidArgumentException $e) {
            \App\flash('error', $e->getMessage());
            redirect('/transfers/new');
        }
    }

    public function approve(int $id): void
    {
        Csrf::verify();
        try {
            (new ShareService())->approveTransfer($id, Request::str('approval_date', date('Y-m-d')), Request::str('notary_reference'));
            \App\flash('success', 'Cession approuvée : mouvement inscrit au registre.');
        } catch (\InvalidArgumentException $e) {
            \App\flash('error', $e->getMessage());
        }
        redirect('/transfers');
    }

    public function reject(int $id): void
    {
        Csrf::verify();
        (new ShareService())->rejectTransfer($id);
        \App\flash('success', 'Cession rejetée.');
        redirect('/transfers');
    }
}
