<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Database;

/**
 * General-meeting voting engine: per-class voting weight (1 ordinaire,
 * 0 pour les actions à dividende prioritaire sans droit de vote,
 * 2 pour le droit de vote double), quorum and majority checks per
 * OHADA AUSCGIE defaults (AGE : quorum 50 %, majorité 2/3 ; AGO : simple).
 */
class VotingService
{
    /** @return array<int, array{shareholder: array, votes: int, pct: float, detail: array}> */
    public function votingPower(): array
    {
        $classes = [];
        foreach (Database::all('SELECT * FROM share_classes') as $c) {
            $classes[(int) $c['id']] = $c;
        }
        $power = [];
        $ownership = (new OwnershipService())->byShareholder();
        foreach ($ownership as $h) {
            $votes = 0;
            $detail = [];
            foreach ($h['rows'] as $row) {
                $weight = (int) ($classes[(int) $row['class']['id']]['voting_weight'] ?? 1);
                $classVotes = $row['quantity'] * $weight;
                $votes += $classVotes;
                $detail[] = sprintf('%s ×%d → %s voix', $row['class']['code'], $weight, number_format((float) $classVotes, 0, ',', ' '));
            }
            if ($votes > 0) {
                $power[] = ['shareholder' => $h['shareholder'], 'votes' => $votes, 'pct' => 0.0, 'detail' => $detail];
            }
        }
        $total = array_sum(array_map(fn($p) => $p['votes'], $power));
        foreach ($power as &$p) {
            $p['pct'] = $total > 0 ? $p['votes'] / $total * 100 : 0.0;
        }
        usort($power, fn($a, $b) => $b['votes'] <=> $a['votes']);
        return ['total' => $total, 'holders' => $power];
    }

    /**
     * @return array{quorum_met: bool, quorum_required: int, votes_for_required: int, blocking: int}
     */
    public function meetingMath(float $quorumPct, float $majorityPct): array
    {
        $power = $this->votingPower();
        $total = $power['total'];
        $quorumRequired = (int) ceil($total * $quorumPct / 100);
        $votesForRequired = (int) floor($total * $majorityPct / 100) + 1;
        $blocking = max(0, $total - $votesForRequired + 1);
        return [
            'quorum_met' => $total >= $quorumRequired, // all voting rights present by default
            'quorum_required' => $quorumRequired,
            'votes_for_required' => $votesForRequired,
            'blocking' => $blocking,
        ];
    }
}
