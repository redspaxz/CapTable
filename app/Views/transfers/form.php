<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3">Nouvelle cession de droits sociaux</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/transfers') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Catégorie *</label>
      <select name="share_class_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['code']) ?> — <?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Cédant (vendeur) *</label>
      <select name="seller_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Cessionnaire (acheteur) *</label>
      <select name="buyer_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Nombre de titres *</label>
      <input type="number" min="1" name="quantity" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date de cession *</label>
      <input type="date" name="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Référence de l'acte</label>
      <input name="deed_reference" class="form-control" placeholder="ACT-… (généré si vide)">
    </div>
  </div>
  <div class="alert alert-light border mt-3 mb-2 small">
    La cession sera inscrite au registre des mouvements de titres après validation des disponibilités du cédant.
  </div>
  <button class="btn btn-primary">Enregistrer la cession</button>
  <a href="<?= url('/transfers') ?>" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
