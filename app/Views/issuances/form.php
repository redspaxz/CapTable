<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3">New share issuance</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form id="issuanceForm" method="post" action="<?= url('/issuances') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="share_class_id">Share class *</label>
      <select id="share_class_id" name="share_class_id" class="form-select" required>
        <option value="">— Select —</option>
        <?php foreach ($classes as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $c['remaining'] <= 0 ? 'disabled' : '' ?>
                data-nominal="<?= (int) $c['nominal_value'] ?>" data-remaining="<?= (int) $c['remaining'] ?>">
          <?= e($c['code']) ?> — <?= e($c['name']) ?> (<?= (int) $c['remaining'] ?> available)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Beneficiary shareholder *</label>
      <select name="shareholder_id" class="form-select" required>
        <option value="">— Select —</option>
        <?php foreach ($shareholders as $s): ?>
        <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Contribution type *</label>
      <select name="apport_type" class="form-select">
        <option value="cash">Cash</option>
        <option value="in_kind">In kind</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Number of shares *</label>
      <input type="number" min="1" name="quantity" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Issuance date *</label>
      <input type="date" name="issuance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Reference (AGM, decision)</label>
      <input name="reference" class="form-control" placeholder="E.g. constitutive AG of …">
    </div>
  </div>
  <div id="issuanceSummary" class="alert alert-light border mt-3 mb-2" aria-live="polite"></div>
  <div class="mt-3">
    <button class="btn btn-primary">Save issuance</button>
    <a href="<?= url('/issuances') ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
</div></div>
