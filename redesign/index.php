<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'home',
    'title'       => 'Lions District 2-E2 Eyeglass Recycling Center | Watauga, TX',
    'description' => '100% volunteer Lions optical recycling center in Watauga, TX collecting eyewear, hearing aids, cell phones, and Windows 11 devices to serve 40+ countries.',
    'canonical'   => '/',
    'preload_img' => erc_media_url('images/our-story-group.png'),
    'og_image'    => erc_media_url('images/our-story-group.png'),
    'css'         => ['/css/pages/editorial.css'],
    'js_modules'  => ['/js/pages/editorial.js'],
    'json_ld'     => [],
    'noindex'     => false,
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

$pipelineStages = [
    [
        'num'      => '01',
        'slug'     => 'collection',
        'short'    => 'COLLECTION',
        'title'    => 'COLLECTION',
        'meta'     => '214 DFW BINS · 34 CITIES',
        'fig'      => 'FIG. 01 · REGIONAL INTAKE NETWORK',
        'summary'  => 'Donated prescription glasses, reading glasses, sunglasses, hearing aids, wiped phones, and Windows 11 laptops arrive from 214 drop-off bins across 34 North Texas cities, Lions Clubs, corporate drives, walk-ins, and pre-arranged shipments.',
        'spec'     => 'Watauga HQ + 214 host boxes',
    ],
    [
        'num'      => '02',
        'slug'     => 'triage',
        'short'    => 'TRIAGE & SORT',
        'title'    => 'INSPECTION & SORTING',
        'meta'     => '5 OPTICAL PATHWAYS',
        'fig'      => 'FIG. 02 · OPTICAL SEPARATION MATRIX',
        'summary'  => 'Volunteers inspect frame integrity and separate eyewear into five primary optical pathways: non-prescription sunglasses, reading glasses, nearsighted (minus/concave), farsighted (plus/convex), and bifocal, trifocal, progressive, and prism lenses.',
        'spec'     => 'Usability check · broken frames repurposed',
    ],
    [
        'num'      => '03',
        'slug'     => 'washing',
        'short'    => 'SANITIZE',
        'title'    => 'WASHING & RACK DRYING',
        'meta'     => 'CLINICAL SANITIZATION',
        'fig'      => 'FIG. 03 · ULTRASONIC WASH & AIR DRY',
        'summary'  => 'Frames and lenses are thoroughly washed, sanitized, and rack-dried by Saturday workteams and weekday volunteer groups before entering the optical measurement bench.',
        'spec'     => 'Clean, dry & ready for lensometry',
    ],
    [
        'num'      => '04',
        'slug'     => 'lensometry',
        'short'    => 'LENSOMETRY',
        'title'    => 'LENSOMETER NEUTRALIZATION',
        'meta'     => '0.25-DIOPTER PRECISION',
        'fig'      => 'FIG. 04 · 0.25D RETICLE CALIBRATION',
        'summary'  => 'Trained volunteers measure exact spherical power on tabletop lensometers so partner optometrists and medical clinic teams receive accurately neutralized prescriptions.',
        'spec'     => 'Calibrated tabletop lensometers',
    ],
    [
        'num'      => '05',
        'slug'     => 'packing',
        'short'    => '50-PAIR BOX',
        'title'    => 'QUALITY CHECK & 50-PAIR PACKING',
        'meta'     => '+0.25 TO ±5.00+ BRACKETS',
        'fig'      => 'FIG. 05 · STANDARDIZED 50-PAIR CARTON',
        'summary'  => 'Each verified pair passes final quality inspection, receives individual tagging and bagging, and is packed into uniform 50-pair cartons sorted by 0.25-diopter increment.',
        'spec'     => 'Single Vision +/- · Readers · Sunglasses',
    ],
    [
        'num'      => '06',
        'slug'     => 'dispatch',
        'short'    => 'DISPATCH',
        'title'    => 'GLOBAL MISSION DISPATCH',
        'meta'     => '38 DOCUMENTED NATIONS · 40+ SERVED',
        'fig'      => 'FIG. 06 · WATAUGA TO FIELD CLINICS',
        'summary'  => 'Verified nonprofit missions with refractive-error testing order 50-pair boxes at least 30 days ahead. Eyewear is supplied free of hardware cost (not for resale; mission covers shipping) for under $0.10 US per pair.',
        'spec'     => '21,696 pairs shipped in 2024–2025',
    ],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main v2-page" tabindex="-1">
  <!-- 01/04 Skippable Optical Calibration Intro Bar (sessionStorage-once, bypassed on reduced motion) -->
  <div class="v2-calib-bar" data-calib-bar role="region" aria-label="Optical workshop calibration status">
    <div class="container v2-calib-bar__inner">
      <div class="v2-calib-bar__status">
        <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
        <span class="v2-calib-bar__step mono" data-calib-step>04/04</span>
        <span class="v2-calib-bar__label mono" data-calib-label>OPTICAL BENCH CALIBRATED · 0.25D READY</span>
      </div>
      <div class="v2-calib-bar__track" aria-hidden="true">
        <span class="v2-calib-bar__fill" data-calib-fill></span>
      </div>
      <button type="button" class="btn btn--secondary btn--sm v2-calib-bar__skip" data-calib-skip hidden>
        <span>SKIP INTRO</span>
      </button>
    </div>
  </div>

  <!-- Atmospheric Poster Hero -->
  <section class="v2-hero" aria-labelledby="home-hero-title">
    <div class="container v2-hero__container">
      <div class="v2-eyebrow v2-hero__eyebrow">
        <span>LIONS DISTRICT 2-E2 // WATAUGA, TEXAS</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>501(C)(3) SINCE 2014 · 100% VOLUNTEER</span>
      </div>

      <div class="v2-hero__grid">
        <div class="v2-hero__copy">
          <h1 id="home-hero-title" class="v2-display v2-display--hero">
            <span class="v2-hero__line">GIVING <span class="v2-knockout">SIGHT</span></span>
            <span class="v2-hero__line">TO 40+ <span class="v2-nowrap">NATIONS<span class="v2-dot" aria-hidden="true"></span></span></span>
          </h1>

          <p class="v2-hero__statement">
            21,696 pairs shipped in 2024–2025 for under $0.10 a pair.
          </p>

          <p class="v2-hero__submono mono">
            214 DFW DROP BINS · 0.25-DIOPTER PRECISION · ZERO PAID STAFF
          </p>

          <p class="lead v2-hero__lead">
            Inside our Watauga workshop, neighbors and Lions Club volunteers clean, neutralize, and pack donated eyewear into standardized 50-pair cartons for nonprofit vision clinics worldwide.
          </p>

          <div class="v2-hero__actions">
            <a class="btn v2-btn-pill btn--lg" href="/donate">
              <?= erc_icon('glasses', ['width' => '18', 'height' => '18']) ?>
              <span>DONATE GLASSES &amp; TECH</span>
            </a>
            <a class="btn btn--secondary btn--lg" href="/locations">
              <?= erc_icon('map-pin', ['width' => '18', 'height' => '18']) ?>
              <span>FIND A DROP BIN (214)</span>
            </a>
            <a class="btn btn--ghost btn--lg" href="/volunteer">
              <?= erc_icon('calendar', ['width' => '18', 'height' => '18']) ?>
              <span>VOLUNTEER SATURDAY</span>
            </a>
          </div>
        </div>

        <div class="v2-hero__stage">
          <p class="v2-note v2-hero__note-top">
            <span>our Watauga crew &amp; founders</span>
            <?= erc_curved_arrow('down-left') ?>
          </p>

          <figure class="v2-hero__photo-frame">
            <img
              class="v2-hero__img"
              src="<?= e(erc_media_url('images/our-story-group.png')) ?>"
              alt="Founding leaders and Lions Club volunteers standing outside the Watauga Eyeglass Recycling Center at 5621 Bunker Blvd"
              width="695"
              height="360"
              fetchpriority="high"
              loading="eager"
              decoding="async"
            >
            <figcaption class="v2-hero__caption">
              <span class="mono">5621 BUNKER BLVD · WATAUGA, TX 76148</span>
              <span class="mono">SAT 9AM–3PM CT</span>
            </figcaption>
          </figure>

          <div class="v2-hero__inset-row">
            <figure class="v2-hero__inset-card">
              <img
                class="v2-hero__inset-img"
                src="<?= e(erc_media_url('gallery/drying-glasses.jpg')) ?>"
                alt="Volunteers inspecting and air-drying sanitized eyeglasses on workshop racks"
                loading="eager"
                decoding="async"
              >
              <figcaption class="mono">STAGE 03 · RACK DRYING</figcaption>
            </figure>

            <div class="v2-hero__access-callout">
              <p class="v2-note v2-hero__note-walkway">
                <span>east-side wooden walkway to rear door</span>
                <?= erc_curved_arrow('up-left') ?>
              </p>
              <p class="v2-hero__access-text">
                <strong>Free on-site parking.</strong> Follow the wooden walkway along the east side of the building to the rear entrance. Weekdays by appointment at
                <a class="v2-inline-link mono" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>"><?= e($GLOBAL_PHONE) ?></a>.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Live Telemetry Bar -->
      <div class="v2-hero__telemetry-bar" role="region" aria-label="Live Watauga workshop telemetry">
        <div class="v2-telemetry">
          <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
          <span>WATAUGA WORKSHOP · 5621 BUNKER BLVD · <span data-ct-clock>SAT 9AM–3PM CT</span></span>
        </div>

        <div class="v2-hero__telemetry-right">
          <a class="btn btn--secondary btn--sm" href="#impact-board">
            <span>SCROLL &darr;</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Mechanical Solari Split-Flap Impact Board -->
  <section id="impact-board" class="section v2-solari-section" aria-labelledby="solari-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>VERIFIED DISPATCH LEDGER</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>LION YEAR JULY 1 – JUNE 30 · EST. 2013</span>
      </div>

      <div class="v2-solari-head">
        <h2 id="solari-title" class="v2-display v2-display--lg">
          <span>ONE <span class="v2-nowrap">CENTER<span class="v2-dot" aria-hidden="true"></span></span></span>
          <span>GLOBAL <span class="v2-nowrap"><span class="v2-accent-text">SIGHT</span><span class="v2-dot" aria-hidden="true"></span></span></span>
        </h2>
        <p class="lead">
          Six verified operational benchmarks from our Watauga bench and 2013–2025 shipping ledger. Spin the mechanical split-flap board to cycle each telemetry card.
        </p>
      </div>

      <div class="v2-solari-housing" data-solari-board>
        <div class="v2-solari-housing__top">
          <div class="v2-solari-status-wrap">
            <span class="v2-pulse" aria-hidden="true"></span>
            <span class="mono" data-solari-status>CYCLING VERIFIED METRICS</span>
          </div>

          <div class="v2-solari-segments" data-solari-segments aria-hidden="true">
            <span class="is-active"></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
          </div>

          <div class="v2-solari-counter mono" data-solari-counter>FACT 01/06</div>
        </div>

        <!-- 2 rows x 9 columns mechanical split-flap board -->
        <div class="v2-solari-grid" data-solari-grid aria-hidden="true">
          <?php
          // Initial server-rendered state: Fact 01 ("21,696+" / "PAIRS")
          $initRow1 = str_split('21,696+  ');
          $initRow2 = str_split('PAIRS    ');
          foreach ($initRow1 as $colIdx => $ch):
              $isAccent = $ch !== ' ';
          ?>
            <div class="v2-flap<?= $isAccent ? ' v2-flap--accent' : '' ?>" data-row="0" data-col="<?= $colIdx ?>">
              <div class="v2-flap__top"><span><?= e($ch === ' ' ? "\u{00A0}" : $ch) ?></span></div>
              <div class="v2-flap__bottom"><span><?= e($ch === ' ' ? "\u{00A0}" : $ch) ?></span></div>
              <div class="v2-flap__hinge"></div>
            </div>
          <?php endforeach; ?>
          <?php foreach ($initRow2 as $colIdx => $ch): ?>
            <div class="v2-flap" data-row="1" data-col="<?= $colIdx ?>">
              <div class="v2-flap__top"><span><?= e($ch === ' ' ? "\u{00A0}" : $ch) ?></span></div>
              <div class="v2-flap__bottom"><span><?= e($ch === ' ' ? "\u{00A0}" : $ch) ?></span></div>
              <div class="v2-flap__hinge"></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="v2-solari-housing__foot">
          <p class="v2-solari-caption" data-solari-caption aria-live="polite">
            <strong>21,696+ Pairs (2024–2025):</strong> 17,692 foreign + 4,004 local mission pairs shipped in our highest recorded Lion Year.
          </p>
          <span class="v2-solari-tag mono" data-solari-tag>LION YEAR 2024–2025</span>
        </div>
      </div>

      <div class="v2-solari-controls">
        <button type="button" class="btn btn--secondary v2-solari-spin" data-solari-spin>
          <?= erc_icon('refresh-cw', ['width' => '17', 'height' => '17']) ?>
          <span>&#8635; SPIN BOARD</span>
        </button>

        <div class="v2-solari-pills" role="group" aria-label="Select verified benchmark">
          <button type="button" class="btn btn--secondary btn--sm v2-solari-pill" data-solari-index="0" aria-pressed="true">01 · PAIRS</button>
          <button type="button" class="btn btn--secondary btn--sm v2-solari-pill" data-solari-index="1" aria-pressed="false">02 · BINS</button>
          <button type="button" class="btn btn--secondary btn--sm v2-solari-pill" data-solari-index="2" aria-pressed="false">03 · NATIONS</button>
          <button type="button" class="btn btn--secondary btn--sm v2-solari-pill" data-solari-index="3" aria-pressed="false">04 · COST</button>
          <button type="button" class="btn btn--secondary btn--sm v2-solari-pill" data-solari-index="4" aria-pressed="false">05 · GRADE</button>
          <button type="button" class="btn btn--secondary btn--sm v2-solari-pill" data-solari-index="5" aria-pressed="false">06 · BOXES</button>
        </div>

        <a class="btn btn--ghost btn--sm" href="/missions#shipping-history">
          <span>FULL 2013–2025 LEDGER</span>
          <?= erc_icon('arrow-right', ['width' => '16', 'height' => '16']) ?>
        </a>
      </div>
    </div>
  </section>

  <!-- Connected SVG Optical Transit Line ("THE ROUTE OF EVERY PAIR") -->
  <section id="optical-pipeline" class="section section--subtle v2-transit-section" aria-labelledby="pipeline-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>32.8707&deg; N, 97.2621&deg; W</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>0.25-DIOPTER REFURBISHMENT LINE</span>
      </div>

      <div class="v2-transit-header">
        <div>
          <h2 id="pipeline-title" class="v2-display v2-display--lg">
            <span>THE ROUTE OF</span>
            <span>EVERY <span class="v2-nowrap"><span class="v2-accent-text">PAIR</span><span class="v2-dot" aria-hidden="true"></span></span></span>
          </h2>
        </div>
        <div class="v2-transit-header__aside">
          <p class="v2-note">
            <span>from drop box to clinic table</span>
            <?= erc_curved_arrow('down-left') ?>
          </p>
          <p class="lead">
            Six disciplined stages transform unsorted neighborhood donations into ready-to-dispense 50-pair cartons graded to <span class="mono">0.25</span> diopters.
          </p>
        </div>
      </div>

      <!-- Interactive Transit Arc + Station Selector -->
      <div class="v2-transit-rail" data-transit-rail>
        <div class="v2-transit-rail__controls">
          <div class="v2-transit-tabs" role="tablist" aria-label="Refurbishment pipeline stations">
            <?php foreach ($pipelineStages as $idx => $stage): ?>
              <button
                type="button"
                role="tab"
                class="v2-transit-tab"
                id="tab-stage-<?= e($stage['num']) ?>"
                aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>"
                aria-controls="stage-card-<?= e($stage['num']) ?>"
                data-stage-trigger="<?= $idx ?>"
              >
                <span class="mono"><?= e($stage['num']) ?></span>
                <span><?= e($stage['short']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="v2-transit-nav-btns">
            <button type="button" class="btn btn--secondary btn--sm" data-stage-prev aria-label="Previous pipeline stage">
              <?= erc_icon('chevron-left', ['width' => '16', 'height' => '16']) ?>
              <span>PREV</span>
            </button>
            <button type="button" class="btn btn--secondary btn--sm" data-stage-next aria-label="Next pipeline stage">
              <span>NEXT</span>
              <?= erc_icon('chevron-right', ['width' => '16', 'height' => '16']) ?>
            </button>
          </div>
        </div>

        <!-- Connected Catenary Suspension SVG Arc -->
        <div class="v2-transit-svg-wrap" aria-hidden="true">
          <svg
            class="v2-transit-svg"
            viewBox="0 0 1200 132"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            preserveAspectRatio="none"
          >
            <!-- Anchor pylons -->
            <line x1="80" y1="16" x2="80" y2="128" stroke="currentColor" stroke-opacity="0.22" stroke-width="1.5" />
            <line x1="600" y1="16" x2="600" y2="128" stroke="currentColor" stroke-opacity="0.22" stroke-width="1.5" />
            <line x1="1120" y1="16" x2="1120" y2="128" stroke="currentColor" stroke-opacity="0.22" stroke-width="1.5" />

            <!-- Full dashed trajectory catenary -->
            <path
              d="M 80 28 Q 340 106 600 28 Q 860 106 1120 28"
              stroke="currentColor"
              stroke-opacity="0.28"
              stroke-width="2"
              stroke-dasharray="5 6"
            />

            <!-- Active vermilion suspension cable (updated via JS stroke-dasharray/dashoffset or path segment) -->
            <path
              class="v2-transit-arc-active"
              data-transit-active-path
              d="M 80 28 Q 340 106 600 28 Q 860 106 1120 28"
              stroke="var(--accent-ink)"
              stroke-width="3"
              stroke-linecap="round"
            />

            <!-- 6 Vertical Suspension Hanger Cables & Station Nodes -->
            <g data-transit-nodes>
              <line x1="80" y1="28" x2="80" y2="128" stroke="var(--accent-ink)" stroke-width="1.5" stroke-opacity="0.65" data-hanger="0" />
              <circle cx="80" cy="28" r="7" fill="var(--accent-ink)" data-node-dot="0" />

              <line x1="288" y1="64" x2="288" y2="128" stroke="currentColor" stroke-width="1.25" stroke-opacity="0.28" data-hanger="1" />
              <circle cx="288" cy="64" r="5.5" fill="var(--surface)" stroke="currentColor" stroke-opacity="0.45" stroke-width="2" data-node-dot="1" />

              <line x1="496" y1="54" x2="496" y2="128" stroke="currentColor" stroke-width="1.25" stroke-opacity="0.28" data-hanger="2" />
              <circle cx="496" cy="54" r="5.5" fill="var(--surface)" stroke="currentColor" stroke-opacity="0.45" stroke-width="2" data-node-dot="2" />

              <line x1="704" y1="54" x2="704" y2="128" stroke="currentColor" stroke-width="1.25" stroke-opacity="0.28" data-hanger="3" />
              <circle cx="704" cy="54" r="5.5" fill="var(--surface)" stroke="currentColor" stroke-opacity="0.45" stroke-width="2" data-node-dot="3" />

              <line x1="912" y1="64" x2="912" y2="128" stroke="currentColor" stroke-width="1.25" stroke-opacity="0.28" data-hanger="4" />
              <circle cx="912" cy="64" r="5.5" fill="var(--surface)" stroke="currentColor" stroke-opacity="0.45" stroke-width="2" data-node-dot="4" />

              <line x1="1120" y1="28" x2="1120" y2="128" stroke="currentColor" stroke-width="1.25" stroke-opacity="0.28" data-hanger="5" />
              <circle cx="1120" cy="28" r="5.5" fill="var(--surface)" stroke="currentColor" stroke-opacity="0.45" stroke-width="2" data-node-dot="5" />
            </g>
          </svg>
        </div>

        <!-- 6 Schematic Blueprint Station Cards -->
        <div class="v2-schematic-grid">
          <?php foreach ($pipelineStages as $idx => $stage): ?>
            <article
              id="stage-card-<?= e($stage['num']) ?>"
              class="v2-schematic-card v2-schematic-card--split v2-station-card<?= $idx === 0 ? ' is-active' : '' ?>"
              data-stage-card="<?= $idx ?>"
            >
              <div class="v2-station-card__body">
                <div class="v2-schematic-card__head">
                  <span class="v2-schematic-card__num"><?= e($stage['num']) ?></span>
                  <span><?= e($stage['meta']) ?></span>
                </div>

                <h3 class="v2-schematic-card__title"><?= e($stage['title']) ?></h3>
                <p class="v2-station-card__desc"><?= e($stage['summary']) ?></p>

                <div class="v2-station-card__foot">
                  <span class="badge badge--brand"><?= e($stage['spec']) ?></span>
                </div>
              </div>

              <div class="v2-schematic-box">
                <span class="v2-schematic-box__caption"><?= e($stage['fig']) ?></span>

                <?php if ($stage['num'] === '01'): ?>
                  <svg class="v2-blueprint-svg" viewBox="0 0 260 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="44" cy="36" r="14" stroke="currentColor" stroke-opacity="0.35" stroke-width="1.5" />
                    <circle cx="44" cy="36" r="4" fill="currentColor" fill-opacity="0.4" />
                    <circle cx="130" cy="24" r="14" stroke="currentColor" stroke-opacity="0.35" stroke-width="1.5" />
                    <circle cx="130" cy="24" r="4" fill="currentColor" fill-opacity="0.4" />
                    <circle cx="216" cy="36" r="14" stroke="currentColor" stroke-opacity="0.35" stroke-width="1.5" />
                    <circle cx="216" cy="36" r="4" fill="currentColor" fill-opacity="0.4" />
                    <path d="M44 50 L118 86 M130 38 L130 82 M216 50 L142 86" stroke="currentColor" stroke-opacity="0.35" stroke-width="1.5" stroke-dasharray="3 4" />
                    <rect x="86" y="84" width="88" height="34" rx="6" stroke="var(--accent-ink)" stroke-width="2" />
                    <text x="130" y="105" text-anchor="middle" fill="var(--accent-ink)" font-family="monospace" font-size="11" font-weight="700">214 BINS → HQ</text>
                  </svg>
                <?php elseif ($stage['num'] === '02'): ?>
                  <svg class="v2-blueprint-svg" viewBox="0 0 260 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <!-- Concave (-) vs Convex (+) vs Sunglasses/Multifocal -->
                    <path d="M36 32 C46 50, 46 80, 36 98 L54 98 C46 80, 46 50, 54 32 Z" stroke="currentColor" stroke-opacity="0.55" stroke-width="1.5" />
                    <path d="M108 32 C94 52, 94 78, 108 98 C122 78, 122 52, 108 32 Z" stroke="var(--accent-ink)" stroke-width="2" />
                    <circle cx="172" cy="65" r="24" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
                    <path d="M150 72 A22 14 0 0 0 194 72" stroke="var(--accent-ink)" stroke-width="1.75" />
                    <rect x="210" y="42" width="32" height="46" rx="4" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
                    <line x1="210" y1="65" x2="242" y2="65" stroke="currentColor" stroke-opacity="0.35" stroke-dasharray="3 3" />
                    <text x="45" y="118" text-anchor="middle" fill="currentColor" fill-opacity="0.65" font-family="monospace" font-size="9">MINUS -</text>
                    <text x="108" y="118" text-anchor="middle" fill="var(--accent-ink)" font-family="monospace" font-size="9">PLUS +</text>
                    <text x="172" y="118" text-anchor="middle" fill="currentColor" fill-opacity="0.65" font-family="monospace" font-size="9">BIFOCAL</text>
                    <text x="226" y="118" text-anchor="middle" fill="currentColor" fill-opacity="0.65" font-family="monospace" font-size="9">SUN</text>
                  </svg>
                <?php elseif ($stage['num'] === '03'): ?>
                  <svg class="v2-blueprint-svg" viewBox="0 0 260 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="24" y="34" width="212" height="66" rx="10" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
                    <path d="M40 58 Q60 48 80 58 T120 58 T160 58 T200 58 T224 58" stroke="var(--accent-ink)" stroke-width="2" />
                    <path d="M40 76 Q60 66 80 76 T120 76 T160 76 T200 76 T224 76" stroke="currentColor" stroke-opacity="0.4" stroke-width="1.5" />
                    <circle cx="90" cy="22" r="5" stroke="var(--accent-ink)" stroke-width="1.5" />
                    <circle cx="135" cy="18" r="7" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
                    <circle cx="176" cy="24" r="4" stroke="var(--accent-ink)" stroke-width="1.5" />
                    <text x="130" y="118" text-anchor="middle" fill="currentColor" fill-opacity="0.65" font-family="monospace" font-size="9.5">WASH · SANITIZE · RACK DRY</text>
                  </svg>
                <?php elseif ($stage['num'] === '04'): ?>
                  <div class="v2-stage4-media">
                    <div class="erc-rotated-frame erc-rotated-frame--landscape">
                      <img
                        class="is-ccw90-no-exif"
                        src="<?= e(erc_media_url('images/process-lensometers.jpg')) ?>"
                        alt="Volunteers operating optical lensometers at the Watauga workshop to measure lens prescriptions"
                        width="1200"
                        height="900"
                        loading="eager"
                        decoding="async"
                      >
                    </div>
                    <svg class="v2-blueprint-svg v2-blueprint-svg--compact" viewBox="0 0 260 76" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <circle cx="52" cy="38" r="26" stroke="currentColor" stroke-opacity="0.4" stroke-width="1.5" />
                      <circle cx="52" cy="38" r="14" stroke="var(--accent-ink)" stroke-width="1.75" stroke-dasharray="3 3" />
                      <line x1="20" y1="38" x2="84" y2="38" stroke="var(--accent-ink)" stroke-width="1.5" />
                      <line x1="52" y1="6" x2="52" y2="70" stroke="var(--accent-ink)" stroke-width="1.5" />
                      <text x="168" y="34" text-anchor="middle" fill="var(--accent-ink)" font-family="monospace" font-size="14" font-weight="700">0.25D STEP</text>
                      <text x="168" y="52" text-anchor="middle" fill="currentColor" fill-opacity="0.65" font-family="monospace" font-size="9.5">SPH / AXIS READOUT</text>
                    </svg>
                  </div>
                <?php elseif ($stage['num'] === '05'): ?>
                  <svg class="v2-blueprint-svg" viewBox="0 0 260 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <polygon points="130,16 214,44 130,72 46,44" stroke="var(--accent-ink)" stroke-width="2" />
                    <polygon points="46,44 130,72 130,114 46,86" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
                    <polygon points="214,44 130,72 130,114 214,86" stroke="currentColor" stroke-opacity="0.45" stroke-width="1.5" />
                    <line x1="88" y1="30" x2="172" y2="58" stroke="var(--accent-ink)" stroke-width="1.25" stroke-dasharray="4 3" />
                    <text x="130" y="126" text-anchor="middle" fill="var(--accent-ink)" font-family="monospace" font-size="10" font-weight="700">50 PAIRS / DIOPTER CARTON</text>
                  </svg>
                <?php else: ?>
                  <svg class="v2-blueprint-svg" viewBox="0 0 260 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="52" cy="65" r="7" fill="var(--accent-ink)" />
                    <path d="M52 65 Q120 14 210 28" stroke="var(--accent-ink)" stroke-width="2" />
                    <path d="M52 65 Q132 54 222 65" stroke="currentColor" stroke-opacity="0.55" stroke-width="1.5" stroke-dasharray="4 4" />
                    <path d="M52 65 Q120 112 206 98" stroke="var(--accent-ink)" stroke-width="1.75" stroke-dasharray="4 3" />
                    <circle cx="210" cy="28" r="5" stroke="var(--accent-ink)" stroke-width="2" />
                    <circle cx="222" cy="65" r="5" stroke="currentColor" stroke-opacity="0.6" stroke-width="1.75" />
                    <circle cx="206" cy="98" r="5" stroke="var(--accent-ink)" stroke-width="2" />
                    <text x="130" y="122" text-anchor="middle" fill="currentColor" fill-opacity="0.65" font-family="monospace" font-size="9.5">AMERICAS · AFRICA · ASIA · EUROPE · FIJI</text>
                  </svg>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="v2-transit-footer">
        <a class="btn btn--secondary" href="/donate#refurbishment-process">
          <span>DIOPTER &amp; PACKAGING SPECS</span>
          <?= erc_icon('arrow-right', ['width' => '16', 'height' => '16']) ?>
        </a>
        <a class="btn btn--primary" href="/volunteer">
          <?= erc_icon('users', ['width' => '16', 'height' => '16']) ?>
          <span>JOIN A SATURDAY WORKTEAM</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Contrast Stage Sheet: THREE WAYS IN + Verbatim Testimonials -->
  <section class="section">
    <div class="v2-sheet" aria-labelledby="ways-in-title">
      <div class="v2-sheet__inner">
        <div class="v2-eyebrow">
          <span>&mdash; PARTICIPATION PASSES</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
          <span>100% VOLUNTEER-RUN · 501(C)(3)</span>
        </div>

        <div class="v2-sheet-head">
          <h2 id="ways-in-title" class="v2-display v2-display--lg">
            <span>THREE WAYS <span class="v2-nowrap"><span class="v2-accent-text">IN</span><span class="v2-dot" aria-hidden="true"></span></span></span>
          </h2>
          <div class="v2-sheet-head__note">
            <p class="v2-note">
              <span>pick your pass</span>
              <?= erc_curved_arrow('down-left') ?>
            </p>
            <p class="lead">
              Whether you have one spare frame, a Saturday morning, or a medical mission team preparing for departure, jump directly to your station.
            </p>
          </div>
        </div>

        <div class="v2-tickets-grid">
          <article class="v2-ticket">
            <div class="v2-ticket__main">
              <div class="v2-ticket__meta">
                <span>PASS 01 · NEIGHBORHOOD DROP-OFF</span>
                <span>214 LOCATIONS</span>
              </div>
              <h3 class="v2-ticket__title">DROP A PAIR</h3>
              <p class="v2-ticket__desc">
                Drop off prescription glasses, readers, or sunglasses in any condition across 34 North Texas cities—or bring hearing aids, wiped phones, and Windows 11 devices to Watauga HQ.
              </p>
              <div class="v2-ticket__actions">
                <a class="btn btn--primary" href="/locations">
                  <?= erc_icon('map-pin', ['width' => '16', 'height' => '16']) ?>
                  <span>FIND A DROP BIN</span>
                </a>
                <a class="btn btn--secondary" href="/donate">
                  <span>ELIGIBILITY GUIDE</span>
                </a>
              </div>
            </div>
            <div class="v2-ticket__stub">
              <span class="v2-ticket__serial">NO. 01 · DROP A PAIR</span>
              <span class="badge badge--brand">DFW · 34 CITIES</span>
            </div>
          </article>

          <article class="v2-ticket">
            <div class="v2-ticket__main">
              <div class="v2-ticket__meta">
                <span>PASS 02 · WATAUGA WORKSHOP</span>
                <span>SAT 9AM–3PM CT</span>
              </div>
              <h3 class="v2-ticket__title">WORK A SHIFT</h3>
              <p class="v2-ticket__desc">
                Walk in Saturdays or reserve a slot for your club, school, church, or company (up to 20 volunteers per group). On-site training provided; no prior optical experience needed.
              </p>
              <div class="v2-ticket__actions">
                <a class="btn btn--primary" href="/volunteer">
                  <?= erc_icon('calendar', ['width' => '16', 'height' => '16']) ?>
                  <span>VIEW SHIFTS &amp; SIGN UP</span>
                </a>
              </div>
            </div>
            <div class="v2-ticket__stub">
              <span class="v2-ticket__serial">NO. 02 · WORK A SHIFT</span>
              <span class="badge badge--brand">UP TO 20 / GROUP</span>
            </div>
          </article>

          <article class="v2-ticket">
            <div class="v2-ticket__main">
              <div class="v2-ticket__meta">
                <span>PASS 03 · HUMANITARIAN CLINICS</span>
                <span>30-DAY LEAD TIME</span>
              </div>
              <h3 class="v2-ticket__title">REQUEST MISSION</h3>
              <p class="v2-ticket__desc">
                Qualifying nonprofit missions with refractive testing receive 50-pair diopter boxes free of hardware cost (strictly not for resale; mission covers shipping expenses).
              </p>
              <div class="v2-ticket__actions">
                <a class="btn btn--primary" href="/missions#mission-requests">
                  <?= erc_icon('globe', ['width' => '16', 'height' => '16']) ?>
                  <span>REQUEST EYEWEAR</span>
                </a>
              </div>
            </div>
            <div class="v2-ticket__stub">
              <span class="v2-ticket__serial">NO. 03 · REQUEST MISSION</span>
              <span class="badge badge--brand">50-PAIR BOXES</span>
            </div>
          </article>
        </div>

        <!-- Verbatim Partner Clinic Testimonials -->
        <?php if (!empty($testimonials)): ?>
          <div class="v2-testimonials-block" aria-labelledby="field-voices-title">
            <div class="v2-eyebrow">
              <span>&mdash; VOICES FROM PARTNER CLINICS</span>
              <span class="v2-eyebrow__rule" aria-hidden="true"></span>
              <span>VERBATIM FIELD REPORTS</span>
            </div>

            <div class="v2-testimonials-head">
              <h3 id="field-voices-title" class="v2-display v2-display--md">
                WHERE EVERY PAIR <span class="v2-nowrap"><span class="v2-accent-text">LANDS</span><span class="v2-dot" aria-hidden="true"></span></span>
              </h3>
              <a class="btn btn--secondary btn--sm" href="/missions#clinic-stories">
                <span>MISSIONS &amp; GLOBAL REACH</span>
                <?= erc_icon('arrow-right', ['width' => '16', 'height' => '16']) ?>
              </a>
            </div>

            <div class="v2-quotes-grid">
              <?php foreach ($testimonials as $qIdx => $quote): ?>
                <figure class="v2-quote-card">
                  <div class="v2-quote-card__top mono">
                    <span>FIELD DISPATCH 0<?= $qIdx + 1 ?></span>
                  </div>
                  <blockquote class="v2-quote-card__text">
                    &ldquo;<?= e((string)($quote['text'] ?? '')) ?>&rdquo;
                  </blockquote>
                  <figcaption class="v2-quote-card__meta">
                    <span class="v2-quote-card__author"><?= e((string)($quote['author'] ?? '')) ?></span>
                    <?php if (trim((string)($quote['organization'] ?? '')) !== ''): ?>
                      <span class="v2-quote-card__org"><?= e((string)$quote['organization']) ?></span>
                    <?php endif; ?>
                  </figcaption>
                </figure>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Watauga HQ & P.O. Box Summary -->
        <div class="v2-hq-strip">
          <div class="v2-hq-strip__card">
            <span class="mono v2-hq-strip__label">PHYSICAL CENTER &amp; DROP-OFF</span>
            <p class="v2-hq-strip__title">5621 Bunker Blvd, Watauga, TX 76148</p>
            <p class="v2-hq-strip__text">
              Saturdays 9:00 AM – 3:00 PM CT; Mon–Fri by appointment; closed Sundays. Free on-site parking — follow the east-side wooden walkway to the rear entrance.
            </p>
            <div class="v2-hq-strip__actions">
              <a class="btn btn--secondary btn--sm" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>">
                <?= erc_icon('phone', ['width' => '15', 'height' => '15']) ?>
                <span><?= e($GLOBAL_PHONE) ?></span>
              </a>
              <a class="btn btn--secondary btn--sm" href="/contact">
                <?= erc_icon('mail', ['width' => '15', 'height' => '15']) ?>
                <span>CONTACT &amp; FORMS</span>
              </a>
            </div>
          </div>

          <div class="v2-hq-strip__card">
            <span class="mono v2-hq-strip__label">MAILING &amp; 501(C)(3) CHECK DONATIONS</span>
            <p class="v2-hq-strip__title">P.O. Box 1854, Keller, TX 76244</p>
            <p class="v2-hq-strip__text">
              Payable to <strong>Lions District 2-E2 ERC</strong>. 100% of financial contributions support mission shipping, processing supplies, and facility operations (501(c)(3) recognized 2014).
            </p>
            <div class="v2-hq-strip__actions">
              <a class="btn btn--primary btn--sm" href="/donate#monetary-gifts">
                <?= erc_icon('dollar-sign', ['width' => '15', 'height' => '15']) ?>
                <span>CHECK &amp; MAIL GUIDE</span>
              </a>
              <a class="btn btn--secondary btn--sm" href="/about#recognition">
                <?= erc_icon('award', ['width' => '15', 'height' => '15']) ?>
                <span>HELEN KELLER &amp; LIFE AWARDS</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
