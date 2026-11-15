<?php
/**
 * Configuration API
 * Provides a single source of truth for public site contact info
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');
require_once __DIR__ . '/config.php';

$email = $GLOBAL_EMAIL ?? '2e2erc1854@gmail.com';
$phone = $GLOBAL_PHONE ?? '(817) 710-5403';
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) === 11 && $digits[0] === '1') {
    $phoneLink = '+' . $digits;
} elseif ($digits !== '') {
    $phoneLink = '+1' . $digits;
} else {
    $phoneLink = '+18177105403';
}

echo json_encode([
    'email' => $email,
    'phone' => $phone,
    'phoneLink' => $phoneLink,
]);
