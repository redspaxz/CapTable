<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3">Nouvelle émission d'actions</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form id="issuanceForm" method="post" action="<?= url('/issuances') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="share_class_id">Catégorie d'actions *</label>
      <select id="share_class_id" name="share_class_id" class="form-select" required>
        <option value="">— Choisir —</option>
        <?php foreach ($classes as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $c['remaining'] <= 0 ? 'disabled' : '' ?>
                data-nominal="<?= (int) $c['nominal_value'] ?>" data-remaining="<?= (int) $c['remaining'] ?>">
          <?= e($c['code']) ?> — <?= e($c['name']) ?> (<?= (int) $c['remaining'] ?> dispo)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Actionnaire bénéficiaire *</label>
      <select name="shareholder_id" class="form-select" required>
        <option value="">— Choisir —</option>
        <?php foreach ($shareholders as $s): ?>
        <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Type d'apport *</label>
      <select name="apport_type" class="form-select">
        <option value="cash">Numéraire</option>
        <option value="in_kind">Nature</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Nombre de titres *</label>
      <input type="number" min="1" name="quantity" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date d'émission *</label>
      <input type="date" name="issuance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Référence (AG, décision)</label>
      <input name="reference" class="form-control" placeholder="AG constitutive du …">
    </div>
  </div>
  <div id="issuanceSummary" class="alert alert-light border mt-3 mb-2" aria-live="polite"></div>
  <div class="mt-3">
    <button class="btn btn-primary">Enregistrer l'émission</button>
    <a href="<?= url('/issuances') ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
</div></div>
