<?php
session_start();

// Configuration
$password = "Lions@2025"; // In a real app, use environment variables!
$error = '';
$message = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: admin-view.php');
    exit;
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin-view.php');
        exit;
    } else {
        $error = "Invalid password";
    }
}

// Auth Check
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// ------------------------------------------------------------------
// DATA LOGIC (Only runs if logged in)
// ------------------------------------------------------------------
if ($is_logged_in) {
    // Helper Functions
    function loadJSON($file) {
        if (!file_exists($file)) return [];
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }

    function saveJSON($file, $data) {
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    function ensureDir($path) {
        if (!is_dir($path)) mkdir($path, 0777, true);
    }

    // Handle POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        // --- SAVE EVENT ---
        if ($action === 'save_event') {
            ensureDir('images/events');
            $events = loadJSON('data/events.json');
            $id = $_POST['id'] ?? null;
            
            $imagePath = $_POST['existing_image'] ?? 'images/placeholder-event.svg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['image']['tmp_name'];
                $name = basename($_FILES['image']['name']);
                $target = "images/events/" . time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $name);
                if (move_uploaded_file($tmp_name, $target)) {
                    $imagePath = $target;
                }
            }

            $eventData = [
                "id" => $id ? (int)$id : (count($events) > 0 ? max(array_column($events, 'id')) + 1 : 1),
                "title" => $_POST['title'],
                "date" => $_POST['date'],
                "time" => $_POST['time'],
                "people" => (int)($_POST['people'] ?? 0),
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
            
            saveJSON('data/events.json', $events);
            $message = "Event saved successfully!";
        }

        // --- DELETE EVENT ---
        if ($action === 'delete_event') {
            $events = loadJSON('data/events.json');
            $id = $_POST['id'];
            $events = array_values(array_filter($events, function($e) use ($id) { return $e['id'] != $id; }));
            saveJSON('data/events.json', $events);
            $message = "Event deleted.";
        }

        // --- SAVE LOCATION ---
        if ($action === 'save_location') {
            $locations = loadJSON('data/locations.json');
            $index = $_POST['index'] ?? -1;
            $locData = [
                "name" => $_POST['name'],
                "address" => $_POST['address'],
                "phone" => $_POST['phone']
            ];

            if ($index >= 0 && $index !== '') {
                $locations[$index] = $locData;
            } else {
                $locations[] = $locData;
            }

            // Javascript provided sorting logic, but we can do it here too
            usort($locations, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            saveJSON('data/locations.json', $locations);
            $message = "Location saved!";
        }

        // --- DELETE LOCATION ---
        if ($action === 'delete_location') {
            $locations = loadJSON('data/locations.json');
            $index = $_POST['index'];
            array_splice($locations, $index, 1);
            saveJSON('data/locations.json', $locations);
            $message = "Location deleted.";
        }

        // --- SAVE BOARD ---
        if ($action === 'save_board') {
            ensureDir('images/board');
            $oldBoard = loadJSON('data/board.json');
            $oldImages = array_column($oldBoard, 'image');
            
            $board = [];
            if (isset($_POST['names'])) {
                for ($i = 0; $i < count($_POST['names']); $i++) {
                    if (!empty($_POST['names'][$i])) {
                        $imagePath = $_POST['images'][$i] ?? "images/board/placeholder.png";
                        
                        // Handle individual photo upload
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
            
            saveJSON('data/board.json', $board);
            // Optional: Cleanup Logic could go here (check for orphaned images)
            $message = "Board updated!";
        }

        // --- DELETE SIGNUP ---
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

        // --- UPLOAD GALLERY ---
        if ($action === 'upload_gallery') {
            ensureDir('gallery');
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

        // --- DELETE GALLERY ---
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

        // --- SAVE PARTNERS ---
        if ($action === 'save_partners') {
            $partners = array_filter(array_map('trim', explode("\n", $_POST['partners'])));
            saveJSON('data/partners.json', array_values($partners));
            $message = "Partners updated!";
        }

        // --- SAVE TESTIMONIALS ---
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

        // --- SAVE CONFIG ---
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

    // Load Data for View
    $events = loadJSON('data/events.json');
    $signups = loadJSON('data/signups.json');
    $locations = loadJSON('data/locations.json');
    $board = loadJSON('data/board.json');
    $partners = loadJSON('data/partners.json') ?: [];
    $testimonials = loadJSON('data/testimonials.json') ?: [];
    $gallery = loadJSON('data/gallery-cache.json');

    // Load Global Config
    if (file_exists('config.php')) include 'config.php';
    $current_email = $GLOBAL_EMAIL ?? "2e2erc1854@gmail.com";
    $current_phone = $GLOBAL_PHONE ?? "(817) 710-5403";
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - District 2-E2 ERC</title>
    
    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                fontFamily: {
                    sans: ['"Outfit"', 'sans-serif'],
                    body: ['Inter', 'sans-serif'],
                },
                extend: {
                    colors: {
                        indigo: { 50:'#eef2ff', 100:'#e0e7ff', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca', 900:'#312e81' }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-link.active {
            background-color: #6366f1;
            color: white;
        }
        /* Hide scrollbar for clean look */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">

    <?php if (!$is_logged_in): ?>
        <!-- Login View -->
        <main class="flex items-center justify-center min-h-screen p-4 relative overflow-hidden">
            <!-- Background Blob -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-500/20 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="glass-panel p-8 rounded-3xl w-full max-w-md shadow-2xl relative z-10">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">🔐</div>
                    <h1 class="text-2xl font-bold">Admin Portal</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">Valid credentials required.</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl text-center text-sm font-bold">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium mb-2 opacity-70">Password</label>
                        <input type="password" name="password" required autofocus 
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 hover:scale-[1.02] transition-all">
                        Access Dashboard
                    </button>
                    <div class="text-center mt-4">
                        <a href="index.html" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">← Back to Website</a>
                    </div>
                </form>
            </div>
        </main>

    <?php else: ?>
        <!-- Dashboard Layout -->
        <div class="flex h-screen overflow-hidden">
            
            <!-- Sidebar -->
            <aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col hidden md:flex shrink-0">
                <div class="p-6">
                    <h1 class="text-xl font-bold tracking-tight flex items-center gap-2">
                        <span class="text-indigo-600">⚡</span> Admin Panel
                    </h1>
                </div>
                
                <nav class="flex-1 px-4 space-y-1 overflow-y-auto no-scrollbar">
                    <a href="#" onclick="showSection('signups')" id="nav-signups" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span>👥</span> Signups
                    </a>
                    <a href="#" onclick="showSection('events')" id="nav-events" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span>📅</span> Events
                    </a>
                    <a href="#" onclick="showSection('locations')" id="nav-locations" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span>📍</span> Locations
                    </a>
                    <a href="#" onclick="showSection('gallery')" id="nav-gallery" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span>🖼️</span> Gallery
                    </a>
                    <a href="#" onclick="showSection('settings')" id="nav-settings" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span>⚙️</span> Settings & Board
                    </a>
                </nav>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    <a href="?action=logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <span>🚪</span> Log Out
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto relative no-scrollbar bg-slate-50 dark:bg-slate-950">
                <!-- Mobile Header -->
                <div class="md:hidden glass-panel sticky top-0 z-50 px-4 py-3 flex justify-between items-center mb-6">
                    <span class="font-bold">Admin Panel</span>
                    <!-- Simple mobile nav trigger could go here, for now just basic -->
                </div>

                <div class="p-4 md:p-8 max-w-6xl mx-auto">
                    
                    <?php if ($message): ?>
                        <div class="mb-6 px-4 py-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl flex items-center gap-2 text-sm font-bold animate-pulse">
                            ✅ <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- SECTIONS -->
                    
                    <!-- 1. SIGNUPS -->
                    <div id="section-signups" class="admin-section">
                        <header class="mb-8">
                            <h2 class="text-3xl font-bold mb-2">Volunteer Signups</h2>
                            <p class="text-slate-500">Overview of all registered volunteers.</p>
                        </header>
                        
                        <?php 
                        $hasSignups = false;
                        foreach ($events as $event):
                            $eventSignups = $signups[$event['id']] ?? [];
                            if (count($eventSignups) > 0):
                                $hasSignups = true;
                        ?>
                            <div class="glass-panel p-6 rounded-2xl mb-6">
                                <h3 class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mb-4"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead class="text-xs uppercase text-slate-500 font-bold border-b border-slate-200 dark:border-slate-700">
                                            <tr>
                                                <th class="py-3 px-2">Name</th>
                                                <th class="py-3 px-2">Email</th>
                                                <th class="py-3 px-2">Phone</th>
                                                <th class="py-3 px-2">Time</th>
                                                <th class="py-3 px-2 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <?php foreach ($eventSignups as $idx => $p): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                                <td class="py-3 px-2 font-medium"><?php echo htmlspecialchars($p['name']); ?></td>
                                                <td class="py-3 px-2 opacity-80"><?php echo htmlspecialchars($p['email']); ?></td>
                                                <td class="py-3 px-2 opacity-80"><?php echo htmlspecialchars($p['phone']); ?></td>
                                                <td class="py-3 px-2 opacity-60 text-xs"><?php echo $p['timestamp']; ?></td>
                                                <td class="py-3 px-2 text-right">
                                                    <form method="POST" onsubmit="return confirm('Remove?')" class="inline">
                                                        <input type="hidden" name="action" value="delete_signup">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                        <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                                        <button class="text-red-500 hover:text-red-700 text-xs font-bold uppercase">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                        
                        <?php if (!$hasSignups): ?>
                            <div class="text-center py-12 opacity-50">
                                <span class="text-4xl block mb-2">📭</span>
                                <p>No signups yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. EVENTS -->
                    <div id="section-events" class="admin-section hidden">
                        <header class="mb-8 flex justify-between items-center">
                            <div>
                                <h2 class="text-3xl font-bold mb-2">Events</h2>
                                <p class="text-slate-500">Manage upcoming opportunities.</p>
                            </div>
                            <button onclick="openModal('eventModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-indigo-500/20 active:scale-95">
                                + New Event
                            </button>
                        </header>

                        <div class="grid gap-6">
                            <?php foreach($events as $e): ?>
                                <div class="glass-panel p-6 rounded-2xl flex flex-col md:flex-row gap-6 items-start">
                                    <div class="w-24 h-24 shrink-0 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                        <img src="<?php echo $e['image']; ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($e['title']); ?></h3>
                                        <div class="flex flex-wrap gap-4 text-sm text-slate-500 mb-3">
                                            <span>📅 <?php echo $e['date']; ?></span>
                                            <span>📍 <?php echo $e['location']; ?></span>
                                            <span>👥 <?php echo $e['people']; ?>/<?php echo $e['maxPeople']; ?></span>
                                        </div>
                                        <div class="flex gap-3">
                                            <button onclick='editEvent(<?php echo json_encode($e); ?>)' class="text-sm font-bold text-indigo-500 hover:text-indigo-600">Edit</button>
                                            <form method="POST" onsubmit="return confirm('Delete this event?')" class="inline">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                                <button class="text-sm font-bold text-red-500 hover:text-red-600">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. LOCATIONS -->
                    <div id="section-locations" class="admin-section hidden">
                        <header class="mb-8 flex justify-between items-center">
                            <div>
                                <h2 class="text-3xl font-bold mb-2">Locations</h2>
                                <p class="text-slate-500">Manage drop-off points.</p>
                            </div>
                            <button onclick="openModal('locationModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg">
                                + New Location
                            </button>
                        </header>

                        <div class="glass-panel overflow-hidden rounded-2xl">
                             <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="py-4 px-6 font-bold">Name</th>
                                        <th class="py-4 px-6 font-bold">Address</th>
                                        <th class="py-4 px-6 font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php foreach($locations as $idx => $l): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                        <td class="py-4 px-6 font-bold"><?php echo htmlspecialchars($l['name']); ?></td>
                                        <td class="py-4 px-6 text-slate-500"><?php echo htmlspecialchars($l['address']); ?></td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <button onclick='editLocation(<?php echo $idx; ?>, <?php echo json_encode($l); ?>)' class="text-indigo-500 font-bold">Edit</button>
                                            <form method="POST" onsubmit="return confirm('Delete?')" class="inline">
                                                <input type="hidden" name="action" value="delete_location">
                                                <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                                <button class="text-red-500 font-bold">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>

                    <!-- 4. GALLERY -->
                    <div id="section-gallery" class="admin-section hidden">
                        <header class="mb-8">
                            <h2 class="text-3xl font-bold mb-2">Gallery</h2>
                            <p class="text-slate-500">Manage carousel images.</p>
                        </header>

                        <div class="glass-panel p-6 rounded-2xl mb-8">
                            <form method="POST" enctype="multipart/form-data" class="flex gap-4 items-end">
                                <input type="hidden" name="action" value="upload_gallery">
                                <div class="flex-1">
                                    <label class="block text-sm font-bold mb-2">Upload Photos</label>
                                    <input type="file" name="gallery_photos[]" multiple accept="image/*" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                                <button class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold">Upload</button>
                            </form>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach($gallery as $photo): ?>
                                <div class="group relative aspect-video rounded-xl overflow-hidden bg-black">
                                    <img src="<?php echo $photo; ?>" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                                    <form method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <input type="hidden" name="action" value="delete_gallery">
                                        <input type="hidden" name="photo" value="<?php echo $photo; ?>">
                                        <button class="bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg hover:bg-red-600">×</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 5. SETTINGS -->
                    <div id="section-settings" class="admin-section hidden">
                        <header class="mb-8">
                            <h2 class="text-3xl font-bold mb-2">Settings</h2>
                            <p class="text-slate-500">Global config & content.</p>
                        </header>

                        <div class="grid gap-8">
                            <!-- Global Config -->
                            <div class="glass-panel p-6 rounded-2xl">
                                <h3 class="text-lg font-bold mb-4">Contact Info</h3>
                                <form method="POST" class="grid md:grid-cols-2 gap-4">
                                    <input type="hidden" name="action" value="save_config">
                                    <div>
                                        <label class="block text-sm font-bold mb-1">Email</label>
                                        <input type="email" name="global_email" value="<?php echo htmlspecialchars($current_email); ?>" class="w-full p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold mb-1">Phone</label>
                                        <input type="text" name="global_phone" value="<?php echo htmlspecialchars($current_phone); ?>" class="w-full p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <button class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold">Save Changes</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Board Members -->
                            <div class="glass-panel p-6 rounded-2xl">
                                <h3 class="text-lg font-bold mb-4">Board Members</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="save_board">
                                    <div id="board-container" class="space-y-4">
                                        <?php foreach($board as $b): ?>
                                            <div class="board-row flex gap-4 items-center bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                                                <img src="<?php echo $b['image']; ?>" class="w-12 h-12 rounded-full object-cover bg-slate-200">
                                                <div class="flex-1 grid md:grid-cols-2 gap-4">
                                                    <input type="text" name="names[]" value="<?php echo htmlspecialchars($b['name']); ?>" class="p-2 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none" placeholder="Name">
                                                    <div class="flex gap-2">
                                                        <input type="file" name="board_files[]" class="text-xs text-slate-500">
                                                        <input type="hidden" name="images[]" value="<?php echo htmlspecialchars($b['image']); ?>">
                                                    </div>
                                                </div>
                                                <button type="button" onclick="this.closest('.board-row').remove()" class="text-red-500 font-bold">×</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-4 flex gap-4">
                                        <button type="button" onclick="addBoardMember()" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">+ Add Member</button>
                                        <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold">Save Board</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Testimonials -->
                            <div class="glass-panel p-6 rounded-2xl">
                                <h3 class="text-lg font-bold mb-4">Testimonials</h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_testimonials">
                                    <div id="testimonial-container" class="space-y-4">
                                        <?php foreach($testimonials as $t): ?>
                                            <div class="testimonial-row bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl relative group">
                                                <button type="button" onclick="this.closest('.testimonial-row').remove()" class="absolute top-2 right-2 text-red-500 opacity-50 group-hover:opacity-100 hover:text-red-700 transition-opacity">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                </button>
                                                <div class="mb-3">
                                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Quote</label>
                                                    <textarea name="texts[]" rows="6" class="w-full p-2 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none text-sm" placeholder="Testimonial text..."><?php echo htmlspecialchars($t['text']); ?></textarea>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Author</label>
                                                        <input type="text" name="authors[]" value="<?php echo htmlspecialchars($t['author']); ?>" class="w-full p-1 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none text-sm" placeholder="Name">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Organization</label>
                                                        <input type="text" name="orgs[]" value="<?php echo htmlspecialchars($t['organization']); ?>" class="w-full p-1 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none text-sm" placeholder="Role/Org">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-4 flex gap-4">
                                        <button type="button" onclick="addTestimonial()" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">+ Add Testimonial</button>
                                        <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold">Update Testimonials</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Partners -->
                            <div class="glass-panel p-6 rounded-2xl">
                                <h3 class="text-lg font-bold mb-4">Partners List</h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_partners">
                                    <textarea name="partners" rows="6" class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border-none focus:ring-2 focus:ring-indigo-500 font-mono text-sm mb-4" placeholder="One per line..."><?php echo implode("\n", $partners); ?></textarea>
                                    <button class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold">Update Partners</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>

        <!-- MODALS -->

        <!-- Event Modal -->
        <div id="eventModal" class="fixed inset-0 z-[100] hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('eventModal')"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl p-8 max-h-[90vh] overflow-y-auto">
                <h3 id="eventModalTitle" class="text-2xl font-bold mb-6">Add Event</h3>
                <form method="POST" enctype="multipart/form-data" id="eventForm" class="space-y-4">
                    <input type="hidden" name="action" value="save_event">
                    <input type="hidden" name="id" id="event_id">
                    <input type="hidden" name="existing_image" id="event_existing_image">
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold mb-1">Title</label>
                            <input type="text" name="title" id="event_title" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Date</label>
                            <input type="date" name="date" id="event_date" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Time</label>
                            <input type="text" name="time" id="event_time" placeholder="9:00 AM - 12:00 PM" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Max Volunteers</label>
                            <input type="number" name="maxPeople" id="event_maxPeople" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Current Count</label>
                            <input type="number" name="people" id="event_people" value="0" class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold mb-1">Location</label>
                            <input type="text" name="location" id="event_location" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                         <div class="col-span-2">
                            <label class="block text-sm font-bold mb-1">Image</label>
                            <input type="file" name="image" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" onclick="closeModal('eventModal')" class="px-6 py-2 font-bold text-slate-500 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700">Save Event</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Location Modal -->
        <div id="locationModal" class="fixed inset-0 z-[100] hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('locationModal')"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl p-8">
                <h3 id="locModalTitle" class="text-2xl font-bold mb-6">Add Location</h3>
                <form method="POST" id="locationForm" class="space-y-4">
                    <input type="hidden" name="action" value="save_location">
                    <input type="hidden" name="index" id="loc_index">
                    
                    <div>
                        <label class="block text-sm font-bold mb-1">Name</label>
                        <input type="text" name="name" id="loc_name" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Address</label>
                        <input type="text" name="address" id="loc_address" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Phone</label>
                        <input type="text" name="phone" id="loc_phone" class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    </div>

                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" onclick="closeModal('locationModal')" class="px-6 py-2 font-bold text-slate-500 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700">Save Location</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Theme Init
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Navigation
            function showSection(id) {
                document.querySelectorAll('.admin-section').forEach(el => el.classList.add('hidden'));
                document.getElementById('section-' + id).classList.remove('hidden');
                
                document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
                document.getElementById('nav-' + id).classList.add('active');
            }

            // Modals
            function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
            function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

            // Event Edit
            function editEvent(event) {
                document.getElementById('eventModalTitle').innerText = 'Edit Event';
                document.getElementById('event_id').value = event.id;
                document.getElementById('event_existing_image').value = event.image;
                document.getElementById('event_title').value = event.title;
                document.getElementById('event_date').value = event.date;
                document.getElementById('event_time').value = event.time;
                document.getElementById('event_maxPeople').value = event.maxPeople;
                document.getElementById('event_people').value = event.people;
                document.getElementById('event_location').value = event.location;
                openModal('eventModal');
            }

            // Location Edit
            function editLocation(index, loc) {
                document.getElementById('locModalTitle').innerText = 'Edit Location';
                document.getElementById('loc_index').value = index;
                document.getElementById('loc_name').value = loc.name;
                document.getElementById('loc_address').value = loc.address;
                document.getElementById('loc_phone').value = loc.phone;
                openModal('locationModal');
            }

            // Board Add
            function addBoardMember() {
                const container = document.getElementById('board-container');
                const div = document.createElement('div');
                div.className = 'board-row flex gap-4 items-center bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl';
                div.innerHTML = `
                    <div class="w-12 h-12 rounded-full bg-slate-200 flex items-center justify-center">👤</div>
                    <div class="flex-1 grid md:grid-cols-2 gap-4">
                        <input type="text" name="names[]" required class="p-2 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none" placeholder="Name">
                        <div class="flex gap-2">
                            <input type="file" name="board_files[]" class="text-xs text-slate-500">
                            <input type="hidden" name="images[]" value="images/board/placeholder.png">
                        </div>
                    </div>
                    <button type="button" onclick="this.closest('.board-row').remove()" class="text-red-500 font-bold">×</button>
                `;
                container.appendChild(div);
            }

            // Testimonial Add
            function addTestimonial() {
                const container = document.getElementById('testimonial-container');
                const div = document.createElement('div');
                div.className = 'testimonial-row bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl relative group';
                div.innerHTML = `
                    <button type="button" onclick="this.closest('.testimonial-row').remove()" class="absolute top-2 right-2 text-red-500 opacity-50 group-hover:opacity-100 hover:text-red-700 transition-opacity">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>
                    <div class="mb-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Quote</label>
                        <textarea name="texts[]" rows="6" class="w-full p-2 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none text-sm" placeholder="Testimonial text..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Author</label>
                             <input type="text" name="authors[]" class="w-full p-1 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none text-sm" placeholder="Name">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Organization</label>
                            <input type="text" name="orgs[]" class="w-full p-1 rounded bg-transparent border-b border-slate-300 focus:border-indigo-500 outline-none text-sm" placeholder="Role/Org">
                        </div>
                    </div>
                `;
                container.appendChild(div);
            }
        </script>
    <?php endif; ?>
</body>
</html>