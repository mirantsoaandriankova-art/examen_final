/* =============================================
   MOBIMONEY — client.js
   Validation, aperçu des frais, confirmation, toasts
   ============================================= */

document.addEventListener('DOMContentLoaded', function () {
  initLoginValidation();
  initTransfertDestinataireValidation();
  initMontantLiveValidation();
  initFraisPreview();
  initOperationSubmit();
  initFlashToasts();
});

/* ---------- Validation téléphone ---------- */

function validateTelephone(numero) {
  const cleaned = (numero || '').replace(/\s+/g, '');
  return /^(033|037)\d{7}$/.test(cleaned);
}

function initLoginValidation() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const input = document.getElementById('telephone');

  form.addEventListener('submit', function (e) {
    if (!validateTelephone(input.value)) {
      e.preventDefault();
      input.classList.add('is-invalid');
    }
  });

  input.addEventListener('input', function () {
    input.classList.remove('is-invalid');
  });
}

function initTransfertDestinataireValidation() {
  const dest = document.getElementById('telephone_destinataire');
  if (!dest) return;

  dest.addEventListener('input', function () {
    dest.classList.remove('is-invalid');
  });

  dest.addEventListener('blur', function () {
    if (dest.value && !validateTelephone(dest.value)) {
      dest.classList.add('is-invalid');
    }
  });
}

/* ---------- Validation montant ---------- */

function validateMontant(input) {
  const value = parseFloat(input.value);
  const valid = !isNaN(value) && value > 0;
  input.classList.toggle('is-invalid', !valid);
  return valid;
}

function initMontantLiveValidation() {
  const input = document.getElementById('montant');
  if (!input) return;

  input.addEventListener('input', function () {
    if (input.value !== '') {
      validateMontant(input);
    } else {
      input.classList.remove('is-invalid');
    }
  });
}

/* ---------- Aperçu des frais (AJAX) ---------- */

function initFraisPreview() {
  const form = document.getElementById('operationForm');
  if (!form) return;

  const type = form.dataset.type; // depot | retrait | transfert
  const montantInput = document.getElementById('montant');
  if (!montantInput) return;

  let timer = null;
  montantInput.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      previewFrais(montantInput.value, type);
    }, 300);
  });
}

function previewFrais(montant, typeOperation) {
  const fraisEl = document.getElementById('previewFrais');
  const totalEl = document.getElementById('previewTotal');
  if (!fraisEl || !totalEl) return;

  const montantVal = parseFloat(montant);

  if (isNaN(montantVal) || montantVal <= 0) {
    fraisEl.textContent = '0 Ar';
    totalEl.textContent = '0 Ar';
    return;
  }

  // Le dépôt est sans frais : pas besoin d'appeler l'API
  if (typeOperation === 'depot') {
    fraisEl.textContent = '0 Ar';
    totalEl.textContent = formatMontant(montantVal) + ' Ar';
    return;
  }

  fetch('/api/calculer-frais', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'type_operation=' + encodeURIComponent(typeOperation) + '&montant=' + encodeURIComponent(montantVal),
  })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      fraisEl.textContent = formatMontant(data.frais) + ' Ar';
      totalEl.textContent = formatMontant(data.total) + ' Ar';
    })
    .catch(function () {
      fraisEl.textContent = '—';
      totalEl.textContent = '—';
    });
}

function formatMontant(n) {
  return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/* ---------- Soumission avec confirmation ---------- */

function initOperationSubmit() {
  const form = document.getElementById('operationForm');
  if (!form) return;

  const type = form.dataset.type;

  form.addEventListener('submit', function (e) {
    const montantInput = document.getElementById('montant');

    if (!validateMontant(montantInput)) {
      e.preventDefault();
      return;
    }

    const destInput = document.getElementById('telephone_destinataire');
    if (destInput && !validateTelephone(destInput.value)) {
      e.preventDefault();
      destInput.classList.add('is-invalid');
      return;
    }

    // Le dépôt ne nécessite pas de confirmation
    if (type === 'depot') return;

    if (form.dataset.confirmed === 'true') return;

    e.preventDefault();

    const montant = formatMontant(parseFloat(montantInput.value));
    let message = 'Confirmez-vous ' + (type === 'retrait' ? 'le retrait' : 'le transfert') + ' de ' + montant + ' Ar ?';

    if (type === 'transfert' && destInput) {
      message += ' Vers le ' + destInput.value + '.';
    }

    confirmAction(message, function () {
      form.dataset.confirmed = 'true';
      form.submit();
    });
  });
}

/* ---------- Modal de confirmation ---------- */

function confirmAction(message, onConfirm) {
  const modalEl = document.getElementById('mmConfirmModal');

  if (!modalEl || typeof bootstrap === 'undefined') {
    if (window.confirm(message)) onConfirm();
    return;
  }

  document.getElementById('mmConfirmMessage').textContent = message;
  const modal = new bootstrap.Modal(modalEl);
  const btn = document.getElementById('mmConfirmBtn');

  const handler = function () {
    modal.hide();
    btn.removeEventListener('click', handler);
    onConfirm();
  };

  btn.addEventListener('click', handler);
  modal.show();
}

/* ---------- Toasts ---------- */

function showToast(type, message) {
  const container = document.getElementById('mmToastContainer');
  if (!container || typeof bootstrap === 'undefined') return;

  const bgClass = type === 'success' ? 'mm-toast--success' : type === 'error' ? 'mm-toast--error' : 'mm-toast--info';
  const icon = type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';

  const toastEl = document.createElement('div');
  toastEl.className = 'toast align-items-center border-0 mm-toast ' + bgClass;
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.setAttribute('aria-atomic', 'true');
  toastEl.innerHTML =
    '<div class="d-flex">' +
      '<div class="toast-body"><i class="bi ' + icon + ' me-2"></i>' + message + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>' +
    '</div>';

  container.appendChild(toastEl);
  const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
  toast.show();
  toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
}

function initFlashToasts() {
  const successEl = document.querySelector('.mm-alert--success');
  const errorEl = document.querySelector('.mm-alert--error');
  if (successEl) showToast('success', successEl.textContent.trim());
  if (errorEl) showToast('error', errorEl.textContent.trim());
}
