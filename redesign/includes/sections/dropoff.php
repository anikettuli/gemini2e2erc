<?php
declare(strict_types=1);

$dropoffRaw = function_exists('erc_load_json') && defined('ERC_DATA_DIR')
    ? erc_load_json(ERC_DATA_DIR . '/locations.json', [])
    : [];
$dropoffGeo = function_exists('erc_load_json') && defined('ERC_DATA_DIR')
    ? erc_load_json(ERC_DATA_DIR . '/geo_cache.json', [])
    : [];

if (!is_array($dropoffRaw)) {
    $dropoffRaw = [];
}
if (!is_array($dropoffGeo)) {
    $dropoffGeo = [];
}

$dropoffSeed = [];
foreach ($dropoffRaw as $loc) {
    if (!is_array($loc)) {
        continue;
    }

    $address = isset($loc['address']) && is_string($loc['address']) ? $loc['address'] : '';
    $hasInlineCoords = isset($loc['lat'], $loc['lng']) && is_numeric($loc['lat']) && is_numeric($loc['lng']);

    if ($hasInlineCoords) {
        $loc['lat'] = (float) $loc['lat'];
        $loc['lng'] = (float) $loc['lng'];
    } elseif ($address !== '' && isset($dropoffGeo[$address]) && is_array($dropoffGeo[$address])) {
        $cached = $dropoffGeo[$address];
        if (isset($cached['lat'], $cached['lng']) && is_numeric($cached['lat']) && is_numeric($cached['lng'])) {
            $loc['lat'] = (float) $cached['lat'];
            $loc['lng'] = (float) $cached['lng'];
        }
    }

    $dropoffSeed[] = $loc;
}

$dropoffEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$orgPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$orgPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');
$orgEmail = (string) ($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');
$totalSeedCount = count($dropoffSeed);
?>
<section id="drop-off" class="section section--alt dropoff-section" aria-labelledby="dropoff-title" tabindex="-1">
  <div class="container">
    <div class="section-head dropoff-head">
      <p class="kicker">01 &middot; Donate &amp; Drop Off</p>
      <h2 id="dropoff-title" class="h2">Drop off used eyewear across 34 North Texas cities.</h2>
      <p class="lead">
        Donate eyewear, hearing aids, phones, and Windows 11 laptops at <?= $dropoffEscape((string) $totalSeedCount) ?> DFW bins or Watauga HQ.
      </p>
    </div>

    <div class="dropoff-accepted" aria-label="Accepted donation items and mailing rules">
      <div class="dropoff-accepted-grid" role="list">
        <article class="card dropoff-item-card" role="listitem">
          <div class="dropoff-item-card__icon" aria-hidden="true">
            <?= erc_icon('glasses', ['width' => '20', 'height' => '20']) ?>
          </div>
          <div class="dropoff-item-card__content">
            <h3 class="dropoff-item-card__title">Eyeglasses &amp; Sunglasses</h3>
            <p class="dropoff-item-card__desc">
              Prescription glasses, readers, sunglasses, frames, and loose lenses in any condition (intact or broken &mdash; metal scrap funds shipping).
            </p>
          </div>
        </article>

        <article class="card dropoff-item-card" role="listitem">
          <div class="dropoff-item-card__icon" aria-hidden="true">
            <?= erc_icon('ear', ['width' => '20', 'height' => '20']) ?>
          </div>
          <div class="dropoff-item-card__content">
            <h3 class="dropoff-item-card__title">Hearing Aids</h3>
            <p class="dropoff-item-card__desc">
              Digital or analog hearing aids in working or non-working condition.
            </p>
          </div>
        </article>

        <article class="card dropoff-item-card" role="listitem">
          <div class="dropoff-item-card__icon" aria-hidden="true">
            <?= erc_icon('smartphone', ['width' => '20', 'height' => '20']) ?>
          </div>
          <div class="dropoff-item-card__content">
            <h3 class="dropoff-item-card__title">Cell Phones</h3>
            <p class="dropoff-item-card__desc">
              Mobile phones in any condition (please factory-reset / wipe personal data first).
            </p>
          </div>
        </article>

        <article class="card dropoff-item-card" role="listitem">
          <div class="dropoff-item-card__icon" aria-hidden="true">
            <?= erc_icon('laptop', ['width' => '20', 'height' => '20']) ?>
          </div>
          <div class="dropoff-item-card__content">
            <h3 class="dropoff-item-card__title">Windows 11 Laptops &amp; Tablets</h3>
            <p class="dropoff-item-card__desc">
              Working laptops and tablets with Windows 11 installed or Windows 11-ready.
            </p>
          </div>
        </article>
      </div>

      <div class="dropoff-info-bar">
        <div class="dropoff-info-bar__item">
          <span class="dropoff-info-bar__label">
            <?= erc_icon('ban', ['width' => '15', 'height' => '15']) ?>
            <span>Not Accepted</span>
          </span>
          <p class="dropoff-info-bar__text">
            Desktop computers, cracked or damaged screens, broken keyboards, or devices missing parts.
          </p>
        </div>

        <div class="dropoff-info-bar__item">
          <span class="dropoff-info-bar__label">
            <?= erc_icon('info', ['width' => '15', 'height' => '15']) ?>
            <span>Partner Bin Note</span>
          </span>
          <p class="dropoff-info-bar__text">
            Some community host bins accept eyeglasses only &mdash; bring electronics or hearing aids directly to
            <strong>Watauga HQ (5621 Bunker Blvd)</strong> or call
            <span class="mono"><?= $dropoffEscape($orgPhone) ?></span> ahead.
          </p>
        </div>

        <div class="dropoff-info-bar__item">
          <span class="dropoff-info-bar__label">
            <?= erc_icon('mail', ['width' => '15', 'height' => '15']) ?>
            <span>Mail-In &amp; Checks</span>
          </span>
          <p class="dropoff-info-bar__text">
            Pad bulk items in a sturdy box (email <span class="mono"><?= $dropoffEscape($orgEmail) ?></span> ahead);
            mail 501(c)(3) checks to <span class="mono">P.O. Box 1854, Keller, TX 76244</span>.
          </p>
        </div>
      </div>
    </div>

    <div
      id="locApp"
      class="dropoff-locator"
      data-main-lat="32.8707405"
      data-main-lng="-97.2620445"
      data-global-phone="<?= $dropoffEscape($orgPhone) ?>"
      aria-label="North Texas drop-off bin finder"
    >
      <div class="dropoff-toolbar">
        <form id="locFilterForm" class="dropoff-toolbar__controls" role="search" aria-label="Search drop-off bins">
          <div class="dropoff-search-wrap">
            <label for="locSearch" class="sr-only">
              Search drop-off bins by business name, street address, city, ZIP code, or Lions Club
            </label>
            <span class="dropoff-search-wrap__icon" aria-hidden="true">
              <?= erc_icon('search', ['width' => '17', 'height' => '17']) ?>
            </span>
            <input
              id="locSearch"
              class="input dropoff-search-input"
              type="search"
              placeholder="Search name, street, city, ZIP, or Lions Club..."
              autocomplete="off"
              spellcheck="false"
            >
            <kbd id="locSearchKbd" class="dropoff-kbd" aria-hidden="true">/</kbd>
            <button
              id="locSearchClear"
              class="dropoff-search-clear"
              type="button"
              aria-label="Clear search"
              hidden
            >
              <?= erc_icon('x', ['width' => '16', 'height' => '16']) ?>
            </button>
          </div>

          <div class="dropoff-city-wrap">
            <label for="locCitySelect" class="sr-only">Filter by North Texas city</label>
            <select id="locCitySelect" class="select dropoff-city-select">
              <option value="all">All 34 Cities (<?= $dropoffEscape((string) $totalSeedCount) ?>)</option>
            </select>
          </div>

          <div class="dropoff-toolbar__actions">
            <button
              id="locNearMe"
              class="btn btn--secondary btn--sm dropoff-near-btn"
              type="button"
              aria-pressed="false"
            >
              <?= erc_icon('locate-fixed', ['width' => '16', 'height' => '16']) ?>
              <span>Near Me</span>
            </button>

            <button
              id="locFocusMain"
              class="btn btn--secondary btn--sm dropoff-hq-btn"
              type="button"
              aria-label="Center map on Watauga HQ, 5621 Bunker Blvd, 24/7 East-Side Walkway Drop Bin"
            >
              <?= erc_icon('map-pin', ['width' => '16', 'height' => '16']) ?>
              <span class="dropoff-hq-btn__full">Watauga HQ</span>
              <span class="dropoff-hq-btn__short" aria-hidden="true">HQ</span>
            </button>
          </div>
        </form>

        <div class="dropoff-toolbar__meta">
          <p id="locResultsSummary" class="dropoff-summary">
            Showing <?= $dropoffEscape((string) $totalSeedCount) ?> locations across 34 cities
          </p>
          <div class="dropoff-hq-strip">
            <span class="dropoff-hq-strip__note mono">
              5621 Bunker Blvd &middot; 24/7 East-Side Walkway Drop Bin
            </span>
            <button id="locReset" class="btn btn--ghost btn--sm dropoff-reset-btn" type="button" hidden>
              Reset filters
            </button>
          </div>
        </div>

        <div id="locGeoBanner" class="dropoff-geo-banner" role="status" hidden>
          <p id="locGeoMessage" class="dropoff-geo-banner__msg"></p>
          <div id="locGeoActions" class="dropoff-geo-banner__actions"></div>
        </div>

        <p id="locStatusLive" class="sr-only" aria-live="polite" aria-atomic="true"></p>
      </div>

      <div id="dropoffWorkspace" class="dropoff-workspace">
        <div id="dropoffListPanel" class="dropoff-list-col">
          <ol id="dropoffList" class="dropoff-list" aria-label="Drop-off bin directory"></ol>

          <div id="dropoffPagination" class="dropoff-pagination" hidden>
            <button id="locShowMore" class="btn btn--secondary dropoff-show-more" type="button">
              Show More Locations (+16)
            </button>
          </div>

          <div id="dropoffEmpty" class="dropoff-empty" hidden>
            <p class="dropoff-empty__title">No drop-off bins match that filter.</p>
            <p class="dropoff-empty__body">
              Clear your search or switch back to all 34 North Texas cities.
            </p>
            <button id="locEmptyReset" class="btn btn--secondary btn--sm" type="button">
              Show All <?= $dropoffEscape((string) $totalSeedCount) ?> Locations
            </button>
          </div>
        </div>

        <div id="dropoffMapPanel" class="dropoff-map-col">
          <div class="dropoff-map-frame">
            <div
              id="dropoffMap"
              class="dropoff-map"
              role="region"
              aria-label="Interactive map of North Texas drop-off bins"
            ></div>

            <div id="dropoffMapFallback" class="dropoff-map-fallback" hidden>
              <?= erc_icon('map', ['width' => '24', 'height' => '24']) ?>
              <p id="dropoffMapFallbackMsg" class="dropoff-map-fallback__msg">
                Interactive map is unavailable in this browser context. All <?= $dropoffEscape((string) $totalSeedCount) ?> drop-off locations and Directions links remain available in the directory.
              </p>
              <button id="locMapRetry" class="btn btn--secondary btn--sm" type="button">
                Retry Map
              </button>
            </div>
          </div>

          <aside id="locSelectedCard" class="loc-selected-card" aria-live="polite" hidden></aside>
        </div>
      </div>
    </div>

    <script type="application/json" id="dropoffLocationsSeed"><?= json_encode($dropoffSeed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  </div>
</section>
