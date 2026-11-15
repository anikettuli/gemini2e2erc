/**
 * Global v2 shell behaviors: native <dialog closedby="any"> mobile drawer,
 * Safari pointer-drag-safe light-dismiss fallback, zoom/overflow nav collapse guard,
 * CSP-safe image fallbacks, live Central Time ticker, and optical swatch easter egg.
 */

let cachedTopLayerSupport;

export function supportsTopLayerExit() {
  if (cachedTopLayerSupport !== undefined) {
    return cachedTopLayerSupport;
  }
  cachedTopLayerSupport =
    typeof CSS !== 'undefined' &&
    typeof CSS.supports === 'function' &&
    CSS.supports('transition-behavior', 'allow-discrete') &&
    CSS.supports('overlay', 'auto');
  return cachedTopLayerSupport;
}

export function closeTopLayer(element) {
  if (!element) return;
  if (element instanceof HTMLDialogElement) {
    if (element.open) {
      element.close();
    }
    return;
  }
  if (typeof element.hidePopover === 'function') {
    try {
      element.hidePopover();
    } catch {
      // Element may already be closed.
    }
  }
}

export function initDialogLightDismiss(dialog) {
  if (!(dialog instanceof HTMLDialogElement)) return;
  if ('closedBy' in HTMLDialogElement.prototype) return;

  let startedOutside = false;

  const isOutsideRect = (event) => {
    const rect = dialog.getBoundingClientRect();
    return (
      event.clientX < rect.left ||
      event.clientX > rect.right ||
      event.clientY < rect.top ||
      event.clientY > rect.bottom
    );
  };

  dialog.addEventListener('pointerdown', (event) => {
    if (event.button !== 0) {
      startedOutside = false;
      return;
    }
    startedOutside = event.target === dialog && isOutsideRect(event);
  });

  dialog.addEventListener('pointerup', (event) => {
    if (!startedOutside) return;
    startedOutside = false;
    if (event.target === dialog && isOutsideRect(event)) {
      closeTopLayer(dialog);
    }
  });
}

function initMobileNavDrawer() {
  const dialog = document.getElementById('mobileNavDialog');
  const openBtn = document.getElementById('navDrawerOpen');
  const closeBtn = document.getElementById('navDrawerClose');

  if (!(dialog instanceof HTMLDialogElement) || !openBtn) return;

  initDialogLightDismiss(dialog);

  const syncExpanded = () => {
    openBtn.setAttribute('aria-expanded', dialog.open ? 'true' : 'false');
  };

  openBtn.addEventListener('click', () => {
    if (dialog.open) return;
    dialog.showModal();
    syncExpanded();
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      closeTopLayer(dialog);
    });
  }

  dialog.addEventListener('close', () => {
    syncExpanded();
    if (document.contains(openBtn) && openBtn.offsetParent !== null) {
      openBtn.focus();
    }
  });

  dialog.querySelectorAll('a[href]').forEach((link) => {
    link.addEventListener('click', () => {
      closeTopLayer(dialog);
    });
  });

  const desktopQuery = window.matchMedia('(min-width: 68rem)');
  const handleDesktopMatch = (event) => {
    const header = document.getElementById('siteHeader');
    const isCollapsed = header && header.getAttribute('data-nav-collapsed') === 'true';
    if (event.matches && !isCollapsed && dialog.open) {
      closeTopLayer(dialog);
    }
  };
  desktopQuery.addEventListener('change', handleDesktopMatch);
}

function initHeaderOverflowGuard() {
  const header = document.getElementById('siteHeader');
  const capsule = header ? header.querySelector('.site-header__capsule') : null;
  const brand = header ? header.querySelector('.site-brand') : null;
  const nav = header ? header.querySelector('.site-nav') : null;
  const controls = header ? header.querySelector('.site-header__controls') : null;

  if (!header || !capsule || !brand || !nav || !controls || typeof ResizeObserver === 'undefined') {
    return;
  }

  let requiredNavWidth = 0;
  let rafId = 0;
  const desktopQuery = window.matchMedia('(min-width: 68rem)');

  const checkOverflow = () => {
    rafId = 0;
    if (!desktopQuery.matches) {
      header.removeAttribute('data-nav-collapsed');
      return;
    }

    const currentlyCollapsed = header.getAttribute('data-nav-collapsed') === 'true';

    if (!currentlyCollapsed) {
      const navStyle = getComputedStyle(nav);
      if (navStyle.display !== 'none') {
        requiredNavWidth = Math.max(requiredNavWidth, nav.scrollWidth);
        const overflows =
          nav.scrollWidth > nav.clientWidth + 1 ||
          capsule.scrollWidth > capsule.clientWidth + 1;
        if (overflows) {
          header.setAttribute('data-nav-collapsed', 'true');
        }
      }
      return;
    }

    const availableWidth =
      capsule.clientWidth - brand.getBoundingClientRect().width - controls.getBoundingClientRect().width - 48;
    if (requiredNavWidth > 0 && availableWidth > requiredNavWidth + 24) {
      header.removeAttribute('data-nav-collapsed');
    }
  };

  const scheduleCheck = () => {
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(checkOverflow);
  };

  const observer = new ResizeObserver(scheduleCheck);
  observer.observe(capsule);
  observer.observe(nav);
  observer.observe(brand);
  observer.observe(controls);
  observer.observe(document.documentElement);
  window.addEventListener('resize', scheduleCheck, { passive: true });
  document.fonts?.ready?.then(scheduleCheck);
  checkOverflow();
}

function initImageFallbacks() {
  document.addEventListener(
    'error',
    (event) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement)) return;
      const fallbackSrc = target.getAttribute('data-fallback-src');
      if (!fallbackSrc || target.dataset.fallbackApplied === '1') return;
      target.dataset.fallbackApplied = '1';
      target.src = fallbackSrc;
    },
    true
  );
}

function initCentralTimeTicker() {
  const footerTicker = document.getElementById('footerLiveTicker');
  const customClocks = document.querySelectorAll('[data-ct-clock]');
  if (!footerTicker && customClocks.length === 0) return;

  const updateTicker = () => {
    try {
      const now = new Date();
      const partsFmt = new Intl.DateTimeFormat('en-US', {
        timeZone: 'America/Chicago',
        weekday: 'short',
        hour: 'numeric',
        minute: '2-digit',
        hour12: false,
      });
      const parts = Object.fromEntries(partsFmt.formatToParts(now).map((p) => [p.type, p.value]));
      const timeText = new Intl.DateTimeFormat('en-US', {
        timeZone: 'America/Chicago',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
      }).format(now);

      const hour24 = Number.parseInt(parts.hour || '0', 10);
      const isSat = (parts.weekday || '').toLowerCase() === 'sat';
      const isOpen = isSat && hour24 >= 9 && hour24 < 15;
      const status = isOpen ? 'OPEN NOW (SAT 9AM\u20133PM)' : 'SAT 9AM\u20133PM \u00B7 MON\u2013FRI BY APPT';

      if (footerTicker) {
        footerTicker.textContent = `WATAUGA TX \u00B7 ${timeText} CT \u00B7 ${status}`;
      }
      customClocks.forEach((el) => {
        el.textContent = `WATAUGA TX ${timeText} CT`;
      });
    } catch {
      // Keep static server-rendered fallback if Intl timeZone is unavailable.
    }
  };

  updateTicker();
  window.setInterval(updateTicker, 30000);
}

function initOpticalSwatchEasterEgg() {
  const eggBtn = document.getElementById('opticalSwatchEgg');
  if (!eggBtn) return;

  const textEl = eggBtn.querySelector('.v2-easter-egg__text');
  eggBtn.addEventListener('click', () => {
    const nextUnlocked = eggBtn.getAttribute('aria-pressed') !== 'true';
    eggBtn.setAttribute('aria-pressed', nextUnlocked ? 'true' : 'false');
    eggBtn.classList.toggle('is-unlocked', nextUnlocked);
    if (textEl) {
      textEl.textContent = nextUnlocked ? '#7CFC06 \u00B7 20/20 SIGHT UNLOCKED' : '20/20 CALIBRATION';
    }
  });
}

function initBackToTopLinks() {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-scroll-top]').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: reduceMotion.matches ? 'auto' : 'smooth',
      });
    });
  });
}

function initShell() {
  initMobileNavDrawer();
  initHeaderOverflowGuard();
  initImageFallbacks();
  initCentralTimeTicker();
  initOpticalSwatchEasterEgg();
  initBackToTopLinks();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initShell, { once: true });
} else {
  initShell();
}
