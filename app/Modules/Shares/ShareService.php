<?php

declare(strict_types=1);

namespace App\Modules\Shares;

use App\Core\Database;
use App\Modules\CapTable\OwnershipService;

/**
 * Records issuances and transfers. Every operation writes to the
 * append-only share_movements register (registre des mouvements de titres,
 * AUSCGIE art. 716) inside a transaction.
 */
class ShareService
{
    public function __construct(private OwnershipService $ownership = new OwnershipService())
    {
    }

    public function issue(int $classId, int $shareholderId, int $quantity, string $apportType, string $date, string $reference): int
    {
        return Database::transaction(function () use ($classId, $shareholderId, $quantity, $apportType, $date, $reference) {
            Database::execute(
                'INSERT INTO share_issuances (share_class_id, shareholder_id, quantity, apport_type, issuance_date, reference)
                 VALUES (?,?,?,?,?,?)',
                [$classId, $shareholderId, $quantity, $apportType, $date, $reference]
            );
            Database::execute(
                'INSERT INTO share_movements (movement_type, share_class_id, shareholder_id, counterparty_id, quantity, movement_date, reference, created_by)
                 VALUES ("issuance",?,?,NULL,?,?,?,?)',
                [$classId, $shareholderId, $quantity, $date, $reference, $this->currentUserId()]
            );
            $this->adjustHolding($shareholderId, $classId, $quantity);
            return Database::lastId();
        });
    }

    /**
     * @throws \InvalidArgumentException when the seller lacks shares
     */
    public function transfer(int $classId, int $sellerId, int $buyerId, int $quantity, string $date, string $deedReference): void
    {
        if ($sellerId === $buyerId) {
            throw new \InvalidArgumentException('Le cédant et le cessionnaire ne peuvent pas être identiques.');
        }
        Database::transaction(function () use ($classId, $sellerId, $buyerId, $quantity, $date, $deedReference) {
            $available = $this->ownership->holding($sellerId, $classId);
            if ($available < $quantity) {
                throw new \InvalidArgumentException(
                    "Titres insuffisants : le cédant détient {$available} titre(s) dans cette classe."
                );
            }
            Database::execute(
                'INSERT INTO share_transfers (share_class_id, seller_id, buyer_id, quantity, transfer_date, deed_reference)
                 VALUES (?,?,?,?,?,?)',
                [$classId, $sellerId, $buyerId, $quantity, $date, $deedReference]
            );
            Database::execute(
                'INSERT INTO share_movements (movement_type, share_class_id, shareholder_id, counterparty_id, quantity, movement_date, reference, created_by)
                 VALUES ("transfer_out",?,?,?,?,?,?,?)',
                [$classId, $sellerId, $buyerId, $quantity, $date, $deedReference, $this->currentUserId()]
            );
            $this->adjustHolding($sellerId, $classId, -$quantity);
            $this->adjustHolding($buyerId, $classId, $quantity);
        });
    }

    /** Issue a numbered certificate and record it. */
    public function issueCertificate(int $shareholderId, int $classId, int $quantity, string $date): array
    {
        return Database::transaction(function () use ($shareholderId, $classId, $quantity, $date) {
            $number = (string) Database::scalar(
                'SELECT COALESCE(MAX(CAST(SUBSTRING(certificate_number, 4) AS INTEGER)), 0) + 1 FROM share_certificates'
            );
            $code = 'CT-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
            Database::execute(
                'INSERT INTO share_certificates (certificate_number, shareholder_id, share_class_id, quantity, issue_date)
                 VALUES (?,?,?,?,?)',
                [$code, $shareholderId, $classId, $quantity, $date]
            );
            return Database::one('SELECT * FROM share_certificates WHERE id = ?', [Database::lastId()]);
        });
    }

    /**
     * Keep the share_holdings projection in sync with the register. Runs
     * inside the same transaction as the movement write; rows whose net
     * quantity reaches zero are removed so the projection stays minimal.
     * The register remains the source of truth (as-of reads ignore this).
     */
    private function adjustHolding(int $shareholderId, int $classId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }
        $row = Database::one(
            'SELECT quantity FROM share_holdings WHERE shareholder_id = ? AND share_class_id = ?',
            [$shareholderId, $classId]
        );
        $new = ($row ? (int) $row['quantity'] : 0) + $delta;
        if ($new <= 0) {
            Database::execute(
                'DELETE FROM share_holdings WHERE shareholder_id = ? AND share_class_id = ?',
                [$shareholderId, $classId]
            );
        } elseif ($row) {
            Database::execute(
                'UPDATE share_holdings SET quantity = ? WHERE shareholder_id = ? AND share_class_id = ?',
                [$new, $shareholderId, $classId]
            );
        } else {
            Database::execute(
                'INSERT INTO share_holdings (shareholder_id, share_class_id, quantity) VALUES (?,?,?)',
                [$shareholderId, $classId, $new]
            );
        }
    }

    private function currentUserId(): ?int
    {
        $user = \App\Core\Auth::user();
        return $user ? (int) $user['id'] : null;
    }
}
