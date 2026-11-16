<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/icons.php';

$heroEsc = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$heroMedia = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};
?>
<section id="hero" class="section section--hero" aria-labelledby="hero-title" tabindex="-1">
  <div class="container hero__layout">
    <div class="hero__copy">
      <p class="kicker">Lions District 2-E2 &middot; Watauga, Texas</p>
      <h1 id="hero-title" class="display-1 hero__title">Giving clear sight to 40+ nations.</h1>
      <p class="lead hero__lead">
        Every Saturday in North Texas, Lions volunteers clean, neutralize, and grade donated eyeglasses to
        <span class="mono hero__inline-metric">0.25</span> diopters &mdash; shipping
        <span class="mono hero__inline-metric">21,696</span> pairs last year for under
        <span class="mono hero__inline-metric">$0.10</span> a pair.
      </p>

      <div class="hero__actions">
        <a class="btn btn--primary btn--lg" href="#drop-off">
          <span>Find a Drop-Off Bin</span>
          <?= erc_icon('arrow-right', ['width' => '18', 'height' => '18']) ?>
        </a>
        <a class="btn btn--secondary btn--lg" href="#volunteer">
          <span>Volunteer Saturday</span>
        </a>
      </div>
    </div>

    <figure class="hero__figure">
      <div class="hero__frame">
        <img
          src="<?= $heroEsc($heroMedia('images/our-story-group.png')) ?>"
          alt="Founding Lions District 2-E2 Eyeglass Recycling Center volunteers and board members gathered outside the Watauga workshop"
          width="695"
          height="360"
          fetchpriority="high"
          loading="eager"
          decoding="async"
        >
      </div>
      <figcaption class="hero__workshop-strip" aria-label="Watauga Workshop hours and entrance">
        <div class="hero__workshop-item">
          <?= erc_icon('map-pin', ['width' => '16', 'height' => '16', 'class' => 'hero__workshop-icon']) ?>
          <div>
            <span class="hero__workshop-label">Watauga Workshop</span>
            <span class="hero__workshop-value">5621 Bunker Blvd, Watauga, TX 76148</span>
          </div>
        </div>
        <div class="hero__workshop-item">
          <?= erc_icon('clock', ['width' => '16', 'height' => '16', 'class' => 'hero__workshop-icon']) ?>
          <div>
            <span class="hero__workshop-label">Hours</span>
            <span class="hero__workshop-value">Saturdays 9:00 AM &ndash; 3:00 PM</span>
          </div>
        </div>
        <div class="hero__workshop-item">
          <?= erc_icon('navigation', ['width' => '16', 'height' => '16', 'class' => 'hero__workshop-icon']) ?>
          <div>
            <span class="hero__workshop-label">Entrance</span>
            <span class="hero__workshop-value">East-side wooden walkway to rear entrance</span>
          </div>
        </div>
      </figcaption>
    </figure>
  </div>

  <div class="container">
    <dl class="stat-row hero__stats" aria-label="Key workshop metrics">
      <div class="stat-item">
        <dt class="stat-label">Pairs Shipped 2024&ndash;25</dt>
        <dd class="stat-value">21,696</dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">Cost Per Pair</dt>
        <dd class="stat-value">&lt; $0.10</dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">North Texas Drop Bins</dt>
        <dd class="stat-value">214</dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">Named Destinations</dt>
        <dd class="stat-value">38</dd>
      </div>
    </dl>
  </div>
</section>
