<?php use function App\{e, url, shares, money}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Options &amp; vesting</h1>
    <div class="text-muted small">Plan d'intéressement — vesting mensuel avec cliff, exercice automatisé</div>
  </div>
  <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
  <a href="<?= url('/options/new') ?>" class="btn btn-primary"><i class="bi bi-award me-1"></i>Nouvelle attribution</a>
  <?php endif; ?>
</div>

<table class="table table-hover bg-white shadow-sm" data-enhance="table">
  <thead class="table-dark">
    <tr><th>Bénéficiaire</th><th>Catégorie</th><th class="text-end">Attribuées</th><th class="text-end">Acquises</th>
        <th class="text-end">Exerçables</th><th class="text-end">Exercées</th><th class="text-end">Strike</th><th>Échéance</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($grants as $g): ?>
    <tr>
      <td class="fw-semibold"><?= e($g['beneficiary']) ?></td>
      <td><span class="badge bg-secondary"><?= e($g['class_code']) ?></span></td>
      <td class="text-end"><?= shares((int) $g['quantity']) ?></td>
      <td class="text-end text-success fw-semibold"><?= shares((int) $g['vested']) ?></td>
      <td class="text-end"><?= shares((int) $g['exercisable']) ?></td>
      <td class="text-end"><?= shares((int) $g['exercised_qty']) ?></td>
      <td class="text-end"><?= money((int) $g['strike_price']) ?></td>
      <td><?= e($g['granted_at']) ?> <span class="text-muted small">+<?= (int) $g['vest_months'] ?> m</span></td>
      <td><a class="btn btn-sm btn-outline-dark" href="<?= url('/options/' . $g['id']) ?>">Échéancier &amp; exercice</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($grants === []): ?><tr><td colspan="9" class="text-center text-muted py-4">Aucune attribution d'options.</td></tr><?php endif; ?>
  </tbody>
</table>
<p class="text-muted small">Valeur de référence utilisée : <?= money($referencePrice) ?> / action (paramétrable via <code>fmv_per_share</code>).</p>
