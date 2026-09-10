<?php use function App\{e, url, shares}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?= __('Share transfers') ?></h1>
  <a href="<?= url('/transfers/new') ?>" class="btn btn-primary"><i class="bi bi-arrow-left-right me-1"></i><?= __('New transfer') ?></a>
</div>
<table class="table table-hover bg-white shadow-sm" data-enhance="table">
  <thead class="table-dark"><tr><th><?= __('Deed ref.') ?></th><th><?= __('Date') ?></th><th><?= __('Seller') ?></th><th><?= __('Buyer') ?></th><th><?= __('Class') ?></th><th class="text-end"><?= __('Shares') ?></th><th><?= __('Status') ?></th><th></th></tr></thead>
  <tbody>
    <?php foreach ($transfers as $t):
      $badges = ['pending' => '<span class="badge text-bg-warning">' . __('Pending approval') . '</span>',
                 'approved' => '<span class="badge text-bg-success">' . __('Approved') . '</span>',
                 'rejected' => '<span class="badge text-bg-danger">' . __('Rejected') . '</span>',
                 'executed' => '<span class="badge text-bg-secondary">' . __('Executed') . '</span>'];
      $status = $badges[$t['status']] ?? e($t['status']); ?>
    <tr>
      <td class="fw-semibold"><?= e($t['deed_reference']) ?></td>
      <td><?= e($t['transfer_date']) ?></td>
      <td><?= e($t['seller_name']) ?></td>
      <td><?= e($t['buyer_name']) ?></td>
      <td><span class="badge bg-secondary"><?= e($t['class_code']) ?></span></td>
      <td class="text-end"><?= shares((int) $t['quantity']) ?></td>
      <td><?= $status ?>
        <?php if ($t['status'] === 'pending' && $t['preemption_deadline']): ?>
          <small class="d-block text-muted"><?= __('pre-emption until') ?> <?= e($t['preemption_deadline']) ?></small>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($t['status'] === 'pending' && Auth::canWrite()): ?>
        <form method="post" action="<?= url('/transfers/' . $t['id'] . '/approve') ?>" class="d-inline">
          <?= App\Core\Csrf::field() ?>
          <input type="hidden" name="approval_date" value="<?= date('Y-m-d') ?>">
          <button class="btn btn-sm btn-success"><?= __('Approve') ?></button>
        </form>
        <form method="post" action="<?= url('/transfers/' . $t['id'] . '/reject') ?>" class="d-inline">
          <?= App\Core\Csrf::field() ?>
          <button class="btn btn-sm btn-outline-danger"><?= __('Reject') ?></button>
        </form>
        <?php elseif (in_array($t['status'], ['executed', 'approved'], true)): ?>
        <a class="btn btn-sm btn-outline-dark" href="<?= url('/documents/deeds/' . $t['id']) ?>"><?= __('Deed') ?></a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($transfers === []): ?><tr><td colspan="8" class="text-center text-muted py-4"><?= __('No transfers.') ?></td></tr><?php endif; ?>
  </tbody>
</table>
