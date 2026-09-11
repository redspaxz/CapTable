<?php use App\Core\Auth; use function App\{e, url, asset, __}; $locale = \App\Core\Lang::locale(); ?>
<!doctype html>
<html lang="<?= $locale === 'fr' ? 'fr' : 'en' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Share capital &amp; equity management for T&amp;Tech Consulting Group (OHADA law)">
<title><?= e($title ?? 'T&T') ?> · <?= e(\App\Core\Tenancy::current()['name'] ?? 'T&Tech Consulting Group') ?></title>
<link href="<?= asset('/assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset('/assets/vendor/bootstrap-icons.min.css') ?>" rel="stylesheet">
<link href="<?= asset('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body data-baseurl="<?= e(url('/')) ?>">
<?php if (Auth::check()): ?>
<header class="app-header sticky-top">
  <nav class="navbar navbar-expand-lg navbar-dark" aria-label="<?= __('Main navigation') ?>">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
        <span class="brand-mark" aria-hidden="true">T&amp;T</span>
        <?php $tenant = \App\Core\Tenancy::current(); ?>
        <span class="fw-semibold d-none d-sm-inline"><?= e($tenant['name'] ?? '') ?></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
              aria-controls="nav" aria-expanded="false" aria-label="<?= __('Toggle navigation') ?>">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto gap-lg-0">
          <li class="nav-item"><a class="nav-link" href="<?= url('/portal') ?>"><i class="bi bi-person-badge me-1"></i><?= __('My space') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>"><i class="bi bi-speedometer2 me-1"></i><?= __('Dashboard') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/captable') ?>"><i class="bi bi-pie-chart me-1"></i><?= __('Capital') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/waterfall') ?>"><i class="bi bi-water me-1"></i>Waterfall</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/options') ?>"><i class="bi bi-award me-1"></i><?= __('Options') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/shareholders') ?>"><i class="bi bi-people me-1"></i><?= __('Shareholders') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/classes') ?>"><i class="bi bi-layers me-1"></i><?= __('Share classes') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/issuances') ?>"><i class="bi bi-plus-square me-1"></i><?= __('Issuances') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/transfers') ?>"><i class="bi bi-arrow-left-right me-1"></i><?= __('Transfers') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/register') ?>"><i class="bi bi-journal-text me-1"></i><?= __('Register') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/compliance') ?>"><i class="bi bi-shield-check me-1"></i><?= __('Compliance') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/convertibles') ?>"><i class="bi bi-arrow-repeat me-1"></i><?= __('Convertibles') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/documents') ?>"><i class="bi bi-file-earmark-text me-1"></i><?= __('Documents') ?></a></li>
        </ul>
        <div class="nav-controls d-flex align-items-lg-center flex-column flex-lg-row flex-wrap gap-2">
          <?php if (Auth::isSuperAdmin()): ?>
          <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-building me-1"></i><?= e($tenant['name'] ?? __('Companies')) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php foreach (\App\Core\Tenancy::all() as $t): ?>
              <li><a class="dropdown-item <?= (int) $t['id'] === (\App\Core\Tenancy::id() ?? 0) ? 'active' : '' ?>" href="<?= url('/tenant/switch/' . (int) $t['id']) ?>"><?= e($t['name']) ?></a></li>
              <?php endforeach; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= url('/tenants') ?>"><i class="bi bi-gear me-1"></i><?= __('Manage companies') ?></a></li>
            </ul>
          </div>
          <?php endif; ?>
          <div class="btn-group btn-group-sm" role="group" aria-label="<?= __('Language') ?>">
            <a class="btn btn-outline-light <?= $locale === 'en' ? 'active' : '' ?>" href="<?= url('/lang/en') ?>">EN</a>
            <a class="btn btn-outline-light <?= $locale === 'fr' ? 'active' : '' ?>" href="<?= url('/lang/fr') ?>">FR</a>
          </div>
          <button id="themeToggle" type="button" class="btn btn-outline-light btn-sm" aria-label="<?= __('Toggle theme') ?>"></button>
          <?php if (Auth::canWrite()): ?>
          <a class="btn btn-outline-light btn-sm" href="<?= url('/settings') ?>" aria-label="<?= __('Settings') ?>"><i class="bi bi-gear"></i></a>
          <?php endif; ?>
          <span class="navbar-text d-flex align-items-center gap-2">
            <i class="bi bi-person-circle fs-5"></i>
            <span><?= e(Auth::user()['name'] ?? '') ?></span>
            <span class="badge text-bg-light text-uppercase"><?= e(Auth::role() ?? '') ?></span>
          </span>
          <form method="post" action="<?= url('/logout') ?>">
            <?= App\Core\Csrf::field() ?>
            <button class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i><?= __('Log out') ?></button>
          </form>
        </div>
      </div>
    </div>
  </nav>
</header>
<?php endif; ?>

<main class="app-main py-3">
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
    <?php $company = \App\company(); ?>
    <span><strong><?= e($company['name'] ?? (\App\Core\Tenancy::current()['name'] ?? '')) ?></strong> — RCCM <?= e($company['rccm'] ?? '—') ?> · <?= e($company['head_office'] ?? '') ?></span>
    <span><?= __('OHADA compliant (AUSCGIE art. 716)') ?></span>
  </div>
</footer>
<?php endif; ?>

<script src="<?= asset('/assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/assets/js/app.js') ?>" defer></script>
<?php if (!empty($charts)): ?><script src="<?= asset('/assets/vendor/chart.umd.min.js') ?>"></script><?php endif; ?>
</body>
</html>
