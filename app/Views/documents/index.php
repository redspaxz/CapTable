<?php use function App\{e, url, shares}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Documents &amp; rapports</h1>
  <div>
    <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
    <a class="btn btn-primary" href="<?= url('/documents/certificates/new') ?>">+ Certificat d'actions</a>
    <a class="btn btn-outline-primary" href="<?= url('/documents/minutes/new') ?>">+ PV d'assemblée</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card bg-white shadow-sm">
      <div class="card-header fw-bold">Certificats d'actions</div>
      <table class="table table-hover mb-0" data-enhance="table">
        <thead><tr><th>N° certificat</th><th>Actionnaire</th><th>Catégorie</th><th class="text-end">Titres</th><th>Émis le</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($certificates as $c): ?>
          <tr>
            <td class="fw-semibold"><?= e($c['certificate_number']) ?></td>
            <td><?= e($c['shareholder_name']) ?></td>
            <td><?= e($c['class_code']) ?></td>
            <td class="text-end"><?= shares((int) $c['quantity']) ?></td>
            <td><?= e($c['issue_date']) ?></td>
            <td><a class="btn btn-sm btn-outline-dark" href="<?= url('/documents/certificates/' . $c['id']) ?>">Voir / imprimer</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if ($certificates === []): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun certificat émis.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card bg-white shadow-sm">
      <div class="card-header fw-bold">Rapports OHADA</div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          Registre des mouvements de titres (art. 716 AUSCGIE)
          <a class="btn btn-sm btn-outline-dark" href="<?= url('/register') ?>">Ouvrir</a>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          Répartition du capital (cap table)
          <a class="btn btn-sm btn-outline-dark" href="<?= url('/captable') ?>">Ouvrir</a>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          PV d'assemblée générale
          <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
          <a class="btn btn-sm btn-outline-dark" href="<?= url('/documents/minutes/new') ?>">Rédiger</a>
          <?php else: ?><span class="text-muted small">Lecture seule</span><?php endif; ?>
        </li>
      </ul>
    </div>
  </div>
</div>
