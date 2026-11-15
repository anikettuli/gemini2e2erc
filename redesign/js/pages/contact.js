/**
 * Progressive enhancement for /contact:
 * - Attaches accessible blur/submit validation via forms.js
 * - Handles dual-mode /send-email.php responses (JSON or followed PRG redirect)
 * - Prevents double-submit and preserves inputs on error
 * - Live message character counter and Watauga address copy helper
 */

import { initFormValidation, validateForm } from '/js/forms.js';

const REASON_MESSAGES = {
  rate_limited:
    'Too many messages were sent recently from your connection. Please wait a few minutes or call or email us directly.',
  invalid_input:
    'Please check your name, email address, and message to make sure all required details are complete.',
  send_failed:
    'We could not deliver your message right now due to a mail server issue. Please contact us directly by email or phone.',
  mail_failed:
    'We could not deliver your message right now due to a mail server issue. Please contact us directly by email or phone.',
};

function renderAlert(alertEl, { type, title, message }) {
  if (!alertEl) return;
  const isSuccess = type === 'success';

  alertEl.className = `callout contact-status ${isSuccess ? 'callout--success' : 'callout--danger'}`;
  alertEl.setAttribute('role', isSuccess ? 'status' : 'alert');
  alertEl.hidden = false;

  const iconSlot = alertEl.querySelector('[data-alert-icon]');
  const titleSlot = alertEl.querySelector('[data-alert-title]');
  const textSlot = alertEl.querySelector('[data-alert-message]');

  if (iconSlot) {
    const tplId = isSuccess ? 'tplContactIconSuccess' : 'tplContactIconError';
    const tpl = document.getElementById(tplId);
    iconSlot.innerHTML = '';
    if (tpl instanceof HTMLTemplateElement) {
      iconSlot.appendChild(tpl.content.cloneNode(true));
    }
  }
  if (titleSlot) titleSlot.textContent = title;
  if (textSlot) textSlot.textContent = message;

  alertEl.setAttribute('tabindex', '-1');
  alertEl.focus();
}

function initMessageCounter(textarea, counterEl) {
  if (!textarea || !counterEl) return;
  const max = Number(textarea.getAttribute('maxlength')) || 5000;
  const formatter = new Intl.NumberFormat('en-US');

  const update = () => {
    const len = textarea.value.length;
    counterEl.textContent = `${formatter.format(len)} / ${formatter.format(max)} characters`;
  };

  textarea.addEventListener('input', update);
  update();
}

function initCopyAddress() {
  const btn = document.getElementById('copyCenterAddressBtn');
  const feedback = document.getElementById('copyCenterFeedback');
  if (!btn || !feedback) return;

  const address = btn.getAttribute('data-copy-text') || '5621 Bunker Blvd, Watauga, TX 76148';
  let timer = 0;

  btn.addEventListener('click', async () => {
    let copied = false;
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      try {
        await navigator.clipboard.writeText(address);
        copied = true;
      } catch {
        copied = false;
      }
    }

    if (timer) window.clearTimeout(timer);
    feedback.textContent = copied
      ? 'Address copied to clipboard.'
      : 'Copy unavailable; select 5621 Bunker Blvd, Watauga, TX 76148 above.';
    timer = window.setTimeout(() => {
      feedback.textContent = '';
    }, 4500);
  });
}

function initContactPage() {
  initCopyAddress();

  const form = document.getElementById('contactForm');
  const alertEl = document.getElementById('contactFormAlert');
  const submitBtn = document.getElementById('contactSubmitBtn');
  const submitLabel = document.getElementById('contactSubmitLabel');
  const messageField = document.getElementById('contactMessage');
  const counterEl = document.getElementById('contactMessageCount');

  if (!(form instanceof HTMLFormElement)) return;

  initFormValidation(form);
  initMessageCounter(messageField, counterEl);

  let isSubmitting = false;
  const defaultLabel = submitLabel ? submitLabel.textContent : 'Send Inquiry';

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (isSubmitting) return;

    if (!validateForm(form)) {
      return;
    }

    isSubmitting = true;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.setAttribute('aria-busy', 'true');
    }
    if (submitLabel) {
      submitLabel.textContent = 'Sending inquiry…';
    }

    try {
      const formData = new FormData(form);
      const body = new URLSearchParams();
      formData.forEach((value, key) => {
        if (typeof value === 'string') {
          body.append(key, value);
        }
      });

      const response = await fetch(form.getAttribute('action') || '/send-email.php', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/html;q=0.9',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'fetch',
        },
        body: body.toString(),
      });

      const contentType = response.headers.get('content-type') || '';

      if (contentType.includes('application/json')) {
        const data = await response.json();
        if (response.ok && data && data.success) {
          renderAlert(alertEl, {
            type: 'success',
            title: 'Inquiry sent',
            message:
              data.message ||
              'Thank you for reaching out. Our volunteers usually respond within 24 hours.',
          });
          form.reset();
          form.querySelectorAll('[aria-invalid]').forEach((el) => {
            el.setAttribute('aria-invalid', 'false');
            el.classList.remove('user-invalid-fallback');
          });
          if (messageField && counterEl) {
            messageField.dispatchEvent(new Event('input'));
          }
          return;
        }

        const reason = data && typeof data.reason === 'string' ? data.reason : '';
        renderAlert(alertEl, {
          type: 'error',
          title: 'Could not send inquiry',
          message:
            (data && data.message) ||
            REASON_MESSAGES[reason] ||
            'Something went wrong sending your message. Please try calling or emailing us directly.',
        });
        return;
      }

      // PRG redirect fallback handled cleanly when fetch follows 302/303 to ?status=...
      const resolvedUrl = new URL(response.url || window.location.href, window.location.href);
      const status = resolvedUrl.searchParams.get('status');
      const reason = resolvedUrl.searchParams.get('reason') || '';

      if (status === 'success') {
        renderAlert(alertEl, {
          type: 'success',
          title: 'Inquiry sent',
          message:
            'Thank you for reaching out. Our volunteers usually respond within 24 hours.',
        });
        form.reset();
        form.querySelectorAll('[aria-invalid]').forEach((el) => {
          el.setAttribute('aria-invalid', 'false');
          el.classList.remove('user-invalid-fallback');
        });
        if (messageField && counterEl) {
          messageField.dispatchEvent(new Event('input'));
        }
      } else if (status === 'error') {
        renderAlert(alertEl, {
          type: 'error',
          title: 'Could not send inquiry',
          message:
            REASON_MESSAGES[reason] ||
            'We could not send your message. Please verify your entries or contact us directly.',
        });
      } else if (!response.ok) {
        renderAlert(alertEl, {
          type: 'error',
          title: 'Unable to send message',
          message:
            'The server could not process your request right now. Please contact us directly by phone or email.',
        });
      } else {
        renderAlert(alertEl, {
          type: 'success',
          title: 'Inquiry submitted',
          message: 'Thank you for contacting the District 2-E2 Eyeglass Recycling Center.',
        });
        form.reset();
      }
    } catch {
      renderAlert(alertEl, {
        type: 'error',
        title: 'Connection error',
        message:
          'We could not reach the server. Please check your internet connection or call us directly.',
      });
    } finally {
      isSubmitting = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.removeAttribute('aria-busy');
      }
      if (submitLabel) {
        submitLabel.textContent = defaultLabel;
      }
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initContactPage, { once: true });
} else {
  initContactPage();
}
