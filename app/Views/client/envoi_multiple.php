<?= $this->extend('layout/app') ?>
<?= $this->section('content') ?>

<div class="mm-form-card mx-auto">
  <div class="mm-form-header mm-form-header--transfert">
    <i class="bi bi-people"></i>
    <h1>Envoi multiple</h1>
  </div>

  <div class="mm-form-solde">
    Solde disponible : <strong><?= number_format($compte['solde'], 0, ',', ' ') ?> Ar</strong>
  </div>

  <form action="/client/envoi-multiple" method="post" id="multipleTransferForm" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label for="montant_total" class="form-label">Montant total à répartir</label>
      <div class="input-group mm-input-group">
        <input type="number" class="form-control" id="montant_total" name="montant_total" min="1" step="1" placeholder="0" required>
        <span class="input-group-text">Ar</span>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <label class="form-label mb-0">Destinataires</label>
      <button class="btn btn-sm mm-btn-outline" type="button" id="addDestinataireBtn"><i class="bi bi-plus-lg"></i> Ajouter</button>
    </div>
    <div id="destinatairesList">
      <div class="input-group mm-input-group mb-2 destinataire-field">
        <input type="tel" class="form-control multiple-telephone" name="telephones[]" placeholder="0331234567" inputmode="numeric" required>
        <button class="btn btn-outline-danger remove-destinataire" type="button" aria-label="Retirer"><i class="bi bi-trash"></i></button>
      </div>
      <div class="input-group mm-input-group mb-2 destinataire-field">
        <input type="tel" class="form-control multiple-telephone" name="telephones[]" placeholder="0372345678" inputmode="numeric" required>
        <button class="btn btn-outline-danger remove-destinataire" type="button" aria-label="Retirer"><i class="bi bi-trash"></i></button>
      </div>
    </div>

    <div class="mm-frais-preview mt-3" id="multiplePreview">
      <p class="mb-0 text-muted">Saisissez le montant et les destinataires pour afficher la répartition.</p>
    </div>

    <button type="submit" class="btn mm-btn-primary w-100 py-2 mt-3">
      <i class="bi bi-send-check me-1"></i> Confirmer l'envoi multiple
    </button>
    <a href="/client" class="btn mm-btn-outline w-100 py-2 mt-2">Annuler</a>
  </form>
</div>

<?= $this->endSection() ?>
