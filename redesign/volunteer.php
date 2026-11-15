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
 * Derive a clean, descriptive caption from a gallery image path.
 */
function erc_volunteer_gallery_caption(string $relativePath): string
{
    $filename = basename($relativePath);
    static $knownCaptions = [
        '2nd-saturday-group.jpg'      => '2nd Saturday Open Session Volunteers',
        'bsa-troop-76.jpg'            => 'BSA Troop 76 Service Day',
        'coahuila-bound.jpg'          => 'Coahuila Mission Shipment Ready',
        'colleyville-erc-2024.jpg'    => 'Colleyville Lions ERC Service Day (2024)',
        'computers.jpg'               => 'Donated Electronics Intake Station',
        'drying-glasses.jpg'          => 'Washing, Sanitizing & Rack Drying Eyeglasses',
        'founders-workday.jpg'        => 'Ft. Worth Founder Lions Workday',
        'grapevine-erc-2025.jpg'      => 'Grapevine Lions Service Day (2025)',
        'kids-sorting.jpg'            => 'Youth Volunteers Sorting Donated Eyewear',
        'kumc-kids.jpg'               => 'Keller United Methodist Youth Group Sorting',
        'lensometers.jpg'             => 'Measuring Prescriptions on Tabletop Lensometers',
        'mckinney-mp1.jpg'            => 'McKinney Lions Volunteer Team',
        'metroport-ncl.jpg'           => 'DFW Metroport NCL Chapter Volunteers',
        'midland-delivery.jpg'        => 'Midland Eyeglass Delivery',
        'panama-bound.jpg'            => 'Panama Mission Box Preparation',
        'past-district-governors.jpg' => 'Past District Governors Service Day',
        'peru-bound.jpg'              => 'Peru Medical Mission Shipment',
    ];
    $key = strtolower($filename);
    if (isset($knownCaptions[$key])) {
        return $knownCaptions[$key];
    }

    $stem = preg_replace('/\.[^.]+$/', '', $filename) ?? $filename;
    $stem = preg_replace('/[-_]+/', ' ', $stem) ?? $stem;
    $stem = trim((string)preg_replace('/\s+/', ' ', $stem));
    if ($stem === '') {
        return 'ERC Volunteer Workshop Photo';
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
    return implode(' ', $words);
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

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $dateStr, new DateTimeZone('America/Chicago'));
    $formattedDate = $dt ? $dt->format('D, M j, Y') : $dateStr;
    $monthKey = substr($dateStr, 0, 7);
    $monthLabel = $dt ? $dt->format('F Y') : $monthKey;

    $isPast = $dateStr < $todayIso;
    $isFull = $maxPeople > 0 && $people >= $maxPeople;
    $remaining = $maxPeople > 0 ? max(0, $maxPeople - $people) : null;
    $range = erc_volunteer_parse_time_range($timeStr);

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
foreach ($rawGallery as $idx => $relPath) {
    $rel = ltrim((string)$relPath, '/');
    if ($rel === '') {
        continue;
    }
    $galleryItems[] = [
        'index'   => count($galleryItems),
        'path'    => $rel,
        'url'     => erc_media_url($rel),
        'caption' => erc_volunteer_gallery_caption($rel),
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
    'title'       => 'Volunteer & Schedule | Lions District 2-E2 Eyeglass Recycling Center',
    'description' => 'Join Saturday volunteer shifts (9:00 AM - 3:00 PM) or schedule a group of up to 20 at 5621 Bunker Blvd, Watauga, TX. Browse sessions, register online, export to calendar, and view workshop photos.',
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

  <!-- 1. Hero & Saturday Arrival Essentials -->
  <section id="volunteer-hero" class="vol-hero" aria-labelledby="vol-hero-title">
    <div class="vol-wrap vol-hero__grid">
      <div class="vol-hero__copy">
        <p class="vol-eyebrow">
          <?= erc_icon('users', ['class' => 'vol-icon-sm']) ?>
          <span>100% Volunteer-Run · Watauga Workshop</span>
        </p>
        <h1 id="vol-hero-title" class="vol-hero__title">Volunteer at the Eyeglass Recycling Center</h1>
        <p class="vol-hero__lead">
          Every pair of eyeglasses shipped free of charge to humanitarian eye clinics across <strong>40+ countries</strong> is sorted, cleaned, measured, and boxed by unpaid community volunteers in Watauga, Texas. <strong>No prior optical experience is needed</strong>—we train you on-site.
        </p>
        <div class="vol-hero__actions">
          <a href="#schedule" class="vol-btn vol-btn--primary">
            <?= erc_icon('calendar-days', ['class' => 'vol-icon-sm']) ?>
            <span>Browse Volunteer Schedule</span>
          </a>
          <a href="#group-volunteering" class="vol-btn vol-btn--secondary">
            <?= erc_icon('user-plus', ['class' => 'vol-icon-sm']) ?>
            <span>Schedule a Group (Up to 20)</span>
          </a>
          <a href="#workstations" class="vol-btn vol-btn--ghost">
            <span>See Volunteer Workstations</span>
            <?= erc_icon('arrow-right', ['class' => 'vol-icon-sm']) ?>
          </a>
        </div>
        <dl class="vol-quick-facts" aria-label="Volunteer essentials at a glance">
          <div class="vol-quick-fact">
            <dt>Saturday Walk-Ins</dt>
            <dd class="vol-mono">9:00 AM – 3:00 PM</dd>
          </div>
          <div class="vol-quick-fact">
            <dt>Group Capacity</dt>
            <dd class="vol-mono">Up to 20 / shift</dd>
          </div>
          <div class="vol-quick-fact">
            <dt>Prior Experience</dt>
            <dd>None (training on-site)</dd>
          </div>
        </dl>
      </div>

      <aside class="vol-arrival-card" aria-label="Watauga Center arrival and entrance directions">
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
          <div class="vol-arrival-card__status-row">
            <span class="vol-status-pill" id="wataugaHoursBadge">
              <?= erc_icon('clock', ['class' => 'vol-icon-xs']) ?>
              <span id="wataugaHoursText">Open Most Saturdays · 9:00 AM – 3:00 PM</span>
            </span>
          </div>
          <h2 class="vol-arrival-card__heading">Watauga Workshop Arrival</h2>
          <p class="vol-arrival-card__address">
            <?= erc_icon('map-pin', ['class' => 'vol-icon-sm']) ?>
            <span><strong>5621 Bunker Blvd, Watauga, TX 76148</strong> · Free on-site parking</span>
          </p>
          <div class="vol-walkway-callout">
            <?= erc_icon('navigation', ['class' => 'vol-icon-sm']) ?>
            <p>
              <strong>Entrance Directions:</strong> Follow the <strong>wooden walkway along the east side of the building</strong> to reach the <strong>rear entrance</strong> (door is at the back on the east side).
            </p>
          </div>
          <p class="vol-arrival-card__hours">
            <strong>Weekdays (Mon–Fri):</strong> By appointment · <strong>Sunday:</strong> Closed
          </p>
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
              <span>Directions</span>
            </a>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <!-- 2. Individual Volunteering & Volunteer Workstations -->
  <section id="workstations" class="vol-section vol-section--band" aria-labelledby="vol-workstations-title">
    <div class="vol-wrap">
      <div class="vol-section__header">
        <div>
          <p class="vol-section__kicker">How a Shift Works</p>
          <h2 id="vol-workstations-title" class="vol-section__title">What Volunteers Do at the Center</h2>
          <p class="vol-section__desc">
            Individuals are welcome to drop in during Saturday hours (<strong>9:00 AM – 3:00 PM</strong>) or sign up online for scheduled open sessions like our <strong>2nd Saturday Open Session</strong> (typically <strong>12:00 PM – 3:00 PM</strong>). When you arrive, a workshop lead pairs you with any active station—zero prior optical background required.
          </p>
        </div>
      </div>

      <div class="vol-stations-layout">
        <ol class="vol-stations-grid">
          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">01</span>
              <span class="vol-station-icon"><?= erc_icon('glasses', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">Sorting &amp; Initial Inspection</h3>
            <p class="vol-station-card__body">
              Inspect donated eyewear and sort frames into single-vision prescription, reading glasses, bifocals/trifocals/progressives/prisms, and non-prescription sunglasses. Broken frames are set aside for parts or repair, alongside intake of donated hearing aids, cell phones, and Windows 11-ready laptops and tablets.
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">02</span>
              <span class="vol-station-icon"><?= erc_icon('sparkles', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">Washing, Sanitizing &amp; Drying</h3>
            <p class="vol-station-card__body">
              Wash donated frames and lenses thoroughly, sanitize every pair, and set them neatly on drying racks so clinic partners receive clean, ready-to-fit eyewear.
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">03</span>
              <span class="vol-station-icon"><?= erc_icon('eye', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">Measuring Prescriptions (Lensometry)</h3>
            <p class="vol-station-card__body">
              Measure exact sphere and optical power in <span class="vol-mono">0.25</span>-diopter increments using tabletop lensometers. Experienced Lions and workshop leads teach the dials step by step right at the bench.
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">04</span>
              <span class="vol-station-icon"><?= erc_icon('package', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">Packing 50-Pair Mission Boxes</h3>
            <p class="vol-station-card__body">
              Perform a final quality check, label each pair, and pack standardized <strong class="vol-mono">50</strong>-pair mission boxes by <span class="vol-mono">0.25</span>-diopter bracket (<span class="vol-mono">+0.25</span> to <span class="vol-mono">+5.00+</span> Plus &amp; Readers, <span class="vol-mono">-0.25</span> to <span class="vol-mono">-5.00+</span> Minus, and Sunglasses).
            </p>
          </li>

          <li class="vol-station-card">
            <div class="vol-station-card__top">
              <span class="vol-station-num vol-mono" aria-hidden="true">05</span>
              <span class="vol-station-icon"><?= erc_icon('file-text', ['class' => 'vol-icon-md']) ?></span>
            </div>
            <h3 class="vol-station-card__title">Administrative &amp; Logistics Support</h3>
            <p class="vol-station-card__body">
              Welcome Saturday walk-ins, log incoming donations from our <span class="vol-mono">214</span> DFW collection bins, stage mission orders, and keep workstation supplies stocked.
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
            <figcaption>Tabletop lensometry station where volunteers read exact <span class="vol-mono">0.25</span>-diopter prescriptions.</figcaption>
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
            <figcaption>Washed and sanitized frames drying on workshop racks prior to inspection and boxing.</figcaption>
          </figure>
        </div>
      </div>
    </div>
  </section>

  <!-- 3. Interactive Schedule, Calendar & Online Shift Registration -->
  <section id="schedule" class="vol-section" aria-labelledby="vol-schedule-title">
    <div class="vol-wrap">
      <div class="vol-section__header vol-section__header--split">
        <div>
          <p class="vol-section__kicker">Schedule &amp; Online Registration</p>
          <h2 id="vol-schedule-title" class="vol-section__title">Scheduled Volunteer Sessions</h2>
          <p class="vol-section__desc">
            Select a session to reserve your spot online, download an <span class="vol-mono">.ics</span> calendar reminder, or add directly to Google Calendar (<span class="vol-mono">America/Chicago</span>). Walk-ins are also always welcome Saturdays <span class="vol-mono">9:00 AM – 3:00 PM</span>.
          </p>
        </div>
      </div>

      <!-- Filter & Scope Bar -->
      <div class="vol-controls" aria-label="Filter volunteer sessions">
        <div class="vol-controls__row">
          <div class="vol-segmented" role="group" aria-label="Session timeframe">
            <button
              type="button"
              class="vol-seg-btn"
              id="scopeUpcomingBtn"
              data-scope="upcoming"
              aria-pressed="<?= $defaultScope === 'upcoming' ? 'true' : 'false' ?>"
            >
              <span>Upcoming</span>
              <span class="vol-count-pill vol-mono" id="upcomingCountPill"><?= count($upcomingEvents) ?></span>
            </button>
            <button
              type="button"
              class="vol-seg-btn"
              id="scopeAllBtn"
              data-scope="all"
              aria-pressed="<?= $defaultScope === 'all' ? 'true' : 'false' ?>"
            >
              <span>All Scheduled Sessions</span>
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
              placeholder="Search sessions, clubs, or dates..."
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
              Showing all <strong class="vol-mono"><?= count($normalizedEvents) ?></strong> scheduled sessions in the directory (latest posted month: <strong><?= e($eventMonthsList[count($eventMonthsList) - 1]['label'] ?? $defaultMonth) ?></strong>). Newly added future sessions will appear automatically under <em>Upcoming</em>—and Saturday walk-ins (<span class="vol-mono">9:00 AM – 3:00 PM</span>) remain open year-round.
            </p>
          </div>
        <?php endif; ?>
      </div>

      <div class="vol-schedule-layout">
        <!-- Monthly Calendar Column -->
        <aside class="vol-calendar-panel" aria-labelledby="calMonthHeading">
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
                $defDt = DateTimeImmutable::createFromFormat('!Y-m', $defaultMonth, new DateTimeZone('America/Chicago'));
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
                <!-- Server-rendered fallback for defaultMonth; hydrated seamlessly by volunteer.js -->
                <?php
                  $calDt = DateTimeImmutable::createFromFormat('!Y-m-d', $defaultMonth . '-01', new DateTimeZone('America/Chicago'));
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
              <span class="vol-legend-item"><span class="vol-legend-swatch vol-legend-swatch--event" aria-hidden="true"></span> Scheduled session</span>
              <span class="vol-legend-item"><span class="vol-legend-swatch vol-legend-swatch--sat" aria-hidden="true"></span> Saturday walk-in window</span>
            </div>
            <div class="vol-calendar-filter-bar">
              <label class="vol-checkbox-label">
                <input type="checkbox" id="filterByCalMonthCheck">
                <span>Show selected calendar month only</span>
              </label>
              <button type="button" class="vol-link-btn" id="clearDayFilterBtn" hidden>Clear date filter</button>
            </div>
          </div>
        </aside>

        <!-- Event List Column -->
        <div class="vol-events-column">
          <div class="vol-events-status-bar">
            <p id="eventsFilterSummary" class="vol-events-summary" aria-live="polite">
              Showing <span class="vol-mono"><?= count($defaultScope === 'upcoming' ? $upcomingEvents : $normalizedEvents) ?></span> scheduled session(s).
            </p>
          </div>

          <div id="eventsEmptyState" class="vol-empty-card" hidden>
            <?= erc_icon('calendar', ['class' => 'vol-icon-lg']) ?>
            <h3 id="eventsEmptyHeading">No matching sessions in this view</h3>
            <p id="eventsEmptyCopy">
              Walk-in volunteers are always welcome most Saturdays from <strong>9:00 AM – 3:00 PM</strong> at 5621 Bunker Blvd, Watauga, TX 76148.
            </p>
            <div class="vol-empty-card__actions">
              <button type="button" class="vol-btn vol-btn--primary" id="resetFiltersBtn">Show All Scheduled Sessions</button>
              <a href="#group-volunteering" class="vol-btn vol-btn--secondary">Book a Group Session</a>
            </div>
          </div>

          <div id="volunteerEventsList" class="vol-events-list">
            <?php foreach ($normalizedEvents as $ev): ?>
              <?php
                $isHiddenByDefaultScope = ($defaultScope === 'upcoming' && $ev['isPast']);
                $pct = $ev['maxPeople'] > 0 ? min(100, (int)round(($ev['people'] / $ev['maxPeople']) * 100)) : 0;
                $popId = 'cal-menu-' . $ev['id'];
              ?>
              <div
                class="vol-event-item"
                data-event-id="<?= (int)$ev['id'] ?>"
                data-event-date="<?= e($ev['date']) ?>"
                data-event-month="<?= e($ev['monthKey']) ?>"
                data-event-past="<?= $ev['isPast'] ? '1' : '0' ?>"
                <?= $isHiddenByDefaultScope ? 'hidden' : '' ?>
              >
                <article class="vol-event-card <?= $ev['isPast'] ? 'is-past' : '' ?> <?= $ev['isFull'] ? 'is-full' : '' ?>">
                  <div class="vol-event-card__media <?= $ev['isLogo'] ? 'is-logo' : '' ?>">
                    <img
                      src="<?= e($ev['imageUrl']) ?>"
                      alt="<?= e($ev['title']) ?>"
                      width="320"
                      height="240"
                      loading="lazy"
                      decoding="async"
                      data-fallback="/images/placeholder-event.svg"
                    >
                  </div>

                  <div class="vol-event-card__body">
                    <div class="vol-event-card__badges">
                      <span class="vol-date-badge vol-mono"><?= e($ev['formattedDate']) ?></span>
                      <?php if ($ev['isPast']): ?>
                        <span class="vol-state-badge vol-state-badge--past">Past Date</span>
                      <?php elseif ($ev['isFull']): ?>
                        <span class="vol-state-badge vol-state-badge--full">Event Full</span>
                      <?php elseif ($ev['remaining'] !== null && $ev['remaining'] <= 3): ?>
                        <span class="vol-state-badge vol-state-badge--warn">Filling Fast</span>
                      <?php else: ?>
                        <span class="vol-state-badge vol-state-badge--open">Open</span>
                      <?php endif; ?>
                    </div>

                    <h3 class="vol-event-card__title"><?= e($ev['title']) ?></h3>

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

                    <?php if ($ev['description'] !== ''): ?>
                      <p class="vol-event-card__desc"><?= e($ev['description']) ?></p>
                    <?php endif; ?>

                    <div class="vol-capacity">
                      <div class="vol-capacity__row">
                        <span class="vol-capacity__label">Registered Seats</span>
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

                    <div class="vol-event-card__actions">
                      <button
                        type="button"
                        class="vol-btn vol-btn--primary vol-register-btn"
                        data-action="open-register"
                        data-event-id="<?= (int)$ev['id'] ?>"
                        <?= $ev['isFull'] ? 'disabled' : '' ?>
                      >
                        <?= erc_icon($ev['isFull'] ? 'circle-alert' : 'user-plus', ['class' => 'vol-icon-xs']) ?>
                        <span><?= $ev['isFull'] ? 'Event Full' : 'Register' ?></span>
                      </button>

                      <div class="vol-cal-menu-wrap">
                        <button
                          type="button"
                          class="vol-btn vol-btn--secondary vol-cal-trigger"
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
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. Group Volunteer Days -->
  <section id="group-volunteering" class="vol-section vol-section--band" aria-labelledby="vol-group-title">
    <div class="vol-wrap vol-group-grid">
      <div class="vol-group-copy">
        <p class="vol-section__kicker">Organizations, Teams &amp; Youth Service</p>
        <h2 id="vol-group-title" class="vol-section__title">Group Volunteer Days</h2>
        <p class="vol-group-lead">
          Bring your team or organization to the Watauga workshop for a dedicated three-hour service shift. We accommodate up to <strong class="vol-mono">20 volunteers</strong> per group in our morning (<span class="vol-mono">9:00 AM – 12:00 PM</span>) or afternoon (<span class="vol-mono">12:00 PM – 3:00 PM</span>) window.
        </p>

        <h3 class="vol-subhead">Organizations We Regularly Host</h3>
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
        <h3 class="vol-group-card__title">Schedule a Group Session</h3>
        <ol class="vol-group-steps">
          <li>
            <strong>Choose a shift window:</strong> Morning (<span class="vol-mono">9:00 AM – 12:00 PM</span>) or Afternoon (<span class="vol-mono">12:00 PM – 3:00 PM</span>), for up to <span class="vol-mono">20</span> volunteers.
          </li>
          <li>
            <strong>Complete the form or reach out:</strong> Download our one-page scheduling form and email it to <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>, call <a href="tel:<?= e($contactPhoneLink) ?>" class="vol-mono"><?= e($contactPhone) ?></a>, or send an online inquiry.
          </li>
          <li>
            <strong>Confirm &amp; arrive:</strong> Park free on-site at <span class="vol-mono">5621 Bunker Blvd</span> and follow the wooden walkway along the east side of the building to the rear entrance.
          </li>
        </ol>

        <div class="vol-group-card__actions">
          <a href="/files/Group-Volunteer-Form.pdf" download class="vol-btn vol-btn--primary">
            <?= erc_icon('download', ['class' => 'vol-icon-sm']) ?>
            <span>Download Group-Volunteer-Form.pdf</span>
          </a>
          <a href="/contact?subject=group-volunteer" class="vol-btn vol-btn--secondary">
            <?= erc_icon('mail', ['class' => 'vol-icon-sm']) ?>
            <span>Request Group Date Online</span>
          </a>
        </div>

        <p class="vol-group-card__contact">
          Direct scheduling: <a href="tel:<?= e($contactPhoneLink) ?>" class="vol-mono"><?= e($contactPhone) ?></a> · <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
        </p>
      </div>
    </div>
  </section>

  <!-- 5. Community Volunteer Photo Gallery & Lightbox -->
  <section id="volunteer-gallery" class="vol-section" aria-labelledby="vol-gallery-title">
    <div class="vol-wrap">
      <div class="vol-section__header vol-section__header--split">
        <div>
          <p class="vol-section__kicker">Workshop &amp; Mission Dispatch Archive</p>
          <h2 id="vol-gallery-title" class="vol-section__title">Community Volunteer Photo Gallery</h2>
          <p class="vol-section__desc">
            Lions Clubs, youth chapters, and mission deliveries in action. Select any photo to open the accessible high-resolution viewer (<span class="vol-mono" id="galleryTotalBadge"><?= count($galleryItems) ?></span> photos).
          </p>
        </div>
        <div class="vol-gallery-toolbar">
          <button type="button" class="vol-btn vol-btn--secondary" id="refreshGalleryBtn">
            <?= erc_icon('refresh-cw', ['class' => 'vol-icon-xs']) ?>
            <span>Refresh Photos</span>
          </button>
        </div>
      </div>

      <p id="galleryStatusMsg" class="vol-gallery-status" aria-live="polite" hidden></p>

      <ul id="volunteerGalleryGrid" class="vol-gallery-grid">
        <?php foreach ($galleryItems as $item): ?>
          <li class="vol-gallery-item">
            <button
              type="button"
              class="vol-gallery-card"
              data-gallery-index="<?= (int)$item['index'] ?>"
              data-gallery-src="<?= e($item['url']) ?>"
              data-gallery-caption="<?= e($item['caption']) ?>"
              aria-label="Open photo: <?= e($item['caption']) ?>"
            >
              <span class="vol-gallery-card__media">
                <img
                  src="<?= e($item['url']) ?>"
                  alt="<?= e($item['caption']) ?>"
                  loading="lazy"
                  decoding="async"
                  width="640"
                  height="480"
                >
                <span class="vol-gallery-card__zoom" aria-hidden="true">
                  <?= erc_icon('zoom-in', ['class' => 'vol-icon-sm']) ?>
                </span>
              </span>
              <span class="vol-gallery-card__caption"><?= e($item['caption']) ?></span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- 6. Watauga Arrival & Next Steps Strip -->
  <section id="arrival-guide" class="vol-section vol-section--band" aria-labelledby="vol-arrival-title">
    <div class="vol-wrap vol-arrival-strip">
      <div class="vol-arrival-strip__main">
        <p class="vol-section__kicker">Visiting the Center</p>
        <h2 id="vol-arrival-title" class="vol-section__title">Finding the Entrance at 5621 Bunker Blvd</h2>
        <p>
          Free parking is available right in front of the building at <strong>5621 Bunker Blvd, Watauga, TX 76148</strong>. Please follow the <strong>wooden walkway along the east side of the building to the rear entrance</strong>—the workshop door is at the back on the east side.
        </p>
      </div>
      <div class="vol-arrival-strip__links">
        <a href="/locations" class="vol-btn vol-btn--secondary">
          <?= erc_icon('map-pin', ['class' => 'vol-icon-sm']) ?>
          <span>Explore 214 Drop-Off Bins</span>
        </a>
        <a href="/donate" class="vol-btn vol-btn--secondary">
          <?= erc_icon('glasses', ['class' => 'vol-icon-sm']) ?>
          <span>See Accepted Donations</span>
        </a>
        <a href="/contact?subject=volunteer" class="vol-btn vol-btn--primary">
          <?= erc_icon('mail', ['class' => 'vol-icon-sm']) ?>
          <span>Ask a Volunteer Question</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Native Modal: Volunteer Shift Registration -->
  <dialog id="volunteerRegDialog" class="vol-dialog" closedby="any" aria-labelledby="volRegDialogTitle">
    <div class="vol-dialog__panel">
      <div class="vol-dialog__header">
        <div>
          <p class="vol-dialog__kicker">Online Shift Registration</p>
          <h2 id="volRegDialogTitle" class="vol-dialog__title">Register for Volunteer Shift</h2>
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
        <h3 id="regEventTitle" class="vol-dialog__event-title">Volunteer Session</h3>
        <p class="vol-dialog__event-meta">
          <span id="regEventDateTime" class="vol-mono"></span>
          <span> · </span>
          <span id="regEventLocation"></span>
        </p>
        <p class="vol-dialog__event-seats">
          Remaining seats: <strong id="regEventSeats" class="vol-mono"></strong>
        </p>
      </div>

      <form id="volunteerSignupForm" class="vol-form" novalidate>
        <input type="hidden" id="regEventId" name="eventId" value="">

        <!-- Honeypot trap for bots; hidden from keyboard & assistive tech -->
        <div class="vol-honeypot" aria-hidden="true">
          <label for="regWebsiteUrl">Leave this field blank</label>
          <input type="text" id="regWebsiteUrl" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="vol-field">
          <label for="regName">Full Name <span class="vol-req" aria-hidden="true">*</span></label>
          <span id="regNameHint" class="vol-hint">Required · Up to 120 characters</span>
          <input
            type="text"
            id="regName"
            name="name"
            required
            maxlength="120"
            autocomplete="name"
            enterkeyhint="next"
            aria-invalid="false"
            aria-describedby="regNameHint"
            data-error="regNameError"
            autofocus
          >
          <div id="regNameError" class="vol-field-error">
            <?= erc_icon('circle-alert', ['class' => 'vol-icon-xs']) ?>
            <span>Error: Please enter your full name (1–120 characters).</span>
          </div>
        </div>

        <div class="vol-field">
          <label for="regEmail">Email Address <span class="vol-req" aria-hidden="true">*</span></label>
          <span id="regEmailHint" class="vol-hint">Format: you@example.com — confirmation details sent here</span>
          <input
            type="email"
            id="regEmail"
            name="email"
            inputmode="email"
            required
            maxlength="254"
            autocomplete="email"
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
            <span>Error: Enter a valid email address in the format you@example.com.</span>
          </div>
        </div>

        <div class="vol-field">
          <label for="regPhone">Phone Number <span class="vol-opt">(optional)</span></label>
          <span id="regPhoneHint" class="vol-hint">Format: (817) 555-0199 — optional contact for schedule updates</span>
          <input
            type="tel"
            id="regPhone"
            name="phone"
            inputmode="tel"
            maxlength="40"
            autocomplete="tel"
            enterkeyhint="done"
            aria-invalid="false"
            aria-describedby="regPhoneHint"
            data-error="regPhoneError"
          >
          <div id="regPhoneError" class="vol-field-error">
            <?= erc_icon('circle-alert', ['class' => 'vol-icon-xs']) ?>
            <span>Error: Phone number must be 40 characters or fewer.</span>
          </div>
        </div>

        <div id="regFormStatus" class="vol-form-status" role="status" aria-live="polite" hidden></div>

        <div class="vol-dialog__footer">
          <button type="submit" id="regSubmitBtn" class="vol-btn vol-btn--primary">
            <span class="vol-spinner" id="regSpinner" aria-hidden="true" hidden></span>
            <span id="regSubmitText">Confirm Registration</span>
          </button>
          <button type="button" class="vol-btn vol-btn--secondary" data-dialog-close>Cancel</button>
        </div>
      </form>

      <!-- Post-Signup Confirmation State -->
      <div id="regSuccessView" class="vol-reg-success" hidden>
        <div class="vol-reg-success__banner">
          <?= erc_icon('circle-check-big', ['class' => 'vol-icon-lg']) ?>
          <div>
            <h3 id="regSuccessHeading" tabindex="-1">You're Registered!</h3>
            <p id="regSuccessMessage"></p>
          </div>
        </div>

        <div class="vol-walkway-callout">
          <?= erc_icon('navigation', ['class' => 'vol-icon-sm']) ?>
          <p>
            <strong>Arrival Reminder:</strong> Free parking at 5621 Bunker Blvd, Watauga. Follow the <strong>wooden walkway along the east side of the building</strong> to the rear entrance.
          </p>
        </div>

        <div class="vol-reg-success__actions">
          <button type="button" id="regSuccessIcsBtn" class="vol-btn vol-btn--primary">
            <?= erc_icon('download', ['class' => 'vol-icon-sm']) ?>
            <span>Download .ics Reminder</span>
          </button>
          <a id="regSuccessGcalLink" href="#" target="_blank" rel="noopener noreferrer" class="vol-btn vol-btn--secondary">
            <?= erc_icon('calendar-plus', ['class' => 'vol-icon-sm']) ?>
            <span>Add to Google Calendar</span>
          </a>
          <button type="button" class="vol-btn vol-btn--ghost" data-dialog-close>Done</button>
        </div>
      </div>
    </div>
  </dialog>

  <!-- Native Modal: Accessible Gallery Photo Lightbox -->
  <dialog id="volunteerLightbox" class="vol-lightbox" closedby="any" aria-labelledby="lightboxCaption">
    <div class="vol-lightbox__shell">
      <div class="vol-lightbox__topbar">
        <p class="vol-lightbox__counter vol-mono" id="lightboxCounter" aria-live="polite">1 of <?= count($galleryItems) ?></p>
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
