<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    
    $fp = fopen($file, 'r');
    if (!$fp) return [];
    
    if (flock($fp, LOCK_SH)) {
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return json_decode($content, true) ?: [];
    }
    
    fclose($fp);
    return [];
}

function saveJSON($file, $data) {
    $fp = fopen($file, 'c');
    if (!$fp) return false;
    
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        $result = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result !== false;
    }
    
    fclose($fp);
    return false;
}

function isValidImageUpload($fileArray) {
    if ($fileArray['error'] !== UPLOAD_ERR_OK) return false;
    
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileArray['tmp_name']);
    finfo_close($finfo);
    
    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    
    return in_array($mime, $allowedMimeTypes) && in_array($ext, $allowedExtensions);
}

$message = "";
$error = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_event') {
        $events = loadJSON('data/events.json');
        $id = $_POST['id'] ?? null;
        
        $imagePath = $_POST['existing_image'] ?? 'images/placeholder-event.svg';
        if (isset($_FILES['image']) && isValidImageUpload($_FILES['image'])) {
            $tmp_name = $_FILES['image']['tmp_name'];
            $name = basename($_FILES['image']['name']);
            $target = "images/events/" . time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $name);
            if (move_uploaded_file($tmp_name, $target)) {
                $imagePath = $target;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
             $error = "Invalid image upload for event.";
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
                        $fileArray = [
                             'name' => $_FILES['board_files']['name'][$i],
                             'type' => $_FILES['board_files']['type'][$i],
                             'tmp_name' => $_FILES['board_files']['tmp_name'][$i],
                             'error' => $_FILES['board_files']['error'][$i],
                             'size' => $_FILES['board_files']['size'][$i]
                        ];
                        if (isValidImageUpload($fileArray)) {
                            $tmp_name = $fileArray['tmp_name'];
                            $name = basename($fileArray['name']);
                            $name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $name);
                            $target = "images/board/" . time() . "_" . $name;
                            if (move_uploaded_file($tmp_name, $target)) {
                                $imagePath = $target;
                            }
                        } else {
                            $error = "Invalid photo upload for board member.";
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
                    $fileArray = [
                         'name' => $files['name'][$i],
                         'type' => $files['type'][$i],
                         'tmp_name' => $files['tmp_name'][$i],
                         'error' => $files['error'][$i],
                         'size' => $files['size'][$i]
                    ];
                    if (isValidImageUpload($fileArray)) {
                        $tmp_name = $fileArray['tmp_name'];
                        $name = basename($fileArray['name']);
                        $name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $name);
                        $target = "gallery/" . time() . "_" . $name;
                        if (move_uploaded_file($tmp_name, $target)) {
                            $gallery[] = $target;
                            $count++;
                        }
                    } else {
                        $error = "One or more gallery images were invalid.";
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
        $safe_email = var_export($email, true);
        $safe_phone = var_export($phone, true);
        
        $configContent = "<?php\n// Global configuration for email and phone\n\$GLOBAL_EMAIL = $safe_email;\n\$GLOBAL_PHONE = $safe_phone;\n?>";
        if (file_put_contents('config.php', $configContent)) {
            $message = "Global settings updated!";
        } else {
            $error = "Failed to update config.php";
        }
    }

    if ($action === 'upload_form') {
        $targetFilename = $_POST['target_filename'];
        if (isset($_FILES['new_form']) && $_FILES['new_form']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['new_form']['tmp_name'];
            $cleanFilename = basename($targetFilename);
            $targetPath = "forms/" . $cleanFilename;

            if ($cleanFilename !== '') {
                $ext = strtolower(pathinfo($cleanFilename, PATHINFO_EXTENSION));
                if ($ext === 'pdf' && mime_content_type($tmp_name) === 'application/pdf') {
                    if (move_uploaded_file($tmp_name, $targetPath)) {
                        $message = "Form '$cleanFilename' successfully updated!";
                    } else {
                        $error = "Failed to upload the new form.";
                    }
                } else {
                     $error = "Invalid form upload. Only PDFs are allowed.";
                }
            } else {
                 $error = "Invalid target filename.";
            }
        } else {
            $error = "Upload error or no file provided.";
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
            --primary: #005A9C;
            --primary-dark: #003F6E;
            --accent: #2E8B57;
            --bg-color: #F8F9FA;
            --surface: #FFFFFF;
            --text-dark: #111827;
            --text-muted: #4B5563;
            --border-radius: 1.5rem;
            --border-radius-lg: 2rem;
            --font-heading: 'Plus Jakarta Sans', sans-serif;
            --font-body: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --glass: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: var(--font-body);
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Global Noise Texture */
        .noise-overlay {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.03;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        h1, h2, h3 { font-family: var(--font-heading); color: var(--text-dark); font-weight: 800; }

        /* Sidebar - Glass Pill Variant */
        .sidebar {
            width: 280px;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            padding: 3rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 3rem;
            position: fixed;
            height: calc(100vh - 2rem);
            margin: 1rem;
            border-radius: var(--border-radius-lg);
            z-index: 100;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.03em;
            padding-left: 1rem;
        }

        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 1rem 1.25rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 1.25rem;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .nav-links li a:hover {
            color: var(--primary);
            background: rgba(0, 90, 156, 0.05);
            transform: translateX(6px);
        }

        .nav-links li a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 25px rgba(0, 90, 156, 0.2);
        }

        /* Main Content */
        .main-content {
            margin-left: 320px;
            flex: 1;
            padding: 4rem 5rem;
            position: relative;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5rem;
        }

        header h1 { font-size: 2.5rem; letter-spacing: -0.04em; }

        /* Stats - Cinematic High Impact */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2.5rem;
            margin-bottom: 5rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 2.5rem;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--glass-border);
            box-shadow: 0 20px 40px rgba(0,0,0,0.02);
            transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 30px 60px rgba(0,0,0,0.05); }

        .stat-card h3 { 
            color: var(--text-muted); 
            font-size: 0.8rem; 
            margin-bottom: 1rem; 
            text-transform: uppercase; 
            letter-spacing: 0.1em;
            font-weight: 700;
        }
        .stat-card div { 
            font-family: var(--font-mono); 
            font-size: 3rem; 
            font-weight: 500; 
            color: var(--primary);
            letter-spacing: -0.05em;
        }

        /* Sections */
        .section {
            display: none;
            opacity: 0;
        }
        .section.active { display: block; }

        .card {
            background: var(--surface);
            padding: 3.5rem;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--glass-border);
            margin-bottom: 3rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.03);
            position: relative;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .card-header h2 { font-size: 1.8rem; letter-spacing: -0.03em; }

        /* Forms - Cinematic Inputs */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2.5rem;
        }
        .form-full { grid-column: span 2; }

        label { 
            display: block; 
            margin-bottom: 1rem; 
            color: var(--text-dark); 
            font-weight: 700; 
            font-size: 0.9rem;
            font-family: var(--font-heading);
        }
        
        input, select, textarea {
            width: 100%;
            padding: 1.25rem 1.5rem;
            background: #F1F3F5;
            border: 2px solid transparent;
            border-radius: 1.25rem;
            color: var(--text-dark);
            font-family: var(--font-body);
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        input:focus, select:focus, textarea:focus { 
            border-color: var(--primary); 
            background: white;
            box-shadow: 0 10px 30px rgba(0, 90, 156, 0.1);
            outline: none; 
        }

        /* Magnetic Buttons */
        .btn {
            font-family: var(--font-heading);
            font-weight: 800;
            padding: 1.25rem 2.5rem;
            border-radius: 4rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-size: 1rem;
            letter-spacing: -0.01em;
            text-decoration: none;
        }
        .btn:hover { transform: scale(1.05) translateY(-2px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .btn:active { transform: scale(0.98); }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-accent { background: var(--accent); color: white; }
        .btn-danger { background: #fee2e2; color: #dc2626; font-weight: 700; }
        .btn-danger:hover { background: #fecaca; }
        .btn-outline { background: transparent; border: 2px solid var(--text-dark); color: var(--text-dark); }
        .btn-outline:hover { background: var(--text-dark); color: white; }

        /* Tables - Ultra Clean */
        table { width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: -12px; }
        th { 
            padding: 1.5rem; 
            text-align: left; 
            color: var(--text-muted); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.15em; 
            font-weight: 800;
        }
        td { 
            padding: 1.75rem 1.5rem; 
            background: #FAFBFC; 
            border-top: 1px solid rgba(0,0,0,0.02);
            border-bottom: 1px solid rgba(0,0,0,0.02);
            font-weight: 500;
            color: var(--text-dark);
        }
        td:first-child { border-left: 1px solid rgba(0,0,0,0.02); border-radius: 1.5rem 0 0 1.5rem; }
        td:last-child { border-right: 1px solid rgba(0,0,0,0.02); border-radius: 0 1.5rem 1.5rem 0; }
        
        tr:hover td { background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.03); transform: scale(1.01); }

        .action-btns { display: flex; gap: 15px; }

        .image-preview {
            width: 60px;
            height: 60px;
            border-radius: 1.25rem;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-color); }
        ::-webkit-scrollbar-thumb {
            background: #D1D5DB;
            border-radius: 20px;
            border: 3px solid var(--bg-color);
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        /* Alerts - Floating Cinematic */
        .alert {
            position: fixed;
            top: 2rem;
            right: 2rem;
            padding: 1.5rem 2.5rem;
            border-radius: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            z-index: 10000;
            backdrop-filter: blur(10px);
            animation: slideInRight 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .alert-success { background: rgba(16, 185, 129, 0.9); color: white; }
        .alert-error { background: rgba(239, 68, 68, 0.9); color: white; }

        @keyframes slideInRight {
            from { transform: translateX(100%) scale(0.9); opacity: 0; }
            to { transform: translateX(0) scale(1); opacity: 1; }
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }
        .gallery-item {
            position: relative;
            border-radius: 2rem;
            overflow: hidden;
            aspect-ratio: 1;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 4px solid white;
        }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .gallery-item:hover img { transform: scale(1.15); }
        .gallery-item .delete-btn {
            position: absolute;
            top: 15px; right: 15px;
            background: rgba(239, 68, 68, 0.95);
            color: white; border: none; border-radius: 50%;
            width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; opacity: 0; transform: translateY(-10px);
            transition: all 0.4s ease;
            font-size: 1.2rem;
        }
        .gallery-item:hover .delete-btn { opacity: 1; transform: translateY(0); }

        @media (max-width: 1200px) {
            .sidebar { width: 100px; padding: 3rem 0.5rem; margin: 0.5rem; height: calc(100vh - 1rem); }
            .sidebar .logo span, .sidebar .nav-links li a span { display: none; }
            .sidebar .nav-links li a { justify-content: center; padding: 1.25rem; }
            .main-content { margin-left: 120px; padding: 3rem; }
        }
    </style>
    
    <!-- GSAP for Smooth Transitions -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
</head>
<body>
    <div class="noise-overlay"></div>

    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-hand-holding-heart"></i>
            <span>ERC ADMIN</span>
        </div>
        <ul class="nav-links">
            <li><a href="#" data-target="dashboard" class="active"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li><a href="#" data-target="signups"><i class="fas fa-users"></i> <span>Signups</span></a></li>
            <li><a href="#" data-target="events"><i class="fas fa-calendar-alt"></i> <span>Events</span></a></li>
            <li><a href="#" data-target="locations"><i class="fas fa-map-marker-alt"></i> <span>Locations</span></a></li>
            <li><a href="#" data-target="gallery"><i class="fas fa-images"></i> <span>Gallery</span></a></li>
            <li><a href="#" data-target="forms"><i class="fas fa-file-pdf"></i> <span>Forms</span></a></li>
            <li><a href="#" data-target="board"><i class="fas fa-user-tie"></i> <span>Board Members</span></a></li>
            <li><a href="#" data-target="testimonials"><i class="fas fa-comment-dots"></i> <span>Testimonials</span></a></li>
            <li><a href="#" data-target="other"><i class="fas fa-cogs"></i> <span>Other Info</span></a></li>
        </ul>
        <div style="margin-top: auto;">
            <a href="index.html" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-external-link-alt"></i> View Site</a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <div>
                <h1>Dashboard</h1>
            </div>
            <div class="user-info">
                <span class="badge" style="background: var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem;">Logged in as Admin</span>
            </div>
        </header>
        
        <!-- Provide CSRF token for JS functions -->
        <script>
            const CSRF_TOKEN = "<?php echo $_SESSION['csrf_token']; ?>";
        </script>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- DASHBOARD SECTION -->
        <section id="dashboard" class="section active">
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
                    <h3>Active Events</h3>
                    <div><?php echo count($events); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Drop-off Locations</h3>
                    <div><?php echo count($locations); ?></div>
                </div>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2>Upcoming Events Overview</h2>
                    <button class="btn btn-outline" onclick="document.querySelector('[data-target=\'events\']').click()"><i class="fas fa-calendar-plus"></i> Manage Events</button>
                </div>
                
                <?php if (count($events) === 0): ?>
                    <p style="color: var(--text-dim);">No upcoming events scheduled.</p>
                <?php else: ?>
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Event Date</th>
                                <th>Title</th>
                                <th>Volunteers</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                // Sort events by date closest to today
                                $sortedEvents = $events;
                                usort($sortedEvents, function($a, $b) {
                                    return strtotime($a['date']) - strtotime($b['date']);
                                });
                                // Keep only future events and top 5
                                $upcoming = array_filter($sortedEvents, function($e) {
                                    return strtotime($e['date']) >= strtotime('today');
                                });
                                $upcoming = array_slice($upcoming, 0, 5);
                                
                                foreach($upcoming as $e): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['date']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($e['title']); ?></strong></td>
                                    <td>
                                        <?php if ($e['people'] >= $e['maxPeople']): ?>
                                            <span style="color: var(--danger);"><i class="fas fa-user-check"></i> FULL (<?php echo $e['maxPeople']; ?>/<?php echo $e['maxPeople']; ?>)</span>
                                        <?php else: ?>
                                            <span style="color: var(--accent);"><i class="fas fa-users"></i> <?php echo $e['people']; ?>/<?php echo $e['maxPeople']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($e['location']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <!-- SIGNUPS SECTION -->
        <section id="signups" class="section">
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
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="delete_gallery">
                                <input type="hidden" name="photo" value="<?php echo $photo; ?>">
                                <button type="submit" class="delete-btn" title="Delete"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- FORMS SECTION -->
        <section id="forms" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Manage Forms</h2>
                </div>
                <p style="margin-bottom: 2rem; color: var(--text-dim);">Upload a new file to replace an existing form. The original filename will be preserved.</p>
                
                <?php
                if (!is_dir('forms')) {
                    mkdir('forms', 0777, true);
                }
                $formFiles = array_diff(scandir('forms'), array('.', '..', 'README.md'));
                $hasPdf = false;
                foreach ($formFiles as $formFile):
                    $ext = strtolower(pathinfo($formFile, PATHINFO_EXTENSION));
                    if ($ext === 'pdf'):
                        $hasPdf = true;
                ?>
                    <div style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--primary); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="margin-bottom: 0.5rem; color: var(--accent);"><i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($formFile); ?></h3>
                            <a href="forms/<?php echo htmlspecialchars($formFile); ?>" target="_blank" style="color: var(--text-dim); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-external-link-alt"></i> View Current File</a>
                        </div>
                        <form action="admin-view.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="upload_form">
                            <input type="hidden" name="target_filename" value="<?php echo htmlspecialchars($formFile); ?>">
                            <input type="file" name="new_form" accept=".pdf,application/pdf" required style="width: auto;">
                            <button type="submit" class="btn btn-accent"><i class="fas fa-upload"></i> Replace</button>
                        </form>
                    </div>
                <?php endif; endforeach; 
                if (!$hasPdf): ?>
                    <p style="color: var(--text-dim);">No PDF forms found in the forms directory.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- OTHER INFO SECTION -->
        <section id="other" class="section">
            <div class="card">
                <h2>Global Settings</h2>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                <h2>Partners</h2>
                <p style="margin-bottom: 1rem; color: var(--text-dim);">One partner name per line.</p>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="save_partners">
                    <textarea name="partners" rows="10"><?php echo implode("\n", $partners); ?></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Update Partners</button>
                </form>
            </div>
        </section>

        <!-- BOARD MEMBERS SECTION -->
        <section id="board" class="section">
            <div class="card">
                <h2>Board Members</h2>
                <form action="admin-view.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="save_board">
                    <div id="board-container" style="margin-top: 1.5rem;">
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
        </section>

        <!-- TESTIMONIALS SECTION -->
        <section id="testimonials" class="section">
            <div class="card">
                <h2>Testimonials</h2>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="save_testimonials">
                    <div id="testimonial-container" style="margin-top: 1.5rem;">
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
        </section>

    </main>

    <script>
        // GSAP Initialization
        document.addEventListener('DOMContentLoaded', () => {
            gsap.from(".sidebar", { x: -80, opacity: 0, duration: 1.2, ease: "power4.out" });
            gsap.from(".main-content header h1", { y: -30, opacity: 0, duration: 1, delay: 0.3, ease: "power3.out" });
            gsap.from(".stat-card", { 
                y: 40, 
                opacity: 0, 
                duration: 1, 
                stagger: 0.15, 
                delay: 0.5, 
                ease: "power3.out" 
            });
            gsap.from(".card", { 
                y: 50, 
                opacity: 0, 
                duration: 1.2, 
                delay: 0.8, 
                ease: "power4.out" 
            });
        });

        // Navigation
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = link.getAttribute('data-target');
                
                // Update nav
                document.querySelectorAll('.nav-links a').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Update sections with cinematic fade
                const sections = document.querySelectorAll('.section');
                sections.forEach(s => {
                    if (s.id === target) {
                        s.classList.add('active');
                        gsap.fromTo(s, 
                            { opacity: 0, y: 30, filter: 'blur(10px)' }, 
                            { opacity: 1, y: 0, filter: 'blur(0px)', duration: 0.8, ease: "power4.out", clearProps: "all" }
                        );
                    } else {
                        s.classList.remove('active');
                    }
                });
                
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