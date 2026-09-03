<?php use function App\{e, url, money, shares, pct}; ?>
<h1 class="h4 mb-1"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></h1>
<p class="text-muted">RCCM <?= e($company['rccm'] ?? '—') ?> · NIU <?= e($company['niu'] ?? '—') ?> · Siège : <?= e($company['head_office'] ?? '—') ?></p>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Capital social', money($totalCapital), 'primary'],
      ['Titres en circulation', shares($totalShares), 'success'],
      ['Actionnaires', (string) $shareholderCount, 'info'],
      ['Mouvements au registre', (string) $movementCount, 'warning'],
  ];
  foreach ($cards as [$label, $value, $color]): ?>
  <div class="col-md-3">
    <div class="card border-<?= $color ?> h-100">
      <div class="card-body">
        <div class="text-muted small"><?= e($label) ?></div>
        <div class="fs-4 fw-bold text-<?= $color ?>"><?= e($value) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header fw-bold">Derniers mouvements de titres</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentMovements as $m):
          $labels = ['issuance' => 'Émission', 'transfer_out' => 'Cession (sortie)', 'transfer_in' => 'Cession (entrée)'];
          $badge = $m['movement_type'] === 'issuance' ? 'success' : 'warning'; ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <span class="badge bg-<?= $badge ?> me-2"><?= e($labels[$m['movement_type']] ?? $m['movement_type']) ?></span>
            <?= e($m['shareholder_name'] ?? '—') ?>
            <span class="text-muted small">— <?= e($m['class_code']) ?></span>
          </div>
          <div class="text-end">
            <div class="fw-bold"><?= shares((int) $m['quantity']) ?></div>
            <div class="text-muted small"><?= e($m['movement_date']) ?></div>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if ($recentMovements === []): ?><li class="list-group-item text-muted">Aucun mouvement.</li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        Répartition du capital
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/captable') ?>">Voir le cap table</a>
      </div>
      <div class="card-body text-center text-muted">
        Consultez la page <a href="<?= url('/captable') ?>">Capital</a> pour le tableau complet,
        le graphique et l'export CSV.
      </div>
    </div>
  </div>
</div>
