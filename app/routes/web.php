<?php

/** @var App\Core\Router $router */

use App\Core\Auth;
use App\Core\Tenancy;
use App\Modules\CapTable\CapTableController;
use App\Modules\CapTable\ConvertibleController;
use App\Modules\CapTable\MeetingController;
use App\Modules\CapTable\WaterfallController;
use App\Modules\Compliance\ComplianceController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Documents\DocumentController;
use App\Modules\Options\OptionController;
use App\Modules\Portals\PortalController;
use App\Modules\Security\AuthController;
use App\Modules\Settings\SettingsController;
use App\Modules\Shares\IssuanceController;
use App\Modules\Shares\ShareClassController;
use App\Modules\Shares\TransferController;
use App\Modules\Shareholders\ShareholderController;
use App\Modules\Tenants\TenantController;

$auth = [Auth::class, 'requireLogin'];
$admin = fn() => Auth::requireRole('admin', 'finance');
$view = fn() => Auth::requireRole('admin', 'finance', 'viewer', 'auditor');
$super = fn() => Auth::requireRole('superadmin');
$tenant = [Tenancy::class, 'requireActive']; // pages below need an active company

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
        'app' => 'T&T',
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

// UI language switcher (session-wide; default configured in Settings)
$router->get('/lang/{code}', function (string $code) {
    \App\Core\Lang::set($code);
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    $base = rtrim(url('/'), '/');
    if ($back !== '' && str_starts_with($back, $base)) {
        header('Location: ' . $back);
    } else {
        \App\redirect('/tenants');
    }
    return '';
});

// Tenant management (global super-admin). No active-tenant requirement:
// these pages exist precisely to pick one.
$router->get('/tenants', [TenantController::class, 'index'], [$super]);
$router->post('/tenants', [TenantController::class, 'store'], [$super]);
$router->get('/tenant/switch/{id}', [TenantController::class, 'switch'], [$super]);

// Settings (admin)
$router->get('/settings', [SettingsController::class, 'index'], [$admin, $tenant]);
$router->post('/settings', [SettingsController::class, 'update'], [$admin, $tenant]);

// Dashboard
$router->get('/', [DashboardController::class, 'index'], [$auth, $tenant]);

// Shareholders
$router->get('/shareholders', [ShareholderController::class, 'index'], [$view, $tenant]);
$router->get('/shareholders/new', [ShareholderController::class, 'create'], [$admin, $tenant]);
$router->post('/shareholders', [ShareholderController::class, 'store'], [$admin, $tenant]);
$router->get('/shareholders/{id}/edit', [ShareholderController::class, 'edit'], [$admin, $tenant]);
$router->post('/shareholders/{id}', [ShareholderController::class, 'update'], [$admin, $tenant]);

// Share classes
$router->get('/classes', [ShareClassController::class, 'index'], [$view, $tenant]);
$router->post('/classes', [ShareClassController::class, 'store'], [$admin, $tenant]);

// Issuances
$router->get('/issuances', [IssuanceController::class, 'index'], [$view, $tenant]);
$router->get('/issuances/new', [IssuanceController::class, 'create'], [$admin, $tenant]);
$router->post('/issuances', [IssuanceController::class, 'store'], [$admin, $tenant]);

// Transfers (with agrément / pre-emption workflow)
$router->get('/transfers', [TransferController::class, 'index'], [$view, $tenant]);
$router->get('/transfers/new', [TransferController::class, 'create'], [$admin, $tenant]);
$router->post('/transfers', [TransferController::class, 'store'], [$admin, $tenant]);
$router->post('/transfers/{id}/approve', [TransferController::class, 'approve'], [$admin, $tenant]);
$router->post('/transfers/{id}/reject', [TransferController::class, 'reject'], [$admin, $tenant]);

// Cap table & register
$router->get('/captable', [CapTableController::class, 'index'], [$view, $tenant]);
$router->get('/captable/export.csv', [CapTableController::class, 'exportCsv'], [$view, $tenant]);
$router->get('/captable/history', [CapTableController::class, 'history'], [$view, $tenant]);
$router->get('/waterfall', [WaterfallController::class, 'index'], [$view, $tenant]);
$router->get('/register', [CapTableController::class, 'register'], [$view, $tenant]);
$router->get('/api/holdings/{id}', [CapTableController::class, 'holdings'], [$auth, $tenant]);

// Options & vesting (ESOP)
$router->get('/options', [OptionController::class, 'index'], [$view, $tenant]);
$router->get('/options/new', [OptionController::class, 'create'], [$admin, $tenant]);
$router->post('/options', [OptionController::class, 'store'], [$admin, $tenant]);
$router->get('/options/{id}', [OptionController::class, 'show'], [$view, $tenant]);
$router->post('/options/{id}/exercise', [OptionController::class, 'exercise'], [$admin, $tenant]);

// Stakeholder portal
$router->get('/portal', [PortalController::class, 'index'], [$auth, $tenant]);

// Compliance (OHADA / COBAC / DGI)
$router->get('/compliance', [ComplianceController::class, 'index'], [$view, $tenant]);
$router->get('/compliance/ubo/new', [ComplianceController::class, 'uboForm'], [$admin, $tenant]);
$router->post('/compliance/ubo', [ComplianceController::class, 'storeUbo'], [$admin, $tenant]);

// Convertibles (OCA / BSA / SAFE)
$router->get('/convertibles', [ConvertibleController::class, 'index'], [$view, $tenant]);
$router->post('/convertibles', [ConvertibleController::class, 'store'], [$admin, $tenant]);

// General meeting & voting engine
$router->get('/meeting', [MeetingController::class, 'index'], [$view, $tenant]);

// Documents
$router->get('/documents', [DocumentController::class, 'index'], [$view, $tenant]);
$router->get('/documents/certificates/new', [DocumentController::class, 'certificateForm'], [$admin, $tenant]);
$router->post('/documents/certificates', [DocumentController::class, 'issueCertificate'], [$admin, $tenant]);
$router->get('/documents/certificates/{id}', [DocumentController::class, 'certificate'], [$view, $tenant]);
$router->get('/documents/deeds/{id}', [DocumentController::class, 'deed'], [$view, $tenant]);
$router->get('/documents/minutes/new', [DocumentController::class, 'minutesForm'], [$admin, $tenant]);
$router->post('/documents/minutes', [DocumentController::class, 'minutes'], [$admin, $tenant]);
