<?php
/**
 * Locations API
 * Returns all partner drop-off locations with pre-computed coordinates.
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$locationsFile = __DIR__ . '/data/locations.json';
if (file_exists($locationsFile)) {
    readfile($locationsFile);
} else {
    echo json_encode([]);
}
?>
