<?php
// get-locations.php
// Serves drop-off locations with pre-baked coordinates (eliminates runtime geocoding merge)
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$locationsFile = __DIR__ . '/data/locations.json';
if (file_exists($locationsFile)) {
    readfile($locationsFile);
} else {
    echo '[]';
}
?>
