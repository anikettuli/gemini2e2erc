<?php
// Simple password protection
$password = "Lions@2025"; // CHANGE THIS
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != 'admin' || $_SERVER['PHP_AUTH_PW'] != $password) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access Denied';
    exit;
}

// Helper Functions
function loadJSON($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveJSON($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$message = "";
$error = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_event') {
        $events = loadJSON('data/events.json');
        $id = $_POST['id'] ?? null;
        
        $imagePath = $_POST['existing_image'] ?? 'images/placeholder-event.svg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['image']['tmp_name'];
            $name = basename($_FILES['image']['name']);
            $target = "images/events/" . time() . "_" . $name;
            if (move_uploaded_file($tmp_name, $target)) {
                $imagePath = $target;
            }
        }

        $signupsForEvent = loadJSON('data/signups.json');
        $eventData = [
            "id" => $id ? (int)$id : (count($events) > 0 ? max(array_column($events, 'id')) + 1 : 1),
            "title" => $_POST['title'],
            "date" => $_POST['date'],
            "time" => $_POST['time'],
            "people" => $id && isset($signupsForEvent[$id]) ? count($signupsForEvent[$id]) : 0,
            "maxPeople" => (int)$_POST['maxPeople'],
            "location" => $_POST['location'],
            "image" => $imagePath,
            "description" => $_POST['description'],
            "contact" => $_POST['contact']
        ];

        if ($id) {
            foreach ($events as &$e) {
                if ($e['id'] == $id) {
                    $e = $eventData;
                    break;
                }
            }
        } else {
            $events[] = $eventData;
        }
        
        if (saveJSON('data/events.json', $events)) {
            $message = "Event saved successfully!";
        } else {
            $error = "Failed to save event.";
        }
    }

    if ($action === 'delete_event') {
        $events = loadJSON('data/events.json');
        $id = $_POST['id'];
        $events = array_values(array_filter($events, function($e) use ($id) { return $e['id'] != $id; }));
        saveJSON('data/events.json', $events);
        $message = "Event deleted.";
    }

    if ($action === 'save_location') {
        $locations = loadJSON('data/locations.json');
        $index = $_POST['index'] ?? -1;
        $locData = [
            "name" => $_POST['name'],
            "address" => $_POST['address'],
            "phone" => $_POST['phone']
        ];

        if ($index >= 0) {
            $locations[$index] = $locData;
        } else {
            $locations[] = $locData;
        }

        // Sort locations alphabetically by name
        usort($locations, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        saveJSON('data/locations.json', $locations);
        $message = "Location saved!";
    }

    if ($action === 'delete_location') {
        $locations = loadJSON('data/locations.json');
        $index = $_POST['index'];
        array_splice($locations, $index, 1);
        saveJSON('data/locations.json', $locations);
        $message = "Location deleted.";
    }

    if ($action === 'save_board') {
        $gallery_data = loadJSON('data/gallery-cache.json'); // need it if we use it elsewhere, but board is separate
        $oldBoard = loadJSON('data/board.json');
        $oldImages = array_column($oldBoard, 'image');
        
        $board = [];
        if (isset($_POST['names'])) {
            for ($i = 0; $i < count($_POST['names']); $i++) {
                if (!empty($_POST['names'][$i])) {
                    $imagePath = $_POST['images'][$i] ?? "images/board/placeholder.png";
                    
                    // Handle individual photo upload for this board member
                    if (isset($_FILES['board_files']['name'][$i]) && $_FILES['board_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['board_files']['tmp_name'][$i];
                        $name = basename($_FILES['board_files']['name'][$i]);
                        $name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $name);
                        $target = "images/board/" . time() . "_" . $name;
                        if (move_uploaded_file($tmp_name, $target)) {
                            $imagePath = $target;
                        }
                    }

                    $board[] = [
                        "name" => $_POST['names'][$i],
                        "image" => $imagePath
                    ];
                }
            }
        }
        
        if (saveJSON('data/board.json', $board)) {
            // Cleanup orphaned images
            $newImages = array_column($board, 'image');
            foreach ($oldImages as $oldImg) {
                if ($oldImg && !in_array($oldImg, $newImages) && strpos($oldImg, 'images/board/') === 0 && !strpos($oldImg, 'placeholder')) {
                    if (file_exists($oldImg)) unlink($oldImg);
                }
            }
            $message = "Board updated!";
        }
    }

    if ($action === 'delete_signup') {
        $signups = loadJSON('data/signups.json');
        $eventId = $_POST['event_id'];
        $index = $_POST['index'];
        if (isset($signups[$eventId][$index])) {
            array_splice($signups[$eventId], $index, 1);
            if (empty($signups[$eventId])) {
                unset($signups[$eventId]);
            }
            saveJSON('data/signups.json', $signups);
            $message = "Signup removed.";
        }
    }

    if ($action === 'upload_gallery') {
        $gallery = loadJSON('data/gallery-cache.json');
        if (isset($_FILES['gallery_photos'])) {
            $files = $_FILES['gallery_photos'];
            $count = 0;
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $files['tmp_name'][$i];
                    $name = basename($files['name'][$i]);
                    $name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $name);
                    $target = "gallery/" . time() . "_" . $name;
                    if (move_uploaded_file($tmp_name, $target)) {
                        $gallery[] = $target;
                        $count++;
                    }
                }
            }
            saveJSON('data/gallery-cache.json', array_values($gallery));
            $message = "$count gallery photos uploaded!";
        }
    }

    if ($action === 'delete_gallery') {
        $gallery = loadJSON('data/gallery-cache.json');
        $photo = $_POST['photo'];
        if (($key = array_search($photo, $gallery)) !== false) {
            unset($gallery[$key]);
            saveJSON('data/gallery-cache.json', array_values($gallery));
            if (file_exists($photo)) {
                unlink($photo);
            }
            $message = "Photo removed from gallery.";
        }
    }

    if ($action === 'save_partners') {
        $partners = array_filter(array_map('trim', explode("\n", $_POST['partners'])));
        saveJSON('data/partners.json', array_values($partners));
        $message = "Partners updated!";
    }

    if ($action === 'save_testimonials') {
        $testimonials = [];
        if (isset($_POST['texts'])) {
            for ($i = 0; $i < count($_POST['texts']); $i++) {
                if (!empty($_POST['texts'][$i])) {
                    $testimonials[] = [
                        "text" => $_POST['texts'][$i],
                        "author" => $_POST['authors'][$i],
                        "organization" => $_POST['orgs'][$i]
                    ];
                }
            }
        }
        saveJSON('data/testimonials.json', $testimonials);
        $message = "Testimonials updated!";
    }

    if ($action === 'save_config') {
        $email = $_POST['global_email'];
        $phone = $_POST['global_phone'];
        $configContent = "<?php\n// Global configuration for email and phone\n\$GLOBAL_EMAIL = \"$email\";\n\$GLOBAL_PHONE = \"$phone\";\n?>";
        if (file_put_contents('config.php', $configContent)) {
            $message = "Global settings updated!";
        } else {
            $error = "Failed to update config.php";
        }
    }
}

// Load current data
$events = loadJSON('data/events.json');
$signups = loadJSON('data/signups.json');

// Auto-sync events 'people' count with actual signups
$needsSync = false;
foreach ($events as &$ev) {
    $actualCount = isset($signups[$ev['id']]) ? count($signups[$ev['id']]) : 0;
    if (!isset($ev['people']) || $ev['people'] != $actualCount) {
        $ev['people'] = $actualCount;
        $needsSync = true;
    }
}
if ($needsSync) {
    saveJSON('data/events.json', $events);
}

$locations = loadJSON('data/locations.json');
$board = loadJSON('data/board.json');
$partners = loadJSON('data/partners.json') ?: [];
$testimonials = loadJSON('data/testimonials.json') ?: [];
$gallery = loadJSON('data/gallery-cache.json');

// Load config
include 'config.php';
$current_email = $GLOBAL_EMAIL ?? "2e2erc1854@gmail.com";
$current_phone = $GLOBAL_PHONE ?? "(817) 710-5403";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERC Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #004990;
            --primary-dark: #003366;
            --accent: #FDB913;
            --accent-hover: #e5a70d;
            --bg-dark: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --text-light: #f8fafc;
            --text-dim: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
        }

        h1, h2, h3 { font-family: 'Outfit', sans-serif; }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            color: var(--text-dim);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links li a:hover, .nav-links li a.active {
            background: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-left: 220px;
            flex: 1;
            padding: 2rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
        }

        .stat-card h3 { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card div { font-size: 1.8rem; font-weight: 700; color: var(--accent); }

        /* Sections */
        .section {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .section.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .form-full { grid-column: span 2; }

        label { display: block; margin-bottom: 0.5rem; color: var(--text-dim); font-size: 0.9rem; }
        input, select, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: white;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        input:focus { border-color: var(--primary); outline: none; }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-accent { background: var(--accent); color: var(--bg-dark); }
        .btn-accent:hover { background: var(--accent-hover); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--glass-border); color: var(--text-light); }

        /* Table Style */
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--glass-border); }
        th { color: var(--text-dim); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .action-btns { display: flex; gap: 10px; }

        .image-preview {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            background: #222;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 14px;
            height: 14px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border: 3px solid var(--bg-dark);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* For Firefox */
        * {
            scrollbar-width: auto;
            scrollbar-color: var(--primary) var(--bg-dark);
        }

        .phone-cell {
            white-space: nowrap;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            animation: slideDown 0.3s ease;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); color: #6ee7b7; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger); color: #fca5a5; }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 2rem 0.5rem; }
            .sidebar .logo span, .sidebar .nav-links li a span { display: none; }
            .sidebar .nav-links li a { justify-content: center; }
            .main-content { margin-left: 80px; }
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 16/9;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .gallery-item:hover img { transform: scale(1.05); }
        .gallery-item .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 5;
        }
        .gallery-item:hover .delete-btn { opacity: 1; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-glasses"></i>
            <span>ERC ADMIN</span>
        </div>
        <ul class="nav-links">
            <li><a href="#" data-target="signups" class="active"><i class="fas fa-users"></i> <span>Signups</span></a></li>
            <li><a href="#" data-target="events"><i class="fas fa-calendar-alt"></i> <span>Events</span></a></li>
            <li><a href="#" data-target="locations"><i class="fas fa-map-marker-alt"></i> <span>Locations</span></a></li>
            <li><a href="#" data-target="gallery"><i class="fas fa-images"></i> <span>Gallery</span></a></li>
            <li><a href="#" data-target="other"><i class="fas fa-edit"></i> <span>Other Info</span></a></li>
        </ul>
        <div style="margin-top: auto;">
            <a href="index.html" style="color: var(--text-dim); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-external-link-alt"></i> View Site</a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <div>
                <h1>Dashboard</h1>
                <p style="color: var(--text-dim);">Manage your eye recycling center data</p>
            </div>
            <div class="user-info">
                <span class="badge" style="background: var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem;">Logged in as Admin</span>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Gallery Photos</h3>
                <div><?php echo count($gallery); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Signups</h3>
                <div><?php 
                    $totalSignups = 0;
                    foreach($signups as $s) $totalSignups += count($s);
                    echo $totalSignups;
                ?></div>
            </div>
            <div class="stat-card">
                <h3>Drop-off Locations</h3>
                <div><?php echo count($locations); ?></div>
            </div>
        </div>

        <!-- SIGNUPS SECTION -->
        <section id="signups" class="section active">
            <div class="card">
                <div class="card-header">
                    <h2>Volunteer Signups</h2>
                </div>
                <?php foreach ($events as $event): 
                    $eventSignups = $signups[$event['id']] ?? [];
                    if (count($eventSignups) > 0):
                ?>
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--accent); margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($event['title']); ?> 
                            <small style="color: var(--text-dim); font-weight: normal; font-size: 0.9rem;">(<?php echo $event['date']; ?>)</small>
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventSignups as $idx => $person): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($person['name']); ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($person['email']); ?>" style="color: var(--accent); text-decoration: none;"><?php echo htmlspecialchars($person['email']); ?></a></td>
                                        <td class="phone-cell"><?php echo htmlspecialchars($person['phone'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($person['timestamp']); ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Remove this signup?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_signup">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                                <button type="submit" class="btn btn-outline" style="padding: 0.4rem 0.8rem; color: var(--danger);"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </section>

        <!-- EVENTS SECTION -->
        <section id="events" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Manage Events</h2>
                    <button class="btn btn-accent" onclick="showEventForm()"><i class="fas fa-plus"></i> New Event</button>
                </div>

                <div id="eventForm" style="display:none; margin-bottom: 3rem; background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; border: 1px solid var(--primary);">
                    <h3 id="formTitle">Add New Event</h3>
                    <form action="admin-view.php" method="POST" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                        <input type="hidden" name="action" value="save_event">
                        <input type="hidden" name="id" id="event_id">
                        <input type="hidden" name="existing_image" id="event_existing_image">
                        
                        <div class="form-grid">
                            <div>
                                <label>Title</label>
                                <input type="text" name="title" id="event_title" required>
                            </div>
                            <div>
                                <label>Image (Upload new or leave empty to keep current)</label>
                                <input type="file" name="image" accept="image/*">
                            </div>
                            <div>
                                <label>Date</label>
                                <input type="date" name="date" id="event_date" required>
                            </div>
                            <div>
                                <label>Time (e.g. 9:00 AM - 12:00 PM)</label>
                                <input type="text" name="time" id="event_time" required>
                            </div>
                            <div>
                                <label>Max Volunteers</label>
                                <input type="number" name="maxPeople" id="event_maxPeople" required>
                            </div>
                            <div class="form-full">
                                <label>Location</label>
                                <input type="text" name="location" id="event_location" required>
                            </div>
                            <div class="form-full">
                                <label>Contact Email</label>
                                <input type="email" name="contact" id="event_contact" value="2e2erc1854@gmail.com">
                            </div>
                            <div class="form-full">
                                <label>Description</label>
                                <textarea name="description" id="event_description" rows="3"></textarea>
                            </div>
                        </div>
                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Event</button>
                            <button type="button" class="btn btn-outline" onclick="hideEventForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Volunteers</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($events as $e): ?>
                            <tr>
                                <td><img src="<?php echo $e['image']; ?>" class="image-preview"></td>
                                <td><strong><?php echo htmlspecialchars($e['title']); ?></strong></td>
                                <td><?php echo $e['date']; ?></td>
                                <td><?php echo $e['time']; ?></td>
                                <td><?php echo $e['people']; ?>/<?php echo $e['maxPeople']; ?></td>
                                <td class="action-btns">
                                    <button class="btn btn-outline" title="Edit Event" onclick='editEvent(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-edit"></i></button>
                                    <button type="button" class="btn btn-outline" title="Duplicate Event" onclick='duplicateEvent(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8'); ?>)'><i class="fas fa-copy"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?');">
                                        <input type="hidden" name="action" value="delete_event">
                                        <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                        <button type="submit" class="btn btn-outline" style="color: var(--danger);"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- LOCATIONS SECTION -->
        <section id="locations" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Drop-off Locations</h2>
                    <button class="btn btn-accent" onclick="showLocationForm()"><i class="fas fa-plus"></i> Add Location</button>
                </div>

                <div id="locationForm" style="display:none; margin-bottom: 3rem; background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; border: 1px solid var(--primary);">
                    <h3 id="locFormTitle">Add New Location</h3>
                    <form action="admin-view.php" method="POST" style="margin-top: 1.5rem;">
                        <input type="hidden" name="action" value="save_location">
                        <input type="hidden" name="index" id="loc_index" value="-1">
                        <div class="form-grid">
                            <div class="form-full">
                                <label>Name</label>
                                <input type="text" name="name" id="loc_name" required>
                            </div>
                            <div class="form-full">
                                <label>Address</label>
                                <input type="text" name="address" id="loc_address" required>
                            </div>
                            <div class="form-full">
                                <label>Phone</label>
                                <input type="text" name="phone" id="loc_phone">
                            </div>
                        </div>
                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Location</button>
                            <button type="button" class="btn btn-outline" onclick="hideLocationForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <div style="max-height: 600px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%;">Name</th>
                                <th style="width: 45%;">Address</th>
                                <th style="width: 15%;">Phone</th>
                                <th style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($locations as $idx => $l): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($l['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($l['address']); ?></td>
                                    <td class="phone-cell"><?php echo htmlspecialchars($l['phone']); ?></td>
                                    <td class="action-btns">
                                        <button class="btn btn-outline" onclick='editLocation(<?php echo $idx; ?>, <?php echo json_encode($l); ?>)'><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this location?');">
                                            <input type="hidden" name="action" value="delete_location">
                                            <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                            <button type="submit" class="btn btn-outline" style="color: var(--danger);"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- GALLERY SECTION -->
        <section id="gallery" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Gallery Carousel Photos</h2>
                </div>
                
                <form action="admin-view.php" method="POST" enctype="multipart/form-data" style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--primary);">
                    <input type="hidden" name="action" value="upload_gallery">
                    <label>Upload new photos (Select multiple)</label>
                    <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem;">
                        <input type="file" name="gallery_photos[]" multiple accept="image/*" required>
                        <button type="submit" class="btn btn-accent"><i class="fas fa-upload"></i> Upload</button>
                    </div>
                </form>

                <div class="gallery-grid">
                    <?php foreach($gallery as $photo): ?>
                        <div class="gallery-item">
                            <img src="<?php echo $photo; ?>" loading="lazy">
                            <form method="POST" onsubmit="return confirm('Remove this photo from gallery?');">
                                <input type="hidden" name="action" value="delete_gallery">
                                <input type="hidden" name="photo" value="<?php echo $photo; ?>">
                                <button type="submit" class="delete-btn" title="Delete"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- OTHER INFO SECTION -->
        <section id="other" class="section">
            <div class="card">
                <h2>Global Settings</h2>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="action" value="save_config">
                    <div class="form-grid">
                        <div>
                            <label>Global Contact Email</label>
                            <input type="email" name="global_email" value="<?php echo htmlspecialchars($current_email); ?>" required>
                        </div>
                        <div>
                            <label>Global Contact Phone</label>
                            <input type="text" name="global_phone" value="<?php echo htmlspecialchars($current_phone); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Update Global Settings</button>
                </form>
            </div>

            <div class="card">
                <h2>Board Members</h2>
                <form action="admin-view.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_board">
                    <div id="board-container">
                        <?php foreach($board as $b): ?>
                            <div class="form-grid board-row" style="margin-bottom: 2rem; align-items: start; border-bottom: 1px solid var(--glass-border); padding-bottom: 1.5rem;">
                                <div style="display: flex; gap: 1.5rem; grid-column: span 2;">
                                    <img src="<?php echo $b['image']; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);">
                                    <div style="flex: 1;">
                                        <div class="form-grid">
                                            <div>
                                                <label>Name</label>
                                                <input type="text" name="names[]" value="<?php echo htmlspecialchars($b['name']); ?>" required>
                                            </div>
                                            <div>
                                                <label>Update Photo</label>
                                                <input type="file" name="board_files[]" accept="image/*">
                                                <input type="hidden" name="images[]" value="<?php echo htmlspecialchars($b['image']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; padding-top: 1.5rem;">
                                        <button type="button" class="btn btn-outline" style="color: var(--danger);" onclick="if(confirm('Remove this member?')) this.closest('.board-row').remove()"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline" onclick="addBoardMember()" style="margin-bottom: 1.5rem; margin-top: 1rem;"><i class="fas fa-plus"></i> Add New Member</button>
                    <br>
                    <button type="submit" class="btn btn-primary">Save All Changes</button>
                </form>
            </div>

            <div class="card">
                <h2>Testimonials</h2>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="action" value="save_testimonials">
                    <div id="testimonial-container">
                        <?php foreach($testimonials as $t): ?>
                            <div class="testimonial-row" style="background: rgba(0,0,0,0.1); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--glass-border); position: relative;">
                                <button type="button" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: var(--danger); cursor: pointer;" onclick="if(confirm('Remove this testimonial?')) this.closest('.testimonial-row').remove()"><i class="fas fa-times-circle fa-lg"></i></button>
                                <label>Testimonial Text</label>
                                <textarea name="texts[]" rows="3" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($t['text']); ?></textarea>
                                <div class="form-grid">
                                    <div>
                                        <label>Author Name</label>
                                        <input type="text" name="authors[]" value="<?php echo htmlspecialchars($t['author']); ?>">
                                    </div>
                                    <div>
                                        <label>Organization / Role</label>
                                        <input type="text" name="orgs[]" value="<?php echo htmlspecialchars($t['organization']); ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline" onclick="addTestimonial()" style="margin-bottom: 1.5rem;"><i class="fas fa-plus"></i> Add New Testimonial</button>
                    <br>
                    <button type="submit" class="btn btn-primary">Update All Testimonials</button>
                </form>
            </div>

            <div class="card">
                <h2>Partners</h2>
                <p style="margin-bottom: 1rem; color: var(--text-dim);">One partner name per line.</p>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="action" value="save_partners">
                    <textarea name="partners" rows="10"><?php echo implode("\n", $partners); ?></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Update Partners</button>
                </form>
            </div>
        </section>

    </main>

    <script>
        // Navigation
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = link.getAttribute('data-target');
                
                // Update nav
                document.querySelectorAll('.nav-links a').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Update sections
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.getElementById(target).classList.add('active');
                
                // Update header
                document.querySelector('header h1').innerText = target.charAt(0).toUpperCase() + target.slice(1);

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        // Event Management
        function showEventForm() {
            document.getElementById('eventForm').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Add New Event';
            document.getElementById('event_id').value = '';
            document.getElementById('event_existing_image').value = 'images/placeholder-event.svg';
            document.querySelector('#eventForm form').reset();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function hideEventForm() { document.getElementById('eventForm').style.display = 'none'; }
        
        function editEvent(event) {
            showEventForm();
            document.getElementById('formTitle').innerText = 'Edit Event: ' + event.title;
            document.getElementById('event_id').value = event.id;
            document.getElementById('event_existing_image').value = event.image;
            document.getElementById('event_title').value = event.title;
            document.getElementById('event_date').value = event.date;
            document.getElementById('event_time').value = event.time;
            document.getElementById('event_maxPeople').value = event.maxPeople;
            document.getElementById('event_location').value = event.location;
            document.getElementById('event_contact').value = event.contact;
            document.getElementById('event_description').value = event.description;
        }

        function duplicateEvent(event) {
            showEventForm();
            document.getElementById('formTitle').innerText = 'Duplicate Event: ' + event.title;
            document.getElementById('event_id').value = ''; // Empty ID to create a new event
            document.getElementById('event_existing_image').value = event.image;
            document.getElementById('event_title').value = 'Copy of ' + event.title;
            document.getElementById('event_date').value = '';
            document.getElementById('event_time').value = event.time;
            document.getElementById('event_maxPeople').value = event.maxPeople;
            document.getElementById('event_location').value = event.location;
            document.getElementById('event_contact').value = event.contact;
            document.getElementById('event_description').value = event.description;
        }


        // Location Management
        function showLocationForm() {
            document.getElementById('locationForm').style.display = 'block';
            document.getElementById('locFormTitle').innerText = 'Add New Location';
            document.getElementById('loc_index').value = '-1';
            document.querySelector('#locationForm form').reset();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function hideLocationForm() { document.getElementById('locationForm').style.display = 'none'; }
        
        function editLocation(index, loc) {
            showLocationForm();
            document.getElementById('locFormTitle').innerText = 'Edit Location: ' + loc.name;
            document.getElementById('loc_index').value = index;
            document.getElementById('loc_name').value = loc.name;
            document.getElementById('loc_address').value = loc.address;
            document.getElementById('loc_phone').value = loc.phone;
        }

        // Row Helpers
        function addBoardMember() {
            const container = document.getElementById('board-container');
            const div = document.createElement('div');
            div.className = 'board-row';
            div.style.marginBottom = '2rem';
            div.style.borderBottom = '1px solid var(--glass-border)';
            div.style.paddingBottom = '1.5rem';
            div.innerHTML = `
                <div style="display: flex; gap: 1.5rem; align-items: start;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #222; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user fa-2x" style="color: var(--text-dim);"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="form-grid">
                            <div>
                                <label>Name</label>
                                <input type="text" name="names[]" required>
                            </div>
                            <div>
                                <label>Upload Photo</label>
                                <input type="file" name="board_files[]" accept="image/*">
                                <input type="hidden" name="images[]" value="images/board/placeholder.png">
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; padding-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" style="color: var(--danger);" onclick="this.closest('.board-row').remove()"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        function addTestimonial() {
            const container = document.getElementById('testimonial-container');
            const div = document.createElement('div');
            div.className = 'testimonial-row';
            div.style.background = 'rgba(0,0,0,0.1)';
            div.style.padding = '1.5rem';
            div.style.borderRadius = '12px';
            div.style.marginBottom = '1.5rem';
            div.style.border = '1px solid var(--glass-border)';
            div.style.position = 'relative';
            div.innerHTML = `
                <button type="button" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: var(--danger); cursor: pointer;" onclick="if(confirm('Remove this testimonial?')) this.closest('.testimonial-row').remove()"><i class="fas fa-times-circle fa-lg"></i></button>
                <label>Testimonial Text</label>
                <textarea name="texts[]" rows="3" style="margin-bottom: 1rem;"></textarea>
                <div class="form-grid">
                    <div>
                        <label>Author Name</label>
                        <input type="text" name="authors[]">
                    </div>
                    <div>
                        <label>Organization / Role</label>
                        <input type="text" name="orgs[]">
                    </div>
                </div>
            `;
            container.appendChild(div);
        }
    </script>
</body>
</html>