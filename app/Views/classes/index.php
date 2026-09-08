<?php use function App\{e, url, money, shares}; use App\Core\Auth; ?>
<h1 class="h4 mb-3"><?= __('Share classes') ?></h1>

<div class="row">
  <div class="col-lg-8">
    <table class="table table-hover bg-white shadow-sm" data-enhance="table">
      <thead class="table-dark"><tr><th><?= __('Code') ?></th><th><?= __('Label') ?></th><th class="text-end"><?= __('Par value') ?></th><th class="text-end"><?= __('Authorized') ?></th><th class="text-end"><?= __('Outstanding') ?></th><th class="text-end"><?= __('Liq. pref.') ?></th></tr></thead>
      <tbody>
        <?php foreach ($classes as $c): ?>
        <tr>
          <td class="fw-bold"><?= e($c['code']) ?></td>
          <td><?= e($c['name']) ?><br><small class="text-muted"><?= e($c['rights']) ?></small></td>
          <td class="text-end"><?= money((int) $c['nominal_value']) ?></td>
          <td class="text-end"><?= shares((int) $c['shares_authorized']) ?></td>
          <td class="text-end fw-semibold"><?= shares((int) $c['outstanding']) ?></td>
          <td class="text-end">×<?= e(rtrim(rtrim((string) $c['liquidation_multiplier'], '0'), '.')) ?>
            <small class="text-muted">(<?= __('Priority') ?> <?= (int) $c['liquidation_priority'] ?>, <?= (int) $c['participing'] === 1 ? __('participating') : __('non-participating') ?>)</small></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($classes === []): ?><tr><td colspan="5" class="text-center text-muted py-4"><?= __('No share classes yet. Create one on the right.') ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
  <div class="col-lg-4">
    <div class="card bg-white shadow-sm"><div class="card-body">
      <h2 class="h6 fw-bold"><?= __('New share class') ?></h2>
      <form method="post" action="<?= url('/classes') ?>">
        <?= App\Core\Csrf::field() ?>
        <div class="mb-2"><label class="form-label"><?= __('Code') ?> *</label><input name="code" class="form-control" placeholder="ORD, PREF…" required></div>
        <div class="mb-2"><label class="form-label"><?= __('Label') ?> *</label><input name="name" class="form-control" placeholder="<?= __('Ordinary shares') ?>" required></div>
        <div class="mb-2"><label class="form-label"><?= __('Par value') ?> (XAF) *</label><input name="nominal_value" type="number" min="1" class="form-control" required></div>
        <div class="mb-2"><label class="form-label"><?= __('Authorized shares') ?> *</label><input name="shares_authorized" type="number" min="1" class="form-control" required></div>
        <div class="row g-2">
          <div class="col-4"><label class="form-label"><?= __('Liq. pref.') ?> ×</label><input name="liquidation_multiplier" type="number" min="0" step="0.1" value="1" class="form-control"></div>
          <div class="col-4"><label class="form-label"><?= __('Priority') ?></label><input name="liquidation_priority" type="number" min="1" value="100" class="form-control"></div>
          <div class="col-4"><label class="form-label"><?= __('Participating') ?></label>
            <select name="participating" class="form-select"><option value="1"><?= __('Yes') ?></option><option value="0"><?= __('No') ?></option></select></div>
          <div class="col-6"><label class="form-label"><?= __('OHADA category') ?></label>
            <select name="category" class="form-select">
              <option value="ordinary"><?= __('Ordinary') ?></option>
              <option value="preference"><?= __('Preference') ?></option>
              <option value="adpsdv"><?= __('Priority dividend, no voting rights') ?></option>
            </select></div>
          <div class="col-6"><label class="form-label"><?= __('Voting weight') ?></label>
            <select name="voting_weight" class="form-select">
              <option value="1">×1 (<?= __('ordinary') ?>)</option>
              <option value="2">×2 (<?= __('double voting rights') ?>)</option>
              <option value="0">×0 (<?= __('no voting rights') ?>)</option>
            </select></div>
          <div class="col-6"><label class="form-label"><?= __('Approval clause') ?></label>
            <select name="requires_approval" class="form-select"><option value="0"><?= __('No') ?></option><option value="1"><?= __('Yes') ?></option></select></div>
          <div class="col-6"><label class="form-label"><?= __('Lock-up until') ?></label>
            <input name="lockup_until" type="date" class="form-control">
            <div class="form-text"><?= __('10 years max (OHADA art. 2-1)') ?></div></div>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('Rights attached') ?></label><textarea name="rights" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100"><?= __('Create') ?></button>
      </form>
    </div></div>
  </div>
  <?php endif; ?>
</div>
