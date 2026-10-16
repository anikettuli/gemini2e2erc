<?php
/**
 * Configuration API
 * Provides single source of truth for site settings to frontend.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

echo json_encode([
    'email' => $GLOBAL_EMAIL,
    'phone' => $GLOBAL_PHONE,
    'phoneLink' => $GLOBAL_PHONE_LINK,
    'address' => $GLOBAL_ADDRESS,
    'mailing' => $GLOBAL_MAILING
]);
?>
