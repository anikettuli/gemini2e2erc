<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$siteConfig = function_exists('erc_get_site_config') ? erc_get_site_config() : [];
$contactEmail = (string)($GLOBALS['GLOBAL_EMAIL'] ?? $siteConfig['email'] ?? '2e2erc1854@gmail.com');
$contactPhone = (string)($GLOBALS['GLOBAL_PHONE'] ?? $siteConfig['phone'] ?? '(817) 710-5403');
$contactPhoneLink = (string)($GLOBALS['GLOBAL_PHONE_LINK'] ?? $siteConfig['phoneLink'] ?? '+18177105403');

/**
 * Parse start and end wall-clock times (HHMMSS) from an event time range string.
 *
 * @return array{start: string, end: string}
 */
function erc_volunteer_parse_time_range(string $timeStr): array
{
    $default = ['start' => '090000', 'end' => '120000'];
    $normalized = trim(str_replace(['–', '—', 'to'], ['-', '-', '-'], $timeStr));
    if ($normalized === '') {
        return $default;
    }

    preg_match_all('/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)?/i', $normalized, $matches, PREG_SET_ORDER);
    if (empty($matches)) {
        return $default;
    }

    $toMinutes = static function (array $m, ?string $fallbackMeridiem = null): int {
        $hour = (int)$m[1];
        $min = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : 0;
        $meridiem = isset($m[3]) && $m[3] !== '' ? strtoupper($m[3]) : $fallbackMeridiem;
        if ($meridiem === 'PM' && $hour < 12) {
            $hour += 12;
        } elseif ($meridiem === 'AM' && $hour === 12) {
            $hour = 0;
        }
        $hour = max(0, min(23, $hour));
        $min = max(0, min(59, $min));
        return ($hour * 60) + $min;
    };

    $endMeridiem = isset($matches[1][3]) && $matches[1][3] !== '' ? strtoupper($matches[1][3]) : null;
    $startMins = $toMinutes($matches[0], $endMeridiem);
    $endMins = isset($matches[1]) ? $toMinutes($matches[1], null) : ($startMins + 180);
    if ($endMins <= $startMins) {
        $endMins = min(23 * 60 + 59, $startMins + 180);
    }

    $format = static function (int $totalMins): string {
        $h = intdiv($totalMins, 60);
        $m = $totalMins % 60;
        return sprintf('%02d%02d00', $h, $m);
    };

    return [
        'start' => $format($startMins),
        'end'   => $format($endMins),
    ];
}

/**
 * Build a Google Calendar template URL anchored to America/Chicago.
 */
function erc_volunteer_gcal_url(array $event): string
{
    $dateDigits = str_replace('-', '', (string)$event['date']);
    if (!preg_match('/^\d{8}$/', $dateDigits)) {
        return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode((string)$event['title']);
    }
    $range = erc_volunteer_parse_time_range((string)$event['time']);
    $params = [
        'action'   => 'TEMPLATE',
        'text'     => (string)$event['title'],
        'dates'    => $dateDigits . 'T' . $range['start'] . '/' . $dateDigits . 'T' . $range['end'],
        'ctz'      => 'America/Chicago',
        'details'  => trim((string)$event['description'] . "\n\nParking & Access: Free on-site parking. Follow the wooden walkway along the east side of the building to the rear entrance."),
        'location' => (string)$event['location'],
    ];
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Classify a volunteer session into a domain ticket category & tone.
 *
 * @return array{tag: string, tone: string}
 */
function erc_volunteer_ticket_meta(string $title, string $description): array
{
    $hay = strtolower($title . ' ' . $description);
    if (str_contains($hay, '2nd saturday') || str_contains($hay, 'open session')) {
        return ['tag' => 'OPEN SHIFT', 'tone' => 'open'];
    }
    if (str_contains($hay, 'board') || str_contains($hay, 'pdg') || str_contains($hay, 'governor')) {
        return ['tag' => 'LEADERSHIP', 'tone' => 'board'];
    }
    if (str_contains($hay, 'ncl') || str_contains($hay, 'charity league')) {
        return ['tag' => 'NCL CHAPTER', 'tone' => 'ncl'];
    }
    if (str_contains($hay, 'lions')) {
        return ['tag' => 'LIONS CREW', 'tone' => 'lions'];
    }
    return ['tag' => 'GROUP SHIFT', 'tone' => 'partner'];
}

/**
 * Derive a clean, descriptive caption and filmstrip note from a gallery image path.
 *
 * @return array{caption: string, note: string}
 */
function erc_volunteer_gallery_meta(string $relativePath): array
{
    $filename = basename($relativePath);
    static $knownMeta = [
        '2nd-saturday-group.jpg'      => ['caption' => '2nd Saturday Open Session Volunteers', 'note' => 'walk-ins welcome'],
        'bsa-troop-76.jpg'            => ['caption' => 'BSA Troop 76 Service Day', 'note' => 'scouting crew'],
        'coahuila-bound.jpg'          => ['caption' => 'Coahuila Mission Shipment Ready', 'note' => 'boxed for Mexico'],
        'colleyville-erc-2024.jpg'    => ['caption' => 'Colleyville Lions ERC Service Day (2024)', 'note' => 'club shift'],
        'computers.jpg'               => ['caption' => 'Donated Electronics Intake Station', 'note' => 'w11 laptops & phones'],
        'drying-glasses.jpg'          => ['caption' => 'Washing, Sanitizing & Rack Drying Eyeglasses', 'note' => 'station 02'],
        'founders-workday.jpg'        => ['caption' => 'Ft. Worth Founder Lions Workday', 'note' => 'lions at bench'],
        'grapevine-erc-2025.jpg'      => ['caption' => 'Grapevine Lions Service Day (2025)', 'note' => '0.25D sorting'],
        'kids-sorting.jpg'            => ['caption' => 'Youth Volunteers Sorting Donated Eyewear', 'note' => 'all hands on'],
        'kumc-kids.jpg'               => ['caption' => 'Keller United Methodist Youth Group Sorting', 'note' => 'youth service'],
        'lensometers.jpg'             => ['caption' => 'Measuring Prescriptions on Tabletop Lensometers', 'note' => 'station 03'],
        'mckinney-mp1.jpg'            => ['caption' => 'McKinney Lions Volunteer Team', 'note' => 'partner club'],
        'metroport-ncl.jpg'           => ['caption' => 'DFW Metroport NCL Chapter Volunteers', 'note' => 'regular crew'],
        'midland-delivery.jpg'        => ['caption' => 'Midland Eyeglass Delivery', 'note' => 'bulk intake'],
        'panama-bound.jpg'            => ['caption' => 'Panama Mission Box Preparation', 'note' => '50 pairs / box'],
        'past-district-governors.jpg' => ['caption' => 'Past District Governors Service Day', 'note' => 'pdg shift'],
        'peru-bound.jpg'              => ['caption' => 'Peru Medical Mission Shipment', 'note' => 'chincha bound'],
    ];
    $key = strtolower($filename);
    if (isset($knownMeta[$key])) {
        return $knownMeta[$key];
    }

    $stem = preg_replace('/\.[^.]+$/', '', $filename) ?? $filename;
    $stem = preg_replace('/[-_]+/', ' ', $stem) ?? $stem;
    $stem = trim((string)preg_replace('/\s+/', ' ', $stem));
    if ($stem === '') {
        return ['caption' => 'ERC Volunteer Workshop Photo', 'note' => 'watauga workshop'];
    }
    $words = explode(' ', $stem);
    $acronyms = ['erc', 'ncl', 'bsa', 'dfw', 'saso', 'pdg', 'kumc'];
    foreach ($words as &$w) {
        $lw = strtolower($w);
        if (in_array($lw, $acronyms, true)) {
            $w = strtoupper($lw);
        } else {
            $w = ucfirst($lw);
        }
    }
    unset($w);
    return ['caption' => implode(' ', $words), 'note' => 'workshop archive'];
}

$chicagoTz = new DateTimeZone('America/Chicago');
$todayIso = (new DateTimeImmutable('now', $chicagoTz))->format('Y-m-d');
$rawEvents = erc_load_json('data/events.json', []);
if (!is_array($rawEvents)) {
    $rawEvents = [];
}

$normalizedEvents = [];
foreach ($rawEvents as $ev) {
    if (!is_array($ev)) {
        continue;
    }
    $id = (int)($ev['id'] ?? 0);
    $dateStr = trim((string)($ev['date'] ?? ''));
    if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        continue;
    }
    $title = trim((string)($ev['title'] ?? 'Volunteer Session'));
    $timeStr = trim((string)($ev['time'] ?? '9:00 AM - 12:00 PM'));
    $people = max(0, (int)($ev['people'] ?? 0));
    $maxPeople = max(0, (int)($ev['maxPeople'] ?? 0));
    $location = trim((string)($ev['location'] ?? 'Main Center - 5621 Bunker Blvd, Watauga, TX 76148'));
    $rawImage = trim((string)($ev['image'] ?? ''));
    if ($rawImage === '') {
        $rawImage = 'images/placeholder-event.svg';
    }
    $imageUrl = erc_media_url($rawImage);
    $isLogo = (bool)preg_match('/(-logo\.|eagle-scout|placeholder-event\.svg$)/i', $rawImage);
    $description = trim((string)($ev['description'] ?? ''));
    $evContact = trim((string)($ev['contact'] ?? $contactEmail));

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $dateStr, $chicagoTz);
    $formattedDate = $dt ? $dt->format('D, M j, Y') : $dateStr;
    $monthKey = substr($dateStr, 0, 7);
    $monthLabel = $dt ? $dt->format('F Y') : $monthKey;

    $isPast = $dateStr < $todayIso;
    $isFull = $maxPeople > 0 && $people >= $maxPeople;
    $remaining = $maxPeople > 0 ? max(0, $maxPeople - $people) : null;
    $range = erc_volunteer_parse_time_range($timeStr);
    $ticketMeta = erc_volunteer_ticket_meta($title, $description);

    $item = [
        'id'            => $id,
        'title'         => $title,
        'date'          => $dateStr,
        'formattedDate' => $formattedDate,
        'monthKey'      => $monthKey,
        'monthLabel'    => $monthLabel,
        'time'          => $timeStr,
        'startTime'     => $range['start'],
        'endTime'       => $range['end'],
        'people'        => $people,
        'maxPeople'     => $maxPeople,
        'remaining'     => $remaining,
        'isFull'        => $isFull,
        'isPast'        => $isPast,
        'location'      => $location,
        'image'         => $rawImage,
        'imageUrl'      => $imageUrl,
        'isLogo'        => $isLogo,
        'description'   => $description,
        'contact'       => $evContact,
        'ticketTag'     => $ticketMeta['tag'],
        'ticketTone'    => $ticketMeta['tone'],
    ];
    $item['gcalUrl'] = erc_volunteer_gcal_url($item);
    $normalizedEvents[] = $item;
}

usort($normalizedEvents, static function (array $a, array $b): int {
    $dateCmp = strcmp($a['date'], $b['date']);
    if ($dateCmp !== 0) {
        return $dateCmp;
    }
    $timeCmp = strcmp($a['startTime'], $b['startTime']);
    if ($timeCmp !== 0) {
        return $timeCmp;
    }
    return $a['id'] <=> $b['id'];
});

// Assign display serial index 01..NN after chronological sort
foreach ($normalizedEvents as $idx => &$evRef) {
    $evRef['serial'] = sprintf('SHIFT · %02d', $idx + 1);
    $evRef['passCode'] = sprintf('SHIFT-%04d', (int)$evRef['id']);
}
unset($evRef);

$upcomingEvents = array_values(array_filter($normalizedEvents, static fn(array $e): bool => !$e['isPast']));
$hasUpcoming = count($upcomingEvents) > 0;
$defaultScope = $hasUpcoming ? 'upcoming' : 'all';

if ($hasUpcoming) {
    $defaultMonth = $upcomingEvents[0]['monthKey'];
} elseif (!empty($normalizedEvents)) {
    $lastEvent = $normalizedEvents[count($normalizedEvents) - 1];
    $defaultMonth = $lastEvent['monthKey'];
} else {
    $defaultMonth = substr($todayIso, 0, 7);
}

$eventMonths = [];
foreach ($normalizedEvents as $ev) {
    $mk = $ev['monthKey'];
    if (!isset($eventMonths[$mk])) {
        $eventMonths[$mk] = [
            'key'   => $mk,
            'label' => $ev['monthLabel'],
            'count' => 0,
        ];
    }
    $eventMonths[$mk]['count']++;
}
$eventMonthsList = array_values($eventMonths);

$rawGallery = function_exists('erc_scan_gallery_images') ? erc_scan_gallery_images() : [];
if (!is_array($rawGallery)) {
    $rawGallery = [];
}
$galleryItems = [];
foreach ($rawGallery as $relPath) {
    $rel = ltrim((string)$relPath, '/');
    if ($rel === '') {
        continue;
    }
    $idx = count($galleryItems);
    $gMeta = erc_volunteer_gallery_meta($rel);
    $galleryItems[] = [
        'index'   => $idx,
        'frameId' => sprintf('FRM-%02d', $idx + 1),
        'path'    => $rel,
        'url'     => erc_media_url($rel),
        'caption' => $gMeta['caption'],
        'note'    => $gMeta['note'],
    ];
}

$eventsPayload = [
    'today'        => $todayIso,
    'defaultMonth' => $defaultMonth,
    'defaultScope' => $defaultScope,
    'phone'        => $contactPhone,
    'email'        => $contactEmail,
    'events'       => $normalizedEvents,
    'gallery'      => $galleryItems,
];

$safeJsonPayload = json_encode(
    $eventsPayload,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
);
if ($safeJsonPayload === false) {
    $safeJsonPayload = '{}';
}

$pageMeta = [
    'slug'        => 'volunteer',
    'title'       => 'Volunteer & Shift Dispatch | Lions District 2-E2 Eyeglass Recycling Center',
    'description' => 'Claim a Saturday volunteer shift (9:00 AM - 3:00 PM) or book a group of up to 20 at 5621 Bunker Blvd, Watauga, TX. Interactive calendar, live seat counts, .ics/Google Calendar export, and 17-frame workshop filmstrip.',
    'canonical'   => '/volunteer',
    'preload_img' => '/gallery/2nd-saturday-group.jpg',
    'og_image'    => '/gallery/2nd-saturday-group.jpg',
    'css'         => ['/css/pages/volunteer.css'],
    'js_modules'  => ['/js/pages/volunteer.js'],
    'json_ld'     => [],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main vol-page" tabindex="-1">

  <!-- 1. Monumental v2 Poster Header & Workshop Briefing -->
  <section id="volunteer-hero" class="vol-hero" aria-labelledby="vol-hero-title">
    <div class="vol-wrap">
      <div class="v2-eyebrow vol-hero__eyebrow">
        <span>SHIFT DISPATCH &middot; WATAUGA OPTICAL WORKSHOP</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span class="v2-telemetry" id="wataugaHoursBadge">
          <span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>
          <span id="wataugaHoursText">SATURDAYS 9:00 AM &ndash; 3:00 PM CT</span>
        </span>
      </div>

      <div class="vol-hero__grid">
        <div class="vol-hero__copy">
          <h1 id="vol-hero-title" class="v2-display v2-display--hero vol-hero__display">
            HANDS ON.<br>
            <span class="v2-knockout">SIGHT</span> OUT<span class="v2-dot" aria-hidden="true"></span>
          </h1>

          <p class="v2-note vol-hero__note">
            <span>no optical experience needed &mdash; we train you in 10 minutes</span>
            <?= erc_curved_arrow('down-left') ?>
          </p>

          <p class="vol-hero__lead">
            Every pair of refurbished glasses shipped free of charge to humanitarian eye clinics across
            <strong>40+ countries</strong> is inspected, washed, measured to <span class="vol-mono">0.25</span>-diopter precision,
            and boxed by unpaid community volunteers in Watauga, Texas. Walk in on Saturday or claim an open shift pass below.
          </p>

          <div class="vol-hero__actions">
            <a href="#schedule" class="btn btn--primary btn--lg">
              <?= erc_icon('calendar-days') ?>
              <span>CLAIM A SHIFT</span>
            </a>
            <a href="#group-volunteering" class="btn btn--secondary btn--lg">
              <?= erc_icon('users') ?>
              <span>BOOK A GROUP (UP TO 20)</span>
            </a>
            <a href="/files/Group-Volunteer-Form.pdf" download class="btn btn--ghost">
              <?= erc_icon('download') ?>
              <span>Group-Volunteer-Form.pdf</span>
            </a>
          </div>

          <dl class="vol-telemetry-grid" aria-label="Workshop shift telemetry">
            <div class="vol-telemetry-cell">
              <dt>SATURDAY WALK-INS</dt>
              <dd class="vol-mono">9:00 AM &ndash; 3:00 PM</dd>
              <dd class="vol-telemetry-sub">Shifts: 9&ndash;12 &amp; 12&ndash;3 CT</dd>
            </div>
            <div class="vol-telemetry-cell">
              <dt>GROUP CAPACITY</dt>
              <dd class="vol-mono">UP TO 20 / SHIFT</dd>
              <dd class="vol-telemetry-sub">Schools, Lions, NCL, BSA, teams</dd>
            </div>
            <div class="vol-telemetry-cell">
              <dt>PRIOR EXPERIENCE</dt>
              <dd>NONE REQUIRED</dd>
              <dd class="vol-telemetry-sub">100% volunteer-run workshop</dd>
            </div>
            <div class="vol-telemetry-cell">
              <dt>UNIT ECONOMICS</dt>
              <dd class="vol-mono">&lt; $0.10 / PAIR</dd>
              <dd class="vol-telemetry-sub">Shipped free to 40+ countries</dd>
            </div>
          </dl>
        </div>

        <aside class="vol-arrival-card" aria-label="Watauga Center arrival and entrance directions">
          <div class="vol-arrival-card__topbar">
            <span class="vol-mono">HQ DISPATCH &middot; 32.8707&deg; N, 97.2621&deg; W</span>
            <span class="vol-arrival-badge vol-mono">FREE PARKING</span>
          </div>

          <div class="vol-arrival-card__media">
            <img
              src="/gallery/2nd-saturday-group.jpg"
              alt="Volunteers gathered inside the Watauga Eyeglass Recycling Center workshop during a 2nd Saturday service session"
              width="1200"
              height="800"
              fetchpriority="high"
              decoding="async"
            >
          </div>

          <div class="vol-arrival-card__body">
            <h2 class="vol-arrival-card__heading">5621 BUNKER BLVD, WATAUGA, TX 76148</h2>

            <div class="vol-walkway-callout">
              <?= erc_icon('navigation', ['class' => 'vol-icon-sm']) ?>
              <p>
                <strong>Rear Entrance Access:</strong> Free parking on-site. Follow the
                <strong>wooden walkway along the east side of the building</strong> to the
                <strong>rear entrance</strong> (door is at the back on the east side).
              </p>
            </div>

            <dl class="vol-hours-ledger">
              <div class="vol-hours-ledger__row">
                <dt>Saturday</dt>
                <dd class="vol-mono">9:00 AM &ndash; 3:00 PM</dd>
              </div>
              <div class="vol-hours-ledger__row">
                <dt>Mon &ndash; Fri</dt>
                <dd>By Appointment</dd>
              </div>
              <div class="vol-hours-ledger__row">
                <dt>Sunday</dt>
                <dd>Closed</dd>
              </div>
            </dl>

            <div class="vol-arrival-card__links">
              <a href="tel:<?= e($contactPhoneLink) ?>" class="vol-inline-link">
                <?= erc_icon('phone', ['class' => 'vol-icon-xs']) ?>
                <span class="vol-mono"><?= e($contactPhone) ?></span>
              </a>
              <a href="mailto:<?= e($contactEmail) ?>" class="vol-inline-link">
                <?= erc_icon('mail', ['class' => 'vol-icon-xs']) ?>
                <span><?= e($contactEmail) ?></span>
              </a>
              <a
                href="https://www.google.com/maps/dir/?api=1&amp;destination=5621+Bunker+Blvd,+Watauga,+TX+76148"
                target="_blank"
                rel="noopener noreferrer"
                class="vol-inline-link"
              >
                <?= erc_icon('external-link', ['class' => 'vol-icon-xs']) ?>
                <span>Map Directions</span>
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <!-- 2. Interactive Shift Board, Monthly Calendar & Perforated .v2-ticket Passes -->
  <section id="schedule" class="vol-section vol-section--schedule" aria-labelledby="vol-schedule-title">
    <div class="vol-wrap">
      <div class="v2-eyebrow">
        <span>LIVE SHIFT BOARD &middot; CLAIM YOUR PASS</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span data-ct-clock>AMERICA / CHICAGO</span>
      </div>

      <div class="vol-section__header vol-section__header--split">
        <div>
          <h2 id="vol-schedule-title" class="v2-display v2-display--lg vol-section__display">
            SCHEDULED <span class="v2-accent-text">SHIFTS</span><span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="vol-section__desc">
            Select any calendar date or perforated dispatch pass to claim a seat online, download an
            <span class="vol-mono">.ics</span> reminder, or export to Google Calendar (<span class="vol-mono">America/Chicago</span>).
            Walk-ins are always welcome Saturdays <span class="vol-mono">9:00 AM &ndash; 3:00 PM</span>.
          </p>
        </div>
        <p class="v2-note vol-schedule-note">
          <span>pick a pass &amp; tap claim shift</span>
          <?= erc_curved_arrow('down-left') ?>
        </p>
      </div>

      <!-- Filter & Scope Controls -->
      <div class="vol-controls" aria-label="Filter volunteer shifts">
        <div class="vol-controls__row">
          <div class="vol-segmented" role="group" aria-label="Session timeframe">
            <button
              type="button"
              class="vol-seg-btn"
              id="scopeUpcomingBtn"
              data-scope="upcoming"
              aria-pressed="<?= $defaultScope === 'upcoming' ? 'true' : 'false' ?>"
            >
              <span>UPCOMING</span>
              <span class="vol-count-pill vol-mono" id="upcomingCountPill"><?= count($upcomingEvents) ?></span>
            </button>
            <button
              type="button"
              class="vol-seg-btn"
              id="scopeAllBtn"
              data-scope="all"
              aria-pressed="<?= $defaultScope === 'all' ? 'true' : 'false' ?>"
            >
              <span>ALL SESSIONS</span>
              <span class="vol-count-pill vol-mono" id="allCountPill"><?= count($normalizedEvents) ?></span>
            </button>
          </div>

          <div class="vol-filter-chips" role="group" aria-label="Session availability filter">
            <button type="button" class="vol-chip" data-filter="all" aria-pressed="true">All Types</button>
            <button type="button" class="vol-chip" data-filter="open-seats" aria-pressed="false">Open Seats</button>
            <button type="button" class="vol-chip" data-filter="open-session" aria-pressed="false">2nd Saturday &amp; Open</button>
          </div>

          <search class="vol-search-box">
            <label for="eventSearchInput" class="vol-visually-hidden">Search scheduled sessions</label>
            <span class="vol-search-box__icon" aria-hidden="true"><?= erc_icon('search', ['class' => 'vol-icon-sm']) ?></span>
            <input
              type="search"
              id="eventSearchInput"
              class="vol-search-input"
              placeholder="Search shifts, clubs, or dates..."
              autocomplete="off"
              autocorrect="off"
              spellcheck="false"
            >
          </search>
        </div>

        <?php if (!$hasUpcoming && !empty($normalizedEvents)): ?>
          <div class="vol-notice-banner" id="seedScheduleNotice" role="status">
            <?= erc_icon('info', ['class' => 'vol-icon-sm']) ?>
            <p>
              Showing all <strong class="vol-mono"><?= count($normalizedEvents) ?></strong> scheduled sessions in the directory
              (latest posted month: <strong><?= e($eventMonthsList[count($eventMonthsList) - 1]['label'] ?? $defaultMonth) ?></strong>).
              Newly posted future dates appear automatically under <em>Upcoming</em>&mdash;and Saturday walk-ins
              (<span class="vol-mono">9:00 AM &ndash; 3:00 PM</span>) remain open year-round.
            </p>
          </div>
        <?php endif; ?>
      </div>

      <div class="vol-schedule-layout">
        <!-- Monthly Calendar Matrix -->
        <aside class="vol-calendar-panel" aria-labelledby="calMonthHeading">
          <div class="vol-calendar-panel__eyebrow vol-mono">
            <span>SHIFT MATRIX</span>
            <span>SAT WALK-INS OPEN</span>
          </div>

          <div class="vol-calendar-header">
            <button
              type="button"
              class="vol-icon-btn"
              id="calPrevBtn"
              aria-label="Previous month"
            >
              <?= erc_icon('chevron-left', ['class' => 'vol-icon-sm']) ?>
            </button>
            <h3 id="calMonthHeading" class="vol-calendar-title" aria-live="polite">
              <?php
                $defDt = DateTimeImmutable::createFromFormat('!Y-m', $defaultMonth, $chicagoTz);
                echo e($defDt ? $defDt->format('F Y') : $defaultMonth);
              ?>
            </h3>
            <button
              type="button"
              class="vol-icon-btn"
              id="calNextBtn"
              aria-label="Next month"
            >
              <?= erc_icon('chevron-right', ['class' => 'vol-icon-sm']) ?>
            </button>
          </div>

          <?php if (!empty($eventMonthsList)): ?>
            <div class="vol-calendar-jumps" aria-label="Months with scheduled sessions">
              <?php foreach ($eventMonthsList as $em): ?>
                <button
                  type="button"
                  class="vol-month-jump <?= $em['key'] === $defaultMonth ? 'is-active' : '' ?>"
                  data-jump-month="<?= e($em['key']) ?>"
                  aria-pressed="<?= $em['key'] === $defaultMonth ? 'true' : 'false' ?>"
                >
                  <span><?= e($em['label']) ?></span>
                  <span class="vol-mono">(<?= (int)$em['count'] ?>)</span>
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="vol-calendar-grid-wrap">
            <table class="vol-calendar-table" id="volunteerCalendarTable" aria-labelledby="calMonthHeading">
              <thead>
                <tr>
                  <th scope="col"><abbr title="Sunday">Sun</abbr></th>
                  <th scope="col"><abbr title="Monday">Mon</abbr></th>
                  <th scope="col"><abbr title="Tuesday">Tue</abbr></th>
                  <th scope="col"><abbr title="Wednesday">Wed</abbr></th>
                  <th scope="col"><abbr title="Thursday">Thu</abbr></th>
                  <th scope="col"><abbr title="Friday">Fri</abbr></th>
                  <th scope="col"><abbr title="Saturday">Sat</abbr></th>
                </tr>
              </thead>
              <tbody id="volunteerCalendarBody">
                <?php
                  $calDt = DateTimeImmutable::createFromFormat('!Y-m-d', $defaultMonth . '-01', $chicagoTz);
                  if ($calDt) {
                      $firstDow = (int)$calDt->format('w');
                      $daysInMonth = (int)$calDt->format('t');
                      $eventsByDate = [];
                      foreach ($normalizedEvents as $ev) {
                          $eventsByDate[$ev['date']][] = $ev;
                      }
                      $cellCount = $firstDow + $daysInMonth;
                      $totalCells = (int)(ceil($cellCount / 7) * 7);
                      for ($i = 0; $i < $totalCells; $i++) {
                          if ($i % 7 === 0) {
                              echo '<tr>';
                          }
                          $dayNum = $i - $firstDow + 1;
                          if ($dayNum < 1 || $dayNum > $daysInMonth) {
                              echo '<td class="vol-cal-cell vol-cal-cell--empty"></td>';
                          } else {
                              $iso = sprintf('%s-%02d', $defaultMonth, $dayNum);
                              $dayEvents = $eventsByDate[$iso] ?? [];
                              $hasEv = count($dayEvents) > 0;
                              $isSat = ($i % 7) === 6;
                              $isToday = $iso === $todayIso;
                              $btnClass = 'vol-cal-day' . ($hasEv ? ' has-events' : '') . ($isSat ? ' is-saturday' : '') . ($isToday ? ' is-today' : '');
                              $ariaLabel = $calDt->format('F') . ' ' . $dayNum . ', ' . $calDt->format('Y') . ($hasEv ? ' — ' . count($dayEvents) . ' scheduled session' . (count($dayEvents) > 1 ? 's' : '') : '');
                              echo '<td class="vol-cal-cell">';
                              echo '<button type="button" class="' . e($btnClass) . '" data-cal-date="' . e($iso) . '" aria-pressed="false" aria-label="' . e($ariaLabel) . '"' . ($hasEv ? '' : ' disabled') . '>';
                              echo '<span class="vol-cal-day__num vol-mono">' . $dayNum . '</span>';
                              if ($hasEv) {
                                  echo '<span class="vol-cal-day__dot" aria-hidden="true">' . count($dayEvents) . '</span>';
                              }
                              echo '</button>';
                              echo '</td>';
                          }
                          if ($i % 7 === 6) {
                              echo '</tr>';
                          }
                      }
                  }
                ?>
              </tbody>
            </table>
          </div>

          <div class="vol-calendar-footer">
            <div class="vol-calendar-legend">
              <span class="vol-legend-item"><span class="vol-legend-swatch vol-legend-swatch--event" aria-hidden="true"></span> Scheduled shift</span>
              <span class="vol-legend-item"><span class="vol-legend-swatch vol-legend-swatch--sat" aria-hidden="true"></span> Saturday walk-in</span>
            </div>
            <div class="vol-calendar-filter-bar">
              <label class="vol-checkbox-label">
                <input type="checkbox" id="filterByCalMonthCheck">
                <span>Filter list by calendar month</span>
              </label>
              <button type="button" class="vol-link-btn" id="clearDayFilterBtn" hidden>Clear date filter</button>
            </div>
          </div>
        </aside>

        <!-- Perforated .v2-ticket Dispatch Pass Grid -->
        <div class="vol-events-column">
          <div class="vol-events-status-bar">
            <p id="eventsFilterSummary" class="vol-events-summary vol-mono" aria-live="polite">
              SHOWING <?= count($defaultScope === 'upcoming' ? $upcomingEvents : $normalizedEvents) ?> SHIFT PASS(ES)
            </p>
          </div>

          <div id="eventsEmptyState" class="vol-empty-card" hidden>
            <?= erc_icon('calendar', ['class' => 'vol-icon-lg']) ?>
            <h3 id="eventsEmptyHeading">No matching shifts in this view</h3>
            <p id="eventsEmptyCopy">
              Walk-in volunteers are always welcome most Saturdays from <strong>9:00 AM &ndash; 3:00 PM</strong> at 5621 Bunker Blvd, Watauga, TX 76148.
            </p>
            <div class="vol-empty-card__actions">
              <button type="button" class="btn btn--primary" id="resetFiltersBtn">Show All Scheduled Shifts</button>
              <a href="#group-volunteering" class="btn btn--secondary">Book a Group Session</a>
            </div>
          </div>

          <div id="volunteerEventsList" class="vol-events-list">
            <?php foreach ($normalizedEvents as $ev): ?>
              <?php
                $isHiddenByDefaultScope = ($defaultScope === 'upcoming' && $ev['isPast']);
                $pct = $ev['maxPeople'] > 0 ? min(100, (int)round(($ev['people'] / $ev['maxPeople']) * 100)) : 0;
                $popId = 'cal-menu-' . $ev['id'];
                $toneClass = 'vol-ticket--' . $ev['ticketTone'];
              ?>
              <div
                class="vol-event-item"
                data-event-id="<?= (int)$ev['id'] ?>"
                data-event-date="<?= e($ev['date']) ?>"
                data-event-month="<?= e($ev['monthKey']) ?>"
                data-event-past="<?= $ev['isPast'] ? '1' : '0' ?>"
                <?= $isHiddenByDefaultScope ? 'hidden' : '' ?>
              >
                <article class="v2-ticket vol-ticket <?= e($toneClass) ?> <?= $ev['isPast'] ? 'is-past' : '' ?> <?= $ev['isFull'] ? 'is-full' : '' ?>">
                  <div class="v2-ticket__main vol-ticket__main">
                    <div class="v2-ticket__meta">
                      <span class="vol-ticket__admit">
                        <span class="vol-ticket__accent-dot" aria-hidden="true"></span>
                        <span>ADMIT ONE &middot; <?= e($ev['ticketTag']) ?></span>
                      </span>
                      <div class="vol-ticket__badges">
                        <span class="vol-date-badge vol-mono"><?= e($ev['formattedDate']) ?></span>
                        <?php if ($ev['isPast']): ?>
                          <span class="vol-state-badge vol-state-badge--past">Past Date</span>
                        <?php elseif ($ev['isFull']): ?>
                          <span class="vol-state-badge vol-state-badge--full">Shift Full</span>
                        <?php elseif ($ev['remaining'] !== null && $ev['remaining'] <= 3): ?>
                          <span class="vol-state-badge vol-state-badge--warn">Filling Fast</span>
                        <?php else: ?>
                          <span class="vol-state-badge vol-state-badge--open">Open</span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="vol-ticket__headline-row">
                      <div class="vol-ticket__thumb <?= $ev['isLogo'] ? 'is-logo' : '' ?>">
                        <img
                          src="<?= e($ev['imageUrl']) ?>"
                          alt="<?= e($ev['title']) ?>"
                          width="120"
                          height="120"
                          loading="lazy"
                          decoding="async"
                          data-fallback="/images/placeholder-event.svg"
                        >
                      </div>
                      <div class="vol-ticket__headline-copy">
                        <h3 class="v2-ticket__title vol-ticket__title"><?= e($ev['title']) ?></h3>
                        <ul class="vol-event-meta">
                          <li>
                            <?= erc_icon('clock', ['class' => 'vol-icon-xs']) ?>
                            <span class="vol-mono"><?= e($ev['time']) ?></span>
                          </li>
                          <li>
                            <?= erc_icon('map-pin', ['class' => 'vol-icon-xs']) ?>
                            <span><?= e($ev['location']) ?></span>
                          </li>
                        </ul>
                      </div>
                    </div>

                    <?php if ($ev['description'] !== ''): ?>
                      <p class="vol-event-card__desc"><?= e($ev['description']) ?></p>
                    <?php endif; ?>

                    <div class="vol-ticket__bottom-row">
                      <div class="vol-capacity">
                        <div class="vol-capacity__row">
                          <span class="vol-capacity__label">SEAT METER</span>
                          <span class="vol-capacity__value vol-mono" data-capacity-text>
                            <?php if ($ev['maxPeople'] > 0): ?>
                              <?= (int)$ev['people'] ?> / <?= (int)$ev['maxPeople'] ?>
                              (<?= (int)$ev['remaining'] ?> <?= $ev['remaining'] === 1 ? 'spot' : 'spots' ?> left)
                            <?php else: ?>
                              <?= (int)$ev['people'] ?> registered
                            <?php endif; ?>
                          </span>
                        </div>
                        <?php if ($ev['maxPeople'] > 0): ?>
                          <div
                            class="vol-meter"
                            role="progressbar"
                            aria-label="Session capacity"
                            aria-valuemin="0"
                            aria-valuemax="<?= (int)$ev['maxPeople'] ?>"
                            aria-valuenow="<?= (int)$ev['people'] ?>"
                            data-capacity-bar
                          >
                            <span class="vol-meter__fill" style="--fill-pct: <?= $pct ?>%;"></span>
                          </div>
                        <?php endif; ?>
                      </div>

                      <div class="vol-cal-menu-wrap">
                        <button
                          type="button"
                          class="btn btn--secondary btn--sm vol-cal-trigger"
                          popovertarget="<?= e($popId) ?>"
                          data-popover-trigger="<?= e($popId) ?>"
                        >
                          <?= erc_icon('calendar-plus', ['class' => 'vol-icon-xs']) ?>
                          <span>Add to Calendar</span>
                          <?= erc_icon('chevron-down', ['class' => 'vol-icon-xs']) ?>
                        </button>
                        <div id="<?= e($popId) ?>" popover="auto" class="vol-cal-popover">
                          <button
                            type="button"
                            class="vol-cal-popover__item"
                            data-action="download-ics"
                            data-event-id="<?= (int)$ev['id'] ?>"
                          >
                            <?= erc_icon('download', ['class' => 'vol-icon-xs']) ?>
                            <span>Download .ics file</span>
                          </button>
                          <a
                            class="vol-cal-popover__item"
                            href="<?= e($ev['gcalUrl']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            <?= erc_icon('external-link', ['class' => 'vol-icon-xs']) ?>
                            <span>Add to Google Calendar</span>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="v2-ticket__stub vol-ticket__stub">
                    <span class="v2-ticket__serial vol-ticket__serial"><?= e($ev['serial']) ?></span>
                    <button
                      type="button"
                      class="btn btn--primary vol-claim-btn"
                      data-action="open-register"
                      data-event-id="<?= (int)$ev['id'] ?>"
                      <?= $ev['isFull'] ? 'disabled' : '' ?>
                    >
                      <span><?= $ev['isFull'] ? 'SHIFT FULL' : 'CLAIM SHIFT' ?></span>
                      <?= erc_icon($ev['isFull'] ? 'circle-alert' : 'arrow-right', ['class' => 'vol-icon-xs']) ?>
                    </button>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 3. Workshop Stations & Group Volunteer Days -->
  <section id="workstations" class="vol-section vol-section--band" aria-labelledby="vol-workstations-title">
    <div class="vol-wrap">
      <div class="v2-eyebrow">
        <span>WORKSHOP STATIONS &middot; ON-SITE TRAINING</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>05 BENCHES</span>
      </div>

      <div class="vol-section__header">
        <h2 id="vol-workstations-title" class="v2-display v2-display--md vol-section__display">
          WHAT VOLUNTEERS DO AT THE CENTER<span class="v2-dot" aria-hidden="true"></span>
        </h2>
        <p class="vol-section__desc">
          Individuals are welcome to drop in during Saturday open hours (<span class="vol-mono">9:00 AM &ndash; 3:00 PM</span>) or sign up online for sessions like our <strong>2nd Saturday Open Session</strong> (typically <span class="vol-mono">12:00 PM &ndash; 3:00 PM</span>). Our team trains you right at the bench.
        </p>
      </div>

      <div class="vol-stations-layout">
        <ol class="vol-stations-grid">
          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">01</span>
              <span class="vol-station-icon"><?= erc_icon('glasses', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">SORTING &amp; INITIAL INSPECTION</h3>
            <p class="vol-station-card__body">
              Inspect donated eyewear and sort into single-vision prescription, reading glasses, bifocals/trifocals/progressives/prisms, and non-prescription sunglasses. Broken frames are set aside for repair or parts alongside donated hearing aids, cell phones, and Windows 11-ready laptops and tablets.
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">02</span>
              <span class="vol-station-icon"><?= erc_icon('sparkles', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">WASHING, SANITIZING &amp; DRYING</h3>
            <p class="vol-station-card__body">
              Wash frames and lenses thoroughly, sanitize every pair, and arrange them on workshop drying racks so clinic partners receive clean, ready-to-dispense eyewear.
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">03</span>
              <span class="vol-station-icon"><?= erc_icon('eye', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">MEASURING PRESCRIPTIONS (LENSOMETRY)</h3>
            <p class="vol-station-card__body">
              Determine exact lens prescriptions in <span class="vol-mono">0.25</span>-diopter increments using tabletop lensometers. Experienced Lions show you the dials step by step.
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">04</span>
              <span class="vol-station-icon"><?= erc_icon('package', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">PACKING 50-PAIR MISSION BOXES</h3>
            <p class="vol-station-card__body">
              Complete final quality checks, label each pair, and pack standardized <strong class="vol-mono">50</strong>-pair mission boxes by <span class="vol-mono">0.25</span>-diopter bracket (<span class="vol-mono">+0.25</span> to <span class="vol-mono">+5.00+</span> Plus &amp; Readers, <span class="vol-mono">-0.25</span> to <span class="vol-mono">-5.00+</span> Minus, and Sunglasses).
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">05</span>
              <span class="vol-station-icon"><?= erc_icon('file-text', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">ADMIN &amp; LOGISTICS SUPPORT</h3>
            <p class="vol-station-card__body">
              Welcome Saturday walk-ins, log incoming donations from our <span class="vol-mono">214</span> DFW collection bins, stage mission shipments, and keep benches supplied.
            </p>
          </li>
        </ol>

        <div class="vol-stations-photos" aria-label="Workstation photos">
          <figure class="vol-station-photo">
            <div class="erc-rotated-frame erc-rotated-frame--landscape vol-station-photo__frame">
              <img
                class="is-ccw90-no-exif"
                src="/images/process-lensometers.jpg"
                alt="Volunteers measuring donated eyeglass prescriptions at tabletop lensometers in Watauga"
                width="1200"
                height="900"
                loading="lazy"
                decoding="async"
              >
            </div>
            <figcaption>Tabletop lensometry station where volunteers read <span class="vol-mono">0.25</span>-diopter prescriptions.</figcaption>
          </figure>
          <figure class="vol-station-photo">
            <div class="vol-station-photo__frame vol-station-photo__frame--plain">
              <img
                src="/gallery/drying-glasses.jpg"
                alt="Volunteers arranging washed and sanitized eyeglasses on drying racks"
                width="1200"
                height="900"
                loading="lazy"
                decoding="async"
              >
            </div>
            <figcaption>Washed and sanitized eyewear drying on workshop racks prior to inspection and boxing.</figcaption>
          </figure>
        </div>
      </div>

      <!-- Group Volunteer Days Sub-Block -->
      <div id="group-volunteering" class="vol-group-panel">
        <div class="vol-group-grid">
          <div class="vol-group-copy">
            <div class="v2-eyebrow">
              <span>GROUP DISPATCH &middot; UP TO 20 VOLUNTEERS</span>
              <span class="v2-eyebrow__rule" aria-hidden="true"></span>
            </div>
            <h2 id="vol-group-title" class="v2-display v2-display--md vol-section__display">
              GROUP VOLUNTEER DAYS<span class="v2-dot" aria-hidden="true"></span>
            </h2>
            <p class="vol-group-lead">
              Reserve a dedicated three-hour shift for your team or civic organization in our morning
              (<span class="vol-mono">9:00 AM &ndash; 12:00 PM</span>) or afternoon (<span class="vol-mono">12:00 PM &ndash; 3:00 PM</span>) window, accommodating up to <strong class="vol-mono">20 volunteers</strong> per group.
            </p>

            <h3 class="vol-subhead">Organizations We Host</h3>
            <ul class="vol-group-tags">
              <li>Corporate teams</li>
              <li>Schools &amp; student clubs</li>
              <li>Lions Clubs</li>
              <li>National Charity League (NCL) chapters</li>
              <li>Scouting troops (BSA / Eagle Scouts)</li>
              <li>SASO chapters</li>
              <li>Church youth groups</li>
              <li>Civic &amp; community organizations</li>
            </ul>
          </div>

          <div class="vol-group-card">
            <div class="vol-group-card__head">
              <span class="vol-mono">GROUP BOOKING PROTOCOL</span>
              <span class="vol-mono">MAX 20 / SHIFT</span>
            </div>
            <h3 class="vol-group-card__title">SCHEDULE A GROUP SESSION</h3>
            <ol class="vol-group-steps">
              <li>
                <strong>Choose a window:</strong> Morning (<span class="vol-mono">9:00 AM &ndash; 12:00 PM</span>) or Afternoon (<span class="vol-mono">12:00 PM &ndash; 3:00 PM</span>), up to <span class="vol-mono">20</span> volunteers.
              </li>
              <li>
                <strong>Submit the form or contact us:</strong> Download <span class="vol-mono">Group-Volunteer-Form.pdf</span> and email it to <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>, call <a href="tel:<?= e($contactPhoneLink) ?>" class="vol-mono"><?= e($contactPhone) ?></a>, or request a date online.
              </li>
              <li>
                <strong>Arrive at Bunker Blvd:</strong> Park free on-site at <span class="vol-mono">5621 Bunker Blvd</span> and follow the wooden walkway along the east side of the building to the rear entrance.
              </li>
            </ol>

            <div class="vol-group-card__actions">
              <a href="/files/Group-Volunteer-Form.pdf" download class="btn btn--primary">
                <?= erc_icon('download', ['class' => 'vol-icon-sm']) ?>
                <span>Download Group-Volunteer-Form.pdf</span>
              </a>
              <a href="/contact?subject=group-volunteer" class="btn btn--secondary">
                <?= erc_icon('mail', ['class' => 'vol-icon-sm']) ?>
                <span>Request Group Date</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. .v2-sheet Contrast Stage — Workshop Contact Sheet / Filmstrip Gallery (17 Photos) -->
  <section id="volunteer-gallery" class="v2-sheet vol-gallery-sheet" aria-labelledby="vol-gallery-title">
    <div class="v2-sheet__inner">
      <div class="v2-eyebrow">
        <span>35MM CONTACT SHEET &middot; WORKSHOP &amp; MISSION ARCHIVE</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span class="vol-mono"><span id="galleryTotalBadge"><?= count($galleryItems) ?></span> FRAMES</span>
      </div>

      <div class="vol-section__header vol-section__header--split">
        <div>
          <h2 id="vol-gallery-title" class="v2-display v2-display--lg vol-section__display">
            WORKSHOP FILMSTRIP<span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="vol-section__desc">
            Lions Clubs, youth chapters, lensometry benches, and outbound mission crates captured inside our Watauga center. Select any frame for the high-resolution keyboard lightbox.
          </p>
        </div>

        <div class="vol-gallery-toolbar">
          <p class="v2-note vol-gallery-note">
            <span>click any frame to inspect</span>
            <?= erc_curved_arrow('down-left') ?>
          </p>
          <button type="button" class="btn btn--secondary" id="refreshGalleryBtn">
            <?= erc_icon('refresh-cw', ['class' => 'vol-icon-xs']) ?>
            <span>Refresh Photos</span>
          </button>
        </div>
      </div>

      <p id="galleryStatusMsg" class="vol-gallery-status" aria-live="polite" hidden></p>

      <ul id="volunteerGalleryGrid" class="vol-filmstrip-grid">
        <?php foreach ($galleryItems as $item): ?>
          <li class="vol-filmstrip-item">
            <button
              type="button"
              class="vol-frame-card"
              data-gallery-index="<?= (int)$item['index'] ?>"
              data-gallery-src="<?= e($item['url']) ?>"
              data-gallery-caption="<?= e($item['caption']) ?>"
              aria-label="Open frame <?= e($item['frameId']) ?>: <?= e($item['caption']) ?>"
            >
              <span class="vol-frame-card__sprockets" aria-hidden="true">
                <span class="vol-frame-card__serial vol-mono"><?= e($item['frameId']) ?></span>
                <span class="vol-frame-card__perf"></span>
                <span class="vol-frame-card__zoom">
                  <?= erc_icon('zoom-in', ['class' => 'vol-icon-xs']) ?>
                </span>
              </span>

              <span class="vol-frame-card__media">
                <img
                  src="<?= e($item['url']) ?>"
                  alt="<?= e($item['caption']) ?>"
                  loading="lazy"
                  decoding="async"
                  width="640"
                  height="480"
                >
              </span>

              <span class="vol-frame-card__footer">
                <span class="vol-frame-card__caption"><?= e($item['caption']) ?></span>
                <span class="vol-frame-card__note"><?= e($item['note']) ?></span>
              </span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- 5. Watauga Arrival & Next Steps Strip -->
  <section id="arrival-guide" class="vol-section vol-section--arrival" aria-labelledby="vol-arrival-title">
    <div class="vol-wrap vol-arrival-strip">
      <div class="vol-arrival-strip__main">
        <div class="v2-eyebrow">
          <span>VISITING THE CENTER</span>
          <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        </div>
        <h2 id="vol-arrival-title" class="v2-display v2-display--sm vol-section__display">
          FINDING 5621 BUNKER BLVD<span class="v2-dot" aria-hidden="true"></span>
        </h2>
        <p>
          Free parking is available right in front of the building at <strong>5621 Bunker Blvd, Watauga, TX 76148</strong>. Follow the <strong>wooden walkway along the east side of the building to the rear entrance</strong>&mdash;the workshop door is at the back on the east side.
        </p>
      </div>
      <div class="vol-arrival-strip__links">
        <a href="/locations" class="btn btn--secondary">
          <?= erc_icon('map-pin', ['class' => 'vol-icon-sm']) ?>
          <span>Explore 214 Drop-Off Bins</span>
        </a>
        <a href="/donate" class="btn btn--secondary">
          <?= erc_icon('glasses', ['class' => 'vol-icon-sm']) ?>
          <span>Accepted Donations</span>
        </a>
        <a href="/contact?subject=volunteer" class="btn btn--primary">
          <?= erc_icon('mail', ['class' => 'vol-icon-sm']) ?>
          <span>Ask a Question</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Native Modal: Dark Obsidian Shift Registration Console -->
  <dialog id="volunteerRegDialog" class="vol-dialog vol-console-dialog" closedby="any" aria-labelledby="volRegDialogTitle">
    <div class="vol-dialog__panel">
      <div class="vol-console__handle" aria-hidden="true"></div>

      <div class="vol-dialog__header">
        <div class="vol-console__title-group">
          <div class="vol-console__meta-bar">
            <span class="vol-console__tag vol-mono">SHIFT-PASS &middot; LIVE</span>
            <span class="vol-console__req vol-mono" id="regPassCode">SHIFT-0000</span>
            <span class="vol-console__pill vol-mono" id="regStatusPill">
              <span class="vol-console__pill-dot" aria-hidden="true"></span>
              <span>DRAFT</span>
            </span>
          </div>
          <h2 id="volRegDialogTitle" class="vol-dialog__title">CLAIM VOLUNTEER SHIFT</h2>
        </div>

        <button
          type="button"
          class="vol-dialog__close"
          data-dialog-close
          aria-label="Close registration modal"
        >
          <?= erc_icon('x', ['class' => 'vol-icon-md']) ?>
        </button>
      </div>

      <div class="vol-dialog__session-summary" id="regSessionSummary">
        <div class="vol-console__telemetry-row">
          <div class="vol-console__telemetry-cell">
            <span class="vol-console__label vol-mono">SESSION</span>
            <h3 id="regEventTitle" class="vol-dialog__event-title">Volunteer Session</h3>
          </div>
          <div class="vol-console__telemetry-cell">
            <span class="vol-console__label vol-mono">REMAINING SEATS</span>
            <p class="vol-dialog__event-seats"><strong id="regEventSeats" class="vol-mono"></strong></p>
          </div>
        </div>
        <div class="vol-console__telemetry-row">
          <div class="vol-console__telemetry-cell">
            <span class="vol-console__label vol-mono">WINDOW (CT)</span>
            <p class="vol-dialog__event-meta"><span id="regEventDateTime" class="vol-mono"></span></p>
          </div>
          <div class="vol-console__telemetry-cell">
            <span class="vol-console__label vol-mono">LOCATION</span>
            <p class="vol-dialog__event-meta"><span id="regEventLocation"></span></p>
          </div>
        </div>
      </div>

      <form id="volunteerSignupForm" class="vol-form" novalidate>
        <input type="hidden" id="regEventId" name="eventId" value="">

        <!-- Honeypot trap for bots; hidden from keyboard & assistive tech -->
        <div class="vol-honeypot" aria-hidden="true">
          <label for="regWebsiteUrl">Leave this field blank</label>
          <input type="text" id="regWebsiteUrl" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="vol-form__row">
          <div class="vol-field">
            <label for="regName">FULL NAME <span class="vol-req" aria-hidden="true">*</span></label>
            <span id="regNameHint" class="vol-hint">Required &middot; Up to 120 characters</span>
            <input
              type="text"
              id="regName"
              name="name"
              required
              maxlength="120"
              autocomplete="name"
              placeholder="Jane Volunteer"
              enterkeyhint="next"
              aria-invalid="false"
              aria-describedby="regNameHint"
              data-error="regNameError"
              autofocus
            >
            <div id="regNameError" class="vol-field-error">
              <?= erc_icon('circle-alert', ['class' => 'vol-icon-xs']) ?>
              <span>Error: Please enter your full name (1&ndash;120 characters).</span>
            </div>
          </div>

          <div class="vol-field">
            <label for="regEmail">EMAIL ADDRESS <span class="vol-req" aria-hidden="true">*</span></label>
            <span id="regEmailHint" class="vol-hint">Confirmation sent here</span>
            <input
              type="email"
              id="regEmail"
              name="email"
              inputmode="email"
              required
              maxlength="254"
              autocomplete="email"
              placeholder="you@example.com"
              autocorrect="off"
              autocapitalize="none"
              spellcheck="false"
              enterkeyhint="next"
              aria-invalid="false"
              aria-describedby="regEmailHint"
              data-error="regEmailError"
            >
            <div id="regEmailError" class="vol-field-error">
              <?= erc_icon('circle-alert', ['class' => 'vol-icon-xs']) ?>
              <span>Error: Enter a valid email address (you@example.com).</span>
            </div>
          </div>
        </div>

        <div class="vol-form__row">
          <div class="vol-field">
            <label for="regPhone">PHONE NUMBER <span class="vol-opt">(optional)</span></label>
            <span id="regPhoneHint" class="vol-hint">Format: (817) 555-0199</span>
            <input
              type="tel"
              id="regPhone"
              name="phone"
              inputmode="tel"
              maxlength="40"
              autocomplete="tel"
              placeholder="(817) 555-0199"
              enterkeyhint="next"
              aria-invalid="false"
              aria-describedby="regPhoneHint"
              data-error="regPhoneError"
            >
            <div id="regPhoneError" class="vol-field-error">
              <?= erc_icon('circle-alert', ['class' => 'vol-icon-xs']) ?>
              <span>Error: Phone number must be 40 characters or fewer.</span>
            </div>
          </div>

          <div class="vol-field">
            <label for="regCount">VOLUNTEER SEATS</label>
            <span id="regCountHint" class="vol-hint">1 online seat per email (groups up to 20 via PDF)</span>
            <input
              type="number"
              id="regCount"
              name="count"
              min="1"
              max="20"
              step="1"
              value="1"
              inputmode="numeric"
              autocomplete="off"
              enterkeyhint="done"
              aria-invalid="false"
              aria-describedby="regCountHint"
              data-error="regCountError"
            >
            <div id="regCountError" class="vol-field-error">
              <?= erc_icon('circle-alert', ['class' => 'vol-icon-xs']) ?>
              <span>Error: Enter a seat count between 1 and 20.</span>
            </div>
          </div>
        </div>

        <p id="regCountGroupNote" class="vol-console__group-note" hidden>
          Online dispatch reserves <strong>1 seat per email address</strong>. For groups of 2&ndash;20, register each volunteer individually or submit <a href="/files/Group-Volunteer-Form.pdf" download>Group-Volunteer-Form.pdf</a>.
        </p>

        <div id="regFormStatus" class="vol-form-status" role="status" aria-live="polite" hidden></div>

        <div class="vol-dialog__footer">
          <button type="submit" id="regSubmitBtn" class="vol-console-submit">
            <span class="vol-spinner" id="regSpinner" aria-hidden="true" hidden></span>
            <span id="regSubmitText">CONFIRM SHIFT</span>
            <span class="vol-console-submit__orb" aria-hidden="true">
              <?= erc_icon('arrow-right', ['class' => 'vol-icon-xs']) ?>
            </span>
          </button>
          <button type="button" class="btn btn--secondary vol-console-cancel" data-dialog-close>Cancel</button>
        </div>

        <p class="vol-console__footnote vol-mono">
          5621 BUNKER BLVD &middot; EAST-SIDE WOODEN WALKWAY TO REAR ENTRANCE
        </p>
      </form>

      <!-- Post-Signup Confirmation State -->
      <div id="regSuccessView" class="vol-reg-success" hidden>
        <div class="vol-reg-success__banner">
          <?= erc_icon('circle-check-big', ['class' => 'vol-icon-lg']) ?>
          <div>
            <h3 id="regSuccessHeading" tabindex="-1">SHIFT CONFIRMED!</h3>
            <p id="regSuccessMessage"></p>
          </div>
        </div>

        <div class="vol-walkway-callout vol-walkway-callout--console">
          <?= erc_icon('navigation', ['class' => 'vol-icon-sm']) ?>
          <p>
            <strong>Arrival Reminder:</strong> Free parking at 5621 Bunker Blvd, Watauga. Follow the
            <strong>wooden walkway along the east side of the building</strong> to the rear entrance.
          </p>
        </div>

        <div class="vol-reg-success__actions">
          <button type="button" id="regSuccessIcsBtn" class="btn btn--primary">
            <?= erc_icon('download', ['class' => 'vol-icon-sm']) ?>
            <span>Download .ics Reminder</span>
          </button>
          <a id="regSuccessGcalLink" href="#" target="_blank" rel="noopener noreferrer" class="btn btn--secondary">
            <?= erc_icon('calendar-plus', ['class' => 'vol-icon-sm']) ?>
            <span>Add to Google Calendar</span>
          </a>
          <button type="button" class="btn btn--ghost" data-dialog-close>Done</button>
        </div>
      </div>
    </div>
  </dialog>

  <!-- Native Modal: Accessible Gallery Photo Lightbox -->
  <dialog id="volunteerLightbox" class="vol-lightbox" closedby="any" aria-labelledby="lightboxCaption">
    <div class="vol-lightbox__shell">
      <div class="vol-lightbox__topbar">
        <p class="vol-lightbox__counter vol-mono" id="lightboxCounter" aria-live="polite">FRM-01 / <?= sprintf('%02d', count($galleryItems)) ?></p>
        <h2 class="vol-lightbox__caption" id="lightboxCaption">Gallery Photo</h2>
        <button
          type="button"
          class="vol-lightbox__close"
          id="lightboxCloseBtn"
          data-dialog-close
          aria-label="Close photo viewer"
        >
          <?= erc_icon('x', ['class' => 'vol-icon-md']) ?>
        </button>
      </div>

      <div class="vol-lightbox__stage">
        <button
          type="button"
          class="vol-lightbox__nav vol-lightbox__nav--prev"
          id="lightboxPrevBtn"
          aria-label="Previous photo"
        >
          <?= erc_icon('chevron-left', ['class' => 'vol-icon-md']) ?>
        </button>

        <figure class="vol-lightbox__figure">
          <img
            id="lightboxImg"
            class="vol-lightbox__img"
            src="/images/placeholder-event.svg"
            alt=""
          >
        </figure>

        <button
          type="button"
          class="vol-lightbox__nav vol-lightbox__nav--next"
          id="lightboxNextBtn"
          aria-label="Next photo"
        >
          <?= erc_icon('chevron-right', ['class' => 'vol-icon-md']) ?>
        </button>
      </div>
    </div>
  </dialog>

  <script type="application/json" id="ercVolunteerEventsData"><?= $safeJsonPayload ?></script>
</main>
<?php
require __DIR__ . '/includes/footer.php';
