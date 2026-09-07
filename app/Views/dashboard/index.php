<?php use function App\{e, url, money, shares, pct}; ?>
<header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 mb-1 fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></h1>
    <p class="text-muted small mb-0">
      <i class="bi bi-geo-alt me-1"></i><?= e($company['head_office'] ?? '—') ?>
      · RCCM <?= e($company['rccm'] ?? '—') ?> · NIU <?= e($company['niu'] ?? '—') ?>
    </p>
  </div>
  <nav class="btn-group" aria-label="Quick actions">
    <a class="btn btn-outline-primary" href="<?= url('/issuances/new') ?>"><i class="bi bi-plus-square me-1"></i>Issue</a>
    <a class="btn btn-outline-primary" href="<?= url('/transfers/new') ?>"><i class="bi bi-arrow-left-right me-1"></i>Transfer</a>
    <a class="btn btn-outline-dark" href="<?= url('/captable/export.csv') ?>"><i class="bi bi-download me-1"></i>CSV</a>
  </nav>
</header>

<section class="row g-3 mb-4" aria-label="Key metrics">
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
        <?php if ($recentMovements === []): ?><li class="list-group-item text-muted">No movements yet.</li><?php endif; ?>
      </ul>
    </div>
  </section>
  <section class="col-lg-7" aria-label="Capital breakdown">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-pie-chart me-2"></i>Capital breakdown</span>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/captable') ?>">Detailed cap table</a>
      </div>
      <div class="card-body d-flex flex-column justify-content-center text-muted">
        <p class="mb-0">See the <a href="<?= url('/captable') ?>">Capital</a> page for the full table,
        the ownership chart and the CSV export.</p>
      </div>
    </div>
  </section>
</div>
