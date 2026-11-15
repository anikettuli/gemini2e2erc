<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$hdrEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$initialSection = isset($initialSection) ? (string) $initialSection : '';
$hdrPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$hdrPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');

$navSections = [
    ['id' => 'drop-off',  'label' => 'Drop-Off'],
    ['id' => 'process',   'label' => 'Process'],
    ['id' => 'impact',    'label' => 'Impact'],
    ['id' => 'volunteer', 'label' => 'Volunteer'],
    ['id' => 'about',     'label' => 'About'],
    ['id' => 'contact',   'label' => 'Contact'],
];
?>
<body class="erc-body" data-initial-section="<?= $hdrEscape($initialSection) ?>">
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <header class="site-header" id="siteHeader">
    <div class="site-header__capsule">
      <a
        class="site-brand"
        href="#top"
        aria-label="Lions District 2-E2 Eyeglass Recycling Center — Top"
      >
        <?= erc_optical_reticle() ?>
        <span class="site-brand__wordmark">Lions 2-E2 ERC</span>
      </a>

      <nav class="site-nav" id="primaryNav" aria-label="Primary navigation">
        <ul class="site-nav__list">
<?php foreach ($navSections as $item): ?>
          <li>
            <a
              class="site-nav__link<?= $initialSection === $item['id'] ? ' is-active' : '' ?>"
              href="#<?= $hdrEscape($item['id']) ?>"
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
          aria-pressed="false"
          aria-label="Color theme: Light. Switch to Dark."
          title="Switch theme"
        >
          <span class="theme-toggle__glyph" aria-hidden="true">&#9728;</span>
          <span class="theme-toggle__label" id="themeToggleLabel">Light</span>
        </button>

        <button
          type="button"
          class="nav-toggle-btn"
          id="navDrawerOpen"
          aria-controls="mobileNavDialog"
          aria-expanded="false"
          aria-label="Open navigation menu"
        >
          <?= erc_icon('menu', ['width' => '18', 'height' => '18']) ?>
          <span class="nav-toggle-btn__label">Menu</span>
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
          <li>
            <a class="nav-drawer__link" href="#top">
              <span>Overview</span>
              <?= erc_icon('chevron-right', ['width' => '18', 'height' => '18']) ?>
            </a>
          </li>
<?php foreach ($navSections as $item): ?>
          <li>
            <a
              class="nav-drawer__link<?= $initialSection === $item['id'] ? ' is-active' : '' ?>"
              href="#<?= $hdrEscape($item['id']) ?>"
            >
              <span><?= $hdrEscape($item['label']) ?></span>
              <?= erc_icon('chevron-right', ['width' => '18', 'height' => '18']) ?>
            </a>
          </li>
<?php endforeach; ?>
        </ul>
      </nav>

      <div class="nav-drawer__meta">
        <p><strong>Watauga Center:</strong> 5621 Bunker Blvd, Watauga, TX 76148 (rear entrance via east-side wooden walkway)</p>
        <p><strong>Hours:</strong> Saturdays 9:00 AM &ndash; 3:00 PM; Mon&ndash;Fri by appointment</p>
        <p>
          <strong>Phone:</strong>
          <a class="nav-drawer__tel" href="tel:<?= $hdrEscape($hdrPhoneLink) ?>"><?= $hdrEscape($hdrPhone) ?></a>
        </p>
      </div>
    </div>
  </dialog>
