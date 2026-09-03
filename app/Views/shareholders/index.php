<?php use function App\{e, url, shares}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Actionnaires</h1>
  <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
  <a href="<?= url('/shareholders/new') ?>" class="btn btn-primary">+ Nouvel actionnaire</a>
  <?php endif; ?>
</div>

<form class="row g-2 mb-3" method="get" action="<?= url('/shareholders') ?>">
  <div class="col-md-4"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom ou CNI/RC…"></div>
  <div class="col-auto"><button class="btn btn-outline-secondary">Rechercher</button></div>
</form>

<table class="table table-hover bg-white shadow-sm">
  <thead class="table-dark">
    <tr><th>Nom</th><th>Type</th><th>CNI / RC</th><th>Contact</th><th class="text-end">Titres</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($shareholders as $s): ?>
    <tr>
      <td class="fw-semibold"><?= e($s['name']) ?></td>
      <td><span class="badge bg-<?= $s['type'] === 'corporate' ? 'dark' : 'secondary' ?>"><?= $s['type'] === 'corporate' ? 'Personne morale' : 'Personne physique' ?></span></td>
      <td><?= e($s['id_type']) ?> <?= e($s['id_number']) ?></td>
      <td><?= e($s['email']) ?><br><small class="text-muted"><?= e($s['phone']) ?></small></td>
      <td class="text-end"><?= shares((int) $s['share_count']) ?></td>
      <td>
        <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/shareholders/' . $s['id'] . '/edit') ?>">Modifier</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($shareholders === []): ?><tr><td colspan="6" class="text-muted text-center py-4">Aucun actionnaire enregistré.</td></tr><?php endif; ?>
  </tbody>
</table>
