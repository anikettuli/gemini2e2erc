// Lazy Drop-Off Locations Directory & MapLibre GL Loader
let allLocations = [];
let locationsLoaded = false;
let locationsLoadingPromise = null;
let mapInitialized = false;
let mapInstance = null;
let mapLibreLoadPromise = null;

const MAP_STYLE_LIGHT = 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json';
const MAP_STYLE_DARK = 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json';

const MAPLIBRE_CSS_URL = 'https://cdn.jsdelivr.net/npm/maplibre-gl@4.0.0/dist/maplibre-gl.css';
const MAPLIBRE_CSS_SRI = 'sha384-lwiCiEXwjwuSiTeYhtgeL/ADQBiUjDsQkKHCwT0j+8iKuBrb7skjKQAYQ+cpZspI';
const MAPLIBRE_JS_URL = 'https://cdn.jsdelivr.net/npm/maplibre-gl@4.0.0/dist/maplibre-gl.js';
const MAPLIBRE_JS_SRI = 'sha384-C8SvKhT0WT55AOw5pvwVG699JuuZgN0KFt/4wtpRPhSl+TzRYqDUravAfuy13vJK';

function loadMapLibreAssets() {
  if (window.maplibregl) {
    return Promise.resolve(window.maplibregl);
  }
  if (mapLibreLoadPromise) {
    return mapLibreLoadPromise;
  }

  mapLibreLoadPromise = new Promise((resolve, reject) => {
    if (!document.querySelector(`link[href="${MAPLIBRE_CSS_URL}"]`)) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = MAPLIBRE_CSS_URL;
      link.integrity = MAPLIBRE_CSS_SRI;
      link.crossOrigin = 'anonymous';
      document.head.appendChild(link);
    }

    const script = document.createElement('script');
    script.src = MAPLIBRE_JS_URL;
    script.integrity = MAPLIBRE_JS_SRI;
    script.crossOrigin = 'anonymous';
    script.defer = true;
    script.onload = () => resolve(window.maplibregl);
    script.onerror = () => {
      mapLibreLoadPromise = null;
      reject(new Error('Failed to load MapLibre GL'));
    };
    document.head.appendChild(script);
  });

  return mapLibreLoadPromise;
}

async function loadLocations() {
  if (locationsLoaded) {
    checkAndInitMap();
    return;
  }
  if (locationsLoadingPromise) {
    return locationsLoadingPromise;
  }

  locationsLoadingPromise = (async () => {
    try {
      const response = await fetch('get-locations.php', { cache: 'no-cache' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      allLocations = Array.isArray(data) ? data : [];
      locationsLoaded = true;
      renderLocationsList();
      checkAndInitMap();
    } catch (error) {
      console.error('Error loading locations:', error);
      const container = document.getElementById('locations-list');
      if (container) {
        const p = document.createElement('p');
        p.className = 'text-center p-2';
        p.textContent = 'Error loading locations. Please try refreshing the page.';
        container.replaceChildren(p);
      }
    } finally {
      locationsLoadingPromise = null;
    }
  })();

  return locationsLoadingPromise;
}

function renderLocationsList() {
  const container = document.getElementById('locations-list');
  const azContainer = document.getElementById('az-directory');
  if (!container || !azContainer) return;

  container.replaceChildren();
  azContainer.replaceChildren();

  allLocations.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));

  const firstLetters = new Set();
  const listFragment = document.createDocumentFragment();
  const azFragment = document.createDocumentFragment();

  allLocations.forEach(loc => {
    const locName = String(loc.name || '').trim();
    const locAddress = String(loc.address || '').trim();
    const locPhone = String(loc.phone || '').trim();
    const phoneDigits = locPhone.replace(/\D/g, '');
    const directionsLink = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(locAddress)}`;

    const firstChar = (locName.charAt(0) || '#').toUpperCase();
    const isNewLetter = /^[A-Z0-9]$/.test(firstChar) && !firstLetters.has(firstChar);

    const row = document.createElement('div');
    row.className = 'location-row';

    if (isNewLetter) {
      firstLetters.add(firstChar);
      row.id = `loc-letter-${firstChar}`;

      const azLink = document.createElement('a');
      azLink.href = `#loc-letter-${firstChar}`;
      azLink.textContent = firstChar;
      azLink.setAttribute('aria-label', `Jump to locations starting with ${firstChar}`);
      azLink.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.getElementById(`loc-letter-${firstChar}`);
        if (target) {
          const headerHeight = document.querySelector('header')?.offsetHeight || 70;
          const top = target.getBoundingClientRect().top + window.scrollY - headerHeight - 12;
          const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          window.scrollTo({ top, behavior: prefersReduced ? 'auto' : 'smooth' });
        }
      });
      azFragment.appendChild(azLink);
    }

    const nameCell = document.createElement('div');
    nameCell.className = 'location-name';
    nameCell.textContent = locName;

    const addressCell = document.createElement('div');
    addressCell.className = 'location-address';
    addressCell.textContent = locAddress;

    const phoneCell = document.createElement('div');
    phoneCell.className = 'location-phone';
    if (phoneDigits.length >= 7) {
      const phoneAnchor = document.createElement('a');
      phoneAnchor.href = phoneDigits.length === 11 && phoneDigits.startsWith('1')
        ? `tel:+${phoneDigits}`
        : `tel:+1${phoneDigits}`;
      phoneAnchor.textContent = locPhone;
      phoneCell.appendChild(phoneAnchor);
    } else if (locPhone !== '') {
      phoneCell.textContent = locPhone;
    } else {
      const dash = document.createElement('span');
      dash.className = 'text-dim';
      dash.textContent = '—';
      phoneCell.appendChild(dash);
    }

    const linkCell = document.createElement('div');
    linkCell.className = 'location-link';
    const dirAnchor = document.createElement('a');
    dirAnchor.href = directionsLink;
    dirAnchor.target = '_blank';
    dirAnchor.rel = 'noopener noreferrer';
    dirAnchor.textContent = 'Directions';
    dirAnchor.setAttribute('aria-label', `Directions to ${locName}`);
    linkCell.appendChild(dirAnchor);

    row.append(nameCell, addressCell, phoneCell, linkCell);
    listFragment.appendChild(row);
  });

  azContainer.appendChild(azFragment);
  container.appendChild(listFragment);
}

function getMapStyle() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  return isDark ? MAP_STYLE_DARK : MAP_STYLE_LIGHT;
}

async function initializeMap() {
  const mapContainer = document.getElementById('allLocationsMap');
  if (!mapContainer) return;

  try {
    await loadMapLibreAssets();
  } catch (error) {
    console.error('Error loading map library:', error);
    mapInitialized = false;
    const msg = document.createElement('p');
    msg.className = 'text-center p-2 text-dim';
    msg.textContent = 'Interactive map unavailable. Please browse the directory below.';
    mapContainer.replaceChildren(msg);
    return;
  }

  mapInstance = new maplibregl.Map({
    container: 'allLocationsMap',
    style: getMapStyle(),
    center: [-97.2, 32.75],
    zoom: 9
  });

  mapInstance.addControl(new maplibregl.NavigationControl());

  const bounds = new maplibregl.LngLatBounds();
  let hasValidLocation = false;

  allLocations.forEach(loc => {
    const lat = Number(loc.lat);
    const lng = Number(loc.lng);
    if (Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0)) {
      hasValidLocation = true;
      const el = document.createElement('div');
      el.className = 'marker';
      el.style.backgroundImage = "url('images/marker-icon.svg')";
      el.style.backgroundSize = '100%';
      el.style.width = '30px';
      el.style.height = '30px';
      el.style.cursor = 'pointer';
      el.setAttribute('role', 'button');
      el.setAttribute('aria-label', String(loc.name || 'Drop-off location'));

      const popupContent = document.createElement('div');
      const popupTitle = document.createElement('div');
      popupTitle.className = 'map-popup-title';
      popupTitle.textContent = String(loc.name || '');

      const popupAddr = document.createElement('div');
      popupAddr.className = 'map-popup-address';
      popupAddr.textContent = String(loc.address || '');

      const popupLink = document.createElement('a');
      popupLink.className = 'map-popup-btn';
      popupLink.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(String(loc.address || ''))}`;
      popupLink.target = '_blank';
      popupLink.rel = 'noopener noreferrer';
      popupLink.textContent = 'Get Directions';

      popupContent.append(popupTitle, popupAddr, popupLink);

      const popup = new maplibregl.Popup({ offset: 25 }).setDOMContent(popupContent);

      new maplibregl.Marker(el)
        .setLngLat([lng, lat])
        .setPopup(popup)
        .addTo(mapInstance);

      bounds.extend([lng, lat]);
    }
  });

  if (hasValidLocation) {
    mapInstance.fitBounds(bounds, { padding: 50 });
  }
}

function checkAndInitMap() {
  const locationsTab = document.getElementById('locations');
  if (locationsTab && locationsTab.classList.contains('active') && allLocations.length > 0) {
    if (!mapInitialized) {
      mapInitialized = true;
      setTimeout(initializeMap, 50);
    } else if (mapInstance) {
      mapInstance.resize();
    }
  }
}

function updateMapTheme() {
  if (mapInstance && mapInitialized) {
    mapInstance.setStyle(getMapStyle());
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
        updateMapTheme();
      }
    }
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme']
  });
});

window.ensureLocationsLoaded = loadLocations;
