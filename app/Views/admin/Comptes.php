<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4">Comptes clients</h1>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
            <thead>
                <tr>
                    <th>Téléphone</th>
                    <th>Nom</th>
                    <th class="text-end">Solde</th>
                    <th>Créé le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comptes as $c): ?>
                    <tr>
                        <td><?= esc($c['telephone']) ?></td>
                        <td><?= esc($c['nom'] ?? '-') ?></td>
                        <td class="text-end fw-semibold"><?= number_format((float) $c['solde'], 0, ',', ' ') ?> Ar</td>
                        <td><?= esc($c['date_creation'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($comptes)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Aucun compte client.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>