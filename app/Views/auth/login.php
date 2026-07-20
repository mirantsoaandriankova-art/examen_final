<?= $this->extend('layout/app') ?>
<?= $this->section('content') ?>

<div class="mm-auth-wrapper">
  <div class="mm-auth-card mx-auto">
    <div class="text-center mb-4">
      <div class="mm-auth-logo"><i class="bi bi-wallet2"></i></div>
      <h1 class="mm-auth-title">MobiMoney</h1>
      <p class="mm-auth-subtitle">Connectez-vous avec votre numéro de téléphone</p>
    </div>

    <form action="<?= site_url('login') ?>" method="post" id="loginForm" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="telephone" class="form-label">Numéro de téléphone</label>
        <div class="input-group mm-input-group">
          <span class="input-group-text"><i class="bi bi-telephone"></i></span>
          <input
            type="tel"
            class="form-control"
            id="telephone"
            name="telephone"
            placeholder="0331234567"
            required
            inputmode="numeric"
            autocomplete="tel"
            autofocus
          >
        </div>
        <div class="form-text">Formats acceptés : préfixe 033 ou 037 suivi de 7 chiffres.</div>
        <div class="invalid-feedback" id="telephoneError">Numéro invalide. Vérifiez le préfixe et la longueur.</div>
      </div>

      <button type="submit" class="btn mm-btn-primary w-100 py-2">
        <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
      </button>
    </form>

    <p class="text-center mm-auth-hint mt-4 mb-0">
      Pas d'inscription : votre compte doit déjà exister dans le système.
    </p>
  </div>
</div>

<?= $this->endSection() ?>
