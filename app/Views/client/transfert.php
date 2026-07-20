<?= $this->extend('layout/app') ?>
<?= $this->section('content') ?>

<div class="mm-form-card mx-auto">
  <div class="mm-form-header mm-form-header--transfert">
    <i class="bi bi-arrow-left-right"></i>
    <h1>Transfert d'argent</h1>
  </div>

  <div class="mm-form-solde">
    Solde disponible : <strong><?= number_format($compte['solde'], 0, ',', ' ') ?> Ar</strong>
  </div>

  <form action="/client/transfert" method="post" id="operationForm" data-type="transfert" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
      <label for="telephone_destinataire" class="form-label">Numéro du destinataire</label>
      <div class="input-group mm-input-group">
        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
        <input
          type="tel"
          class="form-control"
          id="telephone_destinataire"
          name="telephone_destinataire"
          placeholder="0331234567"
          required
          inputmode="numeric"
        >
      </div>
      <div class="invalid-feedback" id="destinataireError">Numéro invalide.</div>
    </div>

    <div class="mb-3">
      <label for="montant" class="form-label">Montant à transférer</label>
      <div class="input-group mm-input-group">
        <input type="number" class="form-control" id="montant" name="montant" min="1" step="1" placeholder="0" required>
        <span class="input-group-text">Ar</span>
      </div>
      <div class="invalid-feedback" id="montantError">Montant invalide ou solde insuffisant.</div>
    </div>

    <div class="mm-frais-preview" id="fraisPreview">
      <div class="d-flex justify-content-between">
        <span>Frais (à votre charge)</span>
        <strong id="previewFrais">0 Ar</strong>
      </div>
      <div class="d-flex justify-content-between mm-frais-total">
        <span>Total débité (montant + frais)</span>
        <strong id="previewTotal">0 Ar</strong>
      </div>
    </div>

    <button type="submit" class="btn mm-btn-primary w-100 py-2 mt-3">
      <i class="bi bi-check2-circle me-1"></i> Confirmer le transfert
    </button>
    <a href="/client" class="btn mm-btn-outline w-100 py-2 mt-2">Annuler</a>
  </form>
</div>

<?= $this->endSection() ?>
