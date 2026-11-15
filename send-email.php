<?php
/**
 * Contact Form Email Handler (Standalone Authenticated SMTP)
 */

date_default_timezone_set('America/Chicago');
require_once __DIR__ . '/mail-utils.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (is_dir(__DIR__ . '/data') || @mkdir(__DIR__ . '/data', 0755, true)) {
    ini_set('error_log', __DIR__ . '/data/email_errors.log');
}
error_reporting(E_ALL);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: index.html');
    exit;
}

// Honeypot check: bots filling the hidden website_url field are silently dropped
if (!empty(trim((string)($_POST['website_url'] ?? '')))) {
    header('Location: index.html?status=success#contact');
    exit;
}

// Per-IP rate limit: max 5 contact submissions per 15 minutes
if (!erc_check_rate_limit('contact_form', 5, 900)) {
    header('Location: index.html?status=error&reason=rate_limited#contact');
    exit;
}

$name = strip_tags(trim((string)($_POST['name'] ?? '')));
$email_raw = trim((string)($_POST['email'] ?? ''));
$phone = strip_tags(trim((string)($_POST['phone'] ?? '')));
$subject_raw = strip_tags(trim((string)($_POST['subject'] ?? 'General Inquiry')));
$message = trim((string)($_POST['message'] ?? ''));

if (
    $name === '' || strlen($name) > 120 ||
    $email_raw === '' || strlen($email_raw) > 254 || !filter_var($email_raw, FILTER_VALIDATE_EMAIL) ||
    strlen($phone) > 40 ||
    strlen($subject_raw) > 100 ||
    $message === '' || strlen($message) > 5000
) {
    header('Location: index.html?status=error&reason=invalid_input#contact');
    exit;
}

$email = $email_raw;
$safe_name = SimpleSMTP::sanitizeHeader($name);
$safe_subject = SimpleSMTP::sanitizeHeader($subject_raw !== '' ? $subject_raw : 'General Inquiry');
$email_subject = "$safe_subject: $safe_name";

$escaped_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$escaped_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$escaped_phone = htmlspecialchars($phone !== '' ? $phone : 'Not provided', ENT_QUOTES, 'UTF-8');
$escaped_subject = htmlspecialchars($safe_subject, ENT_QUOTES, 'UTF-8');
$escaped_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

$content_html = "
    <p><strong>Name:</strong> $escaped_name</p>
    <p><strong>Email:</strong> <a href='mailto:$escaped_email'>$escaped_email</a></p>
    <p><strong>Phone:</strong> $escaped_phone</p>
    <p><strong>Subject:</strong> $escaped_subject</p>
    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
    <h3>Message:</h3>
    <p style='white-space: pre-wrap;'>$escaped_message</p>
";

$html_body = render_email_template('New Website Inquiry', $content_html);
$config = get_smtp_config();

try {
    $mail = new SimpleSMTP($config['host'], $config['port'], $config['user'], $config['pass']);

    // Deliver exclusively to the organization admin inbox (Reply-To set to sender; no open CC relay)
    $mail->send(
        $config['user'],
        $config['admin_email'],
        $email_subject,
        $html_body,
        'Lions 2-E2 ERC',
        $email
    );

    header('Location: index.html?status=success#contact');
    exit;
} catch (Exception $e) {
    $error = $e->getMessage();
    erc_log_mail_error('Contact SMTP Error: ' . $error);

    $reason = 'send_failed';
    if (stripos($error, 'auth') !== false) {
        $reason = 'auth_failed';
    } elseif (stripos($error, 'connect') !== false) {
        $reason = 'connect_failed';
    }

    header('Location: index.html?status=error&reason=' . urlencode($reason) . '#contact');
    exit;
}
