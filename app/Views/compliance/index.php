<?php use function App\{e, url, shares, pct}; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Conformité OHADA</h1>
    <div class="text-muted small">Registres statutaires, agréments, conventions réglementées et bénéficiaires effectifs — <?= e($company['legal_form'] ?? '') ?> (<?= e($unit['plural']) ?>)</div>
  </div>
  <div>
    <a class="btn btn-outline-primary btn-sm" href="<?= url('/meeting') ?>"><i class="bi bi-clipboard2-check me-1"></i>Droits de vote</a>
    <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
    <a class="btn btn-primary btn-sm" href="<?= url('/compliance/ubo/new') ?>"><i class="bi bi-person-lock me-1"></i>Déclarer un bénéficiaire</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <section class="col-lg-6">
    <div class="card bg-white shadow-sm h-100">
      <div class="card-header"><i class="bi bi-hourglass-split me-2"></i>Cessions en attente d'agrément / préemption</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($pending as $t): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <span class="fw-semibold"><?= shares((int) $t['quantity']) ?> <?= e($t['class_code']) ?></span>
            — <?= e($t['seller_name']) ?> → <?= e($t['buyer_name']) ?><br>
            <small class="text-muted">Acte <?= e($t['deed_reference']) ?> · préemption jusqu'au <?= e($t['preemption_deadline']) ?></small>
          </div>
          <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
          <form method="post" action="<?= url('/transfers/' . $t['id'] . '/approve') ?>" class="d-inline">
            <?= App\Core\Csrf::field() ?>
            <input type="hidden" name="approval_date" value="<?= date('Y-m-d') ?>">
            <button class="btn btn-sm btn-success">Approuver</button>
          </form>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <?php if ($pending === []): ?><li class="list-group-item text-muted">Aucune cession en attente.</li><?php endif; ?>
      </ul>
    </div>
  </section>

  <section class="col-lg-6">
    <div class="card bg-white shadow-sm h-100">
      <div class="card-header"><i class="bi bi-person-lock me-2"></i>Bénéficiaires effectifs (COBAC / DGI)</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($ubo['alerts'] as $alert): ?>
        <li class="list-group-item text-danger small"><i class="bi bi-exclamation-triangle me-1"></i><?= e($alert) ?></li>
        <?php endforeach; ?>
        <?php foreach ($ubo['declared'] as $d): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span><?= e($d['name']) ?> <small class="text-muted">(<?= e($d['id_number']) ?><?= $d['shareholder_name'] ? ' · ' . e($d['shareholder_name']) : '' ?>)</small></span>
          <span class="fw-semibold"><?= pct((float) $d['ownership_pct']) ?></span>
        </li>
        <?php endforeach; ?>
        <?php if ($ubo['declared'] === [] && $ubo['alerts'] === []): ?><li class="list-group-item text-muted">Aucune déclaration enregistrée.</li><?php endif; ?>
      </ul>
      <div class="card-footer text-muted small">Seuil de déclaration : <?= (int) $uboThreshold ?> % du capital. Transparence COBAC / DGI.</div>
    </div>
  </section>

  <section class="col-12">
    <div class="card bg-white shadow-sm">
      <div class="card-header"><i class="bi bi-exclamation-diamond me-2"></i>Conventions réglementées (≥ <?= (int) $threshold ?> % — art. 440 AUSCGIE)</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0" data-enhance="table">
          <thead><tr><th>Partie</th><th class="text-end">Détention</th><th class="text-end">Mouvements liés</th><th>Alerte CAC</th></tr></thead>
          <tbody>
            <?php foreach ($regulated as $r): ?>
            <tr>
              <td class="fw-semibold"><?= e($r['shareholder']['name']) ?></td>
              <td class="text-end"><?= pct($r['percentage']) ?></td>
              <td class="text-end"><?= count($r['movements']) ?></td>
              <td><span class="badge text-bg-warning">Divulgation au commissaire aux comptes requise</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($regulated === []): ?><tr><td colspan="4" class="text-muted text-center py-3">Aucune partie ≥ <?= (int) $threshold ?> %.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
