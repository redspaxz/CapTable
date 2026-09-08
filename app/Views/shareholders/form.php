<?php use function App\{e, url}; $s = $shareholder; $action = $s ? url('/shareholders/' . $s['id']) : url('/shareholders'); ?>
<h1 class="h4 mb-3"><?= $s ? __('Edit shareholder') : __('New shareholder') ?></h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label"><?= __('Type') ?></label>
      <select name="type" class="form-select">
        <option value="individual" <?= ($s['type'] ?? '') === 'individual' ? 'selected' : '' ?>><?= __('Individual') ?></option>
        <option value="corporate" <?= ($s['type'] ?? '') === 'corporate' ? 'selected' : '' ?>><?= __('Corporate') ?></option>
      </select>
    </div>
    <div class="col-md-8">
      <label class="form-label"><?= __('Name / Legal name') ?> *</label>
      <input name="name" class="form-control" required value="<?= e($s['name'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('ID document') ?></label>
      <select name="id_type" class="form-select">
        <?php foreach (['CNI', 'Passeport', 'RC'] as $t): ?>
        <option <?= ($s['id_type'] ?? 'CNI') === $t ? 'selected' : '' ?>><?= $t === 'Passeport' ? __('Passport') : $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('ID number') ?> *</label>
      <input name="id_number" class="form-control" required value="<?= e($s['id_number'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('Nationality') ?></label>
      <input name="nationality" class="form-control" value="<?= e($s['nationality'] ?? 'Cameroonian') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('Phone') ?></label>
      <input name="phone" class="form-control" value="<?= e($s['phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label"><?= __('Address') ?></label>
      <input name="address" class="form-control" value="<?= e($s['address'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label"><?= __('Email') ?></label>
      <input type="email" name="email" class="form-control" value="<?= e($s['email'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label"><?= __('Notes') ?></label>
      <textarea name="notes" class="form-control" rows="2"><?= e($s['notes'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary"><?= __('Save') ?></button>
    <a href="<?= url('/shareholders') ?>" class="btn btn-outline-secondary"><?= __('Cancel') ?></a>
  </div>
</form>
</div></div>
