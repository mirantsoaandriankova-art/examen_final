<?= $this->extend('layout/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="mm-panel-title" style="font-size:1.25rem;">
    <i class="bi bi-clock-history me-2"></i>Historique des transactions
  </h1>
  <a href="/client" class="mm-link-more"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<div class="mm-panel">
  <?php if (empty($transactions)): ?>
    <p class="mm-empty">Aucune transaction enregistrée pour ce compte.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table mm-table align-middle mb-0">
        <thead>
          <tr>
            <th>Opération</th>
            <th>Sens</th>
            <th>Montant</th>
            <th>Frais</th>
            <th>Solde après</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td><?= esc($tx['type_libelle'] ?? ucfirst($tx['type_code'] ?? '')) ?></td>
              <td>
                <span class="mm-badge-sens mm-badge-sens--<?= esc($tx['sens']) ?>">
                  <i class="bi bi-<?= $tx['sens'] === 'credit' ? 'plus' : 'dash' ?>-circle"></i>
                  <?= $tx['sens'] === 'credit' ? 'Crédit' : 'Débit' ?>
                </span>
              </td>
              <td><?= number_format($tx['montant'], 0, ',', ' ') ?> Ar</td>
              <td><?= $tx['frais'] > 0 ? number_format($tx['frais'], 0, ',', ' ') . ' Ar' : '—' ?></td>
              <td><?= number_format($tx['solde_apres'], 0, ',', ' ') ?> Ar</td>
              <td><?= date('d/m/Y H:i', strtotime($tx['date_operation'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
