<?php use App\Core\Auth; use function App\{e, url}; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'CapTable') ?> — T&Tech Consulting Group</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php if (Auth::check()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= url('/') ?>">📋 CapTable</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>">Tableau de bord</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/captable') ?>">Capital</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/shareholders') ?>">Actionnaires</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/classes') ?>">Catégories</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/issuances') ?>">Émissions</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/transfers') ?>">Cessions</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/register') ?>">Registre</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/documents') ?>">Documents</a></li>
      </ul>
      <span class="navbar-text me-3"><?= e(Auth::user()['name'] ?? '') ?> <span class="badge bg-secondary"><?= e(Auth::role() ?? '') ?></span></span>
      <form method="post" action="<?= url('/logout') ?>" class="d-inline"><?= App\Core\Csrf::field() ?>
        <button class="btn btn-outline-light btn-sm">Déconnexion</button>
      </form>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container-fluid py-4">
  <?php if (!empty($flash['success'])): ?><div class="alert alert-success"><?= e($flash['success']) ?></div><?php endif; ?>
  <?php if (!empty($flash['error'])): ?><div class="alert alert-danger"><?= e($flash['error']) ?></div><?php endif; ?>
  <?= $content ?? '' ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($charts)): ?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><?php endif; ?>
</body>
</html>
