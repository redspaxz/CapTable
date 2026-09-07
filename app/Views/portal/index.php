<?php use function App\{e, url, shares, money, pct}; use App\Core\Auth;
$roleLabels = ['founder' => 'Founder', 'investor' => 'Investor', 'board' => 'Board member', 'employee' => 'Employee']; ?>
<header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 mb-1 fw-bold">My space <span class="fs-6 text-muted fw-normal"><?= e(Auth::user()['name'] ?? '') ?></span></h1>
    <?php if ($stakeholderRole !== null): ?>
      <span class="badge text-bg-primary"><i class="bi bi-person-badge me-1"></i><?= e($roleLabels[$stakeholderRole] ?? $stakeholderRole) ?></span>
    <?php endif; ?>
  </div>
  <div class="text-end">
    <div class="text-muted small"><?= e($company['name'] ?? '') ?></div>
    <div class="fw-bold"><?= shares($totalShares) ?> shares · <?= money($totalCapital) ?></div>
  </div>
</header>

<?php if ($myHolding === null && $myGrants === []): ?>
<div class="alert alert-info">
  Your account is not yet linked to a shareholder or option beneficiary profile.
  Contact the administrator to associate your account with your file.
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Shares held', $myHolding !== null ? shares($myHolding['total']) : '—', 'bi-pie-chart-fill', 'primary'],
      ['Ownership share', $myHolding !== null ? pct($myHolding['percentage']) : '—', 'bi-percent', 'info'],
      ['Vested options', shares($vestedTotal), 'bi-graph-up-arrow', 'success'],
      ['Vested value (est.)', money($vestedValue), 'bi-cash-coin', 'warning'],
  ];
  foreach ($cards as [$label, $value, $icon, $color]): ?>
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
      <span class="stat-icon text-bg-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
      <div><div class="text-muted small"><?= e($label) ?></div>
        <div class="fs-5 fw-bold text-<?= $color ?>"><?= e($value) ?></div></div>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <?php if ($myHolding !== null): ?>
  <section class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-pie-chart me-2"></i>My shares</span>
        <span class="text-muted small"><?= (int) $myHolding['certificates'] ?> certificate(s)</span>
      </div>
      <table class="table table-sm mb-0">
        <thead><tr><th>Class</th><th class="text-end">Shares</th><th class="text-end">Par value</th></tr></thead>
        <tbody>
          <?php foreach ($myHolding['rows'] as $row): ?>
          <tr><td><span class="badge bg-secondary"><?= e($row['class']['code']) ?></span> <?= e($row['class']['name']) ?></td>
              <td class="text-end"><?= shares($row['quantity']) ?></td>
              <td class="text-end"><?= money($row['value']) ?></td></tr>
          <?php endforeach; ?>
          <?php if ($myHolding['rows'] === []): ?><tr><td colspan="3" class="text-muted">No shares to date.</td></tr><?php endif; ?>
        </tbody>
        <tfoot class="table-light fw-bold"><tr><td>Total</td><td class="text-end"><?= shares($myHolding['total']) ?></td><td class="text-end"><?= pct($myHolding['percentage']) ?></td></tr></tfoot>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($myGrants !== []): ?>
  <section class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-award me-2"></i>My options</div>
      <table class="table table-sm mb-0">
        <thead><tr><th>Grant</th><th class="text-end">Granted</th><th class="text-end">Vested</th><th class="text-end">Exercisable</th><th class="text-end">Exercised</th></tr></thead>
        <tbody>
          <?php foreach ($myGrants as $g): ?>
          <tr>
            <td><span class="badge bg-secondary"><?= e($g['class_code']) ?></span>
              <small class="text-muted d-block"><?= e($g['granted_at']) ?></small></td>
            <td class="text-end"><?= shares((int) $g['quantity']) ?></td>
            <td class="text-end text-success fw-semibold"><?= shares((int) $g['vested']) ?></td>
            <td class="text-end"><?= shares((int) $g['exercisable']) ?></td>
            <td class="text-end"><?= shares((int) $g['exercised_qty']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light fw-bold"><tr><td>Total</td>
          <td class="text-end"><?= shares(array_sum(array_map(fn($g) => (int) $g['quantity'], $myGrants))) ?></td>
          <td class="text-end"><?= shares((int) $vestedTotal) ?></td><td></td>
          <td class="text-end"><?= shares((int) $exercisedTotal) ?></td></tr></tfoot>
      </table>
      <div class="card-footer text-muted small">
        Reference price: <?= money($referencePrice) ?> / share · strike per grant.
      </div>
    </div>
  </section>
  <?php endif; ?>
</div>
