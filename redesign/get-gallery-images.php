<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

erc_send_security_headers(false);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

echo json_encode(erc_scan_gallery_images(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
