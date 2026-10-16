/**
 * ====================================================================
 * Lions District 2-E2 ERC - OpenUI Ground-Up Application Logic
 * ====================================================================
 */

// Application Master Configuration
const SITE_CONFIG = {
  email: "2e2erc1854@gmail.com",
  phone: "(817) 710-5403",
  address: "5621 Bunker Blvd, Watauga, TX 76148",
  mailing: "P.O. Box 1854, Keller, TX 76244",
  defaultImage: "e2e2rc_LOGO.png"
};

// Global Reactive State
let appState = {
  currentTab: 'home',
  events: [],
  locations: [],
  selectedMonth: new Date(),
  mapInstance: null,
  mapLoaded: false,
  searchTerm: '',
  selectedCity: 'all',
  selectedLetter: ''
};

/**
 * Toast Notification Dock
 */
function showToast(message, type = 'info') {
  let dock = document.getElementById('toastDock');
  if (!dock) {
    dock = document.createElement('div');
    dock.id = 'toastDock';
    document.body.appendChild(dock);
  }

  const toast = document.createElement('div');
  toast.className = 'toast-pill';
  
  const icon = type === 'success' ? '✨' : (type === 'error' ? '⚠️' : 'ℹ️');
  toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
  
  dock.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

/**
 * Modern Hash Router
 */
class Router {
  constructor() {
    this.validTabs = ['home', 'about', 'services', 'volunteer', 'locations', 'contact'];
    this.init();
  }

  init() {
    window.addEventListener('hashchange', () => this.handleHashChange());
    this.handleHashChange();

    document.querySelectorAll('.js-tab-nav, .js-tab-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const tab = btn.getAttribute('data-tab');
        if (tab && this.validTabs.includes(tab)) {
          window.location.hash = tab;
        }
      });
    });

    // Mobile drawer toggle
    const toggle = document.getElementById('mobileNavToggle');
    const drawer = document.getElementById('mobileDrawer');
    if (toggle && drawer) {
      toggle.addEventListener('click', () => {
        drawer.classList.toggle('open');
      });
    }
  }

  handleHashChange() {
    let tab = window.location.hash.slice(1);
    // Backward compatibility for #get-involved -> #volunteer
    if (tab === 'get-involved') tab = 'volunteer';
    if (!this.validTabs.includes(tab)) tab = 'home';
    this.switchTab(tab);
  }

  switchTab(tab) {
    appState.currentTab = tab;

    // Toggle Panes
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    const target = document.getElementById(`tab-${tab}`);
    if (target) target.classList.add('active');

    // Update Nav Pills
    document.querySelectorAll('.menu-pill').forEach(btn => {
      if (btn.getAttribute('data-tab') === tab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    // Close Mobile Drawer
    const drawer = document.getElementById('mobileDrawer');
    if (drawer) drawer.classList.remove('open');

    // Update Mobile Current Label
    const mobileLabel = document.getElementById('mobileCurrentTabLabel');
    if (mobileLabel) {
      const activeBtn = document.querySelector(`.menu-pill[data-tab="${tab}"]`);
      if (activeBtn) mobileLabel.textContent = activeBtn.textContent;
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });

    if (tab === 'locations') {
      setTimeout(() => initMapIfNeeded(), 100);
    }
  }
}

/**
 * OpenUI Theme Engine
 */
class ThemeEngine {
  constructor() {
    this.init();
  }

  init() {
    const btn = document.getElementById('themeToggleBtn');
    const icon = document.getElementById('themeIconSpan');

    const updateUI = (isDark) => {
      if (icon) icon.textContent = isDark ? '🌙' : '☀️';
    };

    // Dark is default unless explicitly light
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    updateUI(currentTheme === 'dark');

    if (btn) {
      btn.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
        if (isDark) {
          document.documentElement.setAttribute('data-theme', 'light');
          localStorage.setItem('theme', 'light');
          updateUI(false);
          updateMapStyle('light');
        } else {
          document.documentElement.removeAttribute('data-theme'); // default is dark
          localStorage.setItem('theme', 'dark');
          updateUI(true);
          updateMapStyle('dark');
        }
      });
    }
  }
}

/**
 * Interactive Vision Impact Simulator (Creative OpenUI Component)
 */
function setupImpactCalculator() {
  const slider = document.getElementById('impactSlider');
  const countDisp = document.getElementById('calcGlassesCount');
  const costDisp = document.getElementById('calcCost');
  const livesDisp = document.getElementById('calcLives');
  const wasteDisp = document.getElementById('calcWaste');

  if (!slider) return;

  const update = () => {
    const pairs = parseInt(slider.value, 10);
    if (countDisp) countDisp.textContent = `${pairs} Pairs`;
    if (costDisp) costDisp.textContent = `$${(pairs * 0.08).toFixed(2)}`;
    if (livesDisp) livesDisp.textContent = `${pairs} People`;
    if (wasteDisp) wasteDisp.textContent = `${(pairs * 0.035).toFixed(1)} kg`;
  };

  slider.addEventListener('input', update);
  update();
}

/**
 * Drop-Off Location Directory & Live Search (Priority Task 7)
 */
class LocationsDirectory {
  async init() {
    const container = document.getElementById('locationsContainer');
    if (container) container.innerHTML = '<p style="text-align:center; padding:30px; color:var(--text-secondary);">Loading drop-off locations...</p>';

    try {
      const res = await fetch('get-locations.php');
      appState.locations = await res.json();
      appState.locations.sort((a, b) => a.name.localeCompare(b.name));
      this.setupFilters();
      this.render();
    } catch (e) {
      console.error('Failed to load locations:', e);
      if (container) container.innerHTML = '<p style="color:var(--accent-rose); text-align:center;">Error loading locations.</p>';
    }
  }

  setupFilters() {
    const searchInput = document.getElementById('locationSearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        appState.searchTerm = e.target.value.toLowerCase().trim();
        this.render();
      });
    }

    // City pills
    document.querySelectorAll('.city-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        document.querySelectorAll('.city-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        appState.selectedCity = pill.getAttribute('data-city') || 'all';
        this.render();
      });
    });

    // A-Z chips
    document.querySelectorAll('.letter-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        const letter = pill.getAttribute('data-letter');
        if (appState.selectedLetter === letter) {
          appState.selectedLetter = '';
          pill.classList.remove('active');
        } else {
          document.querySelectorAll('.letter-pill').forEach(p => p.classList.remove('active'));
          pill.classList.add('active');
          appState.selectedLetter = letter;
        }
        this.render();
      });
    });
  }

  render() {
    const container = document.getElementById('locationsContainer');
    const counter = document.getElementById('locationsCountDisplay');
    if (!container) return;

    let list = appState.locations;

    // Search filter (Zip, City, Name, Address)
    if (appState.searchTerm) {
      list = list.filter(loc => {
        const haystack = `${loc.name} ${loc.address} ${loc.phone || ''}`.toLowerCase();
        return haystack.includes(appState.searchTerm);
      });
    }

    // City filter
    if (appState.selectedCity && appState.selectedCity !== 'all') {
      list = list.filter(loc => loc.address.toLowerCase().includes(appState.selectedCity.toLowerCase()));
    }

    // A-Z filter
    if (appState.selectedLetter) {
      list = list.filter(loc => loc.name.toUpperCase().startsWith(appState.selectedLetter));
    }

    if (counter) {
      counter.textContent = `Displaying ${list.length} of ${appState.locations.length} locations`;
    }

    if (list.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1/-1; text-align:center; padding:50px 20px; color:var(--text-secondary);">
          <p style="font-size:2rem; margin-bottom:8px;">🔍</p>
          <p>No drop-off locations match your search criteria.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = list.map(loc => {
      const gMapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.name + ' ' + loc.address)}`;
      const phoneDigits = (loc.phone || '').replace(/\D/g, '');
      const phoneLink = phoneDigits ? `<a href="tel:+1${phoneDigits}" class="phone-link">📞 ${loc.phone}</a>` : '<span>-</span>';

      return `
        <div class="location-card">
          <div>
            <h4>${loc.name}</h4>
            <p class="loc-addr">📍 ${loc.address}</p>
          </div>
          <div class="location-card-footer">
            ${phoneLink}
            <a href="${gMapsUrl}" target="_blank" rel="noopener noreferrer" class="btn-directions">
              Directions &rarr;
            </a>
          </div>
        </div>
      `;
    }).join('');
  }
}

/**
 * MapLibre GL Integration
 */
function initMapIfNeeded() {
  const container = document.getElementById('allLocationsMap');
  if (!container || typeof maplibregl === 'undefined' || appState.locations.length === 0) return;

  if (appState.mapLoaded && appState.mapInstance) {
    appState.mapInstance.resize();
    return;
  }

  const isLight = document.documentElement.getAttribute('data-theme') === 'light';
  const styleUrl = isLight ? 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json'
                           : 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json';

  try {
    appState.mapInstance = new maplibregl.Map({
      container: 'allLocationsMap',
      style: styleUrl,
      center: [-97.2, 32.75], // DFW center
      zoom: 9
    });

    appState.mapInstance.addControl(new maplibregl.NavigationControl(), 'top-right');

    const bounds = new maplibregl.LngLatBounds();
    let pins = 0;

    appState.locations.forEach(loc => {
      if (loc.lat && loc.lng) {
        pins++;
        bounds.extend([loc.lng, loc.lat]);

        const gMaps = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.name + ' ' + loc.address)}`;
        const popup = new maplibregl.Popup({ offset: 25 }).setHTML(`
          <div style="font-weight: 700; color: #0284c7; margin-bottom: 4px;">${loc.name}</div>
          <div style="font-size: 0.85em; color: #334155; margin-bottom: 8px;">${loc.address}</div>
          <a href="${gMaps}" target="_blank" style="display:inline-block; background:#0284c7; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8em; font-weight:600;">Get Directions &rarr;</a>
        `);

        new maplibregl.Marker({ color: '#6366f1' })
          .setLngLat([loc.lng, loc.lat])
          .setPopup(popup)
          .addTo(appState.mapInstance);
      }
    });

    if (pins > 0) {
      appState.mapInstance.fitBounds(bounds, { padding: 40, maxZoom: 14 });
    }

    appState.mapLoaded = true;
  } catch (err) {
    console.error('MapLibre error:', err);
  }
}

function updateMapStyle(theme) {
  if (appState.mapInstance && appState.mapLoaded) {
    const style = theme === 'light' ? 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json'
                                    : 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json';
    appState.mapInstance.setStyle(style);
  }
}

/**
 * Calendar & Volunteer Event Management
 */
class CalendarHub {
  constructor() {
    this.months = ['January', 'February', 'March', 'April', 'May', 'June',
                   'July', 'August', 'September', 'October', 'November', 'December'];
  }

  async init() {
    await this.fetchEvents();
    this.renderMonth();
    this.renderUpcoming();
    this.setupListeners();
  }

  async fetchEvents() {
    try {
      const res = await fetch('data/events.json?v=' + Date.now());
      appState.events = await res.json();
      appState.events.sort((a, b) => new Date(a.date) - new Date(b.date));
    } catch (e) {
      console.error('Error fetching events:', e);
      appState.events = [];
    }
  }

  setupListeners() {
    document.getElementById('calPrevBtn')?.addEventListener('click', () => {
      appState.selectedMonth.setMonth(appState.selectedMonth.getMonth() - 1);
      this.renderMonth();
    });

    document.getElementById('calNextBtn')?.addEventListener('click', () => {
      appState.selectedMonth.setMonth(appState.selectedMonth.getMonth() + 1);
      this.renderMonth();
    });
  }

  renderMonth() {
    const titleEl = document.getElementById('calHeaderTitle');
    const grid = document.getElementById('calCellsGrid');
    if (!titleEl || !grid) return;

    const y = appState.selectedMonth.getFullYear();
    const m = appState.selectedMonth.getMonth();

    titleEl.textContent = `${this.months[m]} ${y}`;
    grid.innerHTML = '';

    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const prevMonthDays = new Date(y, m, 0).getDate();

    for (let i = firstDay - 1; i >= 0; i--) {
      const cell = document.createElement('div');
      cell.className = 'cal-cell empty-day';
      cell.textContent = prevMonthDays - i;
      grid.appendChild(cell);
    }

    const todayStr = new Date().toISOString().split('T')[0];

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      const hasEvents = appState.events.filter(e => e.date === dateStr);

      const cell = document.createElement('div');
      cell.className = 'cal-cell';
      if (dateStr === todayStr) cell.classList.add('today');
      if (hasEvents.length > 0) cell.classList.add('has-event');
      cell.textContent = d;

      cell.addEventListener('click', () => {
        document.querySelectorAll('.cal-cell').forEach(c => c.classList.remove('active-selected'));
        cell.classList.add('active-selected');
        this.renderDayEvents(dateStr, hasEvents);
      });

      grid.appendChild(cell);
    }

    // Auto-select next active session
    const nextSession = appState.events.find(e => new Date(e.date + 'T23:59:59') >= new Date());
    if (nextSession) {
      const matchDay = Array.from(grid.querySelectorAll('.cal-cell:not(.empty-day)')).find(c => {
        const dayNum = c.textContent.padStart(2, '0');
        const monthNum = String(m + 1).padStart(2, '0');
        return `${y}-${monthNum}-${dayNum}` === nextSession.date;
      });
      if (matchDay) {
        matchDay.classList.add('active-selected');
        this.renderDayEvents(nextSession.date, appState.events.filter(e => e.date === nextSession.date));
      }
    }
  }

  renderDayEvents(dateStr, events) {
    const listEl = document.getElementById('calDayEventsBox');
    if (!listEl) return;

    if (!events || events.length === 0) {
      listEl.innerHTML = `
        <div style="text-align:center; padding: 40px 10px; color: var(--text-secondary);">
          <p style="font-size: 1.8rem; margin-bottom: 8px;">🗓️</p>
          <p>No volunteer sessions scheduled on <strong>${dateStr}</strong>.</p>
        </div>
      `;
      return;
    }

    listEl.innerHTML = events.map(ev => this.buildEventCard(ev)).join('');
  }

  renderUpcoming() {
    const container = document.getElementById('upcomingEventsList');
    if (!container) return;

    const now = new Date();
    now.setHours(0, 0, 0, 0);

    const upcoming = appState.events.filter(e => new Date(e.date + 'T23:59:59') >= now).slice(0, 6);

    if (upcoming.length === 0) {
      container.innerHTML = '<p style="color:var(--text-secondary); text-align:center; grid-column:1/-1;">No upcoming sessions scheduled right now.</p>';
      return;
    }

    container.innerHTML = upcoming.map(ev => this.buildEventCard(ev)).join('');
  }

  buildEventCard(ev) {
    const spotsLeft = Math.max(0, ev.maxPeople - ev.people);
    const pct = Math.min(100, Math.round((ev.people / ev.maxPeople) * 100));
    const isFull = spotsLeft <= 0;

    return `
      <div class="event-bento-card">
        <img src="${ev.image || SITE_CONFIG.defaultImage}" alt="${ev.title}" class="event-hero-banner" onerror="this.src='${SITE_CONFIG.defaultImage}'">
        <div class="event-card-inner">
          <h4>${ev.title}</h4>
          <div class="event-detail-list">
            <div>📅 <strong>Date:</strong> ${ev.date}</div>
            <div>⏰ <strong>Time:</strong> ${ev.time}</div>
            <div>📍 <strong>Location:</strong> ${ev.location}</div>
          </div>
          <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 14px;">${ev.description || ''}</p>
          
          <div class="capacity-meter">
            <div class="capacity-legend">
              <span>Volunteers: ${ev.people} / ${ev.maxPeople}</span>
              <span style="color: ${isFull ? 'var(--accent-rose)' : 'var(--accent-emerald)'};">
                ${isFull ? 'Session Full' : `${spotsLeft} spots available`}
              </span>
            </div>
            <div class="meter-track">
              <div class="meter-fill" style="width: ${pct}%;"></div>
            </div>
          </div>

          <button class="btn-glow-primary" style="width:100%; justify-content:center; margin-top:auto;"
                  ${isFull ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : `onclick="openVolunteerDialog(${ev.id}, '${ev.title.replace(/'/g, "\\'")}', '${ev.date}', '${ev.time}')"`}>
            ${isFull ? 'Session Full' : 'Register to Volunteer &rarr;'}
          </button>
        </div>
      </div>
    `;
  }
}

/**
 * Dynamic Content (Board Members, Testimonials, Partners)
 */
async function loadDynamicContent() {
  // Board Members (Split into Current and Previous)
  try {
    const res = await fetch('data/board.json');
    const board = await res.json();
    const currentContainer = document.getElementById('currentBoardCards');
    const prevContainer = document.getElementById('prevBoardCards');

    const current = board.filter(b => !b.tag || b.tag === 'current');
    const prev = board.filter(b => b.tag === 'previous');

    if (currentContainer) {
      currentContainer.innerHTML = current.map(b => `
        <div class="board-profile-card">
          <img src="${b.image || SITE_CONFIG.defaultImage}" alt="${b.name}" class="board-profile-img" onerror="this.src='${SITE_CONFIG.defaultImage}'">
          <div class="board-profile-name">${b.name}</div>
        </div>
      `).join('');
    }

    if (prevContainer) {
      prevContainer.innerHTML = prev.map(b => `
        <div class="board-profile-card">
          <img src="${b.image || SITE_CONFIG.defaultImage}" alt="${b.name}" class="board-profile-img" onerror="this.src='${SITE_CONFIG.defaultImage}'">
          <div class="board-profile-name">${b.name}</div>
        </div>
      `).join('');
    }
  } catch (e) {
    console.error('Error loading board members:', e);
  }

  // Testimonials
  try {
    const res = await fetch('data/testimonials.json');
    const testimonials = await res.json();
    const container = document.getElementById('testimonialsGrid');
    if (container) {
      container.innerHTML = testimonials.map(t => `
        <div class="bento-card" style="display:flex; flex-direction:column; justify-content:space-between;">
          <p style="font-style:italic; font-size:0.95rem; line-height:1.6; margin-bottom:16px;">"${t.text}"</p>
          <div>
            <strong style="color:var(--text-primary); font-size:1rem;">${t.author}</strong>
            <p style="font-size:0.8rem; color:var(--accent-cyan); font-weight:600;">${t.organization}</p>
          </div>
        </div>
      `).join('');
    }
  } catch (e) {
    console.error('Error loading testimonials:', e);
  }

  // Partners List
  try {
    const res = await fetch('data/partners.json');
    const partners = await res.json();
    const container = document.getElementById('partnersListGrid');
    if (container) {
      container.innerHTML = partners.map(p => `
        <div style="background:var(--bg-subtle); border:1px solid var(--border-subtle); padding:10px 14px; border-radius:var(--radius-sm); font-size:0.88rem; font-weight:600; color:var(--text-secondary);">
          🤝 ${p}
        </div>
      `).join('');
    }
  } catch (e) {
    console.error('Error loading partners:', e);
  }
}

/**
 * Volunteer Modal Dialog Controller
 */
window.openVolunteerDialog = function(id, title, date, time) {
  const dialog = document.getElementById('volunteerModal');
  if (!dialog) return;

  document.getElementById('modalEventIdInput').value = id;
  document.getElementById('modalEventTitlePreview').textContent = title;
  document.getElementById('modalEventDatePreview').textContent = `${date} • ${time}`;

  dialog.showModal();
};

function setupVolunteerDialog() {
  const dialog = document.getElementById('volunteerModal');
  const form = document.getElementById('volunteerSignupForm');
  const closeBtn = document.getElementById('closeModalBtn');

  if (closeBtn && dialog) {
    closeBtn.addEventListener('click', () => dialog.close());
  }

  if (dialog) {
    dialog.addEventListener('click', (e) => {
      const rect = dialog.getBoundingClientRect();
      if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
        dialog.close();
      }
    });
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      const prevText = submitBtn.textContent;
      submitBtn.textContent = 'Registering...';
      submitBtn.disabled = true;

      const payload = {
        eventId: document.getElementById('modalEventIdInput').value,
        name: document.getElementById('volNameInput').value,
        email: document.getElementById('volEmailInput').value,
        phone: document.getElementById('volPhoneInput').value
      };

      try {
        const res = await fetch('register-volunteer.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          showToast(data.message, 'success');
          dialog.close();
          form.reset();

          if (window.calendarHub) {
            await window.calendarHub.fetchEvents();
            window.calendarHub.renderUpcoming();
            window.calendarHub.renderMonth();
          }
        } else {
          showToast(data.message || 'Registration failed', 'error');
        }
      } catch (err) {
        showToast('Connection error during registration. Please retry.', 'error');
      } finally {
        submitBtn.textContent = prevText;
        submitBtn.disabled = false;
      }
    });
  }
}

/**
 * Contact Form Handler
 */
function setupContactForm() {
  const form = document.getElementById('contactUsForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const prev = btn.textContent;
    btn.textContent = 'Transmitting Message...';
    btn.disabled = true;

    try {
      const res = await fetch('send-email.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message, 'success');
        form.reset();
      } else {
        showToast(data.message || 'Unable to send message.', 'error');
      }
    } catch (err) {
      showToast('Network error while sending message. Please call us directly.', 'error');
    } finally {
      btn.textContent = prev;
      btn.disabled = false;
    }
  });
}

// App Initialization
document.addEventListener('DOMContentLoaded', () => {
  new Router();
  new ThemeEngine();
  
  setupImpactCalculator();

  window.locationsDirectory = new LocationsDirectory();
  window.locationsDirectory.init();

  window.calendarHub = new CalendarHub();
  window.calendarHub.init();

  loadDynamicContent();
  setupVolunteerDialog();
  setupContactForm();
});
