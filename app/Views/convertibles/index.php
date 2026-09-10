<?php use function App\{e, url, shares, money, pct}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0"><?= __('Convertible instruments') ?></h1>
    <div class="text-muted small"><?= __('OCA (convertible bonds), BSA (subscription warrants), SAFE — dilution modelling at conversion') ?></div>
  </div>
  <a class="btn btn-outline-primary" href="<?= url('/captable') ?>"><i class="bi bi-pie-chart me-1"></i><?= __('Current cap table') ?></a>
</div>

<div class="row g-3">
  <section class="col-lg-7">
    <div class="card bg-white shadow-sm">
      <div class="card-header"><?= __('Outstanding instruments') ?></div>
      <table class="table table-sm table-hover mb-0" data-enhance="table">
        <thead><tr><th><?= __('Type') ?></th><th><?= __('Holder') ?></th><th class="text-end"><?= __('Principal') ?></th><th class="text-end"><?= __('Discount') ?></th><th class="text-end"><?= __('Cap') ?></th><th class="text-end"><?= __('Modelled shares') ?></th></tr></thead>
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
          <?php if ($instruments === []): ?><tr><td colspan="6" class="text-center text-muted py-4"><?= __('No convertible instruments.') ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (Auth::canWrite()): ?>
    <div class="card bg-white shadow-sm mt-3"><div class="card-header"><?= __('New instrument') ?></div>
      <div class="card-body">
        <form method="post" action="<?= url('/convertibles') ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="row g-2">
            <div class="col-md-2"><label class="form-label"><?= __('Type') ?></label>
              <select name="type" class="form-select"><option>OCA</option><option>BSA</option><option>SAFE</option></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('Holder') ?> *</label>
              <input name="holder" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label"><?= __('Principal (XAF)') ?> *</label>
              <input type="number" min="1" name="principal_amount" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label"><?= __('Date') ?></label>
              <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-3"><label class="form-label"><?= __('Discount %') ?></label>
              <input type="number" min="0" max="99" step="0.5" name="discount_pct" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label"><?= __('Valuation cap (XAF)') ?></label>
              <input type="number" min="0" name="valuation_cap" class="form-control" placeholder="<?= __('Optional') ?>"></div>
          </div>
          <button class="btn btn-primary btn-sm mt-3"><?= __('Save') ?></button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <section class="col-lg-5">
    <div class="card bg-white shadow-sm h-100">
      <div class="card-header"><?= __('Pro-forma cap table (full conversion)') ?></div>
      <table class="table table-sm mb-0">
        <thead><tr><th><?= __('Holder') ?></th><th class="text-end"><?= __('Current') ?></th><th class="text-end"><?= __('Diluted') ?></th><th class="text-end">%</th></tr></thead>
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
          <tr><td><?= __('Total') ?></td><td class="text-end"><?= shares($currentShares) ?></td>
              <td class="text-end"><?= shares(array_sum(array_map(fn($r) => $r['modelled'], $proforma))) ?></td><td></td></tr>
        </tfoot>
      </table>
      <div class="card-footer text-muted small"><?= __('Modelled conversion price: cap / current shares, less the discount. Indicative simulation — an actual conversion follows an EGM capital increase.') ?></div>
    </div>
  </section>
</div>
