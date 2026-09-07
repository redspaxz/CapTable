<?php use function App\{e, shares}; ?>
<div class="row justify-content-center my-4">
  <div class="col-lg-8">
    <div class="card certificate border-dark shadow">
      <div class="card-body p-5">
        <div class="text-center border-bottom pb-3 mb-4">
          <div class="text-uppercase fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></div>
          <div class="small">Public Limited Company with Board of Directors — OHADA law</div>
          <div class="small">RCCM <?= e($company['rccm'] ?? '—') ?> · NIU <?= e($company['niu'] ?? '—') ?> · Registered office: <?= e($company['head_office'] ?? '—') ?></div>
        </div>
        <h1 class="h5 text-center fw-bold mb-4">SHARE CERTIFICATE</h1>
        <p class="text-center lead">Certificate No. <strong><?= e($cert['certificate_number']) ?></strong></p>
        <p>The company certifies that <strong><?= e($cert['shareholder_name']) ?></strong>
          (<?= $cert['shareholder_type'] === 'corporate' ? 'corporate entity' : 'individual' ?>,
          ID document: <?= e($cert['id_number']) ?>) is the owner of
          <strong><?= shares((int) $cert['quantity']) ?> <?= e($cert['code']) ?> shares</strong>
          (<?= e($cert['class_name']) ?>) of the unit par value recorded in the share capital.</p>
        <p class="small text-muted">This certificate is issued pursuant to the provisions of the Uniform Act on commercial companies and GIE (AUSCGIE). It must be returned to the company upon transfer of the rights it represents.</p>
        <div class="d-flex justify-content-between mt-5">
          <div>Issued on <?= e($cert['issue_date']) ?></div>
          <div class="text-center">
            <div style="height: 60px"></div>
            <div class="border-top px-5 pt-1 small">The Managing Director</div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-dark" onclick="window.print()">🖨 Print certificate</button>
    </div>
  </div>
</div>
