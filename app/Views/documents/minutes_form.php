<?php use function App\{e, url, shares, pct}; ?>
<h1 class="h4 mb-3">Rédiger un PV d'assemblée générale</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/documents/minutes') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Type d'assemblée *</label>
      <select name="meeting_type" id="meeting_type" class="form-select">
        <option value="AGE">AGE (extraordinaire)</option>
        <option value="AGO">AGO (ordinaire)</option>
        <option value="AGC">AG constitutive</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Modèle de résolution</label>
      <select id="template" class="form-select">
        <option value="">— Libre —</option>
        <option value="capital_increase">Augmentation de capital</option>
        <option value="buyback">Rachat / annulation d'actions</option>
        <option value="conversion">Conversion d'OCA / BSA</option>
        <option value="transfer_approval">Approbation de cession (agrément)</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date *</label>
      <input type="date" name="meeting_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Lieu *</label>
      <input name="location" class="form-control" value="Siège social" required>
    </div>
    <div class="col-12">
      <label class="form-label">Ordre du jour *</label>
      <textarea name="agenda" class="form-control" rows="3" required></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Résolutions adoptées *</label>
      <textarea name="resolutions" class="form-control" rows="5" required></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Générer le PV</button>
    <a href="<?= url('/documents') ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
</div></div>

<h2 class="h6 mt-4">Présence prévue (répartition actuelle)</h2>
<table class="table table-sm bg-white shadow-sm w-auto">
  <thead><tr><th>Actionnaire</th><th class="text-end">Titres</th><th class="text-end">% — droit de vote</th></tr></thead>
  <tbody>
    <?php foreach ($holdings as $h): ?>
    <tr><td><?= e($h['shareholder']['name']) ?></td><td class="text-end"><?= shares($h['total']) ?></td><td class="text-end"><?= pct($h['percentage']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var templates = {
    capital_increase: {
      agenda: "1. Rapport du conseil sur l'augmentation de capital proposée.\n2. Augmentation du capital social par émission de nouvelles actions.\n3. Modification corrélative des statuts.",
      resolutions: "L'assemblée, statuant aux conditions de quorum et de majorité des assemblées générales extraordinaires, après lecture du rapport du conseil et du rapport du commissaire aux comptes :\n\nPREMIÈRE RÉSOLUTION — L'assemblée décide d'augmenter le capital social de [MONTANT] XAF, par émission de [NOMBRE] actions de [VALEUR] XAF chacune, avec droit de préférence à la souscription au profit des actionnaires existants.\n\nDEUXIÈME RÉSOLUTION — Les statuts sont modifiés en conséquence : l'article [X] est amendé pour porter le capital à [NOUVEAU CAPITAL] XAF.\n\nTROISIÈME RÉSOLUTION — Tous pouvoirs sont conférés au porteur d'un original aux fins d'accomplir les formalités de dépôt au greffe et de publicité au RCCM."
    },
    buyback: {
      agenda: "1. Rachat par la société de ses propres actions.\n2. Annulation des actions rachetées et réduction corrélative du capital.",
      resolutions: "PREMIÈRE RÉSOLUTION — L'assemblée autorise la société à racheter [NOMBRE] actions de catégorie [CODE], dans le respect des limites de l'AUSCGIE.\n\nDEUXIÈME RÉSOLUTION — Les actions rachetées seront annulées et le capital réduit de [MONTANT] XAF ; les statuts sont modifiés en conséquence."
    },
    conversion: {
      agenda: "1. Conversion des obligations convertibles en actions / exercice des bons de souscription.\n2. Augmentation de capital corrélative.",
      resolutions: "PREMIÈRE RÉSOLUTION — L'assemblée constate la conversion de [NOMBRE] obligations convertibles (référence [REF]) en [NOMBRE] actions nouvelles et décide l'augmentation de capital corrélative de [MONTANT] XAF.\n\nDEUXIÈME RÉSOLUTION — Les statuts sont modifiés en conséquence et les formalités RCCM seront accomplies par le porteur d'un original."
    },
    transfer_approval: {
      agenda: "1. Demande d'agrément d'une cession de [NOMBRE] actions de catégorie [CODE] par [CÉDANT] au profit de [CESSIONNAIRE].\n2. Droit de préemption des associés.",
      resolutions: "PREMIÈRE RÉSOLUTION — L'assemblée, statuant sur la demande d'agrément présentée conformément aux statuts, agrée [CESSIONNAIRE] en qualité de cessionnaire de [NOMBRE] actions.\n\nDEUXIÈME RÉSOLUTION — Il est donné conscience aux associés de leur droit de préemption dans un délai de trente jours ; à défaut d'exercice, la cession sera réalisée aux conditions notifiées."
    }
  };
  var tpl = document.getElementById('template');
  if (!tpl) return;
  tpl.addEventListener('change', function () {
    var data = templates[tpl.value];
    if (!data) return;
    document.querySelector('[name="agenda"]').value = data.agenda;
    document.querySelector('[name="resolutions"]').value = data.resolutions;
    document.getElementById('meeting_type').value = 'AGE';
  });
});
</script>
