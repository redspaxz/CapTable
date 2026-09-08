<?php

declare(strict_types=1);

namespace App\Modules\Options;

use App\Core\Database;
use App\Modules\Shares\ShareService;

/**
 * ESOP: option grants with monthly vesting (standard cliff schedule),
 * schedule computation, and exercise — exercising automatically issues
 * the underlying shares and writes to the register of title movements.
 */
class OptionService
{
    public function __construct(private ShareService $shares = new ShareService())
    {
    }

    public function grant(
        int $beneficiaryId,
        int $classId,
        int $quantity,
        int $strikePrice,
        string $grantedAt,
        int $vestMonths,
        int $cliffMonths,
        string $notes = ''
    ): int {
        if ($quantity <= 0 || $vestMonths <= 0) {
            throw new \InvalidArgumentException(__('Quantity and vesting duration must be positive.'));
        }
        if ($cliffMonths < 0 || $cliffMonths > $vestMonths) {
            throw new \InvalidArgumentException(__('The cliff must be between 0 and the vesting duration.'));
        }
        $class = Database::one('SELECT * FROM share_classes WHERE id = ?', [$classId]);
        if (!$class) {
            throw new \InvalidArgumentException(__('Unknown share class.'));
        }
        Database::execute(
            'INSERT INTO option_grants (shareholder_id, share_class_id, quantity, strike_price, granted_at, vest_months, cliff_months, notes)
             VALUES (?,?,?,?,?,?,?,?)',
            [$beneficiaryId, $classId, $quantity, $strikePrice, $grantedAt, $vestMonths, $cliffMonths, $notes]
        );
        return Database::lastId();
    }

    /** Whole months elapsed since grant (never negative). */
    public function elapsedMonths(array $grant, ?string $asOf = null): int
    {
        $from = new \DateTimeImmutable($grant['granted_at']);
        $to = new \DateTimeImmutable($asOf ?? date('Y-m-d'));
        return max(0, (int) $from->diff($to)->format('%y') * 12 + (int) $from->diff($to)->format('%m'));
    }

    /** Vested quantity as of a date: 0 before the cliff, then linear monthly. */
    public function vestedQty(array $grant, ?string $asOf = null): int
    {
        $elapsed = $this->elapsedMonths($grant, $asOf);
        if ($elapsed < (int) $grant['cliff_months']) {
            return 0;
        }
        if ($elapsed >= (int) $grant['vest_months']) {
            return (int) $grant['quantity'];
        }
        return (int) floor((int) $grant['quantity'] * $elapsed / (int) $grant['vest_months']);
    }

    /** Options currently exercisable (vested minus already exercised). */
    public function exercisableQty(array $grant, ?string $asOf = null): int
    {
        return max(0, $this->vestedQty($grant, $asOf) - (int) $grant['exercised_qty']);
    }

    /**
     * Monthly vesting schedule for display.
     * @return array<int, array{date: string, cumulative: int, tranche: int, kind: string}>
     */
    public function schedule(array $grant): array
    {
        $qty = (int) $grant['quantity'];
        $vest = (int) $grant['vest_months'];
        $cliff = (int) $grant['cliff_months'];
        $from = new \DateTimeImmutable($grant['granted_at']);
        $rows = [];
        $prev = 0;
        for ($m = 1; $m <= $vest; $m++) {
            $cumulative = $m >= $vest ? $qty : (int) floor($qty * $m / $vest);
            $date = $from->add(new \DateInterval('P' . $m . 'M'))->format('Y-m-d');
            $kind = $m === $cliff ? 'cliff' : ($m === $vest ? 'final' : 'monthly');
            $rows[] = ['date' => $date, 'cumulative' => $cumulative, 'tranche' => $cumulative - $prev, 'kind' => $kind];
            $prev = $cumulative;
        }
        return $rows;
    }

    /**
     * Exercise options: atomically records the exercise, issues the shares
     * (register of title movements) and links the two.
     * @throws \InvalidArgumentException when quantity exceeds exercisable
     */
    public function exercise(int $grantId, int $quantity, string $date, string $reference = ''): int
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException(__('Invalid exercise quantity.'));
        }
        $grant = Database::one('SELECT * FROM option_grants WHERE id = ?', [$grantId]);
        if (!$grant) {
            throw new \InvalidArgumentException(__('Grant not found.'));
        }
        $available = $this->exercisableQty($grant);
        if ($quantity > $available) {
            throw new \InvalidArgumentException(
                __('Insufficient options: :available exercisable (vesting not elapsed or already exercised).', ['available' => $available])
            );
        }

        $reference = $reference !== '' ? $reference : 'EX-' . date('Ymd', strtotime($date)) . '-' . random_int(100, 999);

        return Database::transaction(function () use ($grant, $quantity, $date, $reference) {
            $issuanceId = $this->shares->issue(
                (int) $grant['share_class_id'],
                (int) $grant['shareholder_id'],
                $quantity,
                'cash',
                $date,
                $reference
            );
            Database::execute(
                'INSERT INTO option_exercises (grant_id, quantity, exercise_date, reference, share_issuance_id, created_by)
                 VALUES (?,?,?,?,?,?)',
                [$grant['id'], $quantity, $date, $reference, $issuanceId, \App\Core\Auth::user()['id'] ?? null]
            );
            Database::execute(
                'UPDATE option_grants SET exercised_qty = exercised_qty + ? WHERE id = ?',
                [$quantity, $grant['id']]
            );
            return $issuanceId;
        });
    }

    /** @return array<int, array> grants enriched with live vesting figures */
    public function grantsWithVesting(?int $shareholderId = null): array
    {
        $sql = 'SELECT g.*, s.name AS beneficiary, c.code AS class_code, c.nominal_value
                FROM option_grants g
                JOIN shareholders s ON s.id = g.shareholder_id
                JOIN share_classes c ON c.id = g.share_class_id';
        $params = [];
        if ($shareholderId !== null) {
            $sql .= ' WHERE g.shareholder_id = ?';
            $params[] = $shareholderId;
        }
        $sql .= ' ORDER BY g.granted_at DESC, g.id DESC';
        $grants = Database::all($sql, $params);
        foreach ($grants as &$grant) {
            $grant['vested'] = $this->vestedQty($grant);
            $grant['exercisable'] = $this->exercisableQty($grant);
        }
        return $grants;
    }

    /** Reference value per share for "valeur acquise" displays. */
    public function referencePrice(): int
    {
        $fmv = \App\company()['fmv_per_share'] ?? null;
        if ($fmv !== null && (int) $fmv > 0) {
            return (int) $fmv;
        }
        return (int) (Database::scalar('SELECT MIN(nominal_value) FROM share_classes WHERE id IN (SELECT DISTINCT share_class_id FROM share_movements)') ?: 0);
    }

    /** Vested value = vested x (reference price - strike), floored at 0. */
    public function vestedValue(array $grant): int
    {
        return max(0, (int) floor($this->vestedQty($grant) * ($this->referencePrice() - (int) $grant['strike_price'])));
    }
}
