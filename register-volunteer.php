<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get Input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $eventId = $input['eventId'] ?? null;
    $name = strip_tags(trim($input['name'] ?? ''));
    $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = strip_tags(trim($input['phone'] ?? ''));

    if (!$eventId || !$name || !$email) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // 2. Load Data
    $eventsFile = 'data/events.json';
    $signupsFile = 'data/signups.json';

    $events = json_decode(file_get_contents($eventsFile), true);
    $signups = json_decode(file_get_contents($signupsFile), true);

    // 3. Find Event
    $eventIndex = -1;
    foreach ($events as $index => $event) {
        if ($event['id'] == $eventId) {
            $eventIndex = $index;
            break;
        }
    }

    if ($eventIndex === -1) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }

    // 4. Check Capacity
    if ($events[$eventIndex]['people'] >= $events[$eventIndex]['maxPeople']) {
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
    $events[$eventIndex]['people']++;

    // 7. Save Files
    file_put_contents($signupsFile, json_encode($signups, JSON_PRETTY_PRINT));
    file_put_contents($eventsFile, json_encode($events, JSON_PRETTY_PRINT));

    // 8. Send Emails via SMTP
    require_once 'mail-utils.php';
    $config = get_smtp_config();
    $mail = new SimpleSMTP($config['host'], $config['port'], $config['user'], $config['pass']);

    $eventTitle = $events[$eventIndex]['title'];
    $eventDate = $events[$eventIndex]['date'];
    $eventTime = $events[$eventIndex]['time'];
    $eventLoc = $events[$eventIndex]['location'];

    // Volunteer Confirmation Email
    $volunteer_subject = "Volunteer Confirmation: $eventTitle";
    $volunteer_body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 5px;'>
            <div style='background: #00447c; color: #fff; padding: 15px; text-align: center;'>
                <h2 style='margin:0;'>Volunteer Confirmation</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Thank you for signing up to volunteer for <strong>" . htmlspecialchars($eventTitle) . "</strong>.</p>
                <hr>
                <p><strong>Date:</strong> " . htmlspecialchars($eventDate) . "</p>
                <p><strong>Time:</strong> " . htmlspecialchars($eventTime) . "</p>
                <p><strong>Location:</strong> " . htmlspecialchars($eventLoc) . "</p>
                <hr>
                <p>We look forward to seeing you!</p>
            </div>
            <div style='background: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; color: #777;'>
                Lions District 2-E2 ERC
            </div>
        </div>
    </body>
    </html>";

    // Admin Notification Email
    $admin_subject = "New Volunteer Signup: $name";
    $admin_body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 5px;'>
            <div style='background: #00447c; color: #fff; padding: 15px; text-align: center;'>
                <h2 style='margin:0;'>New Volunteer Signup</h2>
            </div>
            <div style='padding: 20px;'>
                <p><strong>Event:</strong> " . htmlspecialchars($eventTitle) . "</p>
                <p><strong>Volunteer Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Volunteer Email:</strong> <a href='mailto:$email'>" . htmlspecialchars($email) . "</a></p>
                <p><strong>Volunteer Phone:</strong> " . htmlspecialchars($phone) . "</p>
            </div>
        </div>
    </body>
    </html>";

    try {
        // Send to Volunteer
        $mail->send($config['user'], $email, $volunteer_subject, $volunteer_body, "Lions 2-E2 ERC");
        
        // Send to Admin
        $mail->send($config['user'], $config['admin_email'], $admin_subject, $admin_body, "Lions 2-E2 ERC");
    } catch (Exception $e) {
        error_log("SMTP Registration Error: " . $e->getMessage());
        // We still return success since the signup was saved, but log the email failure
    }

    echo json_encode(['success' => true, 'message' => 'Registration successful!', 'newCount' => $events[$eventIndex]['people']]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>