// Global variable to store locations
let allLocations = [];
let mapInitialized = false;
let mapInstance = null;

// Map Styles
const MAP_STYLE_LIGHT = 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json';
const MAP_STYLE_DARK = 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json';

// Function to fetch locations
async function loadLocations() {
  const container = document.getElementById('locations-list');
  const mapContainer = document.getElementById('allLocationsMap');

  // Only load if we are on the locations page logic (container exists)
  if (!container && !mapContainer) return;

  // SPA RESET: If map container exists but is empty, reset state
  if (mapContainer && mapContainer.innerHTML === '') {
    mapInitialized = false;
    if (mapInstance) {
      mapInstance.remove(); // Clean up old instance logic if still lingering
      mapInstance = null;
    }
  }

  try {
    const response = await fetch('get-locations.php');
    allLocations = await response.json();
    renderLocationsList();
    initMap();
  } catch (error) {
    console.error('Error loading locations:', error);
    if (container) {
      container.innerHTML = '<div class="text-center p-8 text-red-500">Error loading locations. Please try refreshing the page.</div>';
    }
  }
}

// Function to render the list
function renderLocationsList() {
  const container = document.getElementById('locations-list');
  if (!container) return;

  container.innerHTML = '';

  if (allLocations.length === 0) {
    container.innerHTML = '<div class="text-center p-8 text-slate-500">No partner locations found.</div>';
    return;
  }

  // Create a grid layout for the list
  const grid = document.createElement('div');
  grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4';

  allLocations.forEach(loc => {
    const phoneDigits = loc.phone ? loc.phone.replace(/\D/g, '') : '';
    const phoneLink = phoneDigits ? `tel:+1${phoneDigits}` : '#';
    const directionsLink = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address)}`;

    const card = document.createElement('div');
    card.className = 'bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-all border border-slate-100 dark:border-slate-700 block';

    card.innerHTML = `
        <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">${loc.name}</h3>
        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 h-10 line-clamp-2">${loc.address}</p>
        <div class="flex gap-3 text-sm">
            ${loc.phone ? `<a href="${phoneLink}" class="text-indigo-600 hover:text-indigo-500 font-medium">📞 Call</a>` : ''}
            <a href="${directionsLink}" target="_blank" class="text-indigo-600 hover:text-indigo-500 font-medium">🗺️ Directions</a>
        </div>
    `;
    grid.appendChild(card);
  });

  container.appendChild(grid);
}

function getMapStyle() {
  const isDark = document.documentElement.classList.contains('dark');
  return isDark ? MAP_STYLE_DARK : MAP_STYLE_LIGHT;
}

function initMap() {
  const mapContainer = document.getElementById('allLocationsMap');
  if (!mapContainer || mapInitialized) return;

  if (allLocations.length === 0) return; // Wait for data

  mapInitialized = true;

  // Initialize MapLibre GL map
  mapInstance = new maplibregl.Map({
    container: 'allLocationsMap',
    style: getMapStyle(),
    center: [-97.2, 32.75], // DFW center
    zoom: 9
  });

  mapInstance.addControl(new maplibregl.NavigationControl());

  const bounds = new maplibregl.LngLatBounds();
  let hasValidLocation = false;

  allLocations.forEach(loc => {
    if (loc.lat && loc.lng) {
      hasValidLocation = true;

      // Custom marker element
      const el = document.createElement('div');
      el.className = 'marker';
      el.style.backgroundImage = 'url(\'images/marker-icon.svg\')'; // Ensure this exists or use fallback
      el.style.width = '30px';
      el.style.height = '30px';
      el.style.backgroundSize = 'contain';
      el.style.backgroundRepeat = 'no-repeat';
      el.style.cursor = 'pointer';

      // Fallback if image missing
      el.onerror = () => { el.style.backgroundColor = '#6366f1'; el.style.borderRadius = '50%'; };

      const directionsLink = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address)}`;

      const popupHTML = `
        <div class="p-2 min-w-[200px]">
            <strong class="block text-slate-900 text-sm mb-1">${loc.name}</strong>
            <p class="text-slate-600 text-xs mb-2">${loc.address}</p>
            <a href="${directionsLink}" target="_blank" class="inline-block bg-indigo-600 text-white text-xs px-2 py-1 rounded hover:bg-indigo-700 transition-colors">Get Directions</a>
        </div>
      `;

      const popup = new maplibregl.Popup({ offset: 25, closeButton: false }).setHTML(popupHTML);

      new maplibregl.Marker({ element: el }) // Use the custom element if image exists, for now standard color is safer if no image
        .setLngLat([loc.lng, loc.lat])
        .setPopup(popup)
        .addTo(mapInstance);

      // If image marker path is dubious, use default color marker:
      // new maplibregl.Marker({ color: '#6366f1' })...

      bounds.extend([loc.lng, loc.lat]);
    }
  });

  if (hasValidLocation) {
    mapInstance.fitBounds(bounds, { padding: 50 });
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  loadLocations();

  // Watch for theme changes to update map style
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === 'attributes' && (mutation.attributeName === 'class' || mutation.attributeName === 'data-theme')) {
        if (mapInstance) mapInstance.setStyle(getMapStyle());
      }
    });
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class', 'data-theme']
  });
});
