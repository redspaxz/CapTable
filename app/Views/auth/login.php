<?php use function App\{e, url, __}; $locale = \App\Core\Lang::locale(); ?>
<div class="login-screen">
  <div class="position-absolute top-0 end-0 p-3">
    <div class="btn-group btn-group-sm" role="group" aria-label="<?= __('Language') ?>">
      <a class="btn btn-outline-light <?= $locale === 'en' ? 'active' : '' ?>" href="<?= url('/lang/en') ?>">EN</a>
      <a class="btn btn-outline-light <?= $locale === 'fr' ? 'active' : '' ?>" href="<?= url('/lang/fr') ?>">FR</a>
    </div>
  </div>
  <main class="login-card card p-2">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <span class="brand-mark mb-3" style="width:3.4rem;height:3.4rem;font-size:1.15rem;" aria-hidden="true">SX</span>
        <h1 class="h4 fw-bold mb-1 brand-name">STOCKBASE-X</h1>
        <p class="text-muted small mb-0"><?= __('Share capital & equity management') ?></p>
        <p class="text-muted small"><?= __('T&Tech Consulting Group — OHADA law') ?></p>
      </div>
      <form method="post" action="<?= url('/login') ?>">
        <?= App\Core\Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label" for="email"><?= __('Email address') ?></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" id="email" class="form-control" required autofocus>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password"><?= __('Password') ?></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input type="password" name="password" id="password" class="form-control" required>
          </div>
        </div>
        <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i><?= __('Sign in') ?></button>
      </form>
    </div>
  </main>
</div>
