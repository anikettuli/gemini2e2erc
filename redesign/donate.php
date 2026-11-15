<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'donate',
    'title'       => 'Donate Eyeglasses & Electronics | Lions District 2-E2 ERC',
    'description' => 'Accepted prescription glasses, sunglasses, hearing aids, cell phones, and Windows 11 devices; 0.25-diopter sorting process; bulk pickup, collection boxes, and 501(c)(3) check gifts.',
    'canonical'   => '/donate',
    'preload_img' => '',
    'og_image'    => '/images/our-story-group.png',
    'css'         => ['/css/pages/editorial.css'],
    'js_modules'  => ['/js/pages/editorial.js'],
    'json_ld'     => [],
    'noindex'     => false,
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main ed-page" tabindex="-1">
  <!-- Editorial Masthead -->
  <section class="ed-masthead" aria-labelledby="donate-title">
    <div class="ed-wrap">
      <div class="ed-masthead__grid ed-masthead__grid--split">
        <div>
          <span class="ed-kicker"><?= erc_icon('glasses') ?> Donate Eyewear, Tech &amp; Support</span>
          <h1 id="donate-title" class="ed-display">
            What We Accept—and How Every Pair Reaches a Patient
          </h1>
          <p class="ed-lead">
            From broken frames tucked inside a dresser drawer to qualifying Windows 11 laptops, hearing aids, and wiped phones, North Texas donations power free vision care across 40+ countries.
          </p>
        </div>

        <aside class="ed-masthead__aside" aria-label="Watauga Drop-Off Access Summary">
          <div class="ed-hours-badge" data-watauga-hours data-state="appointment">
            <?= erc_icon('clock') ?>
            <span data-watauga-hours-text>Saturdays 9:00 AM – 3:00 PM CT</span>
          </div>
          <h2 class="ed-h4">Watauga Drop-Off &amp; Access</h2>
          <p class="ed-prose">
            <strong>5621 Bunker Blvd, Watauga, TX 76148</strong><br>
            Free parking. Follow the wooden walkway along the <strong>east side</strong> of the building to the <strong>rear entrance</strong>.
          </p>
          <div class="ed-actions ed-mt-sm">
            <a class="ed-btn ed-btn--secondary" href="/locations">
              <?= erc_icon('map-pin') ?>
              <span>Find Nearest Bin (214)</span>
            </a>
          </div>
        </aside>
      </div>

      <nav aria-label="On this page">
        <ul class="ed-jump-nav">
          <li><a href="#accepted-items">Accepted Items</a></li>
          <li><a href="#not-accepted">Not Accepted</a></li>
          <li><a href="#refurbishment-process">6-Step Refurbishment</a></li>
          <li><a href="#bulk-and-boxes">Bulk, Shipping &amp; Boxes</a></li>
          <li><a href="#monetary-gifts">Check Donations</a></li>
        </ul>
      </nav>
    </div>
  </section>

  <!-- Accepted Items Checklist -->
  <section id="accepted-items" class="ed-band ed-band--subtle" aria-labelledby="accepted-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('check-circle-2') ?> Accepted Donations</span>
          <h2 id="accepted-title" class="ed-h2">Four Verified Donation Categories</h2>
          <p class="ed-lead">
            Bring qualifying items to our Watauga center during Saturday hours (<span class="ed-mono">9:00 AM – 3:00 PM</span>), schedule a weekday drop-off, or place eyewear in a neighborhood Lions collection box.
          </p>
        </div>
      </div>

      <div class="ed-grid ed-grid--2">
        <article class="ed-card ed-card--accept">
          <div class="ed-card__icon-box ed-card__icon-box--emerald">
            <?= erc_icon('glasses') ?>
          </div>
          <div class="ed-actions">
            <span class="ed-pill ed-pill--success">Any Condition Accepted</span>
          </div>
          <h3 class="ed-h3">Eyeglasses (Core Collection)</h3>
          <ul class="ed-list ed-list--accept">
            <li>
              <?= erc_icon('check-circle-2') ?>
              <span>All new and used <strong>prescription eyeglasses</strong>, <strong>reading glasses</strong>, and <strong>sunglasses</strong>.</span>
            </li>
            <li>
              <?= erc_icon('check-circle-2') ?>
              <span>Accepted in <strong>any condition</strong>—even <strong>broken</strong> glasses or frames missing parts (repaired or repurposed).</span>
            </li>
          </ul>
        </article>

        <article class="ed-card ed-card--accept">
          <div class="ed-card__icon-box ed-card__icon-box--emerald">
            <?= erc_icon('ear') ?>
          </div>
          <div class="ed-actions">
            <span class="ed-pill ed-pill--success">Working or Non-Working</span>
          </div>
          <h3 class="ed-h3">Hearing Aids</h3>
          <ul class="ed-list ed-list--accept">
            <li>
              <?= erc_icon('check-circle-2') ?>
              <span>Both <strong>digital and analog</strong> hearing aids are welcomed.</span>
            </li>
            <li>
              <?= erc_icon('check-circle-2') ?>
              <span><strong>Functional or non-functional</strong> units accepted—processed with specialized partners to reach recipients in need.</span>
            </li>
          </ul>
        </article>

        <article class="ed-card ed-card--accept">
          <div class="ed-card__icon-box ed-card__icon-box--emerald">
            <?= erc_icon('laptop') ?>
          </div>
          <div class="ed-actions">
            <span class="ed-pill ed-pill--tint">Windows 11 Only</span>
          </div>
          <h3 class="ed-h3">Laptops &amp; Tablets</h3>
          <ul class="ed-list ed-list--accept">
            <li>
              <?= erc_icon('check-circle-2') ?>
              <span>Accepted <strong>ONLY if Windows 11–ready or already running Windows 11</strong>.</span>
            </li>
            <li>
              <?= erc_icon('circle-x') ?>
              <span>Desktop computers, damaged screens, non-functional keyboards, and units missing parts cannot be accepted.</span>
            </li>
          </ul>
        </article>

        <article class="ed-card ed-card--accept">
          <div class="ed-card__icon-box ed-card__icon-box--emerald">
            <?= erc_icon('smartphone') ?>
          </div>
          <div class="ed-actions">
            <span class="ed-pill ed-pill--success">Completely Wiped</span>
          </div>
          <h3 class="ed-h3">Cell Phones</h3>
          <ul class="ed-list ed-list--accept">
            <li>
              <?= erc_icon('check-circle-2') ?>
              <span>Smartphones and basic mobile phones of <strong>any brand or model</strong>, in <strong>any condition</strong>.</span>
            </li>
            <li>
              <?= erc_icon('shield-check') ?>
              <span>All device data is <strong>completely wiped</strong> during processing.</span>
            </li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <!-- Explicitly NOT Accepted & Partner Bin Variance -->
  <section id="not-accepted" class="ed-band ed-band--surface" aria-labelledby="not-accepted-title">
    <div class="ed-wrap">
      <div class="ed-grid ed-grid--2">
        <article class="ed-card ed-card--reject">
          <div class="ed-card__icon-box ed-card__icon-box--danger">
            <?= erc_icon('ban') ?>
          </div>
          <h2 id="not-accepted-title" class="ed-h3">Explicitly Not Accepted</h2>
          <p class="ed-prose">
            To avoid disposal costs that take funds away from humanitarian shipping, please do not place these items in collection boxes or bring them to the center:
          </p>
          <ul class="ed-list ed-list--reject">
            <li>
              <?= erc_icon('circle-x') ?>
              <span><strong>Desktop computers</strong> (including towers and monitors)</span>
            </li>
            <li>
              <?= erc_icon('circle-x') ?>
              <span><strong>Damaged screens</strong></span>
            </li>
            <li>
              <?= erc_icon('circle-x') ?>
              <span><strong>Non-functional keyboards</strong></span>
            </li>
            <li>
              <?= erc_icon('circle-x') ?>
              <span><strong>Electronics or items with missing parts</strong></span>
            </li>
          </ul>
        </article>

        <div class="ed-card ed-card--subtle" role="region" aria-labelledby="bin-variance-title">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('triangle-alert') ?>
          </div>
          <h2 id="bin-variance-title" class="ed-h3">Partner Drop-Off Bin Variance Notice</h2>
          <p class="ed-prose">
            While our Watauga Processing Center accepts all four categories above, <strong>individual DFW drop-off bin locations may accept only eyeglasses or a subset of electronics</strong>.
          </p>
          <p class="ed-prose">
            Before bringing hearing aids, phones, or Windows 11 laptops/tablets to a local business bin, please check directly with that host location or call our center at <a class="ed-inline-link ed-mono" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>"><?= e($GLOBAL_PHONE) ?></a>.
          </p>
          <div class="ed-actions ed-actions--end">
            <a class="ed-btn ed-btn--primary" href="/locations">
              <?= erc_icon('map-pin') ?>
              <span>Browse All 214 Bins</span>
            </a>
            <a class="ed-btn ed-btn--secondary" href="/contact?subject=donation-items">
              <?= erc_icon('mail') ?>
              <span>Ask About an Item</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 6-Step Refurbishment Workflow & 50-Pair 0.25-Diopter Box System -->
  <section id="refurbishment-process" class="ed-band" aria-labelledby="refurbish-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('recycle') ?> Inside the Watauga Workshop</span>
          <h2 id="refurbish-title" class="ed-h2">6-Stage Refurbishment &amp; 0.25-Diopter Packaging</h2>
          <p class="ed-lead">
            Every usable pair costs less than <span class="ed-mono">$0.10 US (10&cent;)</span> to process and is graded, verified, and boxed for immediate field clinic dispensing.
          </p>
        </div>
      </div>

      <ol class="ed-process-grid">
        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">01</span>
            <h3 class="ed-h4">Collection</h3>
          </div>
          <p class="ed-prose">
            Gather donated eyewear and qualifying items from 214 listed DFW drop-off boxes, Lions Clubs, corporate drives, walk-ins, and mailed packages.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">02</span>
            <h3 class="ed-h4">Inspection &amp; Initial Sorting</h3>
          </div>
          <p class="ed-prose">
            Assess frame integrity and sort into five optical pathways: non-prescription/sunglasses, reading glasses, nearsighted (minus/concave), farsighted (plus/convex), and bifocal, trifocal, progressive, and prism lenses.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">03</span>
            <h3 class="ed-h4">Washing, Sanitizing &amp; Drying</h3>
          </div>
          <p class="ed-prose">
            Frames and lenses are thoroughly washed, sanitized, and air-dried on workshop racks before precision optical verification.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">04</span>
            <h3 class="ed-h4">Prescription Measurement (Lensometry)</h3>
          </div>
          <p class="ed-prose">
            Trained volunteers operate tabletop lensometers to measure exact prescription powers across each lens.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">05</span>
            <h3 class="ed-h4">Final Quality Check, Tagging &amp; Storage</h3>
          </div>
          <p class="ed-prose">
            Final quality inspection, individual bagging/labeling, and packing into standardized <strong>50-pair cartons</strong> organized by <span class="ed-mono">0.25</span>-diopter increment.
          </p>
        </li>

        <li class="ed-process-step">
          <div class="ed-step-head">
            <span class="ed-step-num" aria-hidden="true">06</span>
            <h3 class="ed-h4">Mission Dispatch</h3>
          </div>
          <p class="ed-prose">
            Fulfill verified mission orders and ship free of hardware cost (mission covers shipping expenses) to nonprofit clinics worldwide.
          </p>
        </li>
      </ol>

      <!-- Standardized 50-Pair 0.25-Diopter Box System -->
      <div class="ed-mt-xl">
        <h3 class="ed-h3">Standardized 50-Pair Diopter Box Specifications</h3>
        <p class="ed-prose">
          Sorted inventory is packed in uniform 50-pair cartons so partner optometrists, ophthalmologists, and clinic teams can pull exact diopters immediately upon arrival.
        </p>

        <div class="ed-diopter-grid">
          <div class="ed-diopter-box">
            <span class="ed-pill ed-pill--tint">50 pairs per box</span>
            <h4 class="ed-h4">Single Vision Plus (+)</h4>
            <span class="ed-diopter-range">+0.25 to +5.00+</span>
            <p class="ed-muted">Farsighted (convex) single-vision prescriptions packed in 0.25-diopter steps.</p>
          </div>

          <div class="ed-diopter-box">
            <span class="ed-pill ed-pill--tint">50 pairs per box</span>
            <h4 class="ed-h4">Single Vision Minus (&minus;)</h4>
            <span class="ed-diopter-range">&minus;0.25 to &minus;5.00+</span>
            <p class="ed-muted">Nearsighted (concave) single-vision prescriptions packed in 0.25-diopter steps.</p>
          </div>

          <div class="ed-diopter-box">
            <span class="ed-pill ed-pill--tint">50 pairs per box</span>
            <h4 class="ed-h4">Reading Glasses (+)</h4>
            <span class="ed-diopter-range">+0.25 to +5.00+</span>
            <p class="ed-muted">Magnification readers available both to clinics and missions lacking eye-care specialists.</p>
          </div>

          <div class="ed-diopter-box ed-diopter-box--gold">
            <span class="ed-pill ed-pill--gold">50 pairs per box</span>
            <h4 class="ed-h4">Non-Prescription Sunglasses</h4>
            <span class="ed-diopter-range">Non-Rx UV Protection</span>
            <p class="ed-muted">Essential UV protection packed in dedicated 50-pair mission boxes.</p>
          </div>
        </div>
      </div>

      <div class="ed-workshop-pair">
        <figure class="ed-media-frame">
          <div class="erc-rotated-frame erc-rotated-frame--landscape">
            <img
              class="is-ccw90-no-exif"
              src="/images/process-lensometers.jpg"
              alt="Volunteers calibrating and reading eyeglass prescriptions on lensometers in Watauga"
              width="1200"
              height="900"
              loading="lazy"
              decoding="async"
            >
          </div>
          <figcaption class="ed-media-frame__caption">
            <strong>Lensometry Calibration:</strong> Every pair is checked on calibrated lensometers before entering a 50-pair carton.
          </figcaption>
        </figure>

        <figure class="ed-media-frame">
          <img
            class="ed-media-frame__img"
            src="/gallery/computers.jpg"
            alt="Volunteers sorting and testing qualifying technology donations alongside optical worktables"
            loading="lazy"
            decoding="async"
          >
          <figcaption class="ed-media-frame__caption">
            <strong>Tech &amp; Intake Area:</strong> Cell phones and Windows 11-ready laptops and tablets are checked and wiped separately from eyewear.
          </figcaption>
        </figure>
      </div>
    </div>
  </section>

  <!-- Bulk, Shipping & Host a Collection Box -->
  <section id="bulk-and-boxes" class="ed-band ed-band--subtle" aria-labelledby="bulk-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('package') ?> Drives, Bulk Shipments &amp; Store Hosts</span>
          <h2 id="bulk-title" class="ed-h2">Coordinate Bulk Donations or Host a Collection Box</h2>
        </div>
      </div>

      <div class="ed-grid ed-grid--3">
        <article class="ed-card">
          <div class="ed-card__icon-box">
            <?= erc_icon('truck') ?>
          </div>
          <h3 class="ed-h3">Bulk &amp; Business Donations</h3>
          <p class="ed-prose">
            Optical practices, corporate offices, civic groups, and large organizations with bulk donations are warmly welcomed. Call us to coordinate direct delivery or pickup.
          </p>
          <div class="ed-actions ed-actions--end">
            <a class="ed-btn ed-btn--primary" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>">
              <?= erc_icon('phone') ?>
              <span class="ed-mono"><?= e($GLOBAL_PHONE) ?></span>
            </a>
          </div>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box">
            <?= erc_icon('mail') ?>
          </div>
          <h3 class="ed-h3">Shipping Large Quantities</h3>
          <p class="ed-prose">
            Planning to ship boxed eyewear or large quantities from outside North Texas? Email us <strong>before shipping</strong> so our volunteers can provide exact shipping instructions or arrange pickup.
          </p>
          <div class="ed-actions ed-actions--end">
            <a class="ed-btn ed-btn--secondary" href="mailto:<?= e($GLOBAL_EMAIL) ?>">
              <?= erc_icon('mail') ?>
              <span><?= e($GLOBAL_EMAIL) ?></span>
            </a>
          </div>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('building-2') ?>
          </div>
          <h3 class="ed-h3">Host a Collection Box</h3>
          <p class="ed-prose">
            Join North Texas community hosts—including retail partners such as <strong>Westlake Ace Hardware</strong> and <strong>MarketStreet</strong>—by placing an official Lions collection box at your business or organization.
          </p>
          <div class="ed-actions ed-actions--end">
            <a class="ed-btn ed-btn--gold" href="/contact?subject=need-collection-boxes">
              <?= erc_icon('package') ?>
              <span>Request Collection Boxes</span>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- Tax-Deductible 501(c)(3) Monetary Check Donations -->
  <section id="monetary-gifts" class="ed-band ed-band--tint ed-band--last" aria-labelledby="monetary-title">
    <div class="ed-wrap">
      <div class="ed-grid ed-grid--2">
        <div>
          <span class="ed-kicker"><?= erc_icon('dollar-sign') ?> 501(c)(3) Financial Stewardship</span>
          <h2 id="monetary-title" class="ed-h2">Monetary Check Donations</h2>
          <p class="ed-lead">
            <strong>100%</strong> of financial contributions go directly toward mission shipping, optical processing supplies, and facility operations—our center has zero paid staff.
          </p>
          <p class="ed-prose">
            Because it takes less than <span class="ed-mono">$0.10 US</span> to clean, measure, and prepare each pair, even modest financial gifts help place clear vision into hundreds of hands.
          </p>
          <p class="ed-prose">
            The <strong>District 2-E2 Lions Eyeglass Recycling Center Foundation</strong> is an officially recognized <span class="ed-mono">501(c)(3)</span> tax-exempt nonprofit (<span class="ed-mono">2014</span>); donations may be tax-deductible (donors are advised to verify with their tax preparer).
          </p>
        </div>

        <article class="ed-card">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('landmark') ?>
          </div>
          <h3 class="ed-h3">Make Checks Payable &amp; Mail To</h3>
          <dl class="ed-dl">
            <div class="ed-dl__row">
              <dt>Payee &amp; Mailing Address</dt>
              <dd>
                Lions District 2-E2 ERC<br>
                P.O. Box 1854<br>
                Keller, TX 76244
              </dd>
            </div>
            <div class="ed-dl__row">
              <dt>Lions Club &amp; Individual Honors</dt>
              <dd>
                Contributions can also fund <strong>Helen Keller Award</strong> (&ldquo;Knights of Sight&rdquo;: <span class="ed-mono">$250</span> / <span class="ed-mono">$500</span> / <span class="ed-mono">$1,000</span>) or <strong>ERC Life Membership</strong> recognitions.
              </dd>
            </div>
          </dl>
          <div class="ed-actions">
            <a class="ed-btn ed-btn--primary" href="/contact?subject=donation-money">
              <?= erc_icon('mail') ?>
              <span>Monetary Gift Inquiry</span>
            </a>
            <a class="ed-btn ed-btn--secondary" href="/about#recognition">
              <?= erc_icon('arrow-right') ?>
              <span>Explore Lions Awards</span>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
