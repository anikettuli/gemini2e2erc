<?php
/**
 * Volunteer Event Registration Endpoint
 */

date_default_timezone_set('America/Chicago');
require_once __DIR__ . '/config.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');
set_time_limit(60);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$rawBody = file_get_contents('php://input');
$input = is_string($rawBody) ? json_decode($rawBody, true) : null;
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
    exit;
}

// Honeypot check: silently absorb automated bot submissions
if (!empty(trim((string)($input['website_url'] ?? '')))) {
    echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    exit;
}

// Per-IP rate limit: max 10 volunteer signups per 15 minutes
if (!erc_check_rate_limit('volunteer_signup', 10, 900)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many registration attempts. Please wait a few minutes and try again.']);
    exit;
}

$eventId = filter_var($input['eventId'] ?? null, FILTER_VALIDATE_INT);
$name = strip_tags(trim((string)($input['name'] ?? '')));
$email_raw = trim((string)($input['email'] ?? ''));
$phone = strip_tags(trim((string)($input['phone'] ?? '')));

if (!$eventId || $name === '' || strlen($name) > 120 || strlen($email_raw) > 254 || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid name and email are required']);
    exit;
}

if ($phone !== '' && strlen($phone) > 40) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

$email = $email_raw;
$eventsFile = __DIR__ . '/data/events.json';
$signupsFile = __DIR__ . '/data/signups.json';

try {
    $result = erc_with_storage_lock(static function () use ($eventsFile, $signupsFile, $eventId, $name, $email, $phone) {
        if (!is_file($eventsFile)) {
            return ['success' => false, 'message' => 'Event schedule unavailable'];
        }

        $eventsRaw = @file_get_contents($eventsFile);
        $events = is_string($eventsRaw) ? json_decode($eventsRaw, true) : null;
        if (!is_array($events)) {
            return ['success' => false, 'message' => 'Internal database error'];
        }

        $signups = erc_load_json($signupsFile, []);
        $originalSignups = $signups;

        $eventIndex = -1;
        foreach ($events as $index => $event) {
            if (isset($event['id']) && (int)$event['id'] === $eventId) {
                $eventIndex = $index;
                break;
            }
        }

        if ($eventIndex === -1) {
            return ['success' => false, 'message' => 'Event not found'];
        }

        $signupKey = (string)$eventId;
        if (!isset($signups[$signupKey]) || !is_array($signups[$signupKey])) {
            $signups[$signupKey] = [];
        }

        $currentCount = max(count($signups[$signupKey]), (int)($events[$eventIndex]['people'] ?? 0));
        $maxPeople = (int)($events[$eventIndex]['maxPeople'] ?? 0);
        if ($maxPeople > 0 && $currentCount >= $maxPeople) {
            return ['success' => false, 'message' => 'Event is full'];
        }

        $normalizedEmail = strtolower($email);
        foreach ($signups[$signupKey] as $existing) {
            if (isset($existing['email']) && strtolower((string)$existing['email']) === $normalizedEmail) {
                return ['success' => false, 'message' => 'You are already registered for this event'];
            }
        }

        $signups[$signupKey][] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $newCount = count($signups[$signupKey]);
        $events[$eventIndex]['people'] = $newCount;

        if (!erc_save_json_atomic($signupsFile, $signups, 0600)) {
            return ['success' => false, 'message' => 'Unable to save registration. Please try again.'];
        }

        if (!erc_save_json_atomic($eventsFile, $events, 0644)) {
            erc_save_json_atomic($signupsFile, $originalSignups, 0600);
            return ['success' => false, 'message' => 'Unable to update event capacity. Please try again.'];
        }

        return [
            'success' => true,
            'event' => $events[$eventIndex],
            'newCount' => $newCount,
        ];
    });
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to process registration right now.']);
    exit;
}

if (empty($result['success'])) {
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Registration failed',
    ]);
    exit;
}

$event = $result['event'];

// Send confirmation and admin notification emails (non-blocking to saved registration)
try {
    require_once __DIR__ . '/mail-utils.php';
    $config = get_smtp_config();
    $mail = new SimpleSMTP($config['host'], $config['port'], $config['user'], $config['pass']);

    $eventTitle = (string)($event['title'] ?? 'Volunteer Event');
    $eventDate = (string)($event['date'] ?? '');
    $eventTime = (string)($event['time'] ?? '');
    $eventLoc = (string)($event['location'] ?? '');

    $volunteer_subject = 'Volunteer Confirmation: ' . SimpleSMTP::sanitizeHeader($eventTitle);
    $volunteer_content = "
        <p>Hi <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p>Thank you for signing up to volunteer for <strong>" . htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') . "</strong>.</p>
        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
        <p><strong>Date:</strong> " . htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Time:</strong> " . htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Location:</strong> " . htmlspecialchars($eventLoc, ENT_QUOTES, 'UTF-8') . "</p>
        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
        <p>We look forward to seeing you!</p>
    ";
    $volunteer_body = render_email_template('Volunteer Confirmation', $volunteer_content);

    $admin_subject = 'New Volunteer Signup: ' . SimpleSMTP::sanitizeHeader($name);
    $email_html = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $admin_content = "
        <p><strong>Event:</strong> " . htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Volunteer Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Volunteer Email:</strong> <a href='mailto:$email_html'>$email_html</a></p>
        <p><strong>Volunteer Phone:</strong> " . htmlspecialchars($phone !== '' ? $phone : 'Not provided', ENT_QUOTES, 'UTF-8') . "</p>
    ";
    $admin_body = render_email_template('New Volunteer Signup', $admin_content);

    $mail->send($config['user'], $email, $volunteer_subject, $volunteer_body, 'Lions 2-E2 ERC');
    $mail->send($config['user'], $config['admin_email'], $admin_subject, $admin_body, 'Lions 2-E2 ERC', $email);
} catch (Exception $e) {
    if (function_exists('erc_log_mail_error')) {
        erc_log_mail_error('Registration SMTP Error: ' . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Registration successful!',
    'newCount' => $result['newCount'],
]);
