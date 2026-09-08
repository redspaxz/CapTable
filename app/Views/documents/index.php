<?php use function App\{e, url, shares}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?= __('Documents & reports') ?></h1>
  <div>
    <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
    <a class="btn btn-primary" href="<?= url('/documents/certificates/new') ?>">+ <?= __('Share certificate') ?></a>
    <a class="btn btn-outline-primary" href="<?= url('/documents/minutes/new') ?>">+ <?= __('Meeting minutes') ?></a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card bg-white shadow-sm">
      <div class="card-header fw-bold"><?= __('Share certificates') ?></div>
      <table class="table table-hover mb-0" data-enhance="table">
        <thead><tr><th><?= __('Certificate no.') ?></th><th><?= __('Shareholder') ?></th><th><?= __('Class') ?></th><th class="text-end"><?= __('Shares') ?></th><th><?= __('Issued on') ?></th><th></th></tr></thead>
        <tbody>
          <?php foreach ($certificates as $c): ?>
          <tr>
            <td class="fw-semibold"><?= e($c['certificate_number']) ?></td>
            <td><?= e($c['shareholder_name']) ?></td>
            <td><?= e($c['class_code']) ?></td>
            <td class="text-end"><?= shares((int) $c['quantity']) ?></td>
            <td><?= e($c['issue_date']) ?></td>
            <td><a class="btn btn-sm btn-outline-dark" href="<?= url('/documents/certificates/' . $c['id']) ?>"><?= __('View / print') ?></a></td>
          </tr>
          <?php endforeach; ?>
          <?php if ($certificates === []): ?><tr><td colspan="6" class="text-center text-muted py-4"><?= __('No certificates issued.') ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card bg-white shadow-sm">
      <div class="card-header fw-bold"><?= __('OHADA reports') ?></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= __('Share movement register (art. 716 AUSCGIE)') ?>
          <a class="btn btn-sm btn-outline-dark" href="<?= url('/register') ?>"><?= __('Open') ?></a>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= __('Capital breakdown (cap table)') ?>
          <a class="btn btn-sm btn-outline-dark" href="<?= url('/captable') ?>"><?= __('Open') ?></a>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= __('General meeting minutes') ?>
          <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
          <a class="btn btn-sm btn-outline-dark" href="<?= url('/documents/minutes/new') ?>"><?= __('Draft') ?></a>
          <?php else: ?><span class="text-muted small"><?= __('Read only') ?></span><?php endif; ?>
        </li>
      </ul>
    </div>
  </div>
</div>
