<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$ftrEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
  <footer class="site-footer" id="siteFooter">
    <div class="container site-footer__inner">
      <div class="site-footer__legal">
        <p class="site-footer__title">
          District 2-E2 Lions Eyeglass Recycling Center Foundation &middot; 501(c)(3) Nonprofit
        </p>
        <p class="site-footer__meta">
          &copy; <?= $ftrEscape(date('Y')) ?> Lions District 2-E2 ERC &middot; 100% Volunteer-Run &middot; No trackers &middot; Self-hosted fonts
        </p>
      </div>

      <nav class="site-footer__links" aria-label="Footer utility links">
        <a class="site-footer__link" href="/admin-view.php">Admin Portal</a>
        <a class="site-footer__link" href="#top" data-scroll-top>
          <span>Back to Top</span>
          <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
        </a>
      </nav>
    </div>
  </footer>
</body>
</html>
