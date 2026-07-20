<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="mb-4"><h1 class="h3 mb-1">Barèmes de frais</h1><p class="text-muted mb-0">Configurez les frais appliqués à chaque tranche de montant.</p></div>
<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Onglets de filtre par type d'opération -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?= empty($typeSelected) ? 'active' : '' ?>" href="<?= site_url('admin/baremes') ?>">Tous</a>
    </li>
    <?php foreach ($types as $t): ?>
        <li class="nav-item">
            <a class="nav-link <?= $typeSelected === $t['code'] ? 'active' : '' ?>" href="<?= site_url('admin/baremes?type=' . $t['code']) ?>">
                <?= esc($t['libelle']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="row g-4">
    <!-- Formulaire d'ajout -->
    <div class="col-12 col-lg-4">
        <div class="card mm-admin-card">
            <div class="card-header">Ajouter une tranche</div>
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/baremes/store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Type d'opération</label>
                        <select name="type_operation_id" class="form-select" required>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= esc($t['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant min</label>
                        <input type="number" step="1" min="0" name="montant_min" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant max <span class="text-muted small">(laisser vide = illimité)</span></label>
                        <input type="number" step="1" min="0" name="montant_max" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Frais (Ar)</label>
                        <input type="number" step="1" min="0" name="frais" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste -->
    <div class="col-12 col-lg-8">
        <div class="card mm-admin-card">
            <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Tranches configurées</span><span class="badge text-bg-light"><?= count($baremes) ?> affichée(s)</span></div>
            <div class="table-responsive">
                <table class="table mm-table align-middle">
                    <thead>
                        <tr>
                            <?php if (empty($typeSelected)): ?>
                                <th>Type</th>
                            <?php endif; ?>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Frais</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($baremes as $b): ?>
                            <?php
                                $formEditId = 'edit-bareme-' . $b['id'];
                                $formDelId  = 'delete-bareme-' . $b['id'];
                            ?>
                            <tr>
                                <?php if (empty($typeSelected)): ?>
                                    <td><?= esc($b['type_libelle'] ?? '') ?></td>
                                <?php endif; ?>
                                <td style="min-width:110px">
                                    <input form="<?= $formEditId ?>" type="number" step="1" min="0" name="montant_min" value="<?= esc($b['montant_min']) ?>" class="form-control form-control-sm" required>
                                    <input form="<?= $formEditId ?>" type="hidden" name="type_operation_id" value="<?= $b['type_operation_id'] ?>">
                                </td>
                                <td style="min-width:110px">
                                    <input form="<?= $formEditId ?>" type="number" step="1" min="0" name="montant_max" value="<?= esc($b['montant_max']) ?>" class="form-control form-control-sm" placeholder="illimité">
                                </td>
                                <td style="min-width:100px">
                                    <input form="<?= $formEditId ?>" type="number" step="1" min="0" name="frais" value="<?= esc($b['frais']) ?>" class="form-control form-control-sm" required>
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    <button form="<?= $formEditId ?>" type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                                    <button form="<?= $formDelId ?>" type="submit" class="btn btn-sm btn-outline-danger">Suppr.</button>
                                </td>
                            </tr>
                            <form id="<?= $formEditId ?>" method="post" action="<?= site_url('admin/baremes/update/' . $b['id']) ?>"><?= csrf_field() ?></form>
                            <form id="<?= $formDelId ?>" method="post" action="<?= site_url('admin/baremes/delete/' . $b['id']) ?>" onsubmit="return confirm('Supprimer cette tranche ?');"><?= csrf_field() ?></form>
                        <?php endforeach; ?>
                        <?php if (empty($baremes)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune tranche configurée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mm-pagination"><?= $pager->links('baremes', 'bootstrap_full') ?></div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
