<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once ERC_ROOT_DIR . '/includes/icons.php';

/**
 * Extract Basic Auth credentials from PHP_AUTH_* or CGI Authorization server vars.
 *
 * @return array{provided: bool, user: string, pass: string}
 */
function erc_extract_basic_auth(): array
{
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        return [
            'provided' => true,
            'user'     => (string)$_SERVER['PHP_AUTH_USER'],
            'pass'     => (string)($_SERVER['PHP_AUTH_PW'] ?? ''),
        ];
    }

    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($header !== '' && preg_match('/^\s*Basic\s+(.+)\s*$/i', $header, $m)) {
        $decoded = base64_decode($m[1], true);
        if (is_string($decoded) && str_contains($decoded, ':')) {
            [$user, $pass] = explode(':', $decoded, 2);
            return [
                'provided' => true,
                'user'     => $user,
                'pass'     => $pass,
            ];
        }
    }

    return [
        'provided' => false,
        'user'     => '',
        'pass'     => '',
    ];
}

/**
 * Resolve the active Admin credential configuration using the exact 4-tier order.
 *
 * @return array{
 *     tier: string,
 *     label: string,
 *     user: string,
 *     hash?: string,
 *     plain?: string,
 *     salt?: string,
 *     iterations?: int,
 *     digest?: string
 * }
 */
function erc_resolve_admin_auth_config(): array
{
    $env = erc_load_env();
    $envUser = trim((string)($env['ADMIN_USER'] ?? ''));
    $defaultUser = $envUser !== '' ? $envUser : 'admin';

    // Tier 1: data/admin-auth.json bcrypt
    $stored = erc_load_json(ERC_DATA_DIR . '/admin-auth.json', []);
    if (
        is_array($stored)
        && isset($stored['password_hash'])
        && is_string($stored['password_hash'])
        && trim($stored['password_hash']) !== ''
    ) {
        $storedUser = isset($stored['user']) && is_string($stored['user']) && trim($stored['user']) !== ''
            ? trim($stored['user'])
            : 'admin';
        return [
            'tier'  => 'file_bcrypt',
            'label' => 'Managed file hash (data/admin-auth.json)',
            'user'  => $storedUser,
            'hash'  => trim($stored['password_hash']),
        ];
    }

    // Tier 2: .env['ADMIN_PASS_HASH']
    if (isset($env['ADMIN_PASS_HASH']) && trim($env['ADMIN_PASS_HASH']) !== '') {
        return [
            'tier'  => 'env_bcrypt',
            'label' => 'Environment bcrypt hash (.env ADMIN_PASS_HASH)',
            'user'  => $defaultUser,
            'hash'  => trim($env['ADMIN_PASS_HASH']),
        ];
    }

    // Tier 3: .env['ADMIN_PASS']
    if (isset($env['ADMIN_PASS']) && $env['ADMIN_PASS'] !== '') {
        return [
            'tier'  => 'env_plain',
            'label' => 'Environment password (.env ADMIN_PASS)',
            'user'  => $defaultUser,
            'plain' => (string)$env['ADMIN_PASS'],
        ];
    }

    // Tier 4: Legacy PBKDF2-HMAC-SHA256 fallback
    return [
        'tier'       => 'legacy_pbkdf2',
        'label'      => 'Legacy fallback credential (rotation recommended)',
        'user'       => $defaultUser,
        'salt'       => '2e2erc-legacy-admin-v1',
        'iterations' => 210000,
        'digest'     => '06a860745dbe420b317f6e606d01d3c4b62fbf762ee36ec74c948389515de109',
    ];
}

/**
 * Verify username and password in constant time against the active auth tier.
 *
 * @param array<string, mixed> $authConfig
 */
function erc_verify_admin_credentials(string $inputUser, string $inputPass, array $authConfig): bool
{
    $expectedUser = (string)($authConfig['user'] ?? 'admin');
    $userValid = hash_equals($expectedUser, $inputUser);

    $tier = (string)($authConfig['tier'] ?? '');
    $passValid = false;

    if ($tier === 'file_bcrypt' || $tier === 'env_bcrypt') {
        $passValid = password_verify($inputPass, (string)($authConfig['hash'] ?? ''));
    } elseif ($tier === 'env_plain') {
        $passValid = hash_equals((string)($authConfig['plain'] ?? ''), $inputPass);
    } elseif ($tier === 'legacy_pbkdf2') {
        $salt = (string)($authConfig['salt'] ?? '2e2erc-legacy-admin-v1');
        $iterations = (int)($authConfig['iterations'] ?? 210000);
        $expectedDigest = (string)($authConfig['digest'] ?? '');
        $derived = bin2hex(hash_pbkdf2('sha256', $inputPass, $salt, $iterations, 32, true));
        $passValid = hash_equals($expectedDigest, $derived);
    }

    return $userValid && $passValid;
}

/**
 * Render a minimal, accessible auth/error response page.
 */
function erc_render_admin_gate(int $statusCode, string $heading, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= e(erc_media_url('e2e2rc_LOGO.png')) ?>">
    <title><?= e($heading) ?> | Lions District 2-E2 ERC Admin</title>
    <link rel="stylesheet" href="/fonts/fonts.css">
    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/pages/admin.css">
</head>
<body class="admin-gate-body">
    <main class="admin-gate-card">
        <h1><?= e($heading) ?></h1>
        <p><?= e($message) ?></p>
        <p><a class="btn btn-secondary" href="/">Return to public site</a></p>
    </main>
</body>
</html>
    <?php
    exit;
}

// --- Step 1: Brute-Force Pre-Check & 4-Tier HTTP Basic Auth ---
erc_send_security_headers(true);
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');

$authConfig = erc_resolve_admin_auth_config();
$basicAuth = erc_extract_basic_auth();

$bruteForce = erc_check_rate_limit('admin_login_fail', 8, 900, false);
if (!$bruteForce['allowed']) {
    header('Retry-After: 900');
    erc_render_admin_gate(
        429,
        'Too Many Failed Login Attempts',
        'Admin authentication is temporarily locked for 15 minutes due to repeated failed attempts. Please wait and try again.'
    );
}

if (!$basicAuth['provided']) {
    header('WWW-Authenticate: Basic realm="ERC Admin Area"');
    erc_render_admin_gate(
        401,
        'Administrator Sign-In Required',
        'Please sign in with valid Lions District 2-E2 ERC administrator credentials to continue.'
    );
}

if (!erc_verify_admin_credentials($basicAuth['user'], $basicAuth['pass'], $authConfig)) {
    erc_check_rate_limit('admin_login_fail', 8, 900, true);
    header('WWW-Authenticate: Basic realm="ERC Admin Area"');
    erc_render_admin_gate(
        401,
        'Invalid Credentials',
        'The supplied username or password was not recognized. Please try again.'
    );
}

erc_clear_rate_limit('admin_login_fail');

// --- Step 2: Hardened Session & CSRF Token ---
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SESSION['erc_admin_user'] ?? '') !== $authConfig['user']) {
    session_regenerate_id(true);
    $_SESSION['erc_admin_user'] = $authConfig['user'];
}

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$allowedTabs = ['events', 'locations', 'gallery', 'board', 'content', 'forms', 'settings'];
$currentScriptPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/admin-view.php'), PHP_URL_PATH);
$adminSelfUrl = ($currentScriptPath === '/admin' || $currentScriptPath === '/admin/') ? '/admin' : '/admin-view.php';

$redirectAdmin = static function (?string $tab = null) use ($adminSelfUrl, $allowedTabs): never {
    $target = $adminSelfUrl;
    if ($tab !== null && in_array($tab, $allowedTabs, true)) {
        $target .= '?tab=' . rawurlencode($tab);
    }
    header('Location: ' . $target, true, 303);
    exit;
};

// --- Step 3: Image Optimization, Safe Media Cleanup, Geocoding & PDF Helpers ---

/**
 * Validate an uploaded image file (UPLOAD_ERR_OK, <=10MB, finfo MIME, extension, getimagesize).
 *
 * @param array<string, mixed> $file
 * @return array{ok: bool, error: string, ext: string, mime: string, width: int, height: int}
 */
function erc_is_valid_image_upload(array $file): array
{
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        $msg = match ($err) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image exceeds the 10 MB upload size limit.',
            UPLOAD_ERR_PARTIAL => 'Image upload was interrupted. Please retry.',
            default => 'No valid image upload was received.',
        };
        return ['ok' => false, 'error' => $msg, 'ext' => '', 'mime' => '', 'width' => 0, 'height' => 0];
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $size <= 0 || $size > 10 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Image must be non-empty and 10 MB or smaller.', 'ext' => '', 'mime' => '', 'width' => 0, 'height' => 0];
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        return ['ok' => false, 'error' => 'Unsupported image extension. Allowed: JPG, PNG, GIF, WebP.', 'ext' => '', 'mime' => '', 'width' => 0, 'height' => 0];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if (is_string($detected)) {
                $mime = $detected;
            }
        }
    }
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'Uploaded file contents do not match an allowed image format.', 'ext' => '', 'mime' => $mime, 'width' => 0, 'height' => 0];
    }

    $info = @getimagesize($tmpPath);
    if (!is_array($info) || ($info[0] ?? 0) <= 0 || ($info[1] ?? 0) <= 0) {
        return ['ok' => false, 'error' => 'Unable to read valid image dimensions.', 'ext' => '', 'mime' => $mime, 'width' => 0, 'height' => 0];
    }

    return [
        'ok'     => true,
        'error'  => '',
        'ext'    => $ext,
        'mime'   => $mime,
        'width'  => (int)$info[0],
        'height' => (int)$info[1],
    ];
}

/**
 * Optimize an uploaded image with GD (36MP guard, EXIF orientation rotation,
 * proportional downscaling, transparency preservation, and `.webp` sidecar creation).
 */
function erc_optimize_uploaded_image(string $tmpPath, string $destPath, int $maxWidth = 1600, int $maxHeight = 1600): bool
{
    $dir = dirname($destPath);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $info = @getimagesize($tmpPath);
    if (!is_array($info) || !function_exists('imagecreatetruecolor')) {
        if (@move_uploaded_file($tmpPath, $destPath) || @copy($tmpPath, $destPath)) {
            @chmod($destPath, 0644);
            return true;
        }
        return false;
    }

    $width = (int)$info[0];
    $height = (int)$info[1];
    $type = (int)$info[2];

    // 36-megapixel safety guard prevents memory exhaustion on shared hosting
    if ($width <= 0 || $height <= 0 || ($width * $height) > 36000000) {
        if (@move_uploaded_file($tmpPath, $destPath) || @copy($tmpPath, $destPath)) {
            @chmod($destPath, 0644);
            return true;
        }
        return false;
    }

    $srcImg = match ($type) {
        IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($tmpPath) : false,
        IMAGETYPE_PNG  => function_exists('imagecreatefrompng') ? @imagecreatefrompng($tmpPath) : false,
        IMAGETYPE_GIF  => function_exists('imagecreatefromgif') ? @imagecreatefromgif($tmpPath) : false,
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false,
        default        => false,
    };

    if ($srcImg === false) {
        if (@move_uploaded_file($tmpPath, $destPath) || @copy($tmpPath, $destPath)) {
            @chmod($destPath, 0644);
            return true;
        }
        return false;
    }

    // EXIF auto-rotation for JPEGs
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmpPath);
        $orientation = is_array($exif) ? (int)($exif['Orientation'] ?? 1) : 1;
        $rotated = null;
        if ($orientation === 3) {
            $rotated = @imagerotate($srcImg, 180, 0);
        } elseif ($orientation === 6) {
            $rotated = @imagerotate($srcImg, -90, 0);
        } elseif ($orientation === 8) {
            $rotated = @imagerotate($srcImg, 90, 0);
        }
        if ($rotated !== null && $rotated !== false) {
            $srcImg = $rotated;
            $width = imagesx($srcImg);
            $height = imagesy($srcImg);
        }
    }

    $scale = min(1.0, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
    $newWidth = max(1, (int)round($width * $scale));
    $newHeight = max(1, (int)round($height * $scale));

    $dstImg = imagecreatetruecolor($newWidth, $newHeight);
    if ($dstImg === false) {
        if (@move_uploaded_file($tmpPath, $destPath) || @copy($tmpPath, $destPath)) {
            @chmod($destPath, 0644);
            return true;
        }
        return false;
    }

    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $transparent);
        }
    } elseif ($type === IMAGETYPE_GIF) {
        $transIndex = imagecolortransparent($srcImg);
        if ($transIndex >= 0) {
            $transColor = @imagecolorsforindex($srcImg, $transIndex);
            if (is_array($transColor)) {
                $newTrans = imagecolorallocatealpha(
                    $dstImg,
                    (int)$transColor['red'],
                    (int)$transColor['green'],
                    (int)$transColor['blue'],
                    127
                );
                if ($newTrans !== false) {
                    imagefill($dstImg, 0, 0, $newTrans);
                    imagecolortransparent($dstImg, $newTrans);
                }
            }
        }
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $saved = match ($type) {
        IMAGETYPE_JPEG => @imagejpeg($dstImg, $destPath, 84),
        IMAGETYPE_PNG  => @imagepng($dstImg, $destPath, 7),
        IMAGETYPE_GIF  => @imagegif($dstImg, $destPath),
        IMAGETYPE_WEBP => function_exists('imagewebp') ? @imagewebp($dstImg, $destPath, 84) : false,
        default        => false,
    };

    if (!$saved) {
        unset($srcImg, $dstImg);
        if (@move_uploaded_file($tmpPath, $destPath) || @copy($tmpPath, $destPath)) {
            @chmod($destPath, 0644);
            return true;
        }
        return false;
    }

    @chmod($destPath, 0644);

    // Companion WebP sidecar
    if ($type !== IMAGETYPE_WEBP && function_exists('imagewebp')) {
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $destPath);
        if (is_string($webpPath)) {
            if (!imageistruecolor($dstImg) && function_exists('imagepalettetotruecolor')) {
                @imagepalettetotruecolor($dstImg);
            }
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            if (@imagewebp($dstImg, $webpPath, 82)) {
                @chmod($webpPath, 0644);
            }
        }
    }

    unset($srcImg, $dstImg);
    return true;
}

/**
 * Safely delete an owned image + companion `.webp` strictly inside ERC_ROOT_DIR.
 * Never deletes placeholders or files outside ERC_ROOT_DIR (preserves read-only coexistence root).
 */
function erc_delete_owned_media(?string $relativePath, string $allowedSubdir): bool
{
    if (!is_string($relativePath) || trim($relativePath) === '') {
        return false;
    }

    $clean = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
    if (
        str_contains($clean, "\0")
        || preg_match('#(?:^|/)\.\.(?:/|$)#', $clean)
        || str_contains($clean, 'placeholder-')
        || !str_starts_with($clean, $allowedSubdir . '/')
    ) {
        return false;
    }

    $baseReal = realpath(ERC_ROOT_DIR . '/' . $allowedSubdir);
    if ($baseReal === false) {
        return false;
    }

    $targetReal = realpath(ERC_ROOT_DIR . '/' . $clean);
    if (
        $targetReal === false
        || !is_file($targetReal)
        || !str_starts_with($targetReal, $baseReal . DIRECTORY_SEPARATOR)
    ) {
        return false;
    }

    $deleted = @unlink($targetReal);
    $webpCandidate = preg_replace('/\.[^.]+$/', '.webp', $targetReal);
    if (is_string($webpCandidate) && $webpCandidate !== $targetReal) {
        $webpReal = realpath($webpCandidate);
        if ($webpReal !== false && is_file($webpReal) && str_starts_with($webpReal, $baseReal . DIRECTORY_SEPARATOR)) {
            @unlink($webpReal);
        }
    }

    return $deleted;
}

/**
 * Sanitize an image relative path submitted in a form field.
 */
function erc_sanitize_media_rel_path(string $rawPath, string $fallback): string
{
    $clean = ltrim(str_replace('\\', '/', trim($rawPath)), '/');
    if (
        $clean === ''
        || str_contains($clean, "\0")
        || preg_match('#(?:^|/)\.\.(?:/|$)#', $clean)
        || preg_match('#^[a-z]+:#i', $clean)
    ) {
        return $fallback;
    }
    return $clean;
}

/**
 * Generate a safe slugified upload filename inside target subdir without collisions.
 */
function erc_generate_upload_filename(string $stemHint, string $ext, string $targetDir): string
{
    $slug = strtolower((string)preg_replace('/[^a-zA-Z0-9_-]+/', '-', $stemHint));
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'upload';
    }
    $slug = substr($slug, 0, 48);

    try {
        $rand = bin2hex(random_bytes(4));
    } catch (\Throwable) {
        $rand = substr(md5((string)mt_rand()), 0, 8);
    }

    $filename = $slug . '-' . $rand . '.' . strtolower($ext);
    while (file_exists($targetDir . DIRECTORY_SEPARATOR . $filename)) {
        $filename = $slug . '-' . $rand . '-' . mt_rand(10, 99) . '.' . strtolower($ext);
    }
    return $filename;
}

/**
 * Geocode an address via OpenStreetMap Nominatim with US Census Geocoder fallback.
 *
 * @return array{lat: float, lng: float}|null
 */
function erc_geocode_address(string $address): ?array
{
    $clean = trim($address);
    if ($clean === '') {
        return null;
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 6,
            'header'  => "User-Agent: Lions2E2ERC/1.0 (https://2e2erc.org)\r\nAccept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);

    // 1. OpenStreetMap Nominatim
    $nomUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($clean);
    $nomBody = @file_get_contents($nomUrl, false, $ctx);
    if (is_string($nomBody) && $nomBody !== '') {
        $nomJson = json_decode($nomBody, true);
        if (
            is_array($nomJson)
            && isset($nomJson[0]['lat'], $nomJson[0]['lon'])
            && is_numeric($nomJson[0]['lat'])
            && is_numeric($nomJson[0]['lon'])
        ) {
            return [
                'lat' => (float)$nomJson[0]['lat'],
                'lng' => (float)$nomJson[0]['lon'],
            ];
        }
    }

    // 2. US Census Geocoder fallback
    $cenUrl = 'https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?benchmark=Public_AR_Current&format=json&address=' . rawurlencode($clean);
    $cenBody = @file_get_contents($cenUrl, false, $ctx);
    if (is_string($cenBody) && $cenBody !== '') {
        $cenJson = json_decode($cenBody, true);
        $coords = $cenJson['result']['addressMatches'][0]['coordinates'] ?? null;
        if (
            is_array($coords)
            && isset($coords['y'], $coords['x'])
            && is_numeric($coords['y'])
            && is_numeric($coords['x'])
        ) {
            return [
                'lat' => (float)$coords['y'],
                'lng' => (float)$coords['x'],
            ];
        }
    }

    return null;
}

/**
 * Enumerate official downloadable PDF forms across redesign/files and read-only coexistence root files/.
 *
 * @return array<string, array{filename: string, label: string, exists: bool, size: int, mtime: int, overridden: bool}>
 */
function erc_get_official_forms(): array
{
    $descriptions = [
        'ERC-Brochure.pdf'                     => 'Organizational Tri-Fold Brochure & 2013-2025 Shipping History',
        'ERC-Bylaws.pdf'                       => 'Foundation Corporate Bylaws (Approved 2014)',
        'ERC-Life-Membership-Application.pdf'  => 'ERC Life Membership Recognition Application',
        'Group-Volunteer-Form.pdf'             => 'Group Volunteer Day Scheduling Form (Up to 20 Volunteers)',
        'Helen-Keller-Award-Application.pdf'   => 'Helen Keller Award ("Knights of Sight") Application',
        'Mission-Request-Form.pdf'             => 'Medical & Humanitarian Mission Eyeglass Request Form',
    ];

    $names = array_keys($descriptions);
    $scanDirs = [ERC_ROOT_DIR . '/files'];
    if (erc_is_coexistence_mode()) {
        $scanDirs[] = dirname(ERC_ROOT_DIR) . '/files';
    }
    foreach ($scanDirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $entries = @scandir($dir);
        if (!is_array($entries)) {
            continue;
        }
        foreach ($entries as $entry) {
            if (preg_match('/^[A-Za-z0-9._-]+\.pdf$/', $entry) && !in_array($entry, $names, true)) {
                $names[] = $entry;
            }
        }
    }

    $forms = [];
    foreach ($names as $name) {
        $localPath = ERC_ROOT_DIR . '/files/' . $name;
        $resolved = erc_resolve_readable_asset('files/' . $name);
        $forms[$name] = [
            'filename'   => $name,
            'label'      => $descriptions[$name] ?? $name,
            'exists'     => $resolved !== null,
            'size'       => $resolved !== null ? (int)(@filesize($resolved) ?: 0) : 0,
            'mtime'      => $resolved !== null ? (int)(@filemtime($resolved) ?: 0) : 0,
            'overridden' => is_file($localPath),
        ];
    }

    return $forms;
}

// --- Step 4: POST Action Handling (13 Actions + CSRF + 303 PRG) ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['flash_error'] = 'Uploaded request exceeded PHP post_max_size (' . (string)ini_get('post_max_size') . '). Please upload fewer or smaller files.';
        $redirectAdmin((string)($_GET['tab'] ?? 'events'));
    }

    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        erc_render_admin_gate(
            403,
            'Security Verification Failed',
            'Your admin CSRF token was missing or expired. Please return to the dashboard, reload the page, and submit again.'
        );
    }

    $action = (string)($_POST['action'] ?? '');
    $returnTab = (string)($_POST['return_tab'] ?? 'events');
    if (!in_array($returnTab, $allowedTabs, true)) {
        $returnTab = 'events';
    }

    switch ($action) {
        // 1. save_event
        case 'save_event':
            $eventId = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $eventId = ($eventId !== false && $eventId > 0) ? $eventId : 0;
            $title = trim(strip_tags((string)($_POST['title'] ?? '')));
            $date = trim((string)($_POST['date'] ?? ''));
            $time = trim(strip_tags((string)($_POST['time'] ?? '')));
            $maxPeople = filter_var($_POST['maxPeople'] ?? 15, FILTER_VALIDATE_INT);
            $location = trim(strip_tags((string)($_POST['location'] ?? '')));
            $description = trim(strip_tags((string)($_POST['description'] ?? '')));
            $contact = trim((string)($_POST['contact'] ?? ''));
            if ($contact === '') {
                $contact = $GLOBAL_EMAIL;
            }
            $existingImage = erc_sanitize_media_rel_path((string)($_POST['existing_image'] ?? ''), 'images/placeholder-event.svg');

            $dtCheck = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            $dateValid = $dtCheck !== false && $dtCheck->format('Y-m-d') === $date;

            if (
                $title === ''
                || mb_strlen($title, 'UTF-8') > 150
                || !$dateValid
                || $time === ''
                || mb_strlen($time, 'UTF-8') > 80
                || $maxPeople === false
                || $maxPeople < 1
                || $maxPeople > 500
                || $location === ''
                || mb_strlen($location, 'UTF-8') > 250
                || mb_strlen($description, 'UTF-8') > 2000
                || mb_strlen($contact, 'UTF-8') > 254
            ) {
                $_SESSION['flash_error'] = 'Please complete all required event fields with valid date and capacity (1-500).';
                $redirectAdmin('events');
            }

            $finalImage = $existingImage;
            if (isset($_FILES['image']) && is_array($_FILES['image']) && (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $check = erc_is_valid_image_upload($_FILES['image']);
                if (!$check['ok']) {
                    $_SESSION['flash_error'] = $check['error'];
                    $redirectAdmin('events');
                }
                $destDir = ERC_ROOT_DIR . '/images/events';
                $filename = erc_generate_upload_filename($title, $check['ext'], $destDir);
                $destPath = $destDir . '/' . $filename;
                if (!erc_optimize_uploaded_image((string)$_FILES['image']['tmp_name'], $destPath, 1600, 1600)) {
                    $_SESSION['flash_error'] = 'Failed to store uploaded event image.';
                    $redirectAdmin('events');
                }
                $finalImage = 'images/events/' . $filename;
            }

            $res = erc_with_storage_lock(static function () use ($eventId, $title, $date, $time, $maxPeople, $location, $description, $contact, $finalImage): array {
                $events = erc_load_json(ERC_DATA_DIR . '/events.json', []);
                $signups = erc_load_json(ERC_DATA_DIR . '/signups.json', []);
                if (!is_array($events)) {
                    $events = [];
                }
                if (!is_array($signups)) {
                    $signups = [];
                }

                $oldImage = null;
                $updated = false;
                if ($eventId > 0) {
                    foreach ($events as $idx => $ev) {
                        if (is_array($ev) && (int)($ev['id'] ?? 0) === $eventId) {
                            $oldImage = isset($ev['image']) ? (string)$ev['image'] : null;
                            $key = (string)$eventId;
                            $peopleCount = isset($signups[$key]) && is_array($signups[$key]) ? count($signups[$key]) : 0;
                            $events[$idx] = [
                                'id'          => $eventId,
                                'title'       => $title,
                                'date'        => $date,
                                'time'        => $time,
                                'people'      => $peopleCount,
                                'maxPeople'   => $maxPeople,
                                'location'    => $location,
                                'image'       => $finalImage,
                                'description' => $description,
                                'contact'     => $contact,
                            ];
                            $updated = true;
                            break;
                        }
                    }
                }

                if (!$updated) {
                    $maxId = 0;
                    foreach ($events as $ev) {
                        if (is_array($ev)) {
                            $maxId = max($maxId, (int)($ev['id'] ?? 0));
                        }
                    }
                    $newId = $maxId + 1;
                    $events[] = [
                        'id'          => $newId,
                        'title'       => $title,
                        'date'        => $date,
                        'time'        => $time,
                        'people'      => 0,
                        'maxPeople'   => $maxPeople,
                        'location'    => $location,
                        'image'       => $finalImage,
                        'description' => $description,
                        'contact'     => $contact,
                    ];
                }

                usort($events, static function ($a, $b): int {
                    $cmp = strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                    return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
                });

                if (!erc_save_json_atomic(ERC_DATA_DIR . '/events.json', array_values($events), 0644)) {
                    return ['ok' => false, 'error' => 'Failed to save events.json.'];
                }

                if ($oldImage !== null && $oldImage !== $finalImage) {
                    $stillReferenced = false;
                    foreach ($events as $ev) {
                        if (is_array($ev) && (string)($ev['image'] ?? '') === $oldImage) {
                            $stillReferenced = true;
                            break;
                        }
                    }
                    if (!$stillReferenced) {
                        erc_delete_owned_media($oldImage, 'images/events');
                    }
                }

                return ['ok' => true, 'updated' => $updated];
            });

            if ($res['ok']) {
                $_SESSION['flash_message'] = $res['updated'] ? 'Volunteer event updated.' : 'New volunteer event created.';
            } else {
                $_SESSION['flash_error'] = $res['error'];
            }
            $redirectAdmin('events');

        // 2. delete_event
        case 'delete_event':
            $eventId = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            if ($eventId === false || $eventId <= 0) {
                $_SESSION['flash_error'] = 'Invalid event ID.';
                $redirectAdmin('events');
            }

            $res = erc_with_storage_lock(static function () use ($eventId): array {
                $events = erc_load_json(ERC_DATA_DIR . '/events.json', []);
                $signups = erc_load_json(ERC_DATA_DIR . '/signups.json', []);
                if (!is_array($events)) {
                    $events = [];
                }
                if (!is_array($signups)) {
                    $signups = [];
                }

                $removedImage = null;
                $found = false;
                $remaining = [];
                foreach ($events as $ev) {
                    if (is_array($ev) && (int)($ev['id'] ?? 0) === $eventId) {
                        $found = true;
                        $removedImage = isset($ev['image']) ? (string)$ev['image'] : null;
                        continue;
                    }
                    $remaining[] = $ev;
                }

                if (!$found) {
                    return ['ok' => false, 'error' => 'Event not found.'];
                }

                unset($signups[(string)$eventId]);
                $prevSignups = erc_load_json(ERC_DATA_DIR . '/signups.json', []);
                if (!erc_save_json_atomic(ERC_DATA_DIR . '/signups.json', $signups, 0600)) {
                    return ['ok' => false, 'error' => 'Failed to update signups.json.'];
                }
                if (!erc_save_json_atomic(ERC_DATA_DIR . '/events.json', array_values($remaining), 0644)) {
                    erc_save_json_atomic(ERC_DATA_DIR . '/signups.json', $prevSignups, 0600);
                    return ['ok' => false, 'error' => 'Failed to update events.json.'];
                }

                if ($removedImage !== null) {
                    $stillReferenced = false;
                    foreach ($remaining as $ev) {
                        if (is_array($ev) && (string)($ev['image'] ?? '') === $removedImage) {
                            $stillReferenced = true;
                            break;
                        }
                    }
                    if (!$stillReferenced) {
                        erc_delete_owned_media($removedImage, 'images/events');
                    }
                }

                return ['ok' => true];
            });

            if ($res['ok']) {
                $_SESSION['flash_message'] = 'Volunteer event and its attendee list were removed.';
            } else {
                $_SESSION['flash_error'] = $res['error'];
            }
            $redirectAdmin('events');

        // 3. save_location
        case 'save_location':
            $index = filter_var($_POST['index'] ?? -1, FILTER_VALIDATE_INT);
            $name = trim(strip_tags((string)($_POST['name'] ?? '')));
            $address = trim(strip_tags((string)($_POST['address'] ?? '')));
            $phone = trim(strip_tags((string)($_POST['phone'] ?? '')));

            if ($index === false || $name === '' || mb_strlen($name, 'UTF-8') > 160 || $address === '' || mb_strlen($address, 'UTF-8') > 260 || mb_strlen($phone, 'UTF-8') > 40) {
                $_SESSION['flash_error'] = 'Please enter a valid drop-off location name and address.';
                $redirectAdmin('locations');
            }

            $saved = erc_with_storage_lock(static function () use ($index, $name, $address, $phone): bool {
                $locations = erc_load_json(ERC_DATA_DIR . '/locations.json', []);
                if (!is_array($locations)) {
                    $locations = [];
                }
                $record = [
                    'name'    => $name,
                    'address' => $address,
                    'phone'   => $phone,
                ];
                if ($index >= 0 && isset($locations[$index])) {
                    $locations[$index] = $record;
                } else {
                    $locations[] = $record;
                }

                usort($locations, static function ($a, $b): int {
                    return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
                });

                return erc_save_json_atomic(ERC_DATA_DIR . '/locations.json', array_values($locations), 0644);
            });

            if (!$saved) {
                $_SESSION['flash_error'] = 'Unable to save locations.json.';
                $redirectAdmin('locations');
            }

            // Geocode if address lacks numeric coordinates in geo_cache.json
            $geoCache = erc_load_json(ERC_DATA_DIR . '/geo_cache.json', []);
            if (!is_array($geoCache)) {
                $geoCache = [];
            }
            $cached = $geoCache[$address] ?? null;
            $hasCoords = is_array($cached) && isset($cached['lat'], $cached['lng']) && is_numeric($cached['lat']) && is_numeric($cached['lng']);
            $geoNote = 'Cached coordinates reused.';
            if (!$hasCoords) {
                $coords = erc_geocode_address($address);
                erc_with_storage_lock(static function () use ($address, $coords): void {
                    $cache = erc_load_json(ERC_DATA_DIR . '/geo_cache.json', []);
                    if (!is_array($cache)) {
                        $cache = [];
                    }
                    $cache[$address] = [
                        'lat'       => $coords !== null ? $coords['lat'] : null,
                        'lng'       => $coords !== null ? $coords['lng'] : null,
                        'timestamp' => time(),
                    ];
                    erc_save_json_atomic(ERC_DATA_DIR . '/geo_cache.json', $cache, 0644);
                });
                $geoNote = $coords !== null
                    ? 'Coordinates geocoded and cached for map display.'
                    : 'Saved to directory (geocoder could not resolve map pin automatically).';
            }

            $_SESSION['flash_message'] = 'Drop-off location saved. ' . $geoNote;
            $redirectAdmin('locations');

        // 4. delete_location
        case 'delete_location':
            $index = filter_var($_POST['index'] ?? '', FILTER_VALIDATE_INT);
            if ($index === false || $index < 0) {
                $_SESSION['flash_error'] = 'Invalid location index.';
                $redirectAdmin('locations');
            }

            $res = erc_with_storage_lock(static function () use ($index): bool {
                $locations = erc_load_json(ERC_DATA_DIR . '/locations.json', []);
                if (!is_array($locations) || !isset($locations[$index])) {
                    return false;
                }
                array_splice($locations, $index, 1);
                return erc_save_json_atomic(ERC_DATA_DIR . '/locations.json', array_values($locations), 0644);
            });

            if ($res) {
                $_SESSION['flash_message'] = 'Drop-off location removed.';
            } else {
                $_SESSION['flash_error'] = 'Could not locate that drop-off location.';
            }
            $redirectAdmin('locations');

        // 5. save_board
        case 'save_board':
            $names = isset($_POST['names']) && is_array($_POST['names']) ? $_POST['names'] : [];
            $tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
            $images = isset($_POST['images']) && is_array($_POST['images']) ? $_POST['images'] : [];
            $files = isset($_FILES['board_files']) && is_array($_FILES['board_files']) ? $_FILES['board_files'] : [];

            $newBoard = [];
            $uploadErrors = [];
            $boardDir = ERC_ROOT_DIR . '/images/board';

            foreach ($names as $i => $rawName) {
                $memberName = trim(strip_tags((string)$rawName));
                if ($memberName === '') {
                    continue;
                }
                $tag = ((string)($tags[$i] ?? 'current')) === 'previous' ? 'previous' : 'current';
                $memberImg = erc_sanitize_media_rel_path((string)($images[$i] ?? ''), 'images/placeholder-volunteer.svg');

                if (
                    isset($files['error'][$i])
                    && (int)$files['error'][$i] !== UPLOAD_ERR_NO_FILE
                ) {
                    $singleFile = [
                        'name'     => $files['name'][$i] ?? '',
                        'type'     => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size'     => $files['size'][$i] ?? 0,
                    ];
                    $check = erc_is_valid_image_upload($singleFile);
                    if (!$check['ok']) {
                        $uploadErrors[] = $memberName . ': ' . $check['error'];
                    } else {
                        $filename = erc_generate_upload_filename($memberName, $check['ext'], $boardDir);
                        if (erc_optimize_uploaded_image((string)$singleFile['tmp_name'], $boardDir . '/' . $filename, 400, 400)) {
                            $memberImg = 'images/board/' . $filename;
                        } else {
                            $uploadErrors[] = $memberName . ': failed to store portrait upload.';
                        }
                    }
                }

                $newBoard[] = [
                    'name'  => $memberName,
                    'image' => $memberImg,
                    'tag'   => $tag,
                ];
            }

            if (!empty($uploadErrors)) {
                $_SESSION['flash_error'] = 'Board upload issues: ' . implode(' ', $uploadErrors);
                $redirectAdmin('board');
            }

            $res = erc_with_storage_lock(static function () use ($newBoard): bool {
                $oldBoard = erc_load_json(ERC_DATA_DIR . '/board.json', []);
                if (!is_array($oldBoard)) {
                    $oldBoard = [];
                }
                if (!erc_save_json_atomic(ERC_DATA_DIR . '/board.json', $newBoard, 0644)) {
                    return false;
                }

                $activeImages = [];
                foreach ($newBoard as $entry) {
                    $activeImages[(string)$entry['image']] = true;
                }
                foreach ($oldBoard as $oldEntry) {
                    if (!is_array($oldEntry)) {
                        continue;
                    }
                    $oldImg = (string)($oldEntry['image'] ?? '');
                    if ($oldImg !== '' && !isset($activeImages[$oldImg])) {
                        erc_delete_owned_media($oldImg, 'images/board');
                    }
                }
                return true;
            });

            if ($res) {
                $_SESSION['flash_message'] = 'Board of Directors roster saved (' . count($newBoard) . ' members).';
            } else {
                $_SESSION['flash_error'] = 'Failed to save board.json.';
            }
            $redirectAdmin('board');

        // 6. delete_signup
        case 'delete_signup':
            $eventId = filter_var($_POST['event_id'] ?? '', FILTER_VALIDATE_INT);
            $index = filter_var($_POST['index'] ?? '', FILTER_VALIDATE_INT);
            if ($eventId === false || $eventId <= 0 || $index === false || $index < 0) {
                $_SESSION['flash_error'] = 'Invalid signup reference.';
                $redirectAdmin('events');
            }

            $res = erc_with_storage_lock(static function () use ($eventId, $index): bool {
                $signups = erc_load_json(ERC_DATA_DIR . '/signups.json', []);
                $events = erc_load_json(ERC_DATA_DIR . '/events.json', []);
                if (!is_array($signups) || !is_array($events)) {
                    return false;
                }

                $key = (string)$eventId;
                if (!isset($signups[$key]) || !is_array($signups[$key]) || !isset($signups[$key][$index])) {
                    return false;
                }

                $prevSignups = $signups;
                array_splice($signups[$key], $index, 1);
                $newCount = count($signups[$key]);
                if ($newCount === 0) {
                    unset($signups[$key]);
                }

                foreach ($events as $idx => $ev) {
                    if (is_array($ev) && (int)($ev['id'] ?? 0) === $eventId) {
                        $events[$idx]['people'] = $newCount;
                        break;
                    }
                }

                if (!erc_save_json_atomic(ERC_DATA_DIR . '/signups.json', $signups, 0600)) {
                    return false;
                }
                if (!erc_save_json_atomic(ERC_DATA_DIR . '/events.json', $events, 0644)) {
                    erc_save_json_atomic(ERC_DATA_DIR . '/signups.json', $prevSignups, 0600);
                    return false;
                }
                return true;
            });

            if ($res) {
                $_SESSION['flash_message'] = 'Volunteer registration removed and seat capacity updated.';
            } else {
                $_SESSION['flash_error'] = 'Unable to remove that volunteer registration.';
            }
            $redirectAdmin('events');

        // 7. upload_gallery
        case 'upload_gallery':
            if (!isset($_FILES['gallery_photos']) || !is_array($_FILES['gallery_photos']['name'] ?? null)) {
                $_SESSION['flash_error'] = 'Please select one or more images to upload.';
                $redirectAdmin('gallery');
            }

            $galleryDir = ERC_ROOT_DIR . '/gallery';
            $countFiles = count($_FILES['gallery_photos']['name']);
            $uploaded = 0;
            $errors = [];

            for ($i = 0; $i < $countFiles; $i++) {
                $err = (int)($_FILES['gallery_photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $singleFile = [
                    'name'     => $_FILES['gallery_photos']['name'][$i] ?? '',
                    'type'     => $_FILES['gallery_photos']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['gallery_photos']['tmp_name'][$i] ?? '',
                    'error'    => $err,
                    'size'     => $_FILES['gallery_photos']['size'][$i] ?? 0,
                ];
                $check = erc_is_valid_image_upload($singleFile);
                if (!$check['ok']) {
                    $errors[] = (string)$singleFile['name'] . ': ' . $check['error'];
                    continue;
                }

                $stem = pathinfo((string)$singleFile['name'], PATHINFO_FILENAME);
                $safeStem = strtolower((string)preg_replace('/[^a-zA-Z0-9._-]+/', '-', $stem));
                $safeStem = trim($safeStem, '.-') ?: 'gallery-photo';
                $destName = $safeStem . '.' . $check['ext'];
                if (
                    file_exists($galleryDir . '/' . $destName)
                    || (erc_is_coexistence_mode() && file_exists(dirname(ERC_ROOT_DIR) . '/gallery/' . $destName))
                ) {
                    $destName = erc_generate_upload_filename($safeStem, $check['ext'], $galleryDir);
                }

                if (erc_optimize_uploaded_image((string)$singleFile['tmp_name'], $galleryDir . '/' . $destName, 1600, 1600)) {
                    $uploaded++;
                } else {
                    $errors[] = (string)$singleFile['name'] . ': failed to save optimized image.';
                }
            }

            if (is_file(ERC_DATA_DIR . '/gallery-cache.json')) {
                @unlink(ERC_DATA_DIR . '/gallery-cache.json');
            }

            if ($uploaded > 0 && empty($errors)) {
                $_SESSION['flash_message'] = "Uploaded and optimized {$uploaded} gallery photo(s).";
            } elseif ($uploaded > 0) {
                $_SESSION['flash_message'] = "Uploaded {$uploaded} photo(s). Some files were skipped: " . implode(' ', $errors);
            } else {
                $_SESSION['flash_error'] = !empty($errors) ? implode(' ', $errors) : 'No images were uploaded.';
            }
            $redirectAdmin('gallery');

        // 8. delete_gallery
        case 'delete_gallery':
            $photo = trim((string)($_POST['photo'] ?? ''));
            $cleanPhoto = ltrim(str_replace('\\', '/', $photo), '/');
            if (
                !str_starts_with($cleanPhoto, 'gallery/')
                || str_contains($cleanPhoto, "\0")
                || preg_match('#(?:^|/)\.\.(?:/|$)#', $cleanPhoto)
            ) {
                $_SESSION['flash_error'] = 'Invalid gallery photo path.';
                $redirectAdmin('gallery');
            }

            $deleted = erc_delete_owned_media($cleanPhoto, 'gallery');
            if (is_file(ERC_DATA_DIR . '/gallery-cache.json')) {
                @unlink(ERC_DATA_DIR . '/gallery-cache.json');
            }

            if ($deleted) {
                $_SESSION['flash_message'] = 'Gallery photo deleted.';
            } elseif (erc_is_coexistence_mode() && erc_resolve_readable_asset($cleanPhoto) !== null) {
                $_SESSION['flash_error'] = 'Shared root gallery photos are read-only during coexistence preview (:8770). Only newly uploaded redesign gallery photos can be deleted here.';
            } else {
                $_SESSION['flash_error'] = 'Gallery photo could not be found.';
            }
            $redirectAdmin('gallery');

        // 9. save_partners
        case 'save_partners':
            $rawPartners = (string)($_POST['partners'] ?? '');
            $lines = preg_split('/\r\n|\r|\n/', $rawPartners);
            $partnersList = [];
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $clean = trim(strip_tags($line));
                    if ($clean !== '') {
                        $partnersList[] = $clean;
                    }
                }
            }

            if (erc_save_json_atomic(ERC_DATA_DIR . '/partners.json', array_values($partnersList), 0644)) {
                $_SESSION['flash_message'] = 'Saved ' . count($partnersList) . ' partner organizations.';
            } else {
                $_SESSION['flash_error'] = 'Failed to save partners.json.';
            }
            $redirectAdmin('content');

        // 10. save_testimonials
        case 'save_testimonials':
            $texts = isset($_POST['texts']) && is_array($_POST['texts']) ? $_POST['texts'] : [];
            $authors = isset($_POST['authors']) && is_array($_POST['authors']) ? $_POST['authors'] : [];
            $orgs = isset($_POST['orgs']) && is_array($_POST['orgs']) ? $_POST['orgs'] : [];

            $testimonialsList = [];
            foreach ($texts as $i => $rawText) {
                $text = trim(strip_tags((string)$rawText));
                if ($text === '') {
                    continue;
                }
                $testimonialsList[] = [
                    'text'         => $text,
                    'author'       => trim(strip_tags((string)($authors[$i] ?? ''))),
                    'organization' => trim(strip_tags((string)($orgs[$i] ?? ''))),
                ];
            }

            if (erc_save_json_atomic(ERC_DATA_DIR . '/testimonials.json', $testimonialsList, 0644)) {
                $_SESSION['flash_message'] = 'Saved ' . count($testimonialsList) . ' testimonials.';
            } else {
                $_SESSION['flash_error'] = 'Failed to save testimonials.json.';
            }
            $redirectAdmin('content');

        // 11. save_config
        case 'save_config':
            $newEmail = trim((string)($_POST['global_email'] ?? ''));
            $newPhone = trim(strip_tags((string)($_POST['global_phone'] ?? '')));

            if (
                $newEmail === ''
                || strlen($newEmail) > 254
                || filter_var($newEmail, FILTER_VALIDATE_EMAIL) === false
                || $newPhone === ''
                || mb_strlen($newPhone, 'UTF-8') > 40
            ) {
                $_SESSION['flash_error'] = 'Please enter a valid public contact email and phone number (<= 40 characters).';
                $redirectAdmin('settings');
            }

            if (erc_save_json_atomic(ERC_DATA_DIR . '/site-config.json', ['email' => $newEmail, 'phone' => $newPhone], 0600)) {
                $_SESSION['flash_message'] = 'Global site contact email and phone updated.';
            } else {
                $_SESSION['flash_error'] = 'Failed to save site-config.json.';
            }
            $redirectAdmin('settings');

        // 12. change_admin_password
        case 'change_admin_password':
            $newUser = trim((string)($_POST['admin_username'] ?? ''));
            $curPass = (string)($_POST['current_password'] ?? '');
            $newPass = (string)($_POST['new_password'] ?? '');
            $confirmPass = (string)($_POST['confirm_password'] ?? '');

            if (
                $newUser === ''
                || strlen($newUser) > 64
                || preg_match('/[\x00-\x1F\x7F:]/', $newUser)
            ) {
                $_SESSION['flash_error'] = 'Admin username must be 1-64 characters and cannot contain ":" or control characters.';
                $redirectAdmin('settings');
            }

            if (!erc_verify_admin_credentials((string)$authConfig['user'], $curPass, $authConfig)) {
                $_SESSION['flash_error'] = 'Current password did not match.';
                $redirectAdmin('settings');
            }

            if (strlen($newPass) < 8 || strlen($newPass) > 256) {
                $_SESSION['flash_error'] = 'New password must be at least 8 characters long.';
                $redirectAdmin('settings');
            }

            if (!hash_equals($newPass, $confirmPass)) {
                $_SESSION['flash_error'] = 'New password and confirmation do not match.';
                $redirectAdmin('settings');
            }

            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $payload = [
                'user'          => $newUser,
                'password_hash' => $newHash,
                'updated_at'    => date('c'),
            ];

            if (erc_save_json_atomic(ERC_DATA_DIR . '/admin-auth.json', $payload, 0600)) {
                $_SESSION['flash_message'] = 'Admin credentials updated in data/admin-auth.json. Use your new credentials on next browser sign-in.';
            } else {
                $_SESSION['flash_error'] = 'Failed to persist data/admin-auth.json.';
            }
            $redirectAdmin('settings');

        // 13. upload_form
        case 'upload_form':
            $targetFilename = trim((string)($_POST['target_filename'] ?? ''));
            $cleanFilename = basename($targetFilename);
            $officialForms = erc_get_official_forms();

            if (
                $targetFilename === ''
                || $cleanFilename !== $targetFilename
                || !preg_match('/^[A-Za-z0-9._-]+\.pdf$/', $cleanFilename)
                || !isset($officialForms[$cleanFilename])
            ) {
                $_SESSION['flash_error'] = 'Invalid target PDF form selected.';
                $redirectAdmin('forms');
            }

            if (!isset($_FILES['new_form']) || !is_array($_FILES['new_form'])) {
                $_SESSION['flash_error'] = 'Please choose a PDF file to upload.';
                $redirectAdmin('forms');
            }

            $pdfFile = $_FILES['new_form'];
            if ((int)($pdfFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = 'PDF upload failed (error code ' . (int)($pdfFile['error'] ?? -1) . ').';
                $redirectAdmin('forms');
            }

            $tmpPdf = (string)($pdfFile['tmp_name'] ?? '');
            $pdfSize = (int)($pdfFile['size'] ?? 0);
            $uploadedExt = strtolower(pathinfo((string)($pdfFile['name'] ?? ''), PATHINFO_EXTENSION));
            if ($tmpPdf === '' || !is_uploaded_file($tmpPdf) || $pdfSize <= 5 || $pdfSize > 20 * 1024 * 1024 || $uploadedExt !== 'pdf') {
                $_SESSION['flash_error'] = 'Uploaded file must be a valid .pdf document (<= 20 MB).';
                $redirectAdmin('forms');
            }

            $mime = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected = finfo_file($finfo, $tmpPdf);
                    finfo_close($finfo);
                    if (is_string($detected)) {
                        $mime = $detected;
                    }
                }
            }
            if ($mime !== 'application/pdf') {
                $_SESSION['flash_error'] = 'Uploaded file MIME type must be application/pdf.';
                $redirectAdmin('forms');
            }

            $fh = @fopen($tmpPdf, 'rb');
            $magic = $fh !== false ? @fread($fh, 5) : '';
            if ($fh !== false) {
                @fclose($fh);
            }
            if ($magic !== '%PDF-') {
                $_SESSION['flash_error'] = 'Uploaded file does not begin with valid %PDF- signature.';
                $redirectAdmin('forms');
            }

            $filesDir = ERC_ROOT_DIR . '/files';
            if (!is_dir($filesDir) && !@mkdir($filesDir, 0755, true) && !is_dir($filesDir)) {
                $_SESSION['flash_error'] = 'Unable to create files/ directory.';
                $redirectAdmin('forms');
            }

            $destPdf = $filesDir . '/' . $cleanFilename;
            if (@move_uploaded_file($tmpPdf, $destPdf)) {
                @chmod($destPdf, 0644);
                $_SESSION['flash_message'] = 'Updated PDF form: ' . $cleanFilename;
            } else {
                $_SESSION['flash_error'] = 'Failed to write uploaded PDF to files/' . $cleanFilename;
            }
            $redirectAdmin('forms');

        default:
            $_SESSION['flash_error'] = 'Unsupported admin action requested.';
            $redirectAdmin($returnTab);
    }
}

// --- Step 5: Load Current Datasets for Rendering ---
$flashMessage = (string)($_SESSION['flash_message'] ?? '');
$flashError = (string)($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

$activeTab = (string)($_GET['tab'] ?? 'events');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'events';
}

$siteConfig = erc_get_site_config();
$events = erc_load_json(ERC_DATA_DIR . '/events.json', []);
$signups = erc_load_json(ERC_DATA_DIR . '/signups.json', []);
$locations = erc_load_json(ERC_DATA_DIR . '/locations.json', []);
$geoCache = erc_load_json(ERC_DATA_DIR . '/geo_cache.json', []);
$board = erc_load_json(ERC_DATA_DIR . '/board.json', []);
$partners = erc_load_json(ERC_DATA_DIR . '/partners.json', []);
$testimonials = erc_load_json(ERC_DATA_DIR . '/testimonials.json', []);
$galleryImages = erc_scan_gallery_images();
$officialForms = erc_get_official_forms();

if (!is_array($events)) $events = [];
if (!is_array($signups)) $signups = [];
if (!is_array($locations)) $locations = [];
if (!is_array($geoCache)) $geoCache = [];
if (!is_array($board)) $board = [];
if (!is_array($partners)) $partners = [];
if (!is_array($testimonials)) $testimonials = [];

$totalSignups = 0;
foreach ($signups as $list) {
    if (is_array($list)) {
        $totalSignups += count($list);
    }
}

$geocodedCount = 0;
foreach ($locations as $loc) {
    if (!is_array($loc)) {
        continue;
    }
    $addr = (string)($loc['address'] ?? '');
    $c = $geoCache[$addr] ?? null;
    if (
        (isset($loc['lat'], $loc['lng']) && is_numeric($loc['lat']) && is_numeric($loc['lng']))
        || (is_array($c) && isset($c['lat'], $c['lng']) && is_numeric($c['lat']) && is_numeric($c['lng']))
    ) {
        $geocodedCount++;
    }
}

$isCoexistence = erc_is_coexistence_mode();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" id="metaThemeColorLight" content="#eaf0f8" media="(prefers-color-scheme: light)">
    <meta name="theme-color" id="metaThemeColorDark" content="#0b1117" media="(prefers-color-scheme: dark)">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= e(erc_media_url('e2e2rc_LOGO.png')) ?>">
    <title>Admin Operations Console | Lions District 2-E2 ERC</title>
    <script>
        (function () {
            try {
                var pref = localStorage.getItem('erc-color-scheme');
                var metaScheme = document.querySelector('meta[name="color-scheme"]');
                var metaLight = document.getElementById('metaThemeColorLight');
                var metaDark = document.getElementById('metaThemeColorDark');
                if (pref === 'light' || pref === 'dark') {
                    document.documentElement.setAttribute('data-theme', pref);
                    if (metaScheme) {
                        metaScheme.setAttribute('content', pref);
                    }
                    var resolvedHex = pref === 'dark' ? '#0b1117' : '#eaf0f8';
                    if (metaLight) { metaLight.setAttribute('content', resolvedHex); }
                    if (metaDark) { metaDark.setAttribute('content', resolvedHex); }
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="/fonts/fonts.css">
    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/pages/admin.css">
    <script type="module" src="/js/theme.js"></script>
    <script type="module" src="/js/pages/admin.js"></script>
</head>
<body class="admin-body">
    <a class="skip-link" href="#admin-main">Skip to admin workspace</a>

    <header class="admin-header">
        <div class="admin-header__inner">
            <div class="admin-brand">
                <img src="<?= e(erc_media_url('e2e2rc_LOGO.png')) ?>" alt="Lions District 2-E2 ERC emblem" width="44" height="44" class="admin-brand__logo">
                <div class="admin-brand__copy">
                    <div class="admin-brand__kicker">
                        <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
                        <span>2E2 OPS // WATAUGA HQ</span>
                    </div>
                    <h1 class="admin-brand__title">Admin Operations Console</h1>
                    <p class="admin-brand__subtitle">
                        Signed in as <strong><?= e($authConfig['user']) ?></strong>
                        <span class="admin-badge admin-badge--neutral"><?= e($authConfig['label']) ?></span>
                    </p>
                </div>
            </div>
            <div class="admin-header__actions">
                <a href="/" class="btn btn-secondary btn-sm">
                    <?= erc_icon('external-link', ['width' => 16, 'height' => 16]) ?>
                    <span>View Site</span>
                </a>
                <button
                    type="button"
                    id="themeToggle"
                    class="theme-toggle"
                    aria-label="Color theme: Auto. Click to cycle Auto, Light, or Dark."
                    title="Cycle color theme (Auto / Light / Dark)"
                    data-theme-pref="auto"
                >
                    <span class="theme-toggle__glyph" aria-hidden="true">&#9680;</span>
                    <span class="theme-toggle__label" id="themeToggleLabel">AUTO</span>
                </button>
            </div>
        </div>
    </header>

    <main id="admin-main" class="admin-main" tabindex="-1">
        <?php if ($flashMessage !== ''): ?>
            <div class="admin-alert admin-alert--success" role="status" aria-live="polite">
                <?= erc_icon('circle-check', ['width' => 20, 'height' => 20, 'class' => 'admin-alert__icon']) ?>
                <div class="admin-alert__body"><?= e($flashMessage) ?></div>
                <button type="button" class="admin-alert__close" data-dismiss-alert aria-label="Dismiss status message">
                    <?= erc_icon('x', ['width' => 18, 'height' => 18]) ?>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($flashError !== ''): ?>
            <div class="admin-alert admin-alert--danger" role="alert">
                <?= erc_icon('triangle-alert', ['width' => 20, 'height' => 20, 'class' => 'admin-alert__icon']) ?>
                <div class="admin-alert__body"><?= e($flashError) ?></div>
                <button type="button" class="admin-alert__close" data-dismiss-alert aria-label="Dismiss error message">
                    <?= erc_icon('x', ['width' => 18, 'height' => 18]) ?>
                </button>
            </div>
        <?php endif; ?>

        <section class="admin-kpis" aria-label="Operational Summary">
            <article class="kpi-card">
                <div class="kpi-card__head">
                    <span class="kpi-card__label">Volunteer Sessions</span>
                    <?= erc_icon('calendar-days', ['width' => 18, 'height' => 18, 'class' => 'kpi-card__icon']) ?>
                </div>
                <p class="kpi-card__value mono-num"><?= count($events) ?></p>
                <p class="kpi-card__meta"><?= $totalSignups ?> registered volunteer<?= $totalSignups === 1 ? '' : 's' ?></p>
            </article>
            <article class="kpi-card">
                <div class="kpi-card__head">
                    <span class="kpi-card__label">Drop-Off Locations</span>
                    <?= erc_icon('map-pin', ['width' => 18, 'height' => 18, 'class' => 'kpi-card__icon']) ?>
                </div>
                <p class="kpi-card__value mono-num"><?= count($locations) ?></p>
                <p class="kpi-card__meta"><span class="mono-num"><?= $geocodedCount ?></span> geocoded for map</p>
            </article>
            <article class="kpi-card">
                <div class="kpi-card__head">
                    <span class="kpi-card__label">Gallery Photos</span>
                    <?= erc_icon('image', ['width' => 18, 'height' => 18, 'class' => 'kpi-card__icon']) ?>
                </div>
                <p class="kpi-card__value mono-num"><?= count($galleryImages) ?></p>
                <p class="kpi-card__meta"><?= $isCoexistence ? 'Coexistence merge active' : 'Local gallery directory' ?></p>
            </article>
            <article class="kpi-card">
                <div class="kpi-card__head">
                    <span class="kpi-card__label">Leadership &amp; Partners</span>
                    <?= erc_icon('users', ['width' => 18, 'height' => 18, 'class' => 'kpi-card__icon']) ?>
                </div>
                <p class="kpi-card__value mono-num"><?= count($board) ?> / <?= count($partners) ?></p>
                <p class="kpi-card__meta">Board members / partner groups</p>
            </article>
        </section>

        <div class="admin-tabs-shell">
            <div class="admin-tablist" role="tablist" aria-label="Admin management sections">
                <?php
                $tabDefs = [
                    'events'    => ['label' => 'Events & Signups', 'icon' => 'calendar-check'],
                    'locations' => ['label' => 'Drop-Off Bins', 'icon' => 'map-pin'],
                    'gallery'   => ['label' => 'Gallery Photos', 'icon' => 'image'],
                    'board'     => ['label' => 'Board Roster', 'icon' => 'users'],
                    'content'   => ['label' => 'Partners & Quotes', 'icon' => 'heart-handshake'],
                    'forms'     => ['label' => 'PDF Forms', 'icon' => 'file-text'],
                    'settings'  => ['label' => 'Contact & Security', 'icon' => 'shield-check'],
                ];
                foreach ($tabDefs as $slug => $meta):
                    $isSelected = $activeTab === $slug;
                ?>
                    <button
                        type="button"
                        role="tab"
                        id="tab-btn-<?= e($slug) ?>"
                        class="admin-tab"
                        data-tab="<?= e($slug) ?>"
                        aria-controls="panel-<?= e($slug) ?>"
                        aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                        tabindex="<?= $isSelected ? '0' : '-1' ?>"
                    >
                        <?= erc_icon($meta['icon'], ['width' => 17, 'height' => 17]) ?>
                        <span><?= e($meta['label']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- PANEL 1: EVENTS & SIGNUPS -->
            <section
                role="tabpanel"
                id="panel-events"
                aria-labelledby="tab-btn-events"
                class="admin-panel"
                <?= $activeTab === 'events' ? '' : 'hidden' ?>
            >
                <div class="admin-split">
                    <article class="admin-card admin-card--sticky">
                        <div class="admin-card__head">
                            <h2 id="eventFormHeading">Add or Edit Volunteer Session</h2>
                            <button type="button" id="resetEventFormBtn" class="btn btn-secondary btn-sm">New Session</button>
                        </div>
                        <form id="eventEditorForm" method="POST" action="<?= e($adminSelfUrl) ?>" enctype="multipart/form-data" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_event">
                            <input type="hidden" name="return_tab" value="events">
                            <input type="hidden" name="id" id="ev_id" value="">
                            <input type="hidden" name="existing_image" id="ev_existing_image" value="images/placeholder-event.svg">

                            <div class="field">
                                <label for="ev_title">Session Title <span aria-hidden="true">*</span></label>
                                <input type="text" id="ev_title" name="title" required maxlength="150" placeholder="e.g., 2nd Saturday Open Session">
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label for="ev_date">Date <span aria-hidden="true">*</span></label>
                                    <input type="date" id="ev_date" name="date" required>
                                </div>
                                <div class="field">
                                    <label for="ev_time">Time Window <span aria-hidden="true">*</span></label>
                                    <input type="text" id="ev_time" name="time" required maxlength="80" value="12:00 PM - 3:00 PM">
                                </div>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label for="ev_max">Max Volunteers <span aria-hidden="true">*</span></label>
                                    <input type="number" id="ev_max" name="maxPeople" required min="1" max="500" value="15">
                                </div>
                                <div class="field">
                                    <label for="ev_contact">Organizer Email</label>
                                    <input type="email" id="ev_contact" name="contact" maxlength="254" inputmode="email" autocomplete="email" value="<?= e($siteConfig['email']) ?>">
                                </div>
                            </div>

                            <div class="field">
                                <label for="ev_location">Location <span aria-hidden="true">*</span></label>
                                <input type="text" id="ev_location" name="location" required maxlength="250" value="Main Center - 5621 Bunker Blvd, Watauga, TX 76148">
                            </div>

                            <div class="field">
                                <label for="ev_description">Description</label>
                                <textarea id="ev_description" name="description" rows="3" maxlength="2000"></textarea>
                            </div>

                            <div class="field">
                                <label for="ev_image">Session Image (optional)</label>
                                <p id="ev_image_hint" class="field-hint">JPG, PNG, GIF, or WebP up to 10 MB (auto-resized to max 1600px). Leave blank to keep <span id="ev_existing_label" class="mono-num">placeholder</span>.</p>
                                <input type="file" id="ev_image" name="image" accept="image/jpeg,image/png,image/gif,image/webp" aria-describedby="ev_image_hint">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="ev_submit_btn">Save Session</button>
                            </div>
                        </form>
                    </article>

                    <div class="admin-stack">
                        <div class="section-bar">
                            <h2>Scheduled Sessions (<span class="mono-num"><?= count($events) ?></span>)</h2>
                        </div>
                        <?php if (empty($events)): ?>
                            <article class="admin-card">
                                <p>No volunteer sessions are currently scheduled. Create one using the form.</p>
                            </article>
                        <?php else: ?>
                            <?php foreach ($events as $ev):
                                if (!is_array($ev)) continue;
                                $eid = (int)($ev['id'] ?? 0);
                                $evKey = (string)$eid;
                                $evRoster = isset($signups[$evKey]) && is_array($signups[$evKey]) ? $signups[$evKey] : [];
                                $evPeople = max(count($evRoster), (int)($ev['people'] ?? 0));
                                $evMax = (int)($ev['maxPeople'] ?? 0);
                                $evImg = erc_media_url((string)($ev['image'] ?? 'images/placeholder-event.svg'));
                            ?>
                                <article class="admin-card event-admin-card">
                                    <div class="event-admin-card__top">
                                        <img
                                            src="<?= e($evImg) ?>"
                                            alt=""
                                            width="72"
                                            height="72"
                                            loading="lazy"
                                            class="event-admin-card__thumb"
                                        >
                                        <div class="event-admin-card__info">
                                            <div class="event-admin-card__title-row">
                                                <h3><?= e((string)($ev['title'] ?? '')) ?></h3>
                                                <span class="admin-badge <?= ($evMax > 0 && $evPeople >= $evMax) ? 'admin-badge--warn' : 'admin-badge--success' ?> mono-num">
                                                    <?= $evPeople ?> / <?= $evMax ?> seats
                                                </span>
                                            </div>
                                            <p class="event-admin-card__meta">
                                                <span class="mono-num"><?= e((string)($ev['date'] ?? '')) ?></span>
                                                · <?= e((string)($ev['time'] ?? '')) ?>
                                            </p>
                                            <p class="event-admin-card__loc"><?= e((string)($ev['location'] ?? '')) ?></p>
                                            <?php if (!empty($ev['description'])): ?>
                                                <p class="event-admin-card__desc"><?= e((string)$ev['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="event-admin-card__actions">
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm js-edit-event"
                                            data-event="<?= e(json_encode([
                                                'id'          => $eid,
                                                'title'       => (string)($ev['title'] ?? ''),
                                                'date'        => (string)($ev['date'] ?? ''),
                                                'time'        => (string)($ev['time'] ?? ''),
                                                'maxPeople'   => $evMax,
                                                'location'    => (string)($ev['location'] ?? ''),
                                                'description' => (string)($ev['description'] ?? ''),
                                                'contact'     => (string)($ev['contact'] ?? ''),
                                                'image'       => (string)($ev['image'] ?? 'images/placeholder-event.svg'),
                                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"
                                        >
                                            <?= erc_icon('pencil', ['width' => 15, 'height' => 15]) ?>
                                            <span>Edit</span>
                                        </button>

                                        <form method="POST" action="<?= e($adminSelfUrl) ?>" class="inline-form" data-confirm-title="Delete Volunteer Session?" data-confirm-message="This permanently removes '<?= e((string)($ev['title'] ?? 'this session')) ?>' and all <?= count($evRoster) ?> registered attendee(s).">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="return_tab" value="events">
                                            <input type="hidden" name="id" value="<?= $eid ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <?= erc_icon('trash-2', ['width' => 15, 'height' => 15]) ?>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>

                                    <details class="attendee-disclosure">
                                        <summary>
                                            <span>Registered Volunteers (<span class="mono-num"><?= count($evRoster) ?></span>)</span>
                                        </summary>
                                        <div class="attendee-disclosure__body">
                                            <?php if (empty($evRoster)): ?>
                                                <p class="muted-note">No volunteers registered for this session yet.</p>
                                            <?php else: ?>
                                                <div tabindex="0" role="region" aria-label="Attendees for <?= e((string)($ev['title'] ?? '')) ?>" class="table-wrapper">
                                                    <table class="admin-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Email</th>
                                                                <th>Phone</th>
                                                                <th>Registered</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($evRoster as $sIdx => $signup): ?>
                                                                <tr>
                                                                    <th><?= e((string)($signup['name'] ?? '')) ?></th>
                                                                    <td><?= e((string)($signup['email'] ?? '')) ?></td>
                                                                    <td class="mono-num"><?= e((string)($signup['phone'] ?? '')) ?></td>
                                                                    <td class="mono-num"><?= e((string)($signup['timestamp'] ?? '')) ?></td>
                                                                    <td>
                                                                        <form method="POST" action="<?= e($adminSelfUrl) ?>" class="inline-form" data-confirm-title="Remove Attendee?" data-confirm-message="Remove <?= e((string)($signup['name'] ?? 'this volunteer')) ?> from this session?">
                                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                                            <input type="hidden" name="action" value="delete_signup">
                                                                            <input type="hidden" name="return_tab" value="events">
                                                                            <input type="hidden" name="event_id" value="<?= $eid ?>">
                                                                            <input type="hidden" name="index" value="<?= (int)$sIdx ?>">
                                                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- PANEL 2: DROP-OFF LOCATIONS -->
            <section
                role="tabpanel"
                id="panel-locations"
                aria-labelledby="tab-btn-locations"
                class="admin-panel"
                <?= $activeTab === 'locations' ? '' : 'hidden' ?>
            >
                <div class="admin-split">
                    <article class="admin-card admin-card--sticky">
                        <div class="admin-card__head">
                            <h2 id="locationFormHeading">Add or Edit Drop-Off Bin</h2>
                            <button type="button" id="resetLocationFormBtn" class="btn btn-secondary btn-sm">New Bin</button>
                        </div>
                        <form id="locationEditorForm" method="POST" action="<?= e($adminSelfUrl) ?>" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_location">
                            <input type="hidden" name="return_tab" value="locations">
                            <input type="hidden" name="index" id="loc_index" value="-1">

                            <div class="field">
                                <label for="loc_name">Location / Host Name <span aria-hidden="true">*</span></label>
                                <input type="text" id="loc_name" name="name" required maxlength="160" placeholder="e.g., Westlake Ace Hardware">
                            </div>

                            <div class="field">
                                <label for="loc_address">Street Address, City, State ZIP <span aria-hidden="true">*</span></label>
                                <p id="loc_address_hint" class="field-hint">New addresses are automatically geocoded into geo_cache.json via OpenStreetMap Nominatim or US Census.</p>
                                <input type="text" id="loc_address" name="address" required maxlength="260" aria-describedby="loc_address_hint" placeholder="e.g., 5621 Bunker Blvd, Watauga, TX 76148">
                            </div>

                            <div class="field">
                                <label for="loc_phone">Phone (optional)</label>
                                <input type="tel" id="loc_phone" name="phone" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="(817) 555-0100">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="loc_submit_btn">Save Location</button>
                            </div>
                        </form>
                    </article>

                    <article class="admin-card">
                        <div class="section-bar">
                            <h2 id="locations-table-heading">Directory (<span id="locationsCountVisible" class="mono-num"><?= count($locations) ?></span> of <span class="mono-num"><?= count($locations) ?></span>)</h2>
                        </div>

                        <search class="filter-bar">
                            <div class="field filter-bar__search">
                                <label for="locationSearchInput">Search locations by name, street, city, or phone</label>
                                <input type="search" id="locationSearchInput" placeholder="Filter 214 DFW locations..." autocomplete="off">
                            </div>
                            <div class="field filter-bar__select">
                                <label for="locationGeoFilter">Map status</label>
                                <select id="locationGeoFilter">
                                    <option value="all">All locations</option>
                                    <option value="geocoded">Geocoded on map</option>
                                    <option value="unmatched">Needs coordinates</option>
                                </select>
                            </div>
                        </search>

                        <div tabindex="0" role="region" aria-labelledby="locations-table-heading" class="table-wrapper table-wrapper--tall">
                            <table class="admin-table admin-table--locations" id="locationsAdminTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Phone</th>
                                        <th>Map</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locations as $idx => $loc):
                                        if (!is_array($loc)) continue;
                                        $lName = (string)($loc['name'] ?? '');
                                        $lAddr = (string)($loc['address'] ?? '');
                                        $lPhone = (string)($loc['phone'] ?? '');
                                        $c = $geoCache[$lAddr] ?? null;
                                        $isGeocoded = (isset($loc['lat'], $loc['lng']) && is_numeric($loc['lat']) && is_numeric($loc['lng']))
                                            || (is_array($c) && isset($c['lat'], $c['lng']) && is_numeric($c['lat']) && is_numeric($c['lng']));
                                    ?>
                                        <tr data-geo="<?= $isGeocoded ? 'geocoded' : 'unmatched' ?>" data-search="<?= e(strtolower($lName . ' ' . $lAddr . ' ' . $lPhone)) ?>">
                                            <th><?= e($lName) ?></th>
                                            <td><?= e($lAddr) ?></td>
                                            <td class="mono-num"><?= $lPhone !== '' ? e($lPhone) : '—' ?></td>
                                            <td>
                                                <?php if ($isGeocoded): ?>
                                                    <span class="admin-badge admin-badge--success">Mapped</span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge--warn">Directory</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary btn-sm js-edit-location"
                                                        data-location="<?= e(json_encode([
                                                            'index'   => (int)$idx,
                                                            'name'    => $lName,
                                                            'address' => $lAddr,
                                                            'phone'   => $lPhone,
                                                        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"
                                                    >
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="<?= e($adminSelfUrl) ?>" class="inline-form" data-confirm-title="Delete Drop-Off Bin?" data-confirm-message="Remove '<?= e($lName) ?>' (<?= e($lAddr) ?>) from the public directory?">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="delete_location">
                                                        <input type="hidden" name="return_tab" value="locations">
                                                        <input type="hidden" name="index" value="<?= (int)$idx ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
            </section>

            <!-- PANEL 3: GALLERY PHOTOS -->
            <section
                role="tabpanel"
                id="panel-gallery"
                aria-labelledby="tab-btn-gallery"
                class="admin-panel"
                <?= $activeTab === 'gallery' ? '' : 'hidden' ?>
            >
                <article class="admin-card">
                    <h2>Upload Community Gallery Photos</h2>
                    <form method="POST" action="<?= e($adminSelfUrl) ?>" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="upload_gallery">
                        <input type="hidden" name="return_tab" value="gallery">
                        <div class="field">
                            <label for="gallery_photos_input">Select one or more photos</label>
                            <p id="gallery_upload_hint" class="field-hint">Accepted: JPG, PNG, GIF, WebP up to 10 MB each. Images are automatically oriented via EXIF, resized to max 1600px, and paired with a `.webp` sidecar.</p>
                            <input type="file" id="gallery_photos_input" name="gallery_photos[]" multiple required accept="image/jpeg,image/png,image/gif,image/webp" aria-describedby="gallery_upload_hint">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= erc_icon('upload', ['width' => 16, 'height' => 16]) ?>
                                <span>Upload &amp; Optimize Photos</span>
                            </button>
                        </div>
                    </form>
                </article>

                <div class="section-bar">
                    <h2>Gallery Inventory (<span class="mono-num"><?= count($galleryImages) ?></span>)</h2>
                </div>
                <div class="gallery-admin-grid">
                    <?php foreach ($galleryImages as $relPhoto):
                        $isLocalOverride = is_file(ERC_ROOT_DIR . '/' . $relPhoto);
                        $filenameOnly = basename($relPhoto);
                    ?>
                        <article class="admin-card gallery-admin-card">
                            <img src="<?= e(erc_media_url($relPhoto)) ?>" alt="<?= e($filenameOnly) ?>" loading="lazy" class="gallery-admin-card__img">
                            <div class="gallery-admin-card__meta">
                                <span class="gallery-admin-card__name" title="<?= e($filenameOnly) ?>"><?= e($filenameOnly) ?></span>
                                <?php if ($isCoexistence && !$isLocalOverride): ?>
                                    <span class="admin-badge admin-badge--neutral">Shared Root</span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="<?= e($adminSelfUrl) ?>" data-confirm-title="Delete Gallery Photo?" data-confirm-message="Delete <?= e($filenameOnly) ?> and its .webp sidecar from the gallery?">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_gallery">
                                <input type="hidden" name="return_tab" value="gallery">
                                <input type="hidden" name="photo" value="<?= e($relPhoto) ?>">
                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm btn-block"
                                    <?= ($isCoexistence && !$isLocalOverride) ? 'disabled aria-disabled="true" title="Shared root media is read-only during :8770 coexistence"' : '' ?>
                                >
                                    <?= erc_icon('trash-2', ['width' => 15, 'height' => 15]) ?>
                                    <span>Delete Photo</span>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- PANEL 4: BOARD ROSTER -->
            <section
                role="tabpanel"
                id="panel-board"
                aria-labelledby="tab-btn-board"
                class="admin-panel"
                <?= $activeTab === 'board' ? '' : 'hidden' ?>
            >
                <article class="admin-card">
                    <div class="admin-card__head">
                        <div>
                            <h2>Board of Directors Roster</h2>
                            <p class="field-hint">Portraits are auto-resized to 400×400 and paired with WebP sidecars.</p>
                        </div>
                        <button type="button" id="addBoardMemberBtn" class="btn btn-secondary btn-sm">
                            <?= erc_icon('plus', ['width' => 16, 'height' => 16]) ?>
                            <span>Add Member</span>
                        </button>
                    </div>

                    <form method="POST" action="<?= e($adminSelfUrl) ?>" enctype="multipart/form-data" class="admin-form" id="boardRosterForm">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="save_board">
                        <input type="hidden" name="return_tab" value="board">

                        <div id="boardRowsList" class="board-admin-list">
                            <?php foreach ($board as $bIdx => $member):
                                if (!is_array($member)) continue;
                                $mName = (string)($member['name'] ?? '');
                                $mImg = (string)($member['image'] ?? 'images/placeholder-volunteer.svg');
                                $mTag = ((string)($member['tag'] ?? 'current')) === 'previous' ? 'previous' : 'current';
                            ?>
                                <div class="board-admin-row">
                                    <img src="<?= e(erc_media_url($mImg)) ?>" alt="" width="56" height="56" loading="lazy" class="board-admin-row__avatar">
                                    <div class="board-admin-row__fields">
                                        <div class="field">
                                            <label for="board_name_<?= (int)$bIdx ?>">Member Name</label>
                                            <input type="text" id="board_name_<?= (int)$bIdx ?>" name="names[]" value="<?= e($mName) ?>" required maxlength="100">
                                        </div>
                                        <div class="field">
                                            <label for="board_tag_<?= (int)$bIdx ?>">Board Group</label>
                                            <select id="board_tag_<?= (int)$bIdx ?>" name="tags[]">
                                                <option value="current" <?= $mTag === 'current' ? 'selected' : '' ?>>Current Board</option>
                                                <option value="previous" <?= $mTag === 'previous' ? 'selected' : '' ?>>Previous Board</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for="board_file_<?= (int)$bIdx ?>">Replace Portrait (optional)</label>
                                            <input type="hidden" name="images[]" value="<?= e($mImg) ?>">
                                            <input type="file" id="board_file_<?= (int)$bIdx ?>" name="board_files[]" accept="image/jpeg,image/png,image/gif,image/webp">
                                        </div>
                                    </div>
                                    <div class="board-admin-row__controls">
                                        <button type="button" class="btn btn-secondary btn-sm js-move-board-up" aria-label="Move member up">Up</button>
                                        <button type="button" class="btn btn-secondary btn-sm js-move-board-down" aria-label="Move member down">Down</button>
                                        <button type="button" class="btn btn-danger btn-sm js-remove-board-row" aria-label="Remove member row">Remove</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Board Roster</button>
                        </div>
                    </form>
                </article>
            </section>

            <!-- PANEL 5: PARTNERS & TESTIMONIALS -->
            <section
                role="tabpanel"
                id="panel-content"
                aria-labelledby="tab-btn-content"
                class="admin-panel"
                <?= $activeTab === 'content' ? '' : 'hidden' ?>
            >
                <div class="admin-split">
                    <article class="admin-card">
                        <h2>Partners &amp; Friends (<span id="partnersLineCount" class="mono-num"><?= count($partners) ?></span>)</h2>
                        <form method="POST" action="<?= e($adminSelfUrl) ?>" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_partners">
                            <input type="hidden" name="return_tab" value="content">

                            <div class="field">
                                <label for="partnersTextarea">Partner Organization Names</label>
                                <p id="partners_hint" class="field-hint">Enter one organization name per line. Blank lines are ignored automatically.</p>
                                <textarea id="partnersTextarea" name="partners" rows="16" aria-describedby="partners_hint"><?= e(implode("\n", array_map('strval', $partners))) ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Partners</button>
                            </div>
                        </form>
                    </article>

                    <article class="admin-card">
                        <div class="admin-card__head">
                            <h2>Partner Testimonials (<span class="mono-num"><?= count($testimonials) ?></span>)</h2>
                            <button type="button" id="addTestimonialBtn" class="btn btn-secondary btn-sm">
                                <?= erc_icon('plus', ['width' => 16, 'height' => 16]) ?>
                                <span>Add Quote</span>
                            </button>
                        </div>
                        <form method="POST" action="<?= e($adminSelfUrl) ?>" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_testimonials">
                            <input type="hidden" name="return_tab" value="content">

                            <div id="testimonialsList" class="admin-stack">
                                <?php foreach ($testimonials as $tIdx => $t):
                                    if (!is_array($t)) continue;
                                ?>
                                    <fieldset class="testimonial-fieldset">
                                        <legend>Quote #<?= (int)$tIdx + 1 ?></legend>
                                        <div class="field">
                                            <label for="t_author_<?= (int)$tIdx ?>">Author</label>
                                            <input type="text" id="t_author_<?= (int)$tIdx ?>" name="authors[]" value="<?= e((string)($t['author'] ?? '')) ?>" maxlength="120">
                                        </div>
                                        <div class="field">
                                            <label for="t_org_<?= (int)$tIdx ?>">Organization / Clinic</label>
                                            <input type="text" id="t_org_<?= (int)$tIdx ?>" name="orgs[]" value="<?= e((string)($t['organization'] ?? '')) ?>" maxlength="160">
                                        </div>
                                        <div class="field">
                                            <label for="t_text_<?= (int)$tIdx ?>">Quotation Text</label>
                                            <textarea id="t_text_<?= (int)$tIdx ?>" name="texts[]" rows="4" required><?= e((string)($t['text'] ?? '')) ?></textarea>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm js-remove-testimonial">Remove Quote</button>
                                    </fieldset>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Testimonials</button>
                            </div>
                        </form>
                    </article>
                </div>
            </section>

            <!-- PANEL 6: OFFICIAL PDF FORMS -->
            <section
                role="tabpanel"
                id="panel-forms"
                aria-labelledby="tab-btn-forms"
                class="admin-panel"
                <?= $activeTab === 'forms' ? '' : 'hidden' ?>
            >
                <div class="section-bar">
                    <h2>Downloadable PDF Forms (Max 20 MB, %PDF- Verified)</h2>
                </div>
                <div class="forms-admin-grid">
                    <?php foreach ($officialForms as $formInfo):
                        $fName = $formInfo['filename'];
                        $sizeKb = $formInfo['size'] > 0 ? number_format($formInfo['size'] / 1024, 1) . ' KB' : 'Missing';
                        $dateStr = $formInfo['mtime'] > 0 ? date('M j, Y g:i A', $formInfo['mtime']) : 'Not uploaded';
                    ?>
                        <article class="admin-card">
                            <div class="admin-card__head">
                                <div>
                                    <h3 class="mono-num"><?= e($fName) ?></h3>
                                    <p class="field-hint"><?= e($formInfo['label']) ?></p>
                                </div>
                                <a href="<?= e('/files/' . $fName) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                    <?= erc_icon('download', ['width' => 15, 'height' => 15]) ?>
                                    <span>View</span>
                                </a>
                            </div>

                            <p class="pdf-meta-line">
                                <span class="admin-badge <?= $formInfo['exists'] ? 'admin-badge--success' : 'admin-badge--warn' ?> mono-num"><?= e($sizeKb) ?></span>
                                <span class="muted-note">Updated: <?= e($dateStr) ?></span>
                                <?php if ($formInfo['overridden'] && $isCoexistence): ?>
                                    <span class="admin-badge admin-badge--neutral">Redesign Copy</span>
                                <?php endif; ?>
                            </p>

                            <form method="POST" action="<?= e($adminSelfUrl) ?>" enctype="multipart/form-data" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="upload_form">
                                <input type="hidden" name="return_tab" value="forms">
                                <input type="hidden" name="target_filename" value="<?= e($fName) ?>">

                                <div class="field">
                                    <label for="pdf_upload_<?= e(preg_replace('/[^a-zA-Z0-9]/', '_', $fName) ?? 'file') ?>">Replace <?= e($fName) ?></label>
                                    <input
                                        type="file"
                                        id="pdf_upload_<?= e(preg_replace('/[^a-zA-Z0-9]/', '_', $fName) ?? 'file') ?>"
                                        name="new_form"
                                        accept=".pdf,application/pdf"
                                        required
                                    >
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Replace PDF</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- PANEL 7: SITE CONTACT & SECURITY -->
            <section
                role="tabpanel"
                id="panel-settings"
                aria-labelledby="tab-btn-settings"
                class="admin-panel"
                <?= $activeTab === 'settings' ? '' : 'hidden' ?>
            >
                <div class="admin-split">
                    <article class="admin-card">
                        <h2>Public Contact Configuration</h2>
                        <form method="POST" action="<?= e($adminSelfUrl) ?>" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_config">
                            <input type="hidden" name="return_tab" value="settings">

                            <div class="field">
                                <label for="global_email">Contact &amp; Inbound Notification Email <span aria-hidden="true">*</span></label>
                                <p id="global_email_hint" class="field-hint">Displayed publicly on the website and used as the destination for contact inquiries and volunteer signup alerts.</p>
                                <input type="email" id="global_email" name="global_email" required maxlength="254" inputmode="email" autocomplete="email" value="<?= e($siteConfig['email']) ?>" aria-describedby="global_email_hint">
                            </div>

                            <div class="field">
                                <label for="global_phone">Public Phone Number <span aria-hidden="true">*</span></label>
                                <p id="global_phone_hint" class="field-hint">Current normalized tel link: <span class="mono-num"><?= e($siteConfig['phoneLink']) ?></span></p>
                                <input type="tel" id="global_phone" name="global_phone" required maxlength="40" inputmode="tel" autocomplete="tel" value="<?= e($siteConfig['phone']) ?>" aria-describedby="global_phone_hint">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Contact Settings</button>
                            </div>
                        </form>
                    </article>

                    <article class="admin-card">
                        <h2>Rotate Admin Credentials</h2>
                        <p class="field-hint">Active authentication source: <strong><?= e($authConfig['label']) ?></strong>. Saving writes a bcrypt hash to <code class="mono-num">data/admin-auth.json</code>.</p>
                        <form method="POST" action="<?= e($adminSelfUrl) ?>" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="change_admin_password">
                            <input type="hidden" name="return_tab" value="settings">

                            <div class="field">
                                <label for="admin_username">Admin Username <span aria-hidden="true">*</span></label>
                                <input type="text" id="admin_username" name="admin_username" required maxlength="64" autocomplete="username" value="<?= e($authConfig['user']) ?>">
                            </div>

                            <div class="field">
                                <label for="current_password">Current Password <span aria-hidden="true">*</span></label>
                                <div class="password-input-wrap">
                                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                                    <button type="button" class="btn btn-secondary btn-sm js-toggle-password" data-target="current_password" aria-pressed="false" aria-label="Show current password">Show</button>
                                </div>
                            </div>

                            <div class="field">
                                <label for="new_password">New Password <span aria-hidden="true">*</span></label>
                                <p id="new_pass_hint" class="field-hint">Minimum 8 characters.</p>
                                <div class="password-input-wrap">
                                    <input type="password" id="new_password" name="new_password" required minlength="8" maxlength="256" autocomplete="new-password" aria-describedby="new_pass_hint">
                                    <button type="button" class="btn btn-secondary btn-sm js-toggle-password" data-target="new_password" aria-pressed="false" aria-label="Show new password">Show</button>
                                </div>
                            </div>

                            <div class="field">
                                <label for="confirm_password">Confirm New Password <span aria-hidden="true">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" maxlength="256" autocomplete="new-password">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Update Admin Password</button>
                            </div>
                        </form>
                    </article>
                </div>
            </section>
        </div>
    </main>

    <!-- Native Confirmation Modal (no role="dialog" or aria-modal per Baseline 2026 guidance) -->
    <dialog id="confirmDeleteDialog" class="admin-dialog" closedby="any" aria-labelledby="confirmDeleteTitle">
        <div class="admin-dialog__surface">
            <h2 id="confirmDeleteTitle">Confirm Deletion</h2>
            <p id="confirmDeleteBody">Are you sure you want to proceed with this action?</p>
            <div class="admin-dialog__actions">
                <form method="dialog">
                    <button type="submit" value="cancel" class="btn btn-secondary" autofocus>Cancel</button>
                </form>
                <button type="button" id="confirmDeleteProceedBtn" class="btn btn-danger">Confirm Delete</button>
            </div>
        </div>
    </dialog>

    <template id="boardRowTemplate">
        <div class="board-admin-row">
            <img src="/images/placeholder-volunteer.svg" alt="" width="56" height="56" class="board-admin-row__avatar">
            <div class="board-admin-row__fields">
                <div class="field">
                    <label>Member Name <input type="text" name="names[]" required maxlength="100" placeholder="Full Name"></label>
                </div>
                <div class="field">
                    <label>Board Group
                        <select name="tags[]">
                            <option value="current" selected>Current Board</option>
                            <option value="previous">Previous Board</option>
                        </select>
                    </label>
                </div>
                <div class="field">
                    <label>Portrait Image (optional)
                        <input type="hidden" name="images[]" value="images/placeholder-volunteer.svg">
                        <input type="file" name="board_files[]" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                </div>
            </div>
            <div class="board-admin-row__controls">
                <button type="button" class="btn btn-secondary btn-sm js-move-board-up">Up</button>
                <button type="button" class="btn btn-secondary btn-sm js-move-board-down">Down</button>
                <button type="button" class="btn btn-danger btn-sm js-remove-board-row">Remove</button>
            </div>
        </div>
    </template>

    <template id="testimonialTemplate">
        <fieldset class="testimonial-fieldset">
            <legend>New Quote</legend>
            <div class="field">
                <label>Author <input type="text" name="authors[]" maxlength="120" placeholder="Author Name"></label>
            </div>
            <div class="field">
                <label>Organization / Clinic <input type="text" name="orgs[]" maxlength="160" placeholder="Mission or Partner Group"></label>
            </div>
            <div class="field">
                <label>Quotation Text <textarea name="texts[]" rows="4" required placeholder="Verbatim partner quote..."></textarea></label>
            </div>
            <button type="button" class="btn btn-danger btn-sm js-remove-testimonial">Remove Quote</button>
        </fieldset>
    </template>
</body>
</html>
