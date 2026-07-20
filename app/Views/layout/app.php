<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<title><?= $title ?? 'MobiMoney' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php if (session()->get('isLoggedIn')): ?>
<nav class="navbar navbar-expand-lg mm-navbar sticky-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand mm-brand" href="<?= session('role') === 'admin' ? '/admin' : '/client' ?>">
      <span class="mm-brand-icon"><i class="bi bi-wallet2"></i></span>
      MobiMoney
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mmNav" aria-controls="mmNav" aria-expanded="false" aria-label="Basculer la navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mmNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <?php $current = uri_string(); ?>
        <?php if (session('role') === 'client'): ?>
          <li class="nav-item"><a class="nav-link <?= $current === 'client' ? 'active' : '' ?>" href="/client"><i class="bi bi-speedometer2 me-1"></i>Tableau de bord</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'client/depot' ? 'active' : '' ?>" href="/client/depot"><i class="bi bi-arrow-down-circle me-1"></i>Dépôt</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'client/retrait' ? 'active' : '' ?>" href="/client/retrait"><i class="bi bi-arrow-up-circle me-1"></i>Retrait</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'client/transfert' ? 'active' : '' ?>" href="/client/transfert"><i class="bi bi-arrow-left-right me-1"></i>Transfert</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'client/envoi-multiple' ? 'active' : '' ?>" href="/client/envoi-multiple"><i class="bi bi-people me-1"></i>Envoi multiple</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'client/historique' ? 'active' : '' ?>" href="/client/historique"><i class="bi bi-clock-history me-1"></i>Historique</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link <?= $current === 'admin' ? 'active' : '' ?>" href="/admin"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'admin/comptes' ? 'active' : '' ?>" href="/admin/comptes"><i class="bi bi-people me-1"></i>Comptes</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'admin/transactions' ? 'active' : '' ?>" href="/admin/transactions"><i class="bi bi-list-ul me-1"></i>Transactions</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'admin/baremes' ? 'active' : '' ?>" href="/admin/baremes"><i class="bi bi-sliders me-1"></i>Barèmes</a></li>
          <li class="nav-item"><a class="nav-link <?= $current === 'admin/prefixes' ? 'active' : '' ?>" href="/admin/prefixes"><i class="bi bi-hash me-1"></i>Préfixes</a></li>
        <?php endif; ?>
        <li class="nav-item ms-lg-2">
          <a class="nav-link mm-logout" href="/logout"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>

<main class="mm-main <?= session()->get('isLoggedIn') ? '' : 'mm-main--auth' ?>">
  <div class="container-fluid px-3 px-md-4 py-4 <?= session('role') === 'admin' ? 'mm-admin-container' : 'mm-client-container' ?>">

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert mm-alert mm-alert--success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <div><?= esc(session()->getFlashdata('success')) ?></div>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert mm-alert mm-alert--error d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= esc(session()->getFlashdata('error')) ?></div>
      </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>

  </div>
</main>

<footer class="mm-footer text-center py-3">
  <small>&copy; <?= date('Y') ?> MobiMoney — Simulateur Mobile Money v1</small>
</footer>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="mmToastContainer"></div>

<div class="modal fade" id="mmConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content mm-modal">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Confirmation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body" id="mmConfirmMessage"></div>
      <div class="modal-footer border-0">
        <button type="button" class="btn mm-btn-outline" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn mm-btn-primary" id="mmConfirmBtn">Confirmer</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/client.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
