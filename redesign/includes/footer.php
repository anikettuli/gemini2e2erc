<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$ftrEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$ftrMediaUrl = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$pageMeta = isset($pageMeta) && is_array($pageMeta) ? $pageMeta : [];
$ftrEmail = (string) ($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');
$ftrPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$ftrPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');

$baseModules = [
    '/js/theme.js',
    '/js/shell.js',
    '/js/forms.js',
];

$pageModules = [];
if (!empty($pageMeta['js_modules']) && is_array($pageMeta['js_modules'])) {
    foreach ($pageMeta['js_modules'] as $modPath) {
        $modStr = trim((string) $modPath);
        if ($modStr === '') {
            continue;
        }
        if (!str_starts_with($modStr, '/') && !str_starts_with($modStr, 'http://') && !str_starts_with($modStr, 'https://')) {
            $modStr = '/' . ltrim($modStr, '/');
        }
        if (!in_array($modStr, $baseModules, true) && !in_array($modStr, $pageModules, true)) {
            $pageModules[] = $modStr;
        }
    }
}
?>
  <footer class="site-footer">
    <div class="container">
      <div class="site-footer__grid">
        <div>
          <div class="site-footer__brand">
            <img
              class="site-brand__logo"
              src="<?= $ftrEscape($ftrMediaUrl('e2e2rc_LOGO.png')) ?>"
              alt=""
              width="44"
              height="44"
              loading="lazy"
              decoding="async"
            >
            <div>
              <p class="site-brand__title">Lions District 2-E2</p>
              <p class="site-brand__subtitle">Eyeglass Recycling Center</p>
            </div>
          </div>
          <p class="stack--sm">
            Operating as District 2-E2 Lions Eyeglass Recycling Center Foundation, a 100% volunteer-run
            nonprofit founded in 2013 and recognized as a 501(c)(3) organization in 2014.
          </p>
          <p class="mt-4 text-muted">
            Lion Year runs July 1 – June 30. Donated eyewear is cleaned, lensometer-verified, and sorted
            for humanitarian vision missions worldwide.
          </p>
        </div>

        <nav aria-label="Footer navigation">
          <h2 class="site-footer__heading">Explore</h2>
          <ul class="site-footer__list">
            <li><a class="site-footer__link" href="/">Home</a></li>
            <li><a class="site-footer__link" href="/donate">Donate Eyeglasses</a></li>
            <li><a class="site-footer__link" href="/locations">Drop-Off Map</a></li>
            <li><a class="site-footer__link" href="/volunteer">Volunteer</a></li>
            <li><a class="site-footer__link" href="/missions">Missions &amp; Impact</a></li>
            <li><a class="site-footer__link" href="/about">About &amp; Board</a></li>
            <li><a class="site-footer__link" href="/contact">Contact &amp; Forms</a></li>
          </ul>
        </nav>

        <div>
          <h2 class="site-footer__heading">Watauga Center &amp; Hours</h2>
          <div class="site-footer__list">
            <div class="site-footer__detail">
              <?= erc_icon('map-pin', ['width' => '18', 'height' => '18']) ?>
              <div>
                <strong>5621 Bunker Blvd</strong><br>
                Watauga, TX 76148<br>
                <span>Rear entrance via east-side wooden walkway. Free on-site parking.</span>
              </div>
            </div>
            <div class="site-footer__detail">
              <?= erc_icon('clock', ['width' => '18', 'height' => '18']) ?>
              <div>
                <strong>Saturdays:</strong> 9:00 AM – 3:00 PM<br>
                <strong>Mon – Fri:</strong> By appointment<br>
                <strong>Sunday:</strong> Closed
              </div>
            </div>
          </div>
        </div>

        <div>
          <h2 class="site-footer__heading">Mail &amp; Direct Contact</h2>
          <div class="site-footer__list">
            <div class="site-footer__detail">
              <?= erc_icon('mail', ['width' => '18', 'height' => '18']) ?>
              <div>
                <strong>Mailing Address</strong><br>
                P.O. Box 1854<br>
                Keller, TX 76244
              </div>
            </div>
            <a class="site-footer__link" href="tel:<?= $ftrEscape($ftrPhoneLink) ?>">
              <?= erc_icon('phone', ['width' => '18', 'height' => '18']) ?>
              <span><?= $ftrEscape($ftrPhone) ?></span>
            </a>
            <a class="site-footer__link" href="mailto:<?= $ftrEscape($ftrEmail) ?>">
              <?= erc_icon('send', ['width' => '18', 'height' => '18']) ?>
              <span><?= $ftrEscape($ftrEmail) ?></span>
            </a>
            <p class="text-muted">Inquiries usually receive a response within 24 hours.</p>
          </div>
        </div>
      </div>

      <div class="site-footer__bottom">
        <p>
          &copy; <?= $ftrEscape(date('Y')) ?> District 2-E2 Lions Eyeglass Recycling Center Foundation.
          501(c)(3) Nonprofit Organization.
        </p>
        <div class="site-footer__legal-links">
          <a href="/contact#forms-center">PDF Forms &amp; Bylaws</a>
          <a href="/locations">Collection Boxes</a>
          <a href="/admin-view.php">Admin Portal</a>
        </div>
      </div>
    </div>
  </footer>

<?php foreach ($baseModules as $scriptSrc): ?>
  <script type="module" src="<?= $ftrEscape($scriptSrc) ?>"></script>
<?php endforeach; ?>
<?php foreach ($pageModules as $scriptSrc): ?>
  <script type="module" src="<?= $ftrEscape($scriptSrc) ?>"></script>
<?php endforeach; ?>
</body>
</html>
