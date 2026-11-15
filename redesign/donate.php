<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'donate',
    'title'       => 'Donate Eyeglasses & Electronics | Lions District 2-E2 ERC',
    'description' => 'Accepted prescription glasses, reading glasses, sunglasses, broken frames, hearing aids, wiped cell phones, and Windows 11 laptops/tablets; interactive intake inspection queue; bulk pickup and 501(c)(3) check gifts.',
    'canonical'   => '/donate',
    'preload_img' => '',
    'og_image'    => erc_media_url('images/our-story-group.png'),
    'css'         => ['/css/pages/editorial.css'],
    'js_modules'  => ['/js/pages/editorial.js'],
    'json_ld'     => [],
    'noindex'     => false,
];

$acceptedTickets = [
    [
        'serial' => 'NO. 01 · RX GLASSES',
        'tag'    => 'ANY CONDITION',
        'title'  => 'PRESCRIPTION GLASSES',
        'meta'   => 'CORE COLLECTION · SINGLE & MULTIFOCAL',
        'body'   => 'New and used single-vision (plus/minus), bifocal, trifocal, progressive, and prism eyeglasses.',
    ],
    [
        'serial' => 'NO. 02 · READERS',
        'tag'    => '+0.25 TO +5.00+',
        'title'  => 'READING GLASSES',
        'meta'   => 'CORE COLLECTION · MAGNIFICATION',
        'body'   => 'Over-the-counter and prescription reading glasses in any condition for clinics and outreach missions.',
    ],
    [
        'serial' => 'NO. 03 · SUNGLASSES',
        'tag'    => 'RX OR NON-RX',
        'title'  => 'SUNGLASSES',
        'meta'   => 'CORE COLLECTION · UV PROTECTION',
        'body'   => 'Prescription and non-prescription sunglasses in any condition, packed in dedicated 50-pair mission boxes.',
    ],
    [
        'serial' => 'NO. 04 · BROKEN FRAMES',
        'tag'    => 'REPAIRED / REPURPOSED',
        'title'  => 'BROKEN GLASSES & FRAMES',
        'meta'   => 'CORE COLLECTION · SALVAGE & PARTS',
        'body'   => 'Damaged eyeglasses or frames missing screws, nose pads, or temples are repaired or repurposed by volunteers.',
    ],
    [
        'serial' => 'NO. 05 · HEARING AIDS',
        'tag'    => 'WORKING OR NOT',
        'title'  => 'HEARING AIDS',
        'meta'   => 'DIGITAL OR ANALOG · PARTNER PROCESSED',
        'body'   => 'Both digital and analog hearing aids—functional or non-functional—processed with specialized partners.',
    ],
    [
        'serial' => 'NO. 06 · CELL PHONES',
        'tag'    => 'DATA WIPED',
        'title'  => 'CELL PHONES',
        'meta'   => 'ANY BRAND OR MODEL · ANY CONDITION',
        'body'   => 'Smartphones and basic mobile phones in any condition. All device data is completely wiped during intake.',
    ],
    [
        'serial' => 'NO. 07 · WINDOWS 11',
        'tag'    => 'WIN 11 ONLY',
        'title'  => 'LAPTOPS & TABLETS',
        'meta'   => 'WINDOWS 11-READY OR INSTALLED',
        'body'   => 'Accepted ONLY if Windows 11-ready or already running Windows 11. Desktops and broken hardware are excluded.',
    ],
];

$queueItems = [
    [
        'id'      => 'rx-glasses',
        'code'    => 'IN-01',
        'name'    => 'Prescription Eyeglasses',
        'detail'  => 'Single-vision, bifocal, trifocal, progressive, or prism lenses (new or used).',
        'verdict' => 'accepted',
        'stamp'   => 'ACCEPTED',
        'policy'  => 'Neutralized to 0.25D and packed in 50-pair mission boxes.',
    ],
    [
        'id'      => 'reading-glasses',
        'code'    => 'IN-02',
        'name'    => 'Reading Glasses (+)',
        'detail'  => 'Magnification readers (+0.25 to +5.00+), any condition.',
        'verdict' => 'accepted',
        'stamp'   => 'ACCEPTED',
        'policy'  => 'Supplied to clinics and missions lacking eye-care specialists.',
    ],
    [
        'id'      => 'sunglasses',
        'code'    => 'IN-03',
        'name'    => 'Sunglasses (Rx or Non-Rx)',
        'detail'  => 'Tinted prescription or non-prescription sunglasses.',
        'verdict' => 'accepted',
        'stamp'   => 'ACCEPTED',
        'policy'  => 'Cleaned, inspected, and boxed in 50-pair UV mission cartons.',
    ],
    [
        'id'      => 'broken-frames',
        'code'    => 'IN-04',
        'name'    => 'Broken Glasses or Missing Frame Parts',
        'detail'  => 'Bent temples, snapped hinges, scratched lenses, or partial frames.',
        'verdict' => 'recycled',
        'stamp'   => 'RECYCLED',
        'policy'  => 'Accepted in any condition — repaired or repurposed by volunteers.',
    ],
    [
        'id'      => 'hearing-aids',
        'code'    => 'IN-05',
        'name'    => 'Digital or Analog Hearing Aids',
        'detail'  => 'Functional or non-functional units (verify host bin if not dropping at Watauga HQ).',
        'verdict' => 'accepted',
        'stamp'   => 'ACCEPTED',
        'policy'  => 'Processed with specialized partners to reach recipients in need.',
    ],
    [
        'id'      => 'cell-phones',
        'code'    => 'IN-06',
        'name'    => 'Cell Phones (Smartphones & Basic)',
        'detail'  => 'Any brand or model, any condition (verify host bin if not at Watauga HQ).',
        'verdict' => 'accepted',
        'stamp'   => 'ACCEPTED',
        'policy'  => 'All device data is completely wiped during processing.',
    ],
    [
        'id'      => 'win11-laptops',
        'code'    => 'IN-07',
        'name'    => 'Windows 11 Laptops & Tablets',
        'detail'  => 'Must be Windows 11-ready or already running Windows 11 with intact screen & keyboard.',
        'verdict' => 'accepted',
        'stamp'   => 'ACCEPTED',
        'policy'  => 'Accepted ONLY when Windows 11-ready/installed and complete.',
    ],
    [
        'id'      => 'desktops',
        'code'    => 'IN-08',
        'name'    => 'Desktop Computers (Towers & Monitors)',
        'detail'  => 'Full-size desktop PCs, CRT/LCD monitors, or workstation towers.',
        'verdict' => 'skip',
        'stamp'   => 'SKIP',
        'policy'  => 'Explicitly NOT accepted at the center or drop-off bins.',
    ],
    [
        'id'      => 'damaged-tech',
        'code'    => 'IN-09',
        'name'    => 'Damaged Screens, Broken Keyboards & Missing Parts',
        'detail'  => 'Cracked laptop screens, non-functional keyboards, or incomplete electronics.',
        'verdict' => 'skip',
        'stamp'   => 'SKIP',
        'policy'  => 'Explicitly NOT accepted — please keep out of Lions collection boxes.',
    ],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main v2-page" tabindex="-1">
  <!-- Monumental Poster Masthead + Watauga HQ Drop-Off Spotlight -->
  <section class="v2-donate-hero" aria-labelledby="donate-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>DONATION INTAKE &amp; ELIGIBILITY</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>501(C)(3) TAX-EXEMPT NONPROFIT</span>
      </div>

      <div class="v2-donate-hero__grid">
        <div class="v2-donate-hero__copy">
          <h1 id="donate-title" class="v2-display v2-display--hero">
            <span>WHAT GOES</span>
            <span><span class="v2-knockout">IN</span> THE <span class="v2-nowrap">BIN<span class="v2-dot" aria-hidden="true"></span></span></span>
          </h1>

          <p class="lead">
            Every pair of prescription glasses, readers, sunglasses, hearing aids, wiped phones, and Windows 11 laptops/tablets powers free vision care across 40+ countries for under <span class="mono">$0.10 US</span> per pair.
          </p>

          <nav aria-label="On this page">
            <ul class="v2-jump-pills">
              <li><a class="btn btn--secondary btn--sm" href="#accepted-tickets">Accepted Tickets</a></li>
              <li><a class="btn btn--primary btn--sm" href="#inspection-queue">Inspection Queue</a></li>
              <li><a class="btn btn--secondary btn--sm" href="#refurbishment-process">0.25D Boxes</a></li>
              <li><a class="btn btn--secondary btn--sm" href="#mail-checklist">Mail-In &amp; Bulk</a></li>
              <li><a class="btn btn--secondary btn--sm" href="#monetary-gifts">Check Donations</a></li>
            </ul>
          </nav>
        </div>

        <!-- Watauga HQ East-Side Walkway Spotlight -->
        <aside class="v2-hq-spotlight" aria-label="Watauga Headquarters Drop-Off Spotlight">
          <div class="v2-hq-spotlight__top">
            <span class="v2-telemetry" data-watauga-hours data-state="appointment">
              <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
              <span data-watauga-hours-text>SATURDAYS 9:00 AM – 3:00 PM CT · MON–FRI BY APPT</span>
            </span>
          </div>

          <p class="v2-note v2-hq-spotlight__note">
            <span>east-side wooden walkway &rarr; rear door</span>
            <?= erc_curved_arrow('down-left') ?>
          </p>

          <h2 class="v2-display v2-display--sm">WATAUGA HQ DROP-OFF</h2>
          <p class="v2-hq-spotlight__addr mono">5621 BUNKER BLVD, WATAUGA, TX 76148</p>
          <p class="v2-hq-spotlight__desc">
            Free on-site parking. Follow the wooden walkway along the <strong>east side</strong> of the building to reach the <strong>rear entrance</strong>. Open most Saturdays <span class="mono">9:00 AM – 3:00 PM</span>; weekdays by appointment; closed Sundays.
          </p>

          <div class="v2-hq-spotlight__actions">
            <a class="btn btn--primary" href="/locations">
              <?= erc_icon('map-pin', ['width' => '16', 'height' => '16']) ?>
              <span>FIND NEAREST BIN (214)</span>
            </a>
            <a class="btn btn--secondary" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>">
              <?= erc_icon('phone', ['width' => '16', 'height' => '16']) ?>
              <span><?= e($GLOBAL_PHONE) ?></span>
            </a>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <!-- Perforated Ticket Stubs for Accepted Items -->
  <section id="accepted-tickets" class="section section--subtle" aria-labelledby="accepted-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>ACCEPTED INVENTORY STUBS</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>7 VERIFIED ITEM CATEGORIES</span>
      </div>

      <div class="v2-section-intro">
        <h2 id="accepted-title" class="v2-display v2-display--lg">
          ACCEPTED <span class="v2-nowrap"><span class="v2-accent-text">STUBS</span><span class="v2-dot" aria-hidden="true"></span></span>
        </h2>
        <p class="lead">
          Watauga HQ accepts all categories below. Note: individual North Texas partner bins may accept only eyeglasses or a subset of electronics—verify with the host or call <a class="v2-inline-link mono" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>"><?= e($GLOBAL_PHONE) ?></a>.
        </p>
      </div>

      <div class="v2-tickets-grid">
        <?php foreach ($acceptedTickets as $ticket): ?>
          <article class="v2-ticket">
            <div class="v2-ticket__main">
              <div class="v2-ticket__meta">
                <span><?= e($ticket['meta']) ?></span>
              </div>
              <h3 class="v2-ticket__title"><?= e($ticket['title']) ?></h3>
              <p class="v2-ticket__desc"><?= e($ticket['body']) ?></p>
            </div>
            <div class="v2-ticket__stub">
              <span class="v2-ticket__serial"><?= e($ticket['serial']) ?></span>
              <span class="badge badge--brand"><?= e($ticket['tag']) ?></span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="callout callout--warn v2-variance-callout" role="region" aria-labelledby="bin-variance-title">
        <?= erc_icon('triangle-alert', ['class' => 'callout__icon', 'width' => '20', 'height' => '20']) ?>
        <div class="callout__body">
          <h3 id="bin-variance-title" class="callout__title">Partner Drop-Off Bin Variance Notice</h3>
          <p>
            Individual DFW drop-off locations across our 214 bins may accept only eyeglasses or a subset of electronics. Verify accepted items with the host location before visiting, or bring mixed eyewear and qualifying tech directly to Watauga HQ.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contrast Stage Sheet: Interactive "WHAT GOES IN THE BIN?" Inspection Stamp Queue -->
  <section id="inspection-queue" class="section" aria-labelledby="queue-title">
    <div class="v2-sheet">
      <div class="v2-sheet__inner" data-inspection-queue>
        <div class="v2-eyebrow">
          <span>&mdash; WHAT GOES IN THE BIN?</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
          <span>INTERACTIVE VOLUNTEER INTAKE DESK</span>
        </div>

        <div class="v2-sheet-head">
          <div>
            <h2 id="queue-title" class="v2-display v2-display--lg">
              <span>DROP-OFF</span>
              <span><span class="v2-nowrap"><span class="v2-accent-text">INSPECTION</span><span class="v2-dot" aria-hidden="true"></span></span></span>
            </h2>
          </div>

          <div class="v2-sheet-head__note">
            <p class="lead">
              Every intake item gets one clear disposition: accepted for refurbishment, recycled for parts, or skipped before packing.
            </p>
            <p class="v2-note">
              <span>you're the inspector</span>
              <?= erc_curved_arrow('down-left') ?>
            </p>
          </div>
        </div>

        <hr class="v2-queue-rule">

        <!-- Live Queue Bar + Stamp All / Undo Control -->
        <div class="v2-queue-toolbar">
          <div class="v2-queue-toolbar__left">
            <span class="v2-telemetry">
              <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
              <span>INTAKE QUEUE · LIVE</span>
            </span>
            <p class="v2-queue-sub" data-queue-sub>
              9 common items. 1 volunteer inspector. Click any item to stamp or stamp all at once.
            </p>
          </div>

          <div class="v2-queue-toolbar__right">
            <p class="v2-note v2-queue-stamp-note" data-stamp-note>
              <span>stamp the whole bin</span>
              <?= erc_curved_arrow('up-right') ?>
            </p>
            <button
              type="button"
              class="btn btn--primary v2-stamp-all-btn"
              data-stamp-all
            >
              <?= erc_icon('check-circle-2', ['width' => '18', 'height' => '18']) ?>
              <span data-stamp-all-label>STAMP ALL</span>
            </button>
            <button
              type="button"
              class="btn btn--secondary v2-undo-btn"
              data-stamp-undo
              hidden
            >
              <?= erc_icon('refresh-cw', ['width' => '17', 'height' => '17']) ?>
              <span>UNDO</span>
            </button>
          </div>
        </div>

        <!-- 3-Column Live Counter Strip -->
        <div class="v2-queue-counters" aria-live="polite">
          <div class="v2-queue-counter v2-queue-counter--accept">
            <span class="v2-queue-counter__num" data-count-accepted>00</span>
            <span class="v2-queue-counter__label mono">ACCEPTED</span>
          </div>
          <div class="v2-queue-counter v2-queue-counter--recycle">
            <span class="v2-queue-counter__num" data-count-recycled>00</span>
            <span class="v2-queue-counter__label mono">RECYCLED</span>
          </div>
          <div class="v2-queue-counter v2-queue-counter--skip">
            <span class="v2-queue-counter__num" data-count-skip>00</span>
            <span class="v2-queue-counter__label mono">SKIP</span>
          </div>
        </div>

        <!-- Playful Rubber-Stamp Complete Banner -->
        <div class="v2-complete-banner" data-complete-banner hidden role="status" aria-live="polite">
          <div class="v2-complete-banner__body">
            <span class="v2-complete-banner__tag mono">
              <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
              INSPECTION COMPLETE
            </span>
            <p class="v2-complete-banner__text" data-complete-text>
              All 9 items triaged: 6 accepted, 1 repaired/repurposed, and 2 skipped. Your donation bag is Watauga-ready.
            </p>
          </div>
          <button type="button" class="btn btn--secondary btn--sm" data-complete-undo>
            <span>UNDO ALL</span>
          </button>
        </div>

        <!-- Interactive Item Inspection Rows -->
        <ul class="v2-queue-list" data-queue-list>
          <?php foreach ($queueItems as $item): ?>
            <li
              class="v2-queue-row"
              data-queue-item
              data-verdict="<?= e($item['verdict']) ?>"
              data-stamped="false"
            >
              <div class="v2-queue-row__info">
                <div class="v2-queue-row__top">
                  <span class="v2-queue-row__code mono"><?= e($item['code']) ?></span>
                  <h3 class="v2-queue-row__title"><?= e($item['name']) ?></h3>
                </div>
                <p class="v2-queue-row__detail"><?= e($item['detail']) ?></p>
                <p class="v2-queue-row__policy" data-row-policy hidden>
                  <strong>Intake rule:</strong> <?= e($item['policy']) ?>
                </p>
              </div>

              <div class="v2-queue-row__actions">
                <span class="v2-rubber-stamp v2-rubber-stamp--<?= e($item['verdict']) ?>" data-row-stamp aria-hidden="true">
                  <?= e($item['stamp']) ?>
                </span>
                <button
                  type="button"
                  class="btn btn--secondary btn--sm v2-row-inspect-btn"
                  data-row-toggle
                  aria-pressed="false"
                >
                  <span>INSPECT &amp; STAMP</span>
                </button>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>

  <!-- 50-Pair 0.25-Diopter Box System + Rotated Lensometer & Tech Media -->
  <section id="refurbishment-process" class="section section--subtle" aria-labelledby="diopter-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>PACKAGING &amp; CALIBRATION</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>0.25-DIOPTER INVENTORY STANDARD</span>
      </div>

      <h2 id="diopter-title" class="v2-display v2-display--md">
        STANDARDIZED <span class="v2-accent-text">50-PAIR</span> DIOPTER <span class="v2-nowrap">BOXES<span class="v2-dot" aria-hidden="true"></span></span>
      </h2>
      <p class="lead">
        Sorted donations cost less than <span class="mono">$0.10 US (10&cent;)</span> per pair to process and are packed into uniform 50-pair cartons so partner field clinicians can pull exact prescriptions immediately.
      </p>

      <div class="v2-diopter-grid">
        <article class="v2-diopter-card">
          <span class="badge badge--brand">50 PAIRS / BOX</span>
          <h3 class="v2-diopter-card__title">SINGLE VISION PLUS (+)</h3>
          <p class="v2-diopter-card__range mono">+0.25 TO +5.00+</p>
          <p>Farsighted (convex) single-vision prescriptions sorted by 0.25-diopter step.</p>
        </article>

        <article class="v2-diopter-card">
          <span class="badge badge--brand">50 PAIRS / BOX</span>
          <h3 class="v2-diopter-card__title">SINGLE VISION MINUS (&minus;)</h3>
          <p class="v2-diopter-card__range mono">&minus;0.25 TO &minus;5.00+</p>
          <p>Nearsighted (concave) single-vision prescriptions sorted by 0.25-diopter step.</p>
        </article>

        <article class="v2-diopter-card">
          <span class="badge badge--brand">50 PAIRS / BOX</span>
          <h3 class="v2-diopter-card__title">READING GLASSES (+)</h3>
          <p class="v2-diopter-card__range mono">+0.25 TO +5.00+</p>
          <p>Magnification readers available to clinics and missions lacking eye-care professionals.</p>
        </article>

        <article class="v2-diopter-card">
          <span class="badge badge--gold">50 PAIRS / BOX</span>
          <h3 class="v2-diopter-card__title">SUNGLASSES</h3>
          <p class="v2-diopter-card__range mono">NON-RX UV PROTECTION</p>
          <p>Protective outdoor sunglasses boxed in standardized 50-pair mission cartons.</p>
        </article>
      </div>

      <div class="v2-workshop-pair">
        <figure class="v2-media-card">
          <div class="erc-rotated-frame erc-rotated-frame--landscape">
            <img
              class="is-ccw90-no-exif"
              src="<?= e(erc_media_url('images/process-lensometers.jpg')) ?>"
              alt="Volunteers calibrating and reading eyeglass prescriptions on tabletop lensometers in Watauga"
              width="1200"
              height="900"
              loading="eager"
              decoding="async"
            >
          </div>
          <figcaption class="v2-media-card__caption">
            <strong class="mono">LENSOMETRY CALIBRATION:</strong> Every pair is checked on tabletop lensometers before entering a 50-pair carton.
          </figcaption>
        </figure>

        <figure class="v2-media-card">
          <img
            class="v2-media-card__img"
            src="<?= e(erc_media_url('gallery/computers.jpg')) ?>"
            alt="Volunteers sorting and testing qualifying technology donations alongside optical worktables"
            loading="eager"
            decoding="async"
          >
          <figcaption class="v2-media-card__caption">
            <strong class="mono">TECH INTAKE BENCH:</strong> Cell phones are completely wiped and Windows 11 laptops/tablets are verified separately from eyewear.
          </figcaption>
        </figure>
      </div>
    </div>
  </section>

  <!-- Mail-In Packaging Checklist, Bulk Pickup, Collection Boxes & Official PDF -->
  <section id="mail-checklist" class="section" aria-labelledby="mail-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>MAIL-IN, BULK &amp; HOST BOXES</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>DOCUMENTATION &amp; FORMS</span>
      </div>

      <h2 id="mail-title" class="v2-display v2-display--md">
        SHIPPING, BULK &amp; <span class="v2-nowrap"><span class="v2-accent-text">BROCHURE</span><span class="v2-dot" aria-hidden="true"></span></span>
      </h2>

      <div class="v2-mail-grid">
        <!-- Mail-In Packaging Checklist -->
        <article class="card v2-checklist-card">
          <div class="v2-eyebrow">
            <span>MAIL-IN PACKAGING CHECKLIST</span>
          </div>
          <h3 class="v2-checklist-card__title">SHIPPING LARGE QUANTITIES</h3>
          <ul class="v2-checklist">
            <li>
              <?= erc_icon('check-circle-2', ['width' => '18', 'height' => '18']) ?>
              <span><strong>Email before shipping:</strong> Contact <a class="v2-inline-link" href="mailto:<?= e($GLOBAL_EMAIL) ?>"><?= e($GLOBAL_EMAIL) ?></a> prior to mailing large quantities so volunteers can send shipping instructions or arrange pickup.</span>
            </li>
            <li>
              <?= erc_icon('check-circle-2', ['width' => '18', 'height' => '18']) ?>
              <span><strong>Pack eyewear together:</strong> Prescription glasses, readers, sunglasses, and broken frames in any condition are welcomed—cushion loosely to protect reusable frames in transit.</span>
            </li>
            <li>
              <?= erc_icon('check-circle-2', ['width' => '18', 'height' => '18']) ?>
              <span><strong>Screen tech strictly:</strong> Pack only Windows 11-ready/installed laptops or tablets and phones—omit desktops, damaged screens, broken keyboards, and items missing parts.</span>
            </li>
            <li>
              <?= erc_icon('check-circle-2', ['width' => '18', 'height' => '18']) ?>
              <span><strong>Bulk local pickup:</strong> Optical practices, businesses, and civic drives in North Texas may call <a class="v2-inline-link mono" href="tel:<?= e($GLOBAL_PHONE_LINK) ?>"><?= e($GLOBAL_PHONE) ?></a> to coordinate delivery or pickup.</span>
            </li>
          </ul>
        </article>

        <!-- Official Brochure PDF & Host Collection Boxes -->
        <article class="card card--tint v2-docs-card">
          <div class="v2-eyebrow">
            <span>OFFICIAL DOWNLOAD &amp; BOX HOSTS</span>
          </div>
          <h3 class="v2-checklist-card__title">BROCHURE PDF &amp; HOST BOXES</h3>
          <p>
            Download our official 2-page organizational brochure covering accepted eyewear, 0.25-diopter sorting specifications, and 2013–2025 shipping impact—or request a collection box for your business like hosts <strong>Westlake Ace Hardware</strong> and <strong>MarketStreet</strong>.
          </p>

          <div class="v2-docs-card__actions">
            <a
              class="btn btn--primary"
              href="<?= e(erc_media_url('files/ERC-Brochure.pdf')) ?>"
              download
            >
              <?= erc_icon('download', ['width' => '17', 'height' => '17']) ?>
              <span>ERC BROCHURE &amp; SORTING GUIDE (PDF)</span>
            </a>

            <a class="btn btn--secondary" href="/contact?subject=donation-items">
              <?= erc_icon('mail', ['width' => '17', 'height' => '17']) ?>
              <span>DONATION / IN-KIND INQUIRY</span>
            </a>

            <a class="btn btn--secondary" href="/contact?subject=need-collection-boxes">
              <?= erc_icon('package', ['width' => '17', 'height' => '17']) ?>
              <span>REQUEST HOST COLLECTION BOXES</span>
            </a>

            <a class="btn btn--ghost" href="/contact#forms-center">
              <?= erc_icon('file-text', ['width' => '17', 'height' => '17']) ?>
              <span>ALL 6 OFFICIAL PDF FORMS</span>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- 501(c)(3) Monetary Check Donations -->
  <section id="monetary-gifts" class="section section--surface" aria-labelledby="monetary-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>501(C)(3) FINANCIAL STEWARDSHIP</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>ZERO PAID STAFF · RECOGNIZED 2014</span>
      </div>

      <div class="v2-monetary-grid">
        <div>
          <h2 id="monetary-title" class="v2-display v2-display--md">
            MONETARY <span class="v2-accent-text">CHECK</span> <span class="v2-nowrap">DONATIONS<span class="v2-dot" aria-hidden="true"></span></span>
          </h2>
          <p class="lead">
            <strong>100%</strong> of financial contributions support mission shipping, processing supplies, and facility operations—at under <span class="mono">$0.10 US</span> per processed pair.
          </p>
          <p>
            The <strong>District 2-E2 Lions Eyeglass Recycling Center Foundation</strong> is an officially recognized <span class="mono">501(c)(3)</span> tax-exempt nonprofit (<span class="mono">2014</span>). Donations may be tax-deductible; donors are advised to verify with their tax preparer.
          </p>
        </div>

        <article class="card">
          <span class="badge badge--gold">MAKE CHECKS PAYABLE TO</span>
          <h3 class="v2-checklist-card__title">LIONS DISTRICT 2-E2 ERC</h3>
          <p class="mono v2-po-box">
            Lions District 2-E2 ERC<br>
            P.O. Box 1854<br>
            Keller, TX 76244
          </p>
          <p>
            Contributions can also fund <strong>Helen Keller Award</strong> (&ldquo;Knights of Sight&rdquo;: <span class="mono">$250 / $500 / $1,000</span>) or <strong>ERC Life Membership</strong> recognitions.
          </p>
          <div class="v2-docs-card__actions">
            <a class="btn btn--primary" href="/contact?subject=donation-money">
              <?= erc_icon('mail', ['width' => '16', 'height' => '16']) ?>
              <span>MONETARY GIFT INQUIRY</span>
            </a>
            <a class="btn btn--secondary" href="/about#recognition">
              <?= erc_icon('award', ['width' => '16', 'height' => '16']) ?>
              <span>LIONS RECOGNITION AWARDS</span>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
