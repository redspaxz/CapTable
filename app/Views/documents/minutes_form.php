<?php use function App\{e, url, shares, pct}; ?>
<h1 class="h4 mb-3">Rédiger un PV d'assemblée générale</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/documents/minutes') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Type d'assemblée *</label>
      <select name="meeting_type" class="form-select">
        <option value="AGE">AGE (extraordinaire)</option>
        <option value="AGO">AGO (ordinaire)</option>
        <option value="AGC">AG constitutive</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date *</label>
      <input type="date" name="meeting_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Lieu *</label>
      <input name="location" class="form-control" value="Siège social" required>
    </div>
    <div class="col-12">
      <label class="form-label">Ordre du jour *</label>
      <textarea name="agenda" class="form-control" rows="3" required></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Résolutions adoptées *</label>
      <textarea name="resolutions" class="form-control" rows="5" required></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Générer le PV</button>
    <a href="<?= url('/documents') ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
</div></div>

<h2 class="h6 mt-4">Présence prévue (répartition actuelle)</h2>
<table class="table table-sm bg-white shadow-sm w-auto">
  <thead><tr><th>Actionnaire</th><th class="text-end">Titres</th><th class="text-end">% — droit de vote</th></tr></thead>
  <tbody>
    <?php foreach ($holdings as $h): ?>
    <tr><td><?= e($h['shareholder']['name']) ?></td><td class="text-end"><?= shares($h['total']) ?></td><td class="text-end"><?= pct($h['percentage']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
