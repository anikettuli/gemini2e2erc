<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'missions',
    'title'       => 'Missions, Global Impact & Eyeglass Requests | Lions District 2-E2 ERC',
    'description' => 'WHO global vision facts, 2013-2025 Lion-Year shipping table, all documented partner countries, medical mission eyeglass request guide, and field clinic testimonials.',
    'canonical'   => '/missions',
    'preload_img' => '',
    'og_image'    => '/gallery/peru-bound.jpg',
    'css'         => ['/css/pages/editorial.css'],
    'js_modules'  => ['/js/pages/editorial.js'],
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
        'countries' => [
            'Cameroon', 'Ethiopia', 'Ghana', 'Kenya', 'Malawi',
            'Nigeria', 'South Africa', 'Tanzania', 'Uganda', 'Zambia',
        ],
    ],
    [
        'key'       => 'asia',
        'label'     => 'Asia',
        'countries' => [
            'Bangladesh', 'Cambodia', 'Nepal', 'Philippines', 'Vietnam',
        ],
    ],
    [
        'key'       => 'europe',
        'label'     => 'Europe',
        'countries' => [
            'Moldova', 'Poland', 'Romania', 'Ukraine',
        ],
    ],
    [
        'key'       => 'oceania',
        'label'     => 'Additional Documented Destination',
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

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main ed-page" tabindex="-1">
  <!-- Editorial Masthead -->
  <section class="ed-masthead" aria-labelledby="missions-title">
    <div class="ed-wrap">
      <div class="ed-masthead__grid ed-masthead__grid--split">
        <div>
          <span class="ed-kicker"><?= erc_icon('globe') ?> Humanitarian Dispatch &amp; Impact</span>
          <h1 id="missions-title" class="ed-display">
            Global Vision Need, 12-Year Dispatch Ledger &amp; Mission Requests
          </h1>
          <p class="ed-lead">
            For less than <span class="ed-mono">$0.10 US</span> per pair, donated eyeglasses sorted in Watauga equip nonprofit optometry teams and clinics across 40+ countries—at zero cost for the eyewear itself.
          </p>
        </div>

        <aside class="ed-masthead__aside" aria-label="Mission Request Quick Download">
          <h2 class="ed-h4">Planning a Vision Mission?</h2>
          <p class="ed-prose">
            Submit requests at least <strong>30 days in advance</strong> with nonprofit Tax ID and refractive testing method.
          </p>
          <div class="ed-actions ed-mt-sm">
            <a class="ed-btn ed-btn--primary" href="/files/Mission-Request-Form.pdf">
              <?= erc_icon('download') ?>
              <span>Mission Request Form (PDF)</span>
            </a>
          </div>
        </aside>
      </div>

      <nav aria-label="On this page">
        <ul class="ed-jump-nav">
          <li><a href="#global-need">Global Need &amp; Unit Cost</a></li>
          <li><a href="#shipping-history">2013–2025 Shipping Table</a></li>
          <li><a href="#global-reach">Countries Served</a></li>
          <li><a href="#mission-requests">Request Mission Eyewear</a></li>
          <li><a href="#clinic-stories">Clinic Testimonials</a></li>
        </ul>
      </nav>
    </div>
  </section>

  <!-- Global Vision Need & Unit Economics -->
  <section id="global-need" class="ed-band ed-band--subtle" aria-labelledby="need-title">
    <div class="ed-wrap ed-metrics-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('eye') ?> Why Recycled Eyewear Matters</span>
          <h2 id="need-title" class="ed-h2">The Global Vision Gap Meets $0.10 Unit Economics</h2>
          <p class="ed-lead">
            Simple refractive error keeps millions of children out of classrooms and adults out of work. Verified secondhand glasses solve an immediate, tangible barrier.
          </p>
        </div>
      </div>

      <ul class="ed-metrics-grid">
        <li class="ed-metric-card">
          <span class="ed-metric-value">2.2 Billion</span>
          <span class="ed-metric-label">People with Vision Impairment</span>
          <p class="ed-metric-note">According to the World Health Organization (WHO), near or distance vision impairment affects at least 2.2 billion people globally.</p>
        </li>
        <li class="ed-metric-card ed-metric-card--gold">
          <span class="ed-metric-value">1 Billion+</span>
          <span class="ed-metric-label">Unaddressed Cases Worldwide</span>
          <p class="ed-metric-note">WHO reports at least 1 billion vision impairment cases could have been prevented or remain unaddressed.</p>
        </li>
        <li class="ed-metric-card">
          <span class="ed-metric-value">157 Million+</span>
          <span class="ed-metric-label">Simply Lack Eyeglasses</span>
          <p class="ed-metric-note">Per our ERC Brochure, over 157 million people suffer poor eyesight simply because they lack corrective glasses.</p>
        </li>
        <li class="ed-metric-card ed-metric-card--emerald">
          <span class="ed-metric-value">&lt; $0.10 US</span>
          <span class="ed-metric-label">Cost per Recycled Pair</span>
          <p class="ed-metric-note">Less than 10&cent; US per pair to clean, measure, sort, pack, and supply to nonprofit humanitarian clinics.</p>
        </li>
      </ul>

      <div class="ed-workshop-pair ed-align-center">
        <div class="ed-card">
          <h3 class="ed-h3">Record Expansion in 2023–2025</h3>
          <p class="ed-prose">
            In Lion Year <span class="ed-mono">2023–2024</span>, our center supplied <span class="ed-mono">17,000+</span> foreign mission pairs (<span class="ed-mono">17,211</span> foreign + <span class="ed-mono">1,850</span> local = <span class="ed-mono">19,061</span> total). In <span class="ed-mono">2024–2025</span>, volunteers topped that record with <strong>17,692 foreign</strong> and <strong>4,004 local</strong> pairs—<strong>21,696 usable eyeglasses</strong> in a single year.
          </p>
          <div class="ed-actions">
            <a class="ed-btn ed-btn--secondary" href="/files/ERC-Brochure.pdf">
              <?= erc_icon('file-text') ?>
              <span>Download ERC Brochure (PDF · 2 pages)</span>
            </a>
          </div>
        </div>

        <figure class="ed-media-frame">
          <img
            class="ed-media-frame__img"
            src="/gallery/peru-bound.jpg"
            alt="Labeled mission boxes of sorted prescription eyeglasses packed for clinics in Peru"
            loading="lazy"
            decoding="async"
          >
          <figcaption class="ed-media-frame__caption">
            Packed 50-pair diopter boxes prepared at our Watauga center for partner eye clinics bound for Peru.
          </figcaption>
        </figure>
      </div>
    </div>
  </section>

  <!-- 12-Row Lion-Year Usable Eyeglasses Shipped Table -->
  <section id="shipping-history" class="ed-band ed-band--surface" data-shipping-section aria-labelledby="shipping-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('package') ?> Verified Brochure Record</span>
          <h2 id="shipping-title" class="ed-h2">Usable Eyeglasses Shipped by Lion Year (2013–2025)</h2>
          <p class="ed-lead">
            Published in <span class="ed-mono">files/ERC-Brochure.pdf</span>. Each Lion Year runs <span class="ed-mono">July 1 – June 30</span>.
          </p>
        </div>
      </div>

      <div class="ed-table-controls">
        <div class="ed-table-legend" aria-label="Chart bar color legend">
          <span class="ed-legend-item">
            <span class="ed-swatch ed-swatch--foreign" aria-hidden="true"></span>
            <span>Foreign Missions</span>
          </span>
          <span class="ed-legend-item">
            <span class="ed-swatch ed-swatch--local" aria-hidden="true"></span>
            <span>Local Missions</span>
          </span>
        </div>

        <div class="ed-actions" data-shipping-controls hidden>
          <button type="button" class="ed-sort-btn" data-sort-mode="year-asc" aria-pressed="true">
            Chronological (2013–2025)
          </button>
          <button type="button" class="ed-sort-btn" data-sort-mode="year-desc" aria-pressed="false">
            Newest First
          </button>
          <button type="button" class="ed-sort-btn" data-sort-mode="total-desc" aria-pressed="false">
            Highest Total
          </button>
        </div>
      </div>

      <p class="visually-hidden" aria-live="polite" data-shipping-status></p>

      <div class="ed-table-wrap" tabindex="0" role="region" aria-labelledby="shipped-table-caption">
        <table class="ed-table" data-shipping-table>
          <caption id="shipped-table-caption">
            Usable Eyeglasses Shipped by Lion Year (July 1 – June 30), Foreign vs. Local Missions
          </caption>
          <thead>
            <tr>
              <th scope="col" data-col-year aria-sort="ascending">Lion Year (Jul 1 – Jun 30)</th>
              <th scope="col" class="num">Foreign Missions (Pairs)</th>
              <th scope="col" class="num">Local Missions (Pairs)</th>
              <th scope="col" class="num" data-col-total>Combined Total</th>
              <th scope="col">Relative Volume &amp; Split</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($shippingHistory as $row): ?>
              <?php
              $foreignPct = round(($row['foreign'] / $maxCombined) * 100, 2);
              $localPct = round(($row['local'] / $maxCombined) * 100, 2);
              $isPeak = $row['total'] >= 19000;
              ?>
              <tr
                class="<?= $isPeak ? 'is-peak' : '' ?>"
                data-year-start="<?= e((string)$row['start']) ?>"
                data-combined="<?= e((string)$row['total']) ?>"
              >
                <th scope="row">
                  <span class="ed-mono"><?= e($row['year']) ?></span>
                  <?php if ($row['year'] === '2024-2025'): ?>
                    <span class="ed-pill ed-pill--gold ed-badge-gap">Record Year</span>
                  <?php endif; ?>
                </th>
                <td class="num" data-label="Foreign Missions (Pairs)"><?= e(number_format($row['foreign'])) ?></td>
                <td class="num" data-label="Local Missions (Pairs)"><?= e(number_format($row['local'])) ?></td>
                <td class="num" data-label="Combined Total"><strong><?= e(number_format($row['total'])) ?></strong></td>
                <td class="ed-bar-cell" data-label="Foreign / Local Proportion">
                  <div class="ed-bar-track" aria-hidden="true">
                    <span class="ed-bar-foreign" style="width: <?= e((string)$foreignPct) ?>%"></span>
                    <span class="ed-bar-local" style="width: <?= e((string)$localPct) ?>%"></span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th scope="row">12-Year Recorded Table Total (2013–2025)</th>
              <td class="num" data-label="Foreign Total"><?= e(number_format($totalForeign)) ?></td>
              <td class="num" data-label="Local Total"><?= e(number_format($totalLocal)) ?></td>
              <td class="num" data-label="Combined 12-Year Total"><?= e(number_format($totalCombined)) ?></td>
              <td data-label="Cumulative Note">
                <span class="ed-muted">Hundreds of thousands repurposed across all center operations</span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </section>

  <!-- Complete Global Reach Directory (37 Regional Countries + Fiji = 40+ Countries) -->
  <section id="global-reach" class="ed-band" data-country-directory aria-labelledby="reach-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('globe') ?> 40+ Countries Served</span>
          <h2 id="reach-title" class="ed-h2">Complete Documented Global Reach Directory</h2>
          <p class="ed-lead">
            Verified destinations supplied through partner clinics and humanitarian missions—spanning 37 listed countries across four regions plus Fiji, contributing to more than 40 countries served worldwide.
          </p>
        </div>
      </div>

      <div class="ed-country-toolbar" data-country-controls hidden>
        <div class="ed-filter-row">
          <div class="ed-search-field">
            <label for="country-search">Search documented countries</label>
            <div class="ed-input-wrap">
              <?= erc_icon('search') ?>
              <input
                id="country-search"
                type="search"
                class="ed-search-input"
                placeholder="Type a country or region (e.g., Peru, Uganda, Fiji)"
                data-country-search
                autocomplete="off"
              >
            </div>
          </div>

          <div class="ed-chip-group" role="group" aria-label="Filter destinations by region">
            <button type="button" class="ed-filter-chip" data-region-filter="all" aria-pressed="true">
              All Regions (38)
            </button>
            <button type="button" class="ed-filter-chip" data-region-filter="americas" aria-pressed="false">
              The Americas (18)
            </button>
            <button type="button" class="ed-filter-chip" data-region-filter="africa" aria-pressed="false">
              Africa (10)
            </button>
            <button type="button" class="ed-filter-chip" data-region-filter="asia" aria-pressed="false">
              Asia (5)
            </button>
            <button type="button" class="ed-filter-chip" data-region-filter="europe" aria-pressed="false">
              Europe (4)
            </button>
            <button type="button" class="ed-filter-chip" data-region-filter="oceania" aria-pressed="false">
              Fiji (1)
            </button>
          </div>
        </div>

        <p class="ed-muted ed-m-0" aria-live="polite" data-country-status>
          Showing all 38 documented destinations across 40+ countries served.
        </p>
      </div>

      <div class="ed-regions-grid">
        <?php foreach ($globalRegions as $region): ?>
          <section class="ed-region-card" data-region-card="<?= e($region['key']) ?>" aria-labelledby="region-<?= e($region['key']) ?>">
            <div class="ed-region-head">
              <h3 id="region-<?= e($region['key']) ?>" class="ed-h3 ed-m-0">
                <?= e($region['label']) ?>
              </h3>
              <span class="ed-pill ed-pill--tint ed-mono" data-region-count>
                <?= e((string)count($region['countries'])) ?>
              </span>
            </div>

            <ul class="ed-country-list">
              <?php foreach ($region['countries'] as $country): ?>
                <li
                  data-country-item
                  data-country-name="<?= e($country) ?>"
                  data-country-region="<?= e($region['label']) ?>"
                >
                  <?= e($country) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endforeach; ?>
      </div>

      <div class="ed-empty-state" data-country-empty hidden>
        <p class="ed-prose ed-empty-copy">
          No listed country matches that search. Reset the filter to browse all 38 documented destinations.
        </p>
        <button type="button" class="ed-btn ed-btn--secondary" data-country-reset>
          Show All Countries
        </button>
      </div>
    </div>
  </section>

  <!-- Step-by-Step Medical & Humanitarian Mission Eyeglass Request Guide -->
  <section id="mission-requests" class="ed-band ed-band--subtle" aria-labelledby="request-guide-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('calendar-check') ?> Requesting Eyewear for Clinics</span>
          <h2 id="request-guide-title" class="ed-h2">How to Request Glasses for a Humanitarian or Medical Mission</h2>
          <p class="ed-lead">
            Refurbished eyeglasses are provided <strong>when available and without cost</strong> for the eyewear itself to qualifying non-profit missions capable of determining patient refractive error.
          </p>
        </div>
      </div>

      <div class="ed-callout ed-callout--warn ed-mb-lg">
        <?= erc_icon('triangle-alert') ?>
        <div>
          <strong>Mandatory Mission Terms (<span class="ed-mono">Mission-Request-Form.pdf</span>):</strong>
          Submit requests <strong>at least 30 days in advance</strong> of departure. <strong>GLASSES ARE NOT FOR RESALE.</strong> <strong>Missions are responsible for all shipping expenses.</strong>
        </div>
      </div>

      <ol class="ed-process-grid">
        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">01</span>
            <h3 class="ed-h4">Confirm Nonprofit Tax-Exempt Eligibility</h3>
          </div>
          <p class="ed-prose">
            Provide your requesting organization&rsquo;s name, address, primary contact, email, phone, and attestation of non-profit / tax-exempt status (<strong>Tax ID number</strong> required).
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">02</span>
            <h3 class="ed-h4">Verify Refractive Testing Method</h3>
          </div>
          <p class="ed-prose">
            Document the method used to measure patient refractive status (such as a refractor set, autorefractor, or optometrist/ophthalmologist team). Single-vision prescription glasses and sunglasses are supplied for medical missions; reading glasses can be supplied when eye-care professionals are unavailable.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">03</span>
            <h3 class="ed-h4">Specify Destination, Dates &amp; 50-Pair Boxes</h3>
          </div>
          <p class="ed-prose">
            List destination country/location, trip dates, and quantities in <strong>multiples of 50 pairs</strong> across Single Vision Plus (<span class="ed-mono">+0.25</span> to <span class="ed-mono">+5.00+</span>), Single Vision Minus (<span class="ed-mono">&minus;0.25</span> to <span class="ed-mono">&minus;5.00+</span>), Reading Glasses (<span class="ed-mono">+0.25</span> to <span class="ed-mono">+5.00+</span>), and Sunglasses.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">04</span>
            <h3 class="ed-h4">Email Form 30+ Days Before Departure</h3>
          </div>
          <p class="ed-prose">
            Download and complete <span class="ed-mono">Mission-Request-Form.pdf</span>, then email it to <a class="ed-inline-link" href="mailto:<?= e($GLOBAL_EMAIL) ?>"><?= e($GLOBAL_EMAIL) ?></a> at least <strong>30 days prior</strong> to your trip, and arrange shipping freight coverage.
          </p>
        </li>
      </ol>

      <div class="ed-actions ed-mt-lg">
        <a class="ed-btn ed-btn--primary" href="/files/Mission-Request-Form.pdf">
          <?= erc_icon('download') ?>
          <span>Download Mission Request Form (PDF · 1 page)</span>
        </a>
        <a class="ed-btn ed-btn--secondary" href="/files/ERC-Brochure.pdf">
          <?= erc_icon('file-text') ?>
          <span>Download ERC Brochure (PDF · 2 pages)</span>
        </a>
        <a class="ed-btn ed-btn--ghost" href="mailto:<?= e($GLOBAL_EMAIL) ?>">
          <?= erc_icon('mail') ?>
          <span>Email <?= e($GLOBAL_EMAIL) ?></span>
        </a>
      </div>
    </div>
  </section>

  <!-- All 4 Verbatim Clinic Testimonials (data/testimonials.json) -->
  <section id="clinic-stories" class="ed-band ed-band--tint ed-band--last" aria-labelledby="testimonials-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('heart-handshake') ?> Partner Clinic Voices</span>
          <h2 id="testimonials-title" class="ed-h2">Verbatim Reports from Field Vision Missions</h2>
          <p class="ed-lead">
            Unedited accounts from clinic organizers and humanitarian partners serving Zambia, Peru, and Uganda with District 2-E2 eyewear.
          </p>
        </div>
      </div>

      <?php if (count($testimonials) > 0): ?>
        <div class="ed-grid ed-grid--2">
          <?php foreach ($testimonials as $item): ?>
            <figure class="ed-quote-card">
              <blockquote>
                &ldquo;<?= e((string)($item['text'] ?? '')) ?>&rdquo;
              </blockquote>
              <figcaption>
                <span class="ed-quote-author"><?= e((string)($item['author'] ?? 'Mission Partner')) ?></span>
                <?php if (trim((string)($item['organization'] ?? '')) !== ''): ?>
                  <span class="ed-quote-org"><?= e((string)$item['organization']) ?></span>
                <?php endif; ?>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="ed-callout ed-callout--tint">
          <?= erc_icon('info') ?>
          <p class="ed-prose">Partner testimonials are currently unavailable. Contact <a class="ed-inline-link" href="mailto:<?= e($GLOBAL_EMAIL) ?>"><?= e($GLOBAL_EMAIL) ?></a> for mission references.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
