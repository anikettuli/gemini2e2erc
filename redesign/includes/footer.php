<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$ftrEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
  <footer class="site-footer" id="siteFooter">
    <div class="container site-footer__inner">
      <div class="site-footer__top">
        <nav class="site-footer__nav" aria-label="Footer navigation">
          <a class="site-footer__nav-link" href="/">HOME</a>
          <a class="site-footer__nav-link" href="/donate">DONATE</a>
          <a class="site-footer__nav-link" href="/locations">LOCATIONS</a>
          <a class="site-footer__nav-link" href="/volunteer">VOLUNTEER</a>
          <a class="site-footer__nav-link" href="/missions">MISSIONS</a>
          <a class="site-footer__nav-link" href="/about">ABOUT</a>
          <a class="site-footer__nav-link" href="/contact">CONTACT</a>
          <a class="site-footer__nav-link site-footer__top-btn" href="#top" data-scroll-top>
            <span>BACK TO TOP</span>
            <?= erc_icon('arrow-up-right', ['width' => '14', 'height' => '14']) ?>
          </a>
        </nav>

        <div class="site-footer__ticker" aria-live="polite">
          <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
          <span id="footerLiveTicker">WATAUGA TX &middot; SAT 9:00 AM &ndash; 3:00 PM CT &middot; MON&ndash;FRI BY APPT</span>
        </div>
      </div>

      <div class="site-footer__grid">
        <div class="site-footer__col">
          <p class="site-footer__label">HQ COORDINATES &amp; ACCESS</p>
          <p class="site-footer__coord">32.8707&deg; N, 97.2621&deg; W &middot; 5621 BUNKER BLVD, WATAUGA TX 76148</p>
          <p class="site-footer__note">
            Free on-site parking. Follow the wooden walkway along the east side of the building to the rear entrance.
          </p>
        </div>

        <div class="site-footer__col">
          <p class="site-footer__label">DIRECT DISPATCH &amp; MAIL</p>
          <div class="site-footer__contacts">
            <a class="site-footer__link" href="tel:<?= $ftrEscape($ftrPhoneLink) ?>">
              <?= erc_icon('phone', ['width' => '16', 'height' => '16']) ?>
              <span><?= $ftrEscape($ftrPhone) ?></span>
            </a>
            <a class="site-footer__link" href="mailto:<?= $ftrEscape($ftrEmail) ?>">
              <?= erc_icon('mail', ['width' => '16', 'height' => '16']) ?>
              <span><?= $ftrEscape($ftrEmail) ?></span>
            </a>
          </div>
          <p class="site-footer__note">
            Mail &amp; Checks: Lions District 2-E2 ERC, P.O. Box 1854, Keller, TX 76244. Responses typically within 24 hours.
          </p>
        </div>

        <div class="site-footer__col">
          <p class="site-footer__label">GOVERNANCE &amp; RECORDS</p>
          <p class="site-footer__note">
            District 2-E2 Lions Eyeglass Recycling Center Foundation &middot; 100% volunteer-run 501(c)(3) nonprofit (founded 2013; Lion Year July 1 &ndash; June 30).
          </p>
          <div class="site-footer__legal-links">
            <a href="/contact#forms-center">PDF Forms &amp; Bylaws</a>
            <a href="/locations">214 Drop-Off Bins</a>
            <a href="/admin-view.php">Admin Portal</a>
          </div>
        </div>
      </div>

      <div class="site-footer__meta">
        <p class="site-footer__copyright">
          &copy; <?= $ftrEscape(date('Y')) ?> DISTRICT 2-E2 LIONS EYEGLASS RECYCLING CENTER FOUNDATION &middot; NO TRACKERS &middot; SELF-HOSTED FONTS &amp; ASSETS
        </p>

        <button
          type="button"
          class="v2-easter-egg"
          id="opticalSwatchEgg"
          aria-pressed="false"
          title="Optical chart calibration test"
        >
          <span class="v2-easter-egg__dot" aria-hidden="true"></span>
          <span class="v2-easter-egg__text">20/20 CALIBRATION</span>
        </button>
      </div>
    </div>

    <div class="site-footer__watermark" aria-hidden="true">
      <span>LIONS 2-E2 ERC</span>
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
