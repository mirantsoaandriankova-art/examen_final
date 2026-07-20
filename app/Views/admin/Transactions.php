<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 mb-1">Historique des transactions</h1><p class="text-muted mb-0">Les 15 mouvements les plus récents par page.</p></div></div>

<div class="card mm-admin-card">
    <div class="card-header"><span class="fw-semibold">Mouvements enregistrés</span></div>
    <div class="table-responsive"><table class="table mm-table align-middle"><thead><tr><th>Date</th><th>Client</th><th>Type</th><th>Sens</th><th class="text-end">Montant</th><th class="text-end">Frais</th><th class="text-end">Solde après</th></tr></thead><tbody>
        <?php foreach ($transactions as $transaction): ?><tr><td class="text-nowrap text-muted"><?= esc($transaction['date_operation']) ?></td><td><div class="fw-semibold"><?= esc($transaction['telephone'] ?? '—') ?></div><small class="text-muted"><?= esc($transaction['nom_client'] ?? '') ?></small></td><td><?= esc($transaction['type_libelle'] ?? $transaction['type_code'] ?? '—') ?></td><td><span class="badge <?= $transaction['sens'] === 'credit' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $transaction['sens'] === 'credit' ? 'Crédit' : 'Débit' ?></span></td><td class="text-end text-nowrap fw-semibold"><?= number_format((float) $transaction['montant'], 0, ',', ' ') ?> Ar</td><td class="text-end text-nowrap"><?= number_format((float) $transaction['frais'], 0, ',', ' ') ?> Ar</td><td class="text-end text-nowrap"><?= number_format((float) $transaction['solde_apres'], 0, ',', ' ') ?> Ar</td></tr><?php endforeach; ?>
        <?php if (empty($transactions)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune transaction enregistrée.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="mm-pagination"><?= $pager->links('transactions', 'bootstrap_full') ?></div>
</div>
<?= $this->endSection() ?>
