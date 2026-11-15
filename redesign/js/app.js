/**
 * v3 Shell & Single-Scroll Core Module
 * - Native <dialog closedby="any"> mobile nav + pointer-drag-safe fallback
 * - ResizeObserver header overflow guard (200% zoom & narrow viewports)
 * - IntersectionObserver scroll-spy syncing .is-active & aria-current
 * - Initial route deep-link & legacy SPA #hash bridge
 * - CSP-safe image fallback listener
 */

export function prefersReducedMotion() {
  return Boolean(window.matchMedia?.('(prefers-reduced-motion: reduce)').matches);
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
      // Already closed
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

  closeBtn?.addEventListener('click', () => {
    closeTopLayer(dialog);
  });

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

  window.addEventListener('resize', () => {
    const header = document.getElementById('siteHeader');
    const isCollapsed = header && header.getAttribute('data-nav-collapsed') === 'true';
    const rootFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
    const minDesktopPx = rootFontSize * 60;
    if (window.innerWidth >= minDesktopPx && !isCollapsed && dialog.open) {
      closeTopLayer(dialog);
    }
  });
}

function initHeaderOverflowGuard() {
  const header = document.getElementById('siteHeader');
  const capsule = header?.querySelector('.site-header__capsule');
  const brand = header?.querySelector('.site-brand');
  const nav = header?.querySelector('.site-nav');
  const controls = header?.querySelector('.site-header__controls');

  if (!header || !capsule || !brand || !nav || !controls || typeof ResizeObserver === 'undefined') {
    return;
  }

  let collapsedAtFontSize = 0;
  let collapsedAtCapsuleWidth = 0;
  let rafId = 0;

  const checkOverflow = () => {
    rafId = 0;
    const rootFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
    const minDesktopPx = rootFontSize * 60;

    if (capsule.clientWidth < minDesktopPx) {
      header.removeAttribute('data-nav-collapsed');
      collapsedAtFontSize = 0;
      collapsedAtCapsuleWidth = 0;
      return;
    }

    const currentlyCollapsed = header.getAttribute('data-nav-collapsed') === 'true';
    if (currentlyCollapsed) {
      const fontShrank = rootFontSize < collapsedAtFontSize - 0.1;
      const widthGrew = capsule.clientWidth > collapsedAtCapsuleWidth + 16;
      if (!fontShrank && !widthGrew) {
        return;
      }
      header.removeAttribute('data-nav-collapsed');
    }

    const navDisplay = getComputedStyle(nav).display;
    if (navDisplay === 'none') return;

    const totalUsed = brand.offsetWidth + nav.scrollWidth + controls.offsetWidth + 32;
    const hasOverflow = totalUsed > capsule.clientWidth || nav.scrollWidth > nav.clientWidth + 1;
    if (hasOverflow) {
      collapsedAtFontSize = rootFontSize;
      collapsedAtCapsuleWidth = capsule.clientWidth;
      header.setAttribute('data-nav-collapsed', 'true');
    }
  };

  const scheduleCheck = () => {
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(checkOverflow);
  };

  const observer = new ResizeObserver(scheduleCheck);
  observer.observe(capsule);
  observer.observe(document.documentElement);
  window.addEventListener('resize', scheduleCheck, { passive: true });
  document.fonts?.ready?.then(scheduleCheck);
  scheduleCheck();
}

function initScrollSpy() {
  const navLinks = Array.from(document.querySelectorAll('.site-nav__link[href^="#"], .nav-drawer__link[href^="#"]'));
  const sectionIds = ['drop-off', 'process', 'impact', 'volunteer', 'about', 'contact'];
  const sections = sectionIds
    .map((id) => document.getElementById(id))
    .filter((el) => el instanceof HTMLElement);

  if (sections.length === 0 || typeof IntersectionObserver === 'undefined') {
    return;
  }

  let activeId = '';

  const setActiveNav = (id) => {
    if (id === activeId) return;
    activeId = id;

    for (const link of navLinks) {
      const hrefId = (link.getAttribute('href') || '').replace(/^#/, '');
      const isMatch = id !== '' && hrefId === id;
      link.classList.toggle('is-active', isMatch);
      if (isMatch) {
        link.setAttribute('aria-current', 'true');
      } else {
        link.removeAttribute('aria-current');
      }
    }
  };

  const observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

      if (visible.length > 0) {
        setActiveNav(visible[0].target.id);
      } else if (window.scrollY < 160) {
        setActiveNav('');
      }
    },
    {
      rootMargin: '-25% 0px -65% 0px',
      threshold: 0,
    }
  );

  for (const section of sections) {
    observer.observe(section);
  }
}

function initInitialRouteAndLegacyHashes() {
  const legacyHashMap = {
    '#home': 'top',
    '#services': 'impact',
    '#get-involved': 'volunteer',
    '#locations': 'drop-off',
    '#map': 'drop-off',
    '#about': 'about',
    '#contact': 'contact',
  };

  const rawHash = (window.location.hash || '').toLowerCase();
  let targetId = '';

  if (rawHash && Object.prototype.hasOwnProperty.call(legacyHashMap, rawHash)) {
    targetId = legacyHashMap[rawHash];
  } else if (rawHash && rawHash.length > 1) {
    const cleanHash = rawHash.slice(1);
    if (document.getElementById(cleanHash)) {
      targetId = cleanHash;
    }
  } else {
    targetId = (document.body.dataset.initialSection || '').trim();
  }

  if (!targetId) return;

  if (targetId === 'top') {
    window.scrollTo({ top: 0, behavior: 'instant' });
    if (window.history?.replaceState && !window.location.search) {
      window.history.replaceState(null, '', '/');
    }
    return;
  }

  const targetEl = document.getElementById(targetId);
  if (targetEl) {
    targetEl.scrollIntoView({ behavior: 'instant', block: 'start' });
    targetEl.focus({ preventScroll: true });
    if (window.history?.replaceState && !window.location.search) {
      window.history.replaceState(null, '', '#' + targetId);
    }
  }
}

function initImageFallbacks() {
  document.addEventListener(
    'error',
    (event) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement)) return;
      const fallback = target.dataset.fallbackSrc;
      if (!fallback || target.dataset.fallbackApplied === 'true') return;
      target.dataset.fallbackApplied = 'true';
      target.src = fallback;
    },
    true
  );
}

function initDrawers() {
  document.querySelectorAll('dialog.drawer').forEach((dialog) => {
    initDialogLightDismiss(dialog);
  });
}

function bootShell() {
  initMobileNavDrawer();
  initHeaderOverflowGuard();
  initScrollSpy();
  initInitialRouteAndLegacyHashes();
  initImageFallbacks();
  initDrawers();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootShell, { once: true });
} else {
  bootShell();
}
