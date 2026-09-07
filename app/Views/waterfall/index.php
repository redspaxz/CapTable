<?php use function App\{e, url, shares, money, pct}; ?>
<h1 class="h4 mb-1">Liquidation waterfall</h1>
<p class="text-muted small">Exit-proceeds allocation simulator: per-class liquidation preference (by ascending priority), then the remainder pro-rata among participating classes.</p>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card bg-white shadow-sm"><div class="card-body">
      <form method="get" action="<?= url('/waterfall') ?>">
        <label class="form-label" for="exit_value">Exit value (XAF)</label>
        <div class="input-group mb-3">
          <input type="number" min="0" step="1000000" id="exit_value" name="exit_value" class="form-control"
                 value="<?= (int) $exitValue ?: 100000000 ?>" placeholder="150000000">
          <button class="btn btn-primary">Calculate</button>
        </div>
      </form>
      <dl class="small mb-0">
        <dt>Outstanding shares</dt><dd><?= shares($totalShares) ?></dd>
        <dt>Share capital</dt><dd><?= money($totalCapital) ?></dd>
      </dl>
    </div></div>

    <?php if ($result !== null): ?>
    <div class="card bg-white shadow-sm mt-3"><div class="card-header">Preference steps</div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($result['steps'] as $step): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span><span class="badge bg-secondary"><?= e($step['class']['code']) ?></span>
            ×<?= e(rtrim(rtrim((string) $step['class']['liquidation_multiplier'], '0'), '.')) ?>
            <?= (int) $step['class']['participing'] === 1 ? '' : '(non-participating)' ?></span>
          <span><?= money((int) $step['paid']) ?> / <?= money((int) $step['need']) ?></span>
        </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between fw-semibold">
          <span>Participating remainder</span><span><?= money((int) $result['remainder']) ?></span>
        </li>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
    <?php if ($result === null): ?>
    <div class="card bg-white shadow-sm"><div class="card-body text-muted">
      Enter an exit value and click <strong>Calculate</strong> to see the allocation.
    </div></div>
    <?php else: ?>
    <table class="table table-hover bg-white shadow-sm">
      <thead class="table-dark"><tr><th>Beneficiary</th><th class="text-end">Preference</th><th class="text-end">Participating remainder</th>
          <th class="text-end">Total received</th><th class="text-end">% of proceeds</th></tr></thead>
      <tbody>
        <?php foreach ($result['holders'] as $h): ?>
        <tr>
          <td class="fw-semibold"><?= e($h['shareholder']['name']) ?></td>
          <td class="text-end"><?= money((int) $h['preference']) ?></td>
          <td class="text-end"><?= money((int) $h['residual']) ?></td>
          <td class="text-end fw-bold"><?= money((int) $h['total']) ?></td>
          <td class="text-end"><?= pct($exitValue > 0 ? $h['total'] / $exitValue * 100 : 0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr><td>Total distributed</td><td></td><td></td>
            <td class="text-end"><?= money((int) $result['distributed']) ?></td>
            <td class="text-end"><?= pct($exitValue > 0 ? $result['distributed'] / $exitValue * 100 : 0) ?></td></tr>
      </tfoot>
    </table>
    <p class="text-muted small">Model: preference = par value × multiplier, paid in ascending priority order;
      the balance is split pro-rata across participating classes only (whole XAF amounts, rounded down).</p>
    <?php endif; ?>
  </div>
</div>
