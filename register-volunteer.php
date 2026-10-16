<?php
/**
 * Volunteer Registration Processor
 * Lions District 2-E2 Eyeglass Recycling Center
 */

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
set_time_limit(30);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 1. Parse Input (Supports both JSON payload and standard form encoded)
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!is_array($input)) {
    $input = $_POST;
}

$eventId = isset($input['eventId']) ? (int)$input['eventId'] : 0;
$name = strip_tags(trim($input['name'] ?? ''));
$email_raw = trim($input['email'] ?? '');
$phone = strip_tags(trim($input['phone'] ?? ''));

// Validate Name & Email
if (!$eventId || empty($name) || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid name and email address are required.']);
    exit;
}

if (!empty($phone) && strlen($phone) > 25) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
    exit;
}

$email = strtolower($email_raw);

// 2. Concurrency-Safe Atomic File Locking
$eventsFile = __DIR__ . '/data/events.json';
$signupsFile = __DIR__ . '/data/signups.json';

$fpSignups = @fopen($signupsFile, 'c+');
$fpEvents = @fopen($eventsFile, 'c+');

if (!$fpSignups || !$fpEvents) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server database access error.']);
    exit;
}

// Acquire exclusive locks
flock($fpSignups, LOCK_EX);
flock($fpEvents, LOCK_EX);

// Read current data
$signupsRaw = stream_get_contents($fpSignups);
$eventsRaw = stream_get_contents($fpEvents);

$signups = json_decode($signupsRaw, true);
if (!is_array($signups)) {
    $signups = [];
}

$events = json_decode($eventsRaw, true);
if (!is_array($events)) {
    flock($fpEvents, LOCK_UN);
    flock($fpSignups, LOCK_UN);
    fclose($fpEvents);
    fclose($fpSignups);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Events database could not be loaded.']);
    exit;
}

// 3. Locate Event
$eventIndex = -1;
foreach ($events as $idx => $ev) {
    if ((int)$ev['id'] === $eventId) {
        $eventIndex = $idx;
        break;
    }
}

if ($eventIndex === -1) {
    flock($fpEvents, LOCK_UN);
    flock($fpSignups, LOCK_UN);
    fclose($fpEvents);
    fclose($fpSignups);
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'The selected event was not found.']);
    exit;
}

$targetEvent = $events[$eventIndex];

// 4. Reject Signups for Past Events
$eventDateTimestamp = strtotime($targetEvent['date'] . ' 23:59:59');
if ($eventDateTimestamp < time()) {
    flock($fpEvents, LOCK_UN);
    flock($fpSignups, LOCK_UN);
    fclose($fpEvents);
    fclose($fpSignups);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Registration is closed for past events.']);
    exit;
}

// 5. Check Capacity
if ($targetEvent['people'] >= $targetEvent['maxPeople']) {
    flock($fpEvents, LOCK_UN);
    flock($fpSignups, LOCK_UN);
    fclose($fpEvents);
    fclose($fpSignups);
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This event has reached full volunteer capacity.']);
    exit;
}

// 6. Case-Insensitive Duplicate Check
$eventSignups = $signups[(string)$eventId] ?? [];
foreach ($eventSignups as $existing) {
    if (strcasecmp($existing['email'], $email) === 0) {
        flock($fpEvents, LOCK_UN);
        flock($fpSignups, LOCK_UN);
        fclose($fpEvents);
        fclose($fpSignups);
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You are already registered for this event.']);
        exit;
    }
}

// 7. Append New Volunteer Registration
$newRegistration = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'timestamp' => date('Y-m-d H:i:s')
];

$signups[(string)$eventId][] = $newRegistration;
$events[$eventIndex]['people'] = count($signups[(string)$eventId]);

// 8. In-Place Atomic Write while holding locks (preserves inode & descriptor lock)
ftruncate($fpSignups, 0);
rewind($fpSignups);
fwrite($fpSignups, json_encode($signups, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
fflush($fpSignups);

ftruncate($fpEvents, 0);
rewind($fpEvents);
fwrite($fpEvents, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
fflush($fpEvents);

// Release Locks safely
flock($fpEvents, LOCK_UN);
flock($fpSignups, LOCK_UN);
fclose($fpEvents);
fclose($fpSignups);

// 9. Send Email Confirmations (Non-blocking)
try {
    require_once __DIR__ . '/mail-utils.php';
    $smtp_conf = get_smtp_config();
    if (!empty($smtp_conf['host']) && !empty($smtp_conf['user'])) {
        $mail = new SimpleSMTP($smtp_conf['host'], $smtp_conf['port'], $smtp_conf['user'], $smtp_conf['pass']);

        $eventTitle = $targetEvent['title'];
        $eventDate = $targetEvent['date'];
        $eventTime = $targetEvent['time'];
        $eventLoc = $targetEvent['location'];

        // Volunteer confirmation message
        $vContent = "
            <p>Hi <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Thank you for signing up to volunteer with the Lions District 2-E2 Eyeglass Recycling Center!</p>
            <hr>
            <p><strong>Event:</strong> " . htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Date:</strong> " . htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Time:</strong> " . htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Location:</strong> " . htmlspecialchars($eventLoc, ENT_QUOTES, 'UTF-8') . "</p>
            <hr>
            <p>We are grateful for your time and service. See you there!</p>
        ";
        $vBody = render_email_template("Volunteer Confirmation", $vContent);

        // Admin notification message
        $aContent = "
            <p><strong>Event:</strong> " . htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Volunteer Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</a></p>
            <p><strong>Phone:</strong> " . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Current Capacity:</strong> " . $events[$eventIndex]['people'] . " / " . $targetEvent['maxPeople'] . "</p>
        ";
        $aBody = render_email_template("New Volunteer Registered", $aContent);

        $mail->send($smtp_conf['user'], $email, "Volunteer Confirmation: $eventTitle", $vBody, "Lions 2-E2 ERC");
        $mail->send($smtp_conf['user'], $smtp_conf['admin_email'], "Volunteer Signup: $name ($eventTitle)", $aBody, "Lions 2-E2 ERC");
    }
} catch (Exception $e) {
    error_log("Volunteer Email Dispatch Error: " . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'message' => 'Registration confirmed! Thank you for volunteering.',
    'newCount' => $events[$eventIndex]['people'],
    'maxPeople' => $targetEvent['maxPeople']
]);
?>