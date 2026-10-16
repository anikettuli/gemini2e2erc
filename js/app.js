/**
 * Lions District 2-E2 Eyeglass Recycling Center
 * Client Application: Optical Refraction Simulator, Search, Map & Concurrency-Safe Volunteer Booking
 */

document.addEventListener('DOMContentLoaded', () => {
  initDiopterSimulator();
  initMobileNav();
  initLocationsAndMap();
  initVolunteerCalendar();
  initGallery();
  initBoard();
  initToastStatus();
});

/* ==========================================================================
   1. The Memorable Moment: Optical Diopter & Refraction Simulator
   ========================================================================== */
function initDiopterSimulator() {
  const slider = document.getElementById('diopterSlider');
  const lensImg = document.getElementById('simulatedLensImage');
  const badge = document.getElementById('diopterValue');
  const overlay = document.getElementById('lensOverlayMessage');

  if (!slider || !lensImg || !badge || !overlay) return;

  function updateFocus() {
    const progress = parseInt(slider.value, 10); // 0 to 100
    // Blur from 12px at progress 0 down to 0px at progress 100
    const blurAmount = ((100 - progress) / 100) * 12;
    lensImg.style.filter = `blur(${blurAmount.toFixed(1)}px)`;

    // Calculate simulated diopter power from -4.50 D down to 0.00 D
    const diopter = (-4.50 * (1 - progress / 100)).toFixed(2);

    if (progress >= 95) {
      badge.textContent = '0.00 D (20/20)';
      badge.style.color = '#38bdf8';
      badge.style.borderColor = '#38bdf8';
      overlay.textContent = 'Perfect optical correction achieved. A child can read, an adult can work, and a community thrives.';
    } else if (progress >= 50) {
      badge.textContent = `${diopter} D`;
      badge.style.color = '#f59e0b';
      badge.style.borderColor = '#f59e0b';
      overlay.textContent = 'Refraction in progress. Volunteer technicians neutralize spherical and cylindrical power on manual lensometers.';
    } else {
      badge.textContent = `${diopter} D`;
      badge.style.color = '#ffffff';
      badge.style.borderColor = 'rgba(255,255,255,0.2)';
      overlay.textContent = 'Simulating uncorrected myopia. Without corrective lenses, reading textbooks and classroom boards is impossible.';
    }
  }

  slider.addEventListener('input', updateFocus);
  updateFocus();
}

/* ==========================================================================
   2. Mobile Navigation Toggle
   ========================================================================== */
function initMobileNav() {
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('primaryNav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  // Close when clicking a nav link
  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    });
  });
}

/* ==========================================================================
   3. Drop-Off Partner Directory & Interactive MapLibre GL Map
   ========================================================================== */
let allLocations = [];
let mapInstance = null;
let mapMarkers = [];

async function initLocationsAndMap() {
  const listContainer = document.getElementById('partnerListContainer');
  const searchInput = document.getElementById('locationSearchInput');
  const countDisplay = document.getElementById('locationCountDisplay');

  try {
    const res = await fetch('get-locations.php');
    allLocations = await res.json();

    if (!Array.isArray(allLocations)) allLocations = [];

    renderPartnerList(allLocations);
    initMap(allLocations);

    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const filtered = allLocations.filter(loc => {
          const name = (loc.name || '').toLowerCase();
          const addr = (loc.address || '').toLowerCase();
          return name.includes(query) || addr.includes(query);
        });

        renderPartnerList(filtered);
        updateMapMarkers(filtered);

        if (countDisplay) {
          if (query) {
            countDisplay.textContent = `Found ${filtered.length} locations matching "${e.target.value.trim()}"`;
          } else {
            countDisplay.textContent = `Showing all ${allLocations.length} drop-off locations`;
          }
        }
      });
    }
  } catch (err) {
    console.error('Failed to load drop-off locations:', err);
    if (listContainer) {
      listContainer.innerHTML = '<p style="padding: 2rem; color: #ef4444;">Unable to load drop-off locations at this time. Please call (817) 710-5403.</p>';
    }
  }
}

function renderPartnerList(items) {
  const container = document.getElementById('partnerListContainer');
  if (!container) return;

  if (items.length === 0) {
    container.innerHTML = `
      <div style="padding: 2.5rem; text-align: center; color: var(--ink-secondary);">
        <p style="font-weight: 600; font-size: 1.125rem; margin-bottom: 0.5rem;">No drop-off locations found</p>
        <p style="font-size: 0.875rem;">Try searching for a nearby city like "Keller", "Fort Worth", "Arlington", or a 5-digit zip code.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = items.map(loc => {
    const mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address || loc.name)}`;
    const phoneClean = (loc.phone || '').replace(/[^\d]/g, '');
    const phoneLink = phoneClean ? `<a href="tel:+1${phoneClean}" class="partner-link">${escapeHtml(loc.phone)}</a>` : '';

    return `
      <article class="partner-item">
        <h3 class="partner-name">${escapeHtml(loc.name)}</h3>
        <p class="partner-address">${escapeHtml(loc.address)}</p>
        <div class="partner-footer">
          <a href="${mapUrl}" target="_blank" rel="noopener noreferrer" class="partner-link">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            Directions
          </a>
          ${phoneLink}
        </div>
      </article>
    `;
  }).join('');
}

function initMap(locations) {
  const mapElem = document.getElementById('map');
  if (!mapElem || typeof maplibregl === 'undefined') return;

  // DFW center coordinates
  mapInstance = new maplibregl.Map({
    container: 'map',
    style: 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',
    center: [-97.23, 32.88], // Watauga/Keller, TX
    zoom: 9
  });

  mapInstance.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
  updateMapMarkers(locations);
}

function updateMapMarkers(locations) {
  if (!mapInstance || typeof maplibregl === 'undefined') return;

  // Clear existing markers
  mapMarkers.forEach(m => m.remove());
  mapMarkers = [];

  const bounds = new maplibregl.LngLatBounds();
  let hasValidCoords = false;

  locations.forEach(loc => {
    if (loc.lat && loc.lng) {
      hasValidCoords = true;
      bounds.extend([loc.lng, loc.lat]);

      // Create pin element
      const el = document.createElement('div');
      el.style.width = '14px';
      el.style.height = '14px';
      el.style.backgroundColor = '#0284c7';
      el.style.border = '2px solid #ffffff';
      el.style.borderRadius = '50%';
      el.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
      el.style.cursor = 'pointer';

      const popup = new maplibregl.Popup({ offset: 12 }).setHTML(`
        <div style="font-family: sans-serif; font-size: 13px; line-height: 1.4; color: #0f172a; padding: 4px;">
          <strong style="display:block; margin-bottom: 2px;">${escapeHtml(loc.name)}</strong>
          <span style="color: #64748b;">${escapeHtml(loc.address)}</span>
          <br><a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address)}" target="_blank" style="color: #0284c7; font-weight: 600; font-size: 11px; text-decoration: none; margin-top: 4px; display: inline-block;">Get Directions</a>
        </div>
      `);

      const marker = new maplibregl.Marker(el)
        .setLngLat([loc.lng, loc.lat])
        .setPopup(popup)
        .addTo(mapInstance);

      mapMarkers.push(marker);
    }
  });

  if (hasValidCoords && locations.length < 50 && locations.length > 0) {
    mapInstance.fitBounds(bounds, { padding: 40, maxZoom: 13 });
  }
}

/* ==========================================================================
   4. Volunteer Calendar & Modal Sign Up
   ========================================================================== */
let allEvents = [];

async function initVolunteerCalendar() {
  const container = document.getElementById('eventsContainer');
  const modal = document.getElementById('volunteerModal');
  const form = document.getElementById('volunteerForm');
  const closeBtn = document.getElementById('modalCloseBtn');
  const cancelBtn = document.getElementById('modalCancelBtn');

  if (!container || !modal || !form) return;

  // Setup modal handlers
  closeBtn?.addEventListener('click', () => modal.close());
  cancelBtn?.addEventListener('click', () => modal.close());
  modal.addEventListener('click', (e) => {
    const rect = modal.getBoundingClientRect();
    if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
      modal.close();
    }
  });

  try {
    const res = await fetch('data/events.json?v=' + Date.now());
    allEvents = await res.json();

    if (!Array.isArray(allEvents)) allEvents = [];

    // Filter to future and upcoming events
    const today = new Date().toISOString().split('T')[0];
    const upcoming = allEvents.filter(ev => (ev.date || '') >= today);

    const displayEvents = upcoming.length > 0 ? upcoming : allEvents.slice(-6);

    container.innerHTML = displayEvents.map(ev => {
      const isFull = (ev.people || 0) >= (ev.maxPeople || 20);
      const pct = Math.min(100, Math.round(((ev.people || 0) / (ev.maxPeople || 20)) * 100));
      const formattedDate = formatDate(ev.date);

      return `
        <article class="event-card" id="event-card-${ev.id}">
          <img src="${escapeHtml(ev.image || 'images/events/second-saturday-session.jpg')}" alt="${escapeHtml(ev.title)}" class="event-img" onerror="this.src='images/volunteers-working.jpg'">
          <div class="event-body">
            <h3 class="event-title">${escapeHtml(ev.title)}</h3>
            <div class="event-details">
              <div class="event-detail-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>${formattedDate}</span>
              </div>
              <div class="event-detail-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>${escapeHtml(ev.time || '9:00 AM - 12:00 PM')}</span>
              </div>
              <div class="event-detail-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>${escapeHtml(ev.location || '5621 Bunker Blvd, Watauga, TX')}</span>
              </div>
            </div>

            <div class="capacity-meter">
              <div class="meter-labels">
                <span>Capacity</span>
                <span id="capacity-text-${ev.id}">${ev.people || 0} / ${ev.maxPeople || 20} registered</span>
              </div>
              <div class="meter-track">
                <div class="meter-fill" id="capacity-fill-${ev.id}" style="width: ${pct}%;"></div>
              </div>
            </div>

            <button type="button" class="btn ${isFull ? 'btn-outline' : 'btn-lens'}" ${isFull ? 'disabled' : ''} onclick="openVolunteerModal(${ev.id})">
              ${isFull ? 'Session is Full' : 'Volunteer for this session'}
            </button>
          </div>
        </article>
      `;
    }).join('');

  } catch (err) {
    console.error('Failed to load events:', err);
    container.innerHTML = '<p style="padding: 2rem; color: #ef4444;">Unable to load volunteer schedule. Please check back shortly.</p>';
  }

  // Handle Form Submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = document.getElementById('modalSubmitBtn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Registering...';
    submitBtn.disabled = true;

    const payload = {
      eventId: parseInt(document.getElementById('volunteerEventId').value, 10),
      name: document.getElementById('volName').value.trim(),
      email: document.getElementById('volEmail').value.trim(),
      phone: document.getElementById('volPhone').value.trim()
    };

    try {
      const response = await fetch('register-volunteer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await response.json();

      if (data.success) {
        showToast('Registration successful! Confirmation details have been sent to your email.', 'success');
        modal.close();
        form.reset();

        // Live update the capacity meter on the page
        if (data.newCount !== undefined) {
          const textEl = document.getElementById(`capacity-text-${payload.eventId}`);
          const fillEl = document.getElementById(`capacity-fill-${payload.eventId}`);
          const eventItem = allEvents.find(e => e.id === payload.eventId);
          const maxP = eventItem ? eventItem.maxPeople : 20;

          if (textEl) textEl.textContent = `${data.newCount} / ${maxP} registered`;
          if (fillEl) fillEl.style.width = `${Math.min(100, Math.round((data.newCount / maxP) * 100))}%`;
        }
      } else {
        showToast(data.message || 'Unable to complete registration.', 'error');
      }
    } catch (err) {
      showToast('Network error during registration. Please try again.', 'error');
    } finally {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });
}

// Global modal trigger
window.openVolunteerModal = function(eventId) {
  const modal = document.getElementById('volunteerModal');
  const event = allEvents.find(e => e.id === eventId);
  if (!modal || !event) return;

  document.getElementById('volunteerEventId').value = event.id;
  document.getElementById('modalTitle').textContent = `Sign Up: ${event.title}`;
  document.getElementById('modalEventInfo').textContent = `${formatDate(event.date)} • ${event.time} • ${event.location}`;

  modal.showModal();
};

/* ==========================================================================
   5. Mission Gallery Dynamic Loader
   ========================================================================== */
async function initGallery() {
  const container = document.getElementById('galleryContainer');
  if (!container) return;

  try {
    const res = await fetch('get-gallery-images.php');
    const images = await res.json();

    if (Array.isArray(images) && images.length > 0) {
      container.innerHTML = images.map(imgSrc => `
        <div class="gallery-item">
          <img src="${escapeHtml(imgSrc)}" alt="Lions ERC mission trip eyeglass distribution" loading="lazy">
          <div class="gallery-item-caption">Refurbished glasses on humanitarian mission</div>
        </div>
      `).join('');
    }
  } catch (err) {
    // Keep fallback static gallery if fetch fails
  }
}

/* ==========================================================================
   6. Board of Directors Loader
   ========================================================================== */
async function initBoard() {
  const container = document.getElementById('boardContainer');
  if (!container) return;

  try {
    const res = await fetch('data/board.json');
    const board = await res.json();

    if (Array.isArray(board) && board.length > 0) {
      container.innerHTML = board.map(m => `
        <div class="board-card">
          <img src="${escapeHtml(m.image || 'images/board/Jim-Cook.jpg')}" alt="${escapeHtml(m.name)}" class="board-avatar" onerror="this.src='e2e2rc_LOGO.png'">
          <h3 class="board-name">${escapeHtml(m.name)}</h3>
          <span class="board-role">${escapeHtml(m.role || 'Board Member')}</span>
          <span class="board-club">${escapeHtml(m.club || 'Lions District 2-E2')}</span>
        </div>
      `).join('');
    }
  } catch (err) {
    console.error('Failed to load board members:', err);
  }
}

/* ==========================================================================
   7. Toast Notification Utility
   ========================================================================== */
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = 'toast-notice';
  if (type === 'error') toast.style.borderLeftColor = '#ef4444';
  if (type === 'success') toast.style.borderLeftColor = '#10b981';

  toast.innerHTML = `
    <div style="flex-grow: 1; font-size: 0.9375rem;">${escapeHtml(message)}</div>
    <button type="button" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.25rem;" onclick="this.parentElement.remove()">&times;</button>
  `;

  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 6000);
}

function initToastStatus() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('status') === 'success') {
    showToast('Your message has been received. A Lions ERC coordinator will contact you shortly.', 'success');
  } else if (params.get('status') === 'error') {
    showToast('There was an error sending your message. Please call us directly at (817) 710-5403.', 'error');
  }
}

/* Utilities */
function formatDate(dateStr) {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const d = new Date(parts[0], parts[1] - 1, parts[2]);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
