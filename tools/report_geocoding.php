<?php
// tools/report_geocoding.php

$locationsFile = __DIR__ . '/../data/locations.json';
$cacheFile = __DIR__ . '/../data/geo_cache.json';

if (!file_exists($locationsFile)) {
    echo "Error: locations.json not found.\n";
    exit(1);
}

$locations = json_decode(file_get_contents($locationsFile), true);
$totalLocations = count($locations);

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

echo "Total locations: $totalLocations\n";
echo "Cached addresses: $cachedCount\n";
echo "Valid coordinates: $validCoordsCount\n";
echo "Success rate: " . round(($validCoordsCount / $totalLocations) * 100, 1) . "%\n";
