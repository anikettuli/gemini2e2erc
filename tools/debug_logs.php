<?php
/**
 * Debug Log Downloader
 * 
 * Usage: https://yourdomain.com/tools/debug_logs.php?key=Lions2025Debug
 * Security: Requires 'key' parameter to function.
 */

$SECRET_KEY = 'Lions2025Debug'; // Simple protection

if (($_GET['key'] ?? '') !== $SECRET_KEY) {
    header("HTTP/1.1 403 Forbidden");
    die("Access Denied");
}

$files_to_check = [
    __DIR__ . '/../email_errors.log',
    __DIR__ . '/../error_log',
    __DIR__ . '/error_log',
    __DIR__ . '/../php_errors.log'
];

$found_files = [];
$content = "DEBUG LOG EXPORT - " . date('Y-m-d H:i:s') . "\n";
$content .= "==========================================\n\n";

// PHP Info Snippet
$content .= "SERVER INFO:\n";
$content .= "PHP Version: " . phpversion() . "\n";
$content .= "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n\n";

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $found_files[] = basename($file);
        $content .= "FILE: " . basename($file) . " (Path: $file)\n";
        $content .= "SIZE: " . filesize($file) . " bytes\n";
        $content .= "------------------------------------------\n";
        
        // Read last 20KB of the file
        $file_content = file_get_contents($file);
        if (strlen($file_content) > 20000) {
            $content .= "...[truncated]...\n" . substr($file_content, -20000);
        } else {
            $content .= $file_content;
        }
        $content .= "\n\n==========================================\n\n";
    }
}

if (empty($found_files)) {
    $content .= "No error log files found in likely locations.\n";
    $content .= "Searched:\n" . implode("\n", $files_to_check);
}

// Force Download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="debug_logs_' . date('Y-m-d_His') . '.txt"');
header('Content-Length: ' . strlen($content));
echo $content;
exit;
?>
