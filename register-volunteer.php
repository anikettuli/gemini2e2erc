<?php
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

    // 8. Send Email (Simple version)
    $to = $email;
    $subject = "Volunteer Confirmation: " . $events[$eventIndex]['title'];
    $message = "Hi $name,\n\nThank you for signing up for " . $events[$eventIndex]['title'] . ".\n";
    $message .= "Date: " . $events[$eventIndex]['date'] . "\n";
    $message .= "Time: " . $events[$eventIndex]['time'] . "\n";
    $message .= "Location: " . $events[$eventIndex]['location'] . "\n\n";
    $message .= "We look forward to seeing you!\n\nLions District 2-E2 ERC";
    $headers = "From: info@2e2erc.org";

    mail($to, $subject, $message, $headers);

    // Notify Admin
    mail("info@2e2erc.org", "New Volunteer Signup: $name", "Event: " . $events[$eventIndex]['title'] . "\nName: $name\nEmail: $email", $headers);

    echo json_encode(['success' => true, 'message' => 'Registration successful!', 'newCount' => $events[$eventIndex]['people']]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>