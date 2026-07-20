<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h3 mb-1">Comptes clients</h1><p class="text-muted mb-0">Soldes et informations des clients.</p></div>
</div>

<div class="card mm-admin-card">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Liste des comptes</span><span class="badge text-bg-light"><?= count($comptes) ?> affiché(s)</span></div>
    <div class="table-responsive"><table class="table mm-table align-middle"><thead><tr><th>Téléphone</th><th>Nom</th><th class="text-end">Solde</th><th>Créé le</th></tr></thead><tbody>
        <?php foreach ($comptes as $compte): ?><tr><td class="fw-semibold"><?= esc($compte['telephone']) ?></td><td><?= esc($compte['nom'] ?: '—') ?></td><td class="text-end fw-semibold text-nowrap"><?= number_format((float) $compte['solde'], 0, ',', ' ') ?> Ar</td><td class="text-muted text-nowrap"><?= esc($compte['date_creation'] ?? '—') ?></td></tr><?php endforeach; ?>
        <?php if (empty($comptes)): ?><tr><td colspan="4" class="text-center text-muted py-4">Aucun compte client.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="mm-pagination"><?= $pager->links('comptes', 'bootstrap_full') ?></div>
</div>
<?= $this->endSection() ?>
