<?php use function App\{e, url, __}; ?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-0"><?= __('Edit company') ?></h1>
        <div class="text-muted small"><?= e($tenant['name']) ?></div>
      </div>
      <a href="<?= url('/tenants') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> <?= __('Back') ?></a>
    </div>

    <div class="card bg-white shadow-sm">
      <div class="card-body">
        <form method="post" action="<?= url('/tenants/' . (int) $tenant['id']) ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="mb-2"><label class="form-label"><?= __('Name') ?> *</label>
            <input name="name" class="form-control" required value="<?= e($tenant['name']) ?>"></div>
          <div class="row g-2">
            <div class="col-sm-6"><label class="form-label"><?= __('Legal form') ?></label>
              <select name="legal_form" class="form-select">
                <?php foreach (['SARL', 'SAS', 'SA', 'SNC', 'GIE'] as $form): ?>
                <option <?= ($settings['legal_form'] ?? 'SARL') === $form ? 'selected' : '' ?>><?= e($form) ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-sm-6"><label class="form-label">RCCM</label>
              <input name="rccm" class="form-control" value="<?= e($settings['rccm'] ?? '') ?>" placeholder="CM/DOA/…"></div>
            <div class="col-sm-6"><label class="form-label">NIU</label>
              <input name="niu" class="form-control" value="<?= e($settings['niu'] ?? '') ?>"></div>
            <div class="col-sm-6"><label class="form-label"><?= __('Head office') ?></label>
              <input name="head_office" class="form-control" value="<?= e($settings['head_office'] ?? '') ?>" placeholder="Douala"></div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= __('Save') ?></button>
            <a href="<?= url('/tenants') ?>" class="btn btn-outline-secondary"><?= __('Cancel') ?></a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
