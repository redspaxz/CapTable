<?php use function App\{e, url, __}; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0"><?= __('Companies') ?></h1>
    <div class="text-muted small"><?= __('Multi-tenant share management — pick a company to work on, or onboard a new one.') ?></div>
  </div>
  <button class="btn btn-outline-dark btn-sm" onclick="window.print()">🖨 <?= __('Print') ?></button>
</div>

<div class="row g-3">
  <section class="col-lg-7">
    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-building me-2"></i><?= __('Managed companies') ?></div>
      <table class="table table-hover mb-0" data-enhance="table">
        <thead><tr><th><?= __('Name') ?></th><th><?= __('Legal form') ?></th><th>RCCM</th>
            <th class="text-end"><?= __('Shareholders') ?></th><th class="text-end"><?= __('Users') ?></th><th></th></tr></thead>
        <tbody>
          <?php foreach ($tenants as $t): ?>
          <tr class="<?= $t['id'] === $activeTenantId ? 'table-success' : '' ?>">
            <td class="fw-semibold"><?= e($t['name']) ?>
              <?php if ($t['id'] === $activeTenantId): ?><span class="badge text-bg-success ms-1"><?= __('active') ?></span><?php endif; ?></td>
            <td><?= e($t['legal_form'] ?? '—') ?></td>
            <td><?= e($t['rccm'] ?? '—') ?></td>
            <td class="text-end"><?= (int) $t['shareholder_count'] ?></td>
            <td class="text-end"><?= (int) $t['user_count'] ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="<?= url('/tenant/switch/' . (int) $t['id']) ?>"><?= __('Work on this company') ?></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="col-lg-5">
    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-plus-square me-2"></i><?= __('New company') ?></div>
      <div class="card-body">
        <form method="post" action="<?= url('/tenants') ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="mb-2"><label class="form-label"><?= __('Name') ?> *</label>
            <input name="name" class="form-control" required placeholder="<?= e(__('Company name')) ?>"></div>
          <div class="row g-2">
            <div class="col-6"><label class="form-label"><?= __('Legal form') ?></label>
              <select name="legal_form" class="form-select">
                <option>SARL</option><option>SAS</option><option>SA</option><option>SNC</option><option>GIE</option>
              </select></div>
            <div class="col-6"><label class="form-label">RCCM</label>
              <input name="rccm" class="form-control" placeholder="CM/DOA/…"></div>
            <div class="col-6"><label class="form-label">NIU</label>
              <input name="niu" class="form-control"></div>
            <div class="col-6"><label class="form-label"><?= __('Head office') ?></label>
              <input name="head_office" class="form-control" placeholder="Douala"></div>
          </div>
          <hr class="my-3">
          <p class="text-muted small mb-2"><?= __('Administrator account for this company') ?></p>
          <div class="mb-2"><label class="form-label"><?= __('Name') ?> *</label>
            <input name="admin_name" class="form-control" required></div>
          <div class="mb-2"><label class="form-label"><?= __('Email') ?> *</label>
            <input type="email" name="admin_email" class="form-control" required></div>
          <div class="mb-3"><label class="form-label"><?= __('Password') ?> *</label>
            <input type="password" name="admin_password" class="form-control" minlength="8" required>
            <div class="form-text"><?= __('At least 8 characters.') ?></div></div>
          <button class="btn btn-primary w-100"><?= __('Create the company') ?></button>
        </form>
      </div>
    </div>
  </section>
</div>
