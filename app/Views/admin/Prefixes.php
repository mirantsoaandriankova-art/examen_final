<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="mb-4"><h1 class="h3 mb-1">Préfixes opérateurs</h1><p class="text-muted mb-0">Gérez les réseaux internes et les opérateurs externes.</p></div>
<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-4"><div class="card mm-admin-card"><div class="card-header">Ajouter un préfixe</div><div class="card-body">
        <form method="post" action="<?= site_url('admin/prefixes/store') ?>">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">Préfixe</label><input type="text" name="prefixe" class="form-control" maxlength="10" required placeholder="ex: 033"></div>
            <div class="mb-3"><label class="form-label">Description</label><input type="text" name="description" class="form-control" placeholder="ex: Opérateur A"></div>
            <div class="mb-3"><label class="form-label">Type d'opérateur</label><select name="est_operateur_principal" class="form-select js-operator-type"><option value="1">Notre opérateur</option><option value="0">Autre opérateur</option></select></div>
            <div class="mb-3 js-commission d-none"><label class="form-label">Commission (%)</label><input type="number" name="commission_pourcentage" class="form-control" value="0" min="0" step="0.01"></div>
            <div class="form-check mb-3"><input type="checkbox" name="actif" value="1" class="form-check-input" id="actifNew" checked><label class="form-check-label" for="actifNew">Actif</label></div>
            <button type="submit" class="btn btn-primary w-100">Ajouter le préfixe</button>
        </form>
    </div></div></div>
    <div class="col-12 col-lg-8"><div class="card mm-admin-card"><div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Liste des préfixes</span><span class="badge text-bg-light"><?= count($prefixes) ?> affiché(s)</span></div><div class="table-responsive"><table class="table mm-table align-middle">
        <thead><tr><th>Préfixe</th><th>Description</th><th>Type</th><th>Commission %</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
        <tbody><?php foreach ($prefixes as $prefixe): ?>
            <?php $formEditId = 'edit-prefixe-' . $prefixe['id']; $formDeleteId = 'delete-prefixe-' . $prefixe['id']; ?>
            <tr>
                <td><input form="<?= $formEditId ?>" type="text" name="prefixe" value="<?= esc($prefixe['prefixe']) ?>" class="form-control form-control-sm" maxlength="10" required></td>
                <td><input form="<?= $formEditId ?>" type="text" name="description" value="<?= esc($prefixe['description'] ?? '') ?>" class="form-control form-control-sm"></td>
                <td><select form="<?= $formEditId ?>" name="est_operateur_principal" class="form-select form-select-sm js-operator-type"><option value="1" <?= $prefixe['est_operateur_principal'] ? 'selected' : '' ?>>Notre opérateur</option><option value="0" <?= ! $prefixe['est_operateur_principal'] ? 'selected' : '' ?>>Autre opérateur</option></select></td>
                <td><input form="<?= $formEditId ?>" type="number" name="commission_pourcentage" value="<?= esc($prefixe['commission_pourcentage']) ?>" class="form-control form-control-sm js-commission-input" min="0" step="0.01" <?= $prefixe['est_operateur_principal'] ? 'disabled' : '' ?>></td>
                <td><div class="form-check form-switch"><input form="<?= $formEditId ?>" type="checkbox" name="actif" value="1" class="form-check-input" <?= $prefixe['actif'] ? 'checked' : '' ?>><label class="form-check-label small">Actif</label></div></td>
                <td class="text-end text-nowrap"><button form="<?= $formEditId ?>" type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button><button form="<?= $formDeleteId ?>" type="submit" class="btn btn-sm btn-outline-danger">Suppr.</button></td>
            </tr>
            <form id="<?= $formEditId ?>" method="post" action="<?= site_url('admin/prefixes/update/' . $prefixe['id']) ?>"><?= csrf_field() ?></form>
            <form id="<?= $formDeleteId ?>" method="post" action="<?= site_url('admin/prefixes/delete/' . $prefixe['id']) ?>" onsubmit="return confirm('Supprimer ce préfixe ?');"><?= csrf_field() ?></form>
        <?php endforeach; ?>
        <?php if (empty($prefixes)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun préfixe enregistré.</td></tr><?php endif; ?></tbody>
    </table></div><div class="mm-pagination"><?= $pager->links('prefixes', 'bootstrap_full') ?></div></div></div>
</div>
<script>
document.querySelectorAll('.js-operator-type').forEach((select) => {
    const updateCommission = () => {
        const commission = select.closest('form, tr').querySelector('.js-commission, .js-commission-input');
        if (commission) {
            commission.classList.toggle('d-none', select.value === '1');
            commission.disabled = select.value === '1';
        }
    };
    select.addEventListener('change', updateCommission);
    updateCommission();
});
</script>
<?= $this->endSection() ?>
