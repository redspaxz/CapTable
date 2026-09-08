<?php use function App\{e, url, shares, pct}; ?>
<h1 class="h4 mb-1"><?= __('Meeting & voting rights') ?></h1>
<p class="text-muted small"><?= __('Voting power computed for') ?> <?= e($company['legal_form'] ?? '') ?>: <?= __('per-class weight (×1 ordinary, ×0 no voting rights, ×2 double voting rights), configurable quorum and majority (OHADA defaults: EGM quorum 50 % / majority 2/3).') ?></p>

<form class="row g-2 align-items-end mb-3" method="get" action="<?= url('/meeting') ?>">
  <div class="col-auto">
    <label class="form-label small mb-0"><?= __('Type') ?></label>
    <select name="kind" class="form-select form-select-sm">
      <?php foreach (['AGO', 'AGE', 'AGC'] as $k): ?>
      <option value="<?= $k ?>" <?= $kind === $k ? 'selected' : '' ?>><?= $k ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label small mb-0"><?= __('Quorum (%)') ?></label>
    <input type="number" min="0" max="100" step="0.01" name="quorum" class="form-control form-control-sm" value="<?= e((string) $quorum) ?>">
  </div>
  <div class="col-auto">
    <label class="form-label small mb-0"><?= __('Majority (%)') ?></label>
    <input type="number" min="0" max="100" step="0.01" name="majority" class="form-control form-control-sm" value="<?= e((string) $majority) ?>">
  </div>
  <div class="col-auto"><button class="btn btn-primary btn-sm"><?= __('Recalculate') ?></button></div>
</form>

<div class="row g-3">
  <section class="col-lg-7">
    <table class="table table-hover bg-white shadow-sm">
      <thead class="table-dark"><tr><th><?= __('Shareholder') ?></th><th><?= __('Detail by class') ?></th><th class="text-end"><?= __('Votes') ?></th><th class="text-end"><?= __('% voting rights') ?></th></tr></thead>
      <tbody>
        <?php foreach ($power['holders'] as $p): ?>
        <tr>
          <td class="fw-semibold"><?= e($p['shareholder']['name']) ?></td>
          <td class="small text-muted"><?= e(implode(' · ', $p['detail'])) ?></td>
          <td class="text-end fw-bold"><?= shares($p['votes']) ?></td>
          <td class="text-end"><?= pct($p['pct']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-bold"><tr><td colspan="2"><?= __('Total voting rights') ?></td><td class="text-end"><?= shares($power['total']) ?></td><td class="text-end">100 %</td></tr></tfoot>
    </table>
  </section>
  <section class="col-lg-5">
    <div class="card bg-white shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-3"><i class="bi bi-calculator me-2"></i><?= e($kind) ?> — <?= __('decision rules') ?></h2>
      <dl class="row small mb-0">
        <dt class="col-7"><?= __('Required quorum') ?></dt><dd class="col-5 text-end"><?= shares($math['quorum_required']) ?> <?= __('votes') ?></dd>
        <dt class="col-7"><?= __('Majority to adopt') ?></dt><dd class="col-5 text-end"><?= shares($math['votes_for_required']) ?> <?= __('votes') ?></dd>
        <dt class="col-7"><?= __('Blocking minority') ?></dt><dd class="col-5 text-end"><?= shares($math['blocking']) ?> <?= __('votes') ?></dd>
      </dl>
      <div class="alert <?= $math['quorum_met'] ? 'alert-success' : 'alert-danger' ?> mt-3 mb-0 small">
        <?= $math['quorum_met']
            ? __('Quorum met if all voting rights are represented.')
            : __('Insufficient quorum: convene a second meeting.') ?>
      </div>
      <p class="text-muted small mt-3 mb-0"><?= __('Draft the corresponding minutes from') ?> <a href="<?= url('/documents/minutes/new') ?>"><?= __('Documents → Meeting minutes') ?></a> (<?= __('EGM capital-increase templates available.') ?>)</p>
    </div></div>
  </section>
</div>
