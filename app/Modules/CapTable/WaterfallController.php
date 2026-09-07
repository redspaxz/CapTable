<?php

declare(strict_types=1);

namespace App\Modules\CapTable;

use App\Core\Controller;
use App\Core\Request;

class WaterfallController extends Controller
{
    private OwnershipService $ownership;

    public function __construct()
    {
        $this->ownership = new OwnershipService();
    }

    public function index(): string
    {
        $exitValue = Request::int('exit_value', 0);
        if ($exitValue < 0) {
            $exitValue = 0;
        }
        $result = $exitValue > 0 ? $this->ownership->waterfall($exitValue) : null;
        return $this->view('waterfall/index', [
            'title' => 'Liquidation waterfall',
            'exitValue' => $exitValue,
            'result' => $result,
            'company' => \App\company(),
            'totalShares' => $this->ownership->totalShares(),
            'totalCapital' => $this->ownership->totalCapital(),
        ]);
    }
}
