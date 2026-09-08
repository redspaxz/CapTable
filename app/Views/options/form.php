<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3"><?= __('New option grant') ?></h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/options') ?>" id="grantForm">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="shareholder_id"><?= __('Beneficiary') ?> *</label>
      <select id="shareholder_id" name="shareholder_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="share_class_id"><?= __('Underlying class') ?> *</label>
      <select id="share_class_id" name="share_class_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['code']) ?> — <?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="quantity"><?= __('Number of options') ?> *</label>
      <input type="number" min="1" id="quantity" name="quantity" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="strike_price"><?= __('Exercise price (XAF)') ?></label>
      <input type="number" min="0" id="strike_price" name="strike_price" class="form-control" value="0">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="granted_at"><?= __('Grant date') ?> *</label>
      <input type="date" id="granted_at" name="granted_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="vest_months"><?= __('Vesting (months)') ?> *</label>
      <input type="number" min="1" id="vest_months" name="vest_months" class="form-control" value="48" required>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="cliff_months"><?= __('Cliff (months)') ?></label>
      <input type="number" min="0" id="cliff_months" name="cliff_months" class="form-control" value="12">
      <div class="form-text"><?= __('0 = no cliff') ?></div>
    </div>
    <div class="col-12">
      <label class="form-label" for="notes"><?= __('Notes') ?></label>
      <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
    </div>
  </div>
  <div id="vestingPreview" class="alert alert-light border mt-3" aria-live="polite"></div>
  <button class="btn btn-primary"><?= __('Save grant') ?></button>
  <a href="<?= url('/options') ?>" class="btn btn-outline-secondary"><?= __('Cancel') ?></a>
</form>
</div></div>
