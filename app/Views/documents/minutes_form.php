<?php use function App\{e, url, shares, pct}; ?>
<h1 class="h4 mb-3">Draft general meeting minutes</h1>
<div class="card bg-white shadow-sm"><div class="card-body">
<form method="post" action="<?= url('/documents/minutes') ?>">
  <?= App\Core\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Meeting type *</label>
      <select name="meeting_type" id="meeting_type" class="form-select">
        <option value="AGE">EGM (extraordinary)</option>
        <option value="AGO">AGM (ordinary)</option>
        <option value="AGC">Constitutive AGM</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Resolution template</label>
      <select id="template" class="form-select">
        <option value="">— Free —</option>
        <option value="capital_increase">Capital increase</option>
        <option value="buyback">Share buyback / cancellation</option>
        <option value="conversion">OCA / BSA conversion</option>
        <option value="transfer_approval">Transfer approval (consent)</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date *</label>
      <input type="date" name="meeting_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Location *</label>
      <input name="location" class="form-control" value="Registered office" required>
    </div>
    <div class="col-12">
      <label class="form-label">Agenda *</label>
      <textarea name="agenda" class="form-control" rows="3" required></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Resolutions adopted *</label>
      <textarea name="resolutions" class="form-control" rows="5" required></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Generate minutes</button>
    <a href="<?= url('/documents') ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
</div></div>

<h2 class="h6 mt-4">Expected attendance (current breakdown)</h2>
<table class="table table-sm bg-white shadow-sm w-auto">
  <thead><tr><th>Shareholder</th><th class="text-end">Shares</th><th class="text-end">% — voting rights</th></tr></thead>
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
      agenda: "1. Board report on the proposed capital increase.\n2. Increase of the share capital by issuing new shares.\n3. Corresponding amendment of the articles of association.",
      resolutions: "The meeting, acting under the quorum and majority conditions for extraordinary general meetings, having read the board report and the statutory auditor's report:\n\nFIRST RESOLUTION — The meeting resolves to increase the share capital by [AMOUNT] XAF, through the issuance of [NUMBER] shares of [VALUE] XAF each, with preferential subscription rights for existing shareholders.\n\nSECOND RESOLUTION — The articles of association are amended accordingly: article [X] is amended to bring the capital to [NEW CAPITAL] XAF.\n\nTHIRD RESOLUTION — Full powers are granted to the holder of an original to complete the filing formalities with the court registry and the RCCM publication."
    },
    buyback: {
      agenda: "1. Buyback by the company of its own shares.\n2. Cancellation of the repurchased shares and corresponding capital reduction.",
      resolutions: "FIRST RESOLUTION — The meeting authorizes the company to buy back [NUMBER] shares of class [CODE], within the limits of AUSCGIE.\n\nSECOND RESOLUTION — The repurchased shares will be cancelled and the capital reduced by [AMOUNT] XAF; the articles of association are amended accordingly."
    },
    conversion: {
      agenda: "1. Conversion of convertible bonds into shares / exercise of subscription warrants.\n2. Corresponding capital increase.",
      resolutions: "FIRST RESOLUTION — The meeting records the conversion of [NUMBER] convertible bonds (reference [REF]) into [NUMBER] new shares and resolves the corresponding capital increase of [AMOUNT] XAF.\n\nSECOND RESOLUTION — The articles of association are amended accordingly and the RCCM formalities will be completed by the holder of an original."
    },
    transfer_approval: {
      agenda: "1. Consent request for a transfer of [NUMBER] shares of class [CODE] by [SELLER] to [BUYER].\n2. Shareholders' pre-emption right.",
      resolutions: "FIRST RESOLUTION — The meeting, ruling on the consent request made pursuant to the articles of association, approves [BUYER] as transferee of [NUMBER] shares.\n\nSECOND RESOLUTION — The shareholders are notified of their pre-emption right within thirty days; failing exercise thereof, the transfer will be completed on the notified terms."
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
