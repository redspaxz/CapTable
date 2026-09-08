<?php use function App\{e, url, money, shares, pct}; ?>
<header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 mb-1 fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></h1>
    <p class="text-muted small mb-0">
      <i class="bi bi-geo-alt me-1"></i><?= e($company['head_office'] ?? '—') ?>
      · RCCM <?= e($company['rccm'] ?? '—') ?> · NIU <?= e($company['niu'] ?? '—') ?>
    </p>
  </div>
  <nav class="btn-group btn-group-sm" aria-label="Quick actions">
    <a class="btn btn-outline-primary" href="<?= url('/issuances/new') ?>"><i class="bi bi-plus-square me-1"></i>Issue</a>
    <a class="btn btn-outline-primary" href="<?= url('/transfers/new') ?>"><i class="bi bi-arrow-left-right me-1"></i>Transfer</a>
    <a class="btn btn-outline-dark" href="<?= url('/captable/export.csv') ?>"><i class="bi bi-download me-1"></i>CSV</a>
  </nav>
</header>

<section class="row g-3 mb-3" aria-label="Key metrics">
  <?php
  $cards = [
      ['Share capital', $totalCapital, ' XAF', 'bi-bank2', 'primary'],
      ['Outstanding shares', $totalShares, '', 'bi-pie-chart-fill', 'success'],
      ['Shareholders', $shareholderCount, '', 'bi-people-fill', 'info'],
      ['Register movements', $movementCount, '', 'bi-journal-bookmark-fill', 'warning'],
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

<div class="row g-3">
  <section class="col-lg-5" aria-label="Recent movements">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Recent share movements</span>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/register') ?>">Full register</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentMovements as $m):
          $labels = ['issuance' => 'Issuance', 'transfer_out' => 'Transfer (out)', 'transfer_in' => 'Transfer (in)'];
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
        <li class="list-group-item text-muted small py-3">No register movements yet — start with a <a href="<?= url('/issuances/new') ?>">share issuance</a>.</li>
        <?php endif; ?>
      </ul>
    </div>
  </section>
  <section class="col-lg-7" aria-label="Capital breakdown">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-pie-chart me-2"></i>Capital breakdown</span>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/captable') ?>">Detailed cap table</a>
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
          <span class="small text-muted">Other shareholders</span>
          <span class="small text-muted"><?= pct($othersPct) ?> · <?= shares($othersQty) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap justify-content-between gap-2 border-top pt-2 mt-2 small">
          <span><strong><?= shares((int) $totalShares) ?></strong> <span class="text-muted">shares</span></span>
          <span><strong><?= money((int) $totalCapital) ?> XAF</strong><?php if (!empty($company['secondary_currency']) && (float) ($company['fx_rate'] ?? 0) > 0): ?> <span class="text-muted">≈ <?= number_format($totalCapital / (float) $company['fx_rate'], 0, ',', ' ') ?> <?= e($company['secondary_currency']) ?></span><?php endif; ?></span>
        </div>
      </div>
      <?php else: ?>
      <div class="card-body d-flex flex-column justify-content-center align-items-start text-muted py-3">
        <p class="mb-2 small">No shares outstanding yet. Create your share classes, then issue the founding shares.</p>
        <div class="btn-group btn-group-sm">
          <a class="btn btn-outline-primary" href="<?= url('/classes/new') ?>"><i class="bi bi-plus-square me-1"></i>Class</a>
          <a class="btn btn-outline-primary" href="<?= url('/shareholders/new') ?>"><i class="bi bi-person-plus me-1"></i>Shareholder</a>
          <a class="btn btn-primary" href="<?= url('/issuances/new') ?>"><i class="bi bi-bank2 me-1"></i>Issue</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>
