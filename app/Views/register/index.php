<?php use function App\{e, url, shares}; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Registre des mouvements de titres</h1>
    <div class="text-muted small">AUSCGIE (Acte uniforme OHADA), art. 716 — <?= e($company['name'] ?? '') ?></div>
  </div>
  <button class="btn btn-outline-dark" onclick="window.print()">🖨 Imprimer</button>
</div>
<table class="table table-sm table-striped bg-white shadow-sm" data-enhance="table">
  <thead class="table-dark"><tr><th>#</th><th>Date</th><th>Type</th><th>Titulaire</th><th>Contrepartie</th><th>Catégorie</th><th class="text-end">Titres</th><th>Référence</th><th>Notaire / RCCM</th></tr></thead>
  <tbody>
    <?php foreach ($movements as $m):
      $labels = ['issuance' => 'Émission', 'transfer_out' => 'Cession — sortie', 'transfer_in' => 'Cession — entrée']; ?>
    <tr>
      <td><?= (int) $m['id'] ?></td>
      <td><?= e($m['movement_date']) ?></td>
      <td><span class="badge bg-<?= $m['movement_type'] === 'issuance' ? 'success' : 'warning' ?>"><?= e($labels[$m['movement_type']] ?? $m['movement_type']) ?></span></td>
      <td><?= e($m['shareholder_name'] ?? '—') ?></td>
      <td><?= e($m['counterparty_name'] ?? '—') ?></td>
      <td><?= e($m['class_code']) ?></td>
      <td class="text-end"><?= shares((int) $m['quantity']) ?></td>
      <td><?= e($m['reference']) ?></td>
      <td class="small">
        <?php if (!empty($m['notary_reference'])): ?>
          <span class="badge text-bg-dark">Notaire <?= e($m['notary_reference']) ?></span>
        <?php endif; ?>
        <?php if (!empty($m['rccm_reference'])): ?>
          <span class="badge text-bg-info">RCCM <?= e($m['rccm_reference']) ?></span>
        <?php endif; ?>
        <?php if (empty($m['notary_reference']) && empty($m['rccm_reference'])): ?>
          <span class="text-muted">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($movements === []): ?><tr><td colspan="9" class="text-center text-muted py-4">Registre vide.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php if (($pages ?? 1) > 1): ?>
<nav aria-label="Pagination du registre">
  <ul class="pagination pagination-sm justify-content-center mt-3">
    <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= url('/register?page=' . (($page ?? 1) - 1)) ?>">← Précédent</a>
    </li>
    <li class="page-item disabled">
      <span class="page-link">Page <?= (int) ($page ?? 1) ?> / <?= (int) ($pages ?? 1) ?> — <?= number_format((float) ($total ?? 0), 0, ',', ' ') ?> mouvement(s)</span>
    </li>
    <li class="page-item <?= ($page ?? 1) >= ($pages ?? 1) ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= url('/register?page=' . (($page ?? 1) + 1)) ?>">Suivant →</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
