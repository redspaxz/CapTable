<?php use App\Core\Auth; use function App\{e, url, asset}; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="CapTable — share capital &amp; equity management for T&amp;Tech Consulting Group (OHADA law)">
<title><?= e($title ?? 'CapTable') ?> · T&amp;Tech Consulting Group</title>
<link href="<?= asset('/assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset('/assets/vendor/bootstrap-icons.min.css') ?>" rel="stylesheet">
<link href="<?= asset('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body data-baseurl="<?= e(url('/')) ?>">
<?php if (Auth::check()): ?>
<header class="app-header sticky-top">
  <nav class="navbar navbar-expand-lg navbar-dark" aria-label="Main navigation">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
        <span class="brand-mark" aria-hidden="true">T&amp;T</span>
        <span class="fw-semibold d-none d-sm-inline">CapTable</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
              aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto gap-lg-1">
          <li class="nav-item"><a class="nav-link" href="<?= url('/portal') ?>"><i class="bi bi-person-badge me-1"></i>My space</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/captable') ?>"><i class="bi bi-pie-chart me-1"></i>Capital</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/waterfall') ?>"><i class="bi bi-water me-1"></i>Waterfall</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/options') ?>"><i class="bi bi-award me-1"></i>Options</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/shareholders') ?>"><i class="bi bi-people me-1"></i>Shareholders</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/classes') ?>"><i class="bi bi-layers me-1"></i>Share classes</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/issuances') ?>"><i class="bi bi-plus-square me-1"></i>Issuances</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/transfers') ?>"><i class="bi bi-arrow-left-right me-1"></i>Transfers</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/register') ?>"><i class="bi bi-journal-text me-1"></i>Register</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/compliance') ?>"><i class="bi bi-shield-check me-1"></i>Compliance</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/convertibles') ?>"><i class="bi bi-arrow-repeat me-1"></i>Convertibles</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/documents') ?>"><i class="bi bi-file-earmark-text me-1"></i>Documents</a></li>
        </ul>
        <div class="d-flex align-items-lg-center flex-column flex-lg-row gap-2">
          <button id="themeToggle" type="button" class="btn btn-outline-light btn-sm" aria-label="Toggle theme"></button>
          <span class="navbar-text d-flex align-items-center gap-2">
            <i class="bi bi-person-circle fs-5"></i>
            <span><?= e(Auth::user()['name'] ?? '') ?></span>
            <span class="badge text-bg-light text-uppercase"><?= e(Auth::role() ?? '') ?></span>
          </span>
          <form method="post" action="<?= url('/logout') ?>">
            <?= App\Core\Csrf::field() ?>
            <button class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Log out</button>
          </form>
        </div>
      </div>
    </div>
  </nav>
</header>
<?php endif; ?>

<main class="app-main py-4">
  <div class="container-fluid">
    <?php if (!empty($flash['success'])): ?>
      <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i><span><?= e($flash['success']) ?></span>
      </div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i><span><?= e($flash['error']) ?></span>
      </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </div>
</main>

<?php if (Auth::check()): ?>
<footer class="app-footer mt-auto">
  <div class="container-fluid py-3 small text-muted d-flex flex-column flex-md-row justify-content-between gap-1">
    <span><strong>T&amp;Tech Consulting Group</strong> — RCCM <?= e(\App\company()['rccm'] ?? '—') ?> · <?= e(\App\company()['head_office'] ?? '') ?></span>
    <span>OHADA compliant (AUSCGIE art. 716) · CapTable v1</span>
  </div>
</footer>
<?php endif; ?>

<script src="<?= asset('/assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/assets/js/app.js') ?>" defer></script>
<?php if (!empty($charts)): ?><script src="<?= asset('/assets/vendor/chart.umd.min.js') ?>"></script><?php endif; ?>
</body>
</html>
