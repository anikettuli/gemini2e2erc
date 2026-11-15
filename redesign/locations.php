<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Clean display formatting for raw drop-off street addresses without mutating cache lookup keys.
 */
function loc_clean_address(string $rawAddress): string
{
    $cleaned = str_replace(',,', ',', trim($rawAddress));
    return (string) preg_replace('/\s+/', ' ', $cleaned);
}

/**
 * Extract 5-digit Texas ZIP code from an address string.
 */
function loc_extract_zip(string $address): string
{
    if (preg_match('/\b(7\d{4})\b/', $address, $matches)) {
        return $matches[1];
    }
    if (preg_match('/\b(\d{5})\b/', $address, $matches)) {
        return $matches[1];
    }
    return '';
}

/**
 * Build an ordered list (longest-first) of city names present across the dataset.
 * Dynamically adapts as partner bins are added without hardcoded municipality lists.
 *
 * @param array<int, array<string, mixed>> $rawLocations
 * @return array<int, string>
 */
function loc_discover_cities(array $rawLocations): array
{
    $found = [];
    foreach ($rawLocations as $row) {
        if (!is_array($row)) {
            continue;
        }
        $addr = trim((string) ($row['address'] ?? ''));
        if (preg_match('/,\s*([A-Za-z][A-Za-z .\'-]+?)(?:\s*,?\s*(?:TX|Texas))?\s*\d{5}\s*$/i', $addr, $m)) {
            $candidate = trim((string) preg_replace('/[,.]+$/', '', trim($m[1])));
            if ($candidate !== '' && !in_array($candidate, $found, true)) {
                $found[] = $candidate;
            }
        } elseif (preg_match('/,\s*([A-Za-z][A-Za-z .\'-]+?)\s*,?\s*(?:TX|Texas)\s*$/i', $addr, $m)) {
            $candidate = trim((string) preg_replace('/[,.]+$/', '', trim($m[1])));
            if ($candidate !== '' && !in_array($candidate, $found, true)) {
                $found[] = $candidate;
            }
        }
    }

    usort($found, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    return $found;
}

/**
 * Match a drop-off bin record to its municipality using discovered dataset cities.
 * Handles addresses omitting the city token (e.g., "McKinney Eyeworks") via venue name fallback.
 *
 * @param array<int, string> $citiesLongestFirst
 */
function loc_extract_city(string $name, string $address, array $citiesLongestFirst): string
{
    foreach ($citiesLongestFirst as $city) {
        $pattern = '/\b' . preg_quote($city, '/') . '\b/i';
        if (preg_match($pattern, $address)) {
            return $city;
        }
    }
    foreach ($citiesLongestFirst as $city) {
        $pattern = '/\b' . preg_quote($city, '/') . '\b/i';
        if (preg_match($pattern, $name)) {
            return $city;
        }
    }
    return 'Other';
}

/**
 * Build a safe Google Maps Directions link, keeping PO Box venues routable via club name + city/ZIP.
 */
function loc_directions_url(string $name, string $cleanAddress, bool $isPOBox, string $city, string $zip, ?float $lat, ?float $lng): string
{
    if ($lat !== null && $lng !== null && !$isPOBox) {
        $destination = $cleanAddress !== '' ? $cleanAddress : sprintf('%.6F,%.6F', $lat, $lng);
    } elseif ($isPOBox) {
        $destination = trim($name . ' ' . ($city !== 'Other' ? $city : '') . ' TX ' . $zip);
    } else {
        $destination = $cleanAddress !== '' ? $cleanAddress : $name;
    }

    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($destination);
}

$globalPhone     = (string) ($GLOBAL_PHONE ?? '(817) 710-5403');
$globalPhoneLink = (string) ($GLOBAL_PHONE_LINK ?? '+18177105403');
$globalEmail     = (string) ($GLOBAL_EMAIL ?? '2e2erc1854@gmail.com');

$rawLocations = erc_load_json('data/locations.json', []);
$geoCache     = erc_load_json('data/geo_cache.json', []);

if (!is_array($rawLocations)) {
    $rawLocations = [];
}
if (!is_array($geoCache)) {
    $geoCache = [];
}

$citiesLongestFirst = loc_discover_cities($rawLocations);
$locations          = [];
$cityCounts         = [];
$geocodedCount      = 0;
$directoryOnlyCount = 0;

$mainCenterCoords = [
    'lat'     => 32.8707405,
    'lng'     => -97.2620445,
    'address' => '5621 Bunker Blvd, Watauga, TX 76148',
];

foreach ($rawLocations as $entry) {
    if (!is_array($entry)) {
        continue;
    }

    $name       = trim((string) ($entry['name'] ?? ''));
    $rawAddress = (string) ($entry['address'] ?? '');
    $cleanAddr  = loc_clean_address($rawAddress);
    $rawPhone   = trim((string) ($entry['phone'] ?? ''));

    if ($name === '' && $cleanAddr === '') {
        continue;
    }

    $isMainCenter = (bool) preg_match('/5621\s+Bunker\s+Blvd/i', $rawAddress);
    $isLionsHost  = !$isMainCenter && (bool) preg_match('/\blions?\b|\b2-?e2\b/i', $name . ' ' . $cleanAddr);
    $isPOBox      = (bool) preg_match('/^\s*P\.?\s*O\.?\s*Box\b/i', $rawAddress);
    $city         = loc_extract_city($name, $cleanAddr, $citiesLongestFirst);
    $zip          = loc_extract_zip($cleanAddr);

    $lat = null;
    $lng = null;
    $geo = $geoCache[$rawAddress] ?? ($geoCache[trim($rawAddress)] ?? null);
    if (is_array($geo) && isset($geo['lat'], $geo['lng']) && is_numeric($geo['lat']) && is_numeric($geo['lng'])) {
        $lat = (float) $geo['lat'];
        $lng = (float) $geo['lng'];
        $geocodedCount++;
    } else {
        $directoryOnlyCount++;
    }

    $displayPhone = $rawPhone;
    if ($isMainCenter && $displayPhone === '') {
        $displayPhone = $globalPhone;
    }
    $telHref = $displayPhone !== '' ? erc_format_phone_tel($displayPhone) : '';

    if ($isMainCenter && $lat !== null && $lng !== null) {
        $mainCenterCoords['lat'] = $lat;
        $mainCenterCoords['lng'] = $lng;
    }

    $cityCounts[$city] = ($cityCounts[$city] ?? 0) + 1;
    $idx = count($locations);

    $locations[] = [
        'id'           => $idx,
        'serial'       => sprintf('BIN-%03d', $idx + 1),
        'name'         => $name,
        'address'      => $cleanAddr,
        'rawAddress'   => $rawAddress,
        'city'         => $city,
        'zip'          => $zip,
        'phone'        => $displayPhone,
        'rawPhone'     => $rawPhone,
        'tel'          => $telHref,
        'lat'          => $lat,
        'lng'          => $lng,
        'geocoded'     => $lat !== null && $lng !== null,
        'isMainCenter' => $isMainCenter,
        'isLionsHost'  => $isLionsHost,
        'isPOBox'      => $isPOBox,
        'directions'   => loc_directions_url($name, $cleanAddr, $isPOBox, $city, $zip, $lat, $lng),
    ];
}

ksort($cityCounts, SORT_NATURAL | SORT_FLAG_CASE);
$totalLocations = count($locations);
$totalCities    = count($cityCounts);

$topCities = ['Watauga'];
$sortedByVolume = $cityCounts;
arsort($sortedByVolume);
foreach (array_keys($sortedByVolume) as $cityName) {
    if ($cityName !== 'Watauga' && count($topCities) < 9) {
        $topCities[] = $cityName;
    }
}

$ssrSeed = [];
foreach ($locations as $loc) {
    $row = [
        'name'    => $loc['name'],
        'address' => $loc['rawAddress'],
        'phone'   => $loc['rawPhone'],
    ];
    if ($loc['geocoded']) {
        $row['lat'] = $loc['lat'];
        $row['lng'] = $loc['lng'];
    }
    $ssrSeed[] = $row;
}

$ssrSeedJson = (string) json_encode(
    $ssrSeed,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
);

$mainTelHref = erc_format_phone_tel($globalPhone) ?: $globalPhoneLink;
$mainDirectionsUrl = loc_directions_url(
    'District 2E2 Eyeglass Recycling Center',
    $mainCenterCoords['address'],
    false,
    'Watauga',
    '76148',
    $mainCenterCoords['lat'],
    $mainCenterCoords['lng']
);

$pageMeta = [
    'slug'        => 'locations',
    'title'       => 'Geospatial Drop-Off Radar | Lions District 2-E2 Eyeglass Recycling Center',
    'description' => sprintf(
        'Search %d eyeglass drop-off bins across %d North Texas cities on our interactive radar map, or visit Watauga HQ at 5621 Bunker Blvd.',
        $totalLocations,
        $totalCities
    ),
    'canonical'   => '/locations',
    'css'         => ['/vendor/maplibre-gl/maplibre-gl.css', '/css/pages/locations.css'],
    'js_modules'  => ['/js/pages/locations.js'],
    'json_ld'     => [],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main loc-page" tabindex="-1">
  <!-- 1. Monumental v2 Radar Header -->
  <header class="loc-hero" aria-labelledby="loc-hero-heading">
    <div class="container loc-hero__inner">
      <div class="v2-eyebrow">
        <span>DROP-OFF RADAR &middot; DISTRICT 2-E2 NORTH TEXAS</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>DFW OPTICAL COLLECTION GRID</span>
      </div>

      <h1 id="loc-hero-heading" class="v2-display v2-display--hero loc-hero__title">
        <span class="v2-knockout"><?= e((string) $totalLocations) ?></span> DROP BINS.<br>
        <?= e((string) $totalCities) ?> CITIES<span class="v2-dot" aria-hidden="true"></span>
      </h1>

      <div class="loc-hero__row">
        <p class="lead loc-hero__lead">
          Drop off prescription eyeglasses, reading glasses, or sunglasses at any partner collection bin across Dallas&ndash;Fort Worth, or bring multi-item donations directly to our 100% volunteer Watauga Recycling Center.
        </p>

        <div class="loc-hero__telemetry-col">
          <div class="v2-telemetry loc-hero__telemetry">
            <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
            <span>32.8707&deg; N, 97.2621&deg; W &middot; WATAUGA HQ</span>
          </div>

          <p class="v2-note loc-hero__note">
            <span>24/7 drop bin on the east-side walkway!</span>
            <?= erc_curved_arrow('down-left') ?>
          </p>
        </div>
      </div>

      <dl class="loc-stats" aria-label="Drop-off network coverage summary">
        <div class="loc-stat">
          <dt>Total Bins</dt>
          <dd class="loc-stat__num" id="loc-total-count"><?= e((string) $totalLocations) ?></dd>
        </div>
        <div class="loc-stat">
          <dt>Texas Cities</dt>
          <dd class="loc-stat__num" id="loc-city-count"><?= e((string) $totalCities) ?></dd>
        </div>
        <div class="loc-stat">
          <dt>Geocoded Pins</dt>
          <dd class="loc-stat__num" id="loc-mapped-count"><?= e((string) $geocodedCount) ?></dd>
        </div>
        <div class="loc-stat">
          <dt>Directory Only</dt>
          <dd class="loc-stat__num" id="loc-directory-only-count"><?= e((string) $directoryOnlyCount) ?></dd>
        </div>
      </dl>
    </div>
  </header>

  <!-- 2. Watauga HQ Schematic Spotlight & Partner Policy Notice -->
  <section class="loc-highlights" aria-labelledby="loc-spotlight-heading">
    <div class="container loc-highlights-grid">
      <article class="v2-schematic-card v2-schematic-card--split loc-hq-card">
        <div class="loc-hq-card__main">
          <div class="v2-schematic-card__head">
            <span class="v2-schematic-card__num">HQ-00</span>
            <span>PRIMARY RECYCLING CENTER</span>
            <span class="loc-hq-card__pill">WATAUGA, TX</span>
          </div>

          <h2 id="loc-spotlight-heading" class="v2-schematic-card__title">
            Watauga HQ<span class="v2-dot" aria-hidden="true"></span>
          </h2>

          <p class="loc-hq-card__address">5621 Bunker Blvd, Watauga, TX 76148</p>

          <p class="loc-walkway-note">
            Free on-site parking available. Follow the <strong>wooden walkway along the east side</strong> of the building to reach the <strong>rear entrance</strong>.
          </p>

          <div class="loc-hq-grid">
            <div class="loc-detail-block">
              <h3 class="loc-detail-label">
                <?= erc_icon('clock', ['width' => 16, 'height' => 16]) ?>
                <span>Center Hours</span>
              </h3>
              <dl class="loc-hours-list">
                <div class="loc-hours-row">
                  <dt>Saturday</dt>
                  <dd class="loc-mono">9:00 AM - 3:00 PM</dd>
                </div>
                <div class="loc-hours-row">
                  <dt>Monday &ndash; Friday</dt>
                  <dd>By Appointment</dd>
                </div>
                <div class="loc-hours-row">
                  <dt>Sunday</dt>
                  <dd>Closed</dd>
                </div>
              </dl>
            </div>

            <div class="loc-detail-block">
              <h3 class="loc-detail-label">
                <?= erc_icon('glasses', ['width' => 16, 'height' => 16]) ?>
                <span>Accepted at HQ</span>
              </h3>
              <ul class="loc-accepted-list">
                <li>
                  <?= erc_icon('glasses', ['width' => 16, 'height' => 16]) ?>
                  <span>Eyeglasses, sunglasses &amp; broken frames</span>
                </li>
                <li>
                  <?= erc_icon('ear', ['width' => 16, 'height' => 16]) ?>
                  <span>Hearing aids (digital or analog)</span>
                </li>
                <li>
                  <?= erc_icon('smartphone', ['width' => 16, 'height' => 16]) ?>
                  <span>Cell phones (all data wiped)</span>
                </li>
                <li>
                  <?= erc_icon('laptop', ['width' => 16, 'height' => 16]) ?>
                  <span>Windows 11-ready laptops &amp; tablets</span>
                </li>
              </ul>
            </div>
          </div>

          <p class="loc-excluded-note">
            <?= erc_icon('ban', ['width' => 16, 'height' => 16]) ?>
            <span>Not accepted: desktop computers, damaged screens, non-functional keyboards, or electronics with missing parts.</span>
          </p>

          <div class="loc-spotlight-actions">
            <button type="button" class="btn btn--primary loc-hq-btn" id="loc-focus-main">
              <?= erc_icon('locate-fixed', ['width' => 16, 'height' => 16]) ?>
              <span>CENTER RADAR</span>
            </button>
            <a class="btn btn--secondary loc-hq-btn" href="<?= e($mainDirectionsUrl) ?>" target="_blank" rel="noopener noreferrer">
              <span>DIRECTIONS</span>
              <?= erc_icon('arrow-up-right', ['width' => 16, 'height' => 16]) ?>
              <span class="sr-only">to Watauga Recycling Center (opens in new tab)</span>
            </a>
            <a class="btn btn--secondary loc-hq-btn" href="tel:<?= e($mainTelHref) ?>">
              <?= erc_icon('phone', ['width' => 16, 'height' => 16]) ?>
              <span><?= e($globalPhone) ?></span>
            </a>
            <a class="btn btn--secondary loc-hq-btn" href="/donate">
              <?= erc_icon('info', ['width' => 16, 'height' => 16]) ?>
              <span>DONATION GUIDE</span>
            </a>
          </div>
        </div>

        <div class="loc-hq-card__side">
          <div class="v2-schematic-box loc-hq-schematic">
            <span class="v2-schematic-box__caption">FIG. 01 &middot; EAST-SIDE WALKWAY ACCESS</span>
            <svg class="loc-blueprint-svg" viewBox="0 0 320 210" aria-hidden="true" focusable="false">
              <defs>
                <pattern id="locBpGrid" width="20" height="20" patternUnits="userSpaceOnUse">
                  <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-opacity="0.1" stroke-width="0.75" />
                </pattern>
              </defs>
              <rect x="0" y="0" width="320" height="210" rx="10" fill="url(#locBpGrid)" />
              <g font-family="monospace" font-size="9" letter-spacing="1.2" fill="currentColor" opacity="0.75">
                <text x="18" y="24">N &#9650;</text>
                <text x="206" y="24">32.8707&#176; N</text>
              </g>
              <!-- Building footprint -->
              <rect x="46" y="46" width="146" height="96" rx="6" fill="currentColor" fill-opacity="0.06" stroke="currentColor" stroke-width="1.6" stroke-opacity="0.55" />
              <text x="119" y="92" text-anchor="middle" font-family="monospace" font-size="11" font-weight="700" fill="currentColor">5621 BUNKER</text>
              <text x="119" y="108" text-anchor="middle" font-family="monospace" font-size="9" fill="currentColor" opacity="0.72">WATAUGA HQ</text>
              <!-- East-side wooden walkway -->
              <rect x="196" y="44" width="20" height="102" rx="3" fill="none" stroke="var(--cyan)" stroke-width="1.4" stroke-dasharray="3 3" />
              <path d="M 206 154 L 206 38 L 176 38" fill="none" stroke="var(--accent)" stroke-width="2.4" stroke-dasharray="5 4" stroke-linecap="round" stroke-linejoin="round" />
              <polygon points="172,38 180,33 180,43" fill="var(--accent)" />
              <!-- Rear entrance + 24/7 bin beacon -->
              <circle cx="166" cy="38" r="10" fill="var(--accent)" fill-opacity="0.22" stroke="var(--accent)" stroke-width="1.5" />
              <circle cx="166" cy="38" r="4" fill="var(--accent)" />
              <text x="155" y="26" text-anchor="end" font-family="monospace" font-size="8.5" font-weight="700" fill="var(--accent-ink)">REAR ENTRANCE</text>
              <text x="224" y="92" font-family="monospace" font-size="8.5" fill="var(--cyan)">EAST-SIDE</text>
              <text x="224" y="104" font-family="monospace" font-size="8.5" fill="var(--cyan)">WALKWAY</text>
              <!-- Parking + Bunker Blvd -->
              <rect x="46" y="156" width="170" height="20" rx="4" fill="none" stroke="currentColor" stroke-opacity="0.35" stroke-width="1" />
              <text x="131" y="169" text-anchor="middle" font-family="monospace" font-size="8.5" fill="currentColor" opacity="0.8">FREE ON-SITE PARKING</text>
              <line x1="16" y1="190" x2="304" y2="190" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
              <text x="160" y="203" text-anchor="middle" font-family="monospace" font-size="9" letter-spacing="1.6" fill="currentColor" opacity="0.8">BUNKER BLVD &middot; WATAUGA, TX</text>
            </svg>
            <div class="loc-hq-schematic__meta">
              <span>EAST WALKWAY &rarr; REAR DOOR</span>
              <span>FREE PARKING</span>
            </div>
          </div>

          <aside class="loc-policy" aria-labelledby="loc-policy-heading">
            <div class="loc-policy__head">
              <?= erc_icon('triangle-alert', ['width' => 16, 'height' => 16]) ?>
              <span>PARTNER BIN NOTICE</span>
            </div>
            <h3 id="loc-policy-heading" class="loc-policy__title">Check Electronics Policy Before Visiting Partner Bins</h3>
            <p class="loc-policy__body">
              Individual DFW partner bins may accept <strong>only eyeglasses</strong> or a subset of items. Call the listed partner directly, or contact Watauga HQ to verify what a specific box accepts.
            </p>
            <div class="loc-policy__actions">
              <a class="btn btn--secondary loc-policy__btn" href="tel:<?= e($mainTelHref) ?>">
                <?= erc_icon('phone', ['width' => 16, 'height' => 16]) ?>
                <span>Call <?= e($globalPhone) ?></span>
              </a>
              <a class="btn btn--secondary loc-policy__btn" href="mailto:<?= e($globalEmail) ?>">
                <?= erc_icon('mail', ['width' => 16, 'height' => 16]) ?>
                <span>Email <?= e($globalEmail) ?></span>
              </a>
            </div>
          </aside>
        </div>
      </article>
    </div>
  </section>

  <!-- 3. Command-Bar Search & Telemetry Filter Console + Radar Workspace -->
  <section
    class="loc-locator"
    id="loc-app"
    aria-labelledby="loc-locator-heading"
    data-main-lat="<?= e(sprintf('%.7F', $mainCenterCoords['lat'])) ?>"
    data-main-lng="<?= e(sprintf('%.7F', $mainCenterCoords['lng'])) ?>"
    data-global-phone="<?= e($globalPhone) ?>"
    data-global-tel="<?= e($mainTelHref) ?>"
    data-global-email="<?= e($globalEmail) ?>"
  >
    <!-- Inverted Contrast Sheet Command Console -->
    <div class="v2-sheet loc-console">
      <div class="v2-sheet__inner">
        <div class="loc-locator-header">
          <div class="loc-locator-header__copy">
            <div class="v2-eyebrow">
              <span>COMMAND BAR &middot; FUSE.JS FUZZY SEARCH</span>
              <span class="v2-eyebrow__rule" aria-hidden="true"></span>
              <span>PRESS <kbd class="loc-kbd-inline">/</kbd> TO SEARCH</span>
            </div>
            <h2 id="loc-locator-heading" class="v2-display v2-display--md">
              Geospatial Drop-Off Radar<span class="v2-dot" aria-hidden="true"></span>
            </h2>
          </div>

          <div class="loc-view-switch" role="group" aria-label="Radar workspace view mode">
            <button type="button" class="loc-view-btn" data-view-mode="both" aria-pressed="true">
              <?= erc_icon('map-pin', ['width' => 16, 'height' => 16]) ?>
              <span>Directory + Map</span>
            </button>
            <button type="button" class="loc-view-btn" data-view-mode="list" aria-pressed="false">
              <?= erc_icon('list', ['width' => 16, 'height' => 16]) ?>
              <span>Directory Only</span>
            </button>
            <button type="button" class="loc-view-btn" data-view-mode="map" aria-pressed="false">
              <?= erc_icon('map', ['width' => 16, 'height' => 16]) ?>
              <span>Map Only</span>
            </button>
          </div>
        </div>

        <form class="loc-controls" id="loc-filter-form" role="search" novalidate>
          <div class="loc-field loc-field--search">
            <label class="loc-label" for="loc-search">Search drop-off locations</label>
            <p class="loc-field-hint" id="loc-search-hint">Business name, street address, city, 5-digit ZIP, or phone number.</p>
            <div class="loc-input-shell">
              <span class="loc-input-icon" aria-hidden="true">
                <?= erc_icon('search', ['width' => 18, 'height' => 18]) ?>
              </span>
              <input
                id="loc-search"
                name="q"
                type="search"
                inputmode="search"
                autocomplete="off"
                spellcheck="false"
                placeholder="e.g. Keller, 76148, Costco, Lions"
                aria-describedby="loc-search-hint"
              >
              <kbd class="loc-kbd" id="loc-search-kbd" aria-hidden="true">/</kbd>
              <button type="button" id="loc-search-clear" class="loc-clear-btn" aria-label="Clear search query" hidden>
                <?= erc_icon('x', ['width' => 18, 'height' => 18]) ?>
              </button>
            </div>
          </div>

          <div class="loc-field loc-field--city">
            <label class="loc-label" for="loc-city-select">Filter by Texas city</label>
            <p class="loc-field-hint" id="loc-city-hint">Browse any of <?= e((string) $totalCities) ?> North Texas cities.</p>
            <select id="loc-city-select" name="city" aria-describedby="loc-city-hint">
              <option value="all">All Cities (<?= e((string) $totalLocations) ?>)</option>
              <?php foreach ($cityCounts as $cityName => $count): ?>
                <option value="<?= e($cityName) ?>"><?= e($cityName) ?> (<?= e((string) $count) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="loc-field-actions">
            <button type="button" id="loc-near-me" class="btn btn--primary loc-console-btn">
              <?= erc_icon('locate-fixed', ['width' => 18, 'height' => 18]) ?>
              <span>NEAR ME</span>
            </button>
            <button type="button" id="loc-reset" class="btn btn--secondary loc-console-btn" hidden>
              <?= erc_icon('refresh-cw', ['width' => 18, 'height' => 18]) ?>
              <span>RESET</span>
            </button>
          </div>
        </form>

        <div class="loc-chips-region" aria-label="Quick city telemetry filters">
          <div class="loc-chips-track" id="loc-city-chips">
            <button type="button" class="loc-chip is-active" data-city-chip="all" aria-pressed="true">
              <span>All Cities</span>
              <span class="loc-chip-count"><?= e((string) $totalLocations) ?></span>
            </button>
            <?php foreach ($topCities as $chipCity): ?>
              <?php $chipCount = $cityCounts[$chipCity] ?? 0; ?>
              <button type="button" class="loc-chip" data-city-chip="<?= e($chipCity) ?>" aria-pressed="false">
                <span><?= e($chipCity) ?></span>
                <span class="loc-chip-count"><?= e((string) $chipCount) ?></span>
              </button>
            <?php endforeach; ?>
            <button
              type="button"
              class="loc-chip loc-chip--drawer"
              id="loc-open-cities-dialog"
              aria-haspopup="dialog"
              aria-controls="loc-cities-dialog"
            >
              <?= erc_icon('sliders-horizontal', ['width' => 15, 'height' => 15]) ?>
              <span>All <?= e((string) $totalCities) ?> Cities</span>
            </button>
          </div>
        </div>

        <div id="loc-geo-banner" class="loc-geo-banner" role="region" aria-label="Geolocation radar notice" hidden>
          <p id="loc-geo-message" class="loc-geo-message"></p>
          <div class="loc-geo-actions" id="loc-geo-actions"></div>
        </div>
      </div>
    </div>

    <!-- Primary Stage Radar Map + Schematic Directory Workspace -->
    <div class="container loc-workspace-wrap">
      <div class="loc-results-bar">
        <p id="loc-results-summary" class="loc-results-summary">
          Showing <strong><?= e((string) $totalLocations) ?></strong> of <?= e((string) $totalLocations) ?> locations
          &middot; <?= e((string) $geocodedCount) ?> geocoded pins
          &middot; <?= e((string) $directoryOnlyCount) ?> directory only
        </p>
        <p id="loc-status-live" class="sr-only" aria-live="polite" aria-atomic="true"></p>
      </div>

      <div class="loc-workspace" id="loc-workspace" data-view="both">
        <div class="loc-directory-panel" id="loc-directory-panel" aria-label="Drop-off bin directory list">
          <ul id="loc-list" class="loc-list">
            <?php foreach ($locations as $loc): ?>
              <?php
              $cardClasses = 'loc-card';
              if ($loc['isMainCenter']) {
                  $cardClasses .= ' loc-card--hq';
              }
              if (!$loc['geocoded']) {
                  $cardClasses .= ' loc-card--directory-only';
              }
              ?>
              <li class="loc-list-item">
                <article class="<?= e($cardClasses) ?>" data-loc-id="<?= e((string) $loc['id']) ?>">
                  <div class="loc-card-telemetry">
                    <span class="loc-card-serial"><?= e($loc['serial']) ?></span>
                    <span class="loc-card-city"><?= e($loc['city']) ?></span>
                  </div>

                  <div class="loc-card-header">
                    <h3 class="loc-card-title"><?= e($loc['name']) ?></h3>
                    <div class="loc-card-badges">
                      <?php if ($loc['isMainCenter']): ?>
                        <span class="loc-badge loc-badge--hq">Watauga HQ</span>
                      <?php endif; ?>
                      <?php if ($loc['isLionsHost']): ?>
                        <span class="loc-badge loc-badge--lions">Lions Club Host</span>
                      <?php endif; ?>
                      <?php if ($loc['isPOBox']): ?>
                        <span class="loc-badge loc-badge--neutral">P.O. Box</span>
                      <?php endif; ?>
                      <?php if (!$loc['geocoded']): ?>
                        <span class="loc-badge loc-badge--dir-only">Directory Only</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <p class="loc-card-addr">
                    <?= erc_icon('map-pin', ['width' => 16, 'height' => 16]) ?>
                    <span><?= e($loc['address']) ?></span>
                  </p>

                  <p class="loc-card-phone">
                    <?= erc_icon('phone', ['width' => 16, 'height' => 16]) ?>
                    <?php if ($loc['phone'] !== '' && $loc['tel'] !== ''): ?>
                      <a href="tel:<?= e($loc['tel']) ?>"><?= e($loc['phone']) ?></a>
                    <?php else: ?>
                      <span class="loc-phone-missing">Phone not listed &mdash; call <?= e($globalPhone) ?> to verify</span>
                    <?php endif; ?>
                  </p>

                  <div class="loc-card-actions">
                    <?php if ($loc['geocoded']): ?>
                      <button type="button" class="loc-card-btn loc-card-btn--map" data-action="show-on-map" data-loc-id="<?= e((string) $loc['id']) ?>">
                        <?= erc_icon('locate-fixed', ['width' => 16, 'height' => 16]) ?>
                        <span>Show on Radar</span>
                      </button>
                    <?php endif; ?>
                    <a class="loc-card-btn loc-card-btn--dir" href="<?= e($loc['directions']) ?>" target="_blank" rel="noopener noreferrer">
                      <span>Directions</span>
                      <?= erc_icon('arrow-up-right', ['width' => 16, 'height' => 16]) ?>
                      <span class="sr-only">to <?= e($loc['name']) ?> (opens in new tab)</span>
                    </a>
                  </div>
                </article>
              </li>
            <?php endforeach; ?>
          </ul>

          <div id="loc-empty" class="loc-empty" hidden>
            <?= erc_icon('search', ['width' => 28, 'height' => 28]) ?>
            <h3>No matching drop-off bins</h3>
            <p>Try a broader keyword, choose a nearby Texas city, or reset filters to view all <?= e((string) $totalLocations) ?> locations.</p>
            <button type="button" id="loc-empty-reset" class="btn btn--primary">Show All <?= e((string) $totalLocations) ?> Locations</button>
          </div>
        </div>

        <aside class="loc-map-panel" id="loc-map-panel" aria-label="Interactive DFW geospatial radar map">
          <div class="loc-map-frame">
            <div
              id="locations-map"
              class="loc-map-canvas"
              role="region"
              aria-label="Interactive radar map of Lions District 2-E2 eyeglass drop-off bins"
              tabindex="0"
            ></div>

            <div id="loc-map-fallback" class="loc-map-fallback" role="status" hidden>
              <?= erc_icon('circle-alert', ['width' => 26, 'height' => 26]) ?>
              <h3>Interactive Radar Temporarily Unavailable</h3>
              <p id="loc-map-fallback-msg">
                The map display could not load in this browser context, but every drop-off bin and Google Maps Directions link remains available in the directory.
              </p>
              <button type="button" id="loc-map-retry" class="btn btn--primary">
                <?= erc_icon('refresh-cw', ['width' => 16, 'height' => 16]) ?>
                <span>RETRY RADAR</span>
              </button>
            </div>
          </div>

          <section
            id="loc-selected-card"
            class="loc-selected-card"
            aria-label="Selected drop-off location telemetry"
            aria-live="polite"
            hidden
          ></section>

          <ul class="loc-legend" aria-label="Radar marker key">
            <li class="loc-legend-item">
              <span class="loc-legend-swatch loc-legend-swatch--hq" aria-hidden="true"></span>
              <span>Watauga HQ (5621 Bunker Blvd)</span>
            </li>
            <li class="loc-legend-item">
              <span class="loc-legend-swatch loc-legend-swatch--cluster" aria-hidden="true"></span>
              <span>Clustered Bins (click to zoom)</span>
            </li>
            <li class="loc-legend-item">
              <span class="loc-legend-swatch loc-legend-swatch--pin" aria-hidden="true"></span>
              <span>Partner Drop-Off Pin</span>
            </li>
          </ul>
        </aside>
      </div>
    </div>

    <!-- All-34-Cities Native Modal Drawer -->
    <dialog
      id="loc-cities-dialog"
      class="erc-dialog loc-cities-dialog"
      closedby="any"
      aria-labelledby="loc-cities-dialog-title"
    >
      <div class="loc-cities-dialog__head">
        <div>
          <p class="loc-cities-dialog__eyebrow">DISTRICT 2-E2 COVERAGE</p>
          <h3 id="loc-cities-dialog-title" class="loc-cities-dialog__title">All <?= e((string) $totalCities) ?> Texas Municipalities</h3>
        </div>
        <button type="button" class="loc-cities-dialog__close" id="loc-cities-dialog-close" aria-label="Close city filter drawer">
          <?= erc_icon('x', ['width' => 20, 'height' => 20]) ?>
        </button>
      </div>

      <div class="loc-cities-dialog__body" id="loc-cities-dialog-list">
        <button type="button" class="loc-dialog-city is-active" data-dialog-city="all" aria-pressed="true">
          <span>All Cities</span>
          <span class="loc-chip-count"><?= e((string) $totalLocations) ?></span>
        </button>
        <?php foreach ($cityCounts as $cityName => $count): ?>
          <button type="button" class="loc-dialog-city" data-dialog-city="<?= e($cityName) ?>" aria-pressed="false">
            <span><?= e($cityName) ?></span>
            <span class="loc-chip-count"><?= e((string) $count) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </dialog>
  </section>

  <script type="application/json" id="locations-ssr-seed"><?= $ssrSeedJson ?></script>
</main>
<?php
require __DIR__ . '/includes/footer.php';
