<?php

declare(strict_types=1);

namespace App\Modules\Tenants;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Tenancy;
use App\Core\Validator;

/**
 * Global super-admin: company directory, onboarding of new companies and
 * switching the active tenant context.
 */
class TenantController extends Controller
{
    public function index(): string
    {
        $tenants = Database::all(
            'SELECT t.*, s.legal_form, s.rccm,
                    (SELECT COUNT(*) FROM shareholders sh WHERE sh.tenant_id = t.id) AS shareholder_count,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS user_count
             FROM tenants t
             LEFT JOIN settings s ON s.tenant_id = t.id
             WHERE t.is_active = 1
             ORDER BY t.name'
        );
        return $this->view('tenants/index', [
            'title' => 'Companies',
            'tenants' => $tenants,
            'activeTenantId' => Tenancy::id(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'name' => trim(Request::str('name', '')),
            'legal_form' => trim(Request::str('legal_form', 'SARL')),
            'rccm' => trim(Request::str('rccm', '')),
            'niu' => trim(Request::str('niu', '')),
            'head_office' => trim(Request::str('head_office', '')),
            'admin_name' => trim(Request::str('admin_name', '')),
            'admin_email' => trim(Request::str('admin_email', '')),
            'admin_password' => Request::str('admin_password', ''),
        ];
        $v = new Validator($data);
        $v->required('name', 'admin_name', 'admin_email');
        if ($v->fails() || $data['admin_email'] === '' || !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)
            || Database::one('SELECT id FROM users WHERE email = ?', [$data['admin_email']])
            || strlen($data['admin_password']) < 8) {
            \App\flash('error', __('Company name, administrator name, a unique valid e-mail and a password of at least 8 characters are required.'));
            \App\redirect('/tenants');
        }

        $tenantId = Database::transaction(function () use ($data): int {
            Database::execute('INSERT INTO tenants (name) VALUES (?)', [$data['name']]);
            $tenantId = Database::lastId();
            Database::execute(
                'INSERT INTO settings (tenant_id, company_name, legal_form, rccm, niu, head_office)
                 VALUES (?,?,?,?,?,?)',
                [$tenantId, $data['name'], $data['legal_form'], $data['rccm'], $data['niu'], $data['head_office']]
            );
            Database::execute(
                'INSERT INTO users (name, email, password_hash, role, tenant_id) VALUES (?,?,?,?,?)',
                [$data['admin_name'], $data['admin_email'],
                 password_hash($data['admin_password'], PASSWORD_DEFAULT), 'admin', $tenantId]
            );
            return (int) $tenantId;
        });

        \App\flash('success', __('Company ":name" created with its administrator account.', ['name' => $data['name']]));
        \App\redirect('/tenants');
    }

    public function switch(string $id): void
    {
        Tenancy::switchTo((int) $id);
        \App\flash('success', __('Active company switched.'));
        \App\redirect('/');
    }
}
