/**
 * Editorial pages progressive enhancements for Lions District 2-E2 ERC.
 * Scope: /, /donate, /missions, /about
 * Zero unsafe innerHTML; safe DOM updates and WCAG 2.2 live-region discipline.
 */

function evaluateWataugaStatus(now = new Date()) {
  const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Chicago',
    weekday: 'short',
    hour: 'numeric',
    minute: 'numeric',
    hourCycle: 'h23',
  });

  const parts = {};
  for (const part of formatter.formatToParts(now)) {
    if (part.type !== 'literal') {
      parts[part.type] = part.value;
    }
  }

  const weekday = parts.weekday || '';
  const hour = Number(parts.hour ?? 0) % 24;
  const minute = Number(parts.minute ?? 0);
  const totalMinutes = hour * 60 + minute;

  if (weekday === 'Sat') {
    if (totalMinutes >= 540 && totalMinutes < 900) {
      return {
        state: 'open',
        label: 'Open Today · Saturdays 9:00 AM – 3:00 PM CT',
      };
    }
    if (totalMinutes < 540) {
      return {
        state: 'soon',
        label: 'Opens Today at 9:00 AM – 3:00 PM CT',
      };
    }
    return {
      state: 'closed',
      label: 'Closed Now · Open Most Saturdays 9:00 AM – 3:00 PM CT',
    };
  }

  if (weekday === 'Sun') {
    return {
      state: 'closed',
      label: 'Closed Sundays · Open Most Saturdays 9:00 AM – 3:00 PM CT',
    };
  }

  return {
    state: 'appointment',
    label: 'Weekdays by Appointment · Open Saturdays 9:00 AM – 3:00 PM CT',
  };
}

function initWataugaHours() {
  const badges = document.querySelectorAll('[data-watauga-hours]');
  if (!badges.length) return;

  const updateAll = () => {
    const status = evaluateWataugaStatus(new Date());
    badges.forEach((badge) => {
      badge.setAttribute('data-state', status.state);
      const textEl = badge.querySelector('[data-watauga-hours-text]');
      if (textEl) {
        textEl.textContent = status.label;
      }
    });
  };

  updateAll();
  window.setInterval(() => {
    if (document.visibilityState === 'visible') {
      updateAll();
    }
  }, 60000);
}

function initImageFallbacks() {
  const images = document.querySelectorAll('img[data-fallback-src]');
  images.forEach((img) => {
    const applyFallback = () => {
      if (img.dataset.fallbackApplied === '1') return;
      const fallbackSrc = img.dataset.fallbackSrc;
      if (!fallbackSrc) return;
      img.dataset.fallbackApplied = '1';
      img.src = fallbackSrc;
    };

    img.addEventListener('error', applyFallback, { once: true });

    if (img.complete && img.naturalWidth === 0) {
      applyFallback();
    }
  });
}

function initCountryDirectory() {
  const root = document.querySelector('[data-country-directory]');
  if (!root) return;

  const controls = root.querySelector('[data-country-controls]');
  const searchInput = root.querySelector('[data-country-search]');
  const filterButtons = Array.from(root.querySelectorAll('[data-region-filter]'));
  const regionCards = Array.from(root.querySelectorAll('[data-region-card]'));
  const countryItems = Array.from(root.querySelectorAll('[data-country-item]'));
  const statusEl = root.querySelector('[data-country-status]');
  const emptyEl = root.querySelector('[data-country-empty]');
  const resetBtn = root.querySelector('[data-country-reset]');

  if (controls) {
    controls.hidden = false;
  }

  let activeRegion = 'all';
  let statusTimer = null;
  const totalCount = countryItems.length;

  const announceStatus = (visibleCount, immediate) => {
    if (!statusEl) return;
    const message =
      visibleCount === totalCount
        ? `Showing all ${totalCount} documented destinations across 40+ countries served.`
        : `Showing ${visibleCount} of ${totalCount} documented destinations.`;

    if (statusTimer) {
      window.clearTimeout(statusTimer);
      statusTimer = null;
    }

    if (immediate) {
      statusEl.textContent = message;
    } else {
      statusTimer = window.setTimeout(() => {
        statusEl.textContent = message;
      }, 220);
    }
  };

  const applyFilter = (immediateAnnouncement = false) => {
    const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
    let visibleTotal = 0;

    regionCards.forEach((card) => {
      const regionKey = card.getAttribute('data-region-card') || '';
      const regionMatches = activeRegion === 'all' || regionKey === activeRegion;
      const items = Array.from(card.querySelectorAll('[data-country-item]'));
      let regionVisible = 0;

      items.forEach((item) => {
        const name = (item.getAttribute('data-country-name') || item.textContent || '').toLowerCase();
        const regionLabel = (item.getAttribute('data-country-region') || '').toLowerCase();
        const textMatches = query === '' || name.includes(query) || regionLabel.includes(query);
        const show = regionMatches && textMatches;
        item.hidden = !show;
        if (show) {
          regionVisible += 1;
          visibleTotal += 1;
        }
      });

      card.hidden = regionVisible === 0;
      const countEl = card.querySelector('[data-region-count]');
      if (countEl) {
        const originalCount = items.length;
        countEl.textContent =
          regionVisible === originalCount
            ? `${originalCount}`
            : `${regionVisible} / ${originalCount}`;
      }
    });

    if (emptyEl) {
      emptyEl.hidden = visibleTotal > 0;
    }

    announceStatus(visibleTotal, immediateAnnouncement);
  };

  filterButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      activeRegion = btn.getAttribute('data-region-filter') || 'all';
      filterButtons.forEach((other) => {
        other.setAttribute('aria-pressed', other === btn ? 'true' : 'false');
      });
      applyFilter(true);
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      applyFilter(false);
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      activeRegion = 'all';
      if (searchInput) {
        searchInput.value = '';
      }
      filterButtons.forEach((btn) => {
        const isAll = btn.getAttribute('data-region-filter') === 'all';
        btn.setAttribute('aria-pressed', isAll ? 'true' : 'false');
      });
      applyFilter(true);
      if (searchInput) {
        searchInput.focus();
      }
    });
  }
}

function initShippingTableSort() {
  const section = document.querySelector('[data-shipping-section]');
  if (!section) return;

  const controls = section.querySelector('[data-shipping-controls]');
  const table = section.querySelector('[data-shipping-table]');
  const statusEl = section.querySelector('[data-shipping-status]');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll('tr'));
  const buttons = Array.from(section.querySelectorAll('[data-sort-mode]'));
  const yearHeader = table.querySelector('[data-col-year]');
  const totalHeader = table.querySelector('[data-col-total]');

  if (controls) {
    controls.hidden = false;
  }

  const sortRows = (mode) => {
    const sorted = [...rows].sort((a, b) => {
      const yearA = Number(a.getAttribute('data-year-start') || 0);
      const yearB = Number(b.getAttribute('data-year-start') || 0);
      const totalA = Number(a.getAttribute('data-combined') || 0);
      const totalB = Number(b.getAttribute('data-combined') || 0);

      if (mode === 'year-desc') return yearB - yearA;
      if (mode === 'total-desc') return totalB - totalA || yearB - yearA;
      return yearA - yearB;
    });

    const frag = document.createDocumentFragment();
    sorted.forEach((row) => frag.appendChild(row));
    tbody.appendChild(frag);

    if (yearHeader && totalHeader) {
      if (mode === 'year-desc') {
        yearHeader.setAttribute('aria-sort', 'descending');
        totalHeader.removeAttribute('aria-sort');
      } else if (mode === 'total-desc') {
        totalHeader.setAttribute('aria-sort', 'descending');
        yearHeader.removeAttribute('aria-sort');
      } else {
        yearHeader.setAttribute('aria-sort', 'ascending');
        totalHeader.removeAttribute('aria-sort');
      }
    }

    if (statusEl) {
      const labels = {
        'year-asc': 'Sorted chronologically from 2013-2014 through 2024-2025.',
        'year-desc': 'Sorted newest first from 2024-2025 back to 2013-2014.',
        'total-desc': 'Sorted by highest combined eyeglasses shipped.',
      };
      statusEl.textContent = labels[mode] || '';
    }
  };

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const mode = btn.getAttribute('data-sort-mode') || 'year-asc';
      buttons.forEach((other) => {
        other.setAttribute('aria-pressed', other === btn ? 'true' : 'false');
      });
      sortRows(mode);
    });
  });
}

function bootstrap() {
  initWataugaHours();
  initImageFallbacks();
  initCountryDirectory();
  initShippingTableSort();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
} else {
  bootstrap();
}
