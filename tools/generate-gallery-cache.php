<?php
// tools/generate-gallery-cache.php
// Generates a cached JSON file of gallery images for faster loading

$galleryDir = '../gallery/';
$cacheFile = '../data/gallery-cache.json';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
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

// Sort for consistent order
sort($images);

// Write to cache file
if (file_put_contents($cacheFile, json_encode($images, JSON_PRETTY_PRINT))) {
    echo "Gallery cache generated successfully! Found " . count($images) . " images.\n";
} else {
    echo "Error writing cache file.\n";
}
?>
