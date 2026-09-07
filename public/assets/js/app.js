/* CapTable — client-side enhancements (vanilla JS, no dependencies beyond Bootstrap bundle) */
(function () {
  'use strict';

  var BASE = (document.body.dataset.baseurl || '/').replace(/\/+$/, '');
  var fmt = function (n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  };
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Active navigation link
  var here = location.pathname.replace(/\/+$/, '') || '/';
  document.querySelectorAll('.navbar-nav .nav-link').forEach(function (a) {
    var href = (a.getAttribute('href') || '').replace(/\/+$/, '') || '/';
    if (href === here) a.classList.add('active');
  });

  // ---- Dark / light theme (persisted, forced light for printing)
  var THEME_KEY = 'captable-theme';
  var savedTheme = null;
  try { savedTheme = localStorage.getItem(THEME_KEY); } catch (e) {}
  if (savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
  var toggle = document.getElementById('themeToggle');
  function paintToggleIcon() {
    if (!toggle) return;
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    toggle.innerHTML = '<i class="bi ' + (dark ? 'bi-sun' : 'bi-moon-stars') + '"></i>';
    toggle.title = dark ? 'Passer en mode clair' : 'Passer en mode sombre';
  }
  if (toggle) toggle.addEventListener('click', function () {
    var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
    paintToggleIcon();
  });
  paintToggleIcon();
  window.addEventListener('beforeprint', function () {
    document.documentElement.setAttribute('data-bs-theme', 'light');
  });
  window.addEventListener('afterprint', function () {
    if (savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
  });

  // ---- Flash messages: dismissible + auto-hide
  document.querySelectorAll('.alert').forEach(function (el) {
    el.classList.add('alert-dismissible', 'fade', 'show');
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-close';
    btn.setAttribute('data-bs-dismiss', 'alert');
    btn.setAttribute('aria-label', 'Fermer');
    el.appendChild(btn);
    setTimeout(function () {
      if (window.bootstrap && bootstrap.Alert) bootstrap.Alert.getOrCreateInstance(el).close();
    }, 6000);
  });

  // ---- Busy state on submitted forms
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      form.querySelectorAll('button.btn').forEach(function (b) {
        b.disabled = true;
        b.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>');
      });
    });
  });

  // ---- Bootstrap tooltips
  if (window.bootstrap && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }

  // ---- Animated KPI counters (respects reduced motion)
  document.querySelectorAll('[data-countup]').forEach(function (el) {
    var target = parseInt(el.getAttribute('data-countup'), 10);
    if (isNaN(target)) return;
    var suffix = el.getAttribute('data-suffix') || '';
    if (reduceMotion) { el.textContent = fmt(target) + suffix; return; }
    var duration = 800, startTs = null;
    function step(ts) {
      if (startTs === null) startTs = ts;
      var p = Math.min((ts - startTs) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(Math.round(target * eased)) + suffix;
      if (p < 1) requestAnimationFrame(step);
    }
    el.textContent = '0' + suffix;
    requestAnimationFrame(step);
  });

  // ---- Interactive tables: instant filter + click-to-sort columns
  document.querySelectorAll('table[data-enhance="table"]').forEach(function (table) {
    var toolbar = document.createElement('div');
    toolbar.className = 'd-flex flex-wrap justify-content-between align-items-center gap-2 mb-2';
    toolbar.innerHTML =
      '<div class="input-group input-group-sm" style="max-width:18rem;">' +
      '<span class="input-group-text"><i class="bi bi-search"></i></span>' +
      '<input type="search" class="form-control" placeholder="Filtrer…" aria-label="Filtrer le tableau">' +
      '</div><span class="text-muted small" aria-live="polite"></span>';
    table.parentNode.insertBefore(toolbar, table);

    var input = toolbar.querySelector('input');
    var countEl = toolbar.querySelector('span[aria-live]');
    var rows = Array.prototype.slice.call(table.tBodies[0].rows);

    function applyFilter() {
      var q = input.value.trim().toLowerCase();
      var shown = 0;
      rows.forEach(function (r) {
        var hit = !q || r.textContent.toLowerCase().indexOf(q) !== -1;
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      countEl.textContent = shown + ' / ' + rows.length + ' ligne(s)';
    }
    input.addEventListener('input', applyFilter);
    applyFilter();

    Array.prototype.forEach.call(table.querySelectorAll('thead th'), function (th, idx) {
      th.classList.add('tj-sortable');
      th.setAttribute('title', 'Trier par cette colonne');
      th.addEventListener('click', function () {
        var dir = th.getAttribute('data-dir') === 'asc' ? 'desc' : 'asc';
        Array.prototype.forEach.call(table.querySelectorAll('thead th'), function (h) { h.removeAttribute('data-dir'); });
        th.setAttribute('data-dir', dir);
        var tbody = table.tBodies[0];
        var sorted = rows.slice().sort(function (a, b) {
          var va = (a.cells[idx] ? a.cells[idx].textContent : '').trim();
          var vb = (b.cells[idx] ? b.cells[idx].textContent : '').trim();
          var na = va.replace(/\s/g, '').replace(',', '.');
          var nb = vb.replace(/\s/g, '').replace(',', '.');
          var cmp;
          if (na !== '' && nb !== '' && !isNaN(na) && !isNaN(nb)) {
            cmp = parseFloat(na) - parseFloat(nb);
          } else {
            cmp = va.localeCompare(vb, 'fr');
          }
          return dir === 'asc' ? cmp : -cmp;
        });
        sorted.forEach(function (r) { tbody.appendChild(r); });
      });
    });
  });

  // ---- Issuance form: live total value + authorized-shares guard
  var issuanceForm = document.getElementById('issuanceForm');
  if (issuanceForm) {
    var iClass = issuanceForm.querySelector('[name="share_class_id"]');
    var iQty = issuanceForm.querySelector('[name="quantity"]');
    var iOut = document.getElementById('issuanceSummary');
    var updIssuance = function () {
      if (!iOut || !iClass.selectedOptions[0]) return;
      var nominal = parseInt(iClass.selectedOptions[0].dataset.nominal || 0, 10);
      var remaining = parseInt(iClass.selectedOptions[0].dataset.remaining || 0, 10);
      var q = parseInt(iQty.value || 0, 10);
      var total = q * nominal;
      var over = q > remaining;
      iQty.setCustomValidity(over ? 'Dépasse les actions disponibles' : '');
      iOut.innerHTML = 'Valeur totale : <strong>' + fmt(total) + ' XAF</strong>' +
        (over ? ' — <span class="text-danger fw-semibold">dépasse les ' + fmt(remaining) + ' action(s) disponibles</span>'
              : ' · ' + fmt(Math.max(remaining - q, 0)) + ' action(s) resterai(en)t disponibles');
    };
    iClass.addEventListener('change', updIssuance);
    iQty.addEventListener('input', updIssuance);
    updIssuance();
  }

  // ---- Transfer form: seller holdings via AJAX + client-side guard
  var transferForm = document.getElementById('transferForm');
  if (transferForm) {
    var tSeller = transferForm.querySelector('[name="seller_id"]');
    var tClass = transferForm.querySelector('[name="share_class_id"]');
    var tQty = transferForm.querySelector('[name="quantity"]');
    var tHint = document.getElementById('holdingHint');
    var cache = {};
    var updTransfer = function () {
      var s = tSeller.value, c = tClass.value;
      if (!s || !c || !tHint) { if (tHint) tHint.textContent = ''; return; }
      if (!cache[s]) {
        cache[s] = fetch(BASE + '/api/holdings/' + s, { headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); });
      }
      cache[s].then(function (data) {
        var avail = (data.holdings && (data.holdings[c] || data.holdings[String(c)])) || 0;
        tHint.textContent = avail > 0
          ? 'Le cédant détient ' + fmt(avail) + ' action(s) dans cette catégorie.'
          : 'Le cédant ne détient aucune action dans cette catégorie.';
        tHint.className = 'form-text ' + (avail > 0 ? 'text-success' : 'text-danger');
        tQty.max = avail;
        tQty.setCustomValidity(parseInt(tQty.value || 0, 10) > avail ? 'Titres insuffisants' : '');
      });
    };
    tSeller.addEventListener('change', updTransfer);
    tClass.addEventListener('change', updTransfer);
    tQty.addEventListener('input', function () { tQty.setCustomValidity(''); });
    transferForm.addEventListener('submit', function (ev) {
      if (!tSeller.value || tSeller.value === transferForm.querySelector('[name="buyer_id"]').value) {
        ev.preventDefault();
        if (tHint) { tHint.textContent = 'Le cédant et le cessionnaire doivent être différents.'; tHint.className = 'form-text text-danger'; }
      }
    });
    updTransfer();
  }
})();
