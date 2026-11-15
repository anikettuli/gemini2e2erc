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

function initCalibrationIntro() {
  const bar = document.querySelector('[data-calib-bar]');
  if (!bar) return;

  const stepEl = bar.querySelector('[data-calib-step]');
  const labelEl = bar.querySelector('[data-calib-label]');
  const fillEl = bar.querySelector('[data-calib-fill]');
  const skipBtn = bar.querySelector('[data-calib-skip]');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const storageKey = 'erc-v2-intro-seen';

  let alreadySeen = false;
  try {
    alreadySeen = window.sessionStorage.getItem(storageKey) === '1';
  } catch {
    alreadySeen = false;
  }

  const finishCalibration = () => {
    if (stepEl) stepEl.textContent = '04/04';
    if (labelEl) labelEl.textContent = 'OPTICAL BENCH CALIBRATED · 0.25D READY';
    if (fillEl) fillEl.style.transform = 'scaleX(1)';
    if (skipBtn) skipBtn.hidden = true;
    try {
      window.sessionStorage.setItem(storageKey, '1');
    } catch {
      // Ignore storage failures in restricted contexts.
    }
  };

  if (reduceMotion || alreadySeen) {
    finishCalibration();
    return;
  }

  const steps = [
    { step: '01/04', label: 'CALIBRATING LENSOMETER RETICLE...', scale: '0.25' },
    { step: '02/04', label: 'SYNCING 214 DFW DROP-OFF BINS...', scale: '0.50' },
    { step: '03/04', label: 'VERIFYING 0.25-DIOPTER INVENTORY...', scale: '0.75' },
    { step: '04/04', label: 'OPTICAL BENCH CALIBRATED · 0.25D READY', scale: '1' },
  ];

  let idx = 0;
  let timerId = 0;

  const applyStep = (entry) => {
    if (stepEl) stepEl.textContent = entry.step;
    if (labelEl) labelEl.textContent = entry.label;
    if (fillEl) fillEl.style.transform = `scaleX(${entry.scale})`;
  };

  if (skipBtn) {
    skipBtn.hidden = false;
    skipBtn.addEventListener('click', () => {
      if (timerId) window.clearInterval(timerId);
      finishCalibration();
    });
  }

  applyStep(steps[0]);
  timerId = window.setInterval(() => {
    idx += 1;
    if (idx >= steps.length - 1) {
      window.clearInterval(timerId);
      finishCalibration();
      return;
    }
    applyStep(steps[idx]);
  }, 420);
}

function initSolariBoard() {
  const board = document.querySelector('[data-solari-board]');
  if (!board) return;

  const grid = board.querySelector('[data-solari-grid]');
  const counterEl = board.querySelector('[data-solari-counter]');
  const captionEl = board.querySelector('[data-solari-caption]');
  const tagEl = board.querySelector('[data-solari-tag]');
  const segmentsWrap = board.querySelector('[data-solari-segments]');
  const spinBtn = document.querySelector('[data-solari-spin]');
  const pills = Array.from(document.querySelectorAll('[data-solari-index]'));
  const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (!grid) return;

  const cols = 9;
  const facts = [
    {
      row1: '21,696+',
      row2: 'PAIRS',
      accentRow: 0,
      title: '21,696+ Pairs (2024–2025):',
      body: '17,692 foreign + 4,004 local mission pairs shipped in our highest recorded Lion Year.',
      tag: 'LION YEAR 2024–2025',
    },
    {
      row1: '214',
      row2: 'DROP BINS',
      accentRow: 0,
      title: '214 Drop-Off Bins:',
      body: 'Active Lions collection boxes across 34 North Texas municipalities plus Watauga HQ.',
      tag: '34 NORTH TEXAS CITIES',
    },
    {
      row1: '38',
      row2: 'COUNTRIES',
      accentRow: 0,
      title: '38 Documented Nations:',
      body: '37 listed countries across the Americas, Africa, Asia, and Europe plus Fiji — 40+ countries served.',
      tag: '40+ COUNTRIES MILESTONE',
    },
    {
      row1: 'UNDER',
      row2: '$0.10',
      accentRow: 1,
      title: 'Under $0.10 US Per Pair:',
      body: 'Less than 10 cents processing cost per pair thanks to donated eyewear and 100% volunteer labor.',
      tag: 'ZERO PAID STAFF',
    },
    {
      row1: '0.25D',
      row2: 'GRADE',
      accentRow: 0,
      title: '0.25-Diopter Precision:',
      body: 'Tabletop lensometer verification across single-vision plus, minus, and reading glasses.',
      tag: 'LENSOMETRY BENCH',
    },
    {
      row1: '50-PAIR',
      row2: 'BOX',
      accentRow: 0,
      title: 'Standardized 50-Pair Carton:',
      body: 'Packed in uniform 50-pair diopter boxes for rapid dispensing by partner medical missions.',
      tag: 'MISSION DISPATCH SPEC',
    },
  ];

  const padLine = (text) => {
    const clean = String(text || '').toUpperCase().slice(0, cols);
    return clean.padEnd(cols, ' ');
  };

  let currentIndex = 0;

  const renderFact = (index, animate) => {
    currentIndex = ((index % facts.length) + facts.length) % facts.length;
    const fact = facts[currentIndex];
    const line1 = padLine(fact.row1);
    const line2 = padLine(fact.row2);
    const shouldAnimate = animate && !reduceMotionQuery.matches;

    const flaps = Array.from(grid.querySelectorAll('.v2-flap'));
    flaps.forEach((flap) => {
      const row = Number(flap.getAttribute('data-row') || 0);
      const col = Number(flap.getAttribute('data-col') || 0);
      const char = (row === 0 ? line1[col] : line2[col]) || ' ';
      const isAccent = row === fact.accentRow && char !== ' ';
      const displayChar = char === ' ' ? '\u00A0' : char;

      flap.style.setProperty('--row', String(row));
      flap.style.setProperty('--col', String(col));
      flap.classList.toggle('v2-flap--accent', isAccent);

      const topSpan = flap.querySelector('.v2-flap__top span');
      const bottomSpan = flap.querySelector('.v2-flap__bottom span');
      if (topSpan) topSpan.textContent = displayChar;
      if (bottomSpan) bottomSpan.textContent = displayChar;

      if (shouldAnimate) {
        flap.classList.remove('is-flipping');
        void flap.offsetWidth;
        flap.classList.add('is-flipping');
      } else {
        flap.classList.remove('is-flipping');
      }
    });

    if (counterEl) {
      counterEl.textContent = `FACT ${String(currentIndex + 1).padStart(2, '0')}/${String(facts.length).padStart(2, '0')}`;
    }

    if (tagEl) {
      tagEl.textContent = fact.tag;
    }

    if (captionEl) {
      const strong = document.createElement('strong');
      strong.textContent = fact.title;
      captionEl.replaceChildren(strong, document.createTextNode(` ${fact.body}`));
    }

    if (segmentsWrap) {
      Array.from(segmentsWrap.children).forEach((seg, segIdx) => {
        seg.classList.toggle('is-active', segIdx <= currentIndex);
      });
    }

    pills.forEach((pill, pillIdx) => {
      pill.setAttribute('aria-pressed', pillIdx === currentIndex ? 'true' : 'false');
    });
  };

  if (spinBtn) {
    spinBtn.addEventListener('click', () => {
      renderFact(currentIndex + 1, true);
    });
  }

  pills.forEach((pill) => {
    pill.addEventListener('click', () => {
      const targetIdx = Number(pill.getAttribute('data-solari-index') || 0);
      renderFact(targetIdx, true);
    });
  });

  renderFact(0, false);
}

function initTransitPipeline() {
  const rail = document.querySelector('[data-transit-rail]');
  if (!rail) return;

  const tabs = Array.from(rail.querySelectorAll('[data-stage-trigger]'));
  const cards = Array.from(rail.querySelectorAll('[data-stage-card]'));
  const prevBtn = rail.querySelector('[data-stage-prev]');
  const nextBtn = rail.querySelector('[data-stage-next]');
  const activePath = rail.querySelector('[data-transit-active-path]');
  const hangers = Array.from(rail.querySelectorAll('[data-hanger]'));
  const nodeDots = Array.from(rail.querySelectorAll('[data-node-dot]'));

  if (!tabs.length || !cards.length) return;

  let pathLength = 0;
  if (activePath && typeof activePath.getTotalLength === 'function') {
    pathLength = activePath.getTotalLength();
    activePath.style.strokeDasharray = `${pathLength}`;
  }

  let activeIdx = 0;

  const setActiveStage = (nextIdx, focusTab = false) => {
    activeIdx = ((nextIdx % tabs.length) + tabs.length) % tabs.length;

    tabs.forEach((tab, idx) => {
      const selected = idx === activeIdx;
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
      tab.tabIndex = selected ? 0 : -1;
      if (selected && focusTab) {
        tab.focus();
      }
    });

    cards.forEach((card, idx) => {
      card.classList.toggle('is-active', idx === activeIdx);
    });

    if (activePath && pathLength > 0) {
      const fraction = activeIdx / Math.max(1, tabs.length - 1);
      const offset = pathLength * (1 - fraction);
      activePath.style.strokeDashoffset = `${offset}`;
    }

    hangers.forEach((line, idx) => {
      const isLit = idx <= activeIdx;
      line.setAttribute('stroke', isLit ? 'var(--accent-ink)' : 'currentColor');
      line.setAttribute('stroke-opacity', idx === activeIdx ? '0.9' : isLit ? '0.45' : '0.25');
      line.setAttribute('stroke-width', idx === activeIdx ? '2' : '1.25');
    });

    nodeDots.forEach((dot, idx) => {
      const isLit = idx <= activeIdx;
      dot.setAttribute('r', idx === activeIdx ? '7' : '5.5');
      dot.setAttribute('fill', isLit ? 'var(--accent-ink)' : 'var(--surface)');
      dot.setAttribute('stroke', isLit ? 'var(--accent-ink)' : 'currentColor');
      dot.setAttribute('stroke-opacity', isLit ? '1' : '0.45');
    });
  };

  tabs.forEach((tab, idx) => {
    tab.addEventListener('click', () => setActiveStage(idx, false));
    tab.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        setActiveStage(activeIdx + 1, true);
      } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        setActiveStage(activeIdx - 1, true);
      }
    });
  });

  cards.forEach((card, idx) => {
    card.addEventListener('click', () => setActiveStage(idx, false));
  });

  if (prevBtn) {
    prevBtn.addEventListener('click', () => setActiveStage(activeIdx - 1, false));
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => setActiveStage(activeIdx + 1, false));
  }

  setActiveStage(0, false);
}

function initInspectionQueue() {
  const queue = document.querySelector('[data-inspection-queue]');
  if (!queue) return;

  const items = Array.from(queue.querySelectorAll('[data-queue-item]'));
  const acceptCountEl = queue.querySelector('[data-count-accepted]');
  const recycleCountEl = queue.querySelector('[data-count-recycled]');
  const skipCountEl = queue.querySelector('[data-count-skip]');
  const stampAllBtn = queue.querySelector('[data-stamp-all]');
  const stampAllLabel = queue.querySelector('[data-stamp-all-label]');
  const undoBtn = queue.querySelector('[data-stamp-undo]');
  const completeBanner = queue.querySelector('[data-complete-banner]');
  const completeUndoBtn = queue.querySelector('[data-complete-undo]');
  const stampNoteEl = queue.querySelector('[data-stamp-note] span');

  if (!items.length) return;

  const pad2 = (num) => String(num).padStart(2, '0');

  const syncQueueState = () => {
    let accepted = 0;
    let recycled = 0;
    let skip = 0;
    let stampedCount = 0;

    items.forEach((row) => {
      const isStamped = row.getAttribute('data-stamped') === 'true';
      const verdict = row.getAttribute('data-verdict') || 'accepted';
      const toggleBtn = row.querySelector('[data-row-toggle]');
      const toggleSpan = toggleBtn ? toggleBtn.querySelector('span') : null;
      const policyEl = row.querySelector('[data-row-policy]');

      if (isStamped) {
        stampedCount += 1;
        if (verdict === 'accepted') accepted += 1;
        else if (verdict === 'recycled') recycled += 1;
        else if (verdict === 'skip') skip += 1;
      }

      if (toggleBtn) {
        toggleBtn.setAttribute('aria-pressed', isStamped ? 'true' : 'false');
      }
      if (toggleSpan) {
        toggleSpan.textContent = isStamped ? 'STAMPED (UNDO)' : 'INSPECT & STAMP';
      }
      if (policyEl) {
        policyEl.hidden = !isStamped;
      }
    });

    if (acceptCountEl) acceptCountEl.textContent = pad2(accepted);
    if (recycleCountEl) recycleCountEl.textContent = pad2(recycled);
    if (skipCountEl) skipCountEl.textContent = pad2(skip);

    const allStamped = stampedCount === items.length;
    if (completeBanner) {
      completeBanner.hidden = !allStamped;
    }
    if (undoBtn) {
      undoBtn.hidden = stampedCount === 0;
    }
    if (stampAllBtn) {
      stampAllBtn.hidden = allStamped;
    }
    if (stampAllLabel) {
      stampAllLabel.textContent = stampedCount > 0 && !allStamped ? 'STAMP REMAINING' : 'STAMP ALL';
    }
    if (stampNoteEl) {
      stampNoteEl.textContent = allStamped ? '9 items triaged in one click' : 'stamp the whole bin';
    }
  };

  items.forEach((row) => {
    const toggleBtn = row.querySelector('[data-row-toggle]');
    if (!toggleBtn) return;
    toggleBtn.addEventListener('click', () => {
      const next = row.getAttribute('data-stamped') !== 'true';
      row.setAttribute('data-stamped', next ? 'true' : 'false');
      syncQueueState();
    });
  });

  if (stampAllBtn) {
    stampAllBtn.addEventListener('click', () => {
      items.forEach((row) => row.setAttribute('data-stamped', 'true'));
      syncQueueState();
    });
  }

  const resetAll = () => {
    items.forEach((row) => row.setAttribute('data-stamped', 'false'));
    syncQueueState();
  };

  if (undoBtn) undoBtn.addEventListener('click', resetAll);
  if (completeUndoBtn) completeUndoBtn.addEventListener('click', resetAll);

  syncQueueState();
}

function bootstrap() {
  initWataugaHours();
  initImageFallbacks();
  initCountryDirectory();
  initShippingTableSort();
  initCalibrationIntro();
  initSolariBoard();
  initTransitPipeline();
  initInspectionQueue();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
} else {
  bootstrap();
}
