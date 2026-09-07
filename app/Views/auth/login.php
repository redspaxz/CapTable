<?php use function App\{e, url}; ?>
<div class="login-screen">
  <main class="login-card card p-2">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <span class="brand-mark mb-3" style="width:3.4rem;height:3.4rem;font-size:1.05rem;" aria-hidden="true">T&amp;T</span>
        <h1 class="h4 fw-bold mb-1">CapTable</h1>
        <p class="text-muted small mb-0">Share capital &amp; equity management</p>
        <p class="text-muted small">T&amp;Tech Consulting Group — OHADA law</p>
      </div>
      <form method="post" action="<?= url('/login') ?>">
        <?= App\Core\Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" id="email" class="form-control" required autofocus>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input type="password" name="password" id="password" class="form-control" required>
          </div>
        </div>
        <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
      </form>
    </div>
  </main>
</div>
