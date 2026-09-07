<?php use function App\{e, shares, pct}; ?>
<div class="row justify-content-center my-4">
  <div class="col-lg-9">
    <div class="card bg-white shadow">
      <div class="card-body p-5">
        <div class="text-center border-bottom pb-3 mb-4">
          <div class="text-uppercase fw-bold"><?= e($company['name'] ?? 'T&Tech Consulting Group') ?></div>
          <div class="small">RCCM <?= e($company['rccm'] ?? '—') ?> · Registered office: <?= e($company['head_office'] ?? '—') ?></div>
        </div>
        <h1 class="h6 text-center fw-bold mb-4">MINUTES OF THE GENERAL MEETING <?= e($data['meeting_type']) ?><br>OF <?= e(date('d/m/Y', strtotime($data['meeting_date']))) ?></h1>
        <p>The <?= e($data['meeting_type']) ?> general meeting of <?= e($company['name'] ?? '') ?> was held on <?= e(date('d/m/Y', strtotime($data['meeting_date']))) ?> at <?= e($data['location']) ?>, with the participation of the shareholders named below.</p>

        <p><strong>Agenda:</strong></p>
        <p style="white-space:pre-line"><?= e($data['agenda']) ?></p>

        <p><strong>Resolutions:</strong></p>
        <p style="white-space:pre-line"><?= e($data['resolutions']) ?></p>

        <p><strong>Attendance and voting rights:</strong></p>
        <table class="table table-sm table-bordered w-auto">
          <thead><tr><th>Shareholder</th><th class="text-end">Shares</th><th class="text-end">%</th></tr></thead>
          <tbody>
            <?php foreach ($holdings as $h): ?>
            <tr><td><?= e($h['shareholder']['name']) ?></td><td class="text-end"><?= shares($h['total']) ?></td><td class="text-end"><?= pct($h['percentage']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="small text-muted">Done at <?= e($data['location']) ?> on <?= e(date('d/m/Y', strtotime($data['meeting_date']))) ?>. These minutes are drawn up pursuant to the provisions of AUSCGIE.</p>
        <div style="height:60px"></div>
        <div class="border-top pt-1 small" style="width:220px">The Chair of the Meeting</div>
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-dark" onclick="window.print()">🖨 Print minutes</button>
    </div>
  </div>
</div>
