<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<h1 class="h3 mb-4">Dashboard Opérateur</h1>

<?php $principal = $gainsParOperateur['principal'] ?? ['total_gains' => 0, 'nombre_operations' => 0]; ?>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card text-bg-dark h-100"><div class="card-body"><div class="text-uppercase small opacity-75">Gains — notre opérateur</div><div class="fs-3 fw-bold"><?= number_format((float) $principal['total_gains'], 0, ',', ' ') ?> Ar</div><div class="small opacity-75"><?= (int) $principal['nombre_operations'] ?> opération(s)</div></div></div></div>
    <div class="col-12 col-md-8"><div class="card h-100"><div class="card-header">Gains — autres opérateurs</div><div class="card-body"><div class="row g-3">
        <?php foreach ($gainsParOperateur['externes'] ?? [] as $operateur): ?><div class="col-12 col-sm-6"><div class="border rounded p-3"><strong><?= esc($operateur['operateur'] ?: $operateur['prefixe']) ?></strong><div class="fs-5 fw-bold"><?= number_format((float) $operateur['total_gains'], 0, ',', ' ') ?> Ar</div><small class="text-muted"><?= (int) $operateur['nombre_operations'] ?> transfert(s)</small></div></div><?php endforeach; ?>
        <?php if (empty($gainsParOperateur['externes'])): ?><p class="text-muted mb-0">Aucun transfert vers un autre opérateur.</p><?php endif; ?>
    </div></div></div></div>
</div>

<div class="card mb-4"><div class="card-header">Commissions à régler aux autres opérateurs</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Opérateur</th><th>Préfixe</th><th class="text-end">Commission due</th><th class="text-end">Opérations</th></tr></thead><tbody>
    <?php foreach ($montantsAEnvoyer ?? [] as $operateur): ?><tr><td><?= esc($operateur['operateur'] ?: $operateur['prefixe']) ?></td><td><?= esc($operateur['prefixe']) ?></td><td class="text-end fw-bold"><?= number_format((float) $operateur['total_a_envoyer'], 0, ',', ' ') ?> Ar</td><td class="text-end"><?= (int) $operateur['nombre_operations'] ?></td></tr><?php endforeach; ?>
    <?php if (empty($montantsAEnvoyer)): ?><tr><td colspan="4" class="text-center text-muted">Aucune commission à régler.</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="row g-3 mb-4"><?php foreach ($gains ?? [] as $gain): ?><div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="text-uppercase small text-muted"><?= esc($gain['type_libelle']) ?></div><div class="fs-4 fw-bold"><?= number_format((float) $gain['total_frais'], 0, ',', ' ') ?> Ar</div><div class="small text-muted"><?= (int) $gain['nombre_operations'] ?> opération(s)</div></div></div></div><?php endforeach; ?></div>

<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><span>Situation des comptes clients</span><a href="<?= site_url('admin/comptes') ?>" class="btn btn-sm btn-outline-secondary">Voir tout</a></div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Téléphone</th><th>Nom</th><th class="text-end">Solde</th></tr></thead><tbody>
    <?php foreach (array_slice($comptes ?? [], 0, 10) as $compte): ?><tr><td><?= esc($compte['telephone']) ?></td><td><?= esc($compte['nom'] ?? '-') ?></td><td class="text-end"><?= number_format((float) $compte['solde'], 0, ',', ' ') ?> Ar</td></tr><?php endforeach; ?>
    <?php if (empty($comptes)): ?><tr><td colspan="3" class="text-center text-muted">Aucun compte client.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?= $this->endSection() ?>
