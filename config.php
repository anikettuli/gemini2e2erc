<?php
// Global configuration for email and phone
$GLOBAL_EMAIL = "2e2erc1854@gmail.com";
$GLOBAL_PHONE = "(817) 710-5403";

/**
 * Parse a .env file and return key-value pairs.
 * Used by get_smtp_config() and get_admin_credentials().
 */
function parse_env_file($path) {
    $env_config = [];
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return $env_config;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env_config[trim($key)] = trim($value);
    }
    return $env_config;
}
?>
