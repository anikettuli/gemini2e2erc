// Global variable to store locations
let allLocations = [];
let mapInitialized = false;

// Function to fetch locations
async function loadLocations() {
    try {
        // Fetch from PHP script which handles caching and geocoding
        const response = await fetch('get-locations.php');
        allLocations = await response.json();
        renderLocationsList();
        
        // If we are already on the locations tab, init map
        if (document.getElementById('locations').classList.contains('active')) {
            initMapOnDemand();
        }
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

// Initialize map
async function initializeMap() {
    const mapContainer = document.getElementById('allLocationsMap');
    if (!mapContainer) return;

    // Initialize MapLibre GL map
    const map = new maplibregl.Map({
        container: 'allLocationsMap',
        style: 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',
        center: [-97.2, 32.75], // Default center (DFW)
        zoom: 9
    });

    // Add navigation controls
    map.addControl(new maplibregl.NavigationControl());

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

            const popup = new maplibregl.Popup({ offset: 25 })
                .setHTML(`<div style="font-weight: bold; color: #0fbe7c;">${loc.name}</div><div style="font-size: 0.9em;">${loc.address}</div>`);

            new maplibregl.Marker(el)
                .setLngLat([loc.lng, loc.lat])
                .setPopup(popup)
                .addTo(map);

            bounds.extend([loc.lng, loc.lat]);
        }
    });

    if (hasValidLocation) {
        map.fitBounds(bounds, { padding: 50 });
    }
}

const initMapOnDemand = () => {
    const mapContainer = document.getElementById('allLocationsMap');
    if (mapContainer && !mapInitialized && allLocations.length > 0) {
        // Check if container is visible or we are on the tab
        if (mapContainer.offsetParent !== null || document.getElementById('locations').classList.contains('active')) {
            mapInitialized = true;
            initializeMap();
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    loadLocations();

    // Listen for tab changes to init map when visible
    const tabButtons = document.querySelectorAll('.js-tab-trigger');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.getAttribute('data-tab') === 'locations') {
                // Small delay to allow tab to become visible
                setTimeout(initMapOnDemand, 100);
            }
        });
    });
});
