<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail-utils.php';

@set_time_limit(60);
erc_send_security_headers(false);

$acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

$wantsJson = str_contains($acceptHeader, 'application/json')
    || $requestedWith === 'xmlhttprequest'
    || str_contains($contentType, 'application/json');

$sendResponse = static function (bool $wantsJson, bool $ok, int $httpCode, string $reason, string $message): never {
    if ($wantsJson) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'success' => $ok,
            'status'  => $ok ? 'success' : 'error',
            'reason'  => $reason,
            'message' => $message,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $location = $ok
        ? '/contact?status=success'
        : '/contact?status=error&reason=' . rawurlencode($reason);
    header('Location: ' . $location, true, 303);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if ($wantsJson) {
        $sendResponse(true, false, 405, 'method_not_allowed', 'Method not allowed.');
    }
    header('Location: /contact', true, 303);
    exit;
}

$input = $_POST;
if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($decoded)) {
        $sendResponse(true, false, 400, 'invalid_input', 'Invalid JSON payload.');
    }
    $input = $decoded;
}

// Honeypot: silently succeed without incrementing rate limit or sending email
if (trim((string)($input['website_url'] ?? '')) !== '') {
    $sendResponse($wantsJson, true, 200, 'sent', 'Your message has been sent!');
}

// Rate limit: 5 requests per 900 seconds per IP
$rl = erc_check_rate_limit('contact_form', 5, 900, true);
if (!$rl['allowed']) {
    header('Retry-After: ' . (int)$rl['retry_after']);
    $sendResponse($wantsJson, false, 429, 'rate_limited', 'Too many inquiries sent recently. Please try again later.');
}

$name = trim(strip_tags(str_replace("\0", '', (string)($input['name'] ?? ''))));
$email = trim(str_replace("\0", '', (string)($input['email'] ?? '')));
$phone = trim(strip_tags(str_replace("\0", '', (string)($input['phone'] ?? ''))));
$subject = trim(strip_tags(str_replace("\0", '', (string)($input['subject'] ?? ''))));
$message = trim(str_replace("\0", '', (string)($input['message'] ?? '')));

if ($subject === '') {
    $subject = 'General Inquiry';
}

if (
    $name === ''
    || mb_strlen($name, 'UTF-8') > 120
    || $email === ''
    || strlen($email) > 254
    || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    || mb_strlen($phone, 'UTF-8') > 40
    || mb_strlen($subject, 'UTF-8') > 100
    || $message === ''
    || mb_strlen($message, 'UTF-8') > 5000
) {
    $sendResponse($wantsJson, false, 400, 'invalid_input', 'Please check all required fields and character limits.');
}

$sent = erc_send_contact_email([
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone,
    'subject' => $subject,
    'message' => $message,
]);

if (!$sent) {
    $sendResponse(
        $wantsJson,
        false,
        502,
        'send_failed',
        'We could not deliver your message right now. Please call us or email us directly.'
    );
}

$sendResponse(
    $wantsJson,
    true,
    200,
    'sent',
    'Thank you! Your message has been sent. We typically respond within 24 hours.'
);
