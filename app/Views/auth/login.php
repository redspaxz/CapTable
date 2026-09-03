<?php use function App\{e, url}; ?>
<div class="row justify-content-center mt-5">
  <div class="col-md-4">
    <div class="card shadow">
      <div class="card-body">
        <h4 class="card-title text-center mb-3">Connexion — CapTable</h4>
        <p class="text-center text-muted small">T&amp;Tech Consulting Group</p>
        <form method="post" action="<?= url('/login') ?>">
          <?= App\Core\Csrf::field() ?>
          <div class="mb-3">
            <label class="form-label">Adresse e-mail</label>
            <input type="email" name="email" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Se connecter</button>
        </form>
      </div>
    </div>
  </div>
</div>
