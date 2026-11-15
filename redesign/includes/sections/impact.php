<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/icons.php';

$impEsc = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$impMedia = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$testimonialsRaw = function_exists('erc_load_json')
    ? erc_load_json(ERC_DATA_DIR . '/testimonials.json', [])
    : [];
$testimonials = is_array($testimonialsRaw) ? array_values($testimonialsRaw) : [];

$missionRegions = [
    [
        'key'       => 'americas',
        'label'     => 'Americas',
        'countries' => [
            'Belize', 'Bolivia', 'Brazil', 'Colombia', 'Costa Rica', 'Cuba',
            'Dominican Republic', 'Ecuador', 'El Salvador', 'Guatemala', 'Haiti',
            'Honduras', 'Mexico', 'Nicaragua', 'Panama', 'Paraguay', 'Peru', 'Venezuela',
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
        'key'       => 'fiji',
        'label'     => 'Fiji',
        'countries' => [
            'Fiji',
        ],
    ],
];

$totalCountries = 0;
foreach ($missionRegions as $region) {
    $totalCountries += count($region['countries']);
}

$shippingLedger = [
    ['year' => '2013-2014', 'foreign' => 5650,  'local' => 70,   'total' => 5720],
    ['year' => '2014-2015', 'foreign' => 13850, 'local' => 0,    'total' => 13850],
    ['year' => '2015-2016', 'foreign' => 3125,  'local' => 0,    'total' => 3125],
    ['year' => '2016-2017', 'foreign' => 7000,  'local' => 200,  'total' => 7200],
    ['year' => '2017-2018', 'foreign' => 12450, 'local' => 0,    'total' => 12450],
    ['year' => '2018-2019', 'foreign' => 14012, 'local' => 1750, 'total' => 15762],
    ['year' => '2019-2020', 'foreign' => 10118, 'local' => 1850, 'total' => 11968],
    ['year' => '2020-2021', 'foreign' => 4220,  'local' => 1900, 'total' => 6120],
    ['year' => '2021-2022', 'foreign' => 8050,  'local' => 8000, 'total' => 16050],
    ['year' => '2022-2023', 'foreign' => 15362, 'local' => 1875, 'total' => 17237],
    ['year' => '2023-2024', 'foreign' => 17211, 'local' => 1850, 'total' => 19061],
    ['year' => '2024-2025', 'foreign' => 17692, 'local' => 4004, 'total' => 21696],
];

$ledgerMaxTotal = 21696;
$ledgerSumForeign = 0;
$ledgerSumLocal = 0;
$ledgerSumTotal = 0;
foreach ($shippingLedger as $row) {
    $ledgerSumForeign += $row['foreign'];
    $ledgerSumLocal += $row['local'];
    $ledgerSumTotal += $row['total'];
}
?>
<section id="impact" class="section section--proof" aria-labelledby="impact-title" tabindex="-1">
  <div class="container">
    <div class="section-head">
      <p class="kicker">03 &middot; Global Impact &amp; Missions</p>
      <h2 id="impact-title" class="h2">Eyewear dispatched to partner clinics worldwide.</h2>
      <p class="lead">
        Refurbished prescription eyewear ships from Watauga free of hardware cost to verified nonprofit clinics serving communities across 40+ nations.
      </p>
    </div>

    <dl class="stat-row impact-gap-row" aria-label="Global vision gap statistics">
      <div class="stat-item">
        <dt class="stat-label">People globally with vision impairment (WHO)</dt>
        <dd class="stat-value">2.2B</dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">Preventable or unaddressed vision cases</dt>
        <dd class="stat-value">1B+</dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">People lacking poor-eyesight correction without glasses</dt>
        <dd class="stat-value">157M+</dd>
      </div>
    </dl>

    <div class="impact-reach">
      <div class="card impact-globe-card">
        <div class="impact-globe-card__head">
          <div>
            <p class="kicker">Mission Arc Observatory</p>
            <p class="impact-globe-card__origin mono">Origin: Watauga, TX &middot; 32.87&deg;N, 97.26&deg;W</p>
          </div>
          <button
            type="button"
            class="btn btn--secondary btn--sm"
            id="impactGlobeToggle"
            aria-pressed="false"
          >
            Pause
          </button>
        </div>

        <div class="impact-globe-stage">
          <canvas
            id="impactGlobeCanvas"
            class="impact-globe__canvas"
            width="520"
            height="520"
            tabindex="0"
            role="img"
            aria-label="Interactive globe showing geodesic mission arcs from Watauga, Texas to 38 documented destination nations. Drag or use arrow keys to rotate."
          ></canvas>
        </div>

        <p class="impact-globe-card__foot">
          Drag or use arrow keys to rotate &middot; <span id="impactGlobeStatus" class="mono">38 destinations active</span>
        </p>
      </div>

      <div class="impact-regions">
        <div class="impact-regions__head">
          <h3 class="h3">Documented Destinations (<span class="mono"><?= $impEsc((string) $totalCountries) ?></span>)</h3>
          <p class="impact-regions__note">
            37 regionally documented nations plus Fiji across 40+ total countries served.
          </p>
        </div>

        <div class="pill-tabs" role="tablist" aria-label="Filter mission destinations by region">
          <button
            type="button"
            class="pill-tab is-active"
            role="tab"
            aria-selected="true"
            data-region-filter="all"
          >
            All <span class="mono"><?= $impEsc((string) $totalCountries) ?></span>
          </button>
<?php foreach ($missionRegions as $region): ?>
          <button
            type="button"
            class="pill-tab"
            role="tab"
            aria-selected="false"
            data-region-filter="<?= $impEsc($region['key']) ?>"
          >
            <?= $impEsc($region['label']) ?> <span class="mono"><?= $impEsc((string) count($region['countries'])) ?></span>
          </button>
<?php endforeach; ?>
        </div>

        <ul class="impact-country-list" id="impactCountryList" aria-live="polite">
<?php foreach ($missionRegions as $region): ?>
<?php   foreach ($region['countries'] as $country): ?>
          <li class="impact-country-item" data-region="<?= $impEsc($region['key']) ?>">
            <button
              type="button"
              class="impact-country-chip"
              data-country="<?= $impEsc($country) ?>"
              data-region="<?= $impEsc($region['key']) ?>"
            >
              <?= $impEsc($country) ?>
            </button>
          </li>
<?php   endforeach; ?>
<?php endforeach; ?>
        </ul>
      </div>
    </div>

    <div class="impact-testimonials-wrap">
      <div class="impact-subhead">
        <p class="kicker">Field Reports</p>
        <h3 class="h3">Voices from partner clinics in the field</h3>
      </div>

      <div class="impact-testimonials">
<?php foreach ($testimonials as $quote):
    $quoteText = trim((string) ($quote['text'] ?? ''));
    $quoteAuthor = trim((string) ($quote['author'] ?? ''));
    $quoteOrg = trim((string) ($quote['organization'] ?? ''));
    if ($quoteText === '') {
        continue;
    }
?>
        <figure class="card impact-quote-card">
          <blockquote class="impact-quote-card__text">
            <p>&ldquo;<?= $impEsc($quoteText) ?>&rdquo;</p>
          </blockquote>
          <figcaption class="impact-quote-card__meta">
            <span class="impact-quote-card__author"><?= $impEsc($quoteAuthor) ?></span>
<?php   if ($quoteOrg !== ''): ?>
            <span class="impact-quote-card__org"><?= $impEsc($quoteOrg) ?></span>
<?php   endif; ?>
          </figcaption>
        </figure>
<?php endforeach; ?>
      </div>
    </div>

    <div class="card impact-mission-bar">
      <div class="impact-mission-bar__copy">
        <p class="kicker">Request Mission Eyeglasses</p>
        <h3 class="h3">Supplying verified medical &amp; humanitarian eye clinics</h3>
        <ul class="impact-mission-rules">
          <li>Submit requests at least <strong class="mono">30+</strong> days before departure.</li>
          <li>Provide nonprofit <strong class="mono">Tax ID</strong> and refractive testing method verification.</li>
          <li>Glasses are strictly <strong>not for resale</strong>; requesting missions cover shipping expenses.</li>
        </ul>
      </div>

      <div class="impact-mission-bar__actions">
        <a
          class="btn btn--primary"
          href="<?= $impEsc($impMedia('files/Mission-Request-Form.pdf')) ?>"
          target="_blank"
          rel="noopener"
        >
          <?= erc_icon('file-text', ['width' => '17', 'height' => '17']) ?>
          <span>Mission Request Form (PDF)</span>
        </a>
        <a
          class="btn btn--secondary"
          href="#contact"
          data-prefill-topic="mission"
        >
          <span>Inquire About a Mission</span>
          <?= erc_icon('arrow-right', ['width' => '17', 'height' => '17']) ?>
        </a>
      </div>
    </div>

    <div class="impact-ledger-trigger">
      <button
        type="button"
        class="btn btn--secondary btn--lg"
        data-dialog-open="ledgerDrawer"
      >
        <?= erc_icon('scale', ['width' => '18', 'height' => '18']) ?>
        <span>View 12-Year Shipping Ledger (2013&ndash;2025)</span>
      </button>
    </div>
  </div>
</section>

<dialog
  id="ledgerDrawer"
  class="drawer"
  aria-labelledby="ledgerDrawerTitle"
  closedby="any"
>
  <div class="drawer__header">
    <div>
      <p class="kicker">Audited Brochure Ledger &middot; Jul 1 &ndash; Jun 30</p>
      <h3 id="ledgerDrawerTitle" class="drawer__title">12-Year Shipping Ledger (2013&ndash;2025)</h3>
    </div>
    <button
      type="button"
      class="drawer__close"
      data-dialog-close
      aria-label="Close 12-Year Shipping Ledger"
      autofocus
    >
      <?= erc_icon('x', ['width' => '20', 'height' => '20']) ?>
    </button>
  </div>

  <div class="drawer__body ledger-drawer__body">
    <dl class="stat-row ledger-summary" aria-label="12-year cumulative totals">
      <div class="stat-item">
        <dt class="stat-label">Recorded Pairs (2013&ndash;2025)</dt>
        <dd class="stat-value"><?= $impEsc(number_format($ledgerSumTotal)) ?></dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">Foreign Missions</dt>
        <dd class="stat-value"><?= $impEsc(number_format($ledgerSumForeign)) ?></dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">Local Missions</dt>
        <dd class="stat-value"><?= $impEsc(number_format($ledgerSumLocal)) ?></dd>
      </div>
      <div class="stat-item">
        <dt class="stat-label">Peak Year (2024&ndash;25)</dt>
        <dd class="stat-value">21,696</dd>
      </div>
    </dl>

    <div class="ledger-chart-card">
      <div class="ledger-chart-card__head">
        <h4 class="ledger-chart-card__title">Lion-Year Output Breakdown</h4>
        <div class="ledger-legend" aria-label="Chart legend">
          <span class="ledger-legend__item">
            <span class="ledger-legend__swatch ledger-legend__swatch--foreign" aria-hidden="true"></span>
            Foreign Missions
          </span>
          <span class="ledger-legend__item">
            <span class="ledger-legend__swatch ledger-legend__swatch--local" aria-hidden="true"></span>
            Local Missions
          </span>
        </div>
      </div>

      <ul class="ledger-bars" aria-label="Annual eyeglasses shipped by Lion Year">
<?php foreach ($shippingLedger as $row):
    $foreignPct = round(($row['foreign'] / $ledgerMaxTotal) * 100, 2);
    $localPct = round(($row['local'] / $ledgerMaxTotal) * 100, 2);
?>
        <li class="ledger-bar-row">
          <span class="ledger-bar-row__year mono"><?= $impEsc($row['year']) ?></span>
          <div class="ledger-bar-row__track" aria-hidden="true">
            <span
              class="ledger-bar-row__seg ledger-bar-row__seg--foreign"
              style="inline-size: <?= $impEsc((string) $foreignPct) ?>%"
            ></span>
            <span
              class="ledger-bar-row__seg ledger-bar-row__seg--local"
              style="inline-size: <?= $impEsc((string) $localPct) ?>%"
            ></span>
          </div>
          <span class="ledger-bar-row__total mono"><?= $impEsc(number_format($row['total'])) ?></span>
        </li>
<?php endforeach; ?>
      </ul>
    </div>

    <div class="ledger-table-wrap">
      <table class="ledger-table" id="ledgerTable">
        <caption class="sr-only">Usable eyeglasses shipped by Lion Year from 2013 to 2025</caption>
        <thead>
          <tr>
            <th scope="col" aria-sort="ascending">
              <button type="button" class="ledger-sort-btn" data-sort-col="year">
                Lion Year <span class="ledger-sort-indicator" aria-hidden="true">&#8593;</span>
              </button>
            </th>
            <th scope="col" class="is-num" aria-sort="none">
              <button type="button" class="ledger-sort-btn" data-sort-col="foreign">
                Foreign Missions <span class="ledger-sort-indicator" aria-hidden="true">&#8597;</span>
              </button>
            </th>
            <th scope="col" class="is-num" aria-sort="none">
              <button type="button" class="ledger-sort-btn" data-sort-col="local">
                Local Missions <span class="ledger-sort-indicator" aria-hidden="true">&#8597;</span>
              </button>
            </th>
            <th scope="col" class="is-num" aria-sort="none">
              <button type="button" class="ledger-sort-btn" data-sort-col="total">
                Combined Total <span class="ledger-sort-indicator" aria-hidden="true">&#8597;</span>
              </button>
            </th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($shippingLedger as $row): ?>
          <tr
            data-year="<?= $impEsc($row['year']) ?>"
            data-foreign="<?= $impEsc((string) $row['foreign']) ?>"
            data-local="<?= $impEsc((string) $row['local']) ?>"
            data-total="<?= $impEsc((string) $row['total']) ?>"
          >
            <th scope="row" class="mono"><?= $impEsc($row['year']) ?></th>
            <td class="is-num mono"><?= $impEsc(number_format($row['foreign'])) ?></td>
            <td class="is-num mono"><?= $impEsc(number_format($row['local'])) ?></td>
            <td class="is-num mono ledger-table__total-cell"><?= $impEsc(number_format($row['total'])) ?></td>
          </tr>
<?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th scope="row">12-Year Recorded Total</th>
            <td class="is-num mono"><?= $impEsc(number_format($ledgerSumForeign)) ?></td>
            <td class="is-num mono"><?= $impEsc(number_format($ledgerSumLocal)) ?></td>
            <td class="is-num mono"><?= $impEsc(number_format($ledgerSumTotal)) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</dialog>
