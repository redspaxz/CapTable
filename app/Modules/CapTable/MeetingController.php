<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Controller;
use App\Core\Request;

class MeetingController extends Controller
{
    public function index(): string
    {
        $kind = Request::str('kind', 'AGE');
        if (!in_array($kind, ['AGO', 'AGE', 'AGC'], true)) {
            $kind = 'AGE';
        }
        // OHADA defaults: AGE quorum 50 % / majority 2/3 ; AGO quorum 25 %... use quarter? AUSCGIE art. 570 s.
        $defaults = ['AGO' => [25.0, 50.0], 'AGE' => [50.0, 66.67], 'AGC' => [50.0, 66.67]];
        [$defaultQuorum, $defaultMajority] = $defaults[$kind];
        $quorum = (float) Request::str('quorum', (string) $defaultQuorum);
        $majority = (float) Request::str('majority', (string) $defaultMajority);
        $quorum = max(0.0, min(100.0, $quorum));
        $majority = max(0.0, min(100.0, $majority));

        $service = new VotingService();
        return $this->view('meeting/index', [
            'title' => 'Assemblée & droits de vote',
            'kind' => $kind,
            'quorum' => $quorum,
            'majority' => $majority,
            'power' => $service->votingPower(),
            'math' => $service->meetingMath($quorum, $majority),
            'company' => \App\company(),
        ]);
    }
}
