<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3"><?= __('Declare a beneficial owner') ?></h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/compliance/ubo') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-5"><label class="form-label"><?= __('Full name') ?> *</label>
      <input name="name" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label"><?= __('ID document') ?> *</label>
      <input name="id_number" class="form-control" placeholder="<?= __('National ID / passport') ?>" required></div>
    <div class="col-md-2"><label class="form-label"><?= __('Nationality') ?></label>
      <input name="nationality" class="form-control" value="Cameroonian"></div>
    <div class="col-md-2"><label class="form-label"><?= __('% control') ?> *</label>
      <input type="number" min="0" max="100" step="0.01" name="ownership_pct" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label"><?= __('Linked shareholder (if direct)') ?></label>
      <select name="shareholder_id" class="form-select">
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-4"><label class="form-label"><?= __('Nature of control') ?></label>
      <input name="control_nature" class="form-control" placeholder="<?= __('Direct holding, control chain, voting rights…') ?>"></div>
    <div class="col-md-4"><label class="form-label"><?= __('Declaration date') ?></label>
      <input type="date" name="declared_at" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    <div class="col-12"><label class="form-label"><?= __('Notes') ?></label>
      <textarea name="notes" class="form-control" rows="2"></textarea></div>
  </div>
  <button class="btn btn-primary mt-3"><?= __('Save declaration') ?></button>
  <a href="<?= url('/compliance') ?>" class="btn btn-outline-secondary"><?= __('Cancel') ?></a>
</form>
</div></div>
