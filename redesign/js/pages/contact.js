/**
 * Progressive enhancement for /contact:
 * - Accessible blur/submit validation via forms.js
 * - Dual-mode /send-email.php responses (JSON or followed PRG redirect)
 * - 8-category pill selector synced to dynamic routing telemetry + REQ-2E2 status badge
 * - Live message character counter above textarea#contactMessage
 * - One-click address & email copy helpers
 * - Interactive "BEFORE YOU ASK" FAQ review queue with STAMP ALL + UNDO
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
    iconSlot.textContent = '';
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

function bindCopyButton(btnId, feedbackId, copiedLabel, fallbackPrefix) {
  const btn = document.getElementById(btnId);
  const feedback = document.getElementById(feedbackId);
  if (!btn || !feedback) return;

  const text = btn.getAttribute('data-copy-text') || '';
  if (!text) return;
  let timer = 0;

  btn.addEventListener('click', async () => {
    let copied = false;
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      try {
        await navigator.clipboard.writeText(text);
        copied = true;
      } catch {
        copied = false;
      }
    }

    if (timer) window.clearTimeout(timer);
    feedback.textContent = copied ? copiedLabel : `${fallbackPrefix} ${text}`;
    timer = window.setTimeout(() => {
      feedback.textContent = '';
    }, 4500);
  });
}

function initTopicRouting(form) {
  const routingEl = document.getElementById('dispatchRoutingValue');
  if (!form || !routingEl) return;

  const syncRoute = () => {
    const checked = form.querySelector('input[name="subject"]:checked');
    if (checked instanceof HTMLInputElement) {
      const route = checked.getAttribute('data-route-label');
      if (route) {
        routingEl.textContent = route;
      }
    }
  };

  form.addEventListener('change', (event) => {
    const target = event.target;
    if (target instanceof HTMLInputElement && target.name === 'subject') {
      syncRoute();
    }
  });

  syncRoute();
}

function setConsoleState(stateText) {
  const badge = document.getElementById('dispatchStateBadge');
  const label = document.getElementById('dispatchStateText');
  if (!badge || !label) return;
  label.textContent = stateText;
  badge.classList.toggle('is-sent', stateText === 'SENT');
}

function initFaqReviewQueue() {
  const list = document.getElementById('faqQueueList');
  const clearedEl = document.getElementById('faqClearedCount');
  const pendingEl = document.getElementById('faqPendingCount');
  const stampBtn = document.getElementById('faqStampAllBtn');
  const stampLabel = document.getElementById('faqStampAllLabel');
  const toastEl = document.getElementById('faqStampToast');
  const toastMsg = document.getElementById('faqStampToastMsg');
  const undoBtn = document.getElementById('faqUndoBtn');

  if (!list || !clearedEl || !pendingEl) return;

  const items = Array.from(list.querySelectorAll('details.faq-item'));
  if (items.length === 0) return;

  let previousSnapshot = null;

  const padTwo = (n) => String(n).padStart(2, '0');

  const syncQueueCounters = () => {
    let clearedCount = 0;
    for (const item of items) {
      const isCleared = item.dataset.cleared === 'true';
      if (isCleared) clearedCount += 1;
      const badge = item.querySelector('[data-faq-status]');
      if (badge) {
        badge.textContent = isCleared ? 'CLEARED' : 'PENDING';
      }
    }
    const pendingCount = items.length - clearedCount;
    clearedEl.textContent = padTwo(clearedCount);
    pendingEl.textContent = padTwo(pendingCount);

    if (stampLabel && stampBtn) {
      const allCleared = pendingCount === 0;
      stampLabel.textContent = allCleared ? 'ALL CLEARED' : 'STAMP ALL';
      stampBtn.setAttribute('aria-pressed', allCleared ? 'true' : 'false');
    }
  };

  items.forEach((item) => {
    item.addEventListener('toggle', () => {
      if (item.open && item.dataset.cleared !== 'true') {
        item.dataset.cleared = 'true';
        syncQueueCounters();
      }
    });
  });

  if (stampBtn) {
    stampBtn.addEventListener('click', () => {
      const allAlreadyCleared = items.every((item) => item.dataset.cleared === 'true');
      if (allAlreadyCleared) {
        items.forEach((item) => {
          item.dataset.cleared = 'false';
          item.classList.remove('is-stamped');
        });
        if (toastEl) toastEl.hidden = true;
        previousSnapshot = null;
        syncQueueCounters();
        return;
      }

      previousSnapshot = items.map((item) => item.dataset.cleared === 'true');
      items.forEach((item) => {
        item.dataset.cleared = 'true';
        item.classList.remove('is-stamped');
        void item.offsetWidth;
        item.classList.add('is-stamped');
      });
      syncQueueCounters();

      if (toastMsg) {
        toastMsg.textContent = `RUBBER STAMP DETECTED: all ${items.length} questions cleared in one click.`;
      }
      if (toastEl) {
        toastEl.hidden = false;
      }
    });
  }

  if (undoBtn) {
    undoBtn.addEventListener('click', () => {
      if (Array.isArray(previousSnapshot)) {
        items.forEach((item, idx) => {
          item.dataset.cleared = previousSnapshot[idx] ? 'true' : 'false';
          item.classList.remove('is-stamped');
        });
      } else {
        items.forEach((item) => {
          item.dataset.cleared = item.open ? 'true' : 'false';
          item.classList.remove('is-stamped');
        });
      }
      previousSnapshot = null;
      if (toastEl) toastEl.hidden = true;
      syncQueueCounters();
      stampBtn?.focus();
    });
  }

  syncQueueCounters();
}

function initContactPage() {
  bindCopyButton(
    'copyCenterAddressBtn',
    'copyCenterFeedback',
    'Address copied: 5621 Bunker Blvd, Watauga, TX 76148.',
    'Select:'
  );
  bindCopyButton(
    'copyEmailAddressBtn',
    'copyEmailFeedback',
    'Copied!',
    'Email:'
  );
  initFaqReviewQueue();

  const form = document.getElementById('contactForm');
  const alertEl = document.getElementById('contactFormAlert');
  const submitBtn = document.getElementById('contactSubmitBtn');
  const submitLabel = document.getElementById('contactSubmitLabel');
  const messageField = document.getElementById('contactMessage');
  const counterEl = document.getElementById('contactMessageCount');

  if (!(form instanceof HTMLFormElement)) return;

  initFormValidation(form);
  initMessageCounter(messageField, counterEl);
  initTopicRouting(form);

  if (alertEl && !alertEl.hidden && alertEl.classList.contains('callout--success')) {
    setConsoleState('SENT');
  }

  let isSubmitting = false;
  const defaultLabel = submitLabel ? submitLabel.textContent : 'SEND DISPATCH';

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (isSubmitting) return;

    if (!validateForm(form)) {
      return;
    }

    isSubmitting = true;
    setConsoleState('SENDING');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.setAttribute('aria-busy', 'true');
    }
    if (submitLabel) {
      submitLabel.textContent = 'TRANSMITTING...';
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
          setConsoleState('SENT');
          renderAlert(alertEl, {
            type: 'success',
            title: 'Dispatch transmitted',
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
          initTopicRouting(form);
          return;
        }

        setConsoleState('DRAFT');
        const reason = data && typeof data.reason === 'string' ? data.reason : '';
        renderAlert(alertEl, {
          type: 'error',
          title: 'Dispatch not delivered',
          message:
            (data && data.message) ||
            REASON_MESSAGES[reason] ||
            'Something went wrong sending your message. Please try calling or emailing us directly.',
        });
        return;
      }

      const resolvedUrl = new URL(response.url || window.location.href, window.location.href);
      const status = resolvedUrl.searchParams.get('status');
      const reason = resolvedUrl.searchParams.get('reason') || '';

      if (status === 'success') {
        setConsoleState('SENT');
        renderAlert(alertEl, {
          type: 'success',
          title: 'Dispatch transmitted',
          message: 'Thank you for reaching out. Our volunteers usually respond within 24 hours.',
        });
        form.reset();
        form.querySelectorAll('[aria-invalid]').forEach((el) => {
          el.setAttribute('aria-invalid', 'false');
          el.classList.remove('user-invalid-fallback');
        });
        if (messageField && counterEl) {
          messageField.dispatchEvent(new Event('input'));
        }
        initTopicRouting(form);
      } else if (status === 'error') {
        setConsoleState('DRAFT');
        renderAlert(alertEl, {
          type: 'error',
          title: 'Dispatch not delivered',
          message:
            REASON_MESSAGES[reason] ||
            'We could not send your message. Please verify your entries or contact us directly.',
        });
      } else if (!response.ok) {
        setConsoleState('DRAFT');
        renderAlert(alertEl, {
          type: 'error',
          title: 'Unable to send message',
          message:
            'The server could not process your request right now. Please contact us directly by phone or email.',
        });
      } else {
        setConsoleState('SENT');
        renderAlert(alertEl, {
          type: 'success',
          title: 'Dispatch submitted',
          message: 'Thank you for contacting the District 2-E2 Eyeglass Recycling Center.',
        });
        form.reset();
        initTopicRouting(form);
      }
    } catch {
      setConsoleState('DRAFT');
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
