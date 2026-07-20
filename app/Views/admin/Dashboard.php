<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4">Dashboard Opérateur</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<!-- Cards : gains par type d'opération -->
<div class="row g-3 mb-4">
    <?php
        $totalGains = 0;
        foreach ($gains as $g) {
            $totalGains += (float) $g['total_frais'];
        }
        $totalCommissionsExternes = 0;
        foreach ($situationOperateurs as $operateur) {
            $totalCommissionsExternes += (float) $operateur['total_commission'];
        }
    ?>
    <div class="col-12 col-md-3">
        <div class="card text-bg-dark h-100">
            <div class="card-body">
                <div class="text-uppercase small opacity-75">Gains notre opérateur</div>
                <div class="fs-3 fw-bold"><?= number_format($totalGains, 0, ',', ' ') ?> Ar</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="card text-bg-warning h-100">
            <div class="card-body">
                <div class="text-uppercase small opacity-75">Commissions autres opérateurs</div>
                <div class="fs-3 fw-bold"><?= number_format($totalCommissionsExternes, 0, ',', ' ') ?> Ar</div>
            </div>
        </div>
    </div>

    <?php foreach ($gains as $g): ?>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-uppercase small text-muted"><?= esc($g['type_libelle']) ?></div>
                    <div class="fs-4 fw-bold"><?= number_format((float) $g['total_frais'], 0, ',', ' ') ?> Ar</div>
                    <div class="small text-muted"><?= (int) $g['nombre_operations'] ?> opération(s)</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-header">Montants à régulariser avec les autres opérateurs</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead><tr><th>Préfixe</th><th>Opérateur</th><th>Commission</th><th class="text-end">Montant à envoyer</th><th class="text-end">Transferts</th></tr></thead>
            <tbody>
                <?php foreach ($situationOperateurs as $operateur): ?>
                    <tr>
                        <td><?= esc($operateur['prefixe']) ?></td>
                        <td><?= esc($operateur['description']) ?></td>
                        <td><?= number_format((float) $operateur['commission_pourcentage'], 2, ',', ' ') ?> %</td>
                        <td class="text-end"><?= number_format((float) $operateur['total_commission'], 0, ',', ' ') ?> Ar</td>
                        <td class="text-end"><?= (int) $operateur['nombre_transferts'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($situationOperateurs)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Aucun transfert vers un autre opérateur.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tableau des comptes clients -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Situation des comptes clients</span>
        <a href="<?= site_url('admin/comptes') ?>" class="btn btn-sm btn-outline-secondary">Voir tout</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Téléphone</th>
                    <th>Nom</th>
                    <th class="text-end">Solde</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($comptes, 0, 10) as $c): ?>
                    <tr>
                        <td><?= esc($c['telephone']) ?></td>
                        <td><?= esc($c['nom'] ?? '-') ?></td>
                        <td class="text-end"><?= number_format((float) $c['solde'], 0, ',', ' ') ?> Ar</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($comptes)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Aucun compte client.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
