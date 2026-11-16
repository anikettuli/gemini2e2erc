<?php
declare(strict_types=1);

$volEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$volMediaUrl = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$orgPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$orgPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');
$orgEmail = (string) ($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');

$rawEvents = function_exists('erc_load_json')
    ? erc_load_json(ERC_DATA_DIR . '/events.json', [])
    : [];
$rawSignups = function_exists('erc_load_json')
    ? erc_load_json(ERC_DATA_DIR . '/signups.json', [])
    : [];

if (!is_array($rawEvents)) {
    $rawEvents = [];
}
if (!is_array($rawSignups)) {
    $rawSignups = [];
}

$allEvents = [];
foreach ($rawEvents as $ev) {
    if (!is_array($ev)) {
        continue;
    }
    $id = (int) ($ev['id'] ?? 0);
    $date = trim((string) ($ev['date'] ?? ''));
    if ($id <= 0 || $date === '') {
        continue;
    }
    $key = (string) $id;
    $roster = (isset($rawSignups[$key]) && is_array($rawSignups[$key])) ? $rawSignups[$key] : [];
    $people = max(count($roster), (int) ($ev['people'] ?? 0));
    $maxPeople = max(0, (int) ($ev['maxPeople'] ?? 0));
    $imageRel = trim((string) ($ev['image'] ?? 'images/placeholder-event.svg'));
    if ($imageRel === '') {
        $imageRel = 'images/placeholder-event.svg';
    }

    $allEvents[] = [
        'id'          => $id,
        'title'       => trim((string) ($ev['title'] ?? 'Volunteer Session')),
        'date'        => $date,
        'time'        => trim((string) ($ev['time'] ?? '9:00 AM - 12:00 PM')),
        'people'      => $people,
        'maxPeople'   => $maxPeople,
        'location'    => trim((string) ($ev['location'] ?? 'Main Center - 5621 Bunker Blvd, Watauga, TX 76148')),
        'image'       => $volMediaUrl($imageRel),
        'description' => trim((string) ($ev['description'] ?? '')),
        'contact'     => trim((string) ($ev['contact'] ?? $orgEmail)),
    ];
}

usort($allEvents, static function (array $a, array $b): int {
    $dateCmp = strcmp($a['date'], $b['date']);
    if ($dateCmp !== 0) {
        return $dateCmp;
    }
    return $a['id'] <=> $b['id'];
});

$todayYmd = date('Y-m-d');
$futureEvents = array_values(array_filter(
    $allEvents,
    static fn (array $e): bool => $e['date'] >= $todayYmd
));
$displayEvents = !empty($futureEvents) ? $futureEvents : $allEvents;
$totalScheduledCount = count($displayEvents);
$initialVisibleCap = 4;

// Helper to parse YYYY-MM-DD + "9:00 AM - 12:00 PM" into America/Chicago GCal timestamps
$buildGCalUrl = static function (array $ev): string {
    $date = $ev['date'];
    $time = $ev['time'];
    $startHm = '090000';
    $endHm = '120000';
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $m)) {
        $to24 = static function (int $h, int $min, string $ampm): string {
            $u = strtoupper($ampm);
            if ($u === 'PM' && $h < 12) {
                $h += 12;
            } elseif ($u === 'AM' && $h === 12) {
                $h = 0;
            }
            return sprintf('%02d%02d00', $h, $min);
        };
        $startHm = $to24((int) $m[1], (int) $m[2], $m[3]);
        $endHm = $to24((int) $m[4], (int) $m[5], $m[6]);
    }
    $compactDate = str_replace('-', '', $date);
    $dates = $compactDate . 'T' . $startHm . '/' . $compactDate . 'T' . $endHm;
    return 'https://calendar.google.com/calendar/render?' . http_build_query([
        'action'   => 'TEMPLATE',
        'text'     => $ev['title'] . ' — Lions 2-E2 ERC',
        'dates'    => $dates,
        'ctz'      => 'America/Chicago',
        'location' => $ev['location'],
        'details'  => $ev['description'] !== '' ? $ev['description'] : 'Lions District 2-E2 Eyeglass Recycling Center volunteer shift. Access via east-side wooden walkway to rear entrance.',
    ]);
};

// Scan workshop gallery photos via shared coexistence-aware helper
$galleryPaths = function_exists('erc_scan_gallery_images') ? erc_scan_gallery_images() : [];
$galleryCaptions = [
    'gallery/2nd-saturday-group.jpg'   => '2nd Saturday open volunteer group sorting donated eyewear in Watauga.',
    'gallery/bsa-troop-76.jpg'         => 'BSA Troop 76 Scouts helping sort and pack donated eyeglasses.',
    'gallery/coahuila-bound.jpg'       => 'Packaged eyewear ready for humanitarian mission dispatch to Coahuila.',
    'gallery/colleyville-erc-2024.jpg' => 'Colleyville Lions Club volunteers serving at the recycling center.',
    'gallery/computers.jpg'            => 'Volunteers inspecting and preparing donated Windows 11 laptops and tablets.',
    'gallery/drying-glasses.jpg'       => 'Sanitized eyeglasses arranged on drying racks after washing.',
    'gallery/founders-workday.jpg'     => 'Fort Worth Founder Lions Club members on a Saturday workshop shift.',
    'gallery/grapevine-erc-2025.jpg'   => 'Grapevine volunteers working together on prescription sorting.',
    'gallery/kids-sorting.jpg'         => 'Student volunteers sorting donated frames and sunglasses by category.',
    'gallery/kumc-kids.jpg'            => 'Keller United Methodist youth group sorting donated eyeglasses.',
    'gallery/lensometers.jpg'          => 'Volunteers measuring exact lens prescriptions at the lensometer stations.',
    'gallery/mckinney-mp1.jpg'         => 'McKinney Lions volunteers assisting with mission box preparation.',
    'gallery/metroport-ncl.jpg'        => 'DFW Metroport National Charity League volunteers at the Watauga workshop.',
    'gallery/midland-delivery.jpg'     => 'Bulk eyeglass donation delivery arriving from Midland Lions partners.',
    'gallery/panama-bound.jpg'         => 'Refurbished 50-pair eyewear boxes packed for a clinic mission in Panama.',
    'gallery/past-district-governors.jpg' => 'Past District Governors of District 2-E2 volunteering at the center.',
    'gallery/peru-bound.jpg'           => 'Prescription glasses packed by diopter increment for medical clinics in Peru.',
];

$galleryItems = [];
foreach ($galleryPaths as $relPath) {
    $lower = strtolower($relPath);
    $fallbackTitle = ucwords(str_replace(['-', '_'], ' ', (string) pathinfo($relPath, PATHINFO_FILENAME)));
    $galleryItems[] = [
        'src'     => $volMediaUrl($relPath),
        'path'    => $relPath,
        'caption' => $galleryCaptions[$lower] ?? ($fallbackTitle . ' — Lions District 2-E2 Workshop'),
    ];
}

// Pick 6 representative workshop photos for the compact preview strip
$preferredStrip = [
    'gallery/2nd-saturday-group.jpg',
    'gallery/drying-glasses.jpg',
    'gallery/lensometers.jpg',
    'gallery/kids-sorting.jpg',
    'gallery/founders-workday.jpg',
    'gallery/peru-bound.jpg',
];
$stripItems = [];
$usedIndices = [];
foreach ($preferredStrip as $pref) {
    foreach ($galleryItems as $idx => $item) {
        if (strtolower($item['path']) === $pref) {
            $stripItems[] = ['index' => $idx, 'item' => $item];
            $usedIndices[$idx] = true;
            break;
        }
    }
}
if (count($stripItems) < 6) {
    foreach ($galleryItems as $idx => $item) {
        if (isset($usedIndices[$idx])) {
            continue;
        }
        $stripItems[] = ['index' => $idx, 'item' => $item];
        if (count($stripItems) >= 6) {
            break;
        }
    }
}
?>
<section id="volunteer" class="section" aria-labelledby="volunteer-title" tabindex="-1">
  <div class="container">
    <div class="vol-header-row">
      <div class="section-head vol-section-head">
        <p class="kicker">04 &middot; Volunteer Saturdays</p>
        <h2 id="volunteer-title" class="h2">Join a Saturday workshop in Watauga.</h2>
        <p class="lead">
          Open Saturdays <strong>9:00 AM &ndash; 3:00 PM</strong> (morning <span class="mono">9&ndash;12</span> and afternoon <span class="mono">12&ndash;3</span> shifts).
          Zero prior optical experience is needed&mdash;volunteers learn sorting, washing, lensometry, and packing in minutes.
          Groups up to <span class="mono">20</span> are welcome at <strong>5621 Bunker Blvd</strong> (free parking; follow the wooden walkway along the east side of the building to the rear entrance).
        </p>
      </div>

      <div class="vol-group-actions">
        <a
          class="btn btn--secondary btn--sm"
          href="/files/Group-Volunteer-Form.pdf"
          target="_blank"
          rel="noopener"
        >
          <?= erc_icon('file-text', ['width' => 16, 'height' => 16]) ?>
          <span>Group Form (PDF)</span>
        </a>
        <a
          class="btn btn--ghost btn--sm"
          href="#contact"
          data-prefill-topic="group-volunteer"
        >
          <?= erc_icon('users', ['width' => 16, 'height' => 16]) ?>
          <span>Schedule a Group (Up to 20)</span>
        </a>
      </div>
    </div>

    <div class="vol-workspace">
      <!-- Left: Interactive Month Calendar -->
      <aside class="card vol-cal-card" aria-label="Volunteer shift calendar">
        <div class="vol-cal-head">
          <div class="vol-cal-nav">
            <button
              type="button"
              id="volCalPrevBtn"
              class="vol-cal-nav-btn"
              aria-label="Previous month"
            >
              <?= erc_icon('chevron-left', ['width' => 18, 'height' => 18]) ?>
            </button>
            <h3 id="volCalMonthLabel" class="vol-cal-month" aria-live="polite">Schedule</h3>
            <button
              type="button"
              id="volCalNextBtn"
              class="vol-cal-nav-btn"
              aria-label="Next month"
            >
              <?= erc_icon('chevron-right', ['width' => 18, 'height' => 18]) ?>
            </button>
          </div>
          <button
            type="button"
            id="volShowAllBtn"
            class="pill-tab is-active vol-reset-pill"
            aria-pressed="true"
          >
            All Upcoming
          </button>
        </div>

        <div class="vol-cal-weekdays" aria-hidden="true">
          <span>Sun</span>
          <span>Mon</span>
          <span>Tue</span>
          <span>Wed</span>
          <span>Thu</span>
          <span>Fri</span>
          <span>Sat</span>
        </div>

        <div id="volCalGrid" class="vol-cal-grid" role="group" aria-label="Scheduled volunteer dates"></div>

        <div class="vol-cal-footer">
          <span class="vol-cal-legend">
            <span class="vol-cal-legend__dot" aria-hidden="true"></span>
            <span>Scheduled shift</span>
          </span>
          <span id="volFilterStatus" class="vol-cal-status mono" aria-live="polite">
            Showing next <?= $volEscape(min($initialVisibleCap, $totalScheduledCount)) ?> of <?= $volEscape($totalScheduledCount) ?>
          </span>
        </div>
      </aside>

      <!-- Right: Bounded Upcoming Sessions List -->
      <div class="vol-sessions-col">
        <div class="vol-sessions-bar">
          <h3 class="vol-sessions-title">Upcoming Sessions</h3>
          <span id="volSessionsSummary" class="badge badge--brand mono">
            <?= $volEscape($totalScheduledCount) ?> scheduled date<?= $totalScheduledCount === 1 ? '' : 's' ?>
          </span>
        </div>

        <div id="volSessionsList" class="vol-sessions-list">
          <?php if (empty($displayEvents)): ?>
            <article class="card vol-empty-card">
              <p>No volunteer shifts are posted yet. Drop by Saturdays 9:00 AM &ndash; 3:00 PM or call <a href="tel:<?= $volEscape($orgPhoneLink) ?>"><?= $volEscape($orgPhone) ?></a>.</p>
            </article>
          <?php else: ?>
            <?php foreach ($displayEvents as $evIdx => $ev):
              $isHiddenInitial = $evIdx >= $initialVisibleCap;
              $isFull = $ev['maxPeople'] > 0 && $ev['people'] >= $ev['maxPeople'];
              $remaining = $ev['maxPeople'] > 0 ? max(0, $ev['maxPeople'] - $ev['people']) : 0;
              $pct = ($ev['maxPeople'] > 0) ? min(100, (int) round(($ev['people'] / $ev['maxPeople']) * 100)) : 0;
              $dtObj = \DateTimeImmutable::createFromFormat('Y-m-d', $ev['date']);
              $formattedDate = $dtObj ? $dtObj->format('D, M j, Y') : $ev['date'];
              $popoverId = 'volCalPop-' . $ev['id'];
            ?>
              <article
                class="card vol-session-card"
                data-event-id="<?= $volEscape($ev['id']) ?>"
                data-event-date="<?= $volEscape($ev['date']) ?>"
                <?= $isHiddenInitial ? 'hidden' : '' ?>
              >
                <div class="vol-session-card__main">
                  <img
                    src="<?= $volEscape($ev['image']) ?>"
                    alt=""
                    width="56"
                    height="56"
                    loading="eager"
                    decoding="async"
                    data-fallback-src="/images/placeholder-event.svg"
                    class="vol-session-card__thumb"
                  >
                  <div class="vol-session-card__body">
                    <div class="vol-session-card__top">
                      <p class="vol-session-card__when mono">
                        <?= $volEscape($formattedDate) ?> &middot; <?= $volEscape($ev['time']) ?>
                      </p>
                      <span class="badge <?= $isFull ? 'vol-badge--full' : 'badge--brand' ?> js-vol-seats-badge mono">
                        <?= $volEscape($ev['people']) ?> / <?= $volEscape($ev['maxPeople']) ?> spots
                      </span>
                    </div>
                    <h4 class="vol-session-card__title"><?= $volEscape($ev['title']) ?></h4>
                    <?php if ($ev['description'] !== ''): ?>
                      <p class="vol-session-card__desc"><?= $volEscape($ev['description']) ?></p>
                    <?php endif; ?>
                    <p class="vol-session-card__loc">
                      <?= erc_icon('map-pin', ['width' => 14, 'height' => 14]) ?>
                      <span><?= $volEscape($ev['location']) ?></span>
                    </p>
                  </div>
                </div>

                <div class="vol-session-card__footer">
                  <div class="vol-meter-wrap" aria-label="Session capacity">
                    <div class="vol-meter">
                      <div class="vol-meter__fill js-vol-meter-fill" style="--vol-fill: <?= $volEscape($pct) ?>%;"></div>
                    </div>
                    <span class="vol-meter__label mono js-vol-remaining">
                      <?= $isFull ? 'Session full' : ($volEscape($remaining) . ' spots open') ?>
                    </span>
                  </div>

                  <div class="vol-session-card__actions">
                    <div class="vol-cal-menu-wrap">
                      <button
                        type="button"
                        class="btn btn--secondary btn--sm vol-cal-popover-trigger"
                        popovertarget="<?= $volEscape($popoverId) ?>"
                      >
                        <?= erc_icon('calendar-plus', ['width' => 16, 'height' => 16]) ?>
                        <span>Calendar</span>
                      </button>
                      <div id="<?= $volEscape($popoverId) ?>" class="vol-cal-popover" popover="auto">
                        <button
                          type="button"
                          class="vol-cal-popover__item js-vol-ics"
                          data-event-id="<?= $volEscape($ev['id']) ?>"
                        >
                          <?= erc_icon('download', ['width' => 15, 'height' => 15]) ?>
                          <span>Download .ics</span>
                        </button>
                        <a
                          class="vol-cal-popover__item"
                          href="<?= $volEscape($buildGCalUrl($ev)) ?>"
                          target="_blank"
                          rel="noopener"
                        >
                          <?= erc_icon('external-link', ['width' => 15, 'height' => 15]) ?>
                          <span>Google Calendar</span>
                        </a>
                      </div>
                    </div>

                    <button
                      type="button"
                      class="btn btn--primary btn--sm js-vol-signup"
                      data-event-id="<?= $volEscape($ev['id']) ?>"
                      <?= $isFull ? 'disabled aria-disabled="true"' : '' ?>
                    >
                      <?= $isFull ? 'Session Full' : 'Sign Up' ?>
                    </button>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($totalScheduledCount > $initialVisibleCap): ?>
          <div class="vol-sessions-toggle-row">
            <button
              type="button"
              id="volToggleAllBtn"
              class="btn btn--secondary btn--sm"
              aria-expanded="false"
            >
              Show All <?= $volEscape($totalScheduledCount) ?> Scheduled Dates
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Compact 6-Photo Workshop Strip -->
    <?php if (!empty($stripItems)): ?>
      <div class="vol-gallery-strip" aria-label="Workshop photo preview">
        <div class="vol-gallery-strip__head">
          <div>
            <p class="kicker">Inside the Watauga Workshop</p>
            <h3 class="vol-gallery-strip__title">Volunteers in action across sorting, lensometry &amp; mission packing.</h3>
          </div>
          <button
            type="button"
            id="volOpenLightboxBtn"
            class="btn btn--secondary btn--sm"
            data-gallery-open="0"
          >
            <?= erc_icon('zoom-in', ['width' => 16, 'height' => 16]) ?>
            <span>View All <?= $volEscape(count($galleryItems)) ?> Workshop Photos</span>
          </button>
        </div>

        <div class="vol-gallery-grid">
          <?php foreach ($stripItems as $entry):
            $gIdx = $entry['index'];
            $gItem = $entry['item'];
          ?>
            <button
              type="button"
              class="vol-gallery-thumb"
              data-gallery-open="<?= $volEscape($gIdx) ?>"
              aria-label="Open workshop photo <?= $volEscape($gIdx + 1) ?>: <?= $volEscape($gItem['caption']) ?>"
            >
              <img
                src="<?= $volEscape($gItem['src']) ?>"
                alt="<?= $volEscape($gItem['caption']) ?>"
                width="320"
                height="240"
                loading="eager"
                decoding="async"
                class="vol-gallery-img"
              >
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Embedded Non-PII Volunteer Schedule & Gallery Metadata -->
  <script type="application/json" id="volunteerEventsData"><?= json_encode($displayEvents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <script type="application/json" id="volunteerGalleryData"><?= json_encode($galleryItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

  <!-- Native Registration Modal (Top-anchored for mobile keyboard stability) -->
  <dialog
    id="volunteerRegDialog"
    class="drawer vol-reg-dialog"
    closedby="any"
    aria-labelledby="volRegTitle"
  >
    <div class="drawer__header">
      <h3 id="volRegTitle" class="drawer__title">Sign Up for Volunteer Shift</h3>
      <button
        type="button"
        id="volRegCloseBtn"
        class="drawer__close"
        data-dialog-close
        aria-label="Close registration modal"
      >
        <?= erc_icon('x', ['width' => 20, 'height' => 20]) ?>
      </button>
    </div>

    <div class="drawer__body vol-reg-body">
      <div id="volRegSummary" class="vol-reg-summary"></div>

      <div id="volRegAlert" class="vol-alert" role="alert" hidden></div>

      <form id="volunteerRegForm" class="vol-reg-form" novalidate>
        <input type="hidden" id="regEventId" name="eventId" value="">
        <div class="sr-only" aria-hidden="true">
          <label for="regWebsiteUrl">Website</label>
          <input type="text" id="regWebsiteUrl" name="website_url" tabindex="-1" autocomplete="off" value="">
        </div>

        <div class="field">
          <label for="regName" class="field__label">Full Name <span aria-hidden="true">*</span></label>
          <input
            type="text"
            id="regName"
            name="name"
            class="input"
            required
            maxlength="120"
            autocomplete="name"
            aria-describedby="regNameError"
          >
          <p id="regNameError" class="vol-field-error" hidden></p>
        </div>

        <div class="field">
          <label for="regEmail" class="field__label">Email Address <span aria-hidden="true">*</span></label>
          <input
            type="email"
            id="regEmail"
            name="email"
            class="input"
            required
            maxlength="254"
            autocomplete="email"
            inputmode="email"
            aria-describedby="regEmailError"
          >
          <p id="regEmailError" class="vol-field-error" hidden></p>
        </div>

        <div class="vol-reg-row">
          <div class="field">
            <label for="regPhone" class="field__label">Phone <span class="field__hint">(optional)</span></label>
            <input
              type="tel"
              id="regPhone"
              name="phone"
              class="input"
              maxlength="40"
              autocomplete="tel"
              inputmode="tel"
              aria-describedby="regPhoneError"
            >
            <p id="regPhoneError" class="vol-field-error" hidden></p>
          </div>

          <div class="field">
            <label for="regCount" class="field__label">Party Size <span class="field__hint">(1&ndash;20)</span></label>
            <input
              type="number"
              id="regCount"
              name="count"
              class="input"
              min="1"
              max="20"
              value="1"
              inputmode="numeric"
              aria-describedby="regCountHint"
            >
            <p id="regCountHint" class="field__hint">Online form reserves 1 individual spot; groups 2&ndash;20 see note below.</p>
          </div>
        </div>

        <div id="regGroupNote" class="vol-group-note" hidden>
          <p>
            <strong>Bringing a group of <span id="regGroupCountLabel">2</span>?</strong>
            Submitting this form registers you as the group contact. To reserve spots for your entire group (up to 20), email the
            <a href="/files/Group-Volunteer-Form.pdf" target="_blank" rel="noopener">Group Volunteer Form (PDF)</a>
            to <a href="mailto:<?= $volEscape($orgEmail) ?>"><?= $volEscape($orgEmail) ?></a> or call <a href="tel:<?= $volEscape($orgPhoneLink) ?>"><?= $volEscape($orgPhone) ?></a>.
          </p>
        </div>

        <div class="vol-reg-actions">
          <button type="submit" id="regSubmitBtn" class="btn btn--primary btn--lg">
            Confirm Registration
          </button>
          <button type="button" class="btn btn--secondary" data-dialog-close>
            Cancel
          </button>
        </div>
      </form>

      <div id="volRegSuccess" class="vol-reg-success" hidden>
        <div class="vol-reg-success__banner" role="status">
          <?= erc_icon('circle-check', ['width' => 22, 'height' => 22]) ?>
          <div>
            <h4>You are registered!</h4>
            <p id="volRegSuccessMsg">We have emailed your confirmation and look forward to seeing you in Watauga.</p>
          </div>
        </div>
        <div class="vol-reg-success__links">
          <button type="button" id="volRegSuccessIcs" class="btn btn--secondary btn--sm">
            <?= erc_icon('download', ['width' => 16, 'height' => 16]) ?>
            <span>Download .ics</span>
          </button>
          <a id="volRegSuccessGCal" class="btn btn--secondary btn--sm" href="#" target="_blank" rel="noopener">
            <?= erc_icon('calendar', ['width' => 16, 'height' => 16]) ?>
            <span>Google Calendar</span>
          </a>
          <button type="button" class="btn btn--primary btn--sm" data-dialog-close>
            Done
          </button>
        </div>
      </div>
    </div>
  </dialog>

  <!-- Native 17-Photo Lightbox Modal -->
  <dialog
    id="volunteerLightbox"
    class="drawer vol-lightbox"
    closedby="any"
    aria-labelledby="volLightboxTitle"
  >
    <div class="drawer__header">
      <div class="vol-lightbox__title-group">
        <h3 id="volLightboxTitle" class="drawer__title">Workshop &amp; Mission Photos</h3>
        <span id="volLightboxCounter" class="badge mono">1 / <?= $volEscape(count($galleryItems)) ?></span>
      </div>
      <button
        type="button"
        id="volLightboxCloseBtn"
        class="drawer__close"
        data-dialog-close
        aria-label="Close photo gallery"
      >
        <?= erc_icon('x', ['width' => 20, 'height' => 20]) ?>
      </button>
    </div>

    <div class="drawer__body vol-lightbox__body">
      <div class="vol-lightbox__viewer">
        <button
          type="button"
          id="volLightboxPrev"
          class="vol-lightbox__nav"
          aria-label="Previous photo"
        >
          <?= erc_icon('chevron-left', ['width' => 22, 'height' => 22]) ?>
        </button>

        <figure class="vol-lightbox__figure">
          <img
            id="volLightboxImg"
            src="<?= $volEscape($galleryItems[0]['src'] ?? '') ?>"
            alt="<?= $volEscape($galleryItems[0]['caption'] ?? 'Workshop photo') ?>"
            loading="eager"
            decoding="async"
            class="vol-lightbox__img"
          >
          <figcaption id="volLightboxCaption" class="vol-lightbox__caption">
            <?= $volEscape($galleryItems[0]['caption'] ?? '') ?>
          </figcaption>
        </figure>

        <button
          type="button"
          id="volLightboxNext"
          class="vol-lightbox__nav"
          aria-label="Next photo"
        >
          <?= erc_icon('chevron-right', ['width' => 22, 'height' => 22]) ?>
        </button>
      </div>

      <div id="volLightboxThumbs" class="vol-lightbox__thumbs" aria-label="Gallery thumbnails"></div>
    </div>
  </dialog>
</section>
