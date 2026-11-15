<?php
/**
 * Returns the list of displayable gallery images in gallery/.
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');
require_once __DIR__ . '/config.php';

echo json_encode(erc_scan_gallery_images());
