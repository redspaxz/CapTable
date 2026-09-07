<?php use App\Core\Auth; use function App\{e, url}; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="CapTable — gestion du capital et des actions de T&amp;Tech Consulting Group (droit OHADA)">
<title><?= e($title ?? 'CapTable') ?> · T&amp;Tech Consulting Group</title>
<link href="<?= url('/assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= url('/assets/vendor/bootstrap-icons.min.css') ?>" rel="stylesheet">
<link href="<?= url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body data-baseurl="<?= e(url('/')) ?>">
<?php if (Auth::check()): ?>
<header class="app-header sticky-top">
  <nav class="navbar navbar-expand-lg navbar-dark" aria-label="Navigation principale">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
        <span class="brand-mark" aria-hidden="true">T&amp;T</span>
        <span class="fw-semibold d-none d-sm-inline">CapTable</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
              aria-controls="nav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto gap-lg-1">
          <li class="nav-item"><a class="nav-link" href="<?= url('/portal') ?>"><i class="bi bi-person-badge me-1"></i>Mon espace</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>"><i class="bi bi-speedometer2 me-1"></i>Tableau de bord</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/captable') ?>"><i class="bi bi-pie-chart me-1"></i>Capital</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/waterfall') ?>"><i class="bi bi-water me-1"></i>Waterfall</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/options') ?>"><i class="bi bi-award me-1"></i>Options</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/shareholders') ?>"><i class="bi bi-people me-1"></i>Actionnaires</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/classes') ?>"><i class="bi bi-layers me-1"></i>Catégories</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/issuances') ?>"><i class="bi bi-plus-square me-1"></i>Émissions</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/transfers') ?>"><i class="bi bi-arrow-left-right me-1"></i>Cessions</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/register') ?>"><i class="bi bi-journal-text me-1"></i>Registre</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('/documents') ?>"><i class="bi bi-file-earmark-text me-1"></i>Documents</a></li>
        </ul>
        <div class="d-flex align-items-lg-center flex-column flex-lg-row gap-2">
          <button id="themeToggle" type="button" class="btn btn-outline-light btn-sm" aria-label="Basculer le thème"></button>
          <span class="navbar-text d-flex align-items-center gap-2">
            <i class="bi bi-person-circle fs-5"></i>
            <span><?= e(Auth::user()['name'] ?? '') ?></span>
            <span class="badge text-bg-light text-uppercase"><?= e(Auth::role() ?? '') ?></span>
          </span>
          <form method="post" action="<?= url('/logout') ?>">
            <?= App\Core\Csrf::field() ?>
            <button class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</button>
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
    <span>Conforme au droit OHADA (AUSCGIE art. 716) · CapTable v1</span>
  </div>
</footer>
<?php endif; ?>

<script src="<?= url('/assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('/assets/js/app.js') ?>" defer></script>
<?php if (!empty($charts)): ?><script src="<?= url('/assets/vendor/chart.umd.min.js') ?>"></script><?php endif; ?>
</body>
</html>
