<?php
/**
 * Lions District 2-E2 ERC - Administrative Console
 * Built with OpenUI Design Principles
 */

session_start();
require_once __DIR__ . '/config.php';

// Generate or verify CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Authentication
$creds = get_admin_credentials();

// Extract HTTP Auth (handles FastCGI / Apache header rewrites)
$authUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$authPass = $_SERVER['PHP_AUTH_PW'] ?? '';

if (empty($authUser) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0) {
        $decoded = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
        if ($decoded && strpos($decoded, ':') !== false) {
            list($authUser, $authPass) = explode(':', $decoded, 2);
        }
    }
}

// Timing-safe credential comparison
if (!hash_equals($creds['user'], $authUser) || !hash_equals($creds['pass'], $authPass)) {
    header('WWW-Authenticate: Basic realm="Lions District 2-E2 ERC Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Access Denied</h2><p>Valid administrator credentials required.</p></body></html>';
    exit;
}

// Helper to safely load JSON
function load_json_file($path, $default = []) {
    if (!file_exists($path)) return $default;
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

// Helper to safely save JSON atomically
function save_json_file($path, $data) {
    $fp = fopen($path, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

$notice = "";
$error = "";

// 2. Process Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Session verification failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        // Action: Delete Signup
        if ($action === 'delete_signup') {
            $eventId = (string)($_POST['event_id'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $signups = load_json_file(__DIR__ . '/data/signups.json', []);
            $events = load_json_file(__DIR__ . '/data/events.json', []);

            if (isset($signups[$eventId])) {
                $signups[$eventId] = array_values(array_filter($signups[$eventId], function($s) use ($email) {
                    return strtolower($s['email']) !== $email;
                }));
                save_json_file(__DIR__ . '/data/signups.json', $signups);

                // Sync event people count
                foreach ($events as &$ev) {
                    if ((string)$ev['id'] === $eventId) {
                        $ev['people'] = count($signups[$eventId]);
                        break;
                    }
                }
                save_json_file(__DIR__ . '/data/events.json', $events);
                $notice = "Volunteer registration removed.";
            }
        }

        // Action: Save / Edit Event
        if ($action === 'save_event') {
            $events = load_json_file(__DIR__ . '/data/events.json', []);
            $signups = load_json_file(__DIR__ . '/data/signups.json', []);
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
            $title = strip_tags(trim($_POST['title'] ?? ''));
            $date = strip_tags(trim($_POST['date'] ?? ''));
            $time = strip_tags(trim($_POST['time'] ?? ''));
            $maxPeople = (int)($_POST['maxPeople'] ?? 20);
            $location = strip_tags(trim($_POST['location'] ?? ''));
            $description = strip_tags(trim($_POST['description'] ?? ''));
            $contact = strip_tags(trim($_POST['contact'] ?? $GLOBAL_EMAIL));
            $image = strip_tags(trim($_POST['image'] ?? 'images/placeholder-event.svg'));

            if (!empty($title) && !empty($date)) {
                if ($id > 0) {
                    // Update existing
                    foreach ($events as &$ev) {
                        if ($ev['id'] === $id) {
                            $ev['title'] = $title;
                            $ev['date'] = $date;
                            $ev['time'] = $time;
                            $ev['maxPeople'] = $maxPeople;
                            $ev['location'] = $location;
                            $ev['description'] = $description;
                            $ev['contact'] = $contact;
                            if (!empty($image)) $ev['image'] = $image;
                            break;
                        }
                    }
                    $notice = "Event updated successfully.";
                } else {
                    // Create new
                    $newId = count($events) > 0 ? max(array_column($events, 'id')) + 1 : 1;
                    $events[] = [
                        'id' => $newId,
                        'title' => $title,
                        'date' => $date,
                        'time' => $time,
                        'people' => 0,
                        'maxPeople' => $maxPeople,
                        'location' => $location,
                        'image' => !empty($image) ? $image : 'images/placeholder-event.svg',
                        'description' => $description,
                        'contact' => $contact
                    ];
                    $notice = "New event created successfully.";
                }
                save_json_file(__DIR__ . '/data/events.json', $events);
            } else {
                $error = "Event title and date are required.";
            }
        }

        // Action: Delete Event
        if ($action === 'delete_event') {
            $id = (int)($_POST['id'] ?? 0);
            $events = load_json_file(__DIR__ . '/data/events.json', []);
            $events = array_values(array_filter($events, function($ev) use ($id) {
                return (int)$ev['id'] !== $id;
            }));
            save_json_file(__DIR__ . '/data/events.json', $events);
            $notice = "Event deleted successfully.";
        }

        // Action: Add / Update Location
        if ($action === 'save_location') {
            $locations = load_json_file(__DIR__ . '/data/locations.json', []);
            $name = strip_tags(trim($_POST['name'] ?? ''));
            $address = strip_tags(trim($_POST['address'] ?? ''));
            $phone = strip_tags(trim($_POST['phone'] ?? ''));

            if (!empty($name) && !empty($address)) {
                $locations[] = [
                    'name' => $name,
                    'address' => $address,
                    'phone' => $phone
                ];
                save_json_file(__DIR__ . '/data/locations.json', $locations);
                $notice = "Drop-off partner location added.";
            } else {
                $error = "Location name and address are required.";
            }
        }
    }
}

// CSV Export for Volunteer Signups
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $events = load_json_file(__DIR__ . '/data/events.json', []);
    $signups = load_json_file(__DIR__ . '/data/signups.json', []);

    $eventMap = [];
    foreach ($events as $ev) {
        $eventMap[(string)$ev['id']] = $ev['title'] . " (" . $ev['date'] . ")";
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Lions_ERC_Volunteer_Signups_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Event ID', 'Event Name', 'Volunteer Name', 'Email', 'Phone', 'Registered At']);

    foreach ($signups as $evId => $regList) {
        $evName = $eventMap[$evId] ?? "Event #$evId";
        foreach ($regList as $reg) {
            fputcsv($output, [
                $evId,
                $evName,
                $reg['name'] ?? '',
                $reg['email'] ?? '',
                $reg['phone'] ?? '',
                $reg['timestamp'] ?? ''
            ]);
        }
    }
    fclose($output);
    exit;
}

// Load data for display
$events = load_json_file(__DIR__ . '/data/events.json', []);
$signups = load_json_file(__DIR__ . '/data/signups.json', []);
$locations = load_json_file(__DIR__ . '/data/locations.json', []);

// Sort events by date
usort($events, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});

$totalSignups = 0;
foreach ($signups as $sList) {
    $totalSignups += count($sList);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard &bull; Lions District 2-E2 ERC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #090d16;
      --surface: #111827;
      --surface-hover: #1f293d;
      --surface-border: #1f2937;
      --text: #f9fafb;
      --text-muted: #9ca3af;
      --primary: #0284c7;
      --primary-hover: #0369a1;
      --gold: #f59e0b;
      --danger: #ef4444;
      --success: #10b981;
      --radius: 10px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
      padding-bottom: 60px;
    }
    header {
      background: rgba(17, 24, 39, 0.8);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--surface-border);
      position: sticky;
      top: 0;
      z-index: 50;
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo-area { display: flex; align-items: center; gap: 12px; }
    .logo-area h1 { font-size: 18px; font-weight: 700; color: #fff; }
    .logo-area span { font-size: 13px; color: var(--gold); background: rgba(245,158,11,0.1); padding: 2px 8px; border-radius: 9999px; }
    .actions { display: flex; gap: 10px; }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: var(--radius);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      border: 1px solid transparent;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-outline { background: transparent; border-color: var(--surface-border); color: var(--text); }
    .btn-outline:hover { background: var(--surface-hover); }
    .btn-danger { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border-color: rgba(239, 68, 68, 0.3); }
    .btn-danger:hover { background: var(--danger); color: #fff; }

    .container { max-width: 1200px; margin: 32px auto; padding: 0 20px; }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }
    .stat-card {
      background: var(--surface);
      border: 1px solid var(--surface-border);
      border-radius: var(--radius);
      padding: 20px;
    }
    .stat-card span { font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
    .stat-card h2 { font-size: 28px; margin-top: 4px; font-weight: 700; color: #fff; }

    .nav-tabs {
      display: flex;
      gap: 8px;
      border-bottom: 1px solid var(--surface-border);
      margin-bottom: 24px;
    }
    .tab-btn {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 10px 18px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: all 0.15s;
    }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .card {
      background: var(--surface);
      border: 1px solid var(--surface-border);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
    }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-title { font-size: 17px; font-weight: 700; }

    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { padding: 12px 16px; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid var(--surface-border); font-weight: 600; }
    td { padding: 14px 16px; border-bottom: 1px solid var(--surface-border); vertical-align: middle; }
    tr:hover td { background: var(--surface-hover); }

    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 700;
    }
    .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    .badge-full { background: rgba(239, 68, 68, 0.15); color: #f87171; }

    .alert { padding: 12px 18px; border-radius: var(--radius); margin-bottom: 20px; font-size: 14px; }
    .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; }
    .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-group input, .form-group textarea, .form-group select {
      width: 100%;
      background: #0b0f19;
      border: 1px solid var(--surface-border);
      border-radius: var(--radius);
      padding: 10px 14px;
      color: #fff;
      font-size: 14px;
      font-family: inherit;
    }
    .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); outline: none; }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
  </style>
</head>
<body>

<header>
  <div class="logo-area">
    <h1>Lions District 2-E2 ERC</h1>
    <span>Admin Suite</span>
  </div>
  <div class="actions">
    <a href="index.html" target="_blank" class="btn btn-outline">&larr; Live Website</a>
    <a href="?export=csv" class="btn btn-primary">&DownArrow; Export Signups (CSV)</a>
  </div>
</header>

<div class="container">

  <?php if (!empty($notice)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($notice) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat-card">
      <span>Total Volunteers</span>
      <h2><?= $totalSignups ?></h2>
    </div>
    <div class="stat-card">
      <span>Scheduled Events</span>
      <h2><?= count($events) ?></h2>
    </div>
    <div class="stat-card">
      <span>Partner Locations</span>
      <h2><?= count($locations) ?></h2>
    </div>
  </div>

  <div class="nav-tabs">
    <button class="tab-btn active" onclick="showTab('tab-signups')">Volunteer Signups</button>
    <button class="tab-btn" onclick="showTab('tab-events')">Manage Events</button>
    <button class="tab-btn" onclick="showTab('tab-locations')">Drop-Off Locations</button>
    <button class="tab-btn" onclick="showTab('tab-new-event')">+ Create Event</button>
  </div>

  <!-- Tab 1: Volunteer Signups -->
  <div id="tab-signups" class="tab-content active">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Registered Volunteers</h3>
        <a href="?export=csv" class="btn btn-outline btn-sm">Export All to CSV</a>
      </div>
      <?php if ($totalSignups === 0): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 32px 0;">No volunteer registrations recorded yet.</p>
      <?php else: ?>
        <?php foreach ($events as $ev): ?>
          <?php $evSignups = $signups[(string)$ev['id']] ?? []; ?>
          <?php if (!empty($evSignups)): ?>
            <div style="margin-bottom: 28px;">
              <h4 style="margin-bottom: 10px; color: var(--gold); display: flex; align-items: center; justify-content: space-between;">
                <span><?= htmlspecialchars($ev['title']) ?> &bull; <small style="color:var(--text-muted);"><?= htmlspecialchars($ev['date']) ?> (<?= htmlspecialchars($ev['time']) ?>)</small></span>
                <span class="badge <?= count($evSignups) >= $ev['maxPeople'] ? 'badge-full' : 'badge-success' ?>">
                  <?= count($evSignups) ?> / <?= $ev['maxPeople'] ?> Volunteers
                </span>
              </h4>
              <table>
                <thead>
                  <tr>
                    <th>Volunteer Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Signup Time</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($evSignups as $reg): ?>
                    <tr>
                      <td style="font-weight: 600;"><?= htmlspecialchars($reg['name'] ?? '') ?></td>
                      <td><a href="mailto:<?= htmlspecialchars($reg['email'] ?? '') ?>" style="color:var(--primary); text-decoration:none;"><?= htmlspecialchars($reg['email'] ?? '') ?></a></td>
                      <td><?= htmlspecialchars($reg['phone'] ?? '-') ?></td>
                      <td style="color:var(--text-muted); font-size:12px;"><?= htmlspecialchars($reg['timestamp'] ?? '') ?></td>
                      <td>
                        <form method="POST" onsubmit="return confirm('Remove this volunteer registration?');" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                          <input type="hidden" name="action" value="delete_signup">
                          <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                          <input type="hidden" name="email" value="<?= htmlspecialchars($reg['email'] ?? '') ?>">
                          <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 11px;">Remove</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tab 2: Manage Events -->
  <div id="tab-events" class="tab-content">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Calendar Events</h3>
        <button class="btn btn-primary" onclick="showTab('tab-new-event')">+ Add New Event</button>
      </div>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Event Title</th>
            <th>Time</th>
            <th>Location</th>
            <th>Capacity</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td style="font-weight: 600;"><?= htmlspecialchars($ev['date']) ?></td>
              <td><?= htmlspecialchars($ev['title']) ?></td>
              <td style="color: var(--text-muted);"><?= htmlspecialchars($ev['time']) ?></td>
              <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($ev['location']) ?></td>
              <td>
                <span class="badge <?= $ev['people'] >= $ev['maxPeople'] ? 'badge-full' : 'badge-success' ?>">
                  <?= $ev['people'] ?> / <?= $ev['maxPeople'] ?>
                </span>
              </td>
              <td>
                <form method="POST" onsubmit="return confirm('Delete this event?');" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <input type="hidden" name="action" value="delete_event">
                  <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                  <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 11px;">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab 3: Drop-Off Locations -->
  <div id="tab-locations" class="tab-content">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Partner Drop-Off Locations (<?= count($locations) ?>)</h3>
      </div>
      <div style="margin-bottom: 20px;">
        <input type="text" id="adminLocSearch" placeholder="Type city, partner name, or zip code to filter..." 
               style="width: 100%; background: #0b0f19; border: 1px solid var(--surface-border); padding: 10px 14px; border-radius: var(--radius); color: #fff;">
      </div>
      <div style="max-height: 480px; overflow-y: auto;">
        <table id="adminLocTable">
          <thead>
            <tr>
              <th>Partner Name</th>
              <th>Address</th>
              <th>Phone</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($locations as $loc): ?>
              <tr>
                <td style="font-weight: 600;"><?= htmlspecialchars($loc['name']) ?></td>
                <td><?= htmlspecialchars($loc['address']) ?></td>
                <td><?= htmlspecialchars($loc['phone'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab 4: Create Event Form -->
  <div id="tab-new-event" class="tab-content">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Add New Volunteer Event</h3>
      </div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="save_event">

        <div class="form-row">
          <div class="form-group">
            <label>Event Title *</label>
            <input type="text" name="title" required placeholder="e.g. 2nd Saturday Open Session">
          </div>
          <div class="form-group">
            <label>Date (YYYY-MM-DD) *</label>
            <input type="date" name="date" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Time Slot *</label>
            <input type="text" name="time" required value="9:00 AM - 12:00 PM">
          </div>
          <div class="form-group">
            <label>Volunteer Capacity *</label>
            <input type="number" name="maxPeople" required value="20" min="1" max="100">
          </div>
        </div>

        <div class="form-group">
          <label>Location *</label>
          <input type="text" name="location" required value="Main Center - 5621 Bunker Blvd, Watauga, TX 76148">
        </div>

        <div class="form-group">
          <label>Event Description</label>
          <textarea name="description" rows="3" placeholder="Describe the volunteer tasks (sorting, cleaning, measuring)..."></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="contact" value="<?= htmlspecialchars($GLOBAL_EMAIL) ?>">
          </div>
          <div class="form-group">
            <label>Image Path (optional)</label>
            <input type="text" name="image" value="images/events/second-saturday-session.jpg">
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Create Volunteer Event</button>
      </form>
    </div>
  </div>

</div>

<script>
function showTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  const target = document.getElementById(tabId);
  if (target) target.classList.add('active');
  const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.getAttribute('onclick').includes(tabId));
  if (activeBtn) activeBtn.classList.add('active');
}

// Live search in admin locations
document.getElementById('adminLocSearch')?.addEventListener('input', function(e) {
  const term = e.target.value.toLowerCase();
  const rows = document.querySelectorAll('#adminLocTable tbody tr');
  rows.forEach(r => {
    const text = r.textContent.toLowerCase();
    r.style.display = text.includes(term) ? '' : 'none';
  });
});
</script>

</body>
</html>