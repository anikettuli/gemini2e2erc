<?php
/**
 * Shared site configuration, environment loader, and atomic storage helpers.
 */

if (!function_exists('erc_load_env')) {
    /**
     * Parses .env key=value pairs once per request.
     */
    function erc_load_env(): array {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $cached = [];
        $envFile = __DIR__ . '/.env';
        if (!is_file($envFile) || !is_readable($envFile)) {
            return $cached;
        }

        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return $cached;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            if (strpos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            $len = strlen($value);
            if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                $cached[$key] = $value;
            }
        }

        return $cached;
    }

    /**
     * Executes $callback while holding an exclusive lock on a persistent lockfile.
     * Re-entrant within the same PHP process.
     */
    function erc_with_storage_lock(callable $callback) {
        static $lockDepth = 0;
        if ($lockDepth > 0) {
            return $callback();
        }

        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }

        $lockFile = $dataDir . '/write.lock';
        $fp = @fopen($lockFile, 'c');
        if (!$fp) {
            throw new RuntimeException('Unable to open storage lock file.');
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException('Unable to acquire storage lock.');
        }

        $lockDepth++;
        try {
            return $callback();
        } finally {
            $lockDepth--;
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Reads and decodes a JSON file with a shared lock.
     */
    function erc_load_json(string $file, $default = []) {
        if (!is_file($file)) {
            return $default;
        }

        $fp = @fopen($file, 'rb');
        if (!$fp) {
            return $default;
        }

        $content = '';
        if (flock($fp, LOCK_SH)) {
            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);
        } else {
            $content = stream_get_contents($fp);
        }
        fclose($fp);

        if ($content === false || trim($content) === '') {
            return $default;
        }

        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $default;
        }

        return $decoded;
    }

    /**
     * Atomically writes $data as pretty-printed JSON via temp file + rename.
     */
    function erc_save_json_atomic(string $file, $data, int $chmod = 0644): bool {
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return false;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (Exception $e) {
            $suffix = (string)mt_rand(100000, 999999);
        }

        $tmpFile = $file . '.tmp.' . getmypid() . '_' . $suffix;
        $bytes = @file_put_contents($tmpFile, $json, LOCK_EX);
        if ($bytes === false || $bytes !== strlen($json)) {
            @unlink($tmpFile);
            return false;
        }

        @chmod($tmpFile, $chmod);

        if (!@rename($tmpFile, $file)) {
            // Windows fallback when destination exists and blocks rename
            if (DIRECTORY_SEPARATOR === '\\' && is_file($file)) {
                @unlink($file);
                if (@rename($tmpFile, $file)) {
                    @chmod($file, $chmod);
                    return true;
                }
            }
            @unlink($tmpFile);
            return false;
        }

        @chmod($file, $chmod);
        return true;
    }

    /**
     * Scans gallery/ for displayable images, skipping .webp sidecars when a
     * matching .jpg/.jpeg/.png source image exists in the same directory.
     */
    function erc_scan_gallery_images(?string $galleryDir = null): array {
        $dir = $galleryDir ?? (__DIR__ . '/gallery');
        if (!is_dir($dir)) {
            return [];
        }

        $entries = @scandir($dir);
        if (!is_array($entries)) {
            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $nonWebpStems = [];
        $candidates = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || !is_file($dir . '/' . $entry)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }
            $stem = strtolower(pathinfo($entry, PATHINFO_FILENAME));
            if ($ext !== 'webp') {
                $nonWebpStems[$stem] = true;
            }
            $candidates[] = ['file' => $entry, 'ext' => $ext, 'stem' => $stem];
        }

        $images = [];
        foreach ($candidates as $item) {
            if ($item['ext'] === 'webp' && isset($nonWebpStems[$item['stem']])) {
                continue;
            }
            $images[] = 'gallery/' . $item['file'];
        }

        natsort($images);
        return array_values($images);
    }

    /**
     * Lightweight file-backed per-IP sliding-window rate limiter.
     * Returns true if allowed, false if rate-limited.
     */
    function erc_check_rate_limit(string $bucket, int $maxAttempts, int $windowSeconds, bool $recordHit = true): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $tmpDir = sys_get_temp_dir();
        if (!$tmpDir || !is_dir($tmpDir) || !is_writable($tmpDir)) {
            return true;
        }

        $key = hash('sha256', 'erc_rl|' . $bucket . '|' . $ip);
        $file = rtrim($tmpDir, '/\\') . DIRECTORY_SEPARATOR . 'erc_rl_' . substr($key, 0, 32) . '.json';

        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return true;
        }

        $now = time();
        $allowed = true;

        if (flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $hits = json_decode($raw ?: '[]', true);
            if (!is_array($hits)) {
                $hits = [];
            }

            $cutoff = $now - $windowSeconds;
            $hits = array_values(array_filter($hits, static function ($ts) use ($cutoff) {
                return is_int($ts) && $ts > $cutoff;
            }));

            if (count($hits) >= $maxAttempts) {
                $allowed = false;
            } elseif ($recordHit) {
                $hits[] = $now;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($hits));
            fflush($fp);
            flock($fp, LOCK_UN);
        }

        fclose($fp);
        return $allowed;
    }

    /**
     * Clears a rate-limit bucket for the current IP (e.g., after valid admin auth).
     */
    function erc_clear_rate_limit(string $bucket): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $tmpDir = sys_get_temp_dir();
        if (!$tmpDir || !is_dir($tmpDir)) {
            return;
        }
        $key = hash('sha256', 'erc_rl|' . $bucket . '|' . $ip);
        $file = rtrim($tmpDir, '/\\') . DIRECTORY_SEPARATOR . 'erc_rl_' . substr($key, 0, 32) . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

// Global configuration for email and phone (persisted in data/site-config.json across deploys)
$_ercSiteConfig = erc_load_json(__DIR__ . '/data/site-config.json', []);
$GLOBAL_EMAIL = !empty($_ercSiteConfig['email']) ? (string)$_ercSiteConfig['email'] : '2e2erc1854@gmail.com';
$GLOBAL_PHONE = !empty($_ercSiteConfig['phone']) ? (string)$_ercSiteConfig['phone'] : '(817) 710-5403';
unset($_ercSiteConfig);
