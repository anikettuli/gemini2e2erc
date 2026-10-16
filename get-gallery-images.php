<?php
// get-gallery-images.php
// Returns list of image files from the gallery directory using native glob()
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$galleryDir = __DIR__ . '/gallery';
$images = [];

if (is_dir($galleryDir)) {
    $files = glob($galleryDir . '/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
    foreach ($files as $file) {
        $images[] = 'gallery/' . basename($file);
    }
    sort($images);
}

echo json_encode($images);
?>
