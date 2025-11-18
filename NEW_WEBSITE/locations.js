// All drop-off locations
const allLocations = [
    // Eye Care Providers
    {name: 'Advanced Vision Care', address: '4919 South Hulen Street, Fort Worth, TX 76132', lat: 32.6889, lng: -97.3561},
    {name: 'Advantage Eyecare', address: '6509 Precinct Line Rd, North Richland Hills, TX 76180', lat: 32.8347, lng: -97.2261},
    {name: 'Aledo Family Eye Care', address: '103 Main Street, Aledo, TX 76008', lat: 32.6674, lng: -97.6446},
    {name: 'Barton Eye Associates', address: '5616 Colleyville Boulevard, Colleyville, TX 76034', lat: 32.8846, lng: -97.1895},
    {name: 'Cataract & Eye Center Of Cleburne', address: '1020 W Henderson Street, Cleburne, TX 76033', lat: 32.3543, lng: -97.3942},
    {name: 'Clear Eye Care', address: '116 S. Coppell Road, Coppell, TX 75019', lat: 32.9021, lng: -97.0086},
    {name: 'Clearview Eyecare and Laser Center', address: '107 East Main Street, Southlake, TX 76092', lat: 32.9245, lng: -97.1518},
    {name: 'Colleyville Vision Associates', address: '5616 Colleyville Boulevard, Colleyville, TX 76034', lat: 32.8846, lng: -97.1895},
    {name: 'Costco Optical - Fort Worth', address: '3020 W 7th Street, Fort Worth, TX 76107', lat: 32.7538, lng: -97.3756},
    {name: 'Costco Optical - Arlington', address: '2400 N. Collins Street, Arlington, TX 76010', lat: 32.7500, lng: -97.1086},
    {name: 'Eyecrafters Optometry', address: '100 E. Main Street, Grand Prairie, TX 75050', lat: 32.7458, lng: -97.0038},
    {name: 'Eyes Nouveau', address: '404 E. Main Street, Southlake, TX 76092', lat: 32.9265, lng: -97.1528},
    {name: 'First Eye Care', address: '401 N Main Street, Hurst, TX 76053', lat: 32.8147, lng: -97.1686},
    {name: 'Harwood Vision Clinic', address: '2300 W. Pioneer Parkway, Bedford, TX 76021', lat: 32.8369, lng: -97.1883},
    {name: 'Heritage Eye Center', address: '5800 Legacy Drive, Plano, TX 75024', lat: 33.0512, lng: -96.8134},
    {name: 'JCPenney Optical - Arlington', address: '801 S. Cooper Street, Arlington, TX 76010', lat: 32.7326, lng: -97.1048},
    {name: 'LensCrafters - Southlake', address: '1751 S. White Chapel Boulevard, Southlake, TX 76092', lat: 32.8952, lng: -97.1659},
    {name: 'Sam\'s Club Optical - Fort Worth', address: '3020 W 7th Street, Fort Worth, TX 76107', lat: 32.7538, lng: -97.3756},
    {name: 'Super Target Optical', address: '2800 Bass Pro Drive, Grapevine, TX 76051', lat: 32.9647, lng: -97.0911},
    {name: 'Target Optical', address: '123 Central Expressway, Lewisville, TX 75057', lat: 33.0018, lng: -96.9850},
    
    // Libraries & Public Locations
    {name: 'Bedford Public Library', address: '2424 Forest Ridge Drive, Bedford, TX 76021', lat: 32.8426, lng: -97.1789},
    {name: 'Cleburne Public Library', address: '3050 W. Henderson Street, Cleburne, TX 76033', lat: 32.3581, lng: -97.3976},
    {name: 'Euless Public Library', address: '201 W. Main Street, Euless, TX 76039', lat: 32.8375, lng: -97.1834},
    {name: 'Grandview Library', address: '302 N. Highway 67, Grandview, TX 76050', lat: 32.1958, lng: -97.2063},
    {name: 'Lewisville Public Library', address: '1197 W. Main Street, Lewisville, TX 75057', lat: 33.0034, lng: -96.9910},
    {name: 'North Richland Hills Senior Center', address: '6800 Bluff Springs Road, North Richland Hills, TX 76180', lat: 32.8341, lng: -97.2287},
    {name: 'Watauga Senior Center', address: '5621 Bunker Blvd, Watauga, TX 76148', lat: 32.8281, lng: -97.2539},
    {name: 'Arlington Parks & Recreation', address: '1305 W. Division Street, Arlington, TX 76010', lat: 32.7351, lng: -97.1109},
    {name: 'Coppell Community Center', address: '200 W. Parkway Boulevard, Coppell, TX 75019', lat: 32.9038, lng: -97.0073},
    {name: 'Southlake Community Center', address: '1200 Main Street, Southlake, TX 76092', lat: 32.9247, lng: -97.1535},
    
    // Retail & Other
    {name: 'CVS - Fort Worth', address: '3401 W. 7th Street, Fort Worth, TX 76107', lat: 32.7545, lng: -97.3741},
    {name: 'Walgreens - Arlington', address: '3101 W. Division Street, Arlington, TX 76010', lat: 32.7351, lng: -97.1180},
    {name: 'Wal-Mart Vision Center', address: '2701 N. Fielder Road, Arlington, TX 76010', lat: 32.7468, lng: -97.1045},
    {name: 'Main Center', address: '5621 Bunker Blvd, Watauga, TX 76148', lat: 32.8281, lng: -97.2539}
];

// Initialize map when DOM is ready
function initializeMap() {
    const mapContainer = document.getElementById('allLocationsMap');
    if (!mapContainer) return;

    // Initialize MapLibre GL map
    const map = new maplibregl.Map({
        container: 'allLocationsMap',
        style: 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',
        center: [-97.2, 32.75],
        zoom: 10
    });

    // Add markers for all locations
    allLocations.forEach((location, index) => {
        const el = document.createElement('div');
        el.className = 'marker';
        el.style.backgroundImage = 'url(\'images/marker-icon.svg\')';
        el.style.backgroundSize = '100%';
        el.style.width = '30px';
        el.style.height = '30px';
        el.style.cursor = 'pointer';

        const popup = new maplibregl.Popup({ offset: 25 })
            .setHTML(`<div style="font-weight: bold; color: #0fbe7c;">${location.name}</div><div style="font-size: 0.9em;">${location.address}</div>`);

        new maplibregl.Marker(el)
            .setLngLat([location.lng, location.lat])
            .setPopup(popup)
            .addTo(map);
    });

    // Fit map bounds to show all markers
    const bounds = new maplibregl.LngLatBounds();
    allLocations.forEach(location => {
        bounds.extend([location.lng, location.lat]);
    });
    map.fitBounds(bounds, { padding: 50 });
}

// Call map init when locations tab is active
document.addEventListener('DOMContentLoaded', () => {
    let mapInitialized = false;

    const initMapOnDemand = () => {
        const mapContainer = document.getElementById('allLocationsMap');
        if (mapContainer && !mapInitialized) {
            // Check if container is visible
            if (mapContainer.offsetParent !== null || document.getElementById('locations').classList.contains('active')) {
                mapInitialized = true;
                initializeMap();
            }
        }
    };

    // Try immediately if we are on the locations tab
    if (document.getElementById('locations').classList.contains('active')) {
        setTimeout(initMapOnDemand, 100);
    }

    // Listen for tab changes via hash
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#locations') {
            setTimeout(initMapOnDemand, 100);
        }
    });

    // Also listen for clicks on location buttons as a backup
    document.querySelectorAll('[data-tab="locations"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(initMapOnDemand, 100));
    });
});
