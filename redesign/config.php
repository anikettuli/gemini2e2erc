<?php
declare(strict_types=1);

date_default_timezone_set('America/Chicago');

if (!defined('ERC_ROOT_DIR')) {
    define('ERC_ROOT_DIR', __DIR__);
}
if (!defined('ERC_DATA_DIR')) {
    define('ERC_DATA_DIR', __DIR__ . '/data');
}
if (!defined('ERC_ASSET_BASE_DIR')) {
    define('ERC_ASSET_BASE_DIR', is_dir(__DIR__ . '/images') ? __DIR__ : dirname(__DIR__));
}

/**
 * Determine whether the site is currently running inside `redesign/` alongside
 * read-only shared media at the parent repository root.
 */
function erc_is_coexistence_mode(): bool
{
    $parent = dirname(ERC_ROOT_DIR);
    if ($parent === ERC_ROOT_DIR || basename(ERC_ROOT_DIR) !== 'redesign') {
        return false;
    }

    return is_file($parent . '/e2e2rc_LOGO.png')
        || is_dir($parent . '/images')
        || is_dir($parent . '/gallery')
        || is_dir($parent . '/files');
}

/**
 * Resolve a readable static asset path, preferring ERC_ROOT_DIR overrides and
 * falling back to read-only parent root media during coexistence preview.
 */
function erc_resolve_readable_asset(string $relativePath): ?string
{
    $clean = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
    if ($clean === '' || str_contains($clean, "\0") || preg_match('#(?:^|/)\.\.(?:/|$)#', $clean)) {
        return null;
    }

    $candidates = [ERC_ROOT_DIR];
    if (erc_is_coexistence_mode()) {
        $candidates[] = dirname(ERC_ROOT_DIR);
    }

    foreach ($candidates as $baseDir) {
        $baseReal = realpath($baseDir);
        if ($baseReal === false) {
            continue;
        }
        $targetReal = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean));
        if (
            $targetReal !== false
            && is_file($targetReal)
            && str_starts_with($targetReal, $baseReal . DIRECTORY_SEPARATOR)
        ) {
            return $targetReal;
        }
    }

    return null;
}

/**
 * Parse and statically cache `.env` variables from ERC_ROOT_DIR . '/.env'.
 *
 * @return array<string, string>
 */
function erc_load_env(): array
{
    static $env = null;
    if ($env !== null) {
        return $env;
    }

    $env = [];
    $envPath = ERC_ROOT_DIR . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return $env;
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return $env;
    }

    foreach ($lines as $rawLine) {
        $line = trim($rawLine);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (preg_match('/^export\s+(.+)$/i', $line, $m)) {
            $line = trim($m[1]);
        }

        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $eqPos));
        if ($key === '') {
            continue;
        }

        $val = trim(substr($line, $eqPos + 1));
        $len = strlen($val);
        if ($len >= 2) {
            $first = $val[0];
            $last = $val[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $val = substr($val, 1, -1);
            }
        }

        $env[$key] = $val;
    }

    return $env;
}

/**
 * Normalize a phone number string to E.164 `+1...` for tel: links.
 */
function erc_format_phone_tel(string $phone): string
{
    $trimmed = trim($phone);
    $digits = preg_replace('/\D+/', '', $trimmed);
    if (!is_string($digits) || $digits === '') {
        return '+18177105403';
    }

    if (str_starts_with($trimmed, '+') && strlen($digits) >= 7) {
        return '+' . $digits;
    }
    if (strlen($digits) === 10) {
        return '+1' . $digits;
    }
    if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
        return '+' . $digits;
    }
    if (strlen($digits) > 10) {
        return '+' . $digits;
    }
    if (strlen($digits) >= 7) {
        return '+1' . $digits;
    }

    return '+18177105403';
}

/**
 * Resolve runtime public contact configuration (`email`, `phone`, `phoneLink`).
 *
 * @return array{email: string, phone: string, phoneLink: string}
 */
function erc_get_site_config(): array
{
    $env = erc_load_env();
    $stored = erc_load_json(ERC_DATA_DIR . '/site-config.json', []);
    if (!is_array($stored)) {
        $stored = [];
    }

    $email = '';
    if (isset($stored['email']) && is_string($stored['email']) && trim($stored['email']) !== '') {
        $email = trim($stored['email']);
    } elseif (isset($env['GLOBAL_EMAIL']) && trim($env['GLOBAL_EMAIL']) !== '') {
        $email = trim($env['GLOBAL_EMAIL']);
    } else {
        $email = '2e2erc1854@gmail.com';
    }

    $phone = '';
    if (isset($stored['phone']) && is_string($stored['phone']) && trim($stored['phone']) !== '') {
        $phone = trim($stored['phone']);
    } elseif (isset($env['GLOBAL_PHONE']) && trim($env['GLOBAL_PHONE']) !== '') {
        $phone = trim($env['GLOBAL_PHONE']);
    } else {
        $phone = '(817) 710-5403';
    }

    return [
        'email'     => $email,
        'phone'     => $phone,
        'phoneLink' => erc_format_phone_tel($phone),
    ];
}

/**
 * Resolve inbound admin notification email recipient.
 */
function erc_get_admin_email(): string
{
    $env = erc_load_env();
    $stored = erc_load_json(ERC_DATA_DIR . '/site-config.json', []);
    if (is_array($stored) && isset($stored['email']) && is_string($stored['email']) && trim($stored['email']) !== '') {
        return trim($stored['email']);
    }
    if (isset($env['ADMIN_EMAIL']) && trim($env['ADMIN_EMAIL']) !== '') {
        return trim($env['ADMIN_EMAIL']);
    }
    if (isset($env['GLOBAL_EMAIL']) && trim($env['GLOBAL_EMAIL']) !== '') {
        return trim($env['GLOBAL_EMAIL']);
    }
    return '2e2erc1854@gmail.com';
}

/**
 * Execute a callback while holding a re-entrant exclusive lock on `data/write.lock`.
 */
function erc_with_storage_lock(callable $callback): mixed
{
    static $depth = 0;

    if ($depth > 0) {
        $depth++;
        try {
            return $callback();
        } finally {
            $depth--;
        }
    }

    if (!is_dir(ERC_DATA_DIR)) {
        @mkdir(ERC_DATA_DIR, 0755, true);
    }

    $lockFile = ERC_DATA_DIR . '/write.lock';
    $fp = @fopen($lockFile, 'c+');
    if ($fp === false) {
        throw new \RuntimeException('Unable to open storage lock file.');
    }
    @chmod($lockFile, 0644);

    if (!flock($fp, LOCK_EX)) {
        @fclose($fp);
        throw new \RuntimeException('Unable to acquire exclusive storage lock.');
    }

    $depth = 1;
    try {
        return $callback();
    } finally {
        $depth--;
        flock($fp, LOCK_UN);
        @fclose($fp);
    }
}

/**
 * Resolve absolute/relative storage path under ERC_ROOT_DIR.
 */
function erc_resolve_storage_path(string $path): string
{
    if (preg_match('/^(?:[a-zA-Z]:[\\\\\\/]|[\\\\\\/])/', $path)) {
        return $path;
    }
    return ERC_ROOT_DIR . '/' . ltrim(str_replace('\\', '/', $path), '/');
}

/**
 * Read and decode JSON file using a shared `flock(LOCK_SH)`.
 */
function erc_load_json(string $path, mixed $default = []): mixed
{
    $resolved = erc_resolve_storage_path($path);
    if (!is_file($resolved) || !is_readable($resolved)) {
        return $default;
    }

    $fp = @fopen($resolved, 'rb');
    if ($fp === false) {
        return $default;
    }

    $raw = false;
    try {
        flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
    } finally {
        flock($fp, LOCK_UN);
        @fclose($fp);
    }

    if (!is_string($raw) || trim($raw) === '') {
        return $default;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $default;
    }

    return $decoded;
}

/**
 * Atomically encode and persist JSON using `erc_with_storage_lock()`, temp file,
 * `@chmod`, and `rename()` (with Windows destination unlink fallback).
 */
function erc_save_json_atomic(string $path, mixed $data, int $chmod = 0644): bool
{
    $resolved = erc_resolve_storage_path($path);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $payload = $json . "\n";

    return (bool)erc_with_storage_lock(static function () use ($resolved, $payload, $chmod): bool {
        $dir = dirname($resolved);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (\Throwable) {
            $suffix = substr(md5((string)mt_rand()), 0, 8);
        }

        $tmpFile = $resolved . '.tmp.' . getmypid() . '_' . $suffix;
        $fp = @fopen($tmpFile, 'wb');
        if ($fp === false) {
            return false;
        }

        $total = strlen($payload);
        $written = 0;
        $ok = true;
        while ($written < $total) {
            $bytes = @fwrite($fp, substr($payload, $written));
            if ($bytes === false || $bytes === 0) {
                $ok = false;
                break;
            }
            $written += $bytes;
        }
        @fflush($fp);
        @fclose($fp);

        if (!$ok || $written !== $total) {
            @unlink($tmpFile);
            return false;
        }

        @chmod($tmpFile, $chmod);

        if (@rename($tmpFile, $resolved)) {
            @chmod($resolved, $chmod);
            return true;
        }

        // Windows rename() cannot overwrite an existing target reliably across all FS configurations.
        if (is_file($resolved)) {
            @unlink($resolved);
        }
        if (@rename($tmpFile, $resolved)) {
            @chmod($resolved, $chmod);
            return true;
        }

        @unlink($tmpFile);
        return false;
    });
}

if (!function_exists('erc_escape')) {
    /**
     * HTML-escape any scalar or stringable value safely as UTF-8.
     */
    function erc_escape(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('e')) {
    /**
     * Shorthand HTML-escape helper matching erc_escape().
     */
    function e(mixed $value): string
    {
        return erc_escape($value);
    }
}

/**
 * Normalize a relative or root-relative media path for `<img src>` URLs.
 */
function erc_media_url(string $relativePath): string
{
    $trimmed = trim($relativePath);
    if ($trimmed === '') {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#i', $trimmed) || str_starts_with($trimmed, 'data:')) {
        return $trimmed;
    }

    $normalized = str_replace('\\', '/', $trimmed);
    $normalized = preg_replace('#^\./+#', '', $normalized) ?? $normalized;
    return '/' . ltrim($normalized, '/');
}

/**
 * Scan gallery images cleanly across redesign/gallery and read-only coexistence
 * parent gallery/, omitting `.webp` sidecars when a non-WebP original exists.
 *
 * @return array<int, string>
 */
function erc_scan_gallery_images(): array
{
    $dirs = [];
    if (is_dir(ERC_ROOT_DIR . '/gallery')) {
        $dirs[] = ERC_ROOT_DIR . '/gallery';
    }
    if (erc_is_coexistence_mode()) {
        $parentGallery = dirname(ERC_ROOT_DIR) . '/gallery';
        if (is_dir($parentGallery)) {
            $dirs[] = $parentGallery;
        }
    } elseif (empty($dirs) && is_dir(ERC_ASSET_BASE_DIR . '/gallery')) {
        $dirs[] = ERC_ASSET_BASE_DIR . '/gallery';
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $nonWebpStems = [];
    $byFilename = [];

    foreach ($dirs as $dir) {
        $entries = @scandir($dir);
        if (!is_array($entries)) {
            continue;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($full)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                continue;
            }
            $lowerName = strtolower($entry);
            if (!isset($byFilename[$lowerName])) {
                $byFilename[$lowerName] = $entry;
            }
            if ($ext !== 'webp') {
                $stem = strtolower(pathinfo($entry, PATHINFO_FILENAME));
                $nonWebpStems[$stem] = true;
            }
        }
    }

    $results = [];
    foreach ($byFilename as $entry) {
        $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        $stem = strtolower(pathinfo($entry, PATHINFO_FILENAME));
        if ($ext === 'webp' && isset($nonWebpStems[$stem])) {
            continue;
        }
        $results[] = 'gallery/' . $entry;
    }

    natcasesort($results);
    return array_values($results);
}

/**
 * Determine client IP from REMOTE_ADDR without trusting spoofable headers.
 */
function erc_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
        return $ip;
    }
    return '0.0.0.0';
}

/**
 * Check and optionally record a per-IP sliding-window rate limit bucket.
 *
 * @return array{allowed: bool, remaining: int, retry_after: int, hits: int}
 */
function erc_check_rate_limit(string $bucket, int $maxHits, int $windowSeconds, bool $recordHit = true): array
{
    $storePath = ERC_DATA_DIR . '/rate_limits.log';
    $ipHash = hash('sha256', erc_client_ip());
    $key = $bucket . ':' . $ipHash;
    $now = time();

    return erc_with_storage_lock(static function () use ($storePath, $key, $maxHits, $windowSeconds, $recordHit, $now): array {
        $data = erc_load_json($storePath, []);
        if (!is_array($data)) {
            $data = [];
        }

        // Prune stale buckets opportunistically to keep file compact.
        $dirty = false;
        foreach ($data as $k => $timestamps) {
            if (!is_array($timestamps)) {
                unset($data[$k]);
                $dirty = true;
                continue;
            }
            $filtered = [];
            foreach ($timestamps as $ts) {
                $tsInt = (int)$ts;
                if (($now - $tsInt) < $windowSeconds) {
                    $filtered[] = $tsInt;
                }
            }
            if (empty($filtered)) {
                unset($data[$k]);
                $dirty = true;
            } elseif (count($filtered) !== count($timestamps)) {
                $data[$k] = $filtered;
                $dirty = true;
            }
        }

        $entries = isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
        $hits = count($entries);

        if ($hits >= $maxHits) {
            $oldest = !empty($entries) ? min($entries) : $now;
            $retryAfter = max(1, ($oldest + $windowSeconds) - $now);
            if ($dirty) {
                erc_save_json_atomic($storePath, $data, 0600);
            }
            return [
                'allowed'     => false,
                'remaining'   => 0,
                'retry_after' => $retryAfter,
                'hits'        => $hits,
            ];
        }

        if ($recordHit) {
            $entries[] = $now;
            $data[$key] = $entries;
            $hits = count($entries);
            erc_save_json_atomic($storePath, $data, 0600);
        } elseif ($dirty) {
            erc_save_json_atomic($storePath, $data, 0600);
        }

        return [
            'allowed'     => true,
            'remaining'   => max(0, $maxHits - $hits),
            'retry_after' => 0,
            'hits'        => $hits,
        ];
    });
}

/**
 * Clear rate-limit entries for the current client IP in the given bucket.
 */
function erc_clear_rate_limit(string $bucket): void
{
    $storePath = ERC_DATA_DIR . '/rate_limits.log';
    $key = $bucket . ':' . hash('sha256', erc_client_ip());

    erc_with_storage_lock(static function () use ($storePath, $key): void {
        if (!is_file($storePath)) {
            return;
        }
        $data = erc_load_json($storePath, []);
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return;
        }
        unset($data[$key]);
        erc_save_json_atomic($storePath, $data, 0600);
    });
}

/**
 * Emit standard security and CSP headers (safe under both Apache and PHP built-in server).
 */
function erc_send_security_headers(bool $isHtml = true): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(self), microphone=()');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');

    if ($isHtml) {
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
    } else {
        header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    }
}

$__ercSiteConfig = erc_get_site_config();
$GLOBAL_EMAIL = $__ercSiteConfig['email'];
$GLOBAL_PHONE = $__ercSiteConfig['phone'];
$GLOBAL_PHONE_LINK = $__ercSiteConfig['phoneLink'];
$ADMIN_EMAIL = erc_get_admin_email();
unset($__ercSiteConfig);
