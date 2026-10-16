<?php
/**
 * Contact Form Processor
 * Lions District 2-E2 ERC
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail-utils.php';

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html#contact");
    exit;
}

// Check if request expects JSON
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Retrieve inputs
$name = strip_tags(trim($_POST["name"] ?? ''));
$email_raw = trim($_POST["email"] ?? '');
$phone = strip_tags(trim($_POST["phone"] ?? ''));
$subject_raw = strip_tags(trim($_POST["subject"] ?? 'General Inquiry'));
$message = trim($_POST["message"] ?? '');

// Validation
if (empty($name) || empty($email_raw) || empty($message) || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide a valid name, email address, and message.']);
        exit;
    }
    header("Location: index.html?status=error&reason=invalid_input#contact");
    exit;
}

$email = strtolower($email_raw);

// Clean CRLF to prevent SMTP header injection
$clean_name = preg_replace('/[\r\n]/', ' ', $name);
$clean_subject = preg_replace('/[\r\n]/', ' ', $subject_raw);
$email_subject = "ERC Inquiry - $clean_subject: $clean_name";

$content_html = "
    <p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
    <p><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</a></p>
    <p><strong>Phone:</strong> " . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</p>
    <p><strong>Topic / Subject:</strong> " . htmlspecialchars($subject_raw, ENT_QUOTES, 'UTF-8') . "</p>
    <hr>
    <h3>Message:</h3>
    <p style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>
";

$html_body = render_email_template("New Website Contact Inquiry", $content_html);

try {
    $smtp_conf = get_smtp_config();
    $mail = new SimpleSMTP($smtp_conf['host'], $smtp_conf['port'], $smtp_conf['user'], $smtp_conf['pass']);

    // Send to Admin with CC to Sender
    $mail->send(
        $smtp_conf['user'],
        $smtp_conf['admin_email'],
        $email_subject,
        $html_body,
        "Lions 2-E2 ERC Contact Form",
        $email,
        $email
    );

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);
        exit;
    }
    header("Location: index.html?status=success#contact");
    exit;

} catch (Exception $e) {
    error_log("[ERC Contact Form] SMTP Exception: " . $e->getMessage());

    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to send message right now. Please call us or email directly.']);
        exit;
    }
    header("Location: index.html?status=error&reason=send_failed#contact");
    exit;
}
?>