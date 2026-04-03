<?php
require_once 'config.php';
header('Content-Type: application/json');
set_time_limit(60); // Prevent 500 on slow SMTP connections

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get Input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $eventId = $input['eventId'] ?? null;
    $name = strip_tags(trim($input['name'] ?? ''));
    $email_raw = trim($input['email'] ?? '');
    $phone = strip_tags(trim($input['phone'] ?? ''));

    // Improved validation
    if (!$eventId || !$name || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Valid name and email are required']);
        exit;
    }
    
    if (!empty($phone) && strlen($phone) > 20) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
        exit;
    }
    
    $email = $email_raw;

    // 2. Load and Update Data ATOMICALLY
    $eventsFile = 'data/events.json';
    $signupsFile = 'data/signups.json';

    // We open signups file first and lock it for the entire duration of the update
    $fpSignups = fopen($signupsFile, 'c+');
    $fpEvents = fopen($eventsFile, 'c+');
    
    if (!$fpSignups || !$fpEvents) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    // Acquire exclusive locks on both files
    flock($fpSignups, LOCK_EX);
    flock($fpEvents, LOCK_EX);

    // Read Data
    $signupsContent = stream_get_contents($fpSignups);
    $eventsContent = stream_get_contents($fpEvents);
    
    $signups = json_decode($signupsContent, true);
    if ($signups === null && json_last_error() !== JSON_ERROR_NONE) {
        $signups = []; // Or handle error properly
    }
    if (!is_array($signups)) $signups = [];

    $events = json_decode($eventsContent, true);
    if ($events === null && json_last_error() !== JSON_ERROR_NONE) {
        flock($fpEvents, LOCK_UN);
        flock($fpSignups, LOCK_UN);
        echo json_encode(['success' => false, 'message' => 'Internal Database Error']);
        exit;
    }
    if (!is_array($events)) $events = [];

    // 3. Find Event
    $eventIndex = -1;
    foreach ($events as $index => $event) {
        if ($event['id'] == $eventId) {
            $eventIndex = $index;
            break;
        }
    }

    if ($eventIndex === -1) {
        flock($fpEvents, LOCK_UN);
        flock($fpSignups, LOCK_UN);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }

    // 4. Check Capacity
    if ($events[$eventIndex]['people'] >= $events[$eventIndex]['maxPeople']) {
        flock($fpEvents, LOCK_UN);
        flock($fpSignups, LOCK_UN);
        echo json_encode(['success' => false, 'message' => 'Event is full']);
        exit;
    }

    // 5. Add Signup
    if (!isset($signups[$eventId])) {
        $signups[$eventId] = [];
    }

    // Check for duplicate email for this event
    foreach ($signups[$eventId] as $signup) {
        if ($signup['email'] === $email) {
            flock($fpEvents, LOCK_UN);
            flock($fpSignups, LOCK_UN);
            echo json_encode(['success' => false, 'message' => 'You are already registered for this event']);
            exit;
        }
    }

    $newSignup = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $signups[$eventId][] = $newSignup;

    // 6. Update Event Count
    $events[$eventIndex]['people'] = count($signups[$eventId]);

    // 7. Save Files ATOMICALLY via temp files while holding locks
    function saveAtomic($file, $data, $fp) {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $tmpFile = $file . '.tmp.' . getmypid() . '_' . rand(1000, 9999);
        if (file_put_contents($tmpFile, $json) !== false) {
            if (rename($tmpFile, $file)) {
                return true;
            }
            @unlink($tmpFile);
        }
        return false;
    }

    saveAtomic($signupsFile, $signups, $fpSignups);
    saveAtomic($eventsFile, $events, $fpEvents);

    // Release locks
    flock($fpEvents, LOCK_UN);
    flock($fpSignups, LOCK_UN);
    fclose($fpEvents);
    fclose($fpSignups);

    // 8. Send Emails via SMTP (non-blocking — failure is logged but does not break registration)
    try {
        require_once 'mail-utils.php';
        $config = get_smtp_config();
        $mail = new SimpleSMTP($config['host'], $config['port'], $config['user'], $config['pass']);

        $eventTitle = $events[$eventIndex]['title'];
        $eventDate = $events[$eventIndex]['date'];
        $eventTime = $events[$eventIndex]['time'];
        $eventLoc = $events[$eventIndex]['location'];

        // Volunteer Confirmation Email
        $volunteer_subject = "Volunteer Confirmation: $eventTitle";
        $volunteer_content = "
            <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for signing up to volunteer for <strong>" . htmlspecialchars($eventTitle) . "</strong>.</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p><strong>Date:</strong> " . htmlspecialchars($eventDate) . "</p>
            <p><strong>Time:</strong> " . htmlspecialchars($eventTime) . "</p>
            <p><strong>Location:</strong> " . htmlspecialchars($eventLoc) . "</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p>We look forward to seeing you!</p>
        ";
        $volunteer_body = render_email_template("Volunteer Confirmation", $volunteer_content);

        // Admin Notification Email
        $admin_subject = "New Volunteer Signup: " . preg_replace('/[\r\n]/', '', $name);
        $email_html = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $admin_content = "
            <p><strong>Event:</strong> " . htmlspecialchars($eventTitle) . "</p>
            <p><strong>Volunteer Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Volunteer Email:</strong> <a href='mailto:" . $email_html . "'>" . $email_html . "</a></p>
            <p><strong>Volunteer Phone:</strong> " . htmlspecialchars($phone) . "</p>
        ";
        $admin_body = render_email_template("New Volunteer Signup", $admin_content);

        $mail->send($config['user'], $email, $volunteer_subject, $volunteer_body, "Lions 2-E2 ERC");
        $mail->send($config['user'], $config['admin_email'], $admin_subject, $admin_body, "Lions 2-E2 ERC");
    } catch (Exception $e) {
        error_log("SMTP Registration Error: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Registration successful!', 'newCount' => $events[$eventIndex]['people']]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>