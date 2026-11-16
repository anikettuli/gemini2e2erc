<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail-utils.php';

@set_time_limit(60);

erc_send_security_headers(false);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$respond = static function (int $statusCode, array $payload): never {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$rawBody = file_get_contents('php://input');
$data = is_string($rawBody) ? json_decode($rawBody, true) : null;
if (!is_array($data)) {
    $respond(400, ['success' => false, 'message' => 'Invalid JSON payload.']);
}

// Honeypot check: silently succeed without mutating storage or sending mail
if (trim((string)($data['website_url'] ?? '')) !== '') {
    $respond(200, ['success' => true, 'message' => 'Registration successful!']);
}

// Rate limit: 10 requests per 900 seconds per IP
$rl = erc_check_rate_limit('volunteer_signup', 10, 900, true);
if (!$rl['allowed']) {
    header('Retry-After: ' . (int)$rl['retry_after']);
    $respond(429, [
        'success' => false,
        'message' => 'Too many registration attempts. Please wait a few minutes and try again.',
    ]);
}

$eventId = filter_var($data['eventId'] ?? null, FILTER_VALIDATE_INT);
$name = trim(strip_tags(str_replace("\0", '', (string)($data['name'] ?? ''))));
$email = trim(str_replace("\0", '', (string)($data['email'] ?? '')));
$phone = trim(strip_tags(str_replace("\0", '', (string)($data['phone'] ?? ''))));

if (
    $eventId === false
    || $eventId <= 0
    || $name === ''
    || mb_strlen($name, 'UTF-8') > 120
    || $email === ''
    || strlen($email) > 254
    || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    || mb_strlen($phone, 'UTF-8') > 40
) {
    $respond(400, [
        'success' => false,
        'message' => 'Please enter a valid event, full name, and email address.',
    ]);
}

$eventsPath = ERC_DATA_DIR . '/events.json';
$signupsPath = ERC_DATA_DIR . '/signups.json';

$txResult = erc_with_storage_lock(static function () use ($eventsPath, $signupsPath, $eventId, $name, $email, $phone): array {
    $events = erc_load_json($eventsPath, []);
    $signups = erc_load_json($signupsPath, []);
    if (!is_array($events)) {
        $events = [];
    }
    if (!is_array($signups)) {
        $signups = [];
    }

    $eventIdx = null;
    foreach ($events as $idx => $ev) {
        if (is_array($ev) && (int)($ev['id'] ?? 0) === $eventId) {
            $eventIdx = $idx;
            break;
        }
    }

    if ($eventIdx === null) {
        return [
            'status'  => 404,
            'payload' => ['success' => false, 'message' => 'Selected volunteer session was not found.'],
        ];
    }

    $key = (string)$eventId;
    $eventSignups = isset($signups[$key]) && is_array($signups[$key]) ? $signups[$key] : [];
    $priorOnlineCount = count($eventSignups);
    $baseSeededPeople = max(0, (int)($events[$eventIdx]['people'] ?? 0) - $priorOnlineCount);

    // Case-insensitive duplicate email prevention
    $normalizedEmail = strtolower($email);
    foreach ($eventSignups as $existing) {
        if (is_array($existing) && strtolower(trim((string)($existing['email'] ?? ''))) === $normalizedEmail) {
            return [
                'status'  => 409,
                'payload' => ['success' => false, 'message' => 'You are already registered for this event.'],
            ];
        }
    }

    $maxPeople = (int)($events[$eventIdx]['maxPeople'] ?? 0);
    $currentCount = max($priorOnlineCount, (int)($events[$eventIdx]['people'] ?? 0));
    if ($maxPeople > 0 && $currentCount >= $maxPeople) {
        return [
            'status'  => 409,
            'payload' => ['success' => false, 'message' => 'Sorry, this event has reached maximum capacity.'],
        ];
    }

    $newSignup = [
        'name'      => $name,
        'email'     => $email,
        'phone'     => $phone,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    $previousSignups = $signups;
    $eventSignups[] = $newSignup;
    $signups[$key] = $eventSignups;
    $newCount = $baseSeededPeople + count($eventSignups);
    $events[$eventIdx]['people'] = $newCount;

    if (!erc_save_json_atomic($signupsPath, $signups, 0600)) {
        return [
            'status'  => 500,
            'payload' => ['success' => false, 'message' => 'Unable to save registration at this time.'],
        ];
    }

    if (!erc_save_json_atomic($eventsPath, $events, 0644)) {
        // Roll back signups.json so counts and roster stay consistent
        erc_save_json_atomic($signupsPath, $previousSignups, 0600);
        return [
            'status'  => 500,
            'payload' => ['success' => false, 'message' => 'Unable to update session availability. Please try again.'],
        ];
    }

    return [
        'status'   => 200,
        'event'    => $events[$eventIdx],
        'signup'   => $newSignup,
        'newCount' => $newCount,
    ];
});

if (($txResult['status'] ?? 500) !== 200) {
    $respond((int)$txResult['status'], $txResult['payload']);
}

// Send confirmation & admin notification emails outside the lock (non-blocking to saved registration)
erc_send_volunteer_emails($txResult['event'], $txResult['signup']);

$respond(200, [
    'success'  => true,
    'message'  => 'Registration successful!',
    'newCount' => (int)$txResult['newCount'],
]);
