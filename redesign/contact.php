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

$subjectOptions = [
    'volunteer' => 'Volunteer Sign-Up / Individual Interest',
    'group-volunteer' => 'Group Volunteer Session (up to 20)',
    'donation-items' => 'Eyeglass / Hearing Aid / Tech Donation',
    'donation-money' => 'Monetary Donation / Sponsorship',
    'need-collection-boxes' => 'Request Collection Boxes',
    'partnership' => 'Club / Community Partnership',
    'question' => 'General Question',
    'other' => 'Other',
];

$requestedSubject = isset($_GET['subject']) ? trim((string) $_GET['subject']) : '';
$selectedSubject = array_key_exists($requestedSubject, $subjectOptions) ? $requestedSubject : '';

$statusParam = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$reasonParam = isset($_GET['reason']) ? trim((string) $_GET['reason']) : '';

$prgBanner = null;
if ($statusParam === 'success') {
    $prgBanner = [
        'type' => 'success',
        'title' => 'Inquiry sent',
        'message' => 'Thank you for reaching out to the District 2-E2 Eyeglass Recycling Center. Our volunteers usually respond within 24 hours.',
    ];
} elseif ($statusParam === 'error') {
    $reasonMap = [
        'rate_limited' => 'Too many inquiries were submitted recently from your connection. Please wait a few minutes or call or email us directly.',
        'invalid_input' => 'Please verify your name, valid email address, and message, then try submitting again.',
        'send_failed' => 'We were unable to send your message right now due to an email delivery error. Please call or email us directly.',
        'mail_failed' => 'We were unable to send your message right now due to an email delivery error. Please call or email us directly.',
    ];
    $prgBanner = [
        'type' => 'danger',
        'title' => 'Could not send inquiry',
        'message' => $reasonMap[$reasonParam] ?? 'Something went wrong while sending your message. Please try again or reach us by phone or email.',
    ];
}

$resolvePdfSize = static function (string $filename): string {
    $candidates = [];
    if (defined('ERC_ASSET_BASE_DIR')) {
        $candidates[] = rtrim((string) ERC_ASSET_BASE_DIR, '/\\') . '/files/' . $filename;
    }
    $candidates[] = __DIR__ . '/files/' . $filename;

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
        'file' => 'ERC-Brochure.pdf',
        'title' => 'ERC Program Brochure',
        'pages' => '2-page PDF',
        'category' => 'Overview',
        'description' => 'Overview of our mission, accepted donations, and how volunteers and Lions clubs help restore vision.',
    ],
    [
        'file' => 'Group-Volunteer-Form.pdf',
        'title' => 'Group Volunteer Request Form',
        'pages' => '1-page PDF',
        'category' => 'Volunteering',
        'description' => 'Schedule group eyeglass sorting shifts (up to 20 volunteers) for Saturday morning or afternoon sessions.',
    ],
    [
        'file' => 'Mission-Request-Form.pdf',
        'title' => 'Mission Eyeglass Request Form',
        'pages' => '1-page PDF',
        'category' => 'Vision Missions',
        'description' => 'Order request form for humanitarian vision and medical mission teams seeking cleaned, graded eyeglasses.',
    ],
    [
        'file' => 'Helen-Keller-Award-Application.pdf',
        'title' => 'Helen Keller Award ("Knights of Sight") Application',
        'pages' => '1-page PDF',
        'category' => 'Awards',
        'description' => 'Application for volunteer service hours or sponsorship ($250 Benefactor, $500 Endowment, $1,000 Patron) supporting the ERC Foundation.',
    ],
    [
        'file' => 'ERC-Life-Membership-Application.pdf',
        'title' => 'ERC Life Membership Application',
        'pages' => '1-page PDF',
        'category' => 'Membership',
        'description' => 'Enroll as a Life Member supporting ongoing eyewear recycling and mission readiness.',
    ],
    [
        'file' => 'ERC-Bylaws.pdf',
        'title' => 'ERC Foundation Bylaws',
        'pages' => '10-page PDF',
        'category' => 'Governance',
        'description' => 'Official bylaws of the District 2-E2 Lions Eyeglass Recycling Center Foundation.',
    ],
];

$pageMeta = [
    'slug' => 'contact',
    'title' => 'Contact Us & Forms | Lions District 2-E2 Eyeglass Recycling Center',
    'description' => 'Contact the Lions District 2-E2 Eyeglass Recycling Center in Watauga, TX. View center hours, rear walkway access directions, send an inquiry, and download official PDF forms.',
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
  <main id="main-content" class="site-main" tabindex="-1">
    <section class="page-hero">
      <div class="container">
        <div class="page-hero__inner">
          <span class="eyebrow">
            <?= erc_icon('mail', ['width' => '16', 'height' => '16']) ?>
            Get in Touch
          </span>
          <h1>Contact, Directions &amp; Official Forms</h1>
          <p class="lead">
            Reach our 100% volunteer team to schedule a group sorting session, request eyewear for a vision
            mission, arrange a weekday drop-off, or request collection boxes.
          </p>
          <div class="contact-hero__meta">
            <span class="badge badge--brand">
              <?= erc_icon('clock', ['width' => '14', 'height' => '14']) ?>
              Saturdays 9:00 AM – 3:00 PM
            </span>
            <span class="badge badge--emerald">
              <?= erc_icon('check-circle-2', ['width' => '14', 'height' => '14']) ?>
              Usually responds within 24 hours
            </span>
            <span class="badge">
              <?= erc_icon('heart-handshake', ['width' => '14', 'height' => '14']) ?>
              100% Volunteer-Run 501(c)(3)
            </span>
          </div>
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="contact-section-heading">
      <div class="container">
        <div class="contact-split">
          <!-- Inquiry Form -->
          <div class="card contact-form-card">
            <div class="contact-form-card__header">
              <h2 id="contact-section-heading">Send an Inquiry</h2>
              <p class="text-secondary">
                Choose the topic that fits best so our volunteer coordinators can route your request right away.
              </p>
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
              class="form-stack"
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

              <div class="form-row form-row--2">
                <div class="field">
                  <label class="field__label" for="contactName">
                    Full Name <span class="field__required" aria-hidden="true">*</span>
                  </label>
                  <span class="form-hint" id="contactNameHint">Your name or organization</span>
                  <input
                    class="field__control"
                    type="text"
                    id="contactName"
                    name="name"
                    required
                    maxlength="120"
                    autocomplete="name"
                    aria-describedby="contactNameHint"
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
                    Email Address <span class="field__required" aria-hidden="true">*</span>
                  </label>
                  <span class="form-hint" id="contactEmailHint">Used only to reply to your inquiry</span>
                  <input
                    class="field__control"
                    type="email"
                    id="contactEmail"
                    name="email"
                    required
                    maxlength="254"
                    autocomplete="email"
                    aria-describedby="contactEmailHint"
                    data-error="contactEmailError"
                    aria-invalid="false"
                  >
                  <div class="field__error" id="contactEmailError" hidden>
                    <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                    <span data-error-text></span>
                  </div>
                </div>
              </div>

              <div class="form-row form-row--2">
                <div class="field">
                  <label class="field__label" for="contactPhone">
                    Phone Number <span class="text-muted">(Optional)</span>
                  </label>
                  <span class="form-hint" id="contactPhoneHint">Helpful for scheduling weekday drop-offs</span>
                  <input
                    class="field__control"
                    type="tel"
                    id="contactPhone"
                    name="phone"
                    maxlength="40"
                    autocomplete="tel"
                    aria-describedby="contactPhoneHint"
                    data-error="contactPhoneError"
                    aria-invalid="false"
                  >
                  <div class="field__error" id="contactPhoneError" hidden>
                    <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                    <span data-error-text></span>
                  </div>
                </div>

                <div class="field">
                  <label class="field__label" for="contactSubject">
                    Inquiry Topic <span class="field__required" aria-hidden="true">*</span>
                  </label>
                  <span class="form-hint" id="contactSubjectHint">Select the area that fits your message</span>
                  <select
                    class="field__control"
                    id="contactSubject"
                    name="subject"
                    required
                    aria-describedby="contactSubjectHint"
                    data-error="contactSubjectError"
                    data-msg-required="Please select an inquiry topic."
                    aria-invalid="false"
                  >
                    <option value="">Choose an inquiry topic…</option>
<?php foreach ($subjectOptions as $optVal => $optLabel): ?>
                    <option
                      value="<?= $escape($optVal) ?>"
                      <?= $selectedSubject === $optVal ? 'selected' : '' ?>
                    ><?= $escape($optLabel) ?></option>
<?php endforeach; ?>
                  </select>
                  <div class="field__error" id="contactSubjectError" hidden>
                    <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                    <span data-error-text></span>
                  </div>
                </div>
              </div>

              <div class="field">
                <label class="field__label" for="contactMessage">
                  Message <span class="field__required" aria-hidden="true">*</span>
                </label>
                <span class="form-hint" id="contactMessageHint">
                  Share preferred dates, group headcount, drop-off items, or questions (up to 5,000 characters).
                </span>
                <textarea
                  class="field__control"
                  id="contactMessage"
                  name="message"
                  rows="6"
                  required
                  maxlength="5000"
                  aria-describedby="contactMessageHint contactMessageCount"
                  data-error="contactMessageError"
                  aria-invalid="false"
                ></textarea>
                <div class="field__meta" id="contactMessageCount">0 / 5,000 characters</div>
                <div class="field__error" id="contactMessageError" hidden>
                  <?= erc_icon('alert-triangle', ['width' => '16', 'height' => '16']) ?>
                  <span data-error-text></span>
                </div>
              </div>

              <div class="contact-submit-row">
                <span class="contact-response-note">
                  <?= erc_icon('clock', ['width' => '16', 'height' => '16']) ?>
                  Usually responds within 24 hours
                </span>
                <button type="submit" class="btn btn--primary btn--lg" id="contactSubmitBtn">
                  <?= erc_icon('send', ['width' => '18', 'height' => '18']) ?>
                  <span id="contactSubmitLabel">Send Inquiry</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Center Visit, Hours & Direct Contact Sidebar -->
          <aside class="contact-sidebar" aria-label="Center address, hours, and direct contact">
            <div class="card card--tint walkway-highlight">
              <span class="eyebrow">
                <?= erc_icon('map-pin', ['width' => '16', 'height' => '16']) ?>
                Watauga Recycling Center
              </span>
              <h3>Finding Our Entrance</h3>
              <address class="walkway-address">
                5621 Bunker Blvd<br>
                Watauga, TX 76148
              </address>

              <div class="walkway-steps">
                <div class="walkway-step">
                  <?= erc_icon('navigation', ['width' => '18', 'height' => '18']) ?>
                  <div>
                    <strong>East-Side Wooden Walkway:</strong> After parking, follow the wooden walkway on the
                    <strong>east side</strong> of the building to reach our <strong>rear entrance</strong>.
                  </div>
                </div>
                <div class="walkway-step">
                  <?= erc_icon('info', ['width' => '18', 'height' => '18']) ?>
                  <div>
                    <strong>Free Parking:</strong> Complimentary visitor parking is available directly in the center lot.
                  </div>
                </div>
              </div>

              <div class="cluster">
                <button
                  type="button"
                  class="btn btn--secondary btn--sm"
                  id="copyCenterAddressBtn"
                  data-copy-text="5621 Bunker Blvd, Watauga, TX 76148"
                >
                  <?= erc_icon('copy', ['width' => '16', 'height' => '16']) ?>
                  <span>Copy Address</span>
                </button>
                <a
                  class="btn btn--secondary btn--sm"
                  href="https://www.google.com/maps/search/?api=1&amp;query=5621+Bunker+Blvd,+Watauga,+TX+76148"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <?= erc_icon('external-link', ['width' => '16', 'height' => '16']) ?>
                  <span>Directions</span>
                </a>
                <a class="btn btn--ghost btn--sm" href="/locations">
                  <?= erc_icon('map', ['width' => '16', 'height' => '16']) ?>
                  <span>214 Drop-Off Bins</span>
                </a>
              </div>
              <div class="copy-feedback" id="copyCenterFeedback" aria-live="polite"></div>
            </div>

            <div class="card">
              <h3>Center Hours</h3>
              <div class="hours-list">
                <div class="hours-row">
                  <span class="hours-row__day">Saturday</span>
                  <span class="hours-row__time">9:00 AM – 3:00 PM</span>
                </div>
                <div class="hours-row">
                  <span class="hours-row__day">Monday – Friday</span>
                  <span class="hours-row__time">By appointment</span>
                </div>
                <div class="hours-row">
                  <span class="hours-row__day">Sunday</span>
                  <span class="hours-row__time">Closed</span>
                </div>
              </div>
              <p class="mt-4 text-secondary">
                Group sorting sessions run on Saturdays in two shifts: <strong>9:00 AM – 12:00 PM</strong> and
                <strong>12:00 PM – 3:00 PM</strong> (up to 20 volunteers per session).
              </p>
            </div>

            <div class="card">
              <h3>Direct Channels &amp; Mailing</h3>
              <div class="channel-list">
                <div class="channel-item">
                  <span class="channel-item__icon" aria-hidden="true">
                    <?= erc_icon('phone', ['width' => '18', 'height' => '18']) ?>
                  </span>
                  <div class="channel-item__content">
                    <span class="channel-item__label">Phone</span>
                    <a class="channel-item__value" href="tel:<?= $escape($phoneLink) ?>">
                      <?= $escape($phone) ?>
                    </a>
                  </div>
                </div>

                <div class="channel-item">
                  <span class="channel-item__icon" aria-hidden="true">
                    <?= erc_icon('mail', ['width' => '18', 'height' => '18']) ?>
                  </span>
                  <div class="channel-item__content">
                    <span class="channel-item__label">Email</span>
                    <a class="channel-item__value" href="mailto:<?= $escape($email) ?>">
                      <?= $escape($email) ?>
                    </a>
                  </div>
                </div>

                <div class="channel-item">
                  <span class="channel-item__icon" aria-hidden="true">
                    <?= erc_icon('landmark', ['width' => '18', 'height' => '18']) ?>
                  </span>
                  <div class="channel-item__content">
                    <span class="channel-item__label">Mailing Address (Checks &amp; Correspondence)</span>
                    <span class="channel-item__value">P.O. Box 1854, Keller, TX 76244</span>
                  </div>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <!-- Downloadable Forms Center -->
    <section class="section section--subtle" id="forms-center" aria-labelledby="forms-center-heading">
      <div class="container">
        <div class="section-header">
          <span class="eyebrow">
            <?= erc_icon('file-text', ['width' => '16', 'height' => '16']) ?>
            Official Documents
          </span>
          <h2 id="forms-center-heading">Downloadable Forms Center</h2>
          <p class="lead">
            Print or share brochures, group sign-up sheets, mission eyeglass request forms, award applications,
            and our foundation bylaws.
          </p>
        </div>

        <div class="forms-center-grid">
<?php foreach ($pdfForms as $formDoc): ?>
<?php
    $pdfUrl = $media('files/' . $formDoc['file']);
    $pdfSize = $resolvePdfSize($formDoc['file']);
    $metaLabel = $pdfSize !== '' ? $formDoc['pages'] . ' · ' . $pdfSize : $formDoc['pages'];
?>
          <article class="card card--interactive pdf-card">
            <div class="pdf-card__top">
              <span class="pdf-card__icon" aria-hidden="true">
                <?= erc_icon('file-text', ['width' => '22', 'height' => '22']) ?>
              </span>
              <span class="badge"><?= $escape($formDoc['category']) ?></span>
            </div>

            <div class="pdf-card__body">
              <h3 class="pdf-card__title"><?= $escape($formDoc['title']) ?></h3>
              <p class="pdf-card__desc"><?= $escape($formDoc['description']) ?></p>
            </div>

            <div class="pdf-card__footer">
              <span class="pdf-card__meta"><?= $escape($metaLabel) ?></span>
              <a
                class="btn btn--secondary btn--sm"
                href="<?= $escape($pdfUrl) ?>"
                download="<?= $escape($formDoc['file']) ?>"
              >
                <?= erc_icon('download', ['width' => '16', 'height' => '16']) ?>
                <span>Download PDF</span>
              </a>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Verified Factual FAQs -->
    <section class="section" id="faq" aria-labelledby="contact-faq-heading">
      <div class="container container--narrow">
        <div class="section-header">
          <span class="eyebrow">
            <?= erc_icon('circle-help', ['width' => '16', 'height' => '16']) ?>
            Quick Answers
          </span>
          <h2 id="contact-faq-heading">Frequently Asked Questions</h2>
        </div>

        <div class="accordion-group">
          <details class="accordion" open>
            <summary class="accordion__summary">
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
              <span>How do I find the entrance when I arrive at the Watauga center?</span>
            </summary>
            <div class="accordion__panel prose">
              <p>
                Our center is located at <strong>5621 Bunker Blvd, Watauga, TX 76148</strong> with free parking
                available in the center lot. After parking, follow the <strong>wooden walkway along the east side</strong>
                of the building to reach the rear entrance.
              </p>
            </div>
          </details>

          <details class="accordion">
            <summary class="accordion__summary">
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
              <span>Can I drop off eyeglasses on a weekday or near Fort Worth?</span>
            </summary>
            <div class="accordion__panel prose">
              <p>
                Yes. The Watauga center welcomes visitors on <strong>Saturdays from 9:00 AM to 3:00 PM</strong>
                and is open <strong>Monday through Friday by appointment</strong> (use the inquiry form above or
                call <a href="tel:<?= $escape($phoneLink) ?>"><?= $escape($phone) ?></a>). You can also use any of
                our 214 community <a href="/locations">drop-off collection bins</a> across 34 cities in District 2-E2.
              </p>
            </div>
          </details>

          <details class="accordion">
            <summary class="accordion__summary">
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
              <span>What items do you accept — and what items cannot be accepted?</span>
            </summary>
            <div class="accordion__panel prose">
              <p>
                We gladly accept prescription eyeglasses, reading glasses, and sunglasses (even broken glasses or frames
                missing parts), digital or analog hearing aids (functional or non-functional), wiped cell phones, and
                <strong>Windows 11-ready or Windows 11-installed laptops and tablets</strong>.
              </p>
              <p>
                We cannot accept desktop computers, laptops or tablets that cannot run Windows 11, or devices with
                damaged screens, non-functional keyboards, or missing parts. Individual partner drop-off bin
                locations may vary in which device types they collect — check location notes or bring electronics
                directly to our Watauga center.
              </p>
            </div>
          </details>

          <details class="accordion">
            <summary class="accordion__summary">
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
              <span>How do group volunteer sorting sessions work?</span>
            </summary>
            <div class="accordion__panel prose">
              <p>
                Groups of up to <strong>20 volunteers</strong> can sign up for Saturday sessions in either the
                morning shift (<strong>9:00 AM – 12:00 PM</strong>) or afternoon shift (<strong>12:00 PM – 3:00 PM</strong>).
                You can download the <a href="#forms-center">Group Volunteer Request Form</a> above or select
                <em>Group Volunteer Session</em> in the inquiry form.
              </p>
            </div>
          </details>

          <details class="accordion">
            <summary class="accordion__summary">
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
              <span>How do vision mission teams request recycled eyeglasses?</span>
            </summary>
            <div class="accordion__panel prose">
              <p>
                Medical and humanitarian vision teams can download and submit the
                <a href="<?= $escape($media('files/Mission-Request-Form.pdf')) ?>" download="Mission-Request-Form.pdf">Mission Eyeglass Request Form (PDF)</a>
                or review our sorting process on the <a href="/missions">Missions &amp; Impact</a> page.
              </p>
            </div>
          </details>

          <details class="accordion">
            <summary class="accordion__summary">
              <?= erc_icon('chevron-down', ['class' => 'erc-icon accordion__chevron', 'width' => '18', 'height' => '18']) ?>
              <span>Where should I mail checks or written correspondence?</span>
            </summary>
            <div class="accordion__panel prose">
              <p>
                Please send mailed donations and written correspondence to
                <strong>P.O. Box 1854, Keller, TX 76244</strong>. District 2-E2 Lions Eyeglass Recycling Center
                Foundation is a 100% volunteer-run nonprofit founded in 2013 and recognized as a 501(c)(3)
                organization in 2014.
              </p>
            </div>
          </details>
        </div>
      </div>
    </section>
  </main>
<?php
require __DIR__ . '/includes/footer.php';
