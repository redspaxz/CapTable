<?php use function App\{e, url, shares, money}; $t = $transfer; ?>
<div class="row justify-content-center my-4">
  <div class="col-lg-9">
    <div class="card bg-white shadow">
      <div class="card-body p-5">
        <h1 class="h5 text-center fw-bold mb-4">ACTE DE CESSION DE DROITS SOCIAUX</h1>
        <p class="text-center text-muted">Référence : <?= e($t['deed_reference']) ?> — Date : <?= e($t['transfer_date']) ?></p>

        <p><strong>ENTRE LES SOUSSIGNÉS :</strong></p>
        <p>1. <strong><?= e($t['seller_name']) ?></strong>, pièce d'identité / registre n° <?= e($t['seller_id_number']) ?>, ci-après dénommé « le Cédant », d'une part ;</p>
        <p>2. <strong><?= e($t['buyer_name']) ?></strong>, pièce d'identité / registre n° <?= e($t['buyer_id_number']) ?>, ci-après dénommé « le Cessionnaire », d'autre part.</p>

        <p><strong>IL A ÉTÉ CONVENU ET ARRÊTÉ CE QUI SUIT :</strong></p>
        <p>Article 1 — Objet. Le Cédant cède et transfère au Cessionnaire, qui accepte, <strong><?= shares((int) $t['quantity']) ?> actions de catégorie <?= e($t['class_code']) ?></strong> (<?= e($t['class_name']) ?>, valeur nominale <?= money((int) $t['nominal_value']) ?> l'action) de la société <?= e($company['name'] ?? '') ?> (RCCM <?= e($company['rccm'] ?? '—') ?>).</p>
        <p>Article 2 — Prix. Le prix de cession, payé intégralement à la signature, est de : ________________ XAF (à compléter sur l'original).</p>
        <p>Article 3 — Ventes. La cession est réalisée dans les conditions prévues par les statuts et l'Acte uniforme OHADA relatif au droit des sociétés commerciales et du GIE (AUSCGIE). Les parties reconnaissent avoir satisfait au droit de préemption prévu par les statuts.</p>
        <p>Article 4 — Entrée en jouissance. Le Cessionnaire jouira des droits attachés aux titres cédés à compter de la signature du présent acte et de son inscription au registre des mouvements de titres.</p>

        <div class="d-flex justify-content-around mt-5">
          <div class="text-center"><div style="height:60px"></div><div class="border-top px-5 pt-1 small">Le Cédant</div></div>
          <div class="text-center"><div style="height:60px"></div><div class="border-top px-5 pt-1 small">Le Cessionnaire</div></div>
        </div>
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-dark" onclick="window.print()">🖨 Imprimer l'acte</button>
      <a class="btn btn-outline-secondary" href="<?= url('/transfers') ?>">Retour</a>
    </div>
  </div>
</div>
