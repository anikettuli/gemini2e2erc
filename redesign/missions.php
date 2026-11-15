<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'missions',
    'title'       => 'Passport & Humanitarian Missions | Lions District 2-E2 ERC',
    'description' => 'Interactive 3D dispatch globe across 38 documented partner countries (40+ served), WHO global vision need, 2013-2025 Lion-Year shipping ledger, medical mission requests, and field clinic dispatches.',
    'canonical'   => '/missions',
    'preload_img' => '',
    'og_image'    => '/gallery/peru-bound.jpg',
    'css'         => ['/css/pages/missions-about.css'],
    'js_modules'  => ['/js/pages/missions-about.js'],
    'json_ld'     => [],
    'noindex'     => false,
];

$shippingHistory = [
    ['year' => '2013-2014', 'start' => 2013, 'foreign' => 5650,  'local' => 70,   'total' => 5720],
    ['year' => '2014-2015', 'start' => 2014, 'foreign' => 13850, 'local' => 0,    'total' => 13850],
    ['year' => '2015-2016', 'start' => 2015, 'foreign' => 3125,  'local' => 0,    'total' => 3125],
    ['year' => '2016-2017', 'start' => 2016, 'foreign' => 7000,  'local' => 200,  'total' => 7200],
    ['year' => '2017-2018', 'start' => 2017, 'foreign' => 12450, 'local' => 0,    'total' => 12450],
    ['year' => '2018-2019', 'start' => 2018, 'foreign' => 14012, 'local' => 1750, 'total' => 15762],
    ['year' => '2019-2020', 'start' => 2019, 'foreign' => 10118, 'local' => 1850, 'total' => 11968],
    ['year' => '2020-2021', 'start' => 2020, 'foreign' => 4220,  'local' => 1900, 'total' => 6120],
    ['year' => '2021-2022', 'start' => 2021, 'foreign' => 8050,  'local' => 8000, 'total' => 16050],
    ['year' => '2022-2023', 'start' => 2022, 'foreign' => 15362, 'local' => 1875, 'total' => 17237],
    ['year' => '2023-2024', 'start' => 2023, 'foreign' => 17211, 'local' => 1850, 'total' => 19061],
    ['year' => '2024-2025', 'start' => 2024, 'foreign' => 17692, 'local' => 4004, 'total' => 21696],
];

$totalForeign = 0;
$totalLocal = 0;
$totalCombined = 0;
$maxCombined = 1;
foreach ($shippingHistory as $row) {
    $totalForeign += $row['foreign'];
    $totalLocal += $row['local'];
    $totalCombined += $row['total'];
    if ($row['total'] > $maxCombined) {
        $maxCombined = $row['total'];
    }
}

$globalRegions = [
    [
        'key'       => 'americas',
        'label'     => 'The Americas',
        'short'     => 'AMERICAS',
        'countries' => [
            'Belize', 'Bolivia', 'Brazil', 'Colombia', 'Costa Rica', 'Cuba',
            'Dominican Republic', 'Ecuador', 'El Salvador', 'Guatemala',
            'Haiti', 'Honduras', 'Mexico', 'Nicaragua', 'Panama',
            'Paraguay', 'Peru', 'Venezuela',
        ],
    ],
    [
        'key'       => 'africa',
        'label'     => 'Africa',
        'short'     => 'AFRICA',
        'countries' => [
            'Cameroon', 'Ethiopia', 'Ghana', 'Kenya', 'Malawi',
            'Nigeria', 'South Africa', 'Tanzania', 'Uganda', 'Zambia',
        ],
    ],
    [
        'key'       => 'asia',
        'label'     => 'Asia',
        'short'     => 'ASIA',
        'countries' => [
            'Bangladesh', 'Cambodia', 'Nepal', 'Philippines', 'Vietnam',
        ],
    ],
    [
        'key'       => 'europe',
        'label'     => 'Europe',
        'short'     => 'EUROPE',
        'countries' => [
            'Moldova', 'Poland', 'Romania', 'Ukraine',
        ],
    ],
    [
        'key'       => 'oceania',
        'label'     => 'Oceania (Fiji)',
        'short'     => 'FIJI',
        'countries' => [
            'Fiji',
        ],
    ],
];

$testimonialsRaw = erc_load_json('data/testimonials.json', []);
$testimonials = [];
if (is_array($testimonialsRaw)) {
    foreach ($testimonialsRaw as $entry) {
        if (is_array($entry) && trim((string)($entry['text'] ?? '')) !== '') {
            $testimonials[] = $entry;
        }
    }
}

$orgEmail = (string)($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');
$orgPhone = (string)($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main ma-page" tabindex="-1">
  <!-- 1. Signature Interactive HTML5 Canvas 3D Dot-Matrix Globe Stage -->
  <section class="ma-globe-stage" id="passport-globe" aria-labelledby="missions-hero-title">
    <div class="container ma-globe-stage__inner">
      <div class="ma-globe-stage__grid">
        <div class="ma-globe-stage__copy">
          <div class="v2-eyebrow">
            <span>PASSPORT &middot; HUMANITARIAN DISPATCH</span>
            <span class="v2-eyebrow__rule" aria-hidden="true"></span>
            <span>WATAUGA HQ &middot; 32.87&deg; N, 97.26&deg; W</span>
          </div>

          <h1 id="missions-hero-title" class="v2-display v2-display--hero ma-globe-title">
            <span class="v2-knockout">38</span> COUNTRIES.<br>5 CONTINENTS<span class="v2-dot" aria-hidden="true"></span>
          </h1>

          <p class="v2-note ma-globe-note">
            <span>packed in 50-pair boxes in Watauga</span>
            <?= erc_curved_arrow('up-right') ?>
          </p>

          <p class="lead ma-globe-lead">
            <strong>37 regionally listed nations</strong> across The Americas, Africa, Asia, and Europe plus <strong>Fiji</strong>&mdash;supporting <strong>40+ countries</strong> served at less than <span class="mono">&dollar;0.10 US</span> per refurbished pair and zero eyewear cost to nonprofit clinics.
          </p>

          <div class="ma-globe-instructions">
            <p class="ma-mono-meta">HOVER A PIN OR AN ARC. DRAG TO SPIN.</p>
            <div class="ma-globe-actions">
              <button
                type="button"
                class="ma-globe-toggle"
                id="globeToggleBtn"
                aria-pressed="false"
              >
                <span class="ma-globe-toggle__dot" aria-hidden="true"></span>
                <span data-globe-toggle-label>PAUSE GLOBE</span>
              </button>
              <a class="btn btn--primary" href="#mission-requests">
                <span>REQUEST EYEWEAR</span>
                <?= erc_icon('arrow-up-right', ['width' => '16', 'height' => '16']) ?>
              </a>
            </div>
          </div>

          <div class="ma-region-pills" role="group" aria-label="Filter globe and destinations by region">
            <button type="button" class="ma-region-pill is-active" data-globe-region="all" aria-pressed="true">
              ALL (38)
            </button>
            <?php foreach ($globalRegions as $region): ?>
              <button
                type="button"
                class="ma-region-pill"
                data-globe-region="<?= e($region['key']) ?>"
                aria-pressed="false"
              >
                <?= e($region['short']) ?> (<?= e((string)count($region['countries'])) ?>)
              </button>
            <?php endforeach; ?>
          </div>

          <!-- Fixed-Height Telemetry Readout Slot (Prevents Layout Shift) -->
          <div class="ma-globe-readout" id="globeReadout" aria-live="polite">
            <div class="ma-globe-readout__head">
              <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
              <span class="mono" data-readout-route>WATAUGA HQ &rarr; GLOBAL NETWORK</span>
            </div>
            <p class="ma-globe-readout__detail" data-readout-detail>
              Hover or tap any destination pin or geodesic arc to inspect route distance and dispatch format.
            </p>
          </div>
        </div>

        <div class="ma-globe-stage__visual">
          <div class="ma-globe-canvas-shell" data-globe-shell>
            <canvas
              id="missionGlobeCanvas"
              class="ma-globe-canvas"
              role="img"
              aria-label="Interactive 3D dot-matrix globe showing geodesic eyeglass dispatch arcs from Watauga, Texas to 38 documented partner countries across the Americas, Africa, Asia, Europe, and Fiji."
            ></canvas>
            <div class="ma-globe-tooltip" id="globeHoverTooltip" hidden aria-hidden="true">
              <span class="ma-globe-tooltip__country" data-tooltip-country></span>
              <span class="ma-globe-tooltip__meta" data-tooltip-meta></span>
            </div>
          </div>
          <div class="ma-globe-caption">
            <span class="mono">ORIGIN: 5621 BUNKER BLVD, WATAUGA TX</span>
            <span class="mono">ARC PAYLOAD: 50-PAIR DIOPTER BOXES</span>
          </div>
        </div>
      </div>

      <!-- Keyboard & Filterable Destination Atlas -->
      <div class="ma-atlas" id="global-reach" data-country-directory>
        <div class="ma-atlas__bar">
          <div class="ma-atlas__title-wrap">
            <span class="mono ma-atlas__kicker">DOCUMENTED DESTINATION MANIFEST</span>
            <p class="ma-atlas__status" aria-live="polite" data-country-status>
              Showing all 38 documented destinations (37 regional + Fiji) across 40+ countries served.
            </p>
          </div>
          <div class="ma-atlas__search">
            <label class="visually-hidden" for="countrySearchInput">Filter documented countries</label>
            <div class="ma-search-box">
              <?= erc_icon('search', ['width' => '16', 'height' => '16']) ?>
              <input
                id="countrySearchInput"
                type="search"
                class="ma-search-box__input"
                placeholder="Search country (e.g. Peru, Uganda, Fiji)..."
                data-country-search
                autocomplete="off"
              >
            </div>
          </div>
        </div>

        <div class="ma-atlas__regions">
          <?php foreach ($globalRegions as $region): ?>
            <section
              class="ma-region-panel"
              data-region-card="<?= e($region['key']) ?>"
              aria-labelledby="atlas-region-<?= e($region['key']) ?>"
            >
              <header class="ma-region-panel__head">
                <h2 id="atlas-region-<?= e($region['key']) ?>" class="ma-region-panel__title">
                  <?= e($region['label']) ?>
                </h2>
                <span class="badge" data-region-count><?= e((string)count($region['countries'])) ?></span>
              </header>
              <ul class="ma-country-chips">
                <?php foreach ($region['countries'] as $country): ?>
                  <li class="ma-country-chips__item" data-country-item data-country-name="<?= e($country) ?>" data-country-region="<?= e($region['key']) ?>">
                    <button
                      type="button"
                      class="ma-country-chip"
                      data-focus-country="<?= e($country) ?>"
                    >
                      <span><?= e($country) ?></span>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endforeach; ?>
        </div>

        <div class="ma-atlas__empty" data-country-empty hidden>
          <p>No documented destination matches that search filter.</p>
          <button type="button" class="btn btn--secondary" data-country-reset>
            RESET FILTER
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. Global Vision Need & $0.10 Unit Economics -->
  <section class="section section--subtle" id="global-need" aria-labelledby="need-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>WHO GLOBAL NEED &amp; UNIT ECONOMICS</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>LESS THAN &dollar;0.10 US PER PAIR</span>
      </div>

      <div class="ma-section-intro">
        <h2 id="need-title" class="v2-display v2-display--lg">
          THE VISION GAP<span class="v2-dot" aria-hidden="true"></span>
        </h2>
        <p class="lead">
          Refractive error keeps children out of classrooms and adults out of livelihoods. Every pair processed in Watauga reaches partner clinics for less than ten cents US.
        </p>
      </div>

      <ul class="ma-need-grid">
        <li class="ma-stat-card">
          <span class="ma-stat-card__index">METRIC &middot; 01</span>
          <span class="ma-stat-card__value">2.2B</span>
          <h3 class="ma-stat-card__label">Global Vision Impairment</h3>
          <p class="ma-stat-card__note">World Health Organization: at least 2.2 billion people globally have a near or distance vision impairment.</p>
        </li>
        <li class="ma-stat-card">
          <span class="ma-stat-card__index">METRIC &middot; 02</span>
          <span class="ma-stat-card__value">1.0B+</span>
          <h3 class="ma-stat-card__label">Unaddressed Cases</h3>
          <p class="ma-stat-card__note">WHO reports at least 1 billion vision impairment cases remain unaddressed or could have been prevented.</p>
        </li>
        <li class="ma-stat-card">
          <span class="ma-stat-card__index">METRIC &middot; 03</span>
          <span class="ma-stat-card__value">157M+</span>
          <h3 class="ma-stat-card__label">Simply Lack Eyeglasses</h3>
          <p class="ma-stat-card__note">Per <span class="mono">ERC-Brochure.pdf</span>, more than 157 million people suffer poor eyesight simply due to lack of glasses.</p>
        </li>
        <li class="ma-stat-card ma-stat-card--accent">
          <span class="ma-stat-card__index">UNIT COST &middot; 04</span>
          <span class="ma-stat-card__value">&lt; &dollar;0.10</span>
          <h3 class="ma-stat-card__label">Processing Cost / Pair</h3>
          <p class="ma-stat-card__note">Less than 10&cent; US per pair to clean, measure, sort, pack, and supply to nonprofit clinics.</p>
        </li>
      </ul>

      <article class="v2-schematic-card v2-schematic-card--split ma-record-card">
        <div class="ma-record-card__copy">
          <div class="v2-schematic-card__head">
            <span class="v2-schematic-card__num">RECORD EXPANSION</span>
            <span>LION YEAR 2023&ndash;2025</span>
          </div>
          <h3 class="v2-schematic-card__title">21,696 PAIRS IN 2024&ndash;2025</h3>
          <p class="lead">
            In <span class="mono">2023&ndash;2024</span>, volunteers supplied <span class="mono">17,211</span> foreign + <span class="mono">1,850</span> local pairs (<span class="mono">19,061</span> total). In <span class="mono">2024&ndash;2025</span>, output climbed to <strong>17,692 foreign</strong> and <strong>4,004 local</strong> pairs.
          </p>
          <div class="cluster">
            <a class="btn btn--secondary" href="<?= e(erc_media_url('files/ERC-Brochure.pdf')) ?>">
              <?= erc_icon('file-text', ['width' => '16', 'height' => '16']) ?>
              <span>ERC BROCHURE (PDF &middot; 2 PAGES)</span>
            </a>
          </div>
        </div>
        <figure class="v2-schematic-box ma-photo-box">
          <span class="v2-schematic-box__caption">FIG. 01 &middot; PERU-BOUND 50-PAIR DIOPTER BOXES</span>
          <img
            class="ma-photo-box__img"
            src="<?= e(erc_media_url('gallery/peru-bound.jpg')) ?>"
            alt="Labeled 50-pair boxes of sorted prescription eyeglasses packed at Watauga for partner eye clinics in Peru"
            loading="lazy"
            decoding="async"
          >
        </figure>
      </article>
    </div>
  </section>

  <!-- 3. Lion-Year Telemetry (2013–2025) -->
  <section class="section" id="shipping-history" data-shipping-section aria-labelledby="shipping-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>LION-YEAR TELEMETRY &middot; 2013&ndash;2025</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>FISCAL YEAR JUL 1 &ndash; JUN 30</span>
      </div>

      <div class="ma-section-intro">
        <h2 id="shipping-title" class="v2-display v2-display--lg">
          12-YEAR DISPATCH LEDGER<span class="v2-dot" aria-hidden="true"></span>
        </h2>
        <p class="lead">
          Every usable pair recorded in <span class="mono">files/ERC-Brochure.pdf</span> across twelve consecutive Lion Years (<span class="mono"><?= e(number_format($totalCombined)) ?></span> documented table pairs; hundreds of thousands repurposed across all operations).
        </p>
      </div>

      <!-- Architectural Stacked Bar Visualization -->
      <div class="ma-telemetry-chart" aria-label="2013 to 2025 Lion-Year shipping volume comparison">
        <div class="ma-telemetry-chart__header">
          <div class="ma-legend" aria-label="Chart series legend">
            <span class="ma-legend__item">
              <span class="ma-legend__swatch ma-legend__swatch--foreign" aria-hidden="true"></span>
              <span>FOREIGN MISSIONS (<?= e(number_format($totalForeign)) ?>)</span>
            </span>
            <span class="ma-legend__item">
              <span class="ma-legend__swatch ma-legend__swatch--local" aria-hidden="true"></span>
              <span>LOCAL MISSIONS (<?= e(number_format($totalLocal)) ?>)</span>
            </span>
          </div>
          <span class="mono ma-telemetry-chart__peak" data-chart-readout>
            PEAK YEAR: 2024-2025 &middot; 21,696 PAIRS
          </span>
        </div>

        <div class="ma-bars" role="list">
          <?php foreach ($shippingHistory as $row): ?>
            <?php
            $foreignH = round(($row['foreign'] / $maxCombined) * 100, 2);
            $localH = round(($row['local'] / $maxCombined) * 100, 2);
            $isRecord = $row['year'] === '2024-2025';
            ?>
            <div class="ma-bar-col <?= $isRecord ? 'is-record' : '' ?>" role="listitem">
              <button
                type="button"
                class="ma-bar-col__btn"
                data-chart-year="<?= e($row['year']) ?>"
                data-chart-foreign="<?= e(number_format($row['foreign'])) ?>"
                data-chart-local="<?= e(number_format($row['local'])) ?>"
                data-chart-total="<?= e(number_format($row['total'])) ?>"
                aria-label="Lion Year <?= e($row['year']) ?>: <?= e(number_format($row['foreign'])) ?> foreign pairs, <?= e(number_format($row['local'])) ?> local pairs, <?= e(number_format($row['total'])) ?> combined total"
              >
                <span class="ma-bar-col__total mono"><?= e(number_format($row['total'])) ?></span>
                <span class="ma-bar-col__track" aria-hidden="true">
                  <span class="ma-bar-col__seg ma-bar-col__seg--local" style="height: <?= e((string)$localH) ?>%"></span>
                  <span class="ma-bar-col__seg ma-bar-col__seg--foreign" style="height: <?= e((string)$foreignH) ?>%"></span>
                </span>
                <span class="ma-bar-col__label mono"><?= e(substr($row['year'], 2, 2)) ?>&ndash;<?= e(substr($row['year'], 7, 2)) ?></span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Sort Controls & Tabular Ledger -->
      <div class="ma-table-toolbar">
        <p class="mono ma-table-toolbar__caption">TABULAR MISSION RECORD (JUL 1 &ndash; JUN 30)</p>
        <div class="ma-sort-group" data-shipping-controls hidden role="group" aria-label="Sort Lion-Year shipping table">
          <button type="button" class="ma-sort-btn is-active" data-sort-mode="year-asc" aria-pressed="true">
            2013&ndash;2025
          </button>
          <button type="button" class="ma-sort-btn" data-sort-mode="year-desc" aria-pressed="false">
            NEWEST FIRST
          </button>
          <button type="button" class="ma-sort-btn" data-sort-mode="total-desc" aria-pressed="false">
            HIGHEST TOTAL
          </button>
        </div>
      </div>

      <p class="visually-hidden" aria-live="polite" data-shipping-status></p>

      <div class="v2-table-wrap table-wrap" tabindex="0" role="region" aria-labelledby="shipped-table-caption">
        <table class="data-table ma-ledger-table" data-shipping-table>
          <caption id="shipped-table-caption">
            Usable Eyeglasses Shipped by Lion Year (July 1 &ndash; June 30) &mdash; Source: files/ERC-Brochure.pdf
          </caption>
          <thead>
            <tr>
              <th scope="col" data-col-year aria-sort="ascending">Lion Year (Jul 1 &ndash; Jun 30)</th>
              <th scope="col" class="ma-num">Foreign Missions</th>
              <th scope="col" class="ma-num">Local Missions</th>
              <th scope="col" class="ma-num" data-col-total>Combined Total</th>
              <th scope="col">Split Share</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($shippingHistory as $row): ?>
              <?php
              $foreignPct = round(($row['foreign'] / $maxCombined) * 100, 2);
              $localPct = round(($row['local'] / $maxCombined) * 100, 2);
              $isRecord = $row['year'] === '2024-2025';
              ?>
              <tr
                class="<?= $isRecord ? 'is-record-row' : '' ?>"
                data-year-start="<?= e((string)$row['start']) ?>"
                data-combined="<?= e((string)$row['total']) ?>"
              >
                <th scope="row">
                  <span class="mono"><?= e($row['year']) ?></span>
                  <?php if ($isRecord): ?>
                    <span class="badge badge--brand">RECORD</span>
                  <?php endif; ?>
                </th>
                <td class="ma-num mono"><?= e(number_format($row['foreign'])) ?></td>
                <td class="ma-num mono"><?= e(number_format($row['local'])) ?></td>
                <td class="ma-num mono"><strong><?= e(number_format($row['total'])) ?></strong></td>
                <td class="ma-split-cell">
                  <div class="ma-split-track" aria-hidden="true">
                    <span class="ma-split-track__foreign" style="width: <?= e((string)$foreignPct) ?>%"></span>
                    <span class="ma-split-track__local" style="width: <?= e((string)$localPct) ?>%"></span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th scope="row">12-Year Recorded Table Total (2013&ndash;2025)</th>
              <td class="ma-num mono"><?= e(number_format($totalForeign)) ?></td>
              <td class="ma-num mono"><?= e(number_format($totalLocal)) ?></td>
              <td class="ma-num mono"><strong><?= e(number_format($totalCombined)) ?></strong></td>
              <td class="mono ma-muted-cell">50-PAIR DIOPTER BOXES</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </section>

  <!-- 4. Contrast Stage Sheet: Mission Request Sequence (T-04..T-01) & Verbatim Field Dispatches -->
  <section class="v2-sheet ma-dispatch-sheet" id="mission-requests" aria-labelledby="request-guide-title">
    <div class="v2-sheet__inner">
      <div class="v2-eyebrow">
        <span>MISSION REQUEST SEQUENCE</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>30+ DAYS ADVANCE LEAD TIME</span>
      </div>

      <div class="ma-sheet-head">
        <div>
          <h2 id="request-guide-title" class="v2-display v2-display--lg">
            REQUESTING <span class="v2-accent-text">MISSION</span> EYEWEAR<span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="lead">
            Refurbished eyeglasses are provided <strong>when available and without cost</strong> to qualifying nonprofit humanitarian and medical missions with a method to determine patient refractive error.
          </p>
        </div>
        <p class="v2-note">
          <span>zero hardware cost</span>
          <?= erc_curved_arrow('down-left') ?>
        </p>
      </div>

      <div class="callout callout--warn ma-mandatory-terms">
        <?= erc_icon('triangle-alert', ['class' => 'callout__icon', 'width' => '20', 'height' => '20']) ?>
        <div class="callout__body">
          <p class="callout__title">Mandatory Terms (Mission-Request-Form.pdf)</p>
          <p>
            Submit requests <strong>at least 30 days in advance</strong> of departure. <strong>GLASSES ARE NOT FOR RESALE.</strong> <strong>Missions are responsible for all shipping expenses.</strong>
          </p>
        </div>
      </div>

      <ol class="ma-sequence-grid">
        <li class="v2-schematic-card ma-seq-card">
          <div class="v2-schematic-card__head">
            <span class="v2-schematic-card__num">T-04</span>
            <span>NONPROFIT ELIGIBILITY</span>
          </div>
          <h3 class="ma-seq-card__title">Tax-Exempt Status &amp; Contact</h3>
          <p>
            Provide your requesting organization&rsquo;s name, address, primary contact, email, phone, and proof/attestation of non-profit / tax-exempt status (<strong>Tax ID number</strong> required).
          </p>
        </li>

        <li class="v2-schematic-card ma-seq-card">
          <div class="v2-schematic-card__head">
            <span class="v2-schematic-card__num">T-03</span>
            <span>REFRACTIVE METHOD</span>
          </div>
          <h3 class="ma-seq-card__title">Verify Eye Testing Method</h3>
          <p>
            Specify how patient refractive error will be determined (e.g., refractor set, autorefractor, or optometrist/ophthalmologist team). Single-vision prescription glasses &amp; sunglasses are supplied; reading glasses when eye-care professionals are unavailable.
          </p>
        </li>

        <li class="v2-schematic-card ma-seq-card">
          <div class="v2-schematic-card__head">
            <span class="v2-schematic-card__num">T-02</span>
            <span>DIOPTER BOX GRID</span>
          </div>
          <h3 class="ma-seq-card__title">Order in 50-Pair Multiples</h3>
          <p>
            List destination, trip dates, and quantities in multiples of <strong>50 pairs</strong> across Single Vision Plus (<span class="mono">+0.25</span> to <span class="mono">+5.00+</span>), Single Vision Minus (<span class="mono">&minus;0.25</span> to <span class="mono">&minus;5.00+</span>), Reading Glasses (<span class="mono">+0.25</span> to <span class="mono">+5.00+</span>), and Sunglasses.
          </p>
        </li>

        <li class="v2-schematic-card ma-seq-card">
          <div class="v2-schematic-card__head">
            <span class="v2-schematic-card__num">T-01</span>
            <span>DISPATCH &amp; FREIGHT</span>
          </div>
          <h3 class="ma-seq-card__title">Email Form 30+ Days Out</h3>
          <p>
            Send completed <span class="mono">Mission-Request-Form.pdf</span> to <span class="mono"><?= e($orgEmail) ?></span> at least <strong>30 days before</strong> your trip and arrange mission-funded shipping freight.
          </p>
        </li>
      </ol>

      <div class="ma-sheet-actions">
        <a class="btn btn--primary btn--lg" href="<?= e(erc_media_url('files/Mission-Request-Form.pdf')) ?>">
          <?= erc_icon('download', ['width' => '18', 'height' => '18']) ?>
          <span>MISSION REQUEST FORM (PDF)</span>
        </a>
        <a class="btn btn--secondary btn--lg" href="/contact?subject=partnership">
          <?= erc_icon('send', ['width' => '18', 'height' => '18']) ?>
          <span>COORDINATE MISSION ON /CONTACT</span>
        </a>
        <a class="btn btn--secondary btn--lg" href="mailto:<?= e($orgEmail) ?>">
          <?= erc_icon('mail', ['width' => '18', 'height' => '18']) ?>
          <span>EMAIL <?= e($orgEmail) ?></span>
        </a>
        <a class="btn btn--secondary btn--lg" href="tel:<?= e((string)($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403')) ?>">
          <?= erc_icon('phone', ['width' => '18', 'height' => '18']) ?>
          <span>CALL <?= e($orgPhone) ?></span>
        </a>
      </div>

      <hr class="ma-sheet-divider">

      <!-- Verbatim Field Dispatches (data/testimonials.json) -->
      <div id="clinic-stories" class="ma-dispatches">
        <div class="v2-eyebrow">
          <span>FIELD DISPATCHES</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
          <span>VERBATIM PARTNER CLINIC REPORTS</span>
        </div>

        <div class="ma-sheet-head ma-sheet-head--compact">
          <h2 class="v2-display v2-display--md">
            VOICES FROM <span class="v2-accent-text">THE FIELD</span><span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="lead">
            Unedited accounts from humanitarian eye clinic organizers serving rural Zambia, Chincha (Peru), and Pallisa District (Eastern Uganda).
          </p>
        </div>

        <?php if (count($testimonials) > 0): ?>
          <div class="ma-dispatch-grid">
            <?php foreach ($testimonials as $idx => $item): ?>
              <?php $serial = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT); ?>
              <article class="v2-ticket ma-dispatch-ticket">
                <div class="v2-ticket__main">
                  <div class="v2-ticket__meta">
                    <span>FIELD REPORT</span>
                    <span>VERBATIM ARCHIVE</span>
                  </div>
                  <blockquote class="ma-dispatch-quote">
                    &ldquo;<?= e((string)($item['text'] ?? '')) ?>&rdquo;
                  </blockquote>
                  <div class="ma-dispatch-author">
                    <strong><?= e((string)($item['author'] ?? 'Mission Partner')) ?></strong>
                    <?php if (trim((string)($item['organization'] ?? '')) !== ''): ?>
                      <span class="mono ma-dispatch-org"><?= e((string)$item['organization']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="v2-ticket__stub">
                  <span class="v2-ticket__serial">DISPATCH &middot; <?= e($serial) ?></span>
                  <?= erc_icon('glasses', ['width' => '20', 'height' => '20']) ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="callout callout--info">
            <?= erc_icon('info', ['class' => 'callout__icon', 'width' => '20', 'height' => '20']) ?>
            <div class="callout__body">
              <p>Partner clinic dispatches are temporarily unavailable. Contact <a href="mailto:<?= e($orgEmail) ?>"><?= e($orgEmail) ?></a> for references.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
