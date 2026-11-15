/**
 * Admin Operations Console interactive enhancements.
 * Progressively enhances tabs, event/location editing, board/testimonial rows,
 * live location filtering, password visibility toggles, and native <dialog> delete confirmation.
 */

function initFallbackThemeToggle() {
  const toggleBtn = document.getElementById('themeToggle');
  const meta = document.querySelector('meta[name="color-scheme"]');
  if (!toggleBtn || !meta) return;

  // Defer to /js/theme.js when present (ES module resolves from module cache);
  // only attach the local toggle when /js/theme.js is unavailable.
  import('../theme.js').catch(() => {
    const getResolved = () => {
      if (meta.content === 'dark' || meta.content === 'light') return meta.content;
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const syncAria = () => {
      const resolved = getResolved();
      toggleBtn.setAttribute('aria-pressed', resolved === 'dark' ? 'true' : 'false');
    };

    syncAria();
    toggleBtn.addEventListener('click', () => {
      const osScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      const target = getResolved() === 'dark' ? 'light' : 'dark';
      try {
        if (target === osScheme) {
          localStorage.removeItem('erc-color-scheme');
          meta.content = 'light dark';
        } else {
          localStorage.setItem('erc-color-scheme', target);
          meta.content = target;
        }
      } catch {
        meta.content = target;
      }
      syncAria();
      document.dispatchEvent(new CustomEvent('erc:themechange', { detail: { theme: target } }));
    });
  });
}

function initTabs() {
  const tablist = document.querySelector('.admin-tablist');
  if (!tablist) return;
  const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));
  const panels = Array.from(document.querySelectorAll('[role="tabpanel"]'));

  const selectTab = (targetTab, moveFocus = false) => {
    if (!targetTab) return;
    const slug = targetTab.dataset.tab;
    for (const tab of tabs) {
      const active = tab === targetTab;
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.setAttribute('tabindex', active ? '0' : '-1');
    }
    for (const panel of panels) {
      const match = panel.id === `panel-${slug}`;
      panel.hidden = !match;
    }

    targetTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });

    if (moveFocus) {
      targetTab.focus();
    }

    try {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', slug);
      window.history.replaceState(null, '', url.toString());
    } catch {
      // ignore history errors
    }
  };

  for (const tab of tabs) {
    tab.addEventListener('click', () => selectTab(tab, false));
  }

  tablist.addEventListener('keydown', (e) => {
    const idx = tabs.indexOf(document.activeElement);
    if (idx === -1) return;
    let nextIdx = idx;
    if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
    else if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
    else if (e.key === 'Home') nextIdx = 0;
    else if (e.key === 'End') nextIdx = tabs.length - 1;
    else return;

    e.preventDefault();
    selectTab(tabs[nextIdx], true);
  });
}

function initAlerts() {
  for (const btn of document.querySelectorAll('[data-dismiss-alert]')) {
    btn.addEventListener('click', () => {
      btn.closest('.admin-alert')?.remove();
    });
  }
}

function initDeleteConfirmationDialog() {
  const dialog = document.getElementById('confirmDeleteDialog');
  const titleEl = document.getElementById('confirmDeleteTitle');
  const bodyEl = document.getElementById('confirmDeleteBody');
  const proceedBtn = document.getElementById('confirmDeleteProceedBtn');
  if (!dialog || !proceedBtn) return;

  // Light-dismiss fallback for browsers lacking <dialog closedby="any">
  if (!('closedBy' in HTMLDialogElement.prototype)) {
    const isOutside = (ev) => {
      const rect = dialog.getBoundingClientRect();
      return ev.clientX < rect.left || ev.clientX > rect.right || ev.clientY < rect.top || ev.clientY > rect.bottom;
    };
    let pressedOutside = false;
    dialog.addEventListener('pointerdown', (ev) => {
      pressedOutside = isOutside(ev);
    });
    dialog.addEventListener('pointerup', (ev) => {
      if (pressedOutside && isOutside(ev)) dialog.close();
    });
  }

  let pendingForm = null;

  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    const msg = form.dataset.confirmMessage;
    if (!msg || form.dataset.confirmed === 'true') return;

    e.preventDefault();
    pendingForm = form;
    if (titleEl) titleEl.textContent = form.dataset.confirmTitle || 'Confirm Action';
    if (bodyEl) bodyEl.textContent = msg;
    dialog.showModal();
  });

  dialog.addEventListener('close', () => {
    dialog.returnValue = '';
  });

  proceedBtn.addEventListener('click', () => {
    if (pendingForm) {
      const target = pendingForm;
      pendingForm = null;
      target.dataset.confirmed = 'true';
      dialog.close();
      target.submit();
    } else {
      dialog.close();
    }
  });
}

function initEventEditor() {
  const form = document.getElementById('eventEditorForm');
  const heading = document.getElementById('eventFormHeading');
  const resetBtn = document.getElementById('resetEventFormBtn');
  const idInput = document.getElementById('ev_id');
  const titleInput = document.getElementById('ev_title');
  const dateInput = document.getElementById('ev_date');
  const timeInput = document.getElementById('ev_time');
  const maxInput = document.getElementById('ev_max');
  const locInput = document.getElementById('ev_location');
  const descInput = document.getElementById('ev_description');
  const contactInput = document.getElementById('ev_contact');
  const existingImgInput = document.getElementById('ev_existing_image');
  const existingLabel = document.getElementById('ev_existing_label');
  const submitBtn = document.getElementById('ev_submit_btn');

  if (!form || !idInput || !titleInput) return;

  const resetForm = () => {
    form.reset();
    idInput.value = '';
    existingImgInput.value = 'images/placeholder-event.svg';
    if (existingLabel) existingLabel.textContent = 'placeholder';
    if (heading) heading.textContent = 'Add or Edit Volunteer Session';
    if (submitBtn) submitBtn.textContent = 'Save Session';
  };

  resetBtn?.addEventListener('click', () => {
    resetForm();
    titleInput.focus();
  });

  for (const btn of document.querySelectorAll('.js-edit-event')) {
    btn.addEventListener('click', () => {
      try {
        const ev = JSON.parse(btn.dataset.event || '{}');
        idInput.value = String(ev.id || '');
        titleInput.value = ev.title || '';
        dateInput.value = ev.date || '';
        timeInput.value = ev.time || '';
        maxInput.value = String(ev.maxPeople || 15);
        locInput.value = ev.location || '';
        descInput.value = ev.description || '';
        contactInput.value = ev.contact || '';
        existingImgInput.value = ev.image || 'images/placeholder-event.svg';
        if (existingLabel) existingLabel.textContent = existingImgInput.value;
        if (heading) heading.textContent = `Editing Session #${ev.id}`;
        if (submitBtn) submitBtn.textContent = 'Update Session';
        titleInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        titleInput.focus();
      } catch {
        // ignore invalid payload
      }
    });
  }
}

function initLocationDirectory() {
  const form = document.getElementById('locationEditorForm');
  const heading = document.getElementById('locationFormHeading');
  const resetBtn = document.getElementById('resetLocationFormBtn');
  const idxInput = document.getElementById('loc_index');
  const nameInput = document.getElementById('loc_name');
  const addrInput = document.getElementById('loc_address');
  const phoneInput = document.getElementById('loc_phone');
  const submitBtn = document.getElementById('loc_submit_btn');

  const searchInput = document.getElementById('locationSearchInput');
  const geoFilter = document.getElementById('locationGeoFilter');
  const countVisible = document.getElementById('locationsCountVisible');
  const table = document.getElementById('locationsAdminTable');

  if (resetBtn && form && idxInput && nameInput) {
    resetBtn.addEventListener('click', () => {
      form.reset();
      idxInput.value = '-1';
      if (heading) heading.textContent = 'Add or Edit Drop-Off Bin';
      if (submitBtn) submitBtn.textContent = 'Save Location';
      nameInput.focus();
    });
  }

  for (const btn of document.querySelectorAll('.js-edit-location')) {
    btn.addEventListener('click', () => {
      try {
        const loc = JSON.parse(btn.dataset.location || '{}');
        if (idxInput) idxInput.value = String(loc.index ?? -1);
        if (nameInput) nameInput.value = loc.name || '';
        if (addrInput) addrInput.value = loc.address || '';
        if (phoneInput) phoneInput.value = loc.phone || '';
        if (heading) heading.textContent = 'Editing Drop-Off Bin';
        if (submitBtn) submitBtn.textContent = 'Update Location';
        nameInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        nameInput?.focus();
      } catch {
        // ignore
      }
    });
  }

  if (table && searchInput && geoFilter) {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const applyFilter = () => {
      const q = searchInput.value.trim().toLowerCase();
      const geo = geoFilter.value;
      let visible = 0;

      for (const row of rows) {
        const matchText = q === '' || (row.dataset.search || '').includes(q);
        const matchGeo = geo === 'all' || row.dataset.geo === geo;
        const show = matchText && matchGeo;
        row.hidden = !show;
        if (show) visible++;
      }

      if (countVisible) {
        countVisible.textContent = String(visible);
      }
    };

    searchInput.addEventListener('input', applyFilter);
    geoFilter.addEventListener('change', applyFilter);
  }
}

function initBoardRoster() {
  const list = document.getElementById('boardRowsList');
  const addBtn = document.getElementById('addBoardMemberBtn');
  const tpl = document.getElementById('boardRowTemplate');
  if (!list) return;

  addBtn?.addEventListener('click', () => {
    if (!tpl || !('content' in tpl)) return;
    const frag = tpl.content.cloneNode(true);
    list.appendChild(frag);
    const lastRow = list.lastElementChild;
    lastRow?.querySelector('input[name="names[]"]')?.focus();
  });

  list.addEventListener('click', (e) => {
    const target = e.target;
    if (!(target instanceof HTMLElement)) return;
    const row = target.closest('.board-admin-row');
    if (!row) return;

    if (target.closest('.js-remove-board-row')) {
      row.remove();
    } else if (target.closest('.js-move-board-up') && row.previousElementSibling) {
      list.insertBefore(row, row.previousElementSibling);
    } else if (target.closest('.js-move-board-down') && row.nextElementSibling) {
      list.insertBefore(row.nextElementSibling, row);
    }
  });
}

function initPartnersAndTestimonials() {
  const partnersTextarea = document.getElementById('partnersTextarea');
  const partnersCount = document.getElementById('partnersLineCount');
  if (partnersTextarea && partnersCount) {
    const syncCount = () => {
      const count = partnersTextarea.value
        .split(/\r?\n/)
        .map((s) => s.trim())
        .filter(Boolean).length;
      partnersCount.textContent = String(count);
    };
    partnersTextarea.addEventListener('input', syncCount);
  }

  const list = document.getElementById('testimonialsList');
  const addBtn = document.getElementById('addTestimonialBtn');
  const tpl = document.getElementById('testimonialTemplate');
  if (list && addBtn && tpl) {
    addBtn.addEventListener('click', () => {
      const frag = tpl.content.cloneNode(true);
      list.appendChild(frag);
      list.lastElementChild?.querySelector('input')?.focus();
    });

    list.addEventListener('click', (e) => {
      const btn = e.target instanceof HTMLElement ? e.target.closest('.js-remove-testimonial') : null;
      if (btn) {
        btn.closest('.testimonial-fieldset')?.remove();
      }
    });
  }
}

function initPasswordToggles() {
  for (const btn of document.querySelectorAll('.js-toggle-password')) {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const input = targetId ? document.getElementById(targetId) : null;
      if (!(input instanceof HTMLInputElement)) return;
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
      btn.textContent = reveal ? 'Hide' : 'Show';
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initFallbackThemeToggle();
  initTabs();
  initAlerts();
  initDeleteConfirmationDialog();
  initEventEditor();
  initLocationDirectory();
  initBoardRoster();
  initPartnersAndTestimonials();
  initPasswordToggles();
});
