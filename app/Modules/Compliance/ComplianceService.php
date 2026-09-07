<?php

declare(strict_types=1);

namespace App\Modules\Compliance;

use App\Core\Database;
use App\Modules\CapTable\OwnershipService;

/**
 * OHADA/Cameroon compliance engine: transfer restrictions (agrément,
 * préemption, inaliénabilité), conventions réglementées (≥10 %) and
 * beneficial-ownership monitoring (COBAC / DGI transparency).
 */
class ComplianceService
{
    public const REGULATED_THRESHOLD = 10.0; // % equity -> convention réglementée (CAC disclosure)
    public const UBO_THRESHOLD = 25.0;       // % control -> beneficial-owner reporting

    public function __construct(private OwnershipService $ownership = new OwnershipService())
    {
    }

    /** Human-readable equity-unit term based on the corporate form. */
    public static function equityUnit(): array
    {
        $form = strtoupper((string) (\App\company()['legal_form'] ?? 'SA'));
        return $form === 'SARL'
            ? ['unit' => 'part sociale', 'plural' => 'parts sociales', 'register' => 'registre des parts']
            : ['unit' => 'action', 'plural' => 'actions', 'register' => 'registre des mouvements de titres'];
    }

    /**
     * Restrictions applying to a transfer of a given class on a date.
     * @return array<int, string> blocking or workflow requirements
     */
    public function transferRestrictions(array $class, string $date): array
    {
        $rules = [];
        $form = strtoupper((string) (\App\company()['legal_form'] ?? 'SA'));
        if ($form === 'SARL') {
            $rules[] = 'SARL : cession à des tiers soumise à l\'agrément des associés (AUSCGIE art. 313 et s.) — transfert enregistré comme « en attente d\'approbation ».';
        }
        if ((int) ($class['requires_approval'] ?? 0) === 1) {
            $rules[] = 'Clause d\'agrément statutaire : approbation préalable requise.';
        }
        if (!empty($class['lockup_until']) && $date < $class['lockup_until']) {
            $rules[] = 'Inaliénabilité (lock-up) jusqu\'au ' . $class['lockup_until'] . ' — OHADA art. 2-1 : 10 ans maximum.';
        }
        return $rules;
    }

    public function isBlocked(array $class, string $date): bool
    {
        foreach ($this->transferRestrictions($class, $date) as $rule) {
            if (str_contains($rule, 'Inaliénabilité')) {
                return true;
            }
        }
        return false;
    }

    public function needsApproval(array $class): bool
    {
        $form = strtoupper((string) (\App\company()['legal_form'] ?? 'SA'));
        return $form === 'SARL' || (int) ($class['requires_approval'] ?? 0) === 1;
    }

    /**
     * Shareholders at or above the regulated threshold with their recent
     * equity movements (conventions réglementées — CAC disclosure alerts).
     */
    public function regulatedParties(): array
    {
        $regulated = [];
        foreach ($this->ownership->byShareholder() as $h) {
            if ($h['percentage'] >= self::REGULATED_THRESHOLD) {
                $movements = Database::all(
                    'SELECT m.*, c.code AS class_code FROM share_movements m
                     LEFT JOIN share_classes c ON c.id = m.share_class_id
                     WHERE m.shareholder_id = ? OR m.counterparty_id = ?
                     ORDER BY m.movement_date DESC LIMIT 10',
                    [$h['shareholder']['id'], $h['shareholder']['id']]
                );
                $regulated[] = $h + ['movements' => $movements];
            }
        }
        return $regulated;
    }

    /** Declared beneficial owners vs computed direct ownership. */
    public function uboStatus(): array
    {
        $declared = Database::all(
            'SELECT bo.*, s.name AS shareholder_name FROM beneficial_owners bo
             LEFT JOIN shareholders s ON s.id = bo.shareholder_id ORDER BY bo.ownership_pct DESC'
        );
        $alerts = [];
        foreach ($this->ownership->byShareholder() as $h) {
            if ($h['percentage'] >= self::UBO_THRESHOLD) {
                $known = false;
                foreach ($declared as $d) {
                    if ((int) ($d['shareholder_id'] ?? 0) === (int) $h['shareholder']['id']) {
                        $known = true;
                        break;
                    }
                }
                if (!$known) {
                    $alerts[] = sprintf(
                        '%s détient %.2f %% (≥ %.0f %%) sans déclaration de bénéficiaire effectif enregistrée.',
                        $h['shareholder']['name'],
                        $h['percentage'],
                        self::UBO_THRESHOLD
                    );
                }
            }
        }
        return ['declared' => $declared, 'alerts' => $alerts];
    }
}
