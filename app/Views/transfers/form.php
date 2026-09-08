<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3"><?= __('New share transfer') ?></h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form id="transferForm" method="post" action="<?= url('/transfers') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label"><?= __('Class') ?> *</label>
      <select name="share_class_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['code']) ?> — <?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('Seller') ?> *</label>
      <select name="seller_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('Buyer') ?> *</label>
      <select name="buyer_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('Number of shares') ?> *</label>
      <input type="number" min="1" name="quantity" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label"><?= __('Transfer date') ?> *</label>
      <input type="date" name="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label"><?= __('Deed reference') ?></label>
      <input name="deed_reference" class="form-control" placeholder="<?= __('ACT-… (generated if empty)') ?>">
    </div>
  </div>
  <div id="holdingHint" class="form-text mb-2" aria-live="polite"></div>
  <div class="alert alert-light border mt-3 mb-2 small">
    <?= __("The transfer is recorded in the share movement register once the seller's available shares are validated.") ?>
  </div>
  <button class="btn btn-primary"><?= __('Save transfer') ?></button>
  <a href="<?= url('/transfers') ?>" class="btn btn-outline-secondary"><?= __('Cancel') ?></a>
</form>
</div></div>
