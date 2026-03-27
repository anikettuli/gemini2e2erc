<?php
/**
 * Contact Form Email Handler (Standalone Authenticated SMTP)
 * 
 * Implements a lightweight SMTP client to send authenticated emails
 * without external dependencies (like PHPMailer/Composer).
 * This solves issues where mail() is blocked or works silently.
 */

// Set timezone to Central Time (Keller, TX)
date_default_timezone_set('America/Chicago');

// Load Configuration and Mail Utilities
require_once 'mail-utils.php';

$config = get_smtp_config();
$SMTP_HOST = $config['host'];
$SMTP_PORT = $config['port'];
$SMTP_USER = $config['user'];
$SMTP_PASS = $config['pass'];
$TO_EMAIL  = $config['admin_email'];

// Logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$log_file = __DIR__ . '/email_errors.log';
ini_set('error_log', $log_file);
// Create log file if verify writable
if (!file_exists($log_file)) { @touch($log_file); @chmod($log_file, 0666); }
error_reporting(E_ALL);

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

// Sanitize Inputs
$name = strip_tags(trim($_POST["name"] ?? ''));
$email_raw = trim($_POST["email"] ?? '');
$phone = strip_tags(trim($_POST["phone"] ?? ''));
$subject_raw = strip_tags(trim($_POST["subject"] ?? 'General Inquiry'));
$message = trim($_POST["message"] ?? '');
$subscribe = isset($_POST["subscribe"]) ? 'Yes' : 'No';

// Use proper validation - Reject rather than sanitize
if (empty($name) || empty($email_raw) || empty($message) || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.html?status=error&reason=invalid_input#contact");
    exit;
}
$email = $email_raw; 

// Prepare Content
$email_subject = "$subject_raw: $name";

$content_html = "
    <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
    <p><strong>Email:</strong> <a href='mailto:$email'>$email</a></p>
    <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
    <p><strong>Subject:</strong> " . htmlspecialchars($subject_raw) . "</p>
    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
    <h3>Message:</h3>
    <p style='white-space: pre-wrap;'>" . htmlspecialchars($message) . "</p>
";

$html_body = render_email_template("New Website Inquiry", $content_html);

// Send using our custom Lightweight SMTP class
$error = null;
try {
    $mail = new SimpleSMTP($SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS);
    
    // Send to Admin with CC to User
    $mail->send(
        $SMTP_USER,         // From
        $TO_EMAIL,          // To (Admin)
        $email_subject,     // Subject
        $html_body,         // Body
        "Lions 2-E2 ERC",   // From Name
        $email,             // Reply-To (User's email)
        $email              // CC (User's email)
    );
    
    header("Location: index.html?status=success#contact");
    exit;

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("SMTP Error: " . $error);
    
    // Determine reason for URL
    $reason = 'send_failed';
    if (strpos($error, 'Authentic') !== false) $reason = 'auth_failed';
    if (strpos($error, 'connect') !== false) $reason = 'connect_failed';
    
    // Fix: urlencode the reason to prevent header injection
    header("Location: index.html?status=error&reason=" . urlencode($reason) . "#contact");
    exit;
}
?>