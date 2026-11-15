/**
 * Volunteer & Schedule Page Controller (/volunteer)
 * Lions District 2-E2 Eyeglass Recycling Center
 *
 * Pure ES module (zero third-party runtime dependencies).
 * Implements:
 * - Safe hydration from SSR JSON (#ercVolunteerEventsData)
 * - Live Watauga workshop hours badge (America/Chicago)
 * - Capturing placeholder fallback for missing/broken event images
 * - Interactive monthly calendar, scope toggle, availability chips & live search
 * - Anchor-positioned / viewport-clamped "Add to Calendar" popovers (.ics + Google Calendar)
 * - Native <dialog closedby="any"> Shift Registration modal -> POST /register-volunteer.php
 * - Native <dialog closedby="any"> Gallery Lightbox + GET /get-gallery-images.php refresh
 */

import { ercIconElement } from '../../vendor/lucide/icons.mjs';

const CHICAGO_TZ = 'America/Chicago';
const FALLBACK_EVENT_IMG = '/images/placeholder-event.svg';
const GALLERY_ENDPOINT = '/get-gallery-images.php';
const REGISTER_ENDPOINT = '/register-volunteer.php';

const MONTH_NAMES = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

const WEEKDAY_NAMES = [
  'Sunday',
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
];

/**
 * Parse a YYYY-MM-DD string into integer parts without timezone shift.
 * Never passes bare date strings into `new Date(...)`.
 *
 * @param {string} iso
 * @returns {{ year: number, month: number, day: number } | null}
 */
export function parseIsoDate(iso) {
  if (typeof iso !== 'string') {
    return null;
  }
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso.trim());
  if (!match) {
    return null;
  }
  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  if (month < 1 || month > 12 || day < 1 || day > 31) {
    return null;
  }
  return { year, month, day };
}

/**
 * Parse YYYY-MM into numeric year/month.
 *
 * @param {string} monthKey
 * @returns {{ year: number, month: number } | null}
 */
export function parseMonthKey(monthKey) {
  if (typeof monthKey !== 'string') {
    return null;
  }
  const match = /^(\d{4})-(\d{2})$/.exec(monthKey.trim());
  if (!match) {
    return null;
  }
  const year = Number(match[1]);
  const month = Number(match[2]);
  if (month < 1 || month > 12) {
    return null;
  }
  return { year, month };
}

/**
 * Shift a YYYY-MM month key by delta months.
 *
 * @param {string} monthKey
 * @param {number} deltaMonths
 * @returns {string}
 */
export function shiftMonthKey(monthKey, deltaMonths) {
  const parsed = parseMonthKey(monthKey) || { year: 2026, month: 1 };
  const total = parsed.year * 12 + (parsed.month - 1) + deltaMonths;
  const nextYear = Math.floor(total / 12);
  const nextMonth = ((total % 12) + 12) % 12 + 1;
  return `${String(nextYear).padStart(4, '0')}-${String(nextMonth).padStart(2, '0')}`;
}

/**
 * Format YYYY-MM as "Month Year" cleanly without timezone ambiguity.
 *
 * @param {string} monthKey
 * @returns {string}
 */
export function formatMonthHeading(monthKey) {
  const parsed = parseMonthKey(monthKey);
  if (!parsed) {
    return monthKey;
  }
  return `${MONTH_NAMES[parsed.month - 1]} ${parsed.year}`;
}

/**
 * Return weekday index (0=Sun..6=Sat) for any Gregorian date cleanly in UTC.
 *
 * @param {number} year
 * @param {number} month
 * @param {number} day
 * @returns {number}
 */
export function getWeekdayIndex(year, month, day) {
  return new Date(Date.UTC(year, month - 1, day)).getUTCDay();
}

/**
 * Return number of days in a calendar month.
 *
 * @param {number} year
 * @param {number} month
 * @returns {number}
 */
export function getDaysInMonth(year, month) {
  return new Date(Date.UTC(year, month, 0)).getUTCDate();
}

/**
 * Compute live America/Chicago date and Saturday open status.
 * Walk-in hours: Saturdays 9:00 AM - 3:00 PM Central Time.
 *
 * @param {Date} [now]
 * @returns {{ todayIso: string, weekday: string, hour: number, minute: number, isOpenNow: boolean, label: string }}
 */
export function getWataugaOpenState(now = new Date()) {
  try {
    const parts = new Intl.DateTimeFormat('en-US', {
      timeZone: CHICAGO_TZ,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      weekday: 'short',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).formatToParts(now);

    const map = {};
    for (const part of parts) {
      if (part.type !== 'literal') {
        map[part.type] = part.value;
      }
    }

    const hourNum = Number(map.hour) === 24 ? 0 : Number(map.hour);
    const minuteNum = Number(map.minute);
    const todayIso = `${map.year}-${map.month}-${map.day}`;
    const isSaturday = map.weekday === 'Sat';
    const isOpenNow = isSaturday && hourNum >= 9 && hourNum < 15;

    return {
      todayIso,
      weekday: map.weekday || '',
      hour: hourNum,
      minute: minuteNum,
      isOpenNow,
      label: isOpenNow
        ? 'Open now until 3:00 PM CT · Saturdays 9:00 AM – 3:00 PM'
        : 'Saturdays 9:00 AM – 3:00 PM CT (Mon–Fri by appointment)',
    };
  } catch {
    return {
      todayIso: '',
      weekday: '',
      hour: 0,
      minute: 0,
      isOpenNow: false,
      label: 'Saturdays 9:00 AM – 3:00 PM CT (Mon–Fri by appointment)',
    };
  }
}

/**
 * Format a "12:00 PM - 3:00 PM" style window into [startHHMMSS, endHHMMSS].
 *
 * @param {string} timeStr
 * @returns {[string, string]}
 */
export function parseTimeWindow(timeStr) {
  if (typeof timeStr !== 'string') {
    return ['090000', '120000'];
  }
  const matches = [...timeStr.matchAll(/(\d{1,2}):(\d{2})\s*(AM|PM)/gi)];
  const toHms = (m) => {
    let h = Number(m[1]);
    const min = Number(m[2]);
    const mer = m[3].toUpperCase();
    if (mer === 'PM' && h !== 12) {
      h += 12;
    } else if (mer === 'AM' && h === 12) {
      h = 0;
    }
    return `${String(h).padStart(2, '0')}${String(min).padStart(2, '0')}00`;
  };
  if (matches.length >= 2) {
    return [toHms(matches[0]), toHms(matches[1])];
  }
  if (matches.length === 1) {
    const start = toHms(matches[0]);
    const endHour = Math.min(23, Number(start.slice(0, 2)) + 3);
    return [start, `${String(endHour).padStart(2, '0')}${start.slice(2, 4)}00`];
  }
  return ['090000', '120000'];
}

/**
 * Convert PHP/ISO time ("120000" or "12:00:00") to compact "120000" for RFC 5545 / Google Calendar.
 *
 * @param {string | undefined} hms
 * @param {string} fallback
 * @returns {string}
 */
function compactHms(hms, fallback) {
  if (typeof hms === 'string') {
    if (/^\d{6}$/.test(hms)) {
      return hms;
    }
    if (/^\d{2}:\d{2}:\d{2}$/.test(hms)) {
      return hms.replace(/:/g, '');
    }
  }
  return fallback;
}

/**
 * Escape text according to RFC 5545 (iCalendar TEXT value).
 *
 * @param {string} text
 * @returns {string}
 */
export function escapeIcsText(text) {
  return String(text ?? '')
    .replace(/\\/g, '\\\\')
    .replace(/\r?\n/g, '\\n')
    .replace(/;/g, '\\;')
    .replace(/,/g, '\\,');
}

/**
 * Fold an iCalendar content line at <= 75 UTF-16 code units with CRLF + SPACE.
 *
 * @param {string} line
 * @returns {string}
 */
function foldIcsLine(line) {
  const maxLen = 73;
  if (line.length <= maxLen) {
    return line;
  }
  const chunks = [];
  let cursor = 0;
  while (cursor < line.length) {
    const chunk = line.slice(cursor, cursor + (cursor === 0 ? maxLen : maxLen - 1));
    chunks.push(cursor === 0 ? chunk : ` ${chunk}`);
    cursor += chunk.length;
  }
  return chunks.join('\r\n');
}

/**
 * Build an RFC 5545 .ics calendar payload pinned to America/Chicago.
 *
 * @param {object} ev
 * @returns {string}
 */
export function buildIcsContent(ev) {
  const parsedDate = parseIsoDate(ev?.date || '');
  const ymd = parsedDate
    ? `${String(parsedDate.year).padStart(4, '0')}${String(parsedDate.month).padStart(2, '0')}${String(parsedDate.day).padStart(2, '0')}`
    : '20260110';

  const [fallbackStart, fallbackEnd] = parseTimeWindow(ev?.time || '');
  const startCompact = compactHms(ev?.startTime, fallbackStart);
  const endCompact = compactHms(ev?.endTime, fallbackEnd);

  const now = new Date();
  const stamp = [
    now.getUTCFullYear(),
    String(now.getUTCMonth() + 1).padStart(2, '0'),
    String(now.getUTCDate()).padStart(2, '0'),
    'T',
    String(now.getUTCHours()).padStart(2, '0'),
    String(now.getUTCMinutes()).padStart(2, '0'),
    String(now.getUTCSeconds()).padStart(2, '0'),
    'Z',
  ].join('');

  const arrivalNote =
    'Parking is free in front of 5621 Bunker Blvd, Watauga, TX 76148. ' +
    'Follow the wooden walkway along the east side of the building to the rear entrance.';
  const description = [ev?.description, arrivalNote].filter(Boolean).join('\n\n');

  const lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Lions District 2-E2 ERC//Volunteer Schedule//EN',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'BEGIN:VTIMEZONE',
    'TZID:America/Chicago',
    'X-LIC-LOCATION:America/Chicago',
    'BEGIN:DAYLIGHT',
    'TZOFFSETFROM:-0600',
    'TZOFFSETTO:-0500',
    'TZNAME:CDT',
    'DTSTART:19700308T020000',
    'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU',
    'END:DAYLIGHT',
    'BEGIN:STANDARD',
    'TZOFFSETFROM:-0500',
    'TZOFFSETTO:-0600',
    'TZNAME:CST',
    'DTSTART:19701101T020000',
    'RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU',
    'END:STANDARD',
    'END:VTIMEZONE',
    'BEGIN:VEVENT',
    `UID:volunteer-${ev?.id ?? 'session'}-${ymd}@2e2erc.org`,
    `DTSTAMP:${stamp}`,
    `DTSTART;TZID=America/Chicago:${ymd}T${startCompact}`,
    `DTEND;TZID=America/Chicago:${ymd}T${endCompact}`,
    `SUMMARY:${escapeIcsText(ev?.title || 'Volunteer Session - 2-E2 ERC')}`,
    `LOCATION:${escapeIcsText(ev?.location || '5621 Bunker Blvd, Watauga, TX 76148')}`,
    `DESCRIPTION:${escapeIcsText(description)}`,
    'END:VEVENT',
    'END:VCALENDAR',
  ];

  return `${lines.map(foldIcsLine).join('\r\n')}\r\n`;
}

/**
 * Derive a clean Google Calendar template URL pinned to America/Chicago.
 *
 * @param {object} ev
 * @returns {string}
 */
export function buildGoogleCalendarUrl(ev) {
  if (typeof ev?.gcalUrl === 'string' && ev.gcalUrl.startsWith('https://')) {
    return ev.gcalUrl;
  }
  const parsedDate = parseIsoDate(ev?.date || '');
  const ymd = parsedDate
    ? `${String(parsedDate.year).padStart(4, '0')}${String(parsedDate.month).padStart(2, '0')}${String(parsedDate.day).padStart(2, '0')}`
    : '20260110';
  const [fallbackStart, fallbackEnd] = parseTimeWindow(ev?.time || '');
  const startCompact = compactHms(ev?.startTime, fallbackStart);
  const endCompact = compactHms(ev?.endTime, fallbackEnd);

  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: ev?.title || 'Volunteer Session',
    dates: `${ymd}T${startCompact}/${ymd}T${endCompact}`,
    ctz: CHICAGO_TZ,
    details: [
      ev?.description || '',
      'Arrival: Free parking in front; follow the wooden walkway along the east side of the building to the rear entrance.',
    ]
      .filter(Boolean)
      .join('\n\n'),
    location: ev?.location || '5621 Bunker Blvd, Watauga, TX 76148',
  });
  return `https://calendar.google.com/calendar/render?${params.toString()}`;
}

/**
 * Convert a gallery file path into a clean human-readable caption.
 * Matches server-side logic without unsafe HTML injection.
 *
 * @param {string} path
 * @returns {string}
 */
export function formatGalleryCaption(path) {
  const filename = String(path || '').split('/').pop() || '';
  const stem = filename.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
  if (!stem) {
    return 'Volunteer Workshop Photo';
  }
  const acronyms = {
    ncl: 'NCL',
    saso: 'SASO',
    bsa: 'BSA',
    erc: 'ERC',
    d2e2: 'D2E2',
    tx: 'TX',
    '2e2erc': '2-E2 ERC',
  };
  return stem
    .split(/\s+/)
    .map((word) => {
      const lower = word.toLowerCase();
      if (acronyms[lower]) {
        return acronyms[lower];
      }
      if (/^\d+(st|nd|rd|th)$/i.test(lower)) {
        return lower;
      }
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
}

/* =========================================================
   Top-Layer Dialog & Popover Infrastructure
   ========================================================= */

let cachedSupportsTopLayerExit = null;

function canTransitionDisplay() {
  try {
    const el = document.createElement('div');
    el.style.cssText = 'transition: display 1s allow-discrete !important';
    const parts = el.style.transition.split(/\s+/);
    return parts.some((p) => p === 'display') && parts.some((p) => p === 'allow-discrete');
  } catch {
    return false;
  }
}

function supportsTopLayerExit() {
  if (cachedSupportsTopLayerExit === null) {
    cachedSupportsTopLayerExit =
      typeof CSS !== 'undefined' &&
      typeof CSS.supports === 'function' &&
      CSS.supports('overlay', 'auto') &&
      canTransitionDisplay();
  }
  return cachedSupportsTopLayerExit;
}

/**
 * Close a modal <dialog> cleanly, coordinating CSS top-layer exit transitions.
 *
 * @param {HTMLDialogElement} dialog
 * @param {string} [returnValue]
 */
function closeTopLayer(dialog, returnValue = '') {
  if (!dialog || !dialog.open) {
    return;
  }
  if (!supportsTopLayerExit()) {
    dialog.close(returnValue);
    return;
  }
  if (dialog.hasAttribute('data-closing')) {
    return;
  }
  dialog.setAttribute('data-closing', '');
  const panelAnimations =
    typeof dialog.getAnimations === 'function' ? dialog.getAnimations() : [];
  Promise.allSettled(panelAnimations.map((a) => a.finished)).then(() => {
    dialog.removeAttribute('data-closing');
    if (dialog.open) {
      dialog.close(returnValue);
    }
  });
}

/**
 * Attach light-dismiss behavior to a modal <dialog>:
 * - Always intercept `cancel` when top-layer exit animation is supported
 * - Add outside-bounding-rect `pointerdown`/`pointerup` light dismiss ONLY when
 *   native `closedBy` is unsupported by the browser
 *
 * @param {HTMLDialogElement} dialog
 */
function wireDialogDismiss(dialog) {
  if (!dialog) {
    return;
  }

  dialog.addEventListener('cancel', (event) => {
    if (supportsTopLayerExit()) {
      event.preventDefault();
      closeTopLayer(dialog);
    }
  });

  dialog.querySelectorAll('[data-dialog-close]').forEach((btn) => {
    btn.addEventListener('click', () => closeTopLayer(dialog));
  });

  const supportsClosedBy =
    typeof HTMLDialogElement !== 'undefined' &&
    'closedBy' in HTMLDialogElement.prototype;

  if (!supportsClosedBy) {
    let downOutside = false;

    const isOutsideDialogBox = (clientX, clientY) => {
      const rect = dialog.getBoundingClientRect();
      if (rect.width === 0 && rect.height === 0) {
        return false;
      }
      return (
        clientX < rect.left ||
        clientX > rect.right ||
        clientY < rect.top ||
        clientY > rect.bottom
      );
    };

    dialog.addEventListener('pointerdown', (event) => {
      if (event.button !== 0 && event.pointerType === 'mouse') {
        downOutside = false;
        return;
      }
      downOutside = isOutsideDialogBox(event.clientX, event.clientY);
    });

    dialog.addEventListener('pointerup', (event) => {
      const upOutside = isOutsideDialogBox(event.clientX, event.clientY);
      const shouldDismiss = downOutside && upOutside;
      downOutside = false;
      if (shouldDismiss) {
        closeTopLayer(dialog);
      }
    });
  }
}

/* =========================================================
   Main Page Controller
   ========================================================= */

function initVolunteerPage() {
  const payloadEl = document.getElementById('ercVolunteerEventsData');
  if (!payloadEl) {
    return;
  }

  let initialData = {};
  try {
    initialData = JSON.parse(payloadEl.textContent || '{}');
  } catch {
    initialData = {};
  }

  const eventsList = Array.isArray(initialData.events) ? initialData.events : [];
  const eventMap = new Map(eventsList.map((ev) => [Number(ev.id), ev]));
  let galleryItems = Array.isArray(initialData.gallery) ? initialData.gallery : [];

  const hoursState = getWataugaOpenState();
  const effectiveToday = hoursState.todayIso || initialData.today || '';

  const state = {
    scope: initialData.defaultScope === 'upcoming' ? 'upcoming' : 'all',
    availability: 'all',
    searchQuery: '',
    calendarMonth: initialData.defaultMonth || (effectiveToday ? effectiveToday.slice(0, 7) : '2026-01'),
    selectedDate: '',
    filterByCalMonth: false,
    activeEventId: null,
    activeGalleryIndex: 0,
    isSubmittingReg: false,
    lastRegTrigger: null,
    lastGalleryTrigger: null,
  };

  /* ----- Hero Live Hours Indicator ----- */
  const hoursBadge = document.getElementById('wataugaHoursBadge');
  const hoursText = document.getElementById('wataugaHoursText');
  if (hoursBadge && hoursText) {
    hoursBadge.classList.toggle('is-live-open', hoursState.isOpenNow);
    hoursText.textContent = hoursState.label;
  }

  /* ----- Capturing Image Error Fallback ----- */
  const applyEventImageFallback = (img) => {
    if (!(img instanceof HTMLImageElement)) {
      return;
    }
    const fallback = img.dataset.fallback;
    if (!fallback || img.dataset.fallbackApplied === '1') {
      return;
    }
    img.dataset.fallbackApplied = '1';
    img.src = fallback;
    const mediaBox = img.closest('.vol-event-card__media');
    if (mediaBox) {
      mediaBox.classList.add('is-logo');
    }
  };

  document.addEventListener(
    'error',
    (event) => {
      if (event.target instanceof HTMLImageElement && event.target.dataset.fallback) {
        applyEventImageFallback(event.target);
      }
    },
    true
  );

  document.querySelectorAll('img[data-fallback]').forEach((img) => {
    if (img instanceof HTMLImageElement && img.complete && img.naturalWidth === 0) {
      applyEventImageFallback(img);
    }
  });

  /* ----- Schedule Filtering & Calendar Rendering ----- */
  const scopeUpcomingBtn = document.getElementById('scopeUpcomingBtn');
  const scopeAllBtn = document.getElementById('scopeAllBtn');
  const upcomingCountPill = document.getElementById('upcomingCountPill');
  const allCountPill = document.getElementById('allCountPill');
  const filterChips = [...document.querySelectorAll('.vol-chip[data-filter]')];
  const searchInput = document.getElementById('eventSearchInput');

  const calPrevBtn = document.getElementById('calPrevBtn');
  const calNextBtn = document.getElementById('calNextBtn');
  const calMonthHeading = document.getElementById('calMonthHeading');
  const monthJumpBtns = [...document.querySelectorAll('.vol-month-jump[data-jump-month]')];
  const calBody = document.getElementById('volunteerCalendarBody');
  const filterByCalMonthCheck = document.getElementById('filterByCalMonthCheck');
  const clearDayFilterBtn = document.getElementById('clearDayFilterBtn');

  const eventsSummaryEl = document.getElementById('eventsFilterSummary');
  const emptyStateEl = document.getElementById('eventsEmptyState');
  const emptyHeadingEl = document.getElementById('eventsEmptyHeading');
  const emptyCopyEl = document.getElementById('eventsEmptyCopy');
  const resetFiltersBtn = document.getElementById('resetFiltersBtn');
  const eventsListContainer = document.getElementById('volunteerEventsList');

  const eventCardEls = new Map();
  if (eventsListContainer) {
    eventsListContainer.querySelectorAll('.vol-event-item[data-event-id]').forEach((el) => {
      const id = Number(el.getAttribute('data-event-id'));
      if (!Number.isNaN(id)) {
        eventCardEls.set(id, el);
      }
    });
  }

  function isEventPast(ev) {
    if (effectiveToday && typeof ev.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(ev.date)) {
      return ev.date < effectiveToday;
    }
    return Boolean(ev.isPast);
  }

  function syncScopeButtons() {
    const upcomingCount = eventsList.filter((ev) => !isEventPast(ev)).length;
    if (upcomingCountPill) {
      upcomingCountPill.textContent = String(upcomingCount);
    }
    if (allCountPill) {
      allCountPill.textContent = String(eventsList.length);
    }
    if (scopeUpcomingBtn) {
      scopeUpcomingBtn.setAttribute('aria-pressed', state.scope === 'upcoming' ? 'true' : 'false');
    }
    if (scopeAllBtn) {
      scopeAllBtn.setAttribute('aria-pressed', state.scope === 'all' ? 'true' : 'false');
    }
  }

  function syncMonthJumpButtons() {
    monthJumpBtns.forEach((btn) => {
      const targetMonth = btn.getAttribute('data-jump-month');
      const isCurrent = targetMonth === state.calendarMonth;
      btn.classList.toggle('is-active', isCurrent);
      btn.setAttribute('aria-pressed', isCurrent ? 'true' : 'false');
    });
  }

  function renderCalendar() {
    if (!calBody) {
      return;
    }
    if (calMonthHeading) {
      calMonthHeading.textContent = formatMonthHeading(state.calendarMonth);
    }
    syncMonthJumpButtons();

    const parsedMonth = parseMonthKey(state.calendarMonth) || { year: 2026, month: 1 };
    const { year, month } = parsedMonth;
    const firstDow = getWeekdayIndex(year, month, 1);
    const daysInMonth = getDaysInMonth(year, month);
    const totalCells = Math.ceil((firstDow + daysInMonth) / 7) * 7;

    const countsByDate = new Map();
    for (const ev of eventsList) {
      if (state.scope === 'upcoming' && isEventPast(ev)) {
        continue;
      }
      countsByDate.set(ev.date, (countsByDate.get(ev.date) || 0) + 1);
    }

    const fragment = document.createDocumentFragment();
    let row = null;

    for (let i = 0; i < totalCells; i += 1) {
      if (i % 7 === 0) {
        row = document.createElement('tr');
        fragment.appendChild(row);
      }

      const dayNum = i - firstDow + 1;
      const td = document.createElement('td');

      if (dayNum < 1 || dayNum > daysInMonth) {
        td.className = 'vol-cal-cell vol-cal-cell--empty';
        row.appendChild(td);
        continue;
      }

      td.className = 'vol-cal-cell';
      const iso = `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
      const count = countsByDate.get(iso) || 0;
      const isSat = i % 7 === 6;
      const isToday = effectiveToday !== '' && iso === effectiveToday;
      const isSelected = state.selectedDate === iso;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'vol-cal-day';
      if (count > 0) {
        btn.classList.add('has-events');
      } else {
        btn.disabled = true;
      }
      if (isSat) {
        btn.classList.add('is-saturday');
      }
      if (isToday) {
        btn.classList.add('is-today');
      }

      btn.dataset.calDate = iso;
      btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

      const dowName = WEEKDAY_NAMES[i % 7];
      const monthName = MONTH_NAMES[month - 1];
      const baseLabel = `${dowName}, ${monthName} ${dayNum}, ${year}`;
      btn.setAttribute(
        'aria-label',
        count > 0
          ? `${baseLabel} — ${count} scheduled session${count > 1 ? 's' : ''}`
          : baseLabel
      );

      const numSpan = document.createElement('span');
      numSpan.className = 'vol-cal-day__num vol-mono';
      numSpan.textContent = String(dayNum);
      btn.appendChild(numSpan);

      if (count > 0) {
        const dotSpan = document.createElement('span');
        dotSpan.className = 'vol-cal-day__dot';
        dotSpan.setAttribute('aria-hidden', 'true');
        dotSpan.textContent = String(count);
        btn.appendChild(dotSpan);
      }

      td.appendChild(btn);
      row.appendChild(td);
    }

    calBody.replaceChildren(fragment);

    if (clearDayFilterBtn) {
      clearDayFilterBtn.hidden = state.selectedDate === '';
    }
  }

  function eventMatchesFilters(ev) {
    if (state.scope === 'upcoming' && isEventPast(ev)) {
      return false;
    }

    if (state.availability === 'open-seats') {
      const full = ev.maxPeople > 0 && ev.people >= ev.maxPeople;
      if (full) {
        return false;
      }
    } else if (state.availability === 'open-session') {
      const hay = `${ev.title} ${ev.description}`.toLowerCase();
      if (!hay.includes('open') && !hay.includes('2nd saturday') && !hay.includes('saturday')) {
        return false;
      }
    }

    if (state.selectedDate && ev.date !== state.selectedDate) {
      return false;
    }

    if (!state.selectedDate && state.filterByCalMonth && ev.monthKey !== state.calendarMonth) {
      return false;
    }

    if (state.searchQuery.trim() !== '') {
      const q = state.searchQuery.trim().toLowerCase();
      const searchCorpus = [
        ev.title,
        ev.formattedDate,
        ev.date,
        ev.time,
        ev.location,
        ev.description,
        ev.contact,
      ]
        .join(' ')
        .toLowerCase();
      if (!searchCorpus.includes(q)) {
        return false;
      }
    }

    return true;
  }

  function applyScheduleFilters() {
    syncScopeButtons();

    let visibleCount = 0;
    for (const ev of eventsList) {
      const cardWrapper = eventCardEls.get(Number(ev.id));
      if (!cardWrapper) {
        continue;
      }
      const matches = eventMatchesFilters(ev);
      cardWrapper.hidden = !matches;
      if (matches) {
        visibleCount += 1;
      }
    }

    if (eventsSummaryEl) {
      const countSpan = document.createElement('span');
      countSpan.className = 'vol-mono';
      countSpan.textContent = String(visibleCount);

      let suffix = ' scheduled session(s)';
      if (state.selectedDate) {
        suffix += ` on ${state.selectedDate}`;
      } else if (state.filterByCalMonth) {
        suffix += ` in ${formatMonthHeading(state.calendarMonth)}`;
      } else if (state.scope === 'upcoming') {
        suffix = ' upcoming session(s)';
      }

      eventsSummaryEl.replaceChildren(
        document.createTextNode('Showing '),
        countSpan,
        document.createTextNode(`${suffix}.`)
      );
    }

    if (emptyStateEl) {
      emptyStateEl.hidden = visibleCount > 0;
      if (visibleCount === 0 && emptyHeadingEl && emptyCopyEl) {
        if (state.scope === 'upcoming' && !state.selectedDate && !state.searchQuery.trim()) {
          emptyHeadingEl.textContent = 'No future online shifts are currently posted';
          emptyCopyEl.textContent =
            'Walk-in volunteers are always welcome most Saturdays from 9:00 AM – 3:00 PM at 5621 Bunker Blvd, Watauga, TX 76148, or switch to All Scheduled Sessions to review posted workshop dates.';
        } else {
          emptyHeadingEl.textContent = 'No matching sessions in this view';
          emptyCopyEl.textContent =
            'Clear your date or search filter, or join Saturday walk-ins from 9:00 AM – 3:00 PM at 5621 Bunker Blvd, Watauga, TX 76148.';
        }
      }
    }
  }

  scopeUpcomingBtn?.addEventListener('click', () => {
    state.scope = 'upcoming';
    state.selectedDate = '';
    const firstUpcoming = eventsList.find((ev) => !isEventPast(ev));
    if (firstUpcoming) {
      state.calendarMonth = firstUpcoming.monthKey;
    }
    renderCalendar();
    applyScheduleFilters();
  });

  scopeAllBtn?.addEventListener('click', () => {
    state.scope = 'all';
    state.selectedDate = '';
    renderCalendar();
    applyScheduleFilters();
  });

  filterChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      state.availability = chip.getAttribute('data-filter') || 'all';
      filterChips.forEach((c) => {
        c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
      });
      applyScheduleFilters();
    });
  });

  searchInput?.addEventListener('input', () => {
    state.searchQuery = searchInput.value || '';
    applyScheduleFilters();
  });

  calPrevBtn?.addEventListener('click', () => {
    state.calendarMonth = shiftMonthKey(state.calendarMonth, -1);
    if (state.selectedDate && !state.selectedDate.startsWith(state.calendarMonth)) {
      state.selectedDate = '';
    }
    renderCalendar();
    applyScheduleFilters();
  });

  calNextBtn?.addEventListener('click', () => {
    state.calendarMonth = shiftMonthKey(state.calendarMonth, 1);
    if (state.selectedDate && !state.selectedDate.startsWith(state.calendarMonth)) {
      state.selectedDate = '';
    }
    renderCalendar();
    applyScheduleFilters();
  });

  monthJumpBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetMonth = btn.getAttribute('data-jump-month');
      if (!targetMonth) {
        return;
      }
      state.scope = 'all';
      state.selectedDate = '';
      state.calendarMonth = targetMonth;
      renderCalendar();
      applyScheduleFilters();
    });
  });

  calBody?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    const dayBtn = target.closest('.vol-cal-day[data-cal-date]');
    if (!(dayBtn instanceof HTMLButtonElement) || dayBtn.disabled) {
      return;
    }
    const iso = dayBtn.dataset.calDate || '';
    state.selectedDate = state.selectedDate === iso ? '' : iso;
    renderCalendar();
    applyScheduleFilters();
  });

  filterByCalMonthCheck?.addEventListener('change', () => {
    state.filterByCalMonth = Boolean(filterByCalMonthCheck.checked);
    state.selectedDate = '';
    renderCalendar();
    applyScheduleFilters();
  });

  clearDayFilterBtn?.addEventListener('click', () => {
    state.selectedDate = '';
    renderCalendar();
    applyScheduleFilters();
  });

  resetFiltersBtn?.addEventListener('click', () => {
    state.scope = 'all';
    state.availability = 'all';
    state.searchQuery = '';
    state.selectedDate = '';
    state.filterByCalMonth = false;
    if (eventsList.length > 0) {
      state.calendarMonth = eventsList[eventsList.length - 1].monthKey;
    }
    if (searchInput) {
      searchInput.value = '';
    }
    if (filterByCalMonthCheck) {
      filterByCalMonthCheck.checked = false;
    }
    filterChips.forEach((chip) => {
      chip.setAttribute('aria-pressed', chip.dataset.filter === 'all' ? 'true' : 'false');
    });
    renderCalendar();
    applyScheduleFilters();
  });

  /* ----- Popover Anchor & Viewport-Aware Fallback ----- */
  const supportsPopover =
    typeof HTMLElement !== 'undefined' &&
    Object.hasOwn(HTMLElement.prototype, 'popover');
  const supportsAnchorCss =
    typeof CSS !== 'undefined' &&
    typeof CSS.supports === 'function' &&
    CSS.supports('(anchor-name: --vol-a) and (position-area: block-end)');

  function positionPopoverNearTrigger(popover, trigger) {
    if (!popover || !trigger) {
      return;
    }
    const triggerRect = trigger.getBoundingClientRect();
    const popRect = popover.getBoundingClientRect();
    const popWidth = popRect.width || 232;
    const popHeight = popRect.height || 104;
    const gap = 6;
    const margin = 12;

    let left = triggerRect.right - popWidth;
    if (left < margin) {
      left = Math.max(margin, triggerRect.left);
    }
    if (left + popWidth > window.innerWidth - margin) {
      left = Math.max(margin, window.innerWidth - popWidth - margin);
    }

    let top = triggerRect.bottom + gap;
    if (top + popHeight > window.innerHeight - margin && triggerRect.top - popHeight - gap >= margin) {
      top = triggerRect.top - popHeight - gap;
    }

    popover.style.margin = '0';
    popover.style.inset = 'auto';
    popover.style.position = 'fixed';
    popover.style.left = `${Math.round(left)}px`;
    popover.style.top = `${Math.round(top)}px`;
  }

  document.querySelectorAll('.vol-cal-trigger[data-popover-trigger]').forEach((trigger) => {
    const popId = trigger.getAttribute('data-popover-trigger');
    const popover = popId ? document.getElementById(popId) : null;
    if (!popover) {
      return;
    }

    if (supportsAnchorCss) {
      const anchorId = `--vol-cal-anchor-${popId}`;
      trigger.style.anchorName = anchorId;
      popover.style.positionAnchor = anchorId;
    }

    if (supportsPopover) {
      popover.addEventListener('toggle', (event) => {
        if (event.newState === 'open' && !supportsAnchorCss) {
          positionPopoverNearTrigger(popover, trigger);
        }
      });
    } else {
      // Fallback for browsers without native Popover API
      popover.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      trigger.addEventListener('click', () => {
        const willOpen = popover.hidden;
        document.querySelectorAll('.vol-cal-popover').forEach((other) => {
          other.hidden = true;
          other.classList.remove(':popover-open');
        });
        document.querySelectorAll('.vol-cal-trigger[aria-expanded]').forEach((btn) => {
          btn.setAttribute('aria-expanded', 'false');
        });
        if (willOpen) {
          popover.hidden = false;
          popover.classList.add(':popover-open');
          trigger.setAttribute('aria-expanded', 'true');
          positionPopoverNearTrigger(popover, trigger);
        }
      });
    }
  });

  if (!supportsPopover) {
    document.addEventListener('click', (event) => {
      const target = event.target;
      if (target instanceof Element && target.closest('.vol-cal-menu-wrap')) {
        return;
      }
      document.querySelectorAll('.vol-cal-popover').forEach((pop) => {
        pop.hidden = true;
        pop.classList.remove(':popover-open');
      });
      document.querySelectorAll('.vol-cal-trigger[aria-expanded]').forEach((btn) => {
        btn.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ----- Download .ics Calendar Helper ----- */
  function triggerIcsDownload(ev) {
    if (!ev) {
      return;
    }
    const icsText = buildIcsContent(ev);
    const blob = new Blob([icsText], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const slug = String(ev.title || 'volunteer-shift')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 48);

    const link = document.createElement('a');
    link.href = url;
    link.download = `${slug || 'volunteer-shift'}-${ev.date || 'schedule'}.ics`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 10000);
  }

  /* ----- Event Capacity Live UI Sync ----- */
  function updateEventCardCapacity(ev) {
    const wrapper = eventCardEls.get(Number(ev.id));
    if (!wrapper) {
      return;
    }
    const card = wrapper.querySelector('.vol-event-card');
    const isFull = ev.maxPeople > 0 && ev.people >= ev.maxPeople;
    const past = isEventPast(ev);
    const remaining = ev.maxPeople > 0 ? Math.max(0, ev.maxPeople - ev.people) : null;
    ev.remaining = remaining;
    ev.isFull = isFull;

    if (card) {
      card.classList.toggle('is-full', isFull);
    }

    const capTextEl = wrapper.querySelector('[data-capacity-text]');
    if (capTextEl) {
      capTextEl.textContent =
        ev.maxPeople > 0
          ? `${ev.people} / ${ev.maxPeople} (${remaining} ${remaining === 1 ? 'spot' : 'spots'} left)`
          : `${ev.people} registered`;
    }

    const barEl = wrapper.querySelector('[data-capacity-bar]');
    if (barEl && ev.maxPeople > 0) {
      barEl.setAttribute('aria-valuenow', String(ev.people));
      const fill = barEl.querySelector('.vol-meter__fill');
      if (fill instanceof HTMLElement) {
        const pct = Math.min(100, Math.round((ev.people / ev.maxPeople) * 100));
        fill.style.setProperty('--fill-pct', `${pct}%`);
      }
    }

    const badge = wrapper.querySelector('.vol-state-badge');
    if (badge && !past) {
      badge.className = 'vol-state-badge';
      if (isFull) {
        badge.classList.add('vol-state-badge--full');
        badge.textContent = 'Event Full';
      } else if (remaining !== null && remaining <= 3) {
        badge.classList.add('vol-state-badge--warn');
        badge.textContent = 'Filling Fast';
      } else {
        badge.classList.add('vol-state-badge--open');
        badge.textContent = 'Open';
      }
    }

    const regBtn = wrapper.querySelector('button[data-action="open-register"]');
    if (regBtn instanceof HTMLButtonElement) {
      regBtn.disabled = isFull;
      const iconName = isFull ? 'circle-alert' : 'user-plus';
      const labelText = isFull ? 'Event Full' : 'Register';
      const labelSpan = document.createElement('span');
      labelSpan.textContent = labelText;
      regBtn.replaceChildren(
        ercIconElement(iconName, { class: 'vol-icon-xs' }),
        labelSpan
      );
    }
  }

  /* ----- Card Delegation (.ics + Open Registration) ----- */
  eventsListContainer?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    const icsBtn = target.closest('button[data-action="download-ics"]');
    if (icsBtn instanceof HTMLButtonElement) {
      const eventId = Number(icsBtn.dataset.eventId);
      const ev = eventMap.get(eventId);
      if (ev) {
        triggerIcsDownload(ev);
      }
      const popover = icsBtn.closest('.vol-cal-popover');
      if (popover) {
        if (supportsPopover && typeof popover.hidePopover === 'function') {
          try {
            popover.hidePopover();
          } catch {
            // already hidden
          }
        } else {
          popover.hidden = true;
          popover.classList.remove(':popover-open');
        }
      }
      return;
    }

    const regBtn = target.closest('button[data-action="open-register"]');
    if (regBtn instanceof HTMLButtonElement && !regBtn.disabled) {
      const eventId = Number(regBtn.dataset.eventId);
      const ev = eventMap.get(eventId);
      if (ev) {
        openRegistrationModal(ev, regBtn);
      }
    }
  });

  /* ----- Native <dialog closedby="any"> Shift Registration Modal ----- */
  const regDialog = document.getElementById('volunteerRegDialog');
  const regForm = document.getElementById('volunteerSignupForm');
  const regEventIdInput = document.getElementById('regEventId');
  const regWebsiteUrlInput = document.getElementById('regWebsiteUrl');
  const regNameInput = document.getElementById('regName');
  const regEmailInput = document.getElementById('regEmail');
  const regPhoneInput = document.getElementById('regPhone');
  const regFormStatus = document.getElementById('regFormStatus');
  const regSubmitBtn = document.getElementById('regSubmitBtn');
  const regSpinner = document.getElementById('regSpinner');
  const regSubmitText = document.getElementById('regSubmitText');

  const regEventTitleEl = document.getElementById('regEventTitle');
  const regEventDateTimeEl = document.getElementById('regEventDateTime');
  const regEventLocationEl = document.getElementById('regEventLocation');
  const regEventSeatsEl = document.getElementById('regEventSeats');

  const regSuccessView = document.getElementById('regSuccessView');
  const regSuccessHeading = document.getElementById('regSuccessHeading');
  const regSuccessMessage = document.getElementById('regSuccessMessage');
  const regSuccessIcsBtn = document.getElementById('regSuccessIcsBtn');
  const regSuccessGcalLink = document.getElementById('regSuccessGcalLink');

  let regLiveAnnouncer = null;
  let announceTimer = null;
  const regFields = [regNameInput, regEmailInput, regPhoneInput].filter(
    (el) => el instanceof HTMLInputElement
  );
  const touchedFields = new Set();

  if (regDialog) {
    wireDialogDismiss(regDialog);

    // Persistent empty live region kept always rendered inside dialog
    regLiveAnnouncer = document.createElement('div');
    regLiveAnnouncer.className = 'vol-visually-hidden';
    regLiveAnnouncer.setAttribute('aria-live', 'polite');
    regLiveAnnouncer.setAttribute('aria-atomic', 'true');
    regDialog.appendChild(regLiveAnnouncer);

    regDialog.addEventListener('close', () => {
      state.isSubmittingReg = false;
      const trigger = state.lastRegTrigger;
      state.lastRegTrigger = null;
      if (trigger instanceof HTMLElement && trigger.isConnected && !trigger.hasAttribute('disabled')) {
        trigger.focus();
      }
    });
  }

  regFields.forEach((field) => {
    field.dataset.baseDescribedby = field.getAttribute('aria-describedby') || '';
  });

  function announcePolite(message, delayMs = 450) {
    if (!regLiveAnnouncer) {
      return;
    }
    if (announceTimer) {
      window.clearTimeout(announceTimer);
      announceTimer = null;
    }
    regLiveAnnouncer.textContent = '';
    announceTimer = window.setTimeout(() => {
      if (regLiveAnnouncer) {
        regLiveAnnouncer.textContent = message;
      }
    }, delayMs);
  }

  function isFieldValid(field) {
    const value = field.value.trim();
    if (field === regNameInput) {
      return value.length >= 1 && value.length <= 120;
    }
    if (field === regEmailInput) {
      if (value.length === 0 || value.length > 254) {
        return false;
      }
      return field.checkValidity();
    }
    if (field === regPhoneInput) {
      return value.length <= 40;
    }
    return field.checkValidity();
  }

  function syncFieldValidationUI(field, showErrors) {
    const valid = isFieldValid(field);
    const shouldFlag = showErrors && !valid;
    const baseDescribed = field.dataset.baseDescribedby || '';
    const errId = field.dataset.error || '';

    field.classList.toggle('user-invalid-fallback', shouldFlag);
    field.setAttribute('aria-invalid', shouldFlag ? 'true' : 'false');
    if (shouldFlag && errId) {
      field.setAttribute('aria-describedby', `${baseDescribed} ${errId}`.trim());
    } else if (baseDescribed) {
      field.setAttribute('aria-describedby', baseDescribed);
    } else {
      field.removeAttribute('aria-describedby');
    }
    return valid;
  }

  function setFormStatus(message, isError = true) {
    if (!regFormStatus) {
      return;
    }
    if (!message) {
      regFormStatus.hidden = true;
      regFormStatus.replaceChildren();
      return;
    }
    regFormStatus.hidden = false;
    const span = document.createElement('span');
    span.textContent = message;
    regFormStatus.replaceChildren(
      ercIconElement(isError ? 'circle-alert' : 'circle-check-big', { class: 'vol-icon-sm' }),
      span
    );
  }

  function setSubmittingState(submitting) {
    state.isSubmittingReg = submitting;
    if (regSubmitBtn) {
      regSubmitBtn.disabled = submitting;
    }
    if (regSpinner) {
      regSpinner.hidden = !submitting;
    }
    if (regSubmitText) {
      regSubmitText.textContent = submitting ? 'Submitting...' : 'Confirm Registration';
    }
  }

  function resetRegistrationForm() {
    touchedFields.clear();
    if (regForm) {
      regForm.reset();
      regForm.hidden = false;
    }
    if (regSuccessView) {
      regSuccessView.hidden = true;
    }
    regFields.forEach((field) => syncFieldValidationUI(field, false));
    setFormStatus('');
    setSubmittingState(false);
  }

  function updateModalSessionHeader(ev) {
    if (regEventIdInput) {
      regEventIdInput.value = String(ev.id);
    }
    if (regEventTitleEl) {
      regEventTitleEl.textContent = ev.title;
    }
    if (regEventDateTimeEl) {
      regEventDateTimeEl.textContent = `${ev.formattedDate} · ${ev.time}`;
    }
    if (regEventLocationEl) {
      regEventLocationEl.textContent = ev.location;
    }
    if (regEventSeatsEl) {
      if (ev.maxPeople > 0) {
        const rem = Math.max(0, ev.maxPeople - ev.people);
        regEventSeatsEl.textContent = `${rem} of ${ev.maxPeople} remaining (${ev.people} registered)`;
      } else {
        regEventSeatsEl.textContent = `${ev.people} registered`;
      }
    }
  }

  function openRegistrationModal(ev, triggerEl) {
    if (!regDialog) {
      return;
    }
    state.activeEventId = Number(ev.id);
    state.lastRegTrigger = triggerEl || null;
    resetRegistrationForm();
    updateModalSessionHeader(ev);
    regDialog.removeAttribute('data-closing');
    if (!regDialog.open) {
      regDialog.showModal();
    }
    if (regNameInput) {
      regNameInput.focus();
    }
  }

  regFields.forEach((field) => {
    field.addEventListener('blur', () => {
      if (field.required || field.value.trim() !== '' || touchedFields.has(field)) {
        touchedFields.add(field);
        const valid = syncFieldValidationUI(field, true);
        if (!valid) {
          const errEl = field.dataset.error ? document.getElementById(field.dataset.error) : null;
          if (errEl?.textContent) {
            announcePolite(errEl.textContent.trim(), 200);
          }
        }
      }
    });

    field.addEventListener('input', () => {
      if (touchedFields.has(field)) {
        syncFieldValidationUI(field, true);
      }
      if (regFormStatus && !regFormStatus.hidden) {
        setFormStatus('');
      }
    });
  });

  regForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (state.isSubmittingReg) {
      return;
    }

    const ev = eventMap.get(Number(state.activeEventId));
    if (!ev) {
      setFormStatus('Unable to identify this session. Please close and select the session again.');
      return;
    }

    let firstInvalid = null;
    let invalidCount = 0;

    for (const field of regFields) {
      touchedFields.add(field);
      const valid = syncFieldValidationUI(field, true);
      if (!valid) {
        invalidCount += 1;
        if (!firstInvalid) {
          firstInvalid = field;
        }
      }
    }

    if (firstInvalid) {
      firstInvalid.focus();
      const firstErrEl = firstInvalid.dataset.error
        ? document.getElementById(firstInvalid.dataset.error)
        : null;
      const errDetail = firstErrEl?.textContent?.trim() || 'Please check the highlighted field.';
      announcePolite(
        `Registration form has ${invalidCount} ${invalidCount === 1 ? 'error' : 'errors'}. ${errDetail}`,
        450
      );
      return;
    }

    setFormStatus('');
    setSubmittingState(true);

    const payload = {
      eventId: Number(ev.id),
      name: regNameInput ? regNameInput.value.trim() : '',
      email: regEmailInput ? regEmailInput.value.trim() : '',
      phone: regPhoneInput ? regPhoneInput.value.trim() : '',
      website_url: regWebsiteUrlInput ? regWebsiteUrlInput.value : '',
    };

    try {
      const response = await fetch(REGISTER_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify(payload),
      });

      let body = {};
      try {
        body = await response.json();
      } catch {
        body = {};
      }

      if (response.status === 429) {
        const msg =
          body.message ||
          'Too many registration requests were sent from your connection. Please wait a minute and try again.';
        setFormStatus(msg);
        announcePolite(msg, 200);
        setSubmittingState(false);
        return;
      }

      if (!response.ok || !body.success) {
        const errMsg =
          body.message ||
          'We could not complete your registration. Please review your details or call us directly.';
        setFormStatus(errMsg);
        announcePolite(errMsg, 200);

        if (typeof body.newCount === 'number' && !Number.isNaN(body.newCount)) {
          ev.people = Math.max(0, body.newCount);
          updateEventCardCapacity(ev);
          updateModalSessionHeader(ev);
        } else if ((response.status === 409 || response.status === 400) && /(?:full|capacity)/i.test(errMsg) && ev.maxPeople > 0) {
          ev.people = ev.maxPeople;
          updateEventCardCapacity(ev);
          updateModalSessionHeader(ev);
        }

        setSubmittingState(false);
        return;
      }

      if (typeof body.newCount === 'number' && !Number.isNaN(body.newCount)) {
        ev.people = Math.max(0, body.newCount);
      } else {
        ev.people = Number(ev.people || 0) + 1;
      }

      updateEventCardCapacity(ev);
      updateModalSessionHeader(ev);
      setSubmittingState(false);

      if (regForm) {
        regForm.hidden = true;
      }
      if (regSuccessView) {
        regSuccessView.hidden = false;
      }
      if (regSuccessMessage) {
        regSuccessMessage.textContent =
          body.message ||
          `Your spot for "${ev.title}" on ${ev.formattedDate} (${ev.time}) is confirmed.`;
      }
      if (regSuccessGcalLink) {
        regSuccessGcalLink.href = buildGoogleCalendarUrl(ev);
      }
      if (regSuccessHeading) {
        regSuccessHeading.focus();
      }
      announcePolite(
        `Registration confirmed for ${ev.title}. You can now download an .ics reminder or add the shift to Google Calendar.`,
        250
      );
    } catch {
      const netMsg =
        'Network error while submitting your registration. Please check your connection and try again.';
      setFormStatus(netMsg);
      announcePolite(netMsg, 200);
      setSubmittingState(false);
    }
  });

  regSuccessIcsBtn?.addEventListener('click', () => {
    const ev = eventMap.get(Number(state.activeEventId));
    if (ev) {
      triggerIcsDownload(ev);
    }
  });

  /* ----- Community Photo Gallery & Native Lightbox ----- */
  const galleryGrid = document.getElementById('volunteerGalleryGrid');
  const galleryTotalBadge = document.getElementById('galleryTotalBadge');
  const galleryStatusMsg = document.getElementById('galleryStatusMsg');
  const refreshGalleryBtn = document.getElementById('refreshGalleryBtn');

  const lightboxDialog = document.getElementById('volunteerLightbox');
  const lightboxCounter = document.getElementById('lightboxCounter');
  const lightboxCaption = document.getElementById('lightboxCaption');
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxPrevBtn = document.getElementById('lightboxPrevBtn');
  const lightboxNextBtn = document.getElementById('lightboxNextBtn');

  if (lightboxDialog) {
    wireDialogDismiss(lightboxDialog);

    lightboxDialog.addEventListener('keydown', (event) => {
      if (!lightboxDialog.open || event.altKey || event.ctrlKey || event.metaKey) {
        return;
      }
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        stepLightbox(-1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        stepLightbox(1);
      } else if (event.key === 'Home') {
        event.preventDefault();
        showLightboxIndex(0);
      } else if (event.key === 'End') {
        event.preventDefault();
        showLightboxIndex(Math.max(0, galleryItems.length - 1));
      }
    });

    lightboxDialog.addEventListener('close', () => {
      const trigger = state.lastGalleryTrigger;
      state.lastGalleryTrigger = null;
      if (trigger instanceof HTMLElement && trigger.isConnected) {
        trigger.focus();
      }
    });
  }

  function showLightboxIndex(index) {
    if (galleryItems.length === 0) {
      return;
    }
    const total = galleryItems.length;
    const normalized = ((index % total) + total) % total;
    state.activeGalleryIndex = normalized;
    const item = galleryItems[normalized];

    if (lightboxCounter) {
      lightboxCounter.textContent = `${normalized + 1} of ${total}`;
    }
    if (lightboxCaption) {
      lightboxCaption.textContent = item.caption;
    }
    if (lightboxImg) {
      lightboxImg.src = item.url;
      lightboxImg.alt = item.caption;
    }
    const singleItem = total <= 1;
    if (lightboxPrevBtn) {
      lightboxPrevBtn.disabled = singleItem;
    }
    if (lightboxNextBtn) {
      lightboxNextBtn.disabled = singleItem;
    }
  }

  function stepLightbox(delta) {
    showLightboxIndex(state.activeGalleryIndex + delta);
  }

  function openLightbox(index, triggerEl) {
    if (!lightboxDialog || galleryItems.length === 0) {
      return;
    }
    state.lastGalleryTrigger = triggerEl || null;
    showLightboxIndex(index);
    lightboxDialog.removeAttribute('data-closing');
    if (!lightboxDialog.open) {
      lightboxDialog.showModal();
    }
  }

  lightboxPrevBtn?.addEventListener('click', () => stepLightbox(-1));
  lightboxNextBtn?.addEventListener('click', () => stepLightbox(1));

  galleryGrid?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    const cardBtn = target.closest('.vol-gallery-card[data-gallery-index]');
    if (!(cardBtn instanceof HTMLButtonElement)) {
      return;
    }
    const idx = Number(cardBtn.dataset.galleryIndex);
    if (!Number.isNaN(idx)) {
      openLightbox(idx, cardBtn);
    }
  });

  function rebuildGalleryGrid(items) {
    if (!galleryGrid) {
      return;
    }
    const fragment = document.createDocumentFragment();

    items.forEach((item, idx) => {
      const li = document.createElement('li');
      li.className = 'vol-gallery-item';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'vol-gallery-card';
      btn.dataset.galleryIndex = String(idx);
      btn.dataset.gallerySrc = item.url;
      btn.dataset.galleryCaption = item.caption;
      btn.setAttribute('aria-label', `Open photo: ${item.caption}`);

      const media = document.createElement('span');
      media.className = 'vol-gallery-card__media';

      const img = document.createElement('img');
      img.src = item.url;
      img.alt = item.caption;
      img.loading = 'lazy';
      img.decoding = 'async';
      img.width = 640;
      img.height = 480;

      const zoom = document.createElement('span');
      zoom.className = 'vol-gallery-card__zoom';
      zoom.setAttribute('aria-hidden', 'true');
      zoom.appendChild(ercIconElement('zoom-in', { class: 'vol-icon-sm' }));

      media.append(img, zoom);

      const captionSpan = document.createElement('span');
      captionSpan.className = 'vol-gallery-card__caption';
      captionSpan.textContent = item.caption;

      btn.append(media, captionSpan);
      li.appendChild(btn);
      fragment.appendChild(li);
    });

    galleryGrid.replaceChildren(fragment);

    if (galleryTotalBadge) {
      galleryTotalBadge.textContent = String(items.length);
    }
  }

  refreshGalleryBtn?.addEventListener('click', async () => {
    if (!(refreshGalleryBtn instanceof HTMLButtonElement) || refreshGalleryBtn.disabled) {
      return;
    }
    refreshGalleryBtn.disabled = true;
    const prevLabel = refreshGalleryBtn.querySelector('span');
    if (prevLabel) {
      prevLabel.textContent = 'Refreshing...';
    }
    if (galleryStatusMsg) {
      galleryStatusMsg.hidden = true;
      galleryStatusMsg.textContent = '';
    }

    try {
      const res = await fetch(GALLERY_ENDPOINT, {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      const data = await res.json();
      const rawPaths = Array.isArray(data)
        ? data
        : Array.isArray(data?.images)
          ? data.images
          : [];

      const normalized = rawPaths
        .map((p) => String(p || '').replace(/^\/+/, '').trim())
        .filter((p) => p !== '')
        .map((relPath, idx) => ({
          index: idx,
          path: relPath,
          url: `/${relPath}`,
          caption: formatGalleryCaption(relPath),
        }));

      if (normalized.length > 0) {
        galleryItems = normalized;
        rebuildGalleryGrid(galleryItems);
        if (galleryStatusMsg) {
          galleryStatusMsg.hidden = false;
          galleryStatusMsg.textContent = `Updated photo gallery: ${galleryItems.length} workshop and mission photos loaded.`;
        }
      } else if (galleryStatusMsg) {
        galleryStatusMsg.hidden = false;
        galleryStatusMsg.textContent = 'No gallery images were returned by the server.';
      }
    } catch {
      if (galleryStatusMsg) {
        galleryStatusMsg.hidden = false;
        galleryStatusMsg.textContent =
          'Could not refresh gallery photos right now. Showing the loaded photos below.';
      }
    } finally {
      refreshGalleryBtn.disabled = false;
      if (prevLabel) {
        prevLabel.textContent = 'Refresh Photos';
      }
    }
  });

  /* ----- Initial Render ----- */
  renderCalendar();
  applyScheduleFilters();
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVolunteerPage);
  } else {
    initVolunteerPage();
  }
}
