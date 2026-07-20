<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4">Historique des transactions</h1>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Téléphone</th>
                    <th>Type</th>
                    <th>Sens</th>
                    <th class="text-end">Montant</th>
                    <th class="text-end">Frais</th>
                    <th class="text-end">Solde après</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= esc($t['date_operation']) ?></td>
                        <td><?= esc($t['telephone'] ?? '-') ?> <span class="text-muted small"><?= esc($t['nom_client'] ?? '') ?></span></td>
                        <td><?= esc($t['type_libelle'] ?? $t['type_code'] ?? '-') ?></td>
                        <td>
                            <?php if ($t['sens'] === 'credit'): ?>
                                <span class="badge text-bg-success">Crédit</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Débit</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= number_format((float) $t['montant'], 0, ',', ' ') ?> Ar</td>
                        <td class="text-end"><?= number_format((float) $t['frais'], 0, ',', ' ') ?> Ar</td>
                        <td class="text-end"><?= number_format((float) $t['solde_apres'], 0, ',', ' ') ?> Ar</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Aucune transaction enregistrée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>