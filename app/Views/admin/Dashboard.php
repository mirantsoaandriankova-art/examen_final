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
    ?>
    <div class="col-12 col-md-3">
        <div class="card text-bg-dark h-100">
            <div class="card-body">
                <div class="text-uppercase small opacity-75">Total des gains</div>
                <div class="fs-3 fw-bold"><?= number_format($totalGains, 0, ',', ' ') ?> Ar</div>
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