<?php
// Simple password protection
$password = "lions2025"; // CHANGE THIS
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != 'admin' || $_SERVER['PHP_AUTH_PW'] != $password) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access Denied';
    exit;
}

$events = json_decode(file_get_contents('data/events.json'), true);
$signups = json_decode(file_get_contents('data/signups.json'), true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Signups</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { color: #004990; }
    </style>
</head>
<body>
    <h1>Volunteer Signups Admin</h1>
    
    <?php foreach ($events as $event): ?>
        <?php if (isset($signups[$event['id']]) && count($signups[$event['id']]) > 0): ?>
            <h2><?php echo $event['title']; ?> (<?php echo $event['date']; ?>)</h2>
            <p>Registered: <?php echo $event['people']; ?> / <?php echo $event['maxPeople']; ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Signed Up At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signups[$event['id']] as $person): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($person['name']); ?></td>
                            <td><?php echo htmlspecialchars($person['email']); ?></td>
                            <td><?php echo htmlspecialchars($person['phone']); ?></td>
                            <td><?php echo htmlspecialchars($person['timestamp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>