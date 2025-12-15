<?php
// get-locations.php
// Handles serving locations merge with geo cache.
// Note: Actual geocoding is now handled by tools/warm-cache.php during deployment to ensure performance.

header('Content-Type: application/json');

// Files
$locationsFile = 'data/locations.json';
$cacheFile = 'data/geo_cache.json';

// Load Locations
if (!file_exists($locationsFile)) {
    echo json_encode([]);
    exit;
}
$locations = json_decode(file_get_contents($locationsFile), true);

// Load Cache
$cache = [];
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);
    if (!is_array($cache)) $cache = [];
}

// Merge Cache into Locations
foreach ($locations as &$loc) {
    $address = $loc['address'];
    
    // Check Cache
    if (isset($cache[$address])) {
        // If valid coordinates exist, use them
        if (isset($cache[$address]['lat']) && isset($cache[$address]['lng']) &&
            $cache[$address]['lat'] !== null && $cache[$address]['lng'] !== null) {
            $loc['lat'] = $cache[$address]['lat'];
            $loc['lng'] = $cache[$address]['lng'];
        }
    }
}

// Return JSON
echo json_encode($locations);
?>

