<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4">Préfixes opérateurs</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- Formulaire d'ajout -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">Ajouter un préfixe</div>
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/prefixes/store') ?>">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Préfixe</label>
                        <input type="text" name="prefixe" class="form-control" maxlength="10" required placeholder="ex: 033">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="ex: Opérateur A">
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="est_operateur_principal" value="1" class="form-check-input" id="principalNew" checked>
                        <label class="form-check-label" for="principalNew">Notre opérateur</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Commission externe (%)</label>
                        <input type="number" name="commission_pourcentage" class="form-control" min="0" step="0.01" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type d'opérateur</label>
                        <select name="est_operateur_principal" id="est_principal" class="form-select" onchange="toggleCommissionField()">
                            <option value="1">Notre Opérateur (Principal)</option>
                            <option value="0">Autre Opérateur</option>
                        </select>
                    </div>

                    <div class="mb-3" id="commission_group">
                        <label class="form-label">Commission (%)</label>
                        <input type="number" name="commission_pourcentage" id="commission_pourcentage" 
                               class="form-control" value="0" step="0.01" min="0" placeholder="ex: 10.00">
                        <small class="text-muted">Appliquée uniquement sur les transferts sortants</small>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="actif" value="1" class="form-check-input" id="actifNew" checked>
                        <label class="form-check-label" for="actifNew">Actif</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Ajouter le préfixe</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste des préfixes -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">Liste des préfixes</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Préfixe</th>
                            <th>Description</th>
                            <th>Opérateur</th>
                            <th>Commission</th>
                            <th>Type</th>
                            <th>Commission %</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prefixes as $p): ?>
                            <?php
                                $formEditId = 'edit-prefixe-' . $p['id'];
                                $formDelId  = 'delete-prefixe-' . $p['id'];
                            ?>
                            <tr>
                                <td style="min-width:100px">
                                    <input form="<?= $formEditId ?>" type="text" name="prefixe" value="<?= esc($p['prefixe']) ?>" class="form-control form-control-sm" maxlength="10" required>
                                </td>
                                <td>
                                    <input form="<?= $formEditId ?>" type="text" name="description" value="<?= esc($p['description'] ?? '') ?>" class="form-control form-control-sm">
                                </td>
                                <td style="min-width:110px">
                                    <div class="form-check form-switch">
                                        <input form="<?= $formEditId ?>" type="checkbox" name="est_operateur_principal" value="1" class="form-check-input" <?= $p['est_operateur_principal'] ? 'checked' : '' ?>>
                                        <label class="form-check-label small"><?= $p['est_operateur_principal'] ? 'Notre réseau' : 'Externe' ?></label>
                                    </div>
                                </td>
                                <td style="min-width:100px">
                                    <input form="<?= $formEditId ?>" type="number" name="commission_pourcentage" value="<?= esc($p['commission_pourcentage']) ?>" class="form-control form-control-sm" min="0" step="0.01">
                                <td>
                                    <span class="badge <?= $p['est_operateur_principal'] ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $p['est_operateur_principal'] ? 'Principal' : 'Autre' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$p['est_operateur_principal']): ?>
                                        <strong><?= number_format((float)$p['commission_pourcentage'], 2) ?> %</strong>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="width:110px">
                                    <div class="form-check form-switch">
                                        <input form="<?= $formEditId ?>" type="checkbox" name="actif" value="1" class="form-check-input" <?= $p['actif'] ? 'checked' : '' ?>>
                                        <label class="form-check-label small">Actif</label>
                                    </div>
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    <button form="<?= $formEditId ?>" type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                                    <button form="<?= $formDelId ?>" type="submit" class="btn btn-sm btn-outline-danger">Suppr.</button>
                                </td>
                            </tr>
                            <!-- Forms cachés -->
                            <form id="<?= $formEditId ?>" method="post" action="<?= site_url('admin/prefixes/update/' . $p['id']) ?>"><?= csrf_field() ?></form>
                            <form id="<?= $formDelId ?>" method="post" action="<?= site_url('admin/prefixes/delete/' . $p['id']) ?>" onsubmit="return confirm('Supprimer ce préfixe ?');"><?= csrf_field() ?></form>
                        <?php endforeach; ?>

                        <?php if (empty($prefixes)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Aucun préfixe enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<script>
function toggleCommissionField() {
    const isPrincipal = document.getElementById('est_principal').value == "1";
    document.getElementById('commission_group').style.display = isPrincipal ? 'none' : 'block';
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', toggleCommissionField);
</script>

<?= $this->endSection() ?>
