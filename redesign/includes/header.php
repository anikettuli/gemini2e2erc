<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$hdrEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$hdrMediaUrl = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$pageMeta = isset($pageMeta) && is_array($pageMeta) ? $pageMeta : [];
$slug = (string) ($pageMeta['slug'] ?? 'home');
$bodyClassExtra = !empty($pageMeta['body_class']) ? ' ' . trim((string) $pageMeta['body_class']) : '';

$hdrPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$hdrPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');

$primaryNavItems = [
    ['slug' => 'home', 'href' => '/', 'label' => 'Home'],
    ['slug' => 'donate', 'href' => '/donate', 'label' => 'Donate'],
    ['slug' => 'locations', 'href' => '/locations', 'label' => 'Drop-Off Map'],
    ['slug' => 'volunteer', 'href' => '/volunteer', 'label' => 'Volunteer'],
    ['slug' => 'missions', 'href' => '/missions', 'label' => 'Missions & Impact'],
    ['slug' => 'about', 'href' => '/about', 'label' => 'About'],
];
?>
<body class="erc-body<?= $hdrEscape($bodyClassExtra) ?>" data-page="<?= $hdrEscape($slug) ?>">
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <header class="site-header" id="siteHeader">
    <div class="container site-header__inner">
      <a class="site-brand" href="/">
        <img
          class="site-brand__logo"
          src="<?= $hdrEscape($hdrMediaUrl('e2e2rc_LOGO.png')) ?>"
          alt=""
          width="44"
          height="44"
          decoding="async"
        >
        <span class="site-brand__text">
          <span class="site-brand__title">Lions District 2-E2</span>
          <span class="site-brand__subtitle">Eyeglass Recycling Center</span>
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
        <a
          class="btn btn--primary btn--sm site-nav__cta"
          href="/contact"
          <?= $slug === 'contact' ? 'aria-current="page"' : '' ?>
        >Contact</a>
      </nav>

      <div class="site-header__controls">
        <button
          type="button"
          class="theme-toggle"
          id="themeToggle"
          aria-label="Toggle color theme"
          title="Toggle color theme"
        >
          <?= erc_icon('sun', ['class' => 'erc-icon theme-toggle__sun', 'width' => '20', 'height' => '20']) ?>
          <?= erc_icon('moon', ['class' => 'erc-icon theme-toggle__moon', 'width' => '20', 'height' => '20']) ?>
        </button>

        <button
          type="button"
          class="nav-toggle-btn"
          id="navDrawerOpen"
          aria-controls="mobileNavDialog"
          aria-expanded="false"
          aria-label="Open navigation menu"
        >
          <?= erc_icon('menu', ['width' => '20', 'height' => '20']) ?>
          <span>Menu</span>
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
      <span class="nav-drawer__title" id="mobileNavTitle">Navigation</span>
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
              class="nav-drawer__link"
              href="/contact"
              <?= $slug === 'contact' ? 'aria-current="page"' : '' ?>
            >
              <span>Contact</span>
              <?= erc_icon('chevron-right', ['width' => '18', 'height' => '18']) ?>
            </a>
          </li>
        </ul>
      </nav>

      <div class="nav-drawer__meta">
        <p><strong>Watauga Center:</strong> 5621 Bunker Blvd, Watauga, TX 76148 (rear entrance via east-side wooden walkway)</p>
        <p><strong>Hours:</strong> Saturdays 9:00 AM – 3:00 PM; Mon–Fri by appointment</p>
        <p>
          <strong>Phone:</strong>
          <a href="tel:<?= $hdrEscape($hdrPhoneLink) ?>"><?= $hdrEscape($hdrPhone) ?></a>
        </p>
      </div>
    </div>
  </dialog>
