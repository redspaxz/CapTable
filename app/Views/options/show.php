<?php use function App\{e, url, shares, money}; use App\Core\Auth; $g = $grant; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Grant #<?= (int) $g['id'] ?> — <?= e($g['beneficiary']) ?></h1>
    <div class="text-muted small"><?= shares((int) $g['quantity']) ?> options <?= e($g['class_code']) ?>
      · <?= __('strike') ?> <?= money((int) $g['strike_price']) ?> · <?= __('granted on') ?> <?= e($g['granted_at']) ?>
      · <?= __('vesting') ?> <?= (int) $g['vest_months'] ?> <?= __('months') ?> (<?= __('cliff') ?> <?= (int) $g['cliff_months'] ?>)</div>
  </div>
  <a href="<?= url('/options') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?= __('Back') ?></a>
</div>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      [__('Vested'), shares((int) $vested), 'bi-graph-up-arrow', 'success'],
      [__('Exercisable today'), shares((int) $exercisable), 'bi-lightning-charge-fill', 'warning'],
      [__('Exercised to date'), shares((int) $g['exercised_qty']), 'bi-check2-circle', 'info'],
      [__('Vested value (est.)'), money((int) $vestedValue), 'bi-cash-coin', 'primary'],
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
      <div class="card-header"><i class="bi bi-calendar3 me-2"></i><?= __('Vesting schedule') ?></div>
      <div class="table-responsive" style="max-height: 26rem; overflow-y: auto;">
        <table class="table table-sm table-striped mb-0">
          <thead class="table-dark"><tr><th><?= __('Date') ?></th><th><?= __('Tranche') ?></th><th class="text-end"><?= __('Cumulative vested') ?></th><th><?= __('Event') ?></th></tr></thead>
          <tbody>
            <?php $done = false; foreach ($schedule as $row):
              $past = $row['date'] <= date('Y-m-d'); ?>
            <tr class="<?= $past ? '' : 'text-muted' ?>">
              <td><?= e($row['date']) ?></td>
              <td>+<?= shares((int) $row['tranche']) ?></td>
              <td class="text-end"><?= shares((int) $row['cumulative']) ?></td>
              <td>
                <?php if ($row['kind'] === 'cliff'): ?><span class="badge text-bg-danger"><?= __('Cliff') ?></span>
                <?php elseif ($row['kind'] === 'final'): ?><span class="badge text-bg-dark"><?= __('Final vest') ?></span>
                <?php else: ?><span class="badge text-bg-light text-dark"><?= __('Monthly') ?></span><?php endif; ?>
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
      <div class="card-header"><i class="bi bi-lightning-charge me-2"></i><?= __('Exercise options') ?></div>
      <div class="card-body">
        <form method="post" action="<?= url('/options/' . $g['id'] . '/exercise') ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="mb-2">
            <label class="form-label" for="quantity"><?= __('Quantity to exercise') ?> *</label>
            <input type="number" min="1" max="<?= (int) $exercisable ?>" id="quantity" name="quantity" class="form-control" required>
            <div class="form-text"><?= shares((int) $exercisable) ?> <?= __('option(s) exercisable. Exercising automatically issues the shares and records the movement in the register.') ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="exercise_date"><?= __('Exercise date') ?> *</label>
            <input type="date" id="exercise_date" name="exercise_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <button class="btn btn-warning"><i class="bi bi-lightning-charge me-1"></i><?= __('Exercise') ?></button>
          <?php
          $taxRate = (float) (\App\company()['option_tax_rate'] ?? 0);
          if ($taxRate > 0):
            $gainPerOption = max(0, (new \App\Modules\Options\OptionService())->referencePrice() - (int) $g['strike_price']);
            $taxEstimate = (int) floor($exercisable * $gainPerOption * $taxRate); ?>
            <p class="form-text small mb-0 mt-2">
              <i class="bi bi-info-circle me-1"></i><?= __('Indicative tax impact (General Tax Code — acquisition benefit treated as remuneration):') ?>
              ≈ <strong><?= money($taxEstimate) ?></strong> for the exercisable quantity (configurable rate <?= e(rtrim(rtrim(number_format($taxRate * 100, 2, ',', ' '), '0'), ',')) ?> %).
              <?= __('Exercising requires a capital increase resolution (EGM).') ?>
            </p>
          <?php endif; ?>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i><?= __('Exercises performed') ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($exercises as $ex): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><span class="fw-semibold"><?= e($ex['reference']) ?></span>
            <br><small class="text-muted"><?= e($ex['exercise_date']) ?> · <?= __('linked issuance') ?> <?= e($ex['issuance_reference'] ?? $ex['reference']) ?></small></div>
          <span class="fw-bold"><?= shares((int) $ex['quantity']) ?></span>
        </li>
        <?php endforeach; ?>
        <?php if ($exercises === []): ?><li class="list-group-item text-muted"><?= __('No exercises yet.') ?></li><?php endif; ?>
      </ul>
    </div>
  </section>
</div>
