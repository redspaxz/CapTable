<?php use function App\{e, url, shares, money, pct}; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0">Historique du capital</h1>
    <div class="text-muted small">Reconstitution au <?= e(date('d/m/Y', strtotime($asOf))) ?> à partir du registre des mouvements (art. 716)</div>
  </div>
  <a class="btn btn-outline-primary" href="<?= url('/captable') ?>"><i class="bi bi-pie-chart me-1"></i>Cap table actuel</a>
</div>

<form class="row g-2 align-items-end mb-3" method="get" action="<?= url('/captable/history') ?>">
  <div class="col-auto">
    <label class="form-label small mb-0" for="as_of">Date d'arrêté</label>
    <input type="date" id="as_of" name="as_of" class="form-control" value="<?= e($asOf) ?>" max="<?= date('Y-m-d') ?>">
  </div>
  <div class="col-auto"><button class="btn btn-primary">Reconstituer</button></div>
  <div class="col-auto text-muted small align-self-center">
    <?= shares($totalShares) ?> titres · <?= money($totalCapital) ?>
    <?php if ($totalShares !== $currentShares): ?>
      (aujourd'hui : <?= shares($currentShares) ?>)
    <?php endif; ?>
  </div>
</form>

<div class="row g-3">
  <div class="col-lg-7">
    <table class="table table-hover bg-white shadow-sm">
      <thead class="table-dark"><tr><th>Actionnaire</th><th>Catégorie</th><th class="text-end">Titres</th><th class="text-end">Valeur nominale</th><th class="text-end">%</th></tr></thead>
      <tbody>
        <?php foreach ($holdings as $h): $i = 0; foreach ($h['rows'] as $row): $i++; ?>
        <tr>
          <td><?= $i === 1 ? '<strong>' . e($h['shareholder']['name']) . '</strong>' : '' ?></td>
          <td><span class="badge bg-secondary"><?= e($row['class']['code']) ?></span></td>
          <td class="text-end"><?= shares($row['quantity']) ?></td>
          <td class="text-end"><?= money($row['value']) ?></td>
          <td class="text-end"><?= pct($totalShares > 0 ? $row['quantity'] / $totalShares * 100 : 0) ?></td>
        </tr>
        <?php endforeach; endforeach; ?>
        <?php if ($holdings === []): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun titre à cette date.</td></tr><?php endif; ?>
      </tbody>
      <tfoot class="table-light fw-bold"><tr><td colspan="2">Total</td><td class="text-end"><?= shares($totalShares) ?></td><td class="text-end"><?= money($totalCapital) ?></td><td class="text-end">100 %</td></tr></tfoot>
    </table>
  </div>
  <div class="col-lg-5">
    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i>Mouvements jusqu'à cette date</div>
      <ul class="list-group list-group-flush" style="max-height: 30rem; overflow-y: auto;">
        <?php foreach ($movements as $m):
          $labels = ['issuance' => 'Émission', 'transfer_out' => 'Cession', 'transfer_in' => 'Cession (entrée)']; ?>
        <li class="list-group-item d-flex justify-content-between align-items-center small">
          <div><span class="badge text-bg-<?= $m['movement_type'] === 'issuance' ? 'success' : 'warning' ?> me-1"><?= e($labels[$m['movement_type']] ?? $m['movement_type']) ?></span>
            <?= e($m['shareholder_name'] ?? '—') ?>
            <span class="text-muted">· <?= e($m['class_code']) ?></span></div>
          <span class="fw-bold"><?= shares((int) $m['quantity']) ?></span>
        </li>
        <?php endforeach; ?>
        <?php if ($movements === []): ?><li class="list-group-item text-muted small">Aucun mouvement.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
