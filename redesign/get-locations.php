<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

erc_send_security_headers(false);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

$locations = erc_load_json(ERC_DATA_DIR . '/locations.json', []);
$geoCache = erc_load_json(ERC_DATA_DIR . '/geo_cache.json', []);

if (!is_array($locations)) {
    $locations = [];
}
if (!is_array($geoCache)) {
    $geoCache = [];
}

$merged = [];
foreach ($locations as $loc) {
    if (!is_array($loc)) {
        continue;
    }

    $address = isset($loc['address']) && is_string($loc['address']) ? $loc['address'] : '';
    $hasInlineCoords = isset($loc['lat'], $loc['lng']) && is_numeric($loc['lat']) && is_numeric($loc['lng']);

    if ($hasInlineCoords) {
        $loc['lat'] = (float)$loc['lat'];
        $loc['lng'] = (float)$loc['lng'];
    } elseif ($address !== '' && isset($geoCache[$address]) && is_array($geoCache[$address])) {
        $cached = $geoCache[$address];
        if (isset($cached['lat'], $cached['lng']) && is_numeric($cached['lat']) && is_numeric($cached['lng'])) {
            $loc['lat'] = (float)$cached['lat'];
            $loc['lng'] = (float)$cached['lng'];
        }
    }

    $merged[] = $loc;
}

echo json_encode(array_values($merged), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
