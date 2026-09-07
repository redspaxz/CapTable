<?php use function App\{e, url, shares, money}; use App\Core\Auth; $g = $grant; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Attribution #<?= (int) $g['id'] ?> — <?= e($g['beneficiary']) ?></h1>
    <div class="text-muted small"><?= shares((int) $g['quantity']) ?> options <?= e($g['class_code']) ?>
      · strike <?= money((int) $g['strike_price']) ?> · accordées le <?= e($g['granted_at']) ?>
      · vesting <?= (int) $g['vest_months'] ?> mois (cliff <?= (int) $g['cliff_months'] ?>)</div>
  </div>
  <a href="<?= url('/options') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Acquises (vested)', shares((int) $vested), 'bi-graph-up-arrow', 'success'],
      ['Exerçables aujourd\'hui', shares((int) $exercisable), 'bi-lightning-charge-fill', 'warning'],
      ['Exercées à ce jour', shares((int) $g['exercised_qty']), 'bi-check2-circle', 'info'],
      ['Valeur acquise (estimée)', money((int) $vestedValue), 'bi-cash-coin', 'primary'],
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
  <section class="col-lg-7">
    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-calendar3 me-2"></i>Échéancier de vesting</div>
      <div class="table-responsive" style="max-height: 26rem; overflow-y: auto;">
        <table class="table table-sm table-striped mb-0">
          <thead class="table-dark"><tr><th>Date</th><th>Tranche</th><th class="text-end">Cumul acquis</th><th>Événement</th></tr></thead>
          <tbody>
            <?php $done = false; foreach ($schedule as $row):
              $past = $row['date'] <= date('Y-m-d'); ?>
            <tr class="<?= $past ? '' : 'text-muted' ?>">
              <td><?= e($row['date']) ?></td>
              <td>+<?= shares((int) $row['tranche']) ?></td>
              <td class="text-end"><?= shares((int) $row['cumulative']) ?></td>
              <td>
                <?php if ($row['kind'] === 'cliff'): ?><span class="badge text-bg-danger">Cliff</span>
                <?php elseif ($row['kind'] === 'final'): ?><span class="badge text-bg-dark">Solde final</span>
                <?php else: ?><span class="badge text-bg-light text-dark">Mensuel</span><?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="col-lg-5">
    <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
    <div class="card bg-white shadow-sm mb-3">
      <div class="card-header"><i class="bi bi-lightning-charge me-2"></i>Exercer des options</div>
      <div class="card-body">
        <form method="post" action="<?= url('/options/' . $g['id'] . '/exercise') ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="mb-2">
            <label class="form-label" for="quantity">Quantité à exercer *</label>
            <input type="number" min="1" max="<?= (int) $exercisable ?>" id="quantity" name="quantity" class="form-control" required>
            <div class="form-text"><?= shares((int) $exercisable) ?> option(s) exerçables. L'exercice émet automatiquement les actions et inscrit le mouvement au registre.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="exercise_date">Date d'exercice *</label>
            <input type="date" id="exercise_date" name="exercise_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <button class="btn btn-warning"><i class="bi bi-lightning-charge me-1"></i>Exercer</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i>Exercices réalisés</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($exercises as $ex): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><span class="fw-semibold"><?= e($ex['reference']) ?></span>
            <br><small class="text-muted"><?= e($ex['exercise_date']) ?> · émission liée <?= e($ex['issuance_reference'] ?? $ex['reference']) ?></small></div>
          <span class="fw-bold"><?= shares((int) $ex['quantity']) ?></span>
        </li>
        <?php endforeach; ?>
        <?php if ($exercises === []): ?><li class="list-group-item text-muted">Aucun exercice.</li><?php endif; ?>
      </ul>
    </div>
  </section>
</div>
