<?php use function App\{e, url, money, shares}; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Share issuances</h1>
  <a href="<?= url('/issuances/new') ?>" class="btn btn-primary"><i class="bi bi-plus-square me-1"></i>New issuance</a>
</div>
<table class="table table-hover bg-white shadow-sm" data-enhance="table">
  <thead class="table-dark"><tr><th>Reference</th><th>Date</th><th>Shareholder</th><th>Class</th><th>Contribution</th><th class="text-end">Shares</th><th class="text-end">Total value</th></tr></thead>
  <tbody>
    <?php foreach ($issuances as $i): ?>
    <tr>
      <td class="fw-semibold"><?= e($i['reference']) ?></td>
      <td><?= e($i['issuance_date']) ?></td>
      <td><?= e($i['shareholder_name']) ?></td>
      <td><span class="badge bg-secondary"><?= e($i['class_code']) ?></span></td>
      <td><?= $i['apport_type'] === 'cash' ? 'Cash contribution' : 'In-kind contribution' ?></td>
      <td class="text-end"><?= shares((int) $i['quantity']) ?></td>
      <td class="text-end"><?= money((int) $i['quantity'] * (int) $i['nominal_value']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($issuances === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No issuances.</td></tr><?php endif; ?>
  </tbody>
</table>
