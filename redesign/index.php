<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'home',
    'title'       => 'Lions District 2-E2 Eyeglass Recycling Center | Watauga, TX',
    'description' => '100% volunteer Lions optical recycling center in Watauga, TX collecting eyewear, hearing aids, cell phones, and Windows 11 devices to serve 40+ countries.',
    'canonical'   => '/',
    'preload_img' => '/images/our-story-group.png',
    'og_image'    => '/images/our-story-group.png',
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
$featuredQuote = $testimonials[1] ?? ($testimonials[0] ?? null);

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main ed-page" tabindex="-1">
  <!-- Editorial Hero -->
  <section class="ed-hero" aria-labelledby="home-hero-title">
    <div class="ed-wrap">
      <div class="ed-hero__grid">
        <div>
          <div class="ed-hero__crest">
            <img
              class="ed-hero__logo"
              src="/e2e2rc_LOGO.png"
              alt="Official circular emblem of the Lions District 2-E2 Eyeglass Recycling Center"
              width="96"
              height="96"
            >
            <span class="ed-hero__crest-text">Lions District 2-E2 · Watauga, TX · 501(c)(3) Since 2014</span>
          </div>

          <h1 id="home-hero-title" class="ed-display">
            North Texas Optical Care, Refurbished for the World.
          </h1>

          <p class="ed-lead">
            Every Saturday inside our 100% volunteer Watauga workshop, neighbors and Lions Club teams clean, neutralize, and verify donated eyeglasses to 0.25-diopter precision—delivering clear sight to humanitarian clinics in 40+ countries for less than $0.10 a pair.
          </p>

          <div class="ed-actions">
            <a class="ed-btn ed-btn--primary" href="/donate">
              <?= erc_icon('glasses') ?>
              <span>Donate Glasses &amp; Tech</span>
            </a>
            <a class="ed-btn ed-btn--secondary" href="/locations">
              <?= erc_icon('map-pin') ?>
              <span>Find a Drop-Off Bin</span>
            </a>
            <a class="ed-btn ed-btn--ghost" href="/volunteer">
              <?= erc_icon('calendar') ?>
              <span>Volunteer Saturday</span>
            </a>
          </div>
        </div>

        <figure class="ed-hero__figure">
          <img
            class="ed-hero__img"
            src="/images/our-story-group.png"
            alt="Founding team and Lions Club volunteers standing together outside the Lions District 2-E2 Eyeglass Recycling Center in Watauga, Texas"
            width="695"
            height="360"
            fetchpriority="high"
            loading="eager"
            decoding="async"
          >
          <figcaption class="ed-hero__caption">
            <span>Founders &amp; volunteer leaders at our Watauga center</span>
            <span class="ed-mono">5621 Bunker Blvd</span>
          </figcaption>
        </figure>
      </div>

      <!-- Live Saturday Watauga Hours & East-Side Walkway Callout -->
      <div class="ed-dispatch-strip">
        <div class="ed-dispatch-panel" role="region" aria-label="Watauga Center Hours and Rear Entrance Access">
          <div class="ed-dispatch-block">
            <div class="ed-hours-badge" data-watauga-hours data-state="appointment">
              <?= erc_icon('clock') ?>
              <span data-watauga-hours-text>Saturdays 9:00 AM – 3:00 PM CT · Mon–Fri by Appointment</span>
            </div>
            <div class="ed-dispatch-title">
              <?= erc_icon('calendar') ?>
              <span>Watauga Center Operating Hours</span>
            </div>
            <p class="ed-dispatch-meta">
              Open most Saturdays <span class="ed-mono">9:00 AM – 3:00 PM</span> (volunteer sessions <span class="ed-mono">9–12</span> &amp; <span class="ed-mono">12–3</span>). Weekdays by appointment; closed Sundays.
            </p>
          </div>

          <div class="ed-dispatch-block">
            <div class="ed-dispatch-title">
              <?= erc_icon('navigation') ?>
              <span>East-Side Wooden Walkway Entrance</span>
            </div>
            <p class="ed-dispatch-meta">
              <strong>5621 Bunker Blvd, Watauga, TX 76148</strong> · Free on-site parking. Follow the wooden walkway along the <strong>east side</strong> of the building to reach the <strong>rear entrance</strong>.
            </p>
          </div>

          <div class="ed-actions">
            <a class="ed-btn ed-btn--secondary" href="/locations">
              <?= erc_icon('map-pin') ?>
              <span>Map &amp; 214 Bins</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Verified Impact Metrics -->
  <section class="ed-band ed-band--subtle" aria-labelledby="home-metrics-title">
    <div class="ed-wrap ed-metrics-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('sparkles') ?> Verified Dispatch Ledger</span>
          <h2 id="home-metrics-title" class="ed-h2">Measurable Reach from North Texas Volunteers</h2>
          <p class="ed-lead">
            Every figure below comes directly from our Lion-Year shipping records (<span class="ed-mono">July 1 – June 30</span>) and active regional collection network.
          </p>
        </div>
        <a class="ed-btn ed-btn--ghost" href="/missions#shipping-history">
          <span>Full 2013–2025 Ledger</span>
          <?= erc_icon('arrow-right') ?>
        </a>
      </div>

      <ul class="ed-metrics-grid">
        <li class="ed-metric-card">
          <span class="ed-metric-value">21,696</span>
          <span class="ed-metric-label">Pairs Shipped (2024–2025)</span>
          <p class="ed-metric-note">17,692 foreign + 4,004 local mission pairs—our highest recorded Lion Year.</p>
        </li>
        <li class="ed-metric-card ed-metric-card--gold">
          <span class="ed-metric-value">214</span>
          <span class="ed-metric-label">Drop-Off Bins Across 34 Cities</span>
          <p class="ed-metric-note">Partner collection boxes across Tarrant County and North Texas.</p>
        </li>
        <li class="ed-metric-card">
          <span class="ed-metric-value">40+</span>
          <span class="ed-metric-label">Countries Served Worldwide</span>
          <p class="ed-metric-note">The Americas, Africa, Asia, Europe, and Fiji via partner clinics.</p>
        </li>
        <li class="ed-metric-card ed-metric-card--emerald">
          <span class="ed-metric-value">&lt; $0.10</span>
          <span class="ed-metric-label">Processing Cost per Pair</span>
          <p class="ed-metric-note">Under 10&cent; US per pair thanks to donated eyewear and community labor.</p>
        </li>
        <li class="ed-metric-card ed-metric-card--gold">
          <span class="ed-metric-value">100%</span>
          <span class="ed-metric-label">Volunteer-Run Since 2013</span>
          <p class="ed-metric-note">Zero paid staff; 501(c)(3) tax-exempt nonprofit recognized in 2014.</p>
        </li>
      </ul>
    </div>
  </section>

  <!-- 4 Clear Visitor Intent Pathways -->
  <section class="ed-band ed-band--surface" aria-labelledby="home-pathways-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('heart-handshake') ?> How to Take Part</span>
          <h2 id="home-pathways-title" class="ed-h2">Four Clear Ways to Put Sight Within Reach</h2>
          <p class="ed-lead">
            Whether you have a drawer of spare frames, two hours on a Saturday morning, or a humanitarian clinic preparing for departure, start directly where you belong.
          </p>
        </div>
      </div>

      <div class="ed-grid ed-grid--4">
        <article class="ed-card ed-card--clickable">
          <div class="ed-card__icon-box">
            <?= erc_icon('glasses') ?>
          </div>
          <h3 class="ed-h3">
            <a class="ed-card-link" href="/donate">Donate Glasses &amp; Tech</a>
          </h3>
          <p class="ed-prose">
            Give prescription glasses, readers, or sunglasses in any condition—plus hearing aids, wiped cell phones, and Windows 11 laptops or tablets.
          </p>
          <footer class="ed-card__footer">
            <span>See accepted items</span>
            <?= erc_icon('arrow-right') ?>
          </footer>
        </article>

        <article class="ed-card ed-card--clickable">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('map-pin') ?>
          </div>
          <h3 class="ed-h3">
            <a class="ed-card-link" href="/locations">Find a Drop-Off Bin</a>
          </h3>
          <p class="ed-prose">
            Search 214 North Texas drop-off bins across 34 cities or visit our Watauga headquarters on Saturdays from 9:00 AM to 3:00 PM.
          </p>
          <footer class="ed-card__footer">
            <span>Open map &amp; directory</span>
            <?= erc_icon('arrow-right') ?>
          </footer>
        </article>

        <article class="ed-card ed-card--clickable">
          <div class="ed-card__icon-box ed-card__icon-box--emerald">
            <?= erc_icon('users') ?>
          </div>
          <h3 class="ed-h3">
            <a class="ed-card-link" href="/volunteer">Volunteer Saturday</a>
          </h3>
          <p class="ed-prose">
            Drop in individually on Saturdays or schedule a group of up to 20 from your club, school, church, or company—no prior experience needed.
          </p>
          <footer class="ed-card__footer">
            <span>View calendar &amp; sign up</span>
            <?= erc_icon('arrow-right') ?>
          </footer>
        </article>

        <article class="ed-card ed-card--clickable">
          <div class="ed-card__icon-box">
            <?= erc_icon('globe') ?>
          </div>
          <h3 class="ed-h3">
            <a class="ed-card-link" href="/missions#mission-requests">Request Mission Eyewear</a>
          </h3>
          <p class="ed-prose">
            Qualifying nonprofit clinics with refractive testing may order 50-pair diopter boxes at zero hardware cost (30-day lead time; mission covers shipping).
          </p>
          <footer class="ed-card__footer">
            <span>Mission request guide</span>
            <?= erc_icon('arrow-right') ?>
          </footer>
        </article>
      </div>
    </div>
  </section>

  <!-- Visual 6-Stage Optical Refurbishment Overview -->
  <section class="ed-band" aria-labelledby="home-process-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('recycle') ?> Optical Workshop Process</span>
          <h2 id="home-process-title" class="ed-h2">From Neighborhood Collection Box to Verified Prescription</h2>
          <p class="ed-lead">
            Six disciplined steps transform unsorted donations into ready-to-dispense, labeled 50-pair mission cartons graded to <span class="ed-mono">0.25</span> diopters.
          </p>
        </div>
        <a class="ed-btn ed-btn--secondary" href="/donate#refurbishment-process">
          <span>Diopter &amp; Sorting Details</span>
          <?= erc_icon('arrow-right') ?>
        </a>
      </div>

      <ol class="ed-process-grid">
        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">01</span>
            <h3 class="ed-h4">Collection</h3>
          </div>
          <p class="ed-prose">
            Gathered from 214 North Texas drop-off bins, Lions Clubs, corporate drives, walk-ins, and shipped boxes.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">02</span>
            <h3 class="ed-h4">Inspection &amp; Initial Sorting</h3>
          </div>
          <p class="ed-prose">
            Separated into non-prescription sunglasses, readers, nearsighted (minus), farsighted (plus), and multifocal/prism lenses.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">03</span>
            <h3 class="ed-h4">Washing, Sanitizing &amp; Drying</h3>
          </div>
          <p class="ed-prose">
            Frames and lenses are thoroughly washed, sanitized, and rack-dried by volunteer workteams before optical testing.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">04</span>
            <h3 class="ed-h4">Lensometry Measurement</h3>
          </div>
          <p class="ed-prose">
            Volunteers measure exact spherical/lens power on tabletop lensometers so field doctors receive accurate pairs.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">05</span>
            <h3 class="ed-h4">Quality Check &amp; 50-Pair Packing</h3>
          </div>
          <p class="ed-prose">
            Tagged, bagged, and boxed in standardized 50-pair cartons organized by <span class="ed-mono">0.25</span>-diopter increment.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">06</span>
            <h3 class="ed-h4">Humanitarian Mission Dispatch</h3>
          </div>
          <p class="ed-prose">
            Shipped free of hardware cost (mission covers freight) to nonprofit medical clinics in 40+ countries.
          </p>
        </li>
      </ol>

      <div class="ed-workshop-pair">
        <figure class="ed-media-frame">
          <img
            class="ed-media-frame__img"
            src="/gallery/drying-glasses.jpg"
            alt="Volunteers inspecting and air-drying freshly washed eyeglasses on workshop racks"
            loading="lazy"
            decoding="async"
          >
          <figcaption class="ed-media-frame__caption">
            <strong>Stage 03 · Wash &amp; Sanitize:</strong> Freshly cleaned eyewear drying on workshop racks prior to lensometry.
          </figcaption>
        </figure>

        <figure class="ed-media-frame">
          <div class="erc-rotated-frame erc-rotated-frame--landscape">
            <img
              class="is-ccw90-no-exif"
              src="/images/process-lensometers.jpg"
              alt="Volunteers operating optical lensometers at the Watauga workshop to measure lens prescriptions"
              width="1200"
              height="900"
              loading="lazy"
              decoding="async"
            >
          </div>
          <figcaption class="ed-media-frame__caption">
            <strong>Stage 04 · Lensometry:</strong> Determining exact diopter measurements so clinics can match patients rapidly.
          </figcaption>
        </figure>
      </div>
    </div>
  </section>

  <!-- Featured Verbatim Partner Quote -->
  <?php if (is_array($featuredQuote)): ?>
    <section class="ed-band ed-band--tint" aria-labelledby="home-quote-title">
      <div class="ed-wrap">
        <div class="ed-section-head">
          <div class="ed-section-head__copy">
            <span class="ed-kicker"><?= erc_icon('globe') ?> Voice from the Field</span>
            <h2 id="home-quote-title" class="ed-h2">Why Precision Sorting Matters at Partner Clinics</h2>
          </div>
          <a class="ed-btn ed-btn--secondary" href="/missions#clinic-stories">
            <span>All 4 Partner Testimonials</span>
            <?= erc_icon('arrow-right') ?>
          </a>
        </div>

        <figure class="ed-quote-card">
          <blockquote>
            &ldquo;<?= e((string)($featuredQuote['text'] ?? '')) ?>&rdquo;
          </blockquote>
          <figcaption>
            <span class="ed-quote-author"><?= e((string)($featuredQuote['author'] ?? '')) ?></span>
            <?php if (trim((string)($featuredQuote['organization'] ?? '')) !== ''): ?>
              <span class="ed-quote-org"><?= e((string)$featuredQuote['organization']) ?></span>
            <?php endif; ?>
          </figcaption>
        </figure>
      </div>
    </section>
  <?php endif; ?>

  <!-- Watauga HQ & P.O. Box 1854 Summary -->
  <section class="ed-band ed-band--surface ed-band--last" aria-labelledby="home-hq-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('building-2') ?> Center &amp; Mailing Addresses</span>
          <h2 id="home-hq-title" class="ed-h2">Visit Our Workshop or Support by Mail</h2>
        </div>
      </div>

      <div class="ed-grid ed-grid--2">
        <article class="ed-card">
          <div class="ed-card__icon-box">
            <?= erc_icon('map-pin') ?>
          </div>
          <h3 class="ed-h3">Watauga Processing Center</h3>
          <dl class="ed-dl">
            <div class="ed-dl__row">
              <dt>Physical Address</dt>
              <dd>5621 Bunker Blvd, Watauga, TX 76148</dd>
            </div>
            <div class="ed-dl__row">
              <dt>Entrance &amp; Parking</dt>
              <dd>Free on-site parking. Follow the wooden walkway along the east side of the building to the rear entrance.</dd>
            </div>
            <div class="ed-dl__row">
              <dt>Phone &amp; Email (24h Turnaround)</dt>
              <dd>
                <a class="ed-inline-link ed-mono" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>"><?= e($GLOBAL_PHONE) ?></a>
                &middot;
                <a class="ed-inline-link" href="mailto:<?= e($GLOBAL_EMAIL) ?>"><?= e($GLOBAL_EMAIL) ?></a>
              </dd>
            </div>
          </dl>
          <div class="ed-actions">
            <a class="ed-btn ed-btn--primary" href="/contact">Contact &amp; Forms</a>
            <a class="ed-btn ed-btn--secondary" href="/locations">214 Drop-Off Bins</a>
          </div>
        </article>

        <article class="ed-card ed-card--subtle">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('landmark') ?>
          </div>
          <h3 class="ed-h3">Mailing &amp; 501(c)(3) Check Donations</h3>
          <dl class="ed-dl">
            <div class="ed-dl__row">
              <dt>Mailing Address</dt>
              <dd>
                Lions District 2-E2 ERC<br>
                P.O. Box 1854<br>
                Keller, TX 76244
              </dd>
            </div>
            <div class="ed-dl__row">
              <dt>Nonprofit &amp; Financial Stewardship</dt>
              <dd>
                District 2-E2 Lions Eyeglass Recycling Center Foundation (501(c)(3) recognized 2014). 100% of financial contributions support mission shipping, processing supplies, and facility operations.
              </dd>
            </div>
          </dl>
          <div class="ed-actions">
            <a class="ed-btn ed-btn--gold" href="/donate#monetary-gifts">How to Donate by Check</a>
            <a class="ed-btn ed-btn--secondary" href="/about#recognition">Lions Awards</a>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
