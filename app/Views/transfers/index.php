<?php use function App\{e, url, shares}; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Cessions de droits sociaux</h1>
  <a href="<?= url('/transfers/new') ?>" class="btn btn-primary">+ Nouvelle cession</a>
</div>
<table class="table table-hover bg-white shadow-sm">
  <thead class="table-dark"><tr><th>Référence acte</th><th>Date</th><th>Cédant</th><th>Cessionnaire</th><th>Catégorie</th><th class="text-end">Titres</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($transfers as $t): ?>
    <tr>
      <td class="fw-semibold"><?= e($t['deed_reference']) ?></td>
      <td><?= e($t['transfer_date']) ?></td>
      <td><?= e($t['seller_name']) ?></td>
      <td><?= e($t['buyer_name']) ?></td>
      <td><span class="badge bg-secondary"><?= e($t['class_code']) ?></span></td>
      <td class="text-end"><?= shares((int) $t['quantity']) ?></td>
      <td><a class="btn btn-sm btn-outline-dark" href="<?= url('/documents/deeds/' . $t['id']) ?>">Acte</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($transfers === []): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune cession.</td></tr><?php endif; ?>
  </tbody>
</table>
