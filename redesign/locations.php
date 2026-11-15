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
    'address' => '5621 Bunker Blvd., Watauga, TX 76148',
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

    $locations[] = [
        'id'           => count($locations),
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
    'title'       => 'Drop-Off Locations & Interactive Map | Lions District 2-E2 Eyeglass Recycling Center',
    'description' => sprintf(
        'Find %d eyeglass drop-off bins across %d North Texas cities or bring qualifying glasses, hearing aids, cell phones, and Windows 11 laptops to our Watauga Recycling Center.',
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
  <header class="loc-hero">
    <div class="container">
      <p class="loc-eyebrow">
        <?= erc_icon('map-pin', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
        <span>Lions District 2-E2 · North Texas Drop-Off Network</span>
      </p>
      <h1 class="loc-hero-title">Find an Eyeglass Drop-Off Bin Near You</h1>
      <p class="loc-hero-lead">
        Drop off prescription glasses, reading glasses, or sunglasses at any community partner bin across Dallas–Fort Worth, or visit our Watauga Recycling Center on Saturdays with multi-item donations.
      </p>

      <dl class="loc-stats" aria-label="Drop-off network coverage summary">
        <div class="loc-stat">
          <dt>Total Locations</dt>
          <dd class="loc-stat-num" id="loc-total-count"><?= e((string) $totalLocations) ?></dd>
        </div>
        <div class="loc-stat">
          <dt>Texas Cities Served</dt>
          <dd class="loc-stat-num" id="loc-city-count"><?= e((string) $totalCities) ?></dd>
        </div>
        <div class="loc-stat">
          <dt>Mapped Map Pins</dt>
          <dd class="loc-stat-num" id="loc-mapped-count"><?= e((string) $geocodedCount) ?></dd>
        </div>
        <div class="loc-stat">
          <dt>Directory Listings</dt>
          <dd class="loc-stat-num" id="loc-directory-only-count"><?= e((string) $directoryOnlyCount) ?></dd>
        </div>
      </dl>
    </div>
  </header>

  <section class="loc-highlights" aria-labelledby="loc-spotlight-heading">
    <div class="container loc-highlights-grid">
      <article class="loc-spotlight">
        <div class="loc-spotlight-head">
          <span class="loc-badge loc-badge--hq">
            <?= erc_icon('building-2', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
            <span>Main Recycling Center</span>
          </span>
          <h2 id="loc-spotlight-heading" class="loc-spotlight-title">Watauga Eyeglass Recycling Center</h2>
        </div>

        <div class="loc-spotlight-meta">
          <div class="loc-detail-block">
            <h3 class="loc-detail-label">
              <?= erc_icon('map-pin', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
              <span>Address &amp; Rear Entrance Access</span>
            </h3>
            <p class="loc-detail-primary">5621 Bunker Blvd., Watauga, TX 76148</p>
            <p class="loc-walkway-note">
              Walk along the <strong>wooden walkway on the east side of the building to the rear entrance</strong>. Free on-site parking is available.
            </p>
          </div>

          <div class="loc-detail-block">
            <h3 class="loc-detail-label">
              <?= erc_icon('clock', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
              <span>Drop-Off Hours</span>
            </h3>
            <dl class="loc-hours-list">
              <div class="loc-hours-row">
                <dt>Saturday</dt>
                <dd class="loc-mono">9:00 AM - 3:00 PM</dd>
              </div>
              <div class="loc-hours-row">
                <dt>Monday – Friday</dt>
                <dd>By Appointment</dd>
              </div>
              <div class="loc-hours-row">
                <dt>Sunday</dt>
                <dd>Closed</dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="loc-accepted-box">
          <h3 class="loc-accepted-heading">Accepted at Watauga Main Center</h3>
          <ul class="loc-accepted-list">
            <li>
              <?= erc_icon('glasses', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
              <span>Qualifying eyeglasses, sunglasses &amp; broken frames</span>
            </li>
            <li>
              <?= erc_icon('ear', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
              <span>Hearing aids</span>
            </li>
            <li>
              <?= erc_icon('smartphone', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
              <span>Wiped cell phones</span>
            </li>
            <li>
              <?= erc_icon('laptop', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
              <span>Windows 11-ready or installed laptops &amp; tablets</span>
            </li>
          </ul>
          <p class="loc-excluded-note">
            <?= erc_icon('ban', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
            <span>Not accepted: desktop computers, damaged screens, non-functional keyboards, or laptops/tablets with missing parts.</span>
          </p>
        </div>

        <div class="loc-spotlight-actions">
          <button type="button" class="loc-btn loc-btn--primary" id="loc-focus-main">
            <?= erc_icon('map-pin', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            <span>Center Map on Watauga HQ</span>
          </button>
          <a class="loc-btn loc-btn--secondary" href="<?= e($mainDirectionsUrl) ?>" target="_blank" rel="noopener noreferrer">
            <?= erc_icon('navigation', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            <span>Directions</span>
            <?= erc_icon('external-link', ['width' => 15, 'height' => 15, 'aria-hidden' => 'true']) ?>
            <span class="sr-only">to Watauga Recycling Center (opens in new tab)</span>
          </a>
          <a class="loc-btn loc-btn--ghost" href="tel:<?= e($mainTelHref) ?>">
            <?= erc_icon('phone', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            <span><?= e($globalPhone) ?></span>
          </a>
          <a class="loc-btn loc-btn--ghost" href="/donate">
            <?= erc_icon('info', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            <span>Donation Guidelines</span>
          </a>
        </div>
      </article>

      <aside class="loc-policy" aria-labelledby="loc-policy-heading">
        <span class="loc-badge loc-badge--warn">
          <?= erc_icon('triangle-alert', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
          <span>Partner Bin Policy Notice</span>
        </span>
        <h2 id="loc-policy-heading" class="loc-policy-title">Check Before Bringing Electronics or Hearing Aids to Partner Bins</h2>
        <p class="loc-policy-body">
          Individual DFW community partner drop-off bins may accept <strong>only eyeglasses</strong> or only a subset of items. Some clinics and libraries cannot store laptops, tablets, cell phones, or hearing aids.
        </p>
        <p class="loc-policy-body">
          Please <strong>call the partner location directly</strong> before visiting, or call our Main Center at
          <a href="tel:<?= e($mainTelHref) ?>"><?= e($globalPhone) ?></a>
          (or email <a href="mailto:<?= e($globalEmail) ?>"><?= e($globalEmail) ?></a>) to confirm what a specific bin accepts.
        </p>
        <p class="loc-policy-tip">
          All qualifying items — including Windows 11 laptops, tablets, cell phones, and hearing aids — are accepted directly at our Watauga Main Center during Saturday hours or by weekday appointment.
        </p>
      </aside>
    </div>
  </section>

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
    <div class="container">
      <div class="loc-locator-header">
        <div>
          <h2 id="loc-locator-heading" class="loc-section-title">Interactive Drop-Off Map &amp; Directory</h2>
          <p class="loc-section-sub">
            Search across all <?= e((string) $totalLocations) ?> locations, filter by Texas city, or sort nearest bins from your location.
          </p>
        </div>

        <div class="loc-view-switch" role="group" aria-label="Mobile view layout">
          <button type="button" class="loc-view-btn" data-view-mode="both" aria-pressed="true">
            <?= erc_icon('map-pin', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
            <span>Map &amp; List</span>
          </button>
          <button type="button" class="loc-view-btn" data-view-mode="map" aria-pressed="false">
            <?= erc_icon('map', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
            <span>Map</span>
          </button>
          <button type="button" class="loc-view-btn" data-view-mode="list" aria-pressed="false">
            <?= erc_icon('list', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
            <span>List</span>
          </button>
        </div>
      </div>

      <form class="loc-controls" id="loc-filter-form" role="search" novalidate>
        <div class="loc-field loc-field--search">
          <label class="loc-label" for="loc-search">Search drop-off locations</label>
          <p class="loc-field-hint" id="loc-search-hint">Enter a business name, street address, city, 5-digit ZIP code, or phone number.</p>
          <div class="loc-input-shell">
            <span class="loc-input-icon" aria-hidden="true">
              <?= erc_icon('search', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            </span>
            <input
              id="loc-search"
              name="q"
              type="search"
              inputmode="search"
              autocomplete="off"
              spellcheck="false"
              placeholder="e.g. Keller, 76148, Costco"
              aria-describedby="loc-search-hint"
            >
            <button type="button" id="loc-search-clear" class="loc-clear-btn" aria-label="Clear search query" hidden>
              <?= erc_icon('x', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
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
          <button type="button" id="loc-near-me" class="loc-btn loc-btn--secondary">
            <?= erc_icon('locate-fixed', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            <span>Near Me</span>
          </button>
          <button type="button" id="loc-reset" class="loc-btn loc-btn--ghost" hidden>
            <?= erc_icon('refresh-cw', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
            <span>Reset Filters</span>
          </button>
        </div>
      </form>

      <div class="loc-chips-region" aria-label="Quick city filters">
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
          <button type="button" class="loc-chip loc-chip--toggle" id="loc-toggle-all-cities" aria-expanded="false" aria-controls="loc-all-cities-drawer">
            <?= erc_icon('filter', ['width' => 15, 'height' => 15, 'aria-hidden' => 'true']) ?>
            <span>More Cities (<?= e((string) max(0, $totalCities - count($topCities))) ?>)</span>
          </button>
        </div>

        <div class="loc-all-cities-drawer" id="loc-all-cities-drawer" hidden>
          <?php foreach ($cityCounts as $cityName => $count): ?>
            <?php if (!in_array($cityName, $topCities, true)): ?>
              <button type="button" class="loc-chip" data-city-chip="<?= e($cityName) ?>" aria-pressed="false">
                <span><?= e($cityName) ?></span>
                <span class="loc-chip-count"><?= e((string) $count) ?></span>
              </button>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="loc-geo-banner" class="loc-geo-banner" role="region" aria-label="Location sorting notice" hidden>
        <p id="loc-geo-message" class="loc-geo-message"></p>
        <div class="loc-geo-actions" id="loc-geo-actions"></div>
      </div>

      <div class="loc-results-bar">
        <p id="loc-results-summary" class="loc-results-summary">
          Showing <strong><?= e((string) $totalLocations) ?></strong> of <?= e((string) $totalLocations) ?> locations
          · <?= e((string) $geocodedCount) ?> mapped pins
          · <?= e((string) $directoryOnlyCount) ?> directory listings
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
                  <div class="loc-card-header">
                    <h3 class="loc-card-title"><?= e($loc['name']) ?></h3>
                    <div class="loc-card-badges">
                      <?php if ($loc['isMainCenter']): ?>
                        <span class="loc-badge loc-badge--hq">Main Center</span>
                      <?php endif; ?>
                      <?php if ($loc['isPOBox']): ?>
                        <span class="loc-badge loc-badge--neutral">P.O. Box / Club Contact</span>
                      <?php endif; ?>
                      <?php if (!$loc['geocoded']): ?>
                        <span class="loc-badge loc-badge--subtle">Directory Listing</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <p class="loc-card-addr">
                    <?= erc_icon('map-pin', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
                    <span><?= e($loc['address']) ?></span>
                  </p>

                  <p class="loc-card-phone">
                    <?= erc_icon('phone', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
                    <?php if ($loc['phone'] !== '' && $loc['tel'] !== ''): ?>
                      <a href="tel:<?= e($loc['tel']) ?>"><?= e($loc['phone']) ?></a>
                    <?php else: ?>
                      <span class="loc-phone-missing">Phone not listed — call <?= e($globalPhone) ?> to verify</span>
                    <?php endif; ?>
                  </p>

                  <div class="loc-card-actions">
                    <?php if ($loc['geocoded']): ?>
                      <button type="button" class="loc-card-btn loc-card-btn--map" data-action="show-on-map" data-loc-id="<?= e((string) $loc['id']) ?>">
                        <?= erc_icon('map-pin', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
                        <span>Show on Map</span>
                      </button>
                    <?php endif; ?>
                    <a class="loc-card-btn loc-card-btn--dir" href="<?= e($loc['directions']) ?>" target="_blank" rel="noopener noreferrer">
                      <?= erc_icon('navigation', ['width' => 16, 'height' => 16, 'aria-hidden' => 'true']) ?>
                      <span>Directions</span>
                      <span class="sr-only">to <?= e($loc['name']) ?> (opens in new tab)</span>
                    </a>
                  </div>
                </article>
              </li>
            <?php endforeach; ?>
          </ul>

          <div id="loc-empty" class="loc-empty" hidden>
            <?= erc_icon('search', ['width' => 28, 'height' => 28, 'aria-hidden' => 'true']) ?>
            <h3>No matching drop-off bins</h3>
            <p>Try a broader keyword, a nearby Texas city, or clear your current filters to view all <?= e((string) $totalLocations) ?> locations.</p>
            <button type="button" id="loc-empty-reset" class="loc-btn loc-btn--primary">Show All <?= e((string) $totalLocations) ?> Locations</button>
          </div>
        </div>

        <aside class="loc-map-panel" id="loc-map-panel" aria-label="Interactive DFW drop-off map">
          <div class="loc-map-frame">
            <div
              id="locations-map"
              class="loc-map-canvas"
              role="region"
              aria-label="Interactive map of Lions District 2-E2 eyeglass drop-off bins"
              tabindex="0"
            ></div>

            <div id="loc-map-fallback" class="loc-map-fallback" role="status" hidden>
              <?= erc_icon('circle-alert', ['width' => 26, 'height' => 26, 'aria-hidden' => 'true']) ?>
              <h3>Interactive Map Temporarily Unavailable</h3>
              <p id="loc-map-fallback-msg">
                The map display could not load in this browser context, but every drop-off bin and Google Maps Directions link remains available in the directory.
              </p>
              <button type="button" id="loc-map-retry" class="loc-btn loc-btn--primary">
                <?= erc_icon('refresh-cw', ['width' => 18, 'height' => 18, 'aria-hidden' => 'true']) ?>
                <span>Retry Map</span>
              </button>
            </div>
          </div>

          <ul class="loc-legend" aria-label="Map marker key">
            <li class="loc-legend-item">
              <span class="loc-legend-swatch loc-legend-swatch--hq" aria-hidden="true"></span>
              <span>Watauga Main Recycling Center</span>
            </li>
            <li class="loc-legend-item">
              <span class="loc-legend-swatch loc-legend-swatch--cluster" aria-hidden="true"></span>
              <span>Clustered Partner Bins (zoom or click)</span>
            </li>
            <li class="loc-legend-item">
              <span class="loc-legend-swatch loc-legend-swatch--pin" aria-hidden="true"></span>
              <span>Partner Drop-Off Pin</span>
            </li>
          </ul>
        </aside>
      </div>
    </div>
  </section>

  <script type="application/json" id="locations-ssr-seed"><?= $ssrSeedJson ?></script>
</main>
<?php
require __DIR__ . '/includes/footer.php';
