<?php
/**
 * Configuration API
 * Provides a single source of truth for site configuration
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

echo json_encode([
    'email' => $GLOBAL_EMAIL ?? '2e2erc1854@gmail.com',
    'phone' => $GLOBAL_PHONE ?? '(817) 710-5403',
    'phoneLink' => '+1' . preg_replace('/\D/', '', $GLOBAL_PHONE ?? '8177105403')
]);
?>
