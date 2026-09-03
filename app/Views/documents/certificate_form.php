<?php use function App\{e, url}; ?>
<h1 class="h4 mb-3">Émettre un certificat d'actions</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/documents/certificates') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Actionnaire *</label>
      <select name="shareholder_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($shareholders as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Catégorie *</label>
      <select name="share_class_id" class="form-select" required>
        <option value="">—</option>
        <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['code']) ?> — <?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Nombre de titres *</label>
      <input type="number" min="1" name="quantity" class="form-control" required>
      <div class="form-text">Ne peut excéder les titres détenus par l'actionnaire.</div>
    </div>
  </div>
  <button class="btn btn-primary mt-3">Émettre le certificat</button>
  <a href="<?= url('/documents') ?>" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
