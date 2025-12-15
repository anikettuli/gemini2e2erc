// Global variable to store locations
let allLocations = [];
let mapInitialized = false;
let mapInstance = null;

// Map Styles
const MAP_STYLE_LIGHT = 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json';
const MAP_STYLE_DARK = 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json';

// Function to fetch locations
async function loadLocations() {
  try {
    // Fetch from PHP script which handles caching and geocoding
    const response = await fetch('get-locations.php');
    allLocations = await response.json();
    renderLocationsList();

    // Try to init map if we are already on the locations tab
    checkAndInitMap();
  } catch (error) {
    console.error('Error loading locations:', error);
    const container = document.getElementById('locations-list');
    if (container) {
      container.innerHTML = '<p style="text-align:center; padding: 2rem;">Error loading locations. Please try refreshing the page.</p>';
    }
  }
}

// Function to render the list
function renderLocationsList() {
  const container = document.getElementById('locations-list');
  if (!container) return;

  container.innerHTML = '';

  allLocations.forEach(loc => {
    // Generate links dynamically
    const phoneDigits = loc.phone.replace(/\D/g, '');
    const phoneLink = `tel:+1${phoneDigits}`;
    const directionsLink = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address)}`;

    const row = document.createElement('div');
    row.className = 'location-row';
    row.innerHTML = `
            <div class="location-name">${loc.name}</div>
            <div class="location-address">${loc.address}</div>
            <div class="location-phone"><a href="${phoneLink}">${loc.phone}</a></div>
            <div class="location-link"><a href="${directionsLink}" target="_blank">Directions</a></div>
        `;
    container.appendChild(row);
  });
}

// Get current appropriate map style
function getMapStyle() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  return isDark ? MAP_STYLE_DARK : MAP_STYLE_LIGHT;
}

// Initialize map
async function initializeMap() {
  const mapContainer = document.getElementById('allLocationsMap');
  if (!mapContainer) return;

  // Initialize MapLibre GL map
  mapInstance = new maplibregl.Map({
    container: 'allLocationsMap',
    style: getMapStyle(),
    center: [-97.2, 32.75], // Default center (DFW)
    zoom: 9
  });

  // Add navigation controls
  mapInstance.addControl(new maplibregl.NavigationControl());

  const bounds = new maplibregl.LngLatBounds();
  let hasValidLocation = false;

  // Process locations
  allLocations.forEach(loc => {
    // Coordinates are now provided by the server (get-locations.php)
    if (loc.lat && loc.lng) {
      hasValidLocation = true;
      const el = document.createElement('div');
      el.className = 'marker';
      el.style.backgroundImage = 'url(\'images/marker-icon.svg\')';
      el.style.backgroundSize = '100%';
      el.style.width = '30px';
      el.style.height = '30px';
      el.style.cursor = 'pointer';

      // Check if we need to invert marker color for dark mode (though usually SVG handles it, 
      // but if it's an image, we might want a different one. 
      // For now keeping same marker as it is likely high contrast).

      const popup = new maplibregl.Popup({ offset: 25 })
        .setHTML(`<div style="font-weight: bold; color: #0fbe7c;">${loc.name}</div><div style="font-size: 0.9em; color: #333;">${loc.address}</div>`);
      // Force dark text in popup as map styles might affect it, or ensure popup css is robust.

      new maplibregl.Marker(el)
        .setLngLat([loc.lng, loc.lat])
        .setPopup(popup)
        .addTo(mapInstance);

      bounds.extend([loc.lng, loc.lat]);
    }
  });

  if (hasValidLocation) {
    mapInstance.fitBounds(bounds, { padding: 50 });
  }
}

const checkAndInitMap = () => {
  const locationsTab = document.getElementById('locations');
  const mapContainer = document.getElementById('allLocationsMap');

  // Check if locations tab is active/visible and we have data
  if (locationsTab && locationsTab.classList.contains('active') && allLocations.length > 0) {
    if (!mapInitialized) {
      mapInitialized = true;
      // distinct visual break for the user to see map loading
      setTimeout(initializeMap, 100);
    } else if (mapInstance) {
      // If already initialized, trigger a resize in case it was hidden
      mapInstance.resize();
    }
  }
};

// Update map style when theme changes
const updateMapTheme = () => {
  if (mapInstance && mapInitialized) {
    mapInstance.setStyle(getMapStyle());
  }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  loadLocations();

  // Listen for hash changes (handled by app.js router)
  window.addEventListener('hashchange', () => {
    setTimeout(checkAndInitMap, 100); // Small delay to allow tab switch to complete
  });

  // Also listen for tab clicks directly to be safe, though hashchange should cover it
  const tabButtons = document.querySelectorAll('.tab-button, .js-tab-trigger');
  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      if (button.getAttribute('data-tab') === 'locations') {
        setTimeout(checkAndInitMap, 100);
      }
    });
  });

  // Observe theme changes
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
        updateMapTheme();
      }
    });
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme']
  });
});
