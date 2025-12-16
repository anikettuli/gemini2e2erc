<?php
// Simple password protection
$password = "Lions@2025"; // CHANGE THIS
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
    <title>Volunteer Signups - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            padding: 2rem;
            color: #e8e8e8;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            background: linear-gradient(135deg, #004990, #003366);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 73, 144, 0.3);
        }
        h1 { 
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 700;
        }
        header p {
            color: rgba(255,255,255,0.8);
            margin-top: 0.5rem;
        }
        .event-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }
        h2 { 
            color: #3d8fcf; 
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }
        .event-meta {
            color: #FDB913;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
        }
        .badge {
            background: rgba(253, 185, 19, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td { 
            padding: 12px 16px; 
            text-align: left; 
        }
        th { 
            background: rgba(0, 73, 144, 0.5);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }
        tr:last-child td {
            border-bottom: none;
        }
        .no-signups {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
        a { color: #3d8fcf; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>👓 Volunteer Signups</h1>
            <p>Lions District 2-E2 ERC Admin Dashboard</p>
        </header>
        
        <?php 
        $hasSignups = false;
        foreach ($events as $event): 
            if (isset($signups[$event['id']]) && count($signups[$event['id']]) > 0): 
                $hasSignups = true;
        ?>
            <div class="event-card">
                <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                <div class="event-meta">
                    <span>📅 <?php echo $event['date']; ?></span>
                    <span>⏰ <?php echo $event['time']; ?></span>
                    <span class="badge">👥 <?php echo $event['people']; ?> / <?php echo $event['maxPeople']; ?> registered</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Signed Up</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signups[$event['id']] as $person): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($person['name']); ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($person['email']); ?>"><?php echo htmlspecialchars($person['email']); ?></a></td>
                                <td><?php echo htmlspecialchars($person['phone'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($person['timestamp']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php 
            endif;
        endforeach; 
        
        if (!$hasSignups):
        ?>
            <div class="event-card no-signups">
                <p>📋 No volunteer signups yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>