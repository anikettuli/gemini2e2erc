<?php
/**
 * Serves drop-off locations merged with cached coordinates from data/geo_cache.json.
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');
require_once __DIR__ . '/config.php';

$locations = erc_load_json(__DIR__ . '/data/locations.json', []);
if (empty($locations)) {
    echo json_encode([]);
    exit;
}

$cache = erc_load_json(__DIR__ . '/data/geo_cache.json', []);

foreach ($locations as &$loc) {
    if (isset($loc['lat'], $loc['lng']) && is_numeric($loc['lat']) && is_numeric($loc['lng'])) {
        $loc['lat'] = (float)$loc['lat'];
        $loc['lng'] = (float)$loc['lng'];
        continue;
    }

    $address = $loc['address'] ?? '';
    if ($address !== '' && isset($cache[$address]['lat'], $cache[$address]['lng']) &&
        is_numeric($cache[$address]['lat']) && is_numeric($cache[$address]['lng'])) {
        $loc['lat'] = (float)$cache[$address]['lat'];
        $loc['lng'] = (float)$cache[$address]['lng'];
    }
}
unset($loc);

echo json_encode(array_values($locations));
