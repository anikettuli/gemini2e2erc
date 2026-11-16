<?php
declare(strict_types=1);

$cntEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$orgPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$orgPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');
$orgEmail = (string) ($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');

$topicCatalog = [
    'general'               => 'General Question',
    'donation-items'        => 'Donate Eyeglasses or Electronics',
    'donation-money'        => 'Monetary Donation / Check',
    'need-collection-boxes' => 'Request or Host Collection Boxes',
    'volunteer'             => 'Individual Volunteering',
    'group-volunteer'       => 'Group Volunteer Day (Up to 20)',
    'partnership'           => 'Mission Request, Partnership, or Awards',
    'other'                 => 'Other Inquiry',
];

$topicAliases = [
    'general'               => 'general',
    'question'              => 'general',
    'inquiry'               => 'general',
    'donation-items'        => 'donation-items',
    'donate'                => 'donation-items',
    'items'                 => 'donation-items',
    'eyeglasses'            => 'donation-items',
    'donation-money'        => 'donation-money',
    'money'                 => 'donation-money',
    'donate-money'          => 'donation-money',
    'check'                 => 'donation-money',
    'need-collection-boxes' => 'need-collection-boxes',
    'bins'                  => 'need-collection-boxes',
    'boxes'                 => 'need-collection-boxes',
    'collection-boxes'      => 'need-collection-boxes',
    'bin'                   => 'need-collection-boxes',
    'volunteer'             => 'volunteer',
    'solo'                  => 'volunteer',
    'group-volunteer'       => 'group-volunteer',
    'group'                 => 'group-volunteer',
    'groups'                => 'group-volunteer',
    'partnership'           => 'partnership',
    'partner'               => 'partnership',
    'mission'               => 'partnership',
    'missions'              => 'partnership',
    'awards'                => 'partnership',
    'award'                 => 'partnership',
    'other'                 => 'other',
];

$rawTopic = strtolower(trim((string) ($_GET['topic'] ?? $_GET['subject'] ?? '')));
$selectedTopic = $topicAliases[$rawTopic] ?? 'general';
if (!array_key_exists($selectedTopic, $topicCatalog)) {
    $selectedTopic = 'general';
}

$statusParam = strtolower(trim((string) ($_GET['status'] ?? '')));
$reasonParam = strtolower(trim((string) ($_GET['reason'] ?? '')));
$hasStatus = ($statusParam === 'success' || $statusParam === 'error');
$statusMessage = '';
if ($statusParam === 'success') {
    $statusMessage = 'Thank you! Your message has been sent. Our volunteer team typically responds within 24 hours.';
} elseif ($statusParam === 'error') {
    $statusMessage = match ($reasonParam) {
        'rate_limited' => 'Too many messages were sent recently from your connection. Please wait a few minutes or call ' . $orgPhone . '.',
        'invalid_input' => 'Please complete your name, a valid email address, and your message (up to 5,000 characters).',
        'send_failed' => 'We could not deliver your message right now. Please call ' . $orgPhone . ' or email ' . $orgEmail . '.',
        default => 'Unable to send your message. Please verify the form fields or contact us by phone or email.',
    };
}

$officialForms = [
    [
        'file'  => 'ERC-Brochure.pdf',
        'title' => 'ERC Center Brochure',
        'meta'  => 'Overview, 2013–2025 shipments & sorting specs',
    ],
    [
        'file'  => 'Group-Volunteer-Form.pdf',
        'title' => 'Group Volunteer Form',
        'meta'  => 'Schedule civic, school, or team days (up to 20)',
    ],
    [
        'file'  => 'Mission-Request-Form.pdf',
        'title' => 'Mission Request Form',
        'meta'  => 'Eyewear order form (submit 30+ days ahead)',
    ],
    [
        'file'  => 'Helen-Keller-Award-Application.pdf',
        'title' => 'Helen Keller Award',
        'meta'  => 'Knights of Sight ($250 / $500 / $1,000 or hours)',
    ],
    [
        'file'  => 'ERC-Life-Membership-Application.pdf',
        'title' => 'ERC Life Membership',
        'meta'  => 'Lions Club recognition sponsorship form',
    ],
    [
        'file'  => 'ERC-Bylaws.pdf',
        'title' => 'Foundation Bylaws',
        'meta'  => 'Corporate governance & articles (2014)',
    ],
];

$faqs = [
    [
        'q' => 'What items can be donated?',
        'a' => 'We accept prescription eyeglasses, reading glasses, and sunglasses in any condition (even broken); digital or analog hearing aids (working or not); smartphones and basic cell phones in any condition (all data is completely wiped); and Windows 11-ready or installed laptops and tablets. Desktop computers, damaged screens, non-functional keyboards, and electronics with missing parts are not accepted. Individual partner drop-off bins may accept a subset of items, so check with the host location or call ' . $orgPhone . '.',
    ],
    [
        'q' => 'What are the operating hours?',
        'a' => 'The Watauga workshop at 5621 Bunker Blvd is open most Saturdays from 9:00 AM to 3:00 PM (with volunteer sessions typically 9:00 AM – 12:00 PM and 12:00 PM – 3:00 PM). Monday through Friday is by appointment; closed Sundays.',
    ],
    [
        'q' => 'Can items be dropped off on weekdays?',
        'a' => 'Yes. Weekday drop-offs and group appointments (Monday through Friday) can be scheduled by calling ' . $orgPhone . ' or emailing ' . $orgEmail . '. You can also use any of the 214 DFW partner drop-off bins during host business hours.',
    ],
    [
        'q' => 'Are damaged eyeglasses accepted?',
        'a' => 'Yes. Eyeglasses and sunglasses in any condition, including broken frames or glasses missing parts, are welcomed and can often be repaired or repurposed.',
    ],
    [
        'q' => 'Are bulk or business donations accepted?',
        'a' => 'Yes. Businesses, clinics, and organizations with large donations can call ' . $orgPhone . ' to coordinate delivery or pickup, or request collection boxes for their lobby or office.',
    ],
    [
        'q' => 'How can large quantities be shipped?',
        'a' => 'Please email ' . $orgEmail . ' before shipping large quantities so our volunteer coordinators can provide shipping instructions or arrange pickup.',
    ],
    [
        'q' => 'Are donations tax-deductible?',
        'a' => 'District 2-E2 Lions Eyeglass Recycling Center Foundation is a recognized 501(c)(3) nonprofit organization. Donations of items or funds may be tax-deductible; donors are advised to check with their tax preparer.',
    ],
    [
        'q' => 'How are eyeglasses used in missions?',
        'a' => 'Refurbished eyeglasses are measured to 0.25-diopter precision, packed into 50-pair boxes, and provided without hardware cost to vetted nonprofit humanitarian and medical missions serving 40+ countries worldwide (missions cover shipping expenses and eyewear is strictly not for resale).',
    ],
];
?>
<section id="contact" class="section" aria-labelledby="contact-title" tabindex="-1">
  <div class="container">
    <div class="section-head">
      <p class="kicker">06 &middot; Contact, Forms &amp; FAQ</p>
      <h2 id="contact-title" class="h2">Reach the Watauga center or download official forms.</h2>
      <p class="lead">
        100% volunteer-run. Volunteers check voicemail and email daily and typically respond within <strong>24 hours</strong>.
      </p>
    </div>

    <div
      id="contactStatusBanner"
      class="contact-status <?= $statusParam === 'success' ? 'contact-status--success' : ($statusParam === 'error' ? 'contact-status--error' : '') ?>"
      role="<?= $statusParam === 'error' ? 'alert' : 'status' ?>"
      aria-live="polite"
      tabindex="-1"
      <?= $hasStatus ? '' : 'hidden' ?>
    >
      <div class="contact-status__inner">
        <?= erc_icon($statusParam === 'error' ? 'triangle-alert' : 'circle-check', ['width' => 20, 'height' => 20, 'class' => 'contact-status__icon']) ?>
        <p id="contactStatusText" class="contact-status__text"><?= $cntEscape($statusMessage) ?></p>
      </div>
      <button
        type="button"
        id="contactStatusDismiss"
        class="contact-status__close"
        aria-label="Dismiss notification"
      >
        <?= erc_icon('x', ['width' => 18, 'height' => 18]) ?>
      </button>
    </div>

    <div class="contact-layout">
      <!-- Left Column: Direct Channels, Forms & FAQ -->
      <div class="contact-info-col">
        <article class="card contact-channels-card">
          <h3 class="contact-card-title">Direct Contact &amp; Mailing</h3>

          <dl class="contact-channels-list">
            <div class="contact-channel-row">
              <dt class="kicker">Phone / Voicemail</dt>
              <dd class="contact-channel-val">
                <a class="contact-channel-link mono" href="tel:<?= $cntEscape($orgPhoneLink) ?>"><?= $cntEscape($orgPhone) ?></a>
                <button
                  type="button"
                  class="btn btn--secondary btn--sm js-copy-btn"
                  data-copy-text="<?= $cntEscape($orgPhone) ?>"
                  data-copy-label="phone number"
                >
                  <?= erc_icon('copy', ['width' => 15, 'height' => 15]) ?>
                  <span>Copy</span>
                </button>
              </dd>
            </div>

            <div class="contact-channel-row">
              <dt class="kicker">Email</dt>
              <dd class="contact-channel-val">
                <a class="contact-channel-link mono" href="mailto:<?= $cntEscape($orgEmail) ?>"><?= $cntEscape($orgEmail) ?></a>
                <button
                  type="button"
                  class="btn btn--secondary btn--sm js-copy-btn"
                  data-copy-text="<?= $cntEscape($orgEmail) ?>"
                  data-copy-label="email address"
                >
                  <?= erc_icon('copy', ['width' => 15, 'height' => 15]) ?>
                  <span>Copy</span>
                </button>
              </dd>
            </div>

            <div class="contact-channel-row">
              <dt class="kicker">Watauga Workshop</dt>
              <dd class="contact-channel-text">
                <strong>5621 Bunker Blvd, Watauga, TX 76148</strong><br>
                Saturdays 9:00 AM &ndash; 3:00 PM &middot; Mon&ndash;Fri by appointment &middot; Rear entrance via east-side wooden walkway.
              </dd>
            </div>

            <div class="contact-channel-row">
              <dt class="kicker">Check Donations &amp; Mail</dt>
              <dd class="contact-channel-text">
                <strong>Lions District 2-E2 ERC</strong><br>
                P.O. Box 1854, Keller, TX 76244
              </dd>
            </div>
          </dl>

          <p class="contact-response-note">
            <?= erc_icon('clock', ['width' => 15, 'height' => 15]) ?>
            <span>Typical volunteer response turnaround: within 24 hours.</span>
          </p>
          <p id="contactCopyLive" class="sr-only" aria-live="polite"></p>
        </article>

        <article class="card contact-forms-card">
          <h3 class="contact-card-title">Official Forms &amp; Documents</h3>
          <div class="forms-grid">
            <?php foreach ($officialForms as $form): ?>
              <a
                class="form-download-link"
                href="<?= $cntEscape('/files/' . $form['file']) ?>"
                target="_blank"
                rel="noopener"
              >
                <span class="form-download-link__icon" aria-hidden="true">
                  <?= erc_icon('file-text', ['width' => 18, 'height' => 18]) ?>
                </span>
                <span class="form-download-link__copy">
                  <span class="form-download-link__title"><?= $cntEscape($form['title']) ?></span>
                  <span class="form-download-link__meta"><?= $cntEscape($form['meta']) ?></span>
                </span>
                <span class="form-download-link__badge mono" aria-hidden="true">PDF</span>
              </a>
            <?php endforeach; ?>
          </div>
        </article>

        <div id="faq" class="contact-faq-block" tabindex="-1">
          <h3 class="contact-card-title">Frequently Asked Questions</h3>
          <div class="faq-accordion">
            <?php foreach ($faqs as $faq): ?>
              <details class="faq-accordion__item">
                <summary class="faq-accordion__summary"><?= $cntEscape($faq['q']) ?></summary>
                <div class="faq-accordion__body">
                  <p><?= $cntEscape($faq['a']) ?></p>
                </div>
              </details>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Right Column: Accessible Contact Form -->
      <div class="contact-form-col">
        <article class="card contact-form-card">
          <h3 class="contact-card-title">Send an Inquiry</h3>
          <p class="contact-form-intro">
            Questions about drop-off bins, group workdays, medical missions, or awards go straight to our Watauga coordinators.
          </p>

          <form
            id="contactForm"
            method="POST"
            action="/send-email.php"
            class="contact-form"
            novalidate
          >
            <div class="sr-only" aria-hidden="true">
              <label for="contactWebsiteUrl">Website</label>
              <input type="text" id="contactWebsiteUrl" name="website_url" tabindex="-1" autocomplete="off" value="">
            </div>

            <div class="field">
              <label for="contactTopic" class="field__label">Inquiry Topic</label>
              <select id="contactTopic" name="subject" class="select">
                <?php foreach ($topicCatalog as $slug => $label): ?>
                  <option value="<?= $cntEscape($slug) ?>" <?= $selectedTopic === $slug ? 'selected' : '' ?>>
                    <?= $cntEscape($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="contact-form-row">
              <div class="field">
                <label for="contactName" class="field__label">Name <span aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="contactName"
                  name="name"
                  class="input"
                  required
                  maxlength="120"
                  autocomplete="name"
                  aria-describedby="contactNameError"
                >
                <p id="contactNameError" class="vol-field-error" hidden></p>
              </div>

              <div class="field">
                <label for="contactEmail" class="field__label">Email <span aria-hidden="true">*</span></label>
                <input
                  type="email"
                  id="contactEmail"
                  name="email"
                  class="input"
                  required
                  maxlength="254"
                  autocomplete="email"
                  inputmode="email"
                  aria-describedby="contactEmailError"
                >
                <p id="contactEmailError" class="vol-field-error" hidden></p>
              </div>
            </div>

            <div class="contact-form-row">
              <div class="field">
                <label for="contactPhone" class="field__label">Phone <span class="field__hint">(optional)</span></label>
                <input
                  type="tel"
                  id="contactPhone"
                  name="phone"
                  class="input"
                  maxlength="40"
                  autocomplete="tel"
                  inputmode="tel"
                >
              </div>

              <div class="field">
                <label for="contactOrg" class="field__label">Organization / Club <span class="field__hint">(optional)</span></label>
                <input
                  type="text"
                  id="contactOrg"
                  name="organization"
                  class="input"
                  maxlength="120"
                  autocomplete="organization"
                >
              </div>
            </div>

            <div class="field">
              <div class="contact-message-head">
                <label for="contactMessage" class="field__label">Message <span aria-hidden="true">*</span></label>
                <span id="contactCharCounter" class="contact-char-counter mono">0 / 5000</span>
              </div>
              <textarea
                id="contactMessage"
                name="message"
                class="textarea"
                required
                maxlength="5000"
                rows="5"
                placeholder="Share details such as preferred Saturday, group size, drop-off bin location, or mission dates..."
                aria-describedby="contactCharCounter contactMessageError"
              ></textarea>
              <p id="contactMessageError" class="vol-field-error" hidden></p>
            </div>

            <div class="contact-form-submit">
              <button type="submit" id="contactSubmitBtn" class="btn btn--primary btn--lg contact-submit-btn">
                <?= erc_icon('send', ['width' => 17, 'height' => 17]) ?>
                <span>Send Message</span>
              </button>
            </div>
          </form>
        </article>
      </div>
    </div>
  </div>
</section>
