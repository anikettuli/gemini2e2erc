<?php
// tools/warm-cache.php
// CLI script to pre-populate geo_cache.json during deployment

// Ensure we are running in CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

echo "Starting Geo Cache Warmup...\n";

$rootDir = dirname(__DIR__);
$locationsFile = $rootDir . '/data/locations.json';
$cacheFile = $rootDir . '/data/geo_cache.json';

// 1. Load Locations
if (!file_exists($locationsFile)) {
    die("Error: locations.json not found at $locationsFile\n");
}
$locations = json_decode(file_get_contents($locationsFile), true);
if (!$locations) {
    die("Error: Failed to parse locations.json\n");
}
echo "Loaded " . count($locations) . " locations.\n";

// 2. Load Existing Cache
$cache = [];
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);
    if (!is_array($cache)) $cache = [];
}
echo "Loaded " . count($cache) . " existing cache entries.\n";

// Helper: Clean Address (Must match get-locations.php)
function cleanAddress($address) {
    $pattern = '/(?:Suite|Ste|Unit|Apt|#)\s*[\w-]+,?/i';
    $clean = preg_replace($pattern, '', $address);
    return trim($clean);
}

// Helper: Geocode (Must match logic of get-locations.php but without artificial limits)
function getCoordinates($address) {
    $cleanAddr = cleanAddress($address);
    $encodedAddr = urlencode($cleanAddr);
    
    echo "  Geocoding: $cleanAddr ... ";
    
    // 1. Try Nominatim (OpenStreetMap)
    // Sleep to respect rate limits (1 second absolute requirement for Nominatim)
    sleep(1); 
    
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddr}&limit=1";
    $opts = [
        "http" => [
            "header" => "User-Agent: 2E2ERC-Deploy-Bot/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data)) {
            echo "Success (Nominatim)\n";
            return [
                'lat' => floatval($data[0]['lat']),
                'lng' => floatval($data[0]['lon'])
            ];
        }
    }

    // 2. Fallback to US Census API
    echo "Fallback to Census... ";
    $url = "https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?address={$encodedAddr}&benchmark=Public_AR_Current&format=json";
    
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['result']['addressMatches'])) {
            $match = $data['result']['addressMatches'][0];
            echo "Success (Census)\n";
            return [
                'lat' => $match['coordinates']['y'],
                'lng' => $match['coordinates']['x']
            ];
        }
    }

    echo "FAILED\n";
    return null;
}

// 3. Process Locations
$updated = false;
$processedCount = 0;

foreach ($locations as $loc) {
    $address = $loc['address'];
    
    // Check if valid coord exists in cache
    if (isset($cache[$address]) && 
        isset($cache[$address]['lat']) && 
        isset($cache[$address]['lng']) &&
        $cache[$address]['lat'] !== null) {
        continue; // Skip existing
    }

    // Needs Geocoding
    $coords = getCoordinates($address);
    
    $entry = [
        'lat' => null, 
        'lng' => null, 
        'timestamp' => time()
    ];

    if ($coords) {
        $entry['lat'] = $coords['lat'];
        $entry['lng'] = $coords['lng'];
    }
    
    $cache[$address] = $entry;
    $updated = true;
    $processedCount++;
    
    // Save periodically to avoid total data loss on crash
    if ($processedCount % 5 === 0) {
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    }
}

// 4. Final Save
if ($updated) {
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    echo "Cache updated. Saved to $cacheFile.\n";
} else {
    echo "Cache already up to date.\n";
}

echo "Warmup complete.\n";
?>
