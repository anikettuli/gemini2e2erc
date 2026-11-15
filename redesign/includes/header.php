<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$hdrEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$pageMeta = isset($pageMeta) && is_array($pageMeta) ? $pageMeta : [];
$slug = (string) ($pageMeta['slug'] ?? 'home');
$bodyClassExtra = !empty($pageMeta['body_class']) ? ' ' . trim((string) $pageMeta['body_class']) : '';

$hdrPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$hdrPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');

$primaryNavItems = [
    ['slug' => 'donate', 'href' => '/donate', 'label' => 'DONATE'],
    ['slug' => 'locations', 'href' => '/locations', 'label' => 'LOCATIONS'],
    ['slug' => 'volunteer', 'href' => '/volunteer', 'label' => 'VOLUNTEER'],
    ['slug' => 'missions', 'href' => '/missions', 'label' => 'MISSIONS'],
    ['slug' => 'about', 'href' => '/about', 'label' => 'ABOUT'],
];
?>
<body class="erc-body<?= $hdrEscape($bodyClassExtra) ?>" data-page="<?= $hdrEscape($slug) ?>">
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <header class="site-header" id="siteHeader">
    <div class="site-header__capsule">
      <a
        class="site-brand"
        href="/"
        aria-label="Lions District 2-E2 Eyeglass Recycling Center — Home"
        data-tooltip="Lions 2-E2 Eyeglass Recycling Center — Home"
        <?= $slug === 'home' ? 'aria-current="page"' : '' ?>
      >
        <span class="site-brand__reticle" aria-hidden="true">
          <?= erc_optical_reticle() ?>
          <span class="site-brand__monogram">2E2</span>
        </span>
      </a>

      <nav class="site-nav" id="primaryNav" aria-label="Primary navigation">
        <ul class="site-nav__list">
<?php foreach ($primaryNavItems as $item): ?>
          <li>
            <a
              class="site-nav__link"
              href="<?= $hdrEscape($item['href']) ?>"
              <?= $slug === $item['slug'] ? 'aria-current="page"' : '' ?>
            ><?= $hdrEscape($item['label']) ?></a>
          </li>
<?php endforeach; ?>
        </ul>
      </nav>

      <div class="site-header__controls">
        <button
          type="button"
          class="theme-toggle"
          id="themeToggle"
          aria-label="Color theme: Auto. Click to cycle Auto, Light, or Dark."
          title="Cycle color theme (Auto / Light / Dark)"
          data-theme-pref="auto"
        >
          <span class="theme-toggle__glyph" aria-hidden="true">&#9680;</span>
          <span class="theme-toggle__label" id="themeToggleLabel">AUTO</span>
        </button>

        <a
          class="btn btn--primary btn--sm site-nav__cta"
          href="/contact"
          <?= $slug === 'contact' ? 'aria-current="page"' : '' ?>
        >
          <span>CONTACT</span>
          <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
        </a>

        <button
          type="button"
          class="nav-toggle-btn"
          id="navDrawerOpen"
          aria-controls="mobileNavDialog"
          aria-expanded="false"
          aria-label="Open navigation menu"
        >
          <?= erc_icon('menu', ['width' => '18', 'height' => '18']) ?>
          <span>MENU</span>
        </button>
      </div>
    </div>
  </header>

  <dialog
    class="nav-drawer"
    id="mobileNavDialog"
    aria-labelledby="mobileNavTitle"
    closedby="any"
  >
    <div class="nav-drawer__header">
      <span class="nav-drawer__title" id="mobileNavTitle">2E2 // NAVIGATION</span>
      <button
        type="button"
        class="nav-drawer__close"
        id="navDrawerClose"
        aria-label="Close navigation menu"
        autofocus
      >
        <?= erc_icon('x', ['width' => '20', 'height' => '20']) ?>
      </button>
    </div>

    <div class="nav-drawer__body">
      <nav aria-label="Mobile navigation">
        <ul class="nav-drawer__list">
          <li>
            <a
              class="nav-drawer__link"
              href="/"
              <?= $slug === 'home' ? 'aria-current="page"' : '' ?>
            >
              <span>HOME</span>
              <?= erc_icon('chevron-right', ['width' => '18', 'height' => '18']) ?>
            </a>
          </li>
<?php foreach ($primaryNavItems as $item): ?>
          <li>
            <a
              class="nav-drawer__link"
              href="<?= $hdrEscape($item['href']) ?>"
              <?= $slug === $item['slug'] ? 'aria-current="page"' : '' ?>
            >
              <span><?= $hdrEscape($item['label']) ?></span>
              <?= erc_icon('chevron-right', ['width' => '18', 'height' => '18']) ?>
            </a>
          </li>
<?php endforeach; ?>
          <li>
            <a
              class="nav-drawer__link nav-drawer__link--cta"
              href="/contact"
              <?= $slug === 'contact' ? 'aria-current="page"' : '' ?>
            >
              <span>CONTACT &amp; DISPATCH</span>
              <?= erc_icon('arrow-up-right', ['width' => '18', 'height' => '18']) ?>
            </a>
          </li>
        </ul>
      </nav>

      <div class="nav-drawer__meta">
        <p class="mono">32.8707&deg; N, 97.2621&deg; W</p>
        <p><strong>Watauga Center:</strong> 5621 Bunker Blvd, Watauga, TX 76148 (rear entrance via east-side wooden walkway)</p>
        <p><strong>Hours:</strong> Saturdays 9:00 AM &ndash; 3:00 PM CT; Mon&ndash;Fri by appointment</p>
        <p>
          <strong>Phone:</strong>
          <a class="nav-drawer__tel" href="tel:<?= $hdrEscape($hdrPhoneLink) ?>"><?= $hdrEscape($hdrPhone) ?></a>
        </p>
      </div>
    </div>
  </dialog>
