<?php
/**
 * Gallery Images API
 * Returns array of image paths from the gallery directory.
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$galleryDir = __DIR__ . '/gallery';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$images = [];

if (is_dir($galleryDir)) {
    $files = scandir($galleryDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            $images[] = 'gallery/' . $file;
        }
    }
}

sort($images);
echo json_encode($images);
?>
