<?php
// get-gallery-images.php
// Returns a list of image files from the gallery directory.

header('Content-Type: application/json');

$galleryDir = 'gallery/';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$images = [];

if (is_dir($galleryDir)) {
    $files = scandir($galleryDir);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            // Skip README.md or other non-image files if any, though extension check handles it.
            // Also skip directories . and ..
            $images[] = $galleryDir . $file;
        }
    }
}

// Sort images to have a consistent order (optional, maybe by name or modification time)
// sort($images); 

echo json_encode($images);
?>
