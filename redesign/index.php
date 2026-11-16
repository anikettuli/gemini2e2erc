<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$normalizedPath = is_string($requestPath) ? strtolower(rtrim($requestPath, '/')) : '';
if ($normalizedPath === '') {
    $normalizedPath = '/';
}

$sectionMap = [
    '/'          => '',
    '/donate'    => 'drop-off',
    '/locations' => 'drop-off',
    '/volunteer' => 'volunteer',
    '/missions'  => 'impact',
    '/about'     => 'about',
    '/contact'   => 'contact',
];

$initialSection = $sectionMap[$normalizedPath] ?? '';

$pageMeta = [
    'title'       => 'Lions District 2-E2 Eyeglass Recycling Center | Watauga, TX',
    'description' => '100% volunteer Lions optical recycling center in Watauga, TX collecting eyewear, hearing aids, cell phones, and Windows 11 devices to serve 40+ countries.',
    'canonical'   => $normalizedPath === '/' ? '/' : $normalizedPath,
    'preload_img' => erc_media_url('images/our-story-group.png'),
    'og_image'    => erc_media_url('images/our-story-group.png'),
    'noindex'     => false,
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main" tabindex="-1">
  <?php
  require __DIR__ . '/includes/sections/hero.php';
  require __DIR__ . '/includes/sections/dropoff.php';
  require __DIR__ . '/includes/sections/process.php';
  require __DIR__ . '/includes/sections/impact.php';
  require __DIR__ . '/includes/sections/volunteer.php';
  require __DIR__ . '/includes/sections/about.php';
  require __DIR__ . '/includes/sections/contact.php';
  ?>
</main>
<?php
require __DIR__ . '/includes/footer.php';
