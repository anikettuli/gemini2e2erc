/**
 * Accessible progressive form validation helpers.
 * - Starts non-hidden inputs at aria-invalid="false"
 * - Validates on blur (and clears live on input once interacted)
 * - Links error containers dynamically in aria-describedby only while invalid
 * - Uses a single polite aria-live announcer with 500ms debounce
 */

const formRegistry = new WeakMap();
const CONTROL_SELECTOR = 'input:not([type="hidden"]), select, textarea';

function getOrCreateState(form) {
  let state = formRegistry.get(form);
  if (state) return state;

  let announcer = form.querySelector('[data-erc-live-announcer]');
  if (!announcer) {
    announcer = document.createElement('div');
    announcer.className = 'visually-hidden';
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    announcer.setAttribute('data-erc-live-announcer', '1');
    form.appendChild(announcer);
  }

  state = {
    interacted: new WeakSet(),
    announcer,
    announceTimer: 0,
  };
  formRegistry.set(form, state);
  return state;
}

function getFieldLabelText(form, field) {
  if (field.id) {
    const label = form.querySelector(`label[for="${CSS.escape(field.id)}"]`);
    if (label) {
      return label.textContent.replace('*', '').trim();
    }
  }
  return field.getAttribute('aria-label') || 'This field';
}

function updateDescribedBy(field, errorId, shouldInclude) {
  if (!errorId) return;
  const current = (field.getAttribute('aria-describedby') || '')
    .split(/\s+/)
    .filter(Boolean);
  const next = shouldInclude
    ? Array.from(new Set([...current, errorId]))
    : current.filter((id) => id !== errorId);

  if (next.length > 0) {
    field.setAttribute('aria-describedby', next.join(' '));
  } else {
    field.removeAttribute('aria-describedby');
  }
}

function resolveErrorMessage(form, field) {
  const customRequired = field.getAttribute('data-msg-required');
  const label = getFieldLabelText(form, field);

  if (field.validity.customError && field.validationMessage) {
    return field.validationMessage;
  }
  if (field.validity.valueMissing) {
    if (customRequired) return customRequired;
    return field.tagName === 'SELECT'
      ? `Please select ${label.toLowerCase()}.`
      : `Please enter your ${label.toLowerCase()}.`;
  }
  if (field.validity.typeMismatch && field.type === 'email') {
    return 'Please enter a valid email address (for example, name@example.com).';
  }
  if (field.validity.tooShort) {
    return `Please enter at least ${field.minLength} characters.`;
  }
  if (field.validity.tooLong) {
    return `Please keep this within ${field.maxLength} characters.`;
  }
  if (field.validity.patternMismatch) {
    return field.getAttribute('data-msg-pattern') || `Please check the format for ${label.toLowerCase()}.`;
  }
  return field.validationMessage || `Please check ${label.toLowerCase()}.`;
}

function scheduleAnnouncement(state, message) {
  if (!state.announcer) return;
  if (state.announceTimer) {
    window.clearTimeout(state.announceTimer);
  }
  state.announcer.textContent = '';
  state.announceTimer = window.setTimeout(() => {
    state.announcer.textContent = message;
  }, 500);
}

function clearPendingAnnouncement(state) {
  if (state.announceTimer) {
    window.clearTimeout(state.announceTimer);
    state.announceTimer = 0;
  }
  if (state.announcer) {
    state.announcer.textContent = '';
  }
}

function syncWhitespaceRequirement(field) {
  if (field.disabled || field.readOnly) {
    field.setCustomValidity('');
    return;
  }
  if (
    field.hasAttribute('required') &&
    (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) &&
    field.type !== 'checkbox' &&
    field.type !== 'radio' &&
    field.type !== 'select-one' &&
    field.value.length > 0 &&
    field.value.trim().length === 0
  ) {
    const label = field.closest('form') ? getFieldLabelText(field.closest('form'), field) : 'this field';
    field.setCustomValidity(`Please enter your ${label.toLowerCase()}.`);
  } else {
    field.setCustomValidity('');
  }
}

export function syncFieldValidity(field, { announce = false, showError = true } = {}) {
  const form = field.closest('form');
  if (!form) return true;
  if (field.closest('.form-honeypot') || field.disabled) return true;

  const state = getOrCreateState(form);
  syncWhitespaceRequirement(field);

  const isValid = field.checkValidity();
  const errorId = field.getAttribute('data-error');
  const errorEl = errorId ? document.getElementById(errorId) : null;

  if (isValid || !showError) {
    field.setAttribute('aria-invalid', 'false');
    field.classList.remove('user-invalid-fallback');
    updateDescribedBy(field, errorId, false);
    if (errorEl) {
      errorEl.hidden = true;
    }
    if (isValid && announce === false) {
      clearPendingAnnouncement(state);
    }
    return isValid;
  }

  const message = resolveErrorMessage(form, field);
  field.setAttribute('aria-invalid', 'true');
  field.classList.add('user-invalid-fallback');
  updateDescribedBy(field, errorId, true);

  if (errorEl) {
    const textSlot = errorEl.querySelector('[data-error-text]') || errorEl;
    textSlot.textContent = message;
    errorEl.hidden = false;
  }

  if (announce) {
    scheduleAnnouncement(state, message);
  }

  return false;
}

export function validateForm(form) {
  const state = getOrCreateState(form);
  const controls = Array.from(form.querySelectorAll(CONTROL_SELECTOR)).filter(
    (el) => !el.closest('.form-honeypot') && !el.disabled
  );

  let firstInvalid = null;
  let invalidCount = 0;

  for (const field of controls) {
    state.interacted.add(field);
    const valid = syncFieldValidity(field, { announce: false, showError: true });
    if (!valid) {
      invalidCount += 1;
      if (!firstInvalid) {
        firstInvalid = field;
      }
    }
  }

  if (firstInvalid) {
    const msg =
      invalidCount === 1
        ? `Please review 1 highlighted field: ${resolveErrorMessage(form, firstInvalid)}`
        : `Please review ${invalidCount} highlighted fields before submitting.`;
    scheduleAnnouncement(state, msg);
    firstInvalid.focus({ focusVisible: true });
    return false;
  }

  clearPendingAnnouncement(state);
  return true;
}

export function initFormValidation(form) {
  if (!(form instanceof HTMLFormElement) || form.dataset.ercValidateBound === '1') {
    return;
  }
  form.dataset.ercValidateBound = '1';
  form.setAttribute('novalidate', '');

  const state = getOrCreateState(form);
  const controls = form.querySelectorAll(CONTROL_SELECTOR);

  controls.forEach((field) => {
    if (field.closest('.form-honeypot')) return;

    field.setAttribute('aria-invalid', 'false');
    const errorId = field.getAttribute('data-error');
    const errorEl = errorId ? document.getElementById(errorId) : null;
    if (errorEl) {
      errorEl.hidden = true;
      updateDescribedBy(field, errorId, false);
    }

    field.addEventListener('blur', () => {
      state.interacted.add(field);
      syncFieldValidity(field, { announce: true, showError: true });
    });

    const liveEvent = field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'radio'
      ? 'change'
      : 'input';

    field.addEventListener(liveEvent, () => {
      if (liveEvent === 'change') {
        state.interacted.add(field);
      }
      if (state.interacted.has(field) || field.getAttribute('aria-invalid') === 'true') {
        syncFieldValidity(field, { announce: false, showError: true });
      }
    });
  });

  if (form.hasAttribute('data-erc-validate-native')) {
    form.addEventListener('submit', (event) => {
      if (!validateForm(form)) {
        event.preventDefault();
      }
    });
  }
}

function autoInitForms() {
  document.querySelectorAll('form[data-erc-validate]').forEach((form) => {
    initFormValidation(form);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitForms, { once: true });
} else {
  autoInitForms();
}
