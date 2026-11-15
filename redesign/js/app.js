/**
 * v3 Shell, Interactive Drawers, Volunteer Hub & Contact Controller
 * - Native <dialog closedby="any"> mobile nav + pointer-drag-safe fallback
 * - Delegated [data-dialog-open="<id>"] / [data-dialog-close] drawer controller
 * - ResizeObserver header overflow guard (200% zoom & narrow viewports)
 * - IntersectionObserver scroll-spy syncing .is-active & aria-current
 * - Initial route deep-link & legacy SPA #hash bridge
 * - Delegated [data-prefill-topic] smooth scroll to #contact
 * - Volunteer calendar, session cards, .ics/GCal export, registration modal & 17-photo lightbox
 * - Contact copy buttons, character counter, topic aliases & fetch submission
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
  if (dialog.dataset.lightDismissInit === 'true') return;
  dialog.dataset.lightDismissInit = 'true';

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

const dialogOpenerMap = new WeakMap();

export function openDialogWithReturnFocus(dialog, opener = document.activeElement) {
  if (!(dialog instanceof HTMLDialogElement)) return;
  initDialogLightDismiss(dialog);
  if (opener instanceof HTMLElement && !dialog.contains(opener)) {
    dialogOpenerMap.set(dialog, opener);
  }
  if (!dialog.open) {
    dialog.showModal();
  }
}

function attachDrawerFocusRestore(dialog) {
  if (!(dialog instanceof HTMLDialogElement)) return;
  if (dialog.dataset.focusRestoreInit === 'true') return;
  dialog.dataset.focusRestoreInit = 'true';

  dialog.addEventListener('close', () => {
    const opener = dialogOpenerMap.get(dialog);
    if (opener instanceof HTMLElement && document.contains(opener) && opener.offsetParent !== null) {
      opener.focus({ preventScroll: true });
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
    attachDrawerFocusRestore(dialog);
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const openTrigger = target.closest('[data-dialog-open]');
    if (openTrigger instanceof HTMLElement) {
      const dialogId = (openTrigger.getAttribute('data-dialog-open') || '').trim();
      const dialog = dialogId ? document.getElementById(dialogId) : null;
      if (dialog instanceof HTMLDialogElement) {
        event.preventDefault();
        attachDrawerFocusRestore(dialog);
        openDialogWithReturnFocus(dialog, openTrigger);
      }
      return;
    }

    const closeTrigger = target.closest('[data-dialog-close]');
    if (closeTrigger instanceof HTMLElement) {
      const explicitId = (closeTrigger.getAttribute('data-dialog-close') || '').trim();
      const dialog = explicitId
        ? document.getElementById(explicitId)
        : closeTrigger.closest('dialog');
      if (dialog instanceof HTMLDialogElement) {
        event.preventDefault();
        closeTopLayer(dialog);
      }
    }
  });
}

/* ==========================================================================
   Topic Alias Mapping & [data-prefill-topic] Smooth Scroll
   ========================================================================== */

const TOPIC_ALIAS_MAP = {
  general: 'general',
  question: 'general',
  inquiry: 'general',
  'donation-items': 'donation-items',
  donate: 'donation-items',
  items: 'donation-items',
  eyeglasses: 'donation-items',
  'donation-money': 'donation-money',
  money: 'donation-money',
  'donate-money': 'donation-money',
  check: 'donation-money',
  'need-collection-boxes': 'need-collection-boxes',
  bins: 'need-collection-boxes',
  boxes: 'need-collection-boxes',
  'collection-boxes': 'need-collection-boxes',
  bin: 'need-collection-boxes',
  volunteer: 'volunteer',
  solo: 'volunteer',
  'group-volunteer': 'group-volunteer',
  group: 'group-volunteer',
  groups: 'group-volunteer',
  partnership: 'partnership',
  partner: 'partnership',
  mission: 'partnership',
  missions: 'partnership',
  awards: 'partnership',
  award: 'partnership',
  other: 'other',
};

function resolveCanonicalTopic(raw) {
  const key = String(raw || '').trim().toLowerCase();
  return TOPIC_ALIAS_MAP[key] || '';
}

function applyContactTopic(rawTopic) {
  const select = document.getElementById('contactTopic');
  if (!(select instanceof HTMLSelectElement)) return false;
  const canonical = resolveCanonicalTopic(rawTopic);
  if (!canonical) return false;
  const optionExists = Array.from(select.options).some((opt) => opt.value === canonical);
  if (!optionExists) return false;
  select.value = canonical;
  return true;
}

function initPrefillTopicLinks() {
  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const trigger = target.closest('[data-prefill-topic]');
    if (!(trigger instanceof HTMLElement)) return;

    const rawTopic = trigger.getAttribute('data-prefill-topic') || '';
    const applied = applyContactTopic(rawTopic);
    const contactSection = document.getElementById('contact');
    if (!contactSection) return;

    event.preventDefault();
    contactSection.scrollIntoView({
      behavior: prefersReducedMotion() ? 'auto' : 'smooth',
      block: 'start',
    });

    const focusTarget = applied
      ? document.getElementById('contactTopic')
      : document.getElementById('contactName');
    if (focusTarget instanceof HTMLElement) {
      focusTarget.focus({ preventScroll: true });
    }
  });
}

/* ==========================================================================
   Section 4: Volunteer Calendar, Registration Modal & 17-Photo Lightbox
   ========================================================================== */

function parseTimeRangeTo24h(timeStr) {
  const match = String(timeStr || '').match(
    /(\d{1,2}):(\d{2})\s*(AM|PM)\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)/i
  );
  if (!match) {
    return { start: '090000', end: '120000' };
  }
  const to24 = (hRaw, minStr, ampm) => {
    let h = parseInt(hRaw, 10);
    const u = ampm.toUpperCase();
    if (u === 'PM' && h < 12) h += 12;
    else if (u === 'AM' && h === 12) h = 0;
    return String(h).padStart(2, '0') + String(minStr).padStart(2, '0') + '00';
  };
  return {
    start: to24(match[1], match[2], match[3]),
    end: to24(match[4], match[5], match[6]),
  };
}

function buildGoogleCalendarUrl(ev) {
  const { start, end } = parseTimeRangeTo24h(ev.time);
  const compact = String(ev.date || '').replace(/-/g, '');
  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: `${ev.title || 'Volunteer Session'} — Lions 2-E2 ERC`,
    dates: `${compact}T${start}/${compact}T${end}`,
    ctz: 'America/Chicago',
    location: ev.location || '5621 Bunker Blvd, Watauga, TX 76148',
    details:
      ev.description ||
      'Lions District 2-E2 Eyeglass Recycling Center volunteer shift. Access via east-side wooden walkway to rear entrance.',
  });
  return `https://calendar.google.com/calendar/render?${params.toString()}`;
}

function escapeIcsText(str) {
  return String(str || '')
    .replace(/\\/g, '\\\\')
    .replace(/;/g, '\\;')
    .replace(/,/g, '\\,')
    .replace(/\r?\n/g, '\\n');
}

function downloadEventIcs(ev) {
  const { start, end } = parseTimeRangeTo24h(ev.time);
  const compact = String(ev.date || '').replace(/-/g, '');
  const now = new Date();
  const stamp =
    now.getUTCFullYear() +
    String(now.getUTCMonth() + 1).padStart(2, '0') +
    String(now.getUTCDate()).padStart(2, '0') +
    'T' +
    String(now.getUTCHours()).padStart(2, '0') +
    String(now.getUTCMinutes()).padStart(2, '0') +
    String(now.getUTCSeconds()).padStart(2, '0') +
    'Z';

  const lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Lions District 2-E2 ERC//Volunteer Schedule//EN',
    'CALSCALE:GREGORIAN',
    'BEGIN:VEVENT',
    `UID:erc-vol-${ev.id}-${compact}@2e2erc.org`,
    `DTSTAMP:${stamp}`,
    `DTSTART;TZID=America/Chicago:${compact}T${start}`,
    `DTEND;TZID=America/Chicago:${compact}T${end}`,
    `SUMMARY:${escapeIcsText(`${ev.title || 'Volunteer Session'} — Lions 2-E2 ERC`)}`,
    `LOCATION:${escapeIcsText(ev.location || '5621 Bunker Blvd, Watauga, TX 76148')}`,
    `DESCRIPTION:${escapeIcsText(ev.description || 'Saturday volunteer workshop in Watauga. Enter via east-side wooden walkway.')}`,
    'END:VEVENT',
    'END:VCALENDAR',
  ];

  const blob = new Blob([lines.join('\r\n') + '\r\n'], { type: 'text/calendar;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `lions-2e2-volunteer-${ev.id}.ics`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function formatReadableDate(ymd) {
  const parts = String(ymd || '').split('-').map(Number);
  if (parts.length !== 3 || parts.some(Number.isNaN)) return ymd;
  const dt = new Date(parts[0], parts[1] - 1, parts[2]);
  return dt.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function initVolunteerSection() {
  const eventsDataEl = document.getElementById('volunteerEventsData');
  const calGrid = document.getElementById('volCalGrid');
  const calMonthLabel = document.getElementById('volCalMonthLabel');
  const calPrevBtn = document.getElementById('volCalPrevBtn');
  const calNextBtn = document.getElementById('volCalNextBtn');
  const showAllResetBtn = document.getElementById('volShowAllBtn');
  const filterStatusEl = document.getElementById('volFilterStatus');
  const sessionsSummaryEl = document.getElementById('volSessionsSummary');
  const sessionsListEl = document.getElementById('volSessionsList');
  const toggleAllBtn = document.getElementById('volToggleAllBtn');

  let events = [];
  if (eventsDataEl) {
    try {
      const parsed = JSON.parse(eventsDataEl.textContent || '[]');
      if (Array.isArray(parsed)) events = parsed;
    } catch {
      events = [];
    }
  }

  const eventsById = new Map();
  const eventsByDate = new Map();
  for (const ev of events) {
    eventsById.set(Number(ev.id), ev);
    const list = eventsByDate.get(ev.date) || [];
    list.push(ev);
    eventsByDate.set(ev.date, list);
  }

  // Calendar state: start on first scheduled event's month
  const initialDateParts =
    events.length > 0 ? String(events[0].date || '').split('-').map(Number) : [];
  const nowDate = new Date();
  let viewYear =
    initialDateParts.length === 3 && !Number.isNaN(initialDateParts[0])
      ? initialDateParts[0]
      : nowDate.getFullYear();
  let viewMonth =
    initialDateParts.length === 3 && !Number.isNaN(initialDateParts[1])
      ? initialDateParts[1] - 1
      : nowDate.getMonth();
  let selectedDate = null;
  let showAllDates = false;
  const INITIAL_VISIBLE = 4;

  const syncSessionCards = () => {
    if (!sessionsListEl) return;
    const cards = Array.from(sessionsListEl.querySelectorAll('.vol-session-card'));
    let visibleCount = 0;

    if (selectedDate) {
      sessionsListEl.dataset.expanded = 'false';
      for (const card of cards) {
        const match = card.dataset.eventDate === selectedDate;
        card.hidden = !match;
        if (match) visibleCount++;
      }
      if (toggleAllBtn) toggleAllBtn.hidden = true;
      if (showAllResetBtn) {
        showAllResetBtn.classList.remove('is-active');
        showAllResetBtn.setAttribute('aria-pressed', 'false');
      }
      if (filterStatusEl) {
        filterStatusEl.textContent = `${visibleCount} on ${formatReadableDate(selectedDate)}`;
      }
      if (sessionsSummaryEl) {
        sessionsSummaryEl.textContent = `${visibleCount} shift${visibleCount === 1 ? '' : 's'} on ${formatReadableDate(selectedDate)}`;
      }
    } else {
      sessionsListEl.dataset.expanded = showAllDates ? 'true' : 'false';
      cards.forEach((card, idx) => {
        const visible = showAllDates || idx < INITIAL_VISIBLE;
        card.hidden = !visible;
        if (visible) visibleCount++;
      });
      if (toggleAllBtn) {
        toggleAllBtn.hidden = cards.length <= INITIAL_VISIBLE;
        toggleAllBtn.setAttribute('aria-expanded', showAllDates ? 'true' : 'false');
        toggleAllBtn.textContent = showAllDates
          ? 'Show Next 4 Shifts'
          : `Show All ${cards.length} Scheduled Dates`;
      }
      if (showAllResetBtn) {
        showAllResetBtn.classList.add('is-active');
        showAllResetBtn.setAttribute('aria-pressed', 'true');
      }
      if (filterStatusEl) {
        filterStatusEl.textContent = showAllDates
          ? `Showing all ${cards.length}`
          : `Showing next ${visibleCount} of ${cards.length}`;
      }
      if (sessionsSummaryEl) {
        sessionsSummaryEl.textContent = `${cards.length} scheduled date${cards.length === 1 ? '' : 's'}`;
      }
    }
  };

  const renderCalendar = () => {
    if (!calGrid || !calMonthLabel) return;
    const monthDt = new Date(viewYear, viewMonth, 1);
    calMonthLabel.textContent = monthDt.toLocaleDateString('en-US', {
      month: 'long',
      year: 'numeric',
    });

    const firstWeekday = monthDt.getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

    const frag = document.createDocumentFragment();
    for (let i = 0; i < firstWeekday; i++) {
      const pad = document.createElement('span');
      pad.className = 'vol-cal-pad';
      pad.setAttribute('aria-hidden', 'true');
      frag.appendChild(pad);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const ymd = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const dayEvents = eventsByDate.get(ymd) || [];
      const hasEvents = dayEvents.length > 0;
      const isSelected = selectedDate === ymd;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'vol-cal-day';
      if (hasEvents) btn.classList.add('has-events');
      if (isSelected) btn.classList.add('is-selected');
      btn.dataset.date = ymd;
      btn.disabled = !hasEvents;
      btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      btn.setAttribute(
        'aria-label',
        hasEvents
          ? `${formatReadableDate(ymd)} — ${dayEvents.length} volunteer session${dayEvents.length === 1 ? '' : 's'}`
          : `${formatReadableDate(ymd)} — no scheduled sessions`
      );

      const numSpan = document.createElement('span');
      numSpan.textContent = String(day);
      btn.appendChild(numSpan);

      if (hasEvents) {
        const dot = document.createElement('span');
        dot.className = 'vol-cal-day__dot';
        dot.setAttribute('aria-hidden', 'true');
        btn.appendChild(dot);
      }

      frag.appendChild(btn);
    }

    calGrid.replaceChildren(frag);
  };

  calGrid?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const btn = target.closest('.vol-cal-day');
    if (!(btn instanceof HTMLButtonElement) || btn.disabled) return;
    const date = btn.dataset.date || '';
    if (!date) return;

    selectedDate = selectedDate === date ? null : date;
    renderCalendar();
    syncSessionCards();
  });

  calPrevBtn?.addEventListener('click', () => {
    viewMonth -= 1;
    if (viewMonth < 0) {
      viewMonth = 11;
      viewYear -= 1;
    }
    renderCalendar();
  });

  calNextBtn?.addEventListener('click', () => {
    viewMonth += 1;
    if (viewMonth > 11) {
      viewMonth = 0;
      viewYear += 1;
    }
    renderCalendar();
  });

  showAllResetBtn?.addEventListener('click', () => {
    selectedDate = null;
    if (events.length > 0) {
      const firstParts = String(events[0].date || '').split('-').map(Number);
      if (firstParts.length === 3 && !Number.isNaN(firstParts[0])) {
        viewYear = firstParts[0];
        viewMonth = firstParts[1] - 1;
      }
    }
    renderCalendar();
    syncSessionCards();
  });

  toggleAllBtn?.addEventListener('click', () => {
    showAllDates = !showAllDates;
    syncSessionCards();
  });

  renderCalendar();
  syncSessionCards();

  // Position [popover="auto"] calendar menus cleanly next to their trigger
  document.querySelectorAll('.vol-cal-popover').forEach((pop) => {
    pop.addEventListener('toggle', (event) => {
      if (event.newState !== 'open') return;
      const trigger = document.querySelector(`[popovertarget="${pop.id}"]`);
      if (!(trigger instanceof HTMLElement)) return;
      const rect = trigger.getBoundingClientRect();
      const popRect = pop.getBoundingClientRect();
      const left = Math.max(8, Math.min(rect.left, window.innerWidth - popRect.width - 8));
      const topBelow = rect.bottom + 6;
      const top =
        topBelow + popRect.height <= window.innerHeight - 8
          ? topBelow
          : Math.max(8, rect.top - popRect.height - 6);
      pop.style.position = 'fixed';
      pop.style.left = `${Math.round(left)}px`;
      pop.style.top = `${Math.round(top)}px`;
    });
  });

  // Delegated .ics downloads on session cards
  sessionsListEl?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const icsBtn = target.closest('.js-vol-ics');
    if (icsBtn instanceof HTMLElement) {
      const id = Number(icsBtn.dataset.eventId);
      const ev = eventsById.get(id);
      if (ev) {
        downloadEventIcs(ev);
      }
      const pop = icsBtn.closest('[popover]');
      if (pop) closeTopLayer(pop);
    }
  });

  // Helper to update a session card's seat count & capacity UI live
  const updateCardCapacityUI = (eventId, newPeopleCount) => {
    const ev = eventsById.get(Number(eventId));
    if (!ev) return;
    ev.people = newPeopleCount;
    const card = sessionsListEl?.querySelector(`.vol-session-card[data-event-id="${eventId}"]`);
    if (!card) return;

    const maxP = Number(ev.maxPeople || 0);
    const isFull = maxP > 0 && newPeopleCount >= maxP;
    const remaining = maxP > 0 ? Math.max(0, maxP - newPeopleCount) : 0;
    const pct = maxP > 0 ? Math.min(100, Math.round((newPeopleCount / maxP) * 100)) : 0;

    const badge = card.querySelector('.js-vol-seats-badge');
    if (badge) {
      badge.textContent = `${newPeopleCount} / ${maxP} spots`;
      badge.classList.toggle('vol-badge--full', isFull);
      badge.classList.toggle('badge--brand', !isFull);
    }

    const meterFill = card.querySelector('.js-vol-meter-fill');
    if (meterFill instanceof HTMLElement) {
      meterFill.style.setProperty('--vol-fill', `${pct}%`);
    }

    const remEl = card.querySelector('.js-vol-remaining');
    if (remEl) {
      remEl.textContent = isFull ? 'Session full' : `${remaining} spots open`;
    }

    const signupBtn = card.querySelector('.js-vol-signup');
    if (signupBtn instanceof HTMLButtonElement) {
      signupBtn.disabled = isFull;
      if (isFull) {
        signupBtn.setAttribute('aria-disabled', 'true');
        signupBtn.textContent = 'Session Full';
      } else {
        signupBtn.removeAttribute('aria-disabled');
        signupBtn.textContent = 'Sign Up';
      }
    }
  };

  /* ------------------------------------------------------------------------
     Native Registration Modal (#volunteerRegDialog)
     Top-anchored + suppressFieldBlurValidation for 0.00px close top-shift
     ------------------------------------------------------------------------ */
  const regDialog = document.getElementById('volunteerRegDialog');
  const regCloseBtn = document.getElementById('volRegCloseBtn');
  const regSummary = document.getElementById('volRegSummary');
  const regAlert = document.getElementById('volRegAlert');
  const regForm = document.getElementById('volunteerRegForm');
  const regEventIdInput = document.getElementById('regEventId');
  const regNameInput = document.getElementById('regName');
  const regEmailInput = document.getElementById('regEmail');
  const regPhoneInput = document.getElementById('regPhone');
  const regCountInput = document.getElementById('regCount');
  const regWebsiteInput = document.getElementById('regWebsiteUrl');
  const regNameErr = document.getElementById('regNameError');
  const regEmailErr = document.getElementById('regEmailError');
  const regPhoneErr = document.getElementById('regPhoneError');
  const regGroupNote = document.getElementById('regGroupNote');
  const regGroupCountLabel = document.getElementById('regGroupCountLabel');
  const regSubmitBtn = document.getElementById('regSubmitBtn');
  const regSuccessEl = document.getElementById('volRegSuccess');
  const regSuccessMsg = document.getElementById('volRegSuccessMsg');
  const regSuccessIcs = document.getElementById('volRegSuccessIcs');
  const regSuccessGCal = document.getElementById('volRegSuccessGCal');

  let activeRegEvent = null;
  let suppressFieldBlurValidation = false;

  const setFieldError = (input, errEl, message) => {
    if (!errEl) return;
    if (message) {
      errEl.textContent = message;
      errEl.hidden = false;
      input?.setAttribute('aria-invalid', 'true');
    } else {
      errEl.textContent = '';
      errEl.hidden = true;
      input?.removeAttribute('aria-invalid');
    }
  };

  const clearRegMessages = () => {
    if (regAlert) {
      regAlert.textContent = '';
      regAlert.hidden = true;
    }
    setFieldError(regNameInput, regNameErr, '');
    setFieldError(regEmailInput, regEmailErr, '');
    setFieldError(regPhoneInput, regPhoneErr, '');
  };

  const validateRegField = (field) => {
    if (field === regNameInput && regNameInput) {
      const val = regNameInput.value.trim();
      if (!val) {
        setFieldError(regNameInput, regNameErr, 'Please enter your full name.');
        return false;
      }
      if (val.length > 120) {
        setFieldError(regNameInput, regNameErr, 'Name must be 120 characters or fewer.');
        return false;
      }
      setFieldError(regNameInput, regNameErr, '');
      return true;
    }

    if (field === regEmailInput && regEmailInput) {
      const val = regEmailInput.value.trim();
      if (!val || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || val.length > 254) {
        setFieldError(regEmailInput, regEmailErr, 'Please enter a valid email address.');
        return false;
      }
      setFieldError(regEmailInput, regEmailErr, '');
      return true;
    }

    if (field === regPhoneInput && regPhoneInput) {
      const val = regPhoneInput.value.trim();
      if (val.length > 40) {
        setFieldError(regPhoneInput, regPhoneErr, 'Phone number must be 40 characters or fewer.');
        return false;
      }
      setFieldError(regPhoneInput, regPhoneErr, '');
      return true;
    }
    return true;
  };

  if (regCloseBtn) {
    regCloseBtn.addEventListener('pointerdown', () => {
      suppressFieldBlurValidation = true;
    });
    const releaseSuppression = () => {
      setTimeout(() => {
        suppressFieldBlurValidation = false;
      }, 0);
    };
    regCloseBtn.addEventListener('pointerup', releaseSuppression);
    regCloseBtn.addEventListener('pointercancel', releaseSuppression);
  }

  regDialog?.addEventListener('close', () => {
    suppressFieldBlurValidation = false;
  });

  [regNameInput, regEmailInput, regPhoneInput].forEach((input) => {
    input?.addEventListener('blur', () => {
      if (suppressFieldBlurValidation) return;
      validateRegField(input);
    });
  });

  const syncGroupNote = () => {
    if (!regCountInput || !regGroupNote) return;
    const countVal = Math.max(1, Math.min(20, parseInt(regCountInput.value || '1', 10) || 1));
    regGroupNote.hidden = countVal < 2;
    if (regGroupCountLabel) {
      regGroupCountLabel.textContent = String(countVal);
    }
  };

  regCountInput?.addEventListener('input', syncGroupNote);
  regCountInput?.addEventListener('change', syncGroupNote);

  const renderRegSummary = (ev) => {
    if (!regSummary) return;
    const titleEl = document.createElement('p');
    titleEl.className = 'vol-reg-summary__title';
    titleEl.textContent = ev.title || 'Volunteer Session';

    const metaEl = document.createElement('p');
    metaEl.className = 'vol-reg-summary__meta mono';
    const rem = ev.maxPeople > 0 ? Math.max(0, ev.maxPeople - ev.people) : 0;
    metaEl.textContent = `${formatReadableDate(ev.date)} · ${ev.time} · ${rem} of ${ev.maxPeople} spots open`;

    regSummary.replaceChildren(titleEl, metaEl);
  };

  sessionsListEl?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const btn = target.closest('.js-vol-signup');
    if (!(btn instanceof HTMLButtonElement) || btn.disabled) return;
    const eventId = Number(btn.dataset.eventId);
    const ev = eventsById.get(eventId);
    if (!ev || !(regDialog instanceof HTMLDialogElement)) return;

    activeRegEvent = ev;
    suppressFieldBlurValidation = false;
    clearRegMessages();
    if (regForm instanceof HTMLFormElement) {
      regForm.reset();
      regForm.hidden = false;
    }
    if (regSuccessEl) regSuccessEl.hidden = true;
    if (regEventIdInput) regEventIdInput.value = String(ev.id);
    if (regCountInput) regCountInput.value = '1';
    syncGroupNote();
    renderRegSummary(ev);

    openDialogWithReturnFocus(regDialog, btn);
    regNameInput?.focus();
  });

  regForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!activeRegEvent) return;

    const okName = validateRegField(regNameInput);
    const okEmail = validateRegField(regEmailInput);
    const okPhone = validateRegField(regPhoneInput);
    if (!okName || !okEmail || !okPhone) {
      if (!okName) regNameInput?.focus();
      else if (!okEmail) regEmailInput?.focus();
      else regPhoneInput?.focus();
      return;
    }

    if (regAlert) regAlert.hidden = true;
    const originalBtnText = regSubmitBtn ? regSubmitBtn.textContent : 'Confirm Registration';
    if (regSubmitBtn instanceof HTMLButtonElement) {
      regSubmitBtn.disabled = true;
      regSubmitBtn.textContent = 'Submitting...';
    }

    try {
      const payload = {
        eventId: Number(activeRegEvent.id),
        name: (regNameInput?.value || '').trim(),
        email: (regEmailInput?.value || '').trim(),
        phone: (regPhoneInput?.value || '').trim(),
        website_url: (regWebsiteInput?.value || '').trim(),
      };

      const res = await fetch('/register-volunteer.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json().catch(() => ({}));

      if (res.ok && data.success) {
        const newCount =
          typeof data.newCount === 'number'
            ? data.newCount
            : Number(activeRegEvent.people || 0) + 1;
        updateCardCapacityUI(activeRegEvent.id, newCount);
        renderRegSummary(activeRegEvent);

        regForm.hidden = true;
        if (regSuccessEl) regSuccessEl.hidden = false;
        if (regSuccessMsg) {
          const countVal = Math.max(1, parseInt(regCountInput?.value || '1', 10) || 1);
          regSuccessMsg.textContent =
            countVal >= 2
              ? `Spot confirmed for ${payload.name} on ${formatReadableDate(activeRegEvent.date)}. Please also submit Group-Volunteer-Form.pdf or call (817) 710-5403 for your party of ${countVal}.`
              : `Spot confirmed for ${payload.name} on ${formatReadableDate(activeRegEvent.date)} (${activeRegEvent.time}).`;
        }
        if (regSuccessGCal instanceof HTMLAnchorElement) {
          regSuccessGCal.href = buildGoogleCalendarUrl(activeRegEvent);
        }
      } else {
        const msg =
          (data && typeof data.message === 'string' && data.message) ||
          'Unable to complete registration. Please check your details and try again.';
        if (regAlert) {
          regAlert.textContent = msg;
          regAlert.hidden = false;
        }
        if (res.status === 409 && /capacity/i.test(msg)) {
          updateCardCapacityUI(
            activeRegEvent.id,
            Math.max(Number(activeRegEvent.people || 0), Number(activeRegEvent.maxPeople || 0))
          );
          renderRegSummary(activeRegEvent);
        }
      }
    } catch {
      if (regAlert) {
        regAlert.textContent = 'Network error while submitting registration. Please try again.';
        regAlert.hidden = false;
      }
    } finally {
      if (regSubmitBtn instanceof HTMLButtonElement) {
        regSubmitBtn.disabled = false;
        regSubmitBtn.textContent = originalBtnText;
      }
    }
  });

  regSuccessIcs?.addEventListener('click', () => {
    if (activeRegEvent) {
      downloadEventIcs(activeRegEvent);
    }
  });

  /* ------------------------------------------------------------------------
     17-Photo Gallery Lightbox (#volunteerLightbox)
     ------------------------------------------------------------------------ */
  const lightbox = document.getElementById('volunteerLightbox');
  const lbImg = document.getElementById('volLightboxImg');
  const lbCaption = document.getElementById('volLightboxCaption');
  const lbCounter = document.getElementById('volLightboxCounter');
  const lbPrev = document.getElementById('volLightboxPrev');
  const lbNext = document.getElementById('volLightboxNext');
  const lbThumbs = document.getElementById('volLightboxThumbs');
  const galleryDataEl = document.getElementById('volunteerGalleryData');

  let galleryItems = [];
  if (galleryDataEl) {
    try {
      const parsed = JSON.parse(galleryDataEl.textContent || '[]');
      if (Array.isArray(parsed)) galleryItems = parsed;
    } catch {
      galleryItems = [];
    }
  }

  let lbIndex = 0;
  let gallerySynced = false;

  const renderLightboxThumbs = () => {
    if (!lbThumbs) return;
    const frag = document.createDocumentFragment();
    galleryItems.forEach((item, idx) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'vol-lightbox__thumb-btn' + (idx === lbIndex ? ' is-active' : '');
      btn.dataset.thumbIndex = String(idx);
      btn.setAttribute('aria-label', `Photo ${idx + 1}: ${item.caption || 'Workshop photo'}`);

      const img = document.createElement('img');
      img.src = item.src;
      img.alt = '';
      img.loading = 'eager';
      img.decoding = 'async';
      btn.appendChild(img);
      frag.appendChild(btn);
    });
    lbThumbs.replaceChildren(frag);
  };

  const showLightboxSlide = (index) => {
    if (galleryItems.length === 0) return;
    lbIndex = ((index % galleryItems.length) + galleryItems.length) % galleryItems.length;
    const item = galleryItems[lbIndex];

    if (lbImg instanceof HTMLImageElement) {
      lbImg.src = item.src;
      lbImg.alt = item.caption || `Workshop photo ${lbIndex + 1}`;
    }
    if (lbCaption) {
      lbCaption.textContent = item.caption || '';
    }
    if (lbCounter) {
      lbCounter.textContent = `${lbIndex + 1} / ${galleryItems.length}`;
    }
    if (lbThumbs) {
      const buttons = lbThumbs.querySelectorAll('.vol-lightbox__thumb-btn');
      buttons.forEach((btn, idx) => {
        const active = idx === lbIndex;
        btn.classList.toggle('is-active', active);
        if (active) {
          btn.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
      });
    }
  };

  const syncGalleryFromEndpoint = async () => {
    if (gallerySynced) return;
    gallerySynced = true;
    try {
      const res = await fetch('/get-gallery-images.php', { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const paths = await res.json();
      if (!Array.isArray(paths) || paths.length === 0) return;

      const captionByPath = new Map(
        galleryItems.map((it) => [String(it.path || '').toLowerCase(), it.caption])
      );
      galleryItems = paths.map((relPath) => {
        const clean = String(relPath).replace(/^\/+/, '');
        const existingCaption = captionByPath.get(clean.toLowerCase());
        const fileLabel = clean
          .split('/')
          .pop()
          ?.replace(/\.[^.]+$/, '')
          .replace(/[-_]+/g, ' ');
        return {
          path: clean,
          src: '/' + clean,
          caption: existingCaption || `${fileLabel || 'Workshop photo'} — Lions District 2-E2 ERC`,
        };
      });
      renderLightboxThumbs();
      showLightboxSlide(lbIndex);
    } catch {
      // Keep SSR gallery items
    }
  };

  if (lightbox instanceof HTMLDialogElement) {
    renderLightboxThumbs();

    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;
      const trigger = target.closest('[data-gallery-open]');
      if (!(trigger instanceof HTMLElement)) return;
      const startIdx = parseInt(trigger.getAttribute('data-gallery-open') || '0', 10) || 0;
      showLightboxSlide(startIdx);
      openDialogWithReturnFocus(lightbox, trigger);
      syncGalleryFromEndpoint();
    });

    lbPrev?.addEventListener('click', () => showLightboxSlide(lbIndex - 1));
    lbNext?.addEventListener('click', () => showLightboxSlide(lbIndex + 1));

    lbThumbs?.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;
      const btn = target.closest('.vol-lightbox__thumb-btn');
      if (!(btn instanceof HTMLElement)) return;
      const idx = parseInt(btn.dataset.thumbIndex || '0', 10) || 0;
      showLightboxSlide(idx);
    });

    lightbox.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showLightboxSlide(lbIndex - 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        showLightboxSlide(lbIndex + 1);
      }
    });
  }
}

/* ==========================================================================
   Section 6: Contact Copy Buttons, Char Counter, URL Topics & Fetch Form
   ========================================================================== */

function initContactSection() {
  const statusBanner = document.getElementById('contactStatusBanner');
  const statusText = document.getElementById('contactStatusText');
  const statusDismiss = document.getElementById('contactStatusDismiss');
  const copyLive = document.getElementById('contactCopyLive');
  const contactForm = document.getElementById('contactForm');
  const topicSelect = document.getElementById('contactTopic');
  const nameInput = document.getElementById('contactName');
  const emailInput = document.getElementById('contactEmail');
  const phoneInput = document.getElementById('contactPhone');
  const orgInput = document.getElementById('contactOrg');
  const msgInput = document.getElementById('contactMessage');
  const websiteInput = document.getElementById('contactWebsiteUrl');
  const charCounter = document.getElementById('contactCharCounter');
  const submitBtn = document.getElementById('contactSubmitBtn');
  const nameErr = document.getElementById('contactNameError');
  const emailErr = document.getElementById('contactEmailError');
  const msgErr = document.getElementById('contactMessageError');

  // Auto-scroll to status banner if ?status= was returned by classic POST redirect
  const params = new URLSearchParams(window.location.search);
  const urlTopic = params.get('topic') || params.get('subject');
  if (urlTopic) {
    applyContactTopic(urlTopic);
  }

  if (params.has('status') && statusBanner && !statusBanner.hidden) {
    requestAnimationFrame(() => {
      statusBanner.scrollIntoView({ behavior: 'instant', block: 'center' });
      statusBanner.focus({ preventScroll: true });
    });
  }

  statusDismiss?.addEventListener('click', () => {
    if (statusBanner) statusBanner.hidden = true;
  });

  // 1-Click Copy buttons
  document.querySelectorAll('.js-copy-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const value = btn.getAttribute('data-copy-text') || '';
      const label = btn.getAttribute('data-copy-label') || 'value';
      if (!value) return;

      let copied = false;
      try {
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(value);
          copied = true;
        }
      } catch {
        copied = false;
      }

      if (!copied) {
        const tmp = document.createElement('textarea');
        tmp.value = value;
        tmp.setAttribute('readonly', '');
        tmp.style.position = 'fixed';
        tmp.style.opacity = '0';
        document.body.appendChild(tmp);
        tmp.select();
        try {
          copied = document.execCommand('copy');
        } catch {
          copied = false;
        }
        tmp.remove();
      }

      if (copied) {
        const labelSpan = btn.querySelector('span');
        if (labelSpan) {
          const prev = labelSpan.textContent;
          labelSpan.textContent = 'Copied';
          setTimeout(() => {
            labelSpan.textContent = prev;
          }, 1800);
        }
        if (copyLive) {
          copyLive.textContent = `Copied ${label} (${value}) to clipboard.`;
        }
      }
    });
  });

  // Live Character Counter above textarea
  const syncCharCount = () => {
    if (!msgInput || !charCounter) return;
    const len = msgInput.value.length;
    charCounter.textContent = `${len} / 5000`;
  };
  if (msgInput) {
    syncCharCount();
    msgInput.addEventListener('input', syncCharCount);
  }

  const showContactStatus = (isSuccess, message) => {
    if (!statusBanner || !statusText) return;
    statusBanner.classList.toggle('contact-status--success', isSuccess);
    statusBanner.classList.toggle('contact-status--error', !isSuccess);
    statusBanner.setAttribute('role', isSuccess ? 'status' : 'alert');
    statusText.textContent = message;
    statusBanner.hidden = false;
    statusBanner.scrollIntoView({
      behavior: prefersReducedMotion() ? 'auto' : 'smooth',
      block: 'nearest',
    });
  };

  const setContactErr = (input, errEl, msg) => {
    if (!errEl) return;
    if (msg) {
      errEl.textContent = msg;
      errEl.hidden = false;
      input?.setAttribute('aria-invalid', 'true');
    } else {
      errEl.textContent = '';
      errEl.hidden = true;
      input?.removeAttribute('aria-invalid');
    }
  };

  contactForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const nameVal = (nameInput?.value || '').trim();
    const emailVal = (emailInput?.value || '').trim();
    const msgVal = (msgInput?.value || '').trim();
    let valid = true;

    if (!nameVal || nameVal.length > 120) {
      setContactErr(nameInput, nameErr, 'Please enter your name (1–120 characters).');
      valid = false;
    } else {
      setContactErr(nameInput, nameErr, '');
    }

    if (!emailVal || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal) || emailVal.length > 254) {
      setContactErr(emailInput, emailErr, 'Please enter a valid email address.');
      valid = false;
    } else {
      setContactErr(emailInput, emailErr, '');
    }

    if (!msgVal || msgVal.length > 5000) {
      setContactErr(msgInput, msgErr, 'Please enter a message (1–5,000 characters).');
      valid = false;
    } else {
      setContactErr(msgInput, msgErr, '');
    }

    if (!valid) {
      if (nameErr && !nameErr.hidden) nameInput?.focus();
      else if (emailErr && !emailErr.hidden) emailInput?.focus();
      else msgInput?.focus();
      return;
    }

    const orgVal = (orgInput?.value || '').trim();
    let outgoingMessage = msgVal;
    if (orgVal) {
      const prefix = `Organization / Club: ${orgVal}\n\n`;
      if (prefix.length + outgoingMessage.length <= 5000) {
        outgoingMessage = prefix + outgoingMessage;
      }
    }

    const subjectText =
      topicSelect instanceof HTMLSelectElement
        ? topicSelect.options[topicSelect.selectedIndex]?.textContent?.trim() || topicSelect.value
        : 'General Inquiry';

    const labelSpan = submitBtn?.querySelector('span');
    const prevBtnLabel = labelSpan ? labelSpan.textContent : '';
    if (submitBtn instanceof HTMLButtonElement) {
      submitBtn.disabled = true;
      if (labelSpan) labelSpan.textContent = 'Sending...';
    }

    try {
      const res = await fetch('/send-email.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          name: nameVal,
          email: emailVal,
          phone: (phoneInput?.value || '').trim(),
          subject: subjectText.slice(0, 100),
          message: outgoingMessage,
          website_url: (websiteInput?.value || '').trim(),
        }),
      });

      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) {
        showContactStatus(
          true,
          data.message ||
            'Thank you! Your message has been sent. Volunteers typically respond within 24 hours.'
        );
        if (contactForm instanceof HTMLFormElement) {
          contactForm.reset();
        }
        syncCharCount();
      } else {
        showContactStatus(
          false,
          data.message || 'Unable to send your message right now. Please call or email us directly.'
        );
      }
    } catch {
      showContactStatus(
        false,
        'Network error while sending your message. Please call or email us directly.'
      );
    } finally {
      if (submitBtn instanceof HTMLButtonElement) {
        submitBtn.disabled = false;
        if (labelSpan && prevBtnLabel) labelSpan.textContent = prevBtnLabel;
      }
    }
  });
}

function bootShell() {
  initMobileNavDrawer();
  initHeaderOverflowGuard();
  initScrollSpy();
  initInitialRouteAndLegacyHashes();
  initImageFallbacks();
  initDrawers();
  initPrefillTopicLinks();
  initVolunteerSection();
  initContactSection();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootShell, { once: true });
} else {
  bootShell();
}
