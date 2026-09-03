<?php use function App\{e, shares}; ?>
<div class="row justify-content-center my-4">
  <div class="col-lg-8">
    <div class="card certificate border-dark shadow">
      <div class="card-body p-5">
        <div class="text-center border-bottom pb-3 mb-4">
          <div class="text-uppercase fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></div>
          <div class="small">Société Anonyme avec Conseil d'Administration — Droit OHADA</div>
          <div class="small">RCCM <?= e($company['rccm'] ?? '—') ?> · NIU <?= e($company['niu'] ?? '—') ?> · Siège : <?= e($company['head_office'] ?? '—') ?></div>
        </div>
        <h1 class="h5 text-center fw-bold mb-4">CERTIFICAT D'ACTIONS</h1>
        <p class="text-center lead">Certificat N° <strong><?= e($cert['certificate_number']) ?></strong></p>
        <p>La société certifie que <strong><?= e($cert['shareholder_name']) ?></strong>
          (<?= $cert['shareholder_type'] === 'corporate' ? 'personne morale' : 'personne physique' ?>,
          pièce d'identité : <?= e($cert['id_number']) ?>) est propriétaire de
          <strong><?= shares((int) $cert['quantity']) ?> actions <?= e($cert['code']) ?></strong>
          (<?= e($cert['class_name']) ?>) de la valeur nominale unitaire inscrite au capital social.</p>
        <p class="small text-muted">Ce certificat est émis en application des dispositions de l'Acte uniforme relatif au droit des sociétés commerciales et du GIE (AUSCGIE). Il devra être restitué à la société en cas de cession des droits qu'il représente.</p>
        <div class="d-flex justify-content-between mt-5">
          <div>Émis le <?= e($cert['issue_date']) ?></div>
          <div class="text-center">
            <div style="height: 60px"></div>
            <div class="border-top px-5 pt-1 small">Le Directeur Général</div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-dark" onclick="window.print()">🖨 Imprimer le certificat</button>
    </div>
  </div>
</div>
