/**
 * Lions District 2-E2 ERC - Client Application Logic
 * OpenUI Framework Guided Implementation
 */

// Global Configuration Defaults
const SITE_CONFIG = {
  email: "2e2erc1854@gmail.com",
  phone: "(817) 710-5403",
  phoneLink: "+18177105403",
  address: "5621 Bunker Blvd, Watauga, TX 76148",
  mailing: "P.O. Box 1854, Keller, TX 76244",
  defaultImage: "e2e2rc_LOGO.png"
};

// Application State
let appState = {
  currentTab: 'home',
  events: [],
  locations: [],
  selectedMonth: new Date(),
  mapInstance: null,
  mapLoaded: false
};

/**
 * Toast Notification Manager
 */
function showToast(message, type = 'info') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast';
  
  const icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');
  toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
  
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 4500);
}

/**
 * Router & Tab Switching
 */
class Router {
  constructor() {
    this.validTabs = ['home', 'about', 'services', 'get-involved', 'locations', 'contact'];
    this.init();
  }

  init() {
    window.addEventListener('hashchange', () => this.handleHashChange());
    this.handleHashChange(); // initial page load

    // Wire desktop & mobile navigation triggers
    document.querySelectorAll('.js-tab-nav, .js-tab-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const tab = btn.getAttribute('data-tab');
        if (tab && this.validTabs.includes(tab)) {
          window.location.hash = tab;
        }
      });
    });

    // Mobile drawer toggle
    const mobileBtn = document.getElementById('mobileMenuToggle');
    const drawer = document.getElementById('mobileNavDrawer');
    if (mobileBtn && drawer) {
      mobileBtn.addEventListener('click', () => {
        drawer.classList.toggle('open');
      });
    }
  }

  handleHashChange() {
    let tab = window.location.hash.slice(1);
    if (!this.validTabs.includes(tab)) {
      tab = 'home';
    }
    this.switchTab(tab);
  }

  switchTab(tab) {
    appState.currentTab = tab;

    // Toggle Tab Panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
      pane.classList.remove('active');
    });
    const targetPane = document.getElementById(`tab-${tab}`);
    if (targetPane) {
      targetPane.classList.add('active');
    }

    // Update Nav Buttons
    document.querySelectorAll('.nav-tab-btn').forEach(btn => {
      if (btn.getAttribute('data-tab') === tab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    // Close Mobile Drawer
    const drawer = document.getElementById('mobileNavDrawer');
    if (drawer) drawer.classList.remove('open');

    // Update mobile current page indicator
    const mobileCurrent = document.getElementById('mobileCurrentTabName');
    if (mobileCurrent) {
      const activeBtn = document.querySelector(`.nav-tab-btn[data-tab="${tab}"]`);
      if (activeBtn) mobileCurrent.textContent = activeBtn.textContent;
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Handle map resize if locations tab is opened
    if (tab === 'locations') {
      setTimeout(() => initMapIfNeeded(), 100);
    }
  }
}

/**
 * Theme Manager (Dark / Light Mode)
 */
class ThemeManager {
  constructor() {
    this.init();
  }

  init() {
    const toggleBtn = document.getElementById('themeToggleBtn');
    const icon = document.getElementById('themeToggleIcon');

    const updateIcon = (isDark) => {
      if (icon) icon.textContent = isDark ? '🌙' : '☀️';
    };

    const isCurrentDark = document.documentElement.getAttribute('data-theme') === 'dark';
    updateIcon(isCurrentDark);

    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
          document.documentElement.removeAttribute('data-theme');
          localStorage.setItem('theme', 'light');
          updateIcon(false);
          updateMapTheme('light');
        } else {
          document.documentElement.setAttribute('data-theme', 'dark');
          localStorage.setItem('theme', 'dark');
          updateIcon(true);
          updateMapTheme('dark');
        }
      });
    }
  }
}

/**
 * Volunteer Calendar & Events Manager
 */
class CalendarManager {
  constructor() {
    this.monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
  }

  async init() {
    await this.fetchEvents();
    this.setupUpcomingEvents();
    this.renderMonthCalendar();
    this.addListeners();
  }

  async fetchEvents() {
    try {
      const res = await fetch('data/events.json?v=' + Date.now());
      appState.events = await res.json();
      appState.events.sort((a, b) => new Date(a.date) - new Date(b.date));
    } catch (e) {
      console.error('Error loading events:', e);
      appState.events = [];
    }
  }

  addListeners() {
    document.getElementById('calPrevBtn')?.addEventListener('click', () => {
      appState.selectedMonth.setMonth(appState.selectedMonth.getMonth() - 1);
      this.renderMonthCalendar();
    });

    document.getElementById('calNextBtn')?.addEventListener('click', () => {
      appState.selectedMonth.setMonth(appState.selectedMonth.getMonth() + 1);
      this.renderMonthCalendar();
    });
  }

  renderMonthCalendar() {
    const titleEl = document.getElementById('calMonthTitle');
    const daysGrid = document.getElementById('calDaysGrid');
    if (!titleEl || !daysGrid) return;

    const year = appState.selectedMonth.getFullYear();
    const month = appState.selectedMonth.getMonth();

    titleEl.textContent = `${this.monthNames[month]} ${year}`;
    daysGrid.innerHTML = '';

    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();
    const prevMonthDays = new Date(year, month, 0).getDate();

    // Previous month filler days
    for (let i = firstDay - 1; i >= 0; i--) {
      const d = document.createElement('div');
      d.className = 'cal-day other-month';
      d.textContent = prevMonthDays - i;
      daysGrid.appendChild(d);
    }

    const todayStr = new Date().toISOString().split('T')[0];

    // Current month days
    for (let day = 1; day <= totalDays; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const dayEvents = appState.events.filter(e => e.date === dateStr);
      const isToday = (dateStr === todayStr);

      const d = document.createElement('div');
      d.className = 'cal-day';
      if (isToday) d.classList.add('today');
      if (dayEvents.length > 0) d.classList.add('has-event');
      d.textContent = day;

      d.addEventListener('click', () => {
        document.querySelectorAll('.cal-day').forEach(el => el.classList.remove('selected'));
        d.classList.add('selected');
        this.renderDayEvents(dateStr, dayEvents);
      });

      daysGrid.appendChild(d);
    }

    // Auto-select first event or today
    const upcoming = appState.events.find(e => new Date(e.date + 'T23:59:59') >= new Date());
    if (upcoming) {
      const dayEl = Array.from(daysGrid.querySelectorAll('.cal-day:not(.other-month)')).find(el => {
        const d = el.textContent.padStart(2, '0');
        const m = String(month + 1).padStart(2, '0');
        return `${year}-${m}-${d}` === upcoming.date;
      });
      if (dayEl) {
        dayEl.classList.add('selected');
        const dayEvents = appState.events.filter(e => e.date === upcoming.date);
        this.renderDayEvents(upcoming.date, dayEvents);
      }
    }
  }

  renderDayEvents(dateStr, events) {
    const container = document.getElementById('calDayEventsList');
    if (!container) return;

    if (!events || events.length === 0) {
      container.innerHTML = `
        <div style="text-align: center; padding: 36px 12px; color: var(--text-muted);">
          <p style="font-size: 1.5rem; margin-bottom: 6px;">📅</p>
          <p>No volunteer sessions scheduled for <strong>${dateStr}</strong>.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = events.map(ev => this.createEventCardHTML(ev)).join('');
  }

  setupUpcomingEvents() {
    const container = document.getElementById('upcomingEventsGrid');
    if (!container) return;

    const now = new Date();
    now.setHours(0, 0, 0, 0);

    const upcoming = appState.events.filter(e => new Date(e.date + 'T23:59:59') >= now).slice(0, 6);

    if (upcoming.length === 0) {
      container.innerHTML = '<p style="color:var(--text-muted); text-align:center; grid-column: 1/-1;">No upcoming sessions scheduled at this time.</p>';
      return;
    }

    container.innerHTML = upcoming.map(ev => this.createEventCardHTML(ev)).join('');
  }

  createEventCardHTML(ev) {
    const spotsLeft = Math.max(0, ev.maxPeople - ev.people);
    const pct = Math.min(100, Math.round((ev.people / ev.maxPeople) * 100));
    const isFull = spotsLeft <= 0;

    return `
      <div class="event-card">
        <img src="${ev.image || SITE_CONFIG.defaultImage}" alt="${ev.title}" class="event-card-img" onerror="this.src='${SITE_CONFIG.defaultImage}'">
        <div class="event-card-body">
          <h4 class="event-card-title">${ev.title}</h4>
          <div class="event-meta">
            <div>📅 <strong>Date:</strong> ${ev.date}</div>
            <div>⏰ <strong>Time:</strong> ${ev.time}</div>
            <div>📍 <strong>Location:</strong> ${ev.location}</div>
          </div>
          <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">${ev.description || ''}</p>
          <div class="capacity-tracker">
            <div class="capacity-label">
              <span>Volunteers: ${ev.people} / ${ev.maxPeople}</span>
              <span style="color: ${isFull ? 'var(--status-danger)' : 'var(--status-success)'};">
                ${isFull ? 'Event Full' : `${spotsLeft} spots available`}
              </span>
            </div>
            <div class="progress-bar-bg">
              <div class="progress-bar-fill ${isFull ? 'full' : ''}" style="width: ${pct}%;"></div>
            </div>
          </div>
          <button class="btn-cta" style="width: 100%; margin-top: auto;" 
                  ${isFull ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : `onclick="openVolunteerModal(${ev.id}, '${ev.title.replace(/'/g, "\\'")}', '${ev.date}', '${ev.time}')"`}>
            ${isFull ? 'Event Full' : 'Sign Up to Volunteer →'}
          </button>
        </div>
      </div>
    `;
  }
}

/**
 * Drop-Off Locations with Instant Search (Priority Feature)
 */
class LocationsManager {
  constructor() {
    this.searchTerm = '';
    this.activeLetter = '';
  }

  async init() {
    const container = document.getElementById('locationsCardList');
    if (container) container.innerHTML = '<p style="text-align:center; padding:30px;">Loading locations...</p>';

    try {
      const res = await fetch('get-locations.php');
      appState.locations = await res.json();
      appState.locations.sort((a, b) => a.name.localeCompare(b.name));
      this.render();
      this.setupSearch();
    } catch (e) {
      console.error('Error loading locations:', e);
      if (container) container.innerHTML = '<p style="color:var(--status-danger); text-align:center;">Failed to load locations.</p>';
    }
  }

  setupSearch() {
    const input = document.getElementById('locationSearchInput');
    if (input) {
      input.addEventListener('input', (e) => {
        this.searchTerm = e.target.value.toLowerCase().trim();
        this.activeLetter = '';
        this.render();
      });
    }

    // A-Z chips
    document.querySelectorAll('.az-chip').forEach(chip => {
      chip.addEventListener('click', (e) => {
        e.preventDefault();
        const letter = chip.getAttribute('data-letter');
        if (this.activeLetter === letter) {
          this.activeLetter = '';
          chip.classList.remove('active');
        } else {
          document.querySelectorAll('.az-chip').forEach(c => c.classList.remove('active'));
          this.activeLetter = letter;
          chip.classList.add('active');
        }
        this.render();
      });
    });
  }

  render() {
    const container = document.getElementById('locationsCardList');
    const counter = document.getElementById('locationsMatchCount');
    if (!container) return;

    let filtered = appState.locations;

    if (this.searchTerm) {
      filtered = filtered.filter(loc => {
        const fullString = `${loc.name} ${loc.address} ${loc.phone || ''}`.toLowerCase();
        return fullString.includes(this.searchTerm);
      });
    }

    if (this.activeLetter) {
      filtered = filtered.filter(loc => loc.name.toUpperCase().startsWith(this.activeLetter));
    }

    if (counter) {
      counter.textContent = `Showing ${filtered.length} of ${appState.locations.length} locations`;
    }

    if (filtered.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
          <p style="font-size: 1.5rem; margin-bottom: 8px;">🔍</p>
          <p>No locations matched "<strong>${this.searchTerm || this.activeLetter}</strong>".</p>
        </div>
      `;
      return;
    }

    container.innerHTML = filtered.map(loc => {
      const gMapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.name + ' ' + loc.address)}`;
      const phoneDigits = (loc.phone || '').replace(/\D/g, '');
      const phoneHtml = phoneDigits ? `<a href="tel:+1${phoneDigits}">📞 ${loc.phone}</a>` : '<span>-</span>';

      return `
        <div class="location-item-card">
          <div>
            <h4>${loc.name}</h4>
            <p class="addr">📍 ${loc.address}</p>
          </div>
          <div class="location-card-footer">
            ${phoneHtml}
            <a href="${gMapsUrl}" target="_blank" rel="noopener noreferrer" class="dir-btn">Directions &rarr;</a>
          </div>
        </div>
      `;
    }).join('');
  }
}

/**
 * Interactive Map (MapLibre GL)
 */
function initMapIfNeeded() {
  const mapContainer = document.getElementById('allLocationsMap');
  if (!mapContainer || typeof maplibregl === 'undefined' || appState.locations.length === 0) return;

  if (appState.mapLoaded && appState.mapInstance) {
    appState.mapInstance.resize();
    return;
  }

  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const mapStyle = isDark ? 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json'
                          : 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json';

  try {
    appState.mapInstance = new maplibregl.Map({
      container: 'allLocationsMap',
      style: mapStyle,
      center: [-97.2, 32.75], // DFW center
      zoom: 9
    });

    appState.mapInstance.addControl(new maplibregl.NavigationControl(), 'top-right');

    const bounds = new maplibregl.LngLatBounds();
    let validPins = 0;

    appState.locations.forEach(loc => {
      if (loc.lat && loc.lng) {
        validPins++;
        bounds.extend([loc.lng, loc.lat]);

        const gMaps = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.name + ' ' + loc.address)}`;
        const popup = new maplibregl.Popup({ offset: 25 }).setHTML(`
          <div style="font-weight: bold; color: #004990; margin-bottom: 4px;">${loc.name}</div>
          <div style="font-size: 0.85em; color: #374151; margin-bottom: 8px;">${loc.address}</div>
          <a href="${gMaps}" target="_blank" style="display:inline-block; background:#0284c7; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8em;">Get Directions</a>
        `);

        new maplibregl.Marker({ color: '#0284c7' })
          .setLngLat([loc.lng, loc.lat])
          .setPopup(popup)
          .addTo(appState.mapInstance);
      }
    });

    if (validPins > 0) {
      appState.mapInstance.fitBounds(bounds, { padding: 40, maxZoom: 14 });
    }

    appState.mapLoaded = true;
  } catch (err) {
    console.error('MapLibre init error:', err);
  }
}

function updateMapTheme(theme) {
  if (appState.mapInstance && appState.mapLoaded) {
    const style = theme === 'dark' ? 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json'
                                   : 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json';
    appState.mapInstance.setStyle(style);
  }
}

/**
 * Dynamic Content Fetcher (Board, Partners, Testimonials, Gallery)
 */
async function loadDynamicContent() {
  // 1. Board Members
  try {
    const res = await fetch('data/board.json');
    const board = await res.json();
    const currentContainer = document.getElementById('currentBoardGrid');
    const prevContainer = document.getElementById('prevBoardGrid');

    const currentList = board.filter(b => !b.tag || b.tag === 'current');
    const prevList = board.filter(b => b.tag === 'previous');

    if (currentContainer) {
      currentContainer.innerHTML = currentList.map(b => `
        <div class="board-card">
          <img src="${b.image || SITE_CONFIG.defaultImage}" alt="${b.name}" onerror="this.src='${SITE_CONFIG.defaultImage}'">
          <h4>${b.name}</h4>
        </div>
      `).join('');
    }

    if (prevContainer) {
      prevContainer.innerHTML = prevList.map(b => `
        <div class="board-card">
          <img src="${b.image || SITE_CONFIG.defaultImage}" alt="${b.name}" onerror="this.src='${SITE_CONFIG.defaultImage}'">
          <h4>${b.name}</h4>
        </div>
      `).join('');
    }
  } catch (e) {
    console.error('Error loading board:', e);
  }

  // 2. Partners
  try {
    const res = await fetch('data/partners.json');
    const partners = await res.json();
    const container = document.getElementById('partnersListContainer');
    if (container) {
      container.innerHTML = partners.map(p => `<li>${p}</li>`).join('');
    }
  } catch (e) {
    console.error('Error loading partners:', e);
  }

  // 3. Testimonials
  try {
    const res = await fetch('data/testimonials.json');
    const testimonials = await res.json();
    const container = document.getElementById('testimonialsGrid');
    if (container) {
      container.innerHTML = testimonials.map(t => `
        <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
          <p style="font-style: italic; margin-bottom: 16px; font-size: 0.95rem;">"${t.text}"</p>
          <div>
            <strong style="display:block; color:var(--text-main);">${t.author}</strong>
            <span style="font-size: 0.85rem; color:var(--text-muted);">${t.organization}</span>
          </div>
        </div>
      `).join('');
    }
  } catch (e) {
    console.error('Error loading testimonials:', e);
  }

  // 4. Gallery Images for Carousel
  try {
    const res = await fetch('get-gallery-images.php');
    const images = await res.json();
    const track = document.getElementById('galleryMarqueeTrack');
    if (track && images.length > 0) {
      // Repeat images for seamless loop
      const fullList = [...images, ...images];
      track.innerHTML = fullList.map(img => `<img src="${img}" alt="ERC Volunteer Action" loading="lazy">`).join('');
    }
  } catch (e) {
    console.error('Error loading gallery:', e);
  }
}

/**
 * Volunteer Registration Dialog & Submission
 */
window.openVolunteerModal = function(id, title, date, time) {
  const dialog = document.getElementById('signupDialog');
  if (!dialog) return;

  document.getElementById('modalEventId').value = id;
  document.getElementById('modalEventTitleDisplay').textContent = title;
  document.getElementById('modalEventTimeDisplay').textContent = `${date} • ${time}`;

  dialog.showModal();
};

function setupVolunteerForm() {
  const form = document.getElementById('volunteerRegistrationForm');
  const dialog = document.getElementById('signupDialog');
  const closeBtn = document.getElementById('dialogCloseBtn');

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
      submitBtn.textContent = 'Submitting...';
      submitBtn.disabled = true;

      const payload = {
        eventId: document.getElementById('modalEventId').value,
        name: document.getElementById('modalVolName').value,
        email: document.getElementById('modalVolEmail').value,
        phone: document.getElementById('modalVolPhone').value
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

          // Refresh calendar events
          if (window.calendarManager) {
            await window.calendarManager.fetchEvents();
            window.calendarManager.setupUpcomingEvents();
            window.calendarManager.renderMonthCalendar();
          }
        } else {
          showToast(data.message || 'Registration failed', 'error');
        }
      } catch (err) {
        showToast('Network error while registering. Please try again.', 'error');
      } finally {
        submitBtn.textContent = prevText;
        submitBtn.disabled = false;
      }
    });
  }
}

/**
 * Contact Form AJAX Handler
 */
function setupContactForm() {
  const form = document.getElementById('siteContactForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.textContent = 'Sending Message...';
    btn.disabled = true;

    const formData = new FormData(form);

    try {
      const res = await fetch('send-email.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message, 'success');
        form.reset();
      } else {
        showToast(data.message || 'Unable to send message.', 'error');
      }
    } catch (err) {
      showToast('Connection error. Please try again or call us directly.', 'error');
    } finally {
      btn.textContent = originalText;
      btn.disabled = false;
    }
  });
}

// App Initialization
document.addEventListener('DOMContentLoaded', () => {
  new Router();
  new ThemeManager();
  
  window.calendarManager = new CalendarManager();
  window.calendarManager.init();

  window.locationsManager = new LocationsManager();
  window.locationsManager.init();

  loadDynamicContent();
  setupVolunteerForm();
  setupContactForm();
});
