<?php
/**
 * ERC Administrative Dashboard
 * Protected by HTTP Basic Auth + per-IP brute-force rate limiting + CSRF + atomic file locking.
 */

date_default_timezone_set('America/Chicago');
require_once __DIR__ . '/config.php';

ini_set('display_errors', '0');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
    || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

if (PHP_SESSION_ACTIVE !== session_status()) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Extracts HTTP Basic Auth credentials across mod_php, PHP-FPM, and CGI/FastCGI on cPanel.
 */
function erc_get_basic_auth_input(): array {
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        return [(string)$_SERVER['PHP_AUTH_USER'], (string)($_SERVER['PHP_AUTH_PW'] ?? ''), true];
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (is_string($authHeader) && stripos($authHeader, 'Basic ') === 0) {
        $decoded = base64_decode(substr($authHeader, 6), true);
        if (is_string($decoded) && strpos($decoded, ':') !== false) {
            list($u, $p) = explode(':', $decoded, 2);
            return [$u, $p, true];
        }
    }

    return ['', '', false];
}

/**
 * Resolves configured admin credentials without storing any plaintext password in code.
 * Resolution priority:
 *   1. data/admin-auth.json (bcrypt hash set via Admin Dashboard; survives FTP deploys)
 *   2. .env ADMIN_PASS_HASH (bcrypt/Argon2 hash)
 *   3. .env ADMIN_PASS (constant-time string match)
 *   4. Legacy PBKDF2-HMAC-SHA256 fallback digest (prevents cPanel lockout until rotated)
 */
function erc_get_admin_auth_config(): array {
    $authFile = __DIR__ . '/data/admin-auth.json';
    $stored = erc_load_json($authFile, []);
    if (!empty($stored['password_hash']) && is_string($stored['password_hash'])) {
        return [
            'user' => !empty($stored['user']) ? (string)$stored['user'] : 'admin',
            'mode' => 'password_hash',
            'secret' => $stored['password_hash'],
            'is_legacy_default' => false,
        ];
    }

    $env = erc_load_env();
    $user = !empty($env['ADMIN_USER']) ? (string)$env['ADMIN_USER'] : 'admin';

    if (!empty($env['ADMIN_PASS_HASH'])) {
        return [
            'user' => $user,
            'mode' => 'password_hash',
            'secret' => (string)$env['ADMIN_PASS_HASH'],
            'is_legacy_default' => false,
        ];
    }

    if (isset($env['ADMIN_PASS']) && $env['ADMIN_PASS'] !== '') {
        return [
            'user' => $user,
            'mode' => 'env_plain',
            'secret' => (string)$env['ADMIN_PASS'],
            'is_legacy_default' => false,
        ];
    }

    return [
        'user' => $user,
        'mode' => 'pbkdf2_legacy',
        'salt' => '2e2erc-legacy-admin-v1',
        'secret' => '06a860745dbe420b317f6e606d01d3c4b62fbf762ee36ec74c948389515de109',
        'is_legacy_default' => true,
    ];
}

function erc_verify_admin_credentials(string $inputUser, string $inputPass, array $authConfig): bool {
    $userValid = hash_equals((string)$authConfig['user'], $inputUser);
    $passValid = false;

    if ($authConfig['mode'] === 'password_hash') {
        $passValid = password_verify($inputPass, (string)$authConfig['secret']);
    } elseif ($authConfig['mode'] === 'env_plain') {
        $passValid = hash_equals((string)$authConfig['secret'], $inputPass);
    } elseif ($authConfig['mode'] === 'pbkdf2_legacy') {
        $computed = bin2hex(hash_pbkdf2('sha256', $inputPass, (string)$authConfig['salt'], 210000, 32, true));
        $passValid = hash_equals((string)$authConfig['secret'], $computed);
    }

    return $userValid && $passValid;
}

// Enforce brute-force lockout (max 8 failed attempts per IP per 15 minutes)
if (!erc_check_rate_limit('admin_login_fail', 8, 900, false)) {
    http_response_code(429);
    header('Retry-After: 900');
    echo 'Too many failed login attempts. Please wait 15 minutes and try again.';
    exit;
}

$authConfig = erc_get_admin_auth_config();
list($suppliedUser, $suppliedPass, $hasAuthHeader) = erc_get_basic_auth_input();

if (!$hasAuthHeader || !erc_verify_admin_credentials($suppliedUser, $suppliedPass, $authConfig)) {
    if ($hasAuthHeader) {
        erc_check_rate_limit('admin_login_fail', 8, 900, true);
    }
    header('WWW-Authenticate: Basic realm="ERC Admin Area"');
    http_response_code(401);
    echo 'Access Denied';
    exit;
}

erc_clear_rate_limit('admin_login_fail');

/**
 * Validates an uploaded image file via finfo MIME detection and getimagesize().
 */
function isValidImageUpload(array $fileArray): bool {
    if (($fileArray['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return false;
    }
    if (empty($fileArray['tmp_name']) || !is_file($fileArray['tmp_name'])) {
        return false;
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $fileArray['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $ext = strtolower(pathinfo((string)($fileArray['name'] ?? ''), PATHINFO_EXTENSION));
    $imgInfo = @getimagesize($fileArray['tmp_name']);

    return $imgInfo !== false
        && in_array($mime, $allowedMimeTypes, true)
        && in_array($ext, $allowedExtensions, true);
}

/**
 * Optimizes an uploaded image in-place (resizing oversized dimensions + EXIF orientation)
 * and generates a companion .webp file when GD supports WebP so .htaccess serves WebP automatically.
 */
function erc_optimize_uploaded_image(string $filePath, int $maxWidth = 1600, int $maxHeight = 1600): void {
    @chmod($filePath, 0644);
    if (!extension_loaded('gd') || !is_file($filePath)) {
        return;
    }

    $info = @getimagesize($filePath);
    if (!$info || empty($info[0]) || empty($info[1])) {
        return;
    }

    list($width, $height, $type) = $info;
    // Guard memory on shared cPanel hosts for extremely high-megapixel images
    if (($width * $height) > 36000000) {
        return;
    }

    $src = null;
    if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
        $src = @imagecreatefromjpeg($filePath);
        if ($src && function_exists('exif_read_data')) {
            $exif = @exif_read_data($filePath);
            $orientation = (int)($exif['Orientation'] ?? 1);
            if ($orientation === 3) {
                $src = imagerotate($src, 180, 0);
            } elseif ($orientation === 6) {
                $src = imagerotate($src, -90, 0);
                $width = imagesx($src);
                $height = imagesy($src);
            } elseif ($orientation === 8) {
                $src = imagerotate($src, 90, 0);
                $width = imagesx($src);
                $height = imagesy($src);
            }
        }
    } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
        $src = @imagecreatefrompng($filePath);
    } elseif ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($filePath);
    }

    if (!$src) {
        return;
    }

    $scale = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1.0);
    $dst = $src;

    if ($scale < 1.0) {
        $newW = max(1, (int)round($width * $scale));
        $newH = max(1, (int)round($height * $scale));
        $resized = imagecreatetruecolor($newW, $newH);
        if ($resized) {
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
            }
            if (imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $width, $height)) {
                imagedestroy($src);
                $dst = $resized;
            } else {
                imagedestroy($resized);
            }
        }
    }

    if ($scale < 1.0) {
        if ($type === IMAGETYPE_JPEG && function_exists('imagejpeg')) {
            @imagejpeg($dst, $filePath, 84);
        } elseif ($type === IMAGETYPE_PNG && function_exists('imagepng')) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            @imagepng($dst, $filePath, 7);
        } elseif ($type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
            @imagewebp($dst, $filePath, 84);
        }
        @chmod($filePath, 0644);
    }

    // Generate companion .webp for .htaccess transparent negotiation
    if ($type !== IMAGETYPE_WEBP && function_exists('imagewebp')) {
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $filePath);
        if ($webpPath && $webpPath !== $filePath) {
            if ($type === IMAGETYPE_PNG && function_exists('imagepalettetotruecolor')) {
                @imagepalettetotruecolor($dst);
                imagealphablending($dst, true);
                imagesavealpha($dst, true);
            }
            @imagewebp($dst, $webpPath, 82);
            @chmod($webpPath, 0644);
        }
    }

    imagedestroy($dst);
}

/**
 * Safely deletes an image inside $allowedDirRel and its .webp sidecar if present.
 */
function erc_delete_image_with_webp(string $relPath, string $allowedDirRel): bool {
    $baseDir = realpath(__DIR__ . '/' . trim($allowedDirRel, '/\\'));
    if ($baseDir === false) {
        return false;
    }

    $candidate = __DIR__ . '/' . ltrim($relPath, '/\\');
    $realTarget = realpath($candidate);
    if ($realTarget === false) {
        return false;
    }

    $prefix = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($realTarget, $prefix) !== 0 || !is_file($realTarget)) {
        return false;
    }

    $deleted = @unlink($realTarget);
    $webpSibling = preg_replace('/\.(jpe?g|png)$/i', '.webp', $realTarget);
    if ($webpSibling && $webpSibling !== $realTarget && is_file($webpSibling)) {
        @unlink($webpSibling);
    }
    return $deleted;
}

/**
 * Geocodes a street address via Nominatim with US Census fallback (short timeout for admin saves).
 */
function erc_geocode_address(string $address): ?array {
    $cleanAddr = trim((string)preg_replace('/(?:Suite|Ste|Unit|Apt|#)\s*[\w-]+,?/i', '', $address));
    if ($cleanAddr === '') {
        return null;
    }
    $encoded = urlencode($cleanAddr);

    $nomCtx = stream_context_create([
        'http' => [
            'header' => "User-Agent: Lions2E2ERC-Admin/1.0\r\n",
            'timeout' => 4,
        ],
    ]);
    $nomResp = @file_get_contents("https://nominatim.openstreetmap.org/search?format=json&q={$encoded}&limit=1", false, $nomCtx);
    if (is_string($nomResp) && $nomResp !== '') {
        $nomData = json_decode($nomResp, true);
        if (!empty($nomData[0]['lat']) && !empty($nomData[0]['lon'])) {
            return [
                'lat' => (float)$nomData[0]['lat'],
                'lng' => (float)$nomData[0]['lon'],
            ];
        }
    }

    $cenCtx = stream_context_create(['http' => ['timeout' => 4]]);
    $cenResp = @file_get_contents(
        "https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?address={$encoded}&benchmark=Public_AR_Current&format=json",
        false,
        $cenCtx
    );
    if (is_string($cenResp) && $cenResp !== '') {
        $cenData = json_decode($cenResp, true);
        if (!empty($cenData['result']['addressMatches'][0]['coordinates'])) {
            $coords = $cenData['result']['addressMatches'][0]['coordinates'];
            return [
                'lat' => (float)$coords['y'],
                'lng' => (float)$coords['x'],
            ];
        }
    }

    return null;
}

/**
 * Renders a single Board Member row in the Admin Dashboard (shared by Current and Previous lists).
 */
function erc_render_board_member_row(array $member): void {
    $tag = ($member['tag'] ?? 'current') === 'previous' ? 'previous' : 'current';
    $rawImg = (string)($member['image'] ?? '');
    if ($rawImg === '' || $rawImg === 'images/board/placeholder.png') {
        $rawImg = 'images/placeholder-volunteer.svg';
    }
    $borderColor = $tag === 'previous' ? 'var(--text-dim)' : 'var(--accent)';
    $safeImg = htmlspecialchars($rawImg, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars((string)($member['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="form-grid board-row" style="margin-bottom: 2rem; align-items: start; border-bottom: 1px solid var(--glass-border); padding-bottom: 1.5rem;">
        <div style="display: flex; gap: 1.5rem; grid-column: span 2;">
            <img src="<?php echo $safeImg; ?>" alt="<?php echo $safeName; ?>" onerror="this.onerror=null;this.src='images/placeholder-volunteer.svg';" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid <?php echo $borderColor; ?>;">
            <div style="flex: 1;">
                <div class="form-grid">
                    <div>
                        <label>Name</label>
                        <input type="text" name="names[]" value="<?php echo $safeName; ?>" required>
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="tags[]">
                            <option value="current" <?php echo $tag === 'current' ? 'selected' : ''; ?>>Current Board Member</option>
                            <option value="previous" <?php echo $tag === 'previous' ? 'selected' : ''; ?>>Previous Board Member</option>
                        </select>
                    </div>
                    <div>
                        <label>Update Photo</label>
                        <input type="file" name="board_files[]" accept="image/*">
                        <input type="hidden" name="images[]" value="<?php echo $safeImg; ?>">
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; padding-top: 1.5rem;">
                <button type="button" class="btn btn-outline" style="color: var(--danger);" onclick="if(confirm('Remove this member?')) this.closest('.board-row').remove()"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php
}

// Retrieve flash notifications from PRG redirect
$message = (string)($_SESSION['flash_message'] ?? '');
$error = (string)($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

// Handle POST mutations with CSRF verification, atomic locking, and Post/Redirect/Get
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $postMessage = '';
    $postError = '';
    $redirectSection = 'dashboard';

    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $postError = 'The uploaded file is too large for the server to process. Please try a smaller file.';
        $action = 'error_too_large';
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        $postError = 'Session expired or invalid CSRF token. Please try again.';
        $action = 'error_csrf';
    } else {
        $action = (string)($_POST['action'] ?? '');
    }

    try {
        if ($action === 'save_event') {
            $redirectSection = 'events';
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $imagePath = trim((string)($_POST['existing_image'] ?? '')) ?: 'images/placeholder-event.svg';
            $uploadedNewImage = false;

            if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (!isValidImageUpload($_FILES['image'])) {
                    $postError = 'Invalid image upload for event. Allowed formats: JPG, PNG, GIF, WebP.';
                } elseif (($_FILES['image']['size'] ?? 0) > 10 * 1024 * 1024) {
                    $postError = 'Event image exceeds 10MB limit.';
                } else {
                    if (!is_dir(__DIR__ . '/images/events')) {
                        @mkdir(__DIR__ . '/images/events', 0755, true);
                    }
                    $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$_FILES['image']['name']));
                    $targetRel = 'images/events/' . time() . '_' . $cleanName;
                    $targetAbs = __DIR__ . '/' . $targetRel;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetAbs)) {
                        erc_optimize_uploaded_image($targetAbs, 1600, 1600);
                        $imagePath = $targetRel;
                        $uploadedNewImage = true;
                    } else {
                        $postError = 'Failed to save uploaded event image.';
                    }
                }
            }

            if ($postError === '') {
                $saved = erc_with_storage_lock(static function () use ($id, $imagePath, $uploadedNewImage) {
                    $eventsFile = __DIR__ . '/data/events.json';
                    $events = erc_load_json($eventsFile, []);
                    $signups = erc_load_json(__DIR__ . '/data/signups.json', []);

                    $newId = $id ?: (count($events) > 0 ? max(array_map('intval', array_column($events, 'id'))) + 1 : 1);
                    $peopleCount = isset($signups[(string)$newId]) && is_array($signups[(string)$newId])
                        ? count($signups[(string)$newId])
                        : 0;

                    $eventData = [
                        'id' => $newId,
                        'title' => trim((string)($_POST['title'] ?? '')),
                        'date' => trim((string)($_POST['date'] ?? '')),
                        'time' => trim((string)($_POST['time'] ?? '')),
                        'people' => $peopleCount,
                        'maxPeople' => max(1, (int)($_POST['maxPeople'] ?? 15)),
                        'location' => trim((string)($_POST['location'] ?? '')),
                        'image' => $imagePath,
                        'description' => trim((string)($_POST['description'] ?? '')),
                        'contact' => trim((string)($_POST['contact'] ?? '2e2erc1854@gmail.com')),
                    ];

                    $previousImage = null;
                    if ($id) {
                        $updated = false;
                        foreach ($events as &$e) {
                            if ((int)($e['id'] ?? 0) === $id) {
                                $previousImage = (string)($e['image'] ?? '');
                                $e = $eventData;
                                $updated = true;
                                break;
                            }
                        }
                        unset($e);
                        if (!$updated) {
                            $events[] = $eventData;
                        }
                    } else {
                        $events[] = $eventData;
                    }

                    if (!erc_save_json_atomic($eventsFile, array_values($events), 0644)) {
                        return false;
                    }

                    if ($uploadedNewImage && $previousImage && $previousImage !== $imagePath && strpos($previousImage, 'images/events/') === 0) {
                        $stillUsed = false;
                        foreach ($events as $ev) {
                            if (($ev['image'] ?? '') === $previousImage) {
                                $stillUsed = true;
                                break;
                            }
                        }
                        if (!$stillUsed) {
                            erc_delete_image_with_webp($previousImage, 'images/events');
                        }
                    }

                    return true;
                });

                if ($saved) {
                    $postMessage = 'Event saved successfully!';
                } else {
                    $postError = 'Failed to save event.';
                }
            }
        } elseif ($action === 'delete_event') {
            $redirectSection = 'events';
            $id = (int)($_POST['id'] ?? 0);
            erc_with_storage_lock(static function () use ($id) {
                $eventsFile = __DIR__ . '/data/events.json';
                $signupsFile = __DIR__ . '/data/signups.json';
                $events = erc_load_json($eventsFile, []);
                $signups = erc_load_json($signupsFile, []);

                $deletedImages = [];
                $remaining = [];
                foreach ($events as $e) {
                    if ((int)($e['id'] ?? 0) === $id) {
                        if (!empty($e['image'])) {
                            $deletedImages[] = (string)$e['image'];
                        }
                    } else {
                        $remaining[] = $e;
                    }
                }

                erc_save_json_atomic($eventsFile, $remaining, 0644);

                if (isset($signups[(string)$id])) {
                    unset($signups[(string)$id]);
                    erc_save_json_atomic($signupsFile, $signups, 0600);
                }

                $remainingImages = array_column($remaining, 'image');
                foreach ($deletedImages as $img) {
                    if (strpos($img, 'images/events/') === 0 && !in_array($img, $remainingImages, true)) {
                        erc_delete_image_with_webp($img, 'images/events');
                    }
                }
            });
            $postMessage = 'Event deleted.';
        } elseif ($action === 'save_location') {
            $redirectSection = 'locations';
            $index = (int)($_POST['index'] ?? -1);
            $locData = [
                'name' => trim((string)($_POST['name'] ?? '')),
                'address' => trim((string)($_POST['address'] ?? '')),
                'phone' => trim((string)($_POST['phone'] ?? '')),
            ];

            if ($locData['name'] === '' || $locData['address'] === '') {
                $postError = 'Location name and address are required.';
            } else {
                $existingCache = erc_load_json(__DIR__ . '/data/geo_cache.json', []);
                $needsGeocode = !isset($existingCache[$locData['address']]['lat']) || !is_numeric($existingCache[$locData['address']]['lat']);
                $geocoded = $needsGeocode ? erc_geocode_address($locData['address']) : null;

                $saved = erc_with_storage_lock(static function () use ($index, $locData, $needsGeocode, $geocoded) {
                    $locFile = __DIR__ . '/data/locations.json';
                    $locations = erc_load_json($locFile, []);

                    if ($index >= 0 && isset($locations[$index])) {
                        $locations[$index] = $locData;
                    } else {
                        $locations[] = $locData;
                    }

                    usort($locations, static function ($a, $b) {
                        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
                    });

                    if (!erc_save_json_atomic($locFile, array_values($locations), 0644)) {
                        return false;
                    }

                    if ($needsGeocode && $geocoded !== null) {
                        $cacheFile = __DIR__ . '/data/geo_cache.json';
                        $cache = erc_load_json($cacheFile, []);
                        $cache[$locData['address']] = [
                            'lat' => $geocoded['lat'],
                            'lng' => $geocoded['lng'],
                            'timestamp' => time(),
                        ];
                        erc_save_json_atomic($cacheFile, $cache, 0644);
                    }

                    return true;
                });

                if ($saved) {
                    $postMessage = 'Location saved!';
                } else {
                    $postError = 'Failed to save location.';
                }
            }
        } elseif ($action === 'delete_location') {
            $redirectSection = 'locations';
            $index = (int)($_POST['index'] ?? -1);
            erc_with_storage_lock(static function () use ($index) {
                $locFile = __DIR__ . '/data/locations.json';
                $locations = erc_load_json($locFile, []);
                if ($index >= 0 && isset($locations[$index])) {
                    array_splice($locations, $index, 1);
                    erc_save_json_atomic($locFile, array_values($locations), 0644);
                }
            });
            $postMessage = 'Location deleted.';
        } elseif ($action === 'save_board') {
            $redirectSection = 'board';
            if (!is_dir(__DIR__ . '/images/board')) {
                @mkdir(__DIR__ . '/images/board', 0755, true);
            }

            $board = [];
            $names = $_POST['names'] ?? [];
            if (is_array($names)) {
                $countNames = count($names);
                for ($i = 0; $i < $countNames; $i++) {
                    $memberName = trim((string)($names[$i] ?? ''));
                    if ($memberName === '') {
                        continue;
                    }

                    $imagePath = trim((string)($_POST['images'][$i] ?? ''));
                    if ($imagePath === '' || $imagePath === 'images/board/placeholder.png') {
                        $imagePath = 'images/placeholder-volunteer.svg';
                    }

                    if (isset($_FILES['board_files']['name'][$i]) && ($_FILES['board_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                        $fileArray = [
                            'name' => $_FILES['board_files']['name'][$i],
                            'type' => $_FILES['board_files']['type'][$i],
                            'tmp_name' => $_FILES['board_files']['tmp_name'][$i],
                            'error' => $_FILES['board_files']['error'][$i],
                            'size' => $_FILES['board_files']['size'][$i],
                        ];
                        if (isValidImageUpload($fileArray) && ($fileArray['size'] ?? 0) <= 10 * 1024 * 1024) {
                            $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$fileArray['name']));
                            $targetRel = 'images/board/' . time() . '_' . $i . '_' . $cleanName;
                            $targetAbs = __DIR__ . '/' . $targetRel;
                            if (move_uploaded_file($fileArray['tmp_name'], $targetAbs)) {
                                erc_optimize_uploaded_image($targetAbs, 400, 400);
                                $imagePath = $targetRel;
                            }
                        } else {
                            $postError = 'One or more board member photos were invalid or exceeded 10MB.';
                        }
                    }

                    $tag = in_array($_POST['tags'][$i] ?? '', ['current', 'previous'], true) ? $_POST['tags'][$i] : 'current';
                    $board[] = [
                        'name' => $memberName,
                        'image' => $imagePath,
                        'tag' => $tag,
                    ];
                }
            }

            $saved = erc_with_storage_lock(static function () use ($board) {
                $boardFile = __DIR__ . '/data/board.json';
                $oldBoard = erc_load_json($boardFile, []);
                $oldImages = array_column($oldBoard, 'image');

                if (!erc_save_json_atomic($boardFile, $board, 0644)) {
                    return false;
                }

                $newImages = array_column($board, 'image');
                foreach ($oldImages as $oldImg) {
                    if ($oldImg && !in_array($oldImg, $newImages, true) && strpos($oldImg, 'images/board/') === 0 && strpos($oldImg, 'placeholder') === false) {
                        erc_delete_image_with_webp($oldImg, 'images/board');
                    }
                }
                return true;
            });

            if ($saved && $postError === '') {
                $postMessage = 'Board updated!';
            } elseif (!$saved) {
                $postError = 'Failed to save board members.';
            }
        } elseif ($action === 'delete_signup') {
            $redirectSection = 'signups';
            $eventId = (string)($_POST['event_id'] ?? '');
            $index = (int)($_POST['index'] ?? -1);

            $removed = erc_with_storage_lock(static function () use ($eventId, $index) {
                $signupsFile = __DIR__ . '/data/signups.json';
                $eventsFile = __DIR__ . '/data/events.json';
                $signups = erc_load_json($signupsFile, []);
                if (!isset($signups[$eventId][$index])) {
                    return false;
                }

                array_splice($signups[$eventId], $index, 1);
                $remainingCount = count($signups[$eventId]);
                if ($remainingCount === 0) {
                    unset($signups[$eventId]);
                }

                if (!erc_save_json_atomic($signupsFile, $signups, 0600)) {
                    return false;
                }

                $events = erc_load_json($eventsFile, []);
                foreach ($events as &$ev) {
                    if ((string)($ev['id'] ?? '') === $eventId) {
                        $ev['people'] = $remainingCount;
                        break;
                    }
                }
                unset($ev);
                erc_save_json_atomic($eventsFile, $events, 0644);
                return true;
            });

            if ($removed) {
                $postMessage = 'Signup removed.';
            } else {
                $postError = 'Signup entry could not be found.';
            }
        } elseif ($action === 'upload_gallery') {
            $redirectSection = 'gallery';
            if (!is_dir(__DIR__ . '/gallery')) {
                @mkdir(__DIR__ . '/gallery', 0755, true);
            }

            if (isset($_FILES['gallery_photos']) && is_array($_FILES['gallery_photos']['name'])) {
                $files = $_FILES['gallery_photos'];
                $count = 0;
                $total = count($files['name']);

                for ($i = 0; $i < $total; $i++) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $fileArray = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    if (!isValidImageUpload($fileArray)) {
                        $postError = 'One or more gallery images were invalid.';
                        continue;
                    }
                    if (($fileArray['size'] ?? 0) > 10 * 1024 * 1024) {
                        $postError = 'One or more images exceeded the 10MB limit.';
                        continue;
                    }

                    $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$fileArray['name']));
                    $targetRel = 'gallery/' . time() . '_' . $i . '_' . $cleanName;
                    $targetAbs = __DIR__ . '/' . $targetRel;
                    if (move_uploaded_file($fileArray['tmp_name'], $targetAbs)) {
                        erc_optimize_uploaded_image($targetAbs, 1600, 1600);
                        $count++;
                    }
                }

                if (is_file(__DIR__ . '/data/gallery-cache.json')) {
                    @unlink(__DIR__ . '/data/gallery-cache.json');
                }
                if ($count > 0) {
                    $postMessage = "$count gallery photo(s) uploaded!";
                } elseif ($postError === '') {
                    $postError = 'No valid photos were uploaded.';
                }
            }
        } elseif ($action === 'delete_gallery') {
            $redirectSection = 'gallery';
            $photo = (string)($_POST['photo'] ?? '');
            if (erc_delete_image_with_webp($photo, 'gallery')) {
                if (is_file(__DIR__ . '/data/gallery-cache.json')) {
                    @unlink(__DIR__ . '/data/gallery-cache.json');
                }
                $postMessage = 'Photo removed from gallery.';
            } else {
                $postError = 'Unable to remove selected gallery photo.';
            }
        } elseif ($action === 'save_partners') {
            $redirectSection = 'other';
            $rawPartners = (string)($_POST['partners'] ?? '');
            $partnersList = array_values(array_filter(array_map('trim', explode("\n", $rawPartners)), static function ($p) {
                return $p !== '';
            }));
            $saved = erc_with_storage_lock(static function () use ($partnersList) {
                return erc_save_json_atomic(__DIR__ . '/data/partners.json', $partnersList, 0644);
            });
            if ($saved) {
                $postMessage = 'Partners updated!';
            } else {
                $postError = 'Failed to update partners.';
            }
        } elseif ($action === 'save_testimonials') {
            $redirectSection = 'testimonials';
            $testimonialsList = [];
            $texts = $_POST['texts'] ?? [];
            if (is_array($texts)) {
                $countTexts = count($texts);
                for ($i = 0; $i < $countTexts; $i++) {
                    $tText = trim((string)($texts[$i] ?? ''));
                    if ($tText !== '') {
                        $testimonialsList[] = [
                            'text' => $tText,
                            'author' => trim((string)($_POST['authors'][$i] ?? '')),
                            'organization' => trim((string)($_POST['orgs'][$i] ?? '')),
                        ];
                    }
                }
            }
            $saved = erc_with_storage_lock(static function () use ($testimonialsList) {
                return erc_save_json_atomic(__DIR__ . '/data/testimonials.json', $testimonialsList, 0644);
            });
            if ($saved) {
                $postMessage = 'Testimonials updated!';
            } else {
                $postError = 'Failed to update testimonials.';
            }
        } elseif ($action === 'save_config') {
            $redirectSection = 'other';
            $email = trim((string)($_POST['global_email'] ?? ''));
            $phone = trim((string)($_POST['global_phone'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || strlen($phone) > 40) {
                $postError = 'Please enter a valid global contact email and phone number.';
            } else {
                $saved = erc_with_storage_lock(static function () use ($email, $phone) {
                    return erc_save_json_atomic(__DIR__ . '/data/site-config.json', [
                        'email' => $email,
                        'phone' => $phone,
                    ], 0600);
                });
                if ($saved) {
                    $postMessage = 'Global settings updated!';
                } else {
                    $postError = 'Failed to update global settings.';
                }
            }
        } elseif ($action === 'change_admin_password') {
            $redirectSection = 'other';
            $newUsername = trim((string)($_POST['admin_username'] ?? $authConfig['user']));
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($newUsername === '' || strlen($newUsername) > 64) {
                $postError = 'Please provide a valid admin username (1-64 characters).';
            } elseif (!erc_verify_admin_credentials((string)$authConfig['user'], $currentPassword, $authConfig)) {
                $postError = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $postError = 'New password must be at least 8 characters long.';
            } elseif (!hash_equals($newPassword, $confirmPassword)) {
                $postError = 'New password and confirmation do not match.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $saved = erc_with_storage_lock(static function () use ($newUsername, $hash) {
                    return erc_save_json_atomic(__DIR__ . '/data/admin-auth.json', [
                        'user' => $newUsername,
                        'password_hash' => $hash,
                        'updated_at' => date('c'),
                    ], 0600);
                });
                if ($saved) {
                    $postMessage = 'Admin credentials updated! Your browser may prompt you to sign in again with the new password.';
                } else {
                    $postError = 'Failed to save new admin credentials.';
                }
            }
        } elseif ($action === 'upload_form') {
            $redirectSection = 'forms';
            $targetFilename = (string)($_POST['target_filename'] ?? '');
            $cleanFilename = basename($targetFilename);

            if ($cleanFilename === '' || $cleanFilename !== $targetFilename || strtolower(pathinfo($cleanFilename, PATHINFO_EXTENSION)) !== 'pdf') {
                $postError = 'Invalid target PDF filename.';
            } elseif (!isset($_FILES['new_form']) || ($_FILES['new_form']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $postError = 'Upload error or no PDF file provided.';
            } elseif (($_FILES['new_form']['size'] ?? 0) > 20 * 1024 * 1024) {
                $postError = 'PDF file exceeds the 20MB limit.';
            } else {
                $tmpName = $_FILES['new_form']['tmp_name'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $tmpName) : false;
                if ($finfo) {
                    finfo_close($finfo);
                }
                $fh = @fopen($tmpName, 'rb');
                $magic = $fh ? fread($fh, 5) : '';
                if ($fh) {
                    fclose($fh);
                }

                if ($mime !== 'application/pdf' || $magic !== '%PDF-') {
                    $postError = 'Invalid file upload. Only valid PDF documents are allowed.';
                } else {
                    if (!is_dir(__DIR__ . '/files')) {
                        @mkdir(__DIR__ . '/files', 0755, true);
                    }
                    $targetPath = __DIR__ . '/files/' . $cleanFilename;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        @chmod($targetPath, 0644);
                        $postMessage = "Form '$cleanFilename' successfully updated!";
                    } else {
                        $postError = 'Failed to save the uploaded PDF form.';
                    }
                }
            }
        }
    } catch (Throwable $e) {
        $postError = 'An unexpected server error occurred while processing your request.';
    }

    if ($postMessage !== '') {
        $_SESSION['flash_message'] = $postMessage;
    }
    if ($postError !== '') {
        $_SESSION['flash_error'] = $postError;
    }

    header('Location: admin-view.php#' . rawurlencode($redirectSection), true, 303);
    exit;
}

// Load current data for display (read-only on GET; no file mutations during GET)
$events = erc_load_json(__DIR__ . '/data/events.json', []);
$signups = erc_load_json(__DIR__ . '/data/signups.json', []);

foreach ($events as &$ev) {
    $evKey = (string)($ev['id'] ?? '');
    if (isset($signups[$evKey]) && is_array($signups[$evKey])) {
        $ev['people'] = count($signups[$evKey]);
    } else {
        $ev['people'] = (int)($ev['people'] ?? 0);
    }
}
unset($ev);

$locations = erc_load_json(__DIR__ . '/data/locations.json', []);
$board = erc_load_json(__DIR__ . '/data/board.json', []);
$partners = erc_load_json(__DIR__ . '/data/partners.json', []);
$testimonials = erc_load_json(__DIR__ . '/data/testimonials.json', []);
$gallery = erc_scan_gallery_images();

$current_email = $GLOBAL_EMAIL ?? '2e2erc1854@gmail.com';
$current_phone = $GLOBAL_PHONE ?? '(817) 710-5403';
$usingDefaultPassword = !empty($authConfig['is_legacy_default']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERC Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            color-scheme: dark;
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
            --warning: #f59e0b;
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
            min-width: 0;
            padding: 2rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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
            animation: fadeIn 0.3s ease;
        }
        .section.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
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
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        input:focus-visible, select:focus-visible, textarea:focus-visible {
            border-color: var(--accent);
            outline: 2px solid var(--accent);
            outline-offset: 1px;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
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

        :root {
            scrollbar-width: auto;
            scrollbar-color: var(--primary) var(--bg-dark);
        }

        .phone-cell { white-space: nowrap; }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: slideDown 0.3s ease;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); color: #6ee7b7; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger); color: #fca5a5; }
        .alert-warning { background: rgba(245, 158, 11, 0.18); border: 1px solid var(--warning); color: #fde68a; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }

        @keyframes slideDown {
            from { transform: translateY(-12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 2rem 0.5rem; }
            .sidebar .logo span, .sidebar .nav-links li a span { display: none; }
            .sidebar .nav-links li a { justify-content: center; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 1.25rem; }
            .card { padding: 1.25rem; }
            .form-grid { grid-template-columns: 1fr; }
            .form-full { grid-column: span 1; }
            .board-row > div { grid-column: span 1 !important; flex-wrap: wrap; }
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
        .gallery-item:hover .delete-btn,
        .gallery-item .delete-btn:focus-visible { opacity: 1; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-glasses" aria-hidden="true"></i>
            <span>ERC ADMIN</span>
        </div>
        <ul class="nav-links">
            <li><a href="#dashboard" data-target="dashboard" class="active"><i class="fas fa-chart-line" aria-hidden="true"></i> <span>Dashboard</span></a></li>
            <li><a href="#signups" data-target="signups"><i class="fas fa-users" aria-hidden="true"></i> <span>Signups</span></a></li>
            <li><a href="#events" data-target="events"><i class="fas fa-calendar-alt" aria-hidden="true"></i> <span>Events</span></a></li>
            <li><a href="#locations" data-target="locations"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <span>Locations</span></a></li>
            <li><a href="#gallery" data-target="gallery"><i class="fas fa-images" aria-hidden="true"></i> <span>Gallery</span></a></li>
            <li><a href="#forms" data-target="forms"><i class="fas fa-file-pdf" aria-hidden="true"></i> <span>Files</span></a></li>
            <li><a href="#board" data-target="board"><i class="fas fa-user-tie" aria-hidden="true"></i> <span>Board Members</span></a></li>
            <li><a href="#testimonials" data-target="testimonials"><i class="fas fa-comment-dots" aria-hidden="true"></i> <span>Testimonials</span></a></li>
            <li><a href="#other" data-target="other"><i class="fas fa-cogs" aria-hidden="true"></i> <span>Other Info</span></a></li>
        </ul>
        <div style="margin-top: auto;">
            <a href="index.html" style="color: var(--text-dim); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-external-link-alt" aria-hidden="true"></i> View Site</a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <div>
                <h1 id="sectionHeading">Dashboard</h1>
            </div>
            <div class="user-info">
                <span class="badge" style="background: var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem;">
                    Logged in as <?php echo htmlspecialchars((string)$authConfig['user'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        </header>

        <?php if ($usingDefaultPassword): ?>
            <div class="alert alert-warning">
                <span><i class="fas fa-shield-alt" aria-hidden="true"></i> <strong>Security Recommendation:</strong> You are currently using the default admin password. Set a custom admin password in <strong>Other Info</strong>.</span>
                <button type="button" class="btn btn-accent" onclick="activateAdminSection('other')"><i class="fas fa-key" aria-hidden="true"></i> Change Password</button>
            </div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
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
                        foreach ($signups as $s) {
                            if (is_array($s)) {
                                $totalSignups += count($s);
                            }
                        }
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
                    <button type="button" class="btn btn-outline" onclick="activateAdminSection('events')"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Manage Events</button>
                </div>

                <?php
                    $sortedEvents = $events;
                    usort($sortedEvents, static function ($a, $b) {
                        return strtotime((string)($a['date'] ?? '')) <=> strtotime((string)($b['date'] ?? ''));
                    });
                    $upcoming = array_slice(array_values(array_filter($sortedEvents, static function ($e) {
                        return strtotime((string)($e['date'] ?? '')) >= strtotime('today');
                    })), 0, 5);
                ?>

                <?php if (count($upcoming) === 0): ?>
                    <p style="color: var(--text-dim);">No upcoming events scheduled.</p>
                <?php else: ?>
                    <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Event Date</th>
                                <th scope="col">Title</th>
                                <th scope="col">Volunteers</th>
                                <th scope="col">Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming as $e): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($e['date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><strong><?php echo htmlspecialchars((string)($e['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td>
                                        <?php if ((int)$e['people'] >= (int)$e['maxPeople']): ?>
                                            <span style="color: var(--danger);"><i class="fas fa-user-check" aria-hidden="true"></i> FULL (<?php echo (int)$e['maxPeople']; ?>/<?php echo (int)$e['maxPeople']; ?>)</span>
                                        <?php else: ?>
                                            <span style="color: var(--accent);"><i class="fas fa-users" aria-hidden="true"></i> <?php echo (int)$e['people']; ?>/<?php echo (int)$e['maxPeople']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)($e['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SIGNUPS SECTION -->
        <section id="signups" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Volunteer Signups</h2>
                </div>
                <?php
                $hasAnySignups = false;
                foreach ($events as $event):
                    $eventSignups = $signups[(string)$event['id']] ?? [];
                    if (is_array($eventSignups) && count($eventSignups) > 0):
                        $hasAnySignups = true;
                ?>
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--accent); margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                            <?php echo htmlspecialchars((string)$event['title'], ENT_QUOTES, 'UTF-8'); ?>
                            <small style="color: var(--text-dim); font-weight: normal; font-size: 0.9rem;">(<?php echo htmlspecialchars((string)$event['date'], ENT_QUOTES, 'UTF-8'); ?>)</small>
                        </h3>
                        <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Phone</th>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventSignups as $idx => $person): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)($person['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars((string)($person['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="color: var(--accent); text-decoration: none;"><?php echo htmlspecialchars((string)($person['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
                                        <td class="phone-cell"><?php echo htmlspecialchars(!empty($person['phone']) ? (string)$person['phone'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)($person['timestamp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <form method="POST" action="admin-view.php" onsubmit="return confirm('Remove this signup?');" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_signup">
                                                <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
                                                <input type="hidden" name="index" value="<?php echo (int)$idx; ?>">
                                                <button type="submit" class="btn btn-outline" aria-label="Remove signup" style="padding: 0.4rem 0.8rem; color: var(--danger);"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                <?php
                    endif;
                endforeach;
                if (!$hasAnySignups):
                ?>
                    <p style="color: var(--text-dim);">No volunteer signups recorded yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- EVENTS SECTION -->
        <section id="events" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Manage Events</h2>
                    <button type="button" class="btn btn-accent" onclick="showEventForm()"><i class="fas fa-plus" aria-hidden="true"></i> New Event</button>
                </div>

                <div id="eventForm" style="display:none; margin-bottom: 3rem; background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; border: 1px solid var(--primary);">
                    <h3 id="formTitle">Add New Event</h3>
                    <form action="admin-view.php" method="POST" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="save_event">
                        <input type="hidden" name="id" id="event_id">
                        <input type="hidden" name="existing_image" id="event_existing_image">

                        <div class="form-grid">
                            <div>
                                <label for="event_title">Title</label>
                                <input type="text" name="title" id="event_title" required>
                            </div>
                            <div>
                                <label for="event_image_input">Image (Upload new or leave empty to keep current)</label>
                                <input type="file" name="image" id="event_image_input" accept="image/*">
                            </div>
                            <div>
                                <label for="event_date">Date</label>
                                <input type="date" name="date" id="event_date" required>
                            </div>
                            <div>
                                <label for="event_time">Time (e.g. 9:00 AM - 12:00 PM)</label>
                                <input type="text" name="time" id="event_time" required>
                            </div>
                            <div>
                                <label for="event_maxPeople">Max Volunteers</label>
                                <input type="number" name="maxPeople" id="event_maxPeople" min="1" required>
                            </div>
                            <div class="form-full">
                                <label for="event_location">Location</label>
                                <input type="text" name="location" id="event_location" required>
                            </div>
                            <div class="form-full">
                                <label for="event_contact">Contact Email</label>
                                <input type="email" name="contact" id="event_contact" value="<?php echo htmlspecialchars($current_email, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-full">
                                <label for="event_description">Description</label>
                                <textarea name="description" id="event_description" rows="3"></textarea>
                            </div>
                        </div>
                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Event</button>
                            <button type="button" class="btn btn-outline" onclick="hideEventForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Image</th>
                            <th scope="col">Title</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Volunteers</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars((string)($e['image'] ?? 'images/placeholder-event.svg'), ENT_QUOTES, 'UTF-8'); ?>" class="image-preview" alt="Event preview" onerror="this.onerror=null;this.src='images/placeholder-event.svg';"></td>
                                <td><strong><?php echo htmlspecialchars((string)($e['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars((string)($e['date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($e['time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)$e['people']; ?>/<?php echo (int)$e['maxPeople']; ?></td>
                                <td class="action-btns">
                                    <button type="button" class="btn btn-outline" title="Edit Event" aria-label="Edit Event" onclick='editEvent(<?php echo htmlspecialchars((string)json_encode($e), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-edit" aria-hidden="true"></i></button>
                                    <button type="button" class="btn btn-outline" title="Duplicate Event" aria-label="Duplicate Event" onclick='duplicateEvent(<?php echo htmlspecialchars((string)json_encode($e), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-copy" aria-hidden="true"></i></button>
                                    <form method="POST" action="admin-view.php" style="display:inline;" onsubmit="return confirm('Delete this event?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="delete_event">
                                        <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                                        <button type="submit" class="btn btn-outline" title="Delete Event" aria-label="Delete Event" style="color: var(--danger);"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </section>

        <!-- LOCATIONS SECTION -->
        <section id="locations" class="section">
            <div class="card">
                <div class="card-header">
                    <h2>Drop-off Locations</h2>
                    <button type="button" class="btn btn-accent" onclick="showLocationForm()"><i class="fas fa-plus" aria-hidden="true"></i> Add Location</button>
                </div>

                <div id="locationForm" style="display:none; margin-bottom: 3rem; background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; border: 1px solid var(--primary);">
                    <h3 id="locFormTitle">Add New Location</h3>
                    <form action="admin-view.php" method="POST" style="margin-top: 1.5rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="save_location">
                        <input type="hidden" name="index" id="loc_index" value="-1">
                        <div class="form-grid">
                            <div class="form-full">
                                <label for="loc_name">Name</label>
                                <input type="text" name="name" id="loc_name" required>
                            </div>
                            <div class="form-full">
                                <label for="loc_address">Address</label>
                                <input type="text" name="address" id="loc_address" required>
                            </div>
                            <div class="form-full">
                                <label for="loc_phone">Phone</label>
                                <input type="text" name="phone" id="loc_phone">
                            </div>
                        </div>
                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Location</button>
                            <button type="button" class="btn btn-outline" onclick="hideLocationForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 25%;">Name</th>
                                <th scope="col" style="width: 45%;">Address</th>
                                <th scope="col" style="width: 15%;">Phone</th>
                                <th scope="col" style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $idx => $l): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars((string)($l['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo htmlspecialchars((string)($l['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="phone-cell"><?php echo htmlspecialchars((string)($l['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="action-btns">
                                        <button type="button" class="btn btn-outline" title="Edit Location" aria-label="Edit Location" onclick='editLocation(<?php echo (int)$idx; ?>, <?php echo htmlspecialchars((string)json_encode($l), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-edit" aria-hidden="true"></i></button>
                                        <form method="POST" action="admin-view.php" style="display:inline;" onsubmit="return confirm('Delete this location?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_location">
                                            <input type="hidden" name="index" value="<?php echo (int)$idx; ?>">
                                            <button type="submit" class="btn btn-outline" title="Delete Location" aria-label="Delete Location" style="color: var(--danger);"><i class="fas fa-trash" aria-hidden="true"></i></button>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="upload_gallery">
                    <label for="gallery_photos_input">Upload new photos (Select multiple — auto-optimized with WebP generation)</label>
                    <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem;">
                        <input type="file" name="gallery_photos[]" id="gallery_photos_input" multiple accept="image/*" required>
                        <button type="submit" class="btn btn-accent"><i class="fas fa-upload" aria-hidden="true"></i> Upload</button>
                    </div>
                </form>

                <div class="gallery-grid">
                    <?php foreach ($gallery as $photo): ?>
                        <div class="gallery-item" title="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>" alt="Gallery photo" loading="lazy" decoding="async">
                            <form method="POST" action="admin-view.php" onsubmit="return confirm('Remove this photo from gallery?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="delete_gallery">
                                <input type="hidden" name="photo" value="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="delete-btn" title="Delete" aria-label="Delete photo"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
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
                    <h2>Manage Files</h2>
                </div>
                <p style="margin-bottom: 2rem; color: var(--text-dim);">Upload a new file to replace an existing PDF. The original filename will be preserved.</p>

                <?php
                $filesDir = __DIR__ . '/files';
                if (!is_dir($filesDir)) {
                    @mkdir($filesDir, 0755, true);
                }

                $indexHtml = is_file(__DIR__ . '/index.html') ? (string)file_get_contents(__DIR__ . '/index.html') : '';
                $allFiles = is_dir($filesDir) ? array_diff(scandir($filesDir), ['.', '..', 'README.md']) : [];

                $linkedFiles = [];
                $unlinkedFiles = [];
                foreach ($allFiles as $f) {
                    if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf') {
                        if (strpos($indexHtml, 'files/' . $f) !== false) {
                            $linkedFiles[] = $f;
                        } else {
                            $unlinkedFiles[] = $f;
                        }
                    }
                }
                sort($linkedFiles);
                sort($unlinkedFiles);
                $formFiles = array_merge($linkedFiles, $unlinkedFiles);

                foreach ($formFiles as $formFile):
                    $isLinked = strpos($indexHtml, 'files/' . $formFile) !== false;
                ?>
                    <div style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid <?php echo $isLinked ? 'var(--success)' : '#f59e0b'; ?>; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                <h3 style="margin: 0; color: var(--accent);"><i class="fas fa-file-pdf" aria-hidden="true"></i> <?php echo htmlspecialchars($formFile, ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if ($isLinked): ?>
                                    <span style="background: rgba(16,185,129,0.15); color: #6ee7b7; border: 1px solid var(--success); border-radius: 20px; padding: 2px 10px; font-size: 0.78rem; font-weight: 600; white-space: nowrap;">
                                        <i class="fas fa-check-circle" aria-hidden="true"></i> Live on Site
                                    </span>
                                <?php else: ?>
                                    <span style="background: rgba(245,158,11,0.15); color: #fcd34d; border: 1px solid #f59e0b; border-radius: 20px; padding: 2px 10px; font-size: 0.78rem; font-weight: 600; white-space: nowrap;">
                                        <i class="fas fa-eye-slash" aria-hidden="true"></i> Not Displayed
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="files/<?php echo rawurlencode($formFile); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--text-dim); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-external-link-alt" aria-hidden="true"></i> View Current File</a>
                        </div>
                        <form action="admin-view.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="upload_form">
                            <input type="hidden" name="target_filename" value="<?php echo htmlspecialchars($formFile, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="file" name="new_form" accept=".pdf,application/pdf" required aria-label="Replacement PDF for <?php echo htmlspecialchars($formFile, ENT_QUOTES, 'UTF-8'); ?>" style="width: auto;">
                            <button type="submit" class="btn btn-accent"><i class="fas fa-upload" aria-hidden="true"></i> Replace</button>
                        </form>
                    </div>
                <?php endforeach;
                if (empty($formFiles)): ?>
                    <p style="color: var(--text-dim);">No PDF files found in the files directory.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- OTHER INFO SECTION -->
        <section id="other" class="section">
            <div class="card">
                <h2>Global Settings</h2>
                <p style="margin-bottom: 1.25rem; color: var(--text-dim); font-size: 0.9rem;">Saved persistently in <code>data/site-config.json</code> so updates are preserved across website deployments.</p>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="save_config">
                    <div class="form-grid">
                        <div>
                            <label for="global_email">Global Contact Email</label>
                            <input type="email" id="global_email" name="global_email" value="<?php echo htmlspecialchars($current_email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div>
                            <label for="global_phone">Global Contact Phone</label>
                            <input type="text" id="global_phone" name="global_phone" value="<?php echo htmlspecialchars($current_phone, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Update Global Settings</button>
                </form>
            </div>

            <div class="card">
                <h2>Partners</h2>
                <p style="margin-bottom: 1rem; color: var(--text-dim);">One partner name per line.</p>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="save_partners">
                    <label for="partners_textarea" style="display:none;">Partner List</label>
                    <textarea id="partners_textarea" name="partners" rows="10"><?php echo htmlspecialchars(implode("\n", $partners), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Update Partners</button>
                </form>
            </div>

            <div class="card">
                <h2>Admin Credentials</h2>
                <p style="margin-bottom: 1.25rem; color: var(--text-dim); font-size: 0.9rem;">Stores a secure bcrypt password hash in <code>data/admin-auth.json</code> (never overwritten by Git deployments).</p>
                <form action="admin-view.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="change_admin_password">
                    <div class="form-grid">
                        <div>
                            <label for="admin_username">Admin Username</label>
                            <input type="text" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars((string)$authConfig['user'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required>
                        </div>
                        <div>
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div>
                            <label for="new_password">New Password (minimum 8 characters)</label>
                            <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div>
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent" style="margin-top: 1rem;"><i class="fas fa-key" aria-hidden="true"></i> Update Admin Password</button>
                </form>
            </div>
        </section>

        <!-- BOARD MEMBERS SECTION -->
        <section id="board" class="section">
            <div class="card">
                <h2>Board Members</h2>
                <p style="color: var(--text-dim); margin-bottom: 1.5rem; font-size: 0.9rem;">Use the <strong>Category</strong> dropdown on each member to assign them to <em>Current</em> or <em>Previous</em> Board Members. Changes take effect when you click <strong>Save All Changes</strong>.</p>
                <form action="admin-view.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="save_board">

                    <!-- CURRENT BOARD MEMBERS -->
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--accent); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                            <i class="fas fa-user-tie" aria-hidden="true"></i> Current Board Members
                        </h3>
                        <div id="board-container-current" style="margin-top: 1rem;">
                            <?php
                            foreach ($board as $b) {
                                if (($b['tag'] ?? 'current') === 'current') {
                                    erc_render_board_member_row($b);
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- PREVIOUS BOARD MEMBERS -->
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--text-dim); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                            <i class="fas fa-history" aria-hidden="true"></i> Previous Board Members
                        </h3>
                        <div id="board-container-previous">
                            <?php
                            foreach ($board as $b) {
                                if (($b['tag'] ?? 'current') === 'previous') {
                                    erc_render_board_member_row($b);
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline" onclick="addBoardMember('current')" style="margin-bottom: 0.75rem; margin-right: 1rem;"><i class="fas fa-plus" aria-hidden="true"></i> Add Current Member</button>
                    <button type="button" class="btn btn-outline" onclick="addBoardMember('previous')" style="margin-bottom: 1.5rem;"><i class="fas fa-plus" aria-hidden="true"></i> Add Previous Member</button>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="save_testimonials">
                    <div id="testimonial-container" style="margin-top: 1.5rem;">
                        <?php foreach ($testimonials as $t): ?>
                            <div class="testimonial-row" style="background: rgba(0,0,0,0.1); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--glass-border); position: relative;">
                                <button type="button" aria-label="Remove testimonial" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: var(--danger); cursor: pointer;" onclick="if(confirm('Remove this testimonial?')) this.closest('.testimonial-row').remove()"><i class="fas fa-times-circle fa-lg" aria-hidden="true"></i></button>
                                <label>Testimonial Text</label>
                                <textarea name="texts[]" rows="3" style="margin-bottom: 1rem;"><?php echo htmlspecialchars((string)($t['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="form-grid">
                                    <div>
                                        <label>Author Name</label>
                                        <input type="text" name="authors[]" value="<?php echo htmlspecialchars((string)($t['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div>
                                        <label>Organization / Role</label>
                                        <input type="text" name="orgs[]" value="<?php echo htmlspecialchars((string)($t['organization'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline" onclick="addTestimonial()" style="margin-bottom: 1.5rem;"><i class="fas fa-plus" aria-hidden="true"></i> Add New Testimonial</button>
                    <br>
                    <button type="submit" class="btn btn-primary">Update All Testimonials</button>
                </form>
            </div>
        </section>

    </main>

    <script>
        const SECTION_TITLES = {
            dashboard: 'Dashboard',
            signups: 'Signups',
            events: 'Events',
            locations: 'Locations',
            gallery: 'Gallery',
            forms: 'Files',
            board: 'Board Members',
            testimonials: 'Testimonials',
            other: 'Other Info'
        };

        function activateAdminSection(target, updateHash = true) {
            if (!SECTION_TITLES[target]) target = 'dashboard';

            document.querySelectorAll('.nav-links a').forEach(l => {
                l.classList.toggle('active', l.getAttribute('data-target') === target);
            });
            document.querySelectorAll('.section').forEach(s => {
                s.classList.toggle('active', s.id === target);
            });

            const heading = document.getElementById('sectionHeading');
            if (heading) heading.textContent = SECTION_TITLES[target];

            if (updateHash) {
                history.replaceState(null, '', '#' + target);
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                activateAdminSection(link.getAttribute('data-target'), true);
            });
        });

        // Restore active tab from URL hash (supports Post/Redirect/Get & browser refresh)
        const initialSection = window.location.hash.slice(1);
        if (initialSection && SECTION_TITLES[initialSection]) {
            activateAdminSection(initialSection, false);
        }

        // Event Management
        function showEventForm() {
            const formWrap = document.getElementById('eventForm');
            formWrap.style.display = 'block';
            document.querySelector('#eventForm form').reset();
            document.getElementById('formTitle').textContent = 'Add New Event';
            document.getElementById('event_id').value = '';
            document.getElementById('event_existing_image').value = 'images/placeholder-event.svg';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function hideEventForm() { document.getElementById('eventForm').style.display = 'none'; }

        function editEvent(event) {
            showEventForm();
            document.getElementById('formTitle').textContent = 'Edit Event: ' + event.title;
            document.getElementById('event_id').value = event.id;
            document.getElementById('event_existing_image').value = event.image || 'images/placeholder-event.svg';
            document.getElementById('event_title').value = event.title || '';
            document.getElementById('event_date').value = event.date || '';
            document.getElementById('event_time').value = event.time || '';
            document.getElementById('event_maxPeople').value = event.maxPeople || 15;
            document.getElementById('event_location').value = event.location || '';
            document.getElementById('event_contact').value = event.contact || '';
            document.getElementById('event_description').value = event.description || '';
        }

        function duplicateEvent(event) {
            showEventForm();
            document.getElementById('formTitle').textContent = 'Duplicate Event: ' + event.title;
            document.getElementById('event_id').value = '';
            document.getElementById('event_existing_image').value = event.image || 'images/placeholder-event.svg';
            document.getElementById('event_title').value = 'Copy of ' + (event.title || '');
            document.getElementById('event_date').value = '';
            document.getElementById('event_time').value = event.time || '';
            document.getElementById('event_maxPeople').value = event.maxPeople || 15;
            document.getElementById('event_location').value = event.location || '';
            document.getElementById('event_contact').value = event.contact || '';
            document.getElementById('event_description').value = event.description || '';
        }

        // Location Management
        function showLocationForm() {
            document.getElementById('locationForm').style.display = 'block';
            document.querySelector('#locationForm form').reset();
            document.getElementById('locFormTitle').textContent = 'Add New Location';
            document.getElementById('loc_index').value = '-1';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function hideLocationForm() { document.getElementById('locationForm').style.display = 'none'; }

        function editLocation(index, loc) {
            showLocationForm();
            document.getElementById('locFormTitle').textContent = 'Edit Location: ' + (loc.name || '');
            document.getElementById('loc_index').value = index;
            document.getElementById('loc_name').value = loc.name || '';
            document.getElementById('loc_address').value = loc.address || '';
            document.getElementById('loc_phone').value = loc.phone || '';
        }

        // Dynamic Row Builders
        function addBoardMember(tag) {
            tag = tag === 'previous' ? 'previous' : 'current';
            const containerId = tag === 'previous' ? 'board-container-previous' : 'board-container-current';
            const container = document.getElementById(containerId);
            const div = document.createElement('div');
            div.className = 'form-grid board-row';
            div.style.marginBottom = '2rem';
            div.style.borderBottom = '1px solid var(--glass-border)';
            div.style.paddingBottom = '1.5rem';
            div.style.alignItems = 'start';
            const accentColor = tag === 'previous' ? 'var(--text-dim)' : 'var(--accent)';
            div.innerHTML = `
                <div style="display: flex; gap: 1.5rem; grid-column: span 2;">
                    <img src="images/placeholder-volunteer.svg" alt="New board member" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid ${accentColor};">
                    <div style="flex: 1;">
                        <div class="form-grid">
                            <div>
                                <label>Name</label>
                                <input type="text" name="names[]" required>
                            </div>
                            <div>
                                <label>Category</label>
                                <select name="tags[]">
                                    <option value="current" ${tag === 'current' ? 'selected' : ''}>Current Board Member</option>
                                    <option value="previous" ${tag === 'previous' ? 'selected' : ''}>Previous Board Member</option>
                                </select>
                            </div>
                            <div>
                                <label>Upload Photo</label>
                                <input type="file" name="board_files[]" accept="image/*">
                                <input type="hidden" name="images[]" value="images/placeholder-volunteer.svg">
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; padding-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" style="color: var(--danger);" onclick="this.closest('.board-row').remove()"><i class="fas fa-trash" aria-hidden="true"></i></button>
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
                <button type="button" aria-label="Remove testimonial" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: var(--danger); cursor: pointer;" onclick="if(confirm('Remove this testimonial?')) this.closest('.testimonial-row').remove()"><i class="fas fa-times-circle fa-lg" aria-hidden="true"></i></button>
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
