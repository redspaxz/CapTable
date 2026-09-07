<?php use function App\{e, url, shares}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Shareholders</h1>
  <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
  <a href="<?= url('/shareholders/new') ?>" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>New shareholder</a>
  <?php endif; ?>
</div>

<table class="table table-hover bg-white shadow-sm" data-enhance="table">
  <thead class="table-dark">
    <tr><th>Name</th><th>Type</th><th>ID / RC</th><th>Contact</th><th class="text-end">Shares</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($shareholders as $s): ?>
    <tr>
      <td class="fw-semibold"><?= e($s['name']) ?></td>
      <td><span class="badge bg-<?= $s['type'] === 'corporate' ? 'dark' : 'secondary' ?>"><?= $s['type'] === 'corporate' ? 'Corporate' : 'Individual' ?></span></td>
      <td><?= e($s['id_type']) ?> <?= e($s['id_number']) ?></td>
      <td><?= e($s['email']) ?><br><small class="text-muted"><?= e($s['phone']) ?></small></td>
      <td class="text-end"><?= shares((int) $s['share_count']) ?></td>
      <td>
        <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/shareholders/' . $s['id'] . '/edit') ?>">Edit</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($shareholders === []): ?><tr><td colspan="6" class="text-muted text-center py-4">No shareholders registered.</td></tr><?php endif; ?>
  </tbody>
</table>
