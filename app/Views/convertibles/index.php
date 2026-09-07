<?php use function App\{e, url, shares, money, pct}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Convertible instruments</h1>
    <div class="text-muted small">OCA (convertible bonds), BSA (subscription warrants), SAFE — dilution modelling at conversion</div>
  </div>
  <a class="btn btn-outline-primary" href="<?= url('/captable') ?>"><i class="bi bi-pie-chart me-1"></i>Current cap table</a>
</div>

<div class="row g-3">
  <section class="col-lg-7">
    <div class="card bg-white shadow-sm">
      <div class="card-header">Outstanding instruments</div>
      <table class="table table-sm table-hover mb-0" data-enhance="table">
        <thead><tr><th>Type</th><th>Holder</th><th class="text-end">Principal</th><th class="text-end">Discount</th><th class="text-end">Cap</th><th class="text-end">Modelled shares</th></tr></thead>
        <tbody>
          <?php foreach ($instruments as $i): ?>
          <tr>
            <td><span class="badge text-bg-dark"><?= e($i['type']) ?></span></td>
            <td><?= e($i['holder']) ?></td>
            <td class="text-end"><?= money((int) $i['principal_amount']) ?></td>
            <td class="text-end"><?= $i['discount_pct'] > 0 ? e(rtrim(rtrim((string) $i['discount_pct'], '0'), '.')) . ' %' : '—' ?></td>
            <td class="text-end"><?= $i['valuation_cap'] ? money((int) $i['valuation_cap']) : '—' ?></td>
            <td class="text-end fw-semibold"><?= shares((int) $i['modelled_shares']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if ($instruments === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No convertible instruments.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
    <div class="card bg-white shadow-sm mt-3"><div class="card-header">New instrument</div>
      <div class="card-body">
        <form method="post" action="<?= url('/convertibles') ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="row g-2">
            <div class="col-md-2"><label class="form-label">Type</label>
              <select name="type" class="form-select"><option>OCA</option><option>BSA</option><option>SAFE</option></select></div>
            <div class="col-md-4"><label class="form-label">Holder *</label>
              <input name="holder" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Principal (XAF) *</label>
              <input type="number" min="1" name="principal_amount" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Date</label>
              <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-3"><label class="form-label">Discount %</label>
              <input type="number" min="0" max="99" step="0.5" name="discount_pct" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label">Valuation cap (XAF)</label>
              <input type="number" min="0" name="valuation_cap" class="form-control" placeholder="Optional"></div>
          </div>
          <button class="btn btn-primary btn-sm mt-3">Enregistrer</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <section class="col-lg-5">
    <div class="card bg-white shadow-sm h-100">
      <div class="card-header">Pro-forma cap table (full conversion)</div>
      <table class="table table-sm mb-0">
        <thead><tr><th>Holder</th><th class="text-end">Current</th><th class="text-end">Diluted</th><th class="text-end">%</th></tr></thead>
        <tbody>
          <?php foreach ($proforma as $row): ?>
          <tr class="<?= $row['current'] === 0 ? 'table-success' : '' ?>">
            <td><?= e($row['holder']) ?></td>
            <td class="text-end"><?= shares($row['current']) ?></td>
            <td class="text-end fw-semibold"><?= shares($row['modelled']) ?></td>
            <td class="text-end"><?= pct($row['pct']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
          <tr><td>Total</td><td class="text-end"><?= shares($currentShares) ?></td>
              <td class="text-end"><?= shares(array_sum(array_map(fn($r) => $r['modelled'], $proforma))) ?></td><td></td></tr>
        </tfoot>
      </table>
      <div class="card-footer text-muted small">Modelled conversion price: cap / current shares, less the discount. Indicative simulation — an actual conversion follows an EGM capital increase.</div>
    </div>
  </section>
</div>
