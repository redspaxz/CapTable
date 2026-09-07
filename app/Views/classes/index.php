<?php use function App\{e, url, money, shares}; use App\Core\Auth; ?>
<h1 class="h4 mb-3">Catégories d'actions</h1>

<div class="row">
  <div class="col-lg-8">
    <table class="table table-hover bg-white shadow-sm" data-enhance="table">
      <thead class="table-dark"><tr><th>Code</th><th>Libellé</th><th class="text-end">Valeur nominale</th><th class="text-end">Autorisées</th><th class="text-end">En circulation</th><th class="text-end">Préférence liq.</th></tr></thead>
      <tbody>
        <?php foreach ($classes as $c): ?>
        <tr>
          <td class="fw-bold"><?= e($c['code']) ?></td>
          <td><?= e($c['name']) ?><br><small class="text-muted"><?= e($c['rights']) ?></small></td>
          <td class="text-end"><?= money((int) $c['nominal_value']) ?></td>
          <td class="text-end"><?= shares((int) $c['shares_authorized']) ?></td>
          <td class="text-end fw-semibold"><?= shares((int) $c['outstanding']) ?></td>
          <td class="text-end">×<?= e(rtrim(rtrim((string) $c['liquidation_multiplier'], '0'), '.')) ?>
            <small class="text-muted">(prio <?= (int) $c['liquidation_priority'] ?>, <?= (int) $c['participing'] === 1 ? 'participante' : 'non part.' ?>)</small></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($classes === []): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucune catégorie. Créez-en une à droite.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (in_array(Auth::role(), ['admin', 'finance'], true)): ?>
  <div class="col-lg-4">
    <div class="card bg-white shadow-sm"><div class="card-body">
      <h2 class="h6 fw-bold">Nouvelle catégorie</h2>
      <form method="post" action="<?= url('/classes') ?>">
        <?= App\Core\Csrf::field() ?>
        <div class="mb-2"><label class="form-label">Code *</label><input name="code" class="form-control" placeholder="ORD, PREF…" required></div>
        <div class="mb-2"><label class="form-label">Libellé *</label><input name="name" class="form-control" placeholder="Actions ordinaires" required></div>
        <div class="mb-2"><label class="form-label">Valeur nominale (XAF) *</label><input name="nominal_value" type="number" min="1" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Actions autorisées *</label><input name="shares_authorized" type="number" min="1" class="form-control" required></div>
        <div class="row g-2">
          <div class="col-4"><label class="form-label">Préférence liq. ×</label><input name="liquidation_multiplier" type="number" min="0" step="0.1" value="1" class="form-control"></div>
          <div class="col-4"><label class="form-label">Priorité</label><input name="liquidation_priority" type="number" min="1" value="100" class="form-control"></div>
          <div class="col-4"><label class="form-label">Participante</label>
            <select name="participating" class="form-select"><option value="1">Oui</option><option value="0">Non</option></select></div>
        </div>
        <div class="mb-3"><label class="form-label">Droits attachés</label><textarea name="rights" class="form-control" rows="2"></textarea></div>
        <button class="btn btn-primary w-100">Créer</button>
      </form>
    </div></div>
  </div>
  <?php endif; ?>
</div>
