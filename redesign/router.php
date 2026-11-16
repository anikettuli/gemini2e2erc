<?php
declare(strict_types=1);

/**
 * Local PHP built-in server router (`php -S 127.0.0.1:8771 -t redesign redesign/router.php`).
 * Mirrors Apache `.htaccess` + `data/.htaccess` access rules, extensionless clean URLs,
 * 301 legacy `.php` / `/index.html` redirects, WebP negotiation, `.mjs` MIME headers,
 * and read-only coexistence fallback to parent root media.
 */

$rootDir = __DIR__;
$rootReal = realpath($rootDir) ?: $rootDir;
$parentReal = realpath(dirname($rootDir));

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$rawPath = parse_url($rawUri, PHP_URL_PATH);
$rawQuery = parse_url($rawUri, PHP_URL_QUERY);
$querySuffix = (is_string($rawQuery) && $rawQuery !== '') ? ('?' . $rawQuery) : '';

$uriPath = rawurldecode(is_string($rawPath) ? $rawPath : '/');
$uriPath = str_replace('\\', '/', $uriPath);
if ($uriPath === '' || $uriPath[0] !== '/') {
    $uriPath = '/' . $uriPath;
}

/**
 * Send baseline security headers on every HTTP response.
 */
$sendBaseHeaders = static function (): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(self), microphone=()');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline' https://tiles.openfreemap.org; "
        . "font-src 'self' data:; "
        . "img-src 'self' data: blob: https://tiles.openfreemap.org; "
        . "connect-src 'self' https://tiles.openfreemap.org; "
        . "worker-src 'self' blob:; "
        . "child-src 'self' blob:; "
        . "frame-ancestors 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "form-action 'self'"
    );
};

$denyForbidden = static function () use ($sendBaseHeaders): void {
    $sendBaseHeaders();
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "403 Forbidden\n";
    exit;
};

// Reject null bytes or directory traversal segments
if (str_contains($uriPath, "\0") || preg_match('#(?:^|/)\.\.(?:/|$)#', $uriPath)) {
    $denyForbidden();
}

// Block dotfiles (except /.well-known/)
if (preg_match('#(?:^|/)\.(?!well-known(?:/|$))#', $uriPath)) {
    $denyForbidden();
}

// Block protected directories
if (preg_match('#^/(?:includes|vendor/phpmailer|docs|scripts)(?:/|$)#i', $uriPath)) {
    $denyForbidden();
}

// Block internal PHP files and sensitive file extensions
if (
    preg_match('#^/(?:config|mail-utils|router)\.php$#i', $uriPath)
    || preg_match('#(?:\.(?:log|lock|md|bak|sql|swp)|~$|\.tmp\.[^/]*)$#i', $uriPath)
) {
    $denyForbidden();
}

// Enforce data/.htaccess parity: allow ONLY public non-PII JSON files
if (preg_match('#^/data(?:/|$)#i', $uriPath)) {
    if (!preg_match('#^/data/(?:board|events|partners|testimonials)\.json$#', $uriPath)) {
        $denyForbidden();
    }
}

// Legacy /index.html -> 301 /
if (strtolower($uriPath) === '/index.html') {
    $sendBaseHeaders();
    header('Location: /' . $querySuffix, true, 301);
    exit;
}

// Legacy standalone .php routes -> 301 clean paths (preserving query string)
if (preg_match('#^/(donate|locations|volunteer|missions|about|contact)\.php$#i', $uriPath, $legacyPhp)) {
    $sendBaseHeaders();
    header('Location: /' . strtolower($legacyPhp[1]) . $querySuffix, true, 301);
    exit;
}

// Unified single-scroll shell routes + admin/API endpoints
$cleanRouteMap = [
    '/'           => 'index.php',
    '/donate'     => 'index.php',
    '/locations'  => 'index.php',
    '/volunteer'  => 'index.php',
    '/missions'   => 'index.php',
    '/about'      => 'index.php',
    '/contact'    => 'index.php',
    '/admin'      => 'admin-view.php',
];

$normalizedRoute = $uriPath === '/' ? '/' : strtolower(rtrim($uriPath, '/'));
$targetPhp = null;

if (isset($cleanRouteMap[$normalizedRoute])) {
    $targetPhp = $rootReal . DIRECTORY_SEPARATOR . $cleanRouteMap[$normalizedRoute];
} elseif (preg_match('#^/(index|admin-view|get-config|get-gallery-images|get-locations|register-volunteer|send-email)\.php$#', $uriPath, $m)) {
    $targetPhp = $rootReal . DIRECTORY_SEPARATOR . $m[1] . '.php';
} elseif (str_ends_with(strtolower($uriPath), '.php')) {
    $sendBaseHeaders();
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "404 Not Found\n";
    exit;
}

if ($targetPhp !== null) {
    if (!is_file($targetPhp)) {
        $sendBaseHeaders();
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "404 Not Found\n";
        exit;
    }
    $_SERVER['SCRIPT_NAME'] = '/' . basename($targetPhp);
    $_SERVER['SCRIPT_FILENAME'] = $targetPhp;
    require $targetPhp;
    return true;
}

// Resolve static file inside redesign/ first, then coexistence parent root media
$relPath = ltrim($uriPath, '/');
$acceptsWebp = str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'image/webp');

$resolveStaticInBase = static function (string $baseReal, string $relative) use ($acceptsWebp): ?array {
    $nativeRel = str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $candidate = realpath($baseReal . DIRECTORY_SEPARATOR . $nativeRel);
    if ($candidate === false || !is_file($candidate) || !str_starts_with($candidate, $baseReal . DIRECTORY_SEPARATOR)) {
        return null;
    }

    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    if ($acceptsWebp && in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $webpRel = preg_replace('/\.[^.]+$/', '.webp', $relative);
        if (is_string($webpRel)) {
            $webpCand = realpath($baseReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $webpRel));
            if ($webpCand !== false && is_file($webpCand) && str_starts_with($webpCand, $baseReal . DIRECTORY_SEPARATOR)) {
                return ['path' => $webpCand, 'ext' => 'webp', 'vary' => true];
            }
        }
    }

    return ['path' => $candidate, 'ext' => $ext, 'vary' => false];
};

$resolved = null;
if ($relPath !== '') {
    $resolved = $resolveStaticInBase($rootReal, $relPath);
    if (
        $resolved === null
        && $parentReal !== false
        && $parentReal !== $rootReal
        && basename($rootReal) === 'redesign'
        && preg_match('#^(?:e2e2rc_LOGO\.png|(?:files|gallery|images)/.+)$#', $relPath)
    ) {
        $resolved = $resolveStaticInBase($parentReal, $relPath);
    }
    if ($resolved === null && $relPath === 'favicon.ico') {
        $resolved = $resolveStaticInBase($rootReal, 'e2e2rc_LOGO.png');
        if (
            $resolved === null
            && $parentReal !== false
            && $parentReal !== $rootReal
            && basename($rootReal) === 'redesign'
        ) {
            $resolved = $resolveStaticInBase($parentReal, 'e2e2rc_LOGO.png');
        }
    }
}

if ($resolved === null) {
    $sendBaseHeaders();
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "404 Not Found\n";
    exit;
}

$mimeMap = [
    'mjs'   => 'text/javascript; charset=UTF-8',
    'js'    => 'text/javascript; charset=UTF-8',
    'css'   => 'text/css; charset=UTF-8',
    'json'  => 'application/json; charset=UTF-8',
    'woff2' => 'font/woff2',
    'woff'  => 'font/woff',
    'svg'   => 'image/svg+xml',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'webp'  => 'image/webp',
    'pdf'   => 'application/pdf',
    'ico'   => 'image/x-icon',
    'txt'   => 'text/plain; charset=UTF-8',
    'html'  => 'text/html; charset=UTF-8',
];

$ext = $resolved['ext'];
$filePath = $resolved['path'];
$contentType = $mimeMap[$ext] ?? '';
if ($contentType === '' && function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $detected = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        if (is_string($detected) && $detected !== '') {
            $contentType = $detected;
        }
    }
}
if ($contentType === '') {
    $contentType = 'application/octet-stream';
}

$sendBaseHeaders();
header('Content-Type: ' . $contentType);
$size = filesize($filePath);
if ($size !== false) {
    header('Content-Length: ' . (string)$size);
}
if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    header('Vary: Accept');
}
if ($ext === 'json') {
    header('Cache-Control: no-cache, must-revalidate');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
    readfile($filePath);
}
return true;
