<?php use function App\{e, url, money, shares}; use App\Core\Auth; ?>
<h1 class="h4 mb-3">Share classes</h1>

<div class="row">
  <div class="col-lg-8">
    <table class="table table-hover bg-white shadow-sm" data-enhance="table">
      <thead class="table-dark"><tr><th>Code</th><th>Label</th><th class="text-end">Par value</th><th class="text-end">Authorized</th><th class="text-end">Outstanding</th><th class="text-end">Liq. pref.</th></tr></thead>
      <tbody>
        <?php foreach ($classes as $c): ?>
        <tr>
          <td class="fw-bold"><?= e($c['code']) ?></td>
          <td><?= e($c['name']) ?><br><small class="text-muted"><?= e($c['rights']) ?></small></td>
          <td class="text-end"><?= money((int) $c['nominal_value']) ?></td>
          <td class="text-end"><?= shares((int) $c['shares_authorized']) ?></td>
          <td class="text-end fw-semibold"><?= shares((int) $c['outstanding']) ?></td>
          <td class="text-end">×<?= e(rtrim(rtrim((string) $c['liquidation_multiplier'], '0'), '.')) ?>
            <small class="text-muted">(prio <?= (int) $c['liquidation_priority'] ?>, <?= (int) $c['participing'] === 1 ? 'participating' : 'non-participating' ?>)</small></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($classes === []): ?><tr><td colspan="5" class="text-center text-muted py-4">No share classes yet. Create one on the right.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
  <div class="col-lg-4">
    <div class="card bg-white shadow-sm"><div class="card-body">
      <h2 class="h6 fw-bold">New share class</h2>
      <form method="post" action="<?= url('/classes') ?>">
        <?= App\Core\Csrf::field() ?>
        <div class="mb-2"><label class="form-label">Code *</label><input name="code" class="form-control" placeholder="ORD, PREF…" required></div>
        <div class="mb-2"><label class="form-label">Label *</label><input name="name" class="form-control" placeholder="Ordinary shares" required></div>
        <div class="mb-2"><label class="form-label">Par value (XAF) *</label><input name="nominal_value" type="number" min="1" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Authorized shares *</label><input name="shares_authorized" type="number" min="1" class="form-control" required></div>
        <div class="row g-2">
          <div class="col-4"><label class="form-label">Liq. pref. ×</label><input name="liquidation_multiplier" type="number" min="0" step="0.1" value="1" class="form-control"></div>
          <div class="col-4"><label class="form-label">Priority</label><input name="liquidation_priority" type="number" min="1" value="100" class="form-control"></div>
          <div class="col-4"><label class="form-label">Participating</label>
            <select name="participating" class="form-select"><option value="1">Yes</option><option value="0">No</option></select></div>
          <div class="col-6"><label class="form-label">OHADA category</label>
            <select name="category" class="form-select">
              <option value="ordinary">Ordinary</option>
              <option value="preference">Preference</option>
              <option value="adpsdv">Priority dividend, no voting rights</option>
            </select></div>
          <div class="col-6"><label class="form-label">Voting weight</label>
            <select name="voting_weight" class="form-select">
              <option value="1">×1 (ordinary)</option>
              <option value="2">×2 (double voting rights)</option>
              <option value="0">×0 (no voting rights)</option>
            </select></div>
          <div class="col-6"><label class="form-label">Approval clause</label>
            <select name="requires_approval" class="form-select"><option value="0">No</option><option value="1">Yes</option></select></div>
          <div class="col-6"><label class="form-label">Lock-up until</label>
            <input name="lockup_until" type="date" class="form-control">
            <div class="form-text">10 years max (OHADA art. 2-1)</div></div>
        </div>
        <div class="mb-3"><label class="form-label">Rights attached</label><textarea name="rights" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100">Create</button>
      </form>
    </div></div>
  </div>
  <?php endif; ?>
</div>
