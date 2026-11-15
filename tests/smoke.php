<?php
/**
 * Automated CLI Smoke Test Suite for 2E2ERC Website
 * Executed in GitHub Actions prior to GoDaddy cPanel FTP deployment.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script may only be executed from the command line.\n");
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config.php';
require_once $rootDir . '/mail-utils.php';

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $label): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] $label\n";
    } else {
        $failed++;
        fwrite(STDERR, "[FAIL] $label\n");
    }
}

// 1. SMTP Header Sanitization & RFC 5321 DATA Dot-Stuffing
$injectedHeader = SimpleSMTP::sanitizeHeader("Volunteer Inquiry\r\nBcc: victim@example.com\n\0");
assert_true(
    strpos($injectedHeader, "\r") === false &&
    strpos($injectedHeader, "\n") === false &&
    strpos($injectedHeader, "\0") === false,
    'SimpleSMTP::sanitizeHeader strips CR, LF, and NUL bytes'
);

$stuffedBody = SimpleSMTP::formatDataBody("Line 1\n.\nRSET\r\n.dotline");
assert_true(
    $stuffedBody === "Line 1\r\n..\r\nRSET\r\n..dotline",
    'SimpleSMTP::formatDataBody normalizes CRLF and dot-stuffs leading periods'
);

// 2. Atomic JSON Persistence & Re-entrant Lock
$tmpBase = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'erc_smoke_' . getmypid() . '_' . mt_rand(1000, 9999);
@mkdir($tmpBase, 0755, true);

$sampleJsonFile = $tmpBase . DIRECTORY_SEPARATOR . 'sample.json';
$payload = ['status' => 'ok', 'count' => 42, 'items' => ['a', 'b']];
$savedOk = erc_save_json_atomic($sampleJsonFile, $payload, 0600);
$loadedPayload = erc_load_json($sampleJsonFile, []);
assert_true(
    $savedOk && $loadedPayload === $payload,
    'erc_save_json_atomic and erc_load_json round-trip data intact'
);

$lockFileExistedBefore = is_file($rootDir . '/data/write.lock');
$reentrantResult = erc_with_storage_lock(static function () {
    return erc_with_storage_lock(static function () {
        return 'nested-ok';
    });
});
if (!$lockFileExistedBefore && is_file($rootDir . '/data/write.lock')) {
    @unlink($rootDir . '/data/write.lock');
}
assert_true(
    $reentrantResult === 'nested-ok',
    'erc_with_storage_lock supports re-entrant nested locking'
);

// 3. Gallery .webp Sidecar Deduplication
$tmpGallery = $tmpBase . DIRECTORY_SEPARATOR . 'gallery';
@mkdir($tmpGallery, 0755, true);
file_put_contents($tmpGallery . DIRECTORY_SEPARATOR . 'photo-1.jpg', 'jpg');
file_put_contents($tmpGallery . DIRECTORY_SEPARATOR . 'photo-1.webp', 'webp-sidecar');
file_put_contents($tmpGallery . DIRECTORY_SEPARATOR . 'standalone.webp', 'webp-only');
file_put_contents($tmpGallery . DIRECTORY_SEPARATOR . 'readme.txt', 'ignore');

$scannedGallery = erc_scan_gallery_images($tmpGallery);
assert_true(
    $scannedGallery === ['gallery/photo-1.jpg', 'gallery/standalone.webp'],
    'erc_scan_gallery_images skips .webp sidecars when original exists and keeps standalone .webp'
);

// 4. Sliding-Window Rate Limiter
$_SERVER['REMOTE_ADDR'] = '127.0.0.250';
$bucket = 'smoke_test_' . getmypid() . '_' . mt_rand(1000, 9999);
erc_clear_rate_limit($bucket);
$hit1 = erc_check_rate_limit($bucket, 2, 60);
$hit2 = erc_check_rate_limit($bucket, 2, 60);
$hit3 = erc_check_rate_limit($bucket, 2, 60);
erc_clear_rate_limit($bucket);
$hitAfterClear = erc_check_rate_limit($bucket, 2, 60);
erc_clear_rate_limit($bucket);
assert_true(
    $hit1 === true && $hit2 === true && $hit3 === false && $hitAfterClear === true,
    'erc_check_rate_limit enforces window cap and resets via erc_clear_rate_limit'
);

// Cleanup temp directory
foreach ([$tmpGallery . '/photo-1.jpg', $tmpGallery . '/photo-1.webp', $tmpGallery . '/standalone.webp', $tmpGallery . '/readme.txt', $sampleJsonFile] as $f) {
    @unlink($f);
}
@rmdir($tmpGallery);
@rmdir($tmpBase);

// 5. Validate all JSON files in data/
$jsonFiles = glob($rootDir . '/data/*.json') ?: [];
assert_true(count($jsonFiles) >= 6, 'data/ directory contains expected JSON data files');
foreach ($jsonFiles as $jf) {
    $raw = file_get_contents($jf);
    json_decode($raw, true);
    assert_true(
        json_last_error() === JSON_ERROR_NONE,
        'Valid JSON syntax: data/' . basename($jf)
    );
}

// 6. Validate local asset references in index.html, events.json, and board.json
$html = file_get_contents($rootDir . '/index.html');
assert_true($html !== false && strlen($html) > 1000, 'index.html is readable and non-empty');

preg_match_all('/\b(?:src|href)="([^"]+)"/', (string)$html, $matches);
$missingAssets = [];
foreach ($matches[1] ?? [] as $ref) {
    if (
        $ref === '' ||
        $ref[0] === '#' ||
        preg_match('/^(?:https?:|mailto:|tel:|data:|javascript:)/i', $ref)
    ) {
        continue;
    }
    $cleanPath = preg_replace('/[?#].*$/', '', $ref);
    if (!is_file($rootDir . '/' . $cleanPath)) {
        $missingAssets[] = $cleanPath;
    }
}
assert_true(
    count($missingAssets) === 0,
    'All local index.html asset links exist on disk' . (count($missingAssets) ? ' (missing: ' . implode(', ', $missingAssets) . ')' : '')
);

foreach (['events.json', 'board.json'] as $mediaJsonName) {
    $records = erc_load_json($rootDir . '/data/' . $mediaJsonName, []);
    $missingMedia = [];
    foreach ($records as $rec) {
        if (!empty($rec['image']) && !is_file($rootDir . '/' . $rec['image'])) {
            $missingMedia[] = $rec['image'];
        }
    }
    assert_true(
        count($missingMedia) === 0,
        "All image paths in data/$mediaJsonName exist on disk" . (count($missingMedia) ? ' (missing: ' . implode(', ', $missingMedia) . ')' : '')
    );
}

echo "\nSmoke suite finished: $passed passed, $failed failed.\n";
exit($failed > 0 ? 1 : 0);
