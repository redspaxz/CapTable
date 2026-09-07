<?php use function App\{e, url, money, shares, pct}; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Répartition du capital</h1>
    <div class="text-muted"><?= e($company['name'] ?? '') ?> — RCCM <?= e($company['rccm'] ?? '—') ?></div>
  </div>
  <div>
    <button class="btn btn-outline-dark" onclick="window.print()" data-bs-toggle="tooltip" data-bs-title="Imprimer la répartition">🖨 Imprimer</button>
    <a class="btn btn-outline-primary" href="<?= url('/captable/history') ?>"><i class="bi bi-clock-history me-1"></i>Historique</a>
    <a class="btn btn-outline-primary" href="<?= url('/waterfall') ?>"><i class="bi bi-water me-1"></i>Waterfall</a>
    <a class="btn btn-outline-success" href="<?= url('/captable/export.csv') ?>" data-bs-toggle="tooltip" data-bs-title="Télécharger en CSV (séparateur ;)">⬇ Export CSV</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <table class="table table-hover bg-white shadow-sm">
      <thead class="table-dark"><tr><th>Actionnaire</th><th>Catégorie</th><th class="text-end">Titres</th><th class="text-end">Valeur nominale</th><th class="text-end">%</th></tr></thead>
      <tbody>
        <?php foreach ($holdings as $h): $i = 0; foreach ($h['rows'] as $row): $i++; ?>
        <tr>
          <td><?= $i === 1 ? '<strong>' . e($h['shareholder']['name']) . '</strong>' : '' ?></td>
          <td><span class="badge bg-secondary"><?= e($row['class']['code']) ?></span> <?= e($row['class']['name']) ?></td>
          <td class="text-end"><?= shares($row['quantity']) ?></td>
          <td class="text-end"><?= money($row['value']) ?></td>
          <td class="text-end fw-semibold"><?= pct($totalShares > 0 ? $row['quantity'] / $totalShares * 100 : 0) ?></td>
        </tr>
        <?php endforeach; endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr><td colspan="2">Total</td><td class="text-end"><?= shares($totalShares) ?></td><td class="text-end"><?= money($totalCapital) ?></td><td class="text-end">100 %</td></tr>
        <?php if (!empty($company['secondary_currency']) && (float) $company['fx_rate'] > 0): ?>
        <tr class="small"><td colspan="4" class="text-end text-muted">≈ <?= number_format($totalCapital / (float) $company['fx_rate'], 0, ',', ' ') ?> <?= e($company['secondary_currency']) ?></td><td></td></tr>
        <?php endif; ?>
      </tfoot>
    </table>

    <h2 class="h6 mt-4">Par catégorie d'actions</h2>
    <table class="table table-sm bg-white shadow-sm">
      <thead><tr><th>Catégorie</th><th class="text-end">Titres</th><th class="text-end">Valeur nominale totale</th></tr></thead>
      <tbody>
        <?php foreach ($byClass as $row): ?>
        <tr><td><?= e($row['class']['code']) ?> — <?= e($row['class']['name']) ?></td>
            <td class="text-end"><?= shares($row['quantity']) ?></td>
            <td class="text-end"><?= money($row['value']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="col-lg-4">
    <div class="card bg-white shadow-sm"><div class="card-body">
      <h2 class="h6 fw-bold">Répartition graphique</h2>
      <canvas id="capChart" height="260"></canvas>
    </div></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('capChart');
  if (!el || typeof Chart === 'undefined') return;
  var colors = ['#0d6efd', '#6610f2', '#d63384', '#fd7e14', '#198754', '#20c997', '#0dcaf0', '#adb5bd'];
  new Chart(el, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_map(fn($h) => $h['shareholder']['name'], $holdings), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
      datasets: [{ data: <?= json_encode(array_map(fn($h) => $h['total'], $holdings), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, backgroundColor: colors }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
});
</script>
