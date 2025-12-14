<?php
// get-locations.php
// Handles serving locations with server-side geocoding and caching.

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

$cacheUpdated = false;

// Helper function to clean address
function cleanAddress($address) {
    // Remove Suite, Ste, Unit, #, etc.
    $pattern = '/(?:Suite|Ste|Unit|Apt|#)\s*[\w-]+,?/i';
    $clean = preg_replace($pattern, '', $address);
    return trim($clean);
}

// Helper function to geocode
function getCoordinates($address) {
    $cleanAddr = cleanAddress($address);
    $encodedAddr = urlencode($cleanAddr);
    
    // 1. Try US Census API
    $url = "https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?address={$encodedAddr}&benchmark=Public_AR_Current&format=json";
    
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($url, false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['result']['addressMatches'])) {
            $match = $data['result']['addressMatches'][0];
            return [
                'lat' => $match['coordinates']['y'],
                'lng' => $match['coordinates']['x']
            ];
        }
    }

    // 2. Fallback to Nominatim (OpenStreetMap)
    // Sleep to respect rate limits (1 second)
    sleep(1);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddr}&limit=1";
    $opts = [
        "http" => [
            "header" => "User-Agent: 2E2ERC-Website/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data)) {
            return [
                'lat' => floatval($data[0]['lat']),
                'lng' => floatval($data[0]['lon'])
            ];
        }
    }

    return null;
}

// Process Locations
foreach ($locations as &$loc) {
    $address = $loc['address'];
    
    // Check Cache
    if (isset($cache[$address])) {
        $loc['lat'] = $cache[$address]['lat'];
        $loc['lng'] = $cache[$address]['lng'];
    } else {
        // Not in cache, fetch it
        $coords = getCoordinates($address);
        
        if ($coords) {
            $loc['lat'] = $coords['lat'];
            $loc['lng'] = $coords['lng'];
            
            // Save to cache
            $cache[$address] = $coords;
            $cacheUpdated = true;
        }
    }
}

// Save Cache if updated
if ($cacheUpdated) {
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
}

// Return JSON
echo json_encode($locations);
?>
