<?php

/** @var App\Core\Router $router */

use App\Core\Auth;
use App\Modules\CapTable\CapTableController;
use App\Modules\CapTable\WaterfallController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Documents\DocumentController;
use App\Modules\Options\OptionController;
use App\Modules\Portals\PortalController;
use App\Modules\Security\AuthController;
use App\Modules\Shares\IssuanceController;
use App\Modules\Shares\ShareClassController;
use App\Modules\Shares\TransferController;
use App\Modules\Shareholders\ShareholderController;

$auth = [Auth::class, 'requireLogin'];
$admin = fn() => Auth::requireRole('admin', 'finance');
$view = fn() => Auth::requireRole('admin', 'finance', 'viewer');

// Diagnostics: PHP version, detected base path, DB connectivity.
// Unauthenticated on purpose (shared-hosting debugging) but never leaks
// credentials: full error text only when APP_DEBUG=true.
$router->get('/health', function () {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        App\Core\Database::pdo();
        $db = 'connected (' . App\Core\App::config('db.driver') . ')';
    } catch (\Throwable $e) {
        $db = App\Core\App::config('app.debug')
            ? 'error: ' . $e->getMessage()
            : 'error ' . $e->getCode() . ' (connexion refusée ou base inaccessible)';
    }
    echo json_encode([
        'app' => 'CapTable',
        'php' => PHP_VERSION,
        'base_path' => App\Core\Request::basePath(),
        'database' => $db,
        'time' => date('c'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return '';
});

// Security
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Dashboard
$router->get('/', [DashboardController::class, 'index'], [$auth]);

// Shareholders
$router->get('/shareholders', [ShareholderController::class, 'index'], [$view]);
$router->get('/shareholders/new', [ShareholderController::class, 'create'], [$admin]);
$router->post('/shareholders', [ShareholderController::class, 'store'], [$admin]);
$router->get('/shareholders/{id}/edit', [ShareholderController::class, 'edit'], [$admin]);
$router->post('/shareholders/{id}', [ShareholderController::class, 'update'], [$admin]);

// Share classes
$router->get('/classes', [ShareClassController::class, 'index'], [$view]);
$router->post('/classes', [ShareClassController::class, 'store'], [$admin]);

// Issuances
$router->get('/issuances', [IssuanceController::class, 'index'], [$view]);
$router->get('/issuances/new', [IssuanceController::class, 'create'], [$admin]);
$router->post('/issuances', [IssuanceController::class, 'store'], [$admin]);

// Transfers
$router->get('/transfers', [TransferController::class, 'index'], [$view]);
$router->get('/transfers/new', [TransferController::class, 'create'], [$admin]);
$router->post('/transfers', [TransferController::class, 'store'], [$admin]);

// Cap table & register
$router->get('/captable', [CapTableController::class, 'index'], [$view]);
$router->get('/captable/export.csv', [CapTableController::class, 'exportCsv'], [$view]);
$router->get('/captable/history', [CapTableController::class, 'history'], [$view]);
$router->get('/waterfall', [WaterfallController::class, 'index'], [$view]);
$router->get('/register', [CapTableController::class, 'register'], [$view]);
$router->get('/api/holdings/{id}', [CapTableController::class, 'holdings'], [$auth]);

// Options & vesting (ESOP)
$router->get('/options', [OptionController::class, 'index'], [$view]);
$router->get('/options/new', [OptionController::class, 'create'], [$admin]);
$router->post('/options', [OptionController::class, 'store'], [$admin]);
$router->get('/options/{id}', [OptionController::class, 'show'], [$view]);
$router->post('/options/{id}/exercise', [OptionController::class, 'exercise'], [$admin]);

// Stakeholder portal
$router->get('/portal', [PortalController::class, 'index'], [$auth]);

// Documents
$router->get('/documents', [DocumentController::class, 'index'], [$view]);
$router->get('/documents/certificates/new', [DocumentController::class, 'certificateForm'], [$admin]);
$router->post('/documents/certificates', [DocumentController::class, 'issueCertificate'], [$admin]);
$router->get('/documents/certificates/{id}', [DocumentController::class, 'certificate'], [$view]);
$router->get('/documents/deeds/{id}', [DocumentController::class, 'deed'], [$view]);
$router->get('/documents/minutes/new', [DocumentController::class, 'minutesForm'], [$admin]);
$router->post('/documents/minutes', [DocumentController::class, 'minutes'], [$admin]);
