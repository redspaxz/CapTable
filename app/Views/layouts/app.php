<?php use App\Core\Auth; use function App\{e, url, asset, __}; $locale = \App\Core\Lang::locale(); $tenant = \App\Core\Tenancy::current(); ?>
<!doctype html>
<html lang="<?= $locale === 'fr' ? 'fr' : 'en' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Share capital &amp; equity management (OHADA law)">
<title><?= e($title ?? 'T&T') ?> · <?= e($tenant['name'] ?? 'T&Tech Consulting Group') ?></title>
<link href="<?= asset('/assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset('/assets/vendor/bootstrap-icons.min.css') ?>" rel="stylesheet">
<link href="<?= asset('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body data-baseurl="<?= e(url('/')) ?>">
<?php if (Auth::check()): ?>
<?php
  $firstSeg = explode('/', trim(\App\Core\Request::path(), '/'))[0];
  $navItems = [
      ['/portal', 'portal', 'bi-person-badge', __('My space')],
      ['/', '', 'bi-speedometer2', __('Dashboard')],
      ['/captable', 'captable', 'bi-pie-chart', __('Capital')],
      ['/waterfall', 'waterfall', 'bi-water', 'Waterfall'],
      ['/options', 'options', 'bi-award', __('Options')],
      ['/shareholders', 'shareholders', 'bi-people', __('Shareholders')],
      ['/classes', 'classes', 'bi-layers', __('Share classes')],
      ['/issuances', 'issuances', 'bi-plus-square', __('Issuances')],
      ['/transfers', 'transfers', 'bi-arrow-left-right', __('Transfers')],
      ['/register', 'register', 'bi-journal-text', __('Register')],
      ['/compliance', 'compliance', 'bi-shield-check', __('Compliance')],
      ['/convertibles', 'convertibles', 'bi-arrow-repeat', __('Convertibles')],
      ['/documents', 'documents', 'bi-file-earmark-text', __('Documents')],
  ];
?>
<header class="app-header sticky-top">
  <nav class="navbar navbar-dark" aria-label="<?= __('Main navigation') ?>">
    <div class="container-fluid d-flex align-items-center gap-2 flex-nowrap">
      <a class="navbar-brand d-flex align-items-center gap-2 me-auto" href="<?= url('/') ?>">
        <span class="brand-mark" aria-hidden="true">T&amp;T</span>
        <span class="fw-semibold text-truncate d-none d-sm-inline" style="max-width:22vw"><?= e($tenant['name'] ?? '') ?></span>
      </a>
      <?php if (Auth::isSuperAdmin()): ?>
      <div class="dropdown d-none d-sm-block">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= __('Companies') ?>">
          <i class="bi bi-building"></i><span class="d-none d-lg-inline ms-1 text-truncate" style="max-width:14vw"><?= e($tenant['name'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php foreach (\App\Core\Tenancy::all() as $t): ?>
          <li><a class="dropdown-item <?= (int) $t['id'] === (\App\Core\Tenancy::id() ?? 0) ? 'active' : '' ?>" href="<?= url('/tenant/switch/' . (int) $t['id']) ?>"><i class="bi bi-building me-2"></i><?= e($t['name']) ?></a></li>
          <?php endforeach; ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= url('/tenants') ?>"><i class="bi bi-gear me-2"></i><?= __('Manage companies') ?></a></li>
        </ul>
      </div>
      <?php endif; ?>
      <div class="btn-group btn-group-sm" role="group" aria-label="<?= __('Language') ?>">
        <a class="btn btn-outline-light <?= $locale === 'en' ? 'active' : '' ?>" href="<?= url('/lang/en') ?>">EN</a>
        <a class="btn btn-outline-light <?= $locale === 'fr' ? 'active' : '' ?>" href="<?= url('/lang/fr') ?>">FR</a>
      </div>
      <button id="themeToggle" type="button" class="btn btn-outline-light btn-sm" aria-label="<?= __('Toggle theme') ?>"></button>
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle fs-6"></i>
          <span class="d-none d-md-inline text-truncate" style="max-width:12vw"><?= e(Auth::user()['name'] ?? '') ?></span>
          <span class="badge text-bg-light text-uppercase d-none d-lg-inline"><?= e(Auth::role() ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li><h6 class="dropdown-header">
            <i class="bi bi-person-circle me-1"></i><?= e(Auth::user()['name'] ?? '') ?>
            <span class="d-block small text-muted text-uppercase"><?= e(Auth::role() ?? '') ?></span>
          </h6></li>
          <?php if (Auth::canWrite()): ?>
          <li><a class="dropdown-item" href="<?= url('/settings') ?>"><i class="bi bi-gear me-2"></i><?= __('Settings') ?></a></li>
          <li><hr class="dropdown-divider"></li>
          <?php endif; ?>
          <li>
            <form method="post" action="<?= url('/logout') ?>" class="m-0">
              <?= App\Core\Csrf::field() ?>
              <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i><?= __('Log out') ?></button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <nav class="app-subnav" aria-label="<?= __('Sections') ?>">
    <div class="container-fluid">
      <div class="nav-scroll">
        <?php foreach ($navItems as [$path, $seg, $icon, $label]): ?>
        <a href="<?= url($path) ?>" class="<?= $firstSeg === $seg ? 'active' : '' ?>"><i class="bi <?= $icon ?> me-1"></i><?= e($label) ?></a>
        <?php endforeach; ?>
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
<?php $company = \App\company(); ?>
<footer class="app-footer mt-auto">
  <div class="container-fluid py-2 small text-muted d-flex flex-column flex-md-row justify-content-between gap-1">
    <span class="text-truncate"><strong><?= e($company['name'] ?? ($tenant['name'] ?? '')) ?></strong> — RCCM <?= e($company['rccm'] ?? '—') ?> · <?= e($company['head_office'] ?? '') ?></span>
    <span class="text-nowrap"><?= __('OHADA compliant (AUSCGIE art. 716)') ?></span>
  </div>
</footer>
<?php endif; ?>

<script src="<?= asset('/assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/assets/js/app.js') ?>" defer></script>
<?php if (!empty($charts)): ?><script src="<?= asset('/assets/vendor/chart.umd.min.js') ?>"></script><?php endif; ?>
</body>
</html>
