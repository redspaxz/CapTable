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
            throw new \InvalidArgumentException('The seller and the buyer cannot be the same person.');
        }
        Database::transaction(function () use ($classId, $sellerId, $buyerId, $quantity, $date, $deedReference) {
            $available = $this->ownership->holding($sellerId, $classId);
            if ($available < $quantity) {
                throw new \InvalidArgumentException(
                    "Insufficient shares: the seller holds only {$available} share(s) in this class."
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

    /**
     * Record a transfer request pending approval (clause d'agrément /
     * SARL consent). No register movement is written until approval.
     */
    public function requestTransfer(
        int $classId,
        int $sellerId,
        int $buyerId,
        int $quantity,
        string $date,
        string $deedReference,
        string $preemptionDeadline
    ): int {
        if ($sellerId === $buyerId) {
            throw new \InvalidArgumentException('The seller and the buyer cannot be the same person.');
        }
        $available = $this->ownership->holding($sellerId, $classId);
        if ($available < $quantity) {
            throw new \InvalidArgumentException(
                "Insufficient shares: the seller holds only {$available} share(s) in this class."
            );
        }
        Database::execute(
            'INSERT INTO share_transfers (share_class_id, seller_id, buyer_id, quantity, transfer_date, deed_reference, status, preemption_deadline)
             VALUES (?,?,?,?,?,?,?,?)',
            [$classId, $sellerId, $buyerId, $quantity, $date, $deedReference, 'pending', $preemptionDeadline]
        );
        return Database::lastId();
    }

    /**
     * Approve a pending transfer: writes the register movement and
     * marks the deed executed (opposable aux tiers).
     */
    public function approveTransfer(int $transferId, string $approvalDate, string $notaryReference = ''): void
    {
        Database::transaction(function () use ($transferId, $approvalDate, $notaryReference) {
            $transfer = Database::one('SELECT * FROM share_transfers WHERE id = ?', [$transferId]);
            if (!$transfer || $transfer['status'] !== 'pending') {
                throw new \InvalidArgumentException('Transfer not found or already processed.');
            }
            $class = Database::one('SELECT * FROM share_classes WHERE id = ?', [$transfer['share_class_id']]);
            $compliance = new \App\Modules\Compliance\ComplianceService();
            if ($compliance->isBlocked($class, $approvalDate)) {
                throw new \InvalidArgumentException('Transfer still subject to lock-up (inalienability).');
            }
            $available = $this->ownership->holding((int) $transfer['seller_id'], (int) $transfer['share_class_id']);
            if ($available < (int) $transfer['quantity']) {
                throw new \InvalidArgumentException("Insufficient shares: {$available} available.");
            }
            Database::execute(
                'INSERT INTO share_movements (movement_type, share_class_id, shareholder_id, counterparty_id, quantity, movement_date, reference, notary_reference, created_by)
                 VALUES ("transfer_out",?,?,?,?,?,?,?,?)',
                [$transfer['share_class_id'], $transfer['seller_id'], $transfer['buyer_id'],
                 $transfer['quantity'], $approvalDate, $transfer['deed_reference'],
                 $notaryReference !== '' ? $notaryReference : null, \App\Core\Auth::user()['id'] ?? null]
            );
            $this->adjustHolding((int) $transfer['seller_id'], (int) $transfer['share_class_id'], -(int) $transfer['quantity']);
            $this->adjustHolding((int) $transfer['buyer_id'], (int) $transfer['share_class_id'], (int) $transfer['quantity']);
            Database::execute(
                'UPDATE share_transfers SET status = "approved", approval_date = ?, notary_reference = ? WHERE id = ?',
                [$approvalDate, $notaryReference !== '' ? $notaryReference : null, $transferId]
            );
        });
    }

    public function rejectTransfer(int $transferId): void
    {
        Database::execute(
            'UPDATE share_transfers SET status = "rejected" WHERE id = ? AND status = "pending"',
            [$transferId]
        );
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
     * When the projection table has not been deployed yet (install predates
     * database/migrate_holdings.php), maintenance is skipped silently and
     * OwnershipService reads fall back to the register, so writes keep
     * working and the projection self-heals after the migration runs.
     */
    private function adjustHolding(int $shareholderId, int $classId, int $delta): void
    {
        if ($delta === 0 || !OwnershipService::projectionAvailable()) {
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
