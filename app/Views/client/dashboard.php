<?= $this->extend('layout/app') ?>
<?= $this->section('content') ?>

<div class="mm-solde-card mb-4">
  <div class="mm-solde-label"><i class="bi bi-person-circle me-1"></i><?= esc($compte['nom'] ?? 'Client') ?></div>
  <div class="mm-solde-amount"><?= number_format($compte['solde'], 0, ',', ' ') ?> <span class="mm-solde-currency">Ar</span></div>
  <div class="mm-solde-phone"><i class="bi bi-telephone me-1"></i><?= esc($compte['telephone']) ?></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="/client/depot" class="mm-action-tile mm-action-tile--depot">
      <i class="bi bi-arrow-down-circle"></i>
      <span>Dépôt</span>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/client/retrait" class="mm-action-tile mm-action-tile--retrait">
      <i class="bi bi-arrow-up-circle"></i>
      <span>Retrait</span>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/client/transfert" class="mm-action-tile mm-action-tile--transfert">
      <i class="bi bi-arrow-left-right"></i>
      <span>Transfert</span>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/client/historique" class="mm-action-tile mm-action-tile--historique">
      <i class="bi bi-clock-history"></i>
      <span>Historique</span>
    </a>
  </div>
</div>

<div class="mm-panel">
  <div class="mm-panel-header d-flex justify-content-between align-items-center mb-2">
    <h2 class="mm-panel-title">Dernières transactions</h2>
    <a href="/client/historique" class="mm-link-more">Voir tout <i class="bi bi-arrow-right"></i></a>
  </div>

  <?php if (empty($transactions)): ?>
    <p class="mm-empty">Aucune transaction pour le moment.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table mm-table align-middle mb-0">
        <thead>
          <tr>
            <th>Opération</th>
            <th>Montant</th>
            <th>Frais</th>
            <th class="d-none d-md-table-cell">Solde après</th>
            <th class="d-none d-md-table-cell">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td>
                <span class="mm-badge-sens mm-badge-sens--<?= esc($tx['sens']) ?>">
                  <i class="bi bi-<?= $tx['sens'] === 'credit' ? 'plus' : 'dash' ?>-circle"></i>
                  <?= esc($tx['type_libelle'] ?? ucfirst($tx['type_code'] ?? $tx['sens'])) ?>
                </span>
              </td>
              <td><?= number_format($tx['montant'], 0, ',', ' ') ?> Ar</td>
              <td><?= $tx['frais'] > 0 ? number_format($tx['frais'], 0, ',', ' ') . ' Ar' : '—' ?></td>
              <td class="d-none d-md-table-cell"><?= number_format($tx['solde_apres'], 0, ',', ' ') ?> Ar</td>
              <td class="d-none d-md-table-cell"><?= date('d/m/Y H:i', strtotime($tx['date_operation'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
