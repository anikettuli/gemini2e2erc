/**
 * Global shell behaviors: native <dialog closedby="any"> mobile drawer,
 * Safari pointer-drag-safe light-dismiss fallback, zoom/overflow nav collapse guard,
 * and CSP-safe image fallbacks.
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
  const headerInner = header ? header.querySelector('.site-header__inner') : null;
  const brand = header ? header.querySelector('.site-brand') : null;
  const nav = header ? header.querySelector('.site-nav') : null;
  const controls = header ? header.querySelector('.site-header__controls') : null;

  if (!header || !headerInner || !brand || !nav || !controls || typeof ResizeObserver === 'undefined') {
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
          headerInner.scrollWidth > headerInner.clientWidth + 1;
        if (overflows) {
          header.setAttribute('data-nav-collapsed', 'true');
        }
      }
      return;
    }

    const availableWidth =
      headerInner.clientWidth - brand.getBoundingClientRect().width - controls.getBoundingClientRect().width - 48;
    if (requiredNavWidth > 0 && availableWidth > requiredNavWidth + 24) {
      header.removeAttribute('data-nav-collapsed');
    }
  };

  const scheduleCheck = () => {
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(checkOverflow);
  };

  const observer = new ResizeObserver(scheduleCheck);
  observer.observe(headerInner);
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

function initShell() {
  initMobileNavDrawer();
  initHeaderOverflowGuard();
  initImageFallbacks();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initShell, { once: true });
} else {
  initShell();
}
