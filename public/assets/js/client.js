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
  initEnvoiMultiple();
  initFlashToasts();
});

/* ---------- Validation téléphone ---------- */

function validateTelephone(numero) {
  const cleaned = (numero || '').replace(/\s+/g, '');
  return /^\d{10}$/.test(cleaned);
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

  if (type === 'transfert') {
    const telephoneInput = document.getElementById('telephone_destinataire');
    const fraisRetraitInput = document.getElementById('inclureFraisRetrait');
    const preview = function () {
      previewFraisTransfert(
        montantInput.value,
        telephoneInput ? telephoneInput.value : '',
        true,
        fraisRetraitInput && fraisRetraitInput.checked
      );
    };
    montantInput.addEventListener('input', preview);
    if (telephoneInput) telephoneInput.addEventListener('input', preview);
    if (fraisRetraitInput) fraisRetraitInput.addEventListener('change', preview);
    return;
  }

  let timer = null;
  montantInput.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      previewFrais(montantInput.value, type);
    }, 300);
  });
}

function previewFraisTransfert(montant, telephoneDest, fraisInclus, inclureFraisRetrait) {
  const fraisEl = document.getElementById('previewFrais');
  const debiteEl = document.getElementById('previewDebite');
  const recuEl = document.getElementById('previewRecu');
  const retraitEl = document.getElementById('previewRetrait');
  const retraitInput = document.getElementById('inclureFraisRetrait');
  if (!fraisEl || !debiteEl || !recuEl) return;

  const montantVal = parseFloat(montant);
  if (isNaN(montantVal) || montantVal <= 0) {
    fraisEl.textContent = '0 Ar';
    debiteEl.textContent = '0 Ar';
    recuEl.textContent = '0 Ar';
    if (retraitEl) retraitEl.textContent = '0 Ar';
    return;
  }

  requestFrais({
    type_operation: 'transfert',
    montant: montantVal,
    telephone_dest: telephoneDest,
    frais_inclus: fraisInclus ? '1' : '0',
    inclure_frais_retrait: inclureFraisRetrait ? '1' : '0',
  })
    .then(function (data) {
      const frais = numericValue(data.frais);
      const commission = numericValue(data.commission);
      const fraisTotal = frais + commission;
      const montantDebite = numericValue(data.montant_debite, numericValue(data.total, montantVal + fraisTotal));
      const montantRecu = numericValue(data.montant_recu, montantVal);
      const fraisRetrait = numericValue(data.frais_retrait);
      fraisEl.textContent = formatMontant(fraisTotal) + ' Ar';
      debiteEl.textContent = formatMontant(montantDebite) + ' Ar';
      recuEl.textContent = formatMontant(montantRecu) + ' Ar';
      if (retraitEl) retraitEl.textContent = formatMontant(fraisRetrait) + ' Ar';
      if (retraitInput && data.autre_operateur) {
        retraitInput.checked = false;
        retraitInput.disabled = true;
      } else if (retraitInput) {
        retraitInput.disabled = false;
      }
    })
    .catch(function () {
      fraisEl.textContent = '—';
      debiteEl.textContent = '—';
      recuEl.textContent = '—';
      if (retraitEl) retraitEl.textContent = '—';
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

  requestFrais({ type_operation: typeOperation, montant: montantVal })
    .then(function (data) {
      const frais = numericValue(data.frais);
      const total = numericValue(data.total, montantVal + frais);
      fraisEl.textContent = formatMontant(frais) + ' Ar';
      const montantAffiche = typeOperation === 'depot'
        ? montantVal - frais
        : total;
      totalEl.textContent = formatMontant(montantAffiche) + ' Ar';
    })
    .catch(function () {
      fraisEl.textContent = '—';
      totalEl.textContent = '—';
    });
}

/* ---------- Envoi multiple ---------- */

function initEnvoiMultiple() {
  const form = document.getElementById('multipleTransferForm');
  if (!form) return;

  const montantInput = document.getElementById('montant_total');
  const list = document.getElementById('destinatairesList');
  const addButton = document.getElementById('addDestinataireBtn');

  addButton.addEventListener('click', function () {
    addDestinataireField(list);
    previewEnvoiMultiple(montantInput.value, getMultipleTelephones(list));
  });

  list.addEventListener('input', function () {
    previewEnvoiMultiple(montantInput.value, getMultipleTelephones(list));
  });
  list.addEventListener('click', function (event) {
    if (!event.target.closest('.remove-destinataire')) return;
    removeDestinataireField(event.target.closest('.destinataire-field'), list);
    previewEnvoiMultiple(montantInput.value, getMultipleTelephones(list));
  });
  montantInput.addEventListener('input', function () {
    previewEnvoiMultiple(montantInput.value, getMultipleTelephones(list));
  });

  form.addEventListener('submit', function (event) {
    const telephones = getMultipleTelephones(list);
    if (!validateMontant(montantInput) || telephones.length < 2 || telephones.some(function (telephone) { return !validateTelephone(telephone); })) {
      event.preventDefault();
      return;
    }
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();
    const message = 'Confirmez-vous l’envoi multiple de ' + formatMontant(parseFloat(montantInput.value))
      + ' Ar vers ' + telephones.length + ' destinataires ?';
    confirmAction(message, function () {
      form.dataset.confirmed = 'true';
      form.submit();
    });
  });
}

function addDestinataireField(list) {
  const field = document.createElement('div');
  field.className = 'input-group mm-input-group mb-2 destinataire-field';
  field.innerHTML = '<input type="tel" class="form-control multiple-telephone" name="telephones[]" placeholder="0321234567" inputmode="numeric" required>'
    + '<button class="btn btn-outline-danger remove-destinataire" type="button" aria-label="Retirer"><i class="bi bi-trash"></i></button>';
  list.appendChild(field);
}

function removeDestinataireField(field, list) {
  if (list.querySelectorAll('.destinataire-field').length > 2) field.remove();
}

function getMultipleTelephones(list) {
  return Array.from(list.querySelectorAll('.multiple-telephone'))
    .map(function (input) { return input.value.trim(); })
    .filter(Boolean);
}

function previewEnvoiMultiple(montantTotal, telephones) {
  const preview = document.getElementById('multiplePreview');
  const montant = parseFloat(montantTotal);
  if (!preview || isNaN(montant) || montant <= 0 || telephones.length < 2) return;

  const part = Math.floor(montant / telephones.length);
  const reliquat = montant - part * telephones.length;
  preview.textContent = 'Calcul des frais…';

  Promise.all(telephones.map(function (telephone, index) {
    const montantPart = part + (index === telephones.length - 1 ? reliquat : 0);
    return requestFrais({
      type_operation: 'transfert',
      montant: montantPart,
      telephone_dest: telephone,
      frais_inclus: '0',
    })
      .then(function (data) { return { telephone: telephone, part: montantPart, data: data }; });
  }))
    .then(function (resultats) {
      const totalDebite = resultats.reduce(function (total, resultat) {
        return total + numericValue(resultat.data.montant_debite, resultat.part);
      }, 0);
      preview.innerHTML = '<div class="fw-semibold mb-2">Répartition estimée</div>'
        + resultats.map(function (resultat) {
          const cout = numericValue(resultat.data.frais) + numericValue(resultat.data.commission);
          return '<div class="d-flex justify-content-between small"><span>' + resultat.telephone + '</span><span>'
            + formatMontant(resultat.part) + ' Ar, frais ' + formatMontant(cout) + ' Ar</span></div>';
        }).join('')
        + '<hr class="my-2"><div class="d-flex justify-content-between fw-semibold"><span>Total débité</span><span>'
        + formatMontant(totalDebite) + ' Ar</span></div>';
    })
    .catch(function () {
      preview.textContent = 'Aperçu indisponible.';
    });
}

function requestFrais(data) {
  const body = new URLSearchParams(data);

  return fetch('/api/calculer-frais', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: body.toString(),
  }).then(function (response) {
    if (!response.ok) throw new Error('Calcul des frais indisponible.');
    return response.json();
  }).then(function (data) {
    if (data.error) throw new Error('Réponse de calcul invalide.');
    return data;
  });
}

function numericValue(value, fallback) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : (fallback === undefined ? 0 : fallback);
}

function formatMontant(value) {
  return Math.round(numericValue(value)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
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

    if (form.dataset.confirmed === 'true') return;

    e.preventDefault();

    const montant = formatMontant(parseFloat(montantInput.value));
    let operation = type === 'depot' ? 'le dépôt' : type === 'retrait' ? 'le retrait' : 'le transfert';
    let message = 'Confirmez-vous ' + operation + ' de ' + montant + ' Ar ?';

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
