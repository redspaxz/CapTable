<?php use function App\{e, shares, pct}; ?>
<div class="row justify-content-center my-4">
  <div class="col-lg-9">
    <div class="card bg-white shadow">
      <div class="card-body p-5">
        <div class="text-center border-bottom pb-3 mb-4">
          <div class="text-uppercase fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></div>
          <div class="small">RCCM <?= e($company['rccm'] ?? '—') ?> · Siège : <?= e($company['head_office'] ?? '—') ?></div>
        </div>
        <h1 class="h6 text-center fw-bold mb-4">PROCÈS-VERBAL D'ASSEMBLÉE GÉNÉRALE <?= e($data['meeting_type']) ?><br>DU <?= e(date('d/m/Y', strtotime($data['meeting_date']))) ?></h1>
        <p>L'assemblée générale <?= e($data['meeting_type']) ?> de la société <?= e($company['name'] ?? '') ?> s'est tenue le <?= e(date('d/m/Y', strtotime($data['meeting_date']))) ?> à <?= e($data['location']) ?>, avec la participation des actionnaires ci-après désignés.</p>

        <p><strong>Ordre du jour :</strong></p>
        <p style="white-space:pre-line"><?= e($data['agenda']) ?></p>

        <p><strong>Résolutions :</strong></p>
        <p style="white-space:pre-line"><?= e($data['resolutions']) ?></p>

        <p><strong>Présence et droits de vote :</strong></p>
        <table class="table table-sm table-bordered w-auto">
          <thead><tr><th>Actionnaire</th><th class="text-end">Titres</th><th class="text-end">%</th></tr></thead>
          <tbody>
            <?php foreach ($holdings as $h): ?>
            <tr><td><?= e($h['shareholder']['name']) ?></td><td class="text-end"><?= shares($h['total']) ?></td><td class="text-end"><?= pct($h['percentage']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="small text-muted">Fait à <?= e($data['location']) ?>, le <?= e(date('d/m/Y', strtotime($data['meeting_date']))) ?>. Le présent procès-verbal est établi en application des dispositions de l'AUSCGIE.</p>
        <div style="height:60px"></div>
        <div class="border-top pt-1 small" style="width:220px">Le Président de séance</div>
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-dark" onclick="window.print()">🖨 Imprimer le PV</button>
    </div>
  </div>
</div>
