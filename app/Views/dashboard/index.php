<?php use function App\{e, url, money, shares, pct, __}; ?>
<header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 mb-1 fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></h1>
    <p class="text-muted small mb-0">
      <i class="bi bi-geo-alt me-1"></i><?= e($company['head_office'] ?? '—') ?>
      · RCCM <?= e($company['rccm'] ?? '—') ?> · NIU <?= e($company['niu'] ?? '—') ?>
    </p>
  </div>
  <nav class="btn-group btn-group-sm" aria-label="<?= __('Quick actions') ?>">
    <a class="btn btn-outline-primary" href="<?= url('/issuances/new') ?>"><i class="bi bi-plus-square me-1"></i><?= __('Issue') ?></a>
    <a class="btn btn-outline-primary" href="<?= url('/transfers/new') ?>"><i class="bi bi-arrow-left-right me-1"></i><?= __('Transfer') ?></a>
    <a class="btn btn-outline-dark" href="<?= url('/captable/export.csv') ?>"><i class="bi bi-download me-1"></i>CSV</a>
  </nav>
</header>

<section class="row g-3 mb-3" aria-label="<?= __('Key metrics') ?>">
  <?php
  $cards = [
      [__('Share capital'), $totalCapital, ' XAF', 'bi-bank2', 'primary'],
      [__('Outstanding shares'), $totalShares, '', 'bi-pie-chart-fill', 'success'],
      [__('Shareholders'), $shareholderCount, '', 'bi-people-fill', 'info'],
      [__('Register movements'), $movementCount, '', 'bi-journal-bookmark-fill', 'warning'],
  ];
  foreach ($cards as [$label, $raw, $suffix, $icon, $color]): ?>
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="stat-icon text-bg-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
        <div>
          <div class="text-muted small"><?= e($label) ?></div>
          <div class="fs-5 fw-bold text-<?= $color ?>" data-countup="<?= (int) $raw ?>" data-suffix="<?= e($suffix) ?>"><?= e(shares((int) $raw) . $suffix) ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</section>

<section class="row g-3 mb-3" aria-label="<?= __('Charts') ?>">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-graph-up-arrow me-2"></i><?= __('Share capital over time') ?></div>
      <div class="card-body">
        <?php if ($capitalTimeline['labels'] !== []): ?>
        <canvas id="capitalChart" height="110"></canvas>
        <?php else: ?>
        <p class="text-muted small mb-0 py-4 text-center"><?= __('No share movements yet — the curve will appear after the first issuance.') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2"></i><?= __('Ownership split') ?></div>
      <div class="card-body">
        <?php if ($topHolders !== []): ?>
        <canvas id="donutChart" height="168"></canvas>
        <?php else: ?>
        <p class="text-muted small mb-0 py-4 text-center"><?= __('No shares outstanding yet. Create your share classes, then issue the founding shares.') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="row g-3">
  <section class="col-lg-5" aria-label="<?= __('Recent movements') ?>">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i><?= __('Recent share movements') ?></span>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/register') ?>"><?= __('Full register') ?></a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentMovements as $m):
          $labels = ['issuance' => __('Issuance'), 'transfer_out' => __('Transfer (out)'), 'transfer_in' => __('Transfer (in)')];
          $badge = $m['movement_type'] === 'issuance' ? 'success' : 'warning'; ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <span class="badge text-bg-<?= $badge ?> me-2"><?= e($labels[$m['movement_type']] ?? $m['movement_type']) ?></span>
            <?= e($m['shareholder_name'] ?? '—') ?>
            <span class="text-muted small">— <?= e($m['class_code']) ?></span>
          </div>
          <div class="text-end">
            <div class="fw-bold"><?= shares((int) $m['quantity']) ?></div>
            <div class="text-muted small"><?= e($m['movement_date']) ?></div>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if ($recentMovements === []): ?>
        <li class="list-group-item text-muted small py-3"><?= __('No register movements yet — start with a share issuance.') ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </section>
  <section class="col-lg-7" aria-label="<?= __('Capital breakdown') ?>">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check me-2"></i><?= __('Capital breakdown') ?></span>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/captable') ?>"><?= __('Detailed cap table') ?></a>
      </div>
      <?php if ($topHolders !== []): ?>
      <div class="card-body pt-2">
        <?php $othersPct = max(0.0, 100.0 - array_sum(array_column($topHolders, 'percentage')));
              $othersQty = max(0, (int) $totalShares - array_sum(array_column($topHolders, 'total')));
              $barColors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary']; ?>
        <?php foreach ($topHolders as $i => $h): ?>
        <div class="d-flex justify-content-between align-items-baseline gap-3 mb-1">
          <span class="small text-truncate"><span class="badge dot bg-<?= $barColors[$i % count($barColors)] ?> me-2"></span><?= e($h['shareholder']['name']) ?></span>
          <span class="small text-nowrap"><strong><?= pct($h['percentage']) ?></strong> <span class="text-muted">· <?= shares((int) $h['total']) ?></span></span>
        </div>
        <div class="progress mb-2" role="progressbar" aria-label="<?= e($h['shareholder']['name']) ?>" aria-valuenow="<?= round($h['percentage'], 1) ?>" aria-valuemin="0" aria-valuemax="100" style="height:6px">
          <div class="progress-bar bg-<?= $barColors[$i % count($barColors)] ?>" style="width:<?= round($h['percentage'], 2) ?>%"></div>
        </div>
        <?php endforeach; ?>
        <?php if ($othersPct > 0.01): ?>
        <div class="d-flex justify-content-between align-items-baseline gap-3 mb-1">
          <span class="small text-muted"><?= __('Other shareholders') ?></span>
          <span class="small text-muted"><?= pct($othersPct) ?> · <?= shares($othersQty) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap justify-content-between gap-2 border-top pt-2 mt-2 small">
          <span><strong><?= shares((int) $totalShares) ?></strong> <span class="text-muted"><?= __('shares') ?></span></span>
          <span><strong><?= money((int) $totalCapital) ?> XAF</strong><?php if (!empty($company['secondary_currency']) && (float) ($company['fx_rate'] ?? 0) > 0): ?> <span class="text-muted">≈ <?= number_format($totalCapital / (float) $company['fx_rate'], 0, ',', ' ') ?> <?= e($company['secondary_currency']) ?></span><?php endif; ?></span>
        </div>
      </div>
      <?php else: ?>
      <div class="card-body d-flex flex-column justify-content-center align-items-start text-muted py-3">
        <p class="mb-2 small"><?= __('No shares outstanding yet. Create your share classes, then issue the founding shares.') ?></p>
        <div class="btn-group btn-group-sm">
          <a class="btn btn-outline-primary" href="<?= url('/classes') ?>"><i class="bi bi-plus-square me-1"></i><?= __('Share classes') ?></a>
          <a class="btn btn-outline-primary" href="<?= url('/shareholders/new') ?>"><i class="bi bi-person-plus me-1"></i><?= __('Shareholder') ?></a>
          <a class="btn btn-primary" href="<?= url('/issuances/new') ?>"><i class="bi bi-bank2 me-1"></i><?= __('Issue') ?></a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var timeline = <?= json_encode($capitalTimeline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var lineEl = document.getElementById('capitalChart');
  if (lineEl && typeof Chart !== 'undefined' && timeline.labels.length) {
    new Chart(lineEl, {
      type: 'line',
      data: {
        labels: timeline.labels,
        datasets: [{
          label: 'XAF',
          data: timeline.values,
          fill: true,
          tension: 0.3,
          borderColor: '#17588c',
          backgroundColor: 'rgba(23, 88, 140, 0.12)',
          pointRadius: 3,
          pointBackgroundColor: '#17588c'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { ticks: { callback: function (v) { return v >= 1000000 ? (v / 1000000) + ' M' : v; } } },
          x: { grid: { display: false } }
        }
      }
    });
  }
  var donutEl = document.getElementById('donutChart');
  var holders = <?= json_encode(array_map(fn($h) => ['name' => $h['shareholder']['name'], 'total' => (int) $h['total']], array_slice($topHolders, 0, 5)), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var totalTop = holders.reduce(function (s, h) { return s + h.total; }, 0);
  var totalShares = <?= (int) $totalShares ?>;
  if (donutEl && typeof Chart !== 'undefined' && holders.length) {
    var labels = holders.map(function (h) { return h.name; });
    var data = holders.map(function (h) { return h.total; });
    if (totalShares > totalTop) { labels.push(<?= json_encode(__('Other shareholders')) ?>); data.push(totalShares - totalTop); }
    var colors = ['#17588c', '#1d8a5f', '#0dcaf0', '#fd7e14', '#6610f2', '#adb5bd'];
    new Chart(donutEl, {
      type: 'doughnut',
      data: { labels: labels, datasets: [{ data: data, backgroundColor: colors.slice(0, labels.length) }] },
      options: { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
    });
  }
});
</script>
