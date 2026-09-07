<?php use function App\{e, url, shares, money}; $t = $transfer; ?>
<div class="row justify-content-center my-4">
  <div class="col-lg-9">
    <div class="card bg-white shadow">
      <div class="card-body p-5">
        <h1 class="h5 text-center fw-bold mb-4">TRANSFER DEED OF SOCIAL RIGHTS</h1>
        <p class="text-center text-muted">Reference: <?= e($t['deed_reference']) ?> — Date: <?= e($t['transfer_date']) ?></p>

        <p><strong>BETWEEN THE UNDERSIGNED:</strong></p>
        <p>1. <strong><?= e($t['seller_name']) ?></strong>, ID document / registry no. <?= e($t['seller_id_number']) ?>, hereinafter referred to as "the Seller", on the one hand;</p>
        <p>2. <strong><?= e($t['buyer_name']) ?></strong>, ID document / registry no. <?= e($t['buyer_id_number']) ?>, hereinafter referred to as "the Buyer", on the other hand.</p>

        <p><strong>IT HAS BEEN AGREED AND DECIDED AS FOLLOWS:</strong></p>
        <p>Article 1 — Purpose. The Seller assigns and transfers to the Buyer, who accepts, <strong><?= shares((int) $t['quantity']) ?> shares of class <?= e($t['class_code']) ?></strong> (<?= e($t['class_name']) ?>, par value <?= money((int) $t['nominal_value']) ?> per share) of the company <?= e($company['name'] ?? '') ?> (RCCM <?= e($company['rccm'] ?? '—') ?>).</p>
        <p>Article 2 — Price. The transfer price, fully paid upon signature, is: ________________ XAF (to be completed on the original).</p>
        <p>Article 3 — Sale. The transfer is made under the conditions set out in the articles of association and the OHADA Uniform Act on commercial companies and GIE (AUSCGIE). The parties acknowledge having satisfied the pre-emption right provided for in the articles of association.</p>
        <p>Article 4 — Enjoyment. The Buyer shall enjoy the rights attached to the transferred securities from the signature of this deed and its recording in the share movement register.</p>

        <div class="d-flex justify-content-around mt-5">
          <div class="text-center"><div style="height:60px"></div><div class="border-top px-5 pt-1 small">The Seller</div></div>
          <div class="text-center"><div style="height:60px"></div><div class="border-top px-5 pt-1 small">The Buyer</div></div>
        </div>
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-dark" onclick="window.print()">🖨 Print deed</button>
      <a class="btn btn-outline-secondary" href="<?= url('/transfers') ?>">Back</a>
    </div>
  </div>
</div>
