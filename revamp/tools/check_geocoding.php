<?php
// tools/check_geocoding.php

$locationsFile = __DIR__ . '/../data/locations.json';
$cacheFile = __DIR__ . '/../data/geo_cache.json';

if (!file_exists($locationsFile)) {
    echo "Error: locations.json not found.\n";
    exit(1);
}

$locations = json_decode(file_get_contents($locationsFile), true);
$totalLocations = count($locations);
echo "Total locations found: $totalLocations\n";

$cache = [];
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);
    if (!is_array($cache)) $cache = [];
}

$cachedCount = 0;
$validCoordsCount = 0;

foreach ($locations as $loc) {
    $address = $loc['address'];
    if (isset($cache[$address])) {
        $cachedCount++;
        if ($cache[$address]['lat'] !== null && $cache[$address]['lng'] !== null) {
            $validCoordsCount++;
        }
    }
}

echo "Cached addresses: $cachedCount\n";
echo "Valid coordinates in cache: $validCoordsCount\n";

// Helper function to clean address
function cleanAddress($address) {
    $pattern = '/(?:Suite|Ste|Unit|Apt|#)\s*[\w-]+,?/i';
    $clean = preg_replace($pattern, '', $address);
    return trim($clean);
}

// Helper function to geocode
function getCoordinates($address) {
    $cleanAddr = cleanAddress($address);
    $encodedAddr = urlencode($cleanAddr);
    
    echo "Geocoding: $cleanAddr ... ";
    
    // 1. Try Nominatim (OpenStreetMap)
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddr}&limit=1";
    $opts = [
        "http" => [
            "header" => "User-Agent: 2E2ERC-Website-Tool/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    
    // Sleep to respect rate limits (1 second)
    sleep(1);
    
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
    echo "Nominatim failed, trying Census... ";
    $url = "https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?address={$encodedAddr}&benchmark=Public_AR_Current&format=json";
    
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
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

    echo "Failed\n";
    return null;
}

// Try to geocode all missing ones
$attemptCount = 0;
$maxAttempts = 300; // Process all
$cacheUpdated = false;

foreach ($locations as $i => $loc) {
    if ($attemptCount >= $maxAttempts) break;
    
    $address = $loc['address'];
    
    // Force rebuild - do not skip existing
    // if (isset($cache[$address]) && $cache[$address]['lat'] !== null) {
    //    continue;
    // }
    
    echo "[" . ($i + 1) . "/$totalLocations] ";
    
    // Try to geocode
    $coords = getCoordinates($address);
    $attemptCount++;
    
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
    $cacheUpdated = true;
    
    // Save periodically
    if ($attemptCount % 10 == 0) {
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
        echo "Cache saved.\n";
    }
}

if ($cacheUpdated) {
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    echo "Cache updated.\n";
}
