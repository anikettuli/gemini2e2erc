<?php
/**
 * Lions District 2-E2 ERC - Central Configuration
 * Safe single-source-of-truth for both frontend APIs and backend handlers.
 */

// Establish Central Timezone (Keller/Watauga, TX)
date_default_timezone_set('America/Chicago');

// Master Site Contact Information
$GLOBAL_EMAIL = "2e2erc1854@gmail.com";
$GLOBAL_PHONE = "(817) 710-5403";
$GLOBAL_PHONE_LINK = "+18177105403";
$GLOBAL_ADDRESS = "5621 Bunker Blvd, Watauga, TX 76148";
$GLOBAL_MAILING = "P.O. Box 1854, Keller, TX 76244";

/**
 * Helper to safely load environment variables from .env if present.
 */
function get_env_variable($key, $default = null) {
    static $env = null;
    if ($env === null) {
        $env = [];
        $env_file = __DIR__ . '/.env';
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
                list($k, $v) = explode('=', $line, 2);
                $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }
        }
    }
    return $env[$key] ?? $default;
}

/**
 * Get Admin Credentials safely
 */
function get_admin_credentials() {
    return [
        'user' => get_env_variable('ADMIN_USER', 'admin'),
        'pass' => get_env_variable('ADMIN_PASS', 'Lions@2025')
    ];
}
?>
