<?php use function App\{e, url, shares, pct}; ?>
<h1 class="h4 mb-1">Assemblée &amp; droits de vote</h1>
<p class="text-muted small">Puissance de vote calculée par <?= e($company['legal_form'] ?? '') ?> : poids par catégorie (×1 ordinaire, ×0 ADPSDV sans droit de vote, ×2 double droit de vote), quorum et majorité paramétrables (défauts OHADA : AGE quorum 50 % / majorité 2/3).</p>

<form class="row g-2 align-items-end mb-3" method="get" action="<?= url('/meeting') ?>">
  <div class="col-auto">
    <label class="form-label small mb-0">Type</label>
    <select name="kind" class="form-select form-select-sm">
      <?php foreach (['AGO', 'AGE', 'AGC'] as $k): ?>
      <option value="<?= $k ?>" <?= $kind === $k ? 'selected' : '' ?>><?= $k ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label small mb-0">Quorum (%)</label>
    <input type="number" min="0" max="100" step="0.01" name="quorum" class="form-control form-control-sm" value="<?= e((string) $quorum) ?>">
  </div>
  <div class="col-auto">
    <label class="form-label small mb-0">Majorité (%)</label>
    <input type="number" min="0" max="100" step="0.01" name="majority" class="form-control form-control-sm" value="<?= e((string) $majority) ?>">
  </div>
  <div class="col-auto"><button class="btn btn-primary btn-sm">Recalculer</button></div>
</form>

<div class="row g-3">
  <section class="col-lg-7">
    <table class="table table-hover bg-white shadow-sm">
      <thead class="table-dark"><tr><th>Actionnaire</th><th>Détail par catégorie</th><th class="text-end">Voix</th><th class="text-end">% droits de vote</th></tr></thead>
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
      <tfoot class="table-light fw-bold"><tr><td colspan="2">Total droits de vote</td><td class="text-end"><?= shares($power['total']) ?></td><td class="text-end">100 %</td></tr></tfoot>
    </table>
  </section>
  <section class="col-lg-5">
    <div class="card bg-white shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-3"><i class="bi bi-calculator me-2"></i><?= e($kind) ?> — règles de décision</h2>
      <dl class="row small mb-0">
        <dt class="col-7">Quorum requis</dt><dd class="col-5 text-end"><?= shares($math['quorum_required']) ?> voix</dd>
        <dt class="col-7">Majorité pour adopter</dt><dd class="col-5 text-end"><?= shares($math['votes_for_required']) ?> voix</dd>
        <dt class="col-7">Minorité de blocage</dt><dd class="col-5 text-end"><?= shares($math['blocking']) ?> voix</dd>
      </dl>
      <div class="alert <?= $math['quorum_met'] ? 'alert-success' : 'alert-danger' ?> mt-3 mb-0 small">
        <?= $math['quorum_met']
            ? 'Quorum atteint si tous les droits de vote sont représentés.'
            : 'Quorum insuffisant : convoquer une seconde assemblée.' ?>
      </div>
      <p class="text-muted small mt-3 mb-0">Rédigez le procès-verbal correspondant depuis <a href="<?= url('/documents/minutes/new') ?>">Documents → PV d'assemblée</a> (modèles AGE d'augmentation de capital disponibles).</p>
    </div></div>
  </section>
</div>
