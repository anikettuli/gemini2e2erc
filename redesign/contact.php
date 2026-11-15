<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$escape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$media = static function (string $relativePath): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($relativePath);
    }
    return '/' . ltrim($relativePath, '/');
};

$email = (string) ($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');
$phone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$phoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');

$subjectCatalog = [
    'volunteer' => [
        'label' => 'Volunteer (Individual)',
        'pill' => 'Volunteer (Solo)',
        'route' => 'VOLUNTEER DESK · SAT SHIFTS',
    ],
    'group-volunteer' => [
        'label' => 'Group Volunteer (up to 20)',
        'pill' => 'Group (Up to 20)',
        'route' => 'GROUP DESK · MAX 20 / SHIFT',
    ],
    'donation-items' => [
        'label' => 'Donate Items (Eyewear / Tech)',
        'pill' => 'Donate Items',
        'route' => 'INTAKE DESK · WATAUGA HQ',
    ],
    'donation-money' => [
        'label' => 'Donate Money / Sponsorship',
        'pill' => 'Donate Money',
        'route' => '501(C)(3) · PO BOX 1854',
    ],
    'need-collection-boxes' => [
        'label' => 'Request Collection Boxes',
        'pill' => 'Collection Boxes',
        'route' => 'BIN LOGISTICS · 34 CITIES',
    ],
    'partnership' => [
        'label' => 'Club / Community Partnership',
        'pill' => 'Partnership',
        'route' => 'DISTRICT 2-E2 · PARTNERSHIPS',
    ],
    'question' => [
        'label' => 'General Question',
        'pill' => 'General Question',
        'route' => 'DISTRICT 2-E2 · GENERAL DESK',
    ],
    'other' => [
        'label' => 'Other Inquiry',
        'pill' => 'Other',
        'route' => 'DISTRICT 2-E2 · COORDINATOR',
    ],
];

$subjectAliases = [
    'volunteer' => 'volunteer',
    'solo' => 'volunteer',
    'group' => 'group-volunteer',
    'groups' => 'group-volunteer',
    'group-volunteer' => 'group-volunteer',
    'donate' => 'donation-items',
    'items' => 'donation-items',
    'donation-items' => 'donation-items',
    'money' => 'donation-money',
    'donate-money' => 'donation-money',
    'donation-money' => 'donation-money',
    'bins' => 'need-collection-boxes',
    'boxes' => 'need-collection-boxes',
    'collection-boxes' => 'need-collection-boxes',
    'need-collection-boxes' => 'need-collection-boxes',
    'partnership' => 'partnership',
    'partner' => 'partnership',
    'mission' => 'partnership',
    'missions' => 'partnership',
    'awards' => 'partnership',
    'question' => 'question',
    'other' => 'other',
];

$requestedRaw = strtolower(trim((string) ($_GET['subject'] ?? $_GET['topic'] ?? '')));
$requestedSubject = $subjectAliases[$requestedRaw] ?? $requestedRaw;
$selectedSubject = array_key_exists($requestedSubject, $subjectCatalog) ? $requestedSubject : 'question';
$initialRouteLabel = $subjectCatalog[$selectedSubject]['route'];

$statusParam = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$reasonParam = isset($_GET['reason']) ? trim((string) $_GET['reason']) : '';

$prgBanner = null;
if ($statusParam === 'success') {
    $prgBanner = [
        'type' => 'success',
        'title' => 'Dispatch transmitted',
        'message' => 'Thank you for contacting the District 2-E2 Eyeglass Recycling Center. Our volunteer crew typically responds within 24 hours.',
    ];
} elseif ($statusParam === 'error') {
    $reasonMap = [
        'rate_limited' => 'Too many inquiries were submitted recently from your connection. Please wait a few minutes or call or email us directly.',
        'invalid_input' => 'Please verify your name, valid email address, and message, then try submitting again.',
        'send_failed' => 'We could not deliver your message right now due to a mail server issue. Please call or email us directly.',
        'mail_failed' => 'We could not deliver your message right now due to a mail server issue. Please call or email us directly.',
    ];
    $prgBanner = [
        'type' => 'danger',
        'title' => 'Dispatch not delivered',
        'message' => $reasonMap[$reasonParam] ?? 'Something went wrong while sending your message. Please try again or reach us by phone or email.',
    ];
}

$resolvePdfSize = static function (string $filename): string {
    $candidates = [__DIR__ . '/files/' . $filename];
    if (defined('ERC_ASSET_BASE_DIR')) {
        $candidates[] = rtrim((string) ERC_ASSET_BASE_DIR, '/\\') . '/files/' . $filename;
    }
    if (function_exists('erc_is_coexistence_mode') && erc_is_coexistence_mode()) {
        $candidates[] = dirname(__DIR__) . '/files/' . $filename;
    }

    foreach ($candidates as $path) {
        if (is_file($path)) {
            $bytes = (int) filesize($path);
            if ($bytes > 0) {
                $kb = max(1, (int) round($bytes / 1024));
                return $kb >= 1024 ? number_format($kb / 1024, 1) . ' MB' : $kb . ' KB';
            }
        }
    }
    return '';
};

$pdfForms = [
    [
        'serial' => 'DOC · 01',
        'file' => 'ERC-Brochure.pdf',
        'title' => 'ERC Program Brochure',
        'pages' => '2-page PDF',
        'category' => 'OVERVIEW',
        'description' => 'Tri-fold overview, 2013–2025 eyeglasses shipped table, 50-pair diopter sorting specs, and global mission summary.',
    ],
    [
        'serial' => 'DOC · 02',
        'file' => 'Group-Volunteer-Form.pdf',
        'title' => 'Group Volunteer Form',
        'pages' => '1-page PDF',
        'category' => 'VOLUNTEERING',
        'description' => 'Schedule group sorting shifts (up to 20 volunteers) for Saturday 9:00 AM–12:00 PM or 12:00 PM–3:00 PM windows.',
    ],
    [
        'serial' => 'DOC · 03',
        'file' => 'Mission-Request-Form.pdf',
        'title' => 'Mission Eyeglass Request Form',
        'pages' => '1-page PDF',
        'category' => 'MISSIONS',
        'description' => 'Order form for non-profit medical and humanitarian clinics (submit 30+ days ahead; 50-pair boxes, mission pays shipping).',
    ],
    [
        'serial' => 'DOC · 04',
        'file' => 'Helen-Keller-Award-Application.pdf',
        'title' => 'Helen Keller Award Application',
        'pages' => '1-page PDF',
        'category' => 'AWARDS',
        'description' => 'Knights of Sight recognition for volunteer service hours or sponsorship ($250 Benefactor, $500 Endowment, $1,000 Patron).',
    ],
    [
        'serial' => 'DOC · 05',
        'file' => 'ERC-Life-Membership-Application.pdf',
        'title' => 'ERC Life Membership Application',
        'pages' => '1-page PDF',
        'category' => 'MEMBERSHIP',
        'description' => 'Sponsor an individual Lion for District 2-E2 ERC Life Membership recognition.',
    ],
    [
        'serial' => 'DOC · 06',
        'file' => 'ERC-Bylaws.pdf',
        'title' => 'ERC Foundation Bylaws',
        'pages' => '10-page PDF',
        'category' => 'GOVERNANCE',
        'description' => 'Official 501(c)(3) corporate bylaws adopted March/April 2014 covering board terms, officers, and endowment rules.',
    ],
];

$faqItems = [
    [
        'id' => 'FAQ-0001',
        'tag' => 'ACCESS',
        'question' => 'How do I find the entrance when I arrive at the Watauga center?',
        'paragraphs' => [
            'Our center is located at 5621 Bunker Blvd, Watauga, TX 76148 with free on-site visitor parking.',
            'After parking, follow the wooden walkway along the east side of the building to reach the rear entrance.',
        ],
    ],
    [
        'id' => 'FAQ-0002',
        'tag' => 'HOURS',
        'question' => 'What are the operating hours and weekday drop-off options?',
        'paragraphs' => [
            'The Watauga center is open most Saturdays from 9:00 AM to 3:00 PM (with volunteer sessions from 9:00 AM – 12:00 PM and 12:00 PM – 3:00 PM) and closed Sundays.',
            'Monday through Friday drop-offs and group visits are available by appointment by calling ' . $phone . ' or emailing ' . $email . '. You can also drop items at 214 community bins across 34 North Texas cities.',
        ],
        'link' => ['href' => '/locations', 'label' => 'Browse 214 Drop-Off Bins'],
    ],
    [
        'id' => 'FAQ-0003',
        'tag' => 'ELIGIBILITY',
        'question' => 'What items can be donated — and what cannot be accepted?',
        'paragraphs' => [
            'We accept prescription eyeglasses, reading glasses, and sunglasses in any condition (even broken frames); digital or analog hearing aids (working or non-working); cell phones of any brand or condition (data completely wiped); and Windows 11-ready or Windows 11-installed laptops and tablets.',
            'We cannot accept desktop computers, laptops or tablets that cannot run Windows 11, damaged screens, non-functional keyboards, or electronics with missing parts. Partner bin locations may accept eyeglasses only — check before visiting.',
        ],
        'link' => ['href' => '/donate', 'label' => 'Open Donation Eligibility Guide'],
    ],
    [
        'id' => 'FAQ-0004',
        'tag' => 'LOGISTICS',
        'question' => 'Are damaged eyeglasses, business bulk donations, or shipped boxes accepted?',
        'paragraphs' => [
            'Yes — eyeglasses in any condition, including broken pairs, can often be repaired or repurposed.',
            'Business and large organization donations are welcomed; call ' . $phone . ' to coordinate delivery or pickup. If shipping large quantities, email ' . $email . ' before shipping to arrange instructions.',
        ],
    ],
    [
        'id' => 'FAQ-0005',
        'tag' => 'VOLUNTEERING',
        'question' => 'How do individual walk-ins and group volunteer sessions work?',
        'paragraphs' => [
            'Individuals can walk in during Saturday hours (9:00 AM – 3:00 PM) or register online for open sessions such as 2nd Saturday Open Session. No prior experience is required — on-site training covers sorting, washing, lensometry, and packing.',
            'Groups of up to 20 volunteers can reserve Saturday morning (9:00 AM – 12:00 PM) or afternoon (12:00 PM – 3:00 PM) shifts by calling, emailing, or submitting Group-Volunteer-Form.pdf.',
        ],
        'link' => ['href' => '/volunteer', 'label' => 'View Volunteer Schedule'],
    ],
    [
        'id' => 'FAQ-0006',
        'tag' => 'MISSIONS',
        'question' => 'How do medical and humanitarian missions request recycled eyeglasses?',
        'paragraphs' => [
            'Qualified non-profit missions submit Mission-Request-Form.pdf to ' . $email . ' at least 30 days before their trip, along with proof of non-profit/tax-exempt status (Tax ID) and confirmation of how refractive error will be measured.',
            'Eyeglasses are supplied without cost in 50-pair boxes sorted by 0.25-diopter increments (+0.25 to +5.00+, -0.25 to -5.00+, reading glasses, and sunglasses). Glasses are strictly not for resale, and missions cover shipping expenses.',
        ],
        'link' => ['href' => '/missions', 'label' => 'Explore Missions & Requirements'],
    ],
    [
        'id' => 'FAQ-0007',
        'tag' => '501(C)(3)',
        'question' => 'Are donations tax-deductible, and where do I mail checks?',
        'paragraphs' => [
            'Yes — District 2-E2 Lions Eyeglass Recycling Center Foundation is a recognized 501(c)(3) tax-exempt nonprofit (2014). Donations may be tax-deductible; please verify with your tax preparer. 100% of financial gifts support mission shipping, processing supplies, and facility operations.',
            'Mail checks and written correspondence to: Lions District 2-E2 ERC, P.O. Box 1854, Keller, TX 76244.',
        ],
    ],
    [
        'id' => 'FAQ-0008',
        'tag' => 'IMPACT',
        'question' => 'How are donated eyeglasses used in humanitarian missions?',
        'paragraphs' => [
            'Volunteers inspect, wash, sanitize, measure exact prescriptions via lensometry, and pack usable eyewear for non-profit clinics serving 40+ countries — shipping 21,696 usable pairs in 2024–2025 at a processing cost of less than $0.10 US per pair.',
        ],
    ],
];

$pageMeta = [
    'slug' => 'contact',
    'title' => 'Open a Dispatch — Contact, PDFs & FAQ | Lions District 2-E2 ERC',
    'description' => 'Contact the Lions District 2-E2 Eyeglass Recycling Center in Watauga, TX. Open a categorized dispatch, view east-side walkway entrance directions, download all 6 official PDF forms, and review FAQs.',
    'canonical' => '/contact',
    'css' => ['/css/pages/contact.css'],
    'js_modules' => ['/js/pages/contact.js'],
    'json_ld' => [
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'Contact Lions District 2-E2 Eyeglass Recycling Center',
        'url' => 'https://2e2erc.org/contact',
        'mainEntity' => [
            '@type' => 'NGO',
            'name' => 'Lions District 2-E2 Eyeglass Recycling Center',
            'telephone' => $phoneLink,
            'email' => $email,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '5621 Bunker Blvd',
                'addressLocality' => 'Watauga',
                'addressRegion' => 'TX',
                'postalCode' => '76148',
                'addressCountry' => 'US',
            ],
        ],
    ],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
  <main id="main-content" class="site-main contact-main" tabindex="-1">
    <!-- =======================================================================
         1. CONTRAST STAGE: OPEN A DISPATCH + REQ-2E2 DARK CONSOLE
         ======================================================================= -->
    <section class="v2-sheet contact-stage" aria-labelledby="contact-hero-heading">
      <div class="v2-sheet__inner">
        <div class="v2-eyebrow contact-stage__eyebrow">
          <span>DIRECT LINE &middot; WATAUGA HQ</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
          <span class="v2-telemetry">
            <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
            <span data-ct-clock>WATAUGA TX &middot; SAT 9AM&ndash;3PM CT</span>
          </span>
        </div>

        <div class="contact-stage__grid">
          <!-- Left Column: Monumental Display + Handwritten Note + Ruled Directory -->
          <div class="contact-stage__info">
            <div class="contact-stage__headline">
              <h1 id="contact-hero-heading" class="v2-display v2-display--hero">
                OPEN A<br>
                <span class="v2-accent-text">DISPATCH</span><span class="v2-dot" aria-hidden="true"></span>
              </h1>
              <p class="v2-note contact-stage__note">
                <span>100% volunteer crew &mdash; every message read by a Lion</span>
                <?= erc_curved_arrow('up-right') ?>
              </p>
            </div>

            <p class="contact-stage__lead">
              Coordinate a group sorting shift, request eyewear for a humanitarian mission, arrange a weekday drop-off,
              or request host collection boxes. Typical response time is within 24 hours.
            </p>

            <div class="contact-directory" aria-label="Center address, access directions, and direct channels">
              <div class="contact-directory__head">
                <span>VERIFIED DISPATCH DIRECTORY</span>
                <span>32.8707&deg; N, 97.2621&deg; W</span>
              </div>

              <div class="contact-directory__rows">
                <div class="contact-directory__row">
                  <span class="contact-directory__key">CENTER HQ</span>
                  <div class="contact-directory__val">
                    <strong>5621 Bunker Blvd, Watauga, TX 76148</strong>
                    <p class="contact-directory__sub">
                      Free on-site parking. Follow the wooden walkway along the <strong>east side</strong> of the building to the <strong>rear entrance</strong>.
                    </p>
                    <div class="contact-directory__actions">
                      <button
                        type="button"
                        class="btn btn--secondary btn--sm contact-action-btn"
                        id="copyCenterAddressBtn"
                        data-copy-text="5621 Bunker Blvd, Watauga, TX 76148"
                      >
                        <?= erc_icon('copy', ['width' => '15', 'height' => '15']) ?>
                        <span>COPY ADDRESS</span>
                      </button>
                      <a
                        class="btn btn--secondary btn--sm contact-action-btn"
                        href="https://www.google.com/maps/search/?api=1&amp;query=5621+Bunker+Blvd,+Watauga,+TX+76148"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        <span>DIRECTIONS</span>
                        <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
                      </a>
                      <a class="btn btn--ghost btn--sm contact-action-btn" href="/locations">
                        <span>214 DROP-OFF BINS</span>
                        <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
                      </a>
                    </div>
                    <div class="contact-copy-feedback" id="copyCenterFeedback" role="status" aria-live="polite"></div>
                  </div>
                </div>

                <div class="contact-directory__row">
                  <span class="contact-directory__key">HOURS</span>
                  <div class="contact-directory__val contact-hours-grid">
                    <span><strong>Saturday:</strong> 9:00 AM &ndash; 3:00 PM (shifts 9&ndash;12 &amp; 12&ndash;3)</span>
                    <span><strong>Mon&ndash;Fri:</strong> By Appointment &middot; <strong>Sunday:</strong> Closed</span>
                  </div>
                </div>

                <div class="contact-directory__row contact-directory__row--split">
                  <a class="contact-directory__link" href="tel:<?= $escape($phoneLink) ?>">
                    <span class="contact-directory__link-label">PHONE / VOICEMAIL</span>
                    <span class="contact-directory__link-val">
                      <span><?= $escape($phone) ?></span>
                      <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
                    </span>
                  </a>

                  <a class="contact-directory__link" href="mailto:<?= $escape($email) ?>">
                    <span class="contact-directory__link-label">DIRECT EMAIL</span>
                    <span class="contact-directory__link-val">
                      <span><?= $escape($email) ?></span>
                      <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
                    </span>
                  </a>
                </div>

                <div class="contact-directory__row">
                  <span class="contact-directory__key">MAIL &amp; CHECKS</span>
                  <div class="contact-directory__val">
                    <strong>Lions District 2-E2 ERC &middot; P.O. Box 1854, Keller, TX 76244</strong>
                    <p class="contact-directory__sub">100% volunteer-run 501(c)(3) nonprofit (founded 2013).</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Dark Obsidian Console Card -->
          <div class="dispatch-console" id="dispatch-console" aria-labelledby="dispatch-console-heading">
            <div class="dispatch-console__handle" aria-hidden="true"></div>

            <div class="dispatch-console__topbar">
              <h2 id="dispatch-console-heading" class="dispatch-console__title">DISPATCH REQUEST</h2>
              <div class="dispatch-console__id">
                <span>REQ-2E2</span>
                <span class="dispatch-console__badge" id="dispatchStateBadge">
                  <span class="dispatch-console__dot" aria-hidden="true"></span>
                  <span id="dispatchStateText">DRAFT</span>
                </span>
              </div>
            </div>

<?php if ($prgBanner !== null): ?>
            <div
              id="contactFormAlert"
              class="callout contact-status callout--<?= $escape($prgBanner['type']) ?>"
              role="<?= $prgBanner['type'] === 'success' ? 'status' : 'alert' ?>"
            >
              <span class="callout__icon" data-alert-icon>
                <?= erc_icon($prgBanner['type'] === 'success' ? 'circle-check-big' : 'triangle-alert', ['width' => '20', 'height' => '20']) ?>
              </span>
              <div class="callout__body">
                <strong class="callout__title" data-alert-title><?= $escape($prgBanner['title']) ?></strong>
                <p data-alert-message><?= $escape($prgBanner['message']) ?></p>
              </div>
            </div>
<?php else: ?>
            <div id="contactFormAlert" class="callout contact-status" role="status" hidden>
              <span class="callout__icon" data-alert-icon></span>
              <div class="callout__body">
                <strong class="callout__title" data-alert-title></strong>
                <p data-alert-message></p>
              </div>
            </div>
<?php endif; ?>

            <template id="tplContactIconSuccess">
              <?= erc_icon('circle-check-big', ['width' => '20', 'height' => '20']) ?>
            </template>
            <template id="tplContactIconError">
              <?= erc_icon('triangle-alert', ['width' => '20', 'height' => '20']) ?>
            </template>

            <form
              id="contactForm"
              class="form-stack dispatch-form"
              action="/send-email.php"
              method="POST"
              data-erc-validate
            >
              <div class="form-honeypot" aria-hidden="true">
                <label for="contactWebsiteUrl">Leave this field blank</label>
                <input
                  type="text"
                  id="contactWebsiteUrl"
                  name="website_url"
                  tabindex="-1"
                  autocomplete="off"
                >
              </div>

              <div class="dispatch-meta-bar" aria-hidden="true">
                <div class="dispatch-meta-cell">
                  <span class="dispatch-meta-cell__label">RECIPIENT</span>
                  <span class="dispatch-meta-cell__val">Lions District 2-E2 ERC</span>
                </div>
                <div class="dispatch-meta-cell">
                  <span class="dispatch-meta-cell__label">SLA WINDOW</span>
                  <span class="dispatch-meta-cell__val">&le; 24 HOURS</span>
                </div>
              </div>

              <div class="form-row form-row--2">
                <div class="field">
                  <label class="field__label" for="contactName">
                    FULL NAME <span class="field__required" aria-hidden="true">*</span>
                  </label>
                  <input
                    class="field__control"
                    type="text"
                    id="contactName"
                    name="name"
                    required
                    maxlength="120"
                    autocomplete="name"
                    placeholder="Your name or Lions club"
                    data-error="contactNameError"
                    aria-invalid="false"
                  >
                  <div class="field__error" id="contactNameError" hidden>
                    <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                    <span data-error-text></span>
                  </div>
                </div>

                <div class="field">
                  <label class="field__label" for="contactEmail">
                    EMAIL ADDRESS <span class="field__required" aria-hidden="true">*</span>
                  </label>
                  <input
                    class="field__control"
                    type="email"
                    id="contactEmail"
                    name="email"
                    required
                    maxlength="254"
                    inputmode="email"
                    autocomplete="email"
                    placeholder="you@example.org"
                    data-error="contactEmailError"
                    aria-invalid="false"
                  >
                  <div class="field__error" id="contactEmailError" hidden>
                    <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                    <span data-error-text></span>
                  </div>
                </div>
              </div>

              <div class="field">
                <label class="field__label" for="contactPhone">
                  PHONE NUMBER <span class="dispatch-optional">OPTIONAL</span>
                </label>
                <input
                  class="field__control"
                  type="tel"
                  id="contactPhone"
                  name="phone"
                  maxlength="40"
                  inputmode="tel"
                  autocomplete="tel"
                  placeholder="(817) 555-0100 — helpful for weekday drop-offs"
                  data-error="contactPhoneError"
                  aria-invalid="false"
                >
                <div class="field__error" id="contactPhoneError" hidden>
                  <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                  <span data-error-text></span>
                </div>
              </div>

              <fieldset class="dispatch-topic-fieldset">
                <legend class="field__label">
                  INQUIRY PURPOSE <span class="field__required" aria-hidden="true">*</span>
                </legend>
                <div class="dispatch-pills">
<?php foreach ($subjectCatalog as $optVal => $optMeta):
    $pillId = 'contactTopic-' . preg_replace('/[^a-z0-9-]/i', '', $optVal);
    $isChecked = $selectedSubject === $optVal;
?>
                  <label class="dispatch-pill" for="<?= $escape($pillId) ?>">
                    <input
                      class="dispatch-pill__radio"
                      type="radio"
                      id="<?= $escape($pillId) ?>"
                      name="subject"
                      value="<?= $escape($optVal) ?>"
                      data-route-label="<?= $escape($optMeta['route']) ?>"
                      <?= $isChecked ? 'checked' : '' ?>
                    >
                    <span class="dispatch-pill__text"><?= $escape($optMeta['pill']) ?></span>
                  </label>
<?php endforeach; ?>
                </div>
              </fieldset>

              <div class="dispatch-telemetry" id="dispatchTelemetry" aria-live="polite">
                <span>ROUTING: <strong id="dispatchRoutingValue"><?= $escape($initialRouteLabel) ?></strong></span>
                <span>FEE: <strong>$0</strong></span>
              </div>

              <div class="field">
                <div class="dispatch-message-head">
                  <label class="field__label" for="contactMessage">
                    DISPATCH BRIEF <span class="field__required" aria-hidden="true">*</span>
                  </label>
                  <div class="field__meta dispatch-counter" id="contactMessageCount">0 / 5,000 characters</div>
                </div>
                <span class="form-hint" id="contactMessageHint">
                  Share preferred dates, group headcount (up to 20), drop-off items, or mission details.
                </span>
                <textarea
                  class="field__control dispatch-textarea"
                  id="contactMessage"
                  name="message"
                  rows="5"
                  required
                  maxlength="5000"
                  placeholder="What's on your mind? Details speed up volunteer coordination."
                  aria-describedby="contactMessageHint contactMessageCount"
                  data-error="contactMessageError"
                  aria-invalid="false"
                ></textarea>
                <div class="field__error" id="contactMessageError" hidden>
                  <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                  <span data-error-text></span>
                </div>
              </div>

              <div class="dispatch-submit-area">
                <button type="submit" class="btn btn--primary btn--lg dispatch-submit" id="contactSubmitBtn">
                  <span id="contactSubmitLabel">SEND DISPATCH</span>
                  <span class="dispatch-submit__arrow" aria-hidden="true">
                    <?= erc_icon('arrow-right', ['width' => '18', 'height' => '18']) ?>
                  </span>
                </button>
              </div>

              <div class="dispatch-console__footer">
                <span>TYPICAL RESPONSE TIME: WITHIN 24 HOURS</span>
                <div class="dispatch-console__copy-row">
                  <span>DIRECT ADDRESS?</span>
                  <button
                    type="button"
                    class="dispatch-copy-inline"
                    id="copyEmailAddressBtn"
                    data-copy-text="<?= $escape($email) ?>"
                  >
                    COPY ADDRESS
                  </button>
                  <span class="dispatch-copy-status" id="copyEmailFeedback" role="status" aria-live="polite"></span>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- =======================================================================
         2. PERFORATED TICKET STUBS: 6 OFFICIAL PDF FORMS & RECORDS
         ======================================================================= -->
    <section class="section forms-stage" id="forms-center" aria-labelledby="forms-center-heading">
      <div class="container">
        <div class="v2-eyebrow forms-stage__eyebrow">
          <span>OFFICIAL RECORDS &amp; DOWNLOADS</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
          <span>6 VERIFIED PDFS</span>
        </div>

        <div class="forms-stage__header">
          <h2 id="forms-center-heading" class="v2-display v2-display--lg">
            FORMS &amp; <span class="v2-accent-text">RECORDS</span><span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="lead">
            Download printable brochures, group scheduling sheets, mission eyeglass request forms, awards,
            and our 501(c)(3) foundation bylaws.
          </p>
        </div>

        <div class="forms-ticket-grid">
<?php foreach ($pdfForms as $idx => $formDoc):
    $pdfUrl = $media('files/' . $formDoc['file']);
    $pdfSize = $resolvePdfSize($formDoc['file']);
    $metaLabel = $pdfSize !== '' ? strtoupper($formDoc['pages']) . ' · ' . $pdfSize : strtoupper($formDoc['pages']);
    $toneClass = 'ticket-pdf--tone-' . (($idx % 6) + 1);
?>
          <article class="v2-ticket ticket-pdf <?= $escape($toneClass) ?>">
            <div class="v2-ticket__main">
              <div class="v2-ticket__meta">
                <span>PDF &middot; <?= $escape($formDoc['category']) ?></span>
                <span><?= $escape($metaLabel) ?></span>
              </div>
              <h3 class="v2-ticket__title"><?= $escape($formDoc['title']) ?></h3>
              <p class="ticket-pdf__desc"><?= $escape($formDoc['description']) ?></p>
            </div>

            <div class="v2-ticket__stub">
              <span class="v2-ticket__serial"><?= $escape($formDoc['serial']) ?></span>
              <a
                class="btn btn--primary btn--sm ticket-pdf__btn"
                href="<?= $escape($pdfUrl) ?>"
                download="<?= $escape($formDoc['file']) ?>"
                aria-label="Download <?= $escape($formDoc['title']) ?> (<?= $escape($formDoc['file']) ?>)"
              >
                <span>PDF</span>
                <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
              </a>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- =======================================================================
         3. CONTRAST STAGE: BEFORE YOU ASK (INTERACTIVE REVIEW QUEUE)
         ======================================================================= -->
    <section class="v2-sheet faq-stage" id="faq" aria-labelledby="contact-faq-heading">
      <div class="v2-sheet__inner">
        <div class="v2-eyebrow">
          <span>FAQ, FILED AS AN ACCESS REVIEW</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
          <span><?= count($faqItems) ?> VERIFIED ANSWERS</span>
        </div>

        <div class="faq-stage__hero">
          <h2 id="contact-faq-heading" class="v2-display v2-display--lg">
            BEFORE<br>
            YOU <span class="v2-accent-text">ASK</span><span class="v2-dot" aria-hidden="true"></span>
          </h2>

          <div class="faq-stage__intro">
            <p class="faq-stage__lead">
              Volunteer operations deserve clear operational answers: every recurring question filed as a docket you can
              inspect and clear at your own pace.
            </p>
            <p class="v2-note">
              <span>you're the reviewer</span>
              <?= erc_curved_arrow('down-left') ?>
            </p>
          </div>
        </div>

        <div class="faq-queue-bar">
          <div class="faq-queue-bar__status">
            <span class="v2-telemetry">
              <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
              <span>REVIEW QUEUE &middot; LIVE</span>
            </span>
            <p class="faq-queue-bar__sub">
              <span class="mono tabular-nums"><?= count($faqItems) ?></span> questions. 1 reviewer. Click any question to review and clear it.
            </p>
          </div>

          <div class="faq-queue-bar__actions">
            <button
              type="button"
              class="btn btn--primary btn--sm faq-stamp-btn"
              id="faqStampAllBtn"
              data-total-faqs="<?= count($faqItems) ?>"
            >
              <?= erc_icon('check-circle-2', ['width' => '16', 'height' => '16']) ?>
              <span id="faqStampAllLabel">STAMP ALL</span>
            </button>
          </div>
        </div>

        <div class="faq-counters" aria-label="FAQ review progress">
          <div class="faq-counter">
            <span class="faq-counter__num" id="faqClearedCount">00</span>
            <span class="faq-counter__label">CLEARED</span>
          </div>
          <div class="faq-counter">
            <span class="faq-counter__num faq-counter__num--pending" id="faqPendingCount"><?= str_pad((string) count($faqItems), 2, '0', STR_PAD_LEFT) ?></span>
            <span class="faq-counter__label">PENDING</span>
          </div>
        </div>

        <div class="faq-stamp-toast" id="faqStampToast" role="status" aria-live="polite" hidden>
          <div class="faq-stamp-toast__body">
            <span class="faq-stamp-toast__eyebrow">
              <span class="v2-dot" aria-hidden="true"></span>
              RUBBER STAMP DETECTED
            </span>
            <p class="faq-stamp-toast__msg" id="faqStampToastMsg">
              RUBBER STAMP DETECTED: all questions cleared in one click.
            </p>
          </div>
          <button type="button" class="btn btn--secondary btn--sm faq-undo-btn" id="faqUndoBtn">
            UNDO
          </button>
        </div>

        <div class="faq-queue-list" id="faqQueueList">
<?php foreach ($faqItems as $item): ?>
          <details class="accordion faq-item" data-faq-id="<?= $escape($item['id']) ?>" data-cleared="false">
            <summary class="accordion__summary faq-item__summary">
              <span class="faq-item__id"><?= $escape($item['id']) ?></span>
              <span class="faq-item__tag"><?= $escape($item['tag']) ?></span>
              <span class="faq-item__question"><?= $escape($item['question']) ?></span>
              <span class="faq-item__status" data-faq-status>PENDING</span>
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
            </summary>
            <div class="accordion__panel prose faq-item__panel">
<?php foreach ($item['paragraphs'] as $paragraph): ?>
              <p><?= $escape($paragraph) ?></p>
<?php endforeach; ?>
<?php if (!empty($item['link'])): ?>
              <p>
                <a class="faq-item__link" href="<?= $escape($item['link']['href']) ?>">
                  <span><?= $escape($item['link']['label']) ?></span>
                  <?= erc_icon('arrow-up-right', ['width' => '15', 'height' => '15']) ?>
                </a>
              </p>
<?php endif; ?>
            </div>
          </details>
<?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>
<?php
require __DIR__ . '/includes/footer.php';
