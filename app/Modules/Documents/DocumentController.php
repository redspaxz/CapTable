<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Modules\CapTable\OwnershipService;
use App\Modules\Shares\ShareService;

class DocumentController extends Controller
{
    public function index(): string
    {
        return $this->view('documents/index', [
            'title' => 'Documents & reports',
            'certificates' => Database::all(
                'SELECT cert.*, s.name AS shareholder_name, c.code AS class_code
                 FROM share_certificates cert
                 JOIN shareholders s ON s.id = cert.shareholder_id
                 JOIN share_classes c ON c.id = cert.share_class_id
                 ORDER BY cert.id DESC'
            ),
        ]);
    }

    public function certificateForm(): string
    {
        return $this->view('documents/certificate_form', [
            'title' => 'Issue a share certificate',
            'classes' => Database::all('SELECT * FROM share_classes ORDER BY code'),
            'shareholders' => Database::all('SELECT * FROM shareholders ORDER BY name'),
        ]);
    }

    public function issueCertificate(): void
    {
        Csrf::verify();
        $shareholderId = Request::int('shareholder_id');
        $classId = Request::int('share_class_id');
        $quantity = Request::int('quantity');
        $holding = (new OwnershipService())->holding($shareholderId, $classId);
        if ($quantity <= 0 || $quantity > $holding) {
            \App\flash('error', "Invalid quantity: the shareholder holds {$holding} share(s) in this class.");
            redirect('/documents/certificates/new');
        }
        (new ShareService())->issueCertificate($shareholderId, $classId, $quantity, date('Y-m-d'));
        \App\flash('success', 'Certificate issued.');
        redirect('/documents');
    }

    public function certificate(int $id): string
    {
        $cert = Database::one(
            'SELECT cert.*, s.name AS shareholder_name, s.id_number, s.type AS shareholder_type, c.code, c.name AS class_name
             FROM share_certificates cert
             JOIN shareholders s ON s.id = cert.shareholder_id
             JOIN share_classes c ON c.id = cert.share_class_id
             WHERE cert.id = ?',
            [$id]
        );
        if (!$cert) {
            redirect('/documents');
        }
        return $this->view('documents/certificate', [
            'title' => 'Certificat ' . $cert['certificate_number'],
            'cert' => $cert,
            'company' => \App\company(),
        ]);
    }

    public function deed(int $transferId): string
    {
        $transfer = Database::one(
            'SELECT t.*, seller.name AS seller_name, seller.id_number AS seller_id_number,
                    buyer.name AS buyer_name, buyer.id_number AS buyer_id_number,
                    c.code AS class_code, c.name AS class_name, c.nominal_value
             FROM share_transfers t
             JOIN shareholders seller ON seller.id = t.seller_id
             JOIN shareholders buyer ON buyer.id = t.buyer_id
             JOIN share_classes c ON c.id = t.share_class_id
             WHERE t.id = ?',
            [$transferId]
        );
        if (!$transfer) {
            redirect('/transfers');
        }
        return $this->view('documents/deed', [
            'title' => 'Transfer deed — ' . $transfer['deed_reference'],
            'transfer' => $transfer,
            'company' => \App\company(),
        ]);
    }

    public function minutesForm(): string
    {
        return $this->view('documents/minutes_form', [
            'title' => 'General meeting minutes',
            'holdings' => (new OwnershipService())->byShareholder(),
        ]);
    }

    public function minutes(): string
    {
        Csrf::verify();
        $data = [
            'meeting_type' => Request::str('meeting_type', 'AGE'),
            'meeting_date' => Request::str('meeting_date', date('Y-m-d')),
            'location' => Request::str('location', 'Registered office'),
            'agenda' => Request::str('agenda'),
            'resolutions' => Request::str('resolutions'),
        ];
        $v = new Validator($data);
        $v->required('meeting_type', 'meeting_date', 'agenda', 'resolutions')->date('meeting_date');
        if ($v->fails()) {
            \App\flash('error', 'Type, date, agenda and resolutions are required.');
            redirect('/documents/minutes/new');
        }
        Database::execute(
            'INSERT INTO documents (type, title, ref, payload, created_by) VALUES (?,?,?,?,?)',
            [
                'minutes',
                'PV ' . $data['meeting_type'] . ' du ' . $data['meeting_date'],
                'PV-' . date('Ymd', strtotime($data['meeting_date'])),
                json_encode($data, JSON_UNESCAPED_UNICODE),
                \App\Core\Auth::user()['id'] ?? null,
            ]
        );
        return $this->view('documents/minutes', [
            'title' => 'Meeting minutes',
            'data' => $data,
            'holdings' => (new OwnershipService())->byShareholder(),
            'company' => \App\company(),
        ]);
    }
}
