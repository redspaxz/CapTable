<?php use function App\{e, url}; ?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-0"><?= __('Settings') ?></h1>
        <div class="text-muted small"><?= e($company['name'] ?? '') ?> · RCCM <?= e($company['rccm'] ?? '—') ?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-translate me-2"></i><?= __('Default language') ?></div>
      <div class="card-body">
        <p class="text-muted small"><?= __('Language shown to users who have not picked one. Each user can switch language at any time from the menu bar.') ?></p>
        <form method="post" action="<?= url('/settings') ?>" class="d-flex flex-wrap align-items-center gap-3">
          <?= App\Core\Csrf::field() ?>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="default_language" id="lang-en" value="en" <?= $defaultLanguage === 'en' ? 'checked' : '' ?>>
            <label class="form-check-label" for="lang-en">🇬🇧 English</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="default_language" id="lang-fr" value="fr" <?= $defaultLanguage === 'fr' ? 'checked' : '' ?>>
            <label class="form-check-label" for="lang-fr">🇫🇷 Français</label>
          </div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i><?= __('Save') ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
