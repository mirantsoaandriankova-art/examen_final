<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4">Dashboard Opérateur</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<!-- ==================== GAINS V2 ==================== -->
<div class="row g-3 mb-4">

    <!-- Notre Opérateur Principal -->
    <?php $principal = $gainsParOperateur['principal'] ?? []; ?>
    <div class="col-12 col-md-4">
        <div class="card text-bg-dark h-100">
            <div class="card-body">
                <div class="text-uppercase small opacity-75">Notre Réseau (Principal)</div>
                <div class="fs-3 fw-bold">
                    <?= number_format($principal['total_gains'] ?? 0, 0, ',', ' ') ?> Ar
                </div>
                <div class="small text-muted">
                    <?= (int)($principal['nombre_operations'] ?? 0) ?> opération(s)
                </div>
            </div>
        </div>
    </div>

    <!-- Autres Opérateurs -->
    <div class="col-12 col-md-8">
        <div class="card h-100">
            <div class="card-header">Gains - Autres Opérateurs</div>
            <div class="card-body">
                <?php if (empty($gainsParOperateur['externes'])): ?>
                    <p class="text-muted">Aucun transfert vers d'autres opérateurs pour le moment.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($gainsParOperateur['externes'] as $ext): ?>
                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <strong><?= esc($ext['operateur']) ?> (<?= esc($ext['prefixe']) ?>)</strong><br>
                                    <span class="fs-5 fw-bold text-success">
                                        <?= number_format((float)($ext['total_gains'] ?? 0), 0, ',', ' ') ?> Ar
                                    </span>
                                    <small class="text-muted d-block">
                                        <?= (int)($ext['nombre_operations'] ?? 0) ?> transfert(s)
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MONTANTS À ENVOYER ==================== -->
<?php if (!empty($montantsAEnvoyer)): ?>
<div class="card mb-4">
    <div class="card-header">Montants à Régler aux Autres Opérateurs</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Opérateur</th>
                    <th>Préfixe</th>
                    <th class="text-end">Montant Total à Envoyer</th>
                    <th class="text-center">Nombre d'Opérations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($montantsAEnvoyer as $m): ?>
                <tr>
                    <td><?= esc($m['operateur']) ?></td>
                    <td><strong><?= esc($m['prefixe']) ?></strong></td>
                    <td class="text-end fw-bold"><?= number_format((float)$m['total_a_envoyer'], 0, ',', ' ') ?> Ar</td>
                    <td class="text-center"><?= (int)$m['nombre_operations'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ==================== TABLEAU DES COMPTES CLIENTS (inchangé) ==================== -->
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
                <?php foreach (array_slice($comptes ?? [], 0, 10) as $c): ?>
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