<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'about',
    'title'       => 'The Crew & The Workshop | Lions District 2-E2 ERC',
    'description' => '100% volunteer-run 501(c)(3) optical workshop founded in 2013 in District 2-E2. Meet the Board of Directors, explore Bylaws governance, Helen Keller Award, Life Membership, and 24 partner organizations.',
    'canonical'   => '/about',
    'preload_img' => '',
    'og_image'    => '/images/our-story-group.png',
    'css'         => ['/css/pages/missions-about.css'],
    'js_modules'  => ['/js/pages/missions-about.js'],
    'json_ld'     => [],
    'noindex'     => false,
];

$boardRaw = erc_load_json('data/board.json', []);
$currentBoard = [];
$previousBoard = [];
if (is_array($boardRaw)) {
    foreach ($boardRaw as $member) {
        if (!is_array($member)) {
            continue;
        }
        $name = trim((string)($member['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $tag = strtolower(trim((string)($member['tag'] ?? 'current')));
        $rawImg = trim((string)($member['image'] ?? ''));
        $imgUrl = $rawImg !== '' ? erc_media_url($rawImg) : '/images/placeholder-volunteer.svg';
        $entry = [
            'name'  => $name,
            'image' => $imgUrl,
            'tag'   => $tag === 'previous' ? 'previous' : 'current',
        ];
        if ($entry['tag'] === 'previous') {
            $previousBoard[] = $entry;
        } else {
            $currentBoard[] = $entry;
        }
    }
}

$partnersRaw = erc_load_json('data/partners.json', []);
$partners = [];
if (is_array($partnersRaw)) {
    foreach ($partnersRaw as $partner) {
        if (is_string($partner) && trim($partner) !== '') {
            $partners[] = trim($partner);
        }
    }
}

$coreValues = [
    ['num' => '01', 'title' => 'Integrity', 'desc' => 'Transparency and honesty across all operations, stewardship, and partner reporting.'],
    ['num' => '02', 'title' => 'Compassion', 'desc' => 'Committed to improving sight and lives across North Texas and global communities.'],
    ['num' => '03', 'title' => 'Sustainability', 'desc' => 'Landfill reduction and responsible material reuse across eyewear and electronics.'],
    ['num' => '04', 'title' => 'Community', 'desc' => 'Collective service uniting Lions Clubs, student groups, congregations, and civic partners.'],
    ['num' => '05', 'title' => 'Excellence', 'desc' => 'Consistent quality standards in sorting, washing, lensometry, and 50-pair packaging.'],
];

$impactGroups = [
    ['tag' => 'GROUP · 01', 'title' => 'Children', 'desc' => 'Stronger educational outcomes and healthy development.'],
    ['tag' => 'GROUP · 02', 'title' => 'Adults', 'desc' => 'Improved employability and household economic stability.'],
    ['tag' => 'GROUP · 03', 'title' => 'Seniors', 'desc' => 'Reduced dependence on others and preserved daily independence.'],
    ['tag' => 'GROUP · 04', 'title' => 'Communities', 'desc' => 'Broader capacity to work, learn, and thrive together.'],
];

$totalBoardCount = count($currentBoard) + count($previousBoard);

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main ma-page" tabindex="-1">
  <!-- 1. Hero & Founding Workshop Story -->
  <section class="ma-about-hero" id="story-values" aria-labelledby="about-hero-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>THE CREW &amp; THE WORKSHOP</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>DISTRICT 2-E2 &middot; WATAUGA, TX</span>
      </div>

      <div class="ma-about-hero__grid">
        <div class="ma-about-hero__copy">
          <h1 id="about-hero-title" class="v2-display v2-display--hero">
            100% <span class="v2-knockout">VOLUNTEER</span><br>SIGHT WORKSHOP<span class="v2-dot" aria-hidden="true"></span>
          </h1>

          <p class="v2-note ma-about-note">
            <span>zero paid staff &middot; under &dollar;0.10 per pair</span>
            <?= erc_curved_arrow('up-right') ?>
          </p>

          <p class="lead">
            Founded in <strong>2013</strong> and recognized as a <span class="mono">501(c)(3)</span> nonprofit in <strong>2014</strong>, the <strong>District 2-E2 Lions Eyeglass Recycling Center Foundation</strong> operates out of <strong>5621 Bunker Blvd, Watauga, TX 76148</strong>&mdash;within Lions Clubs International, Multiple District 2 (Texas), District 2-E2, and under the auspices of the Texas Lions Eyeglass Recycling Center.
          </p>

          <dl class="ma-identity-specs">
            <div class="ma-identity-specs__cell">
              <dt>FOUNDED</dt>
              <dd class="mono">2013</dd>
            </div>
            <div class="ma-identity-specs__cell">
              <dt>TAX STATUS</dt>
              <dd class="mono">501(c)(3) &middot; 2014</dd>
            </div>
            <div class="ma-identity-specs__cell">
              <dt>UNIT COST</dt>
              <dd class="mono">&lt; &dollar;0.10 US / PAIR</dd>
            </div>
            <div class="ma-identity-specs__cell">
              <dt>PAID STAFF</dt>
              <dd class="mono">0 (100% VOLUNTEER)</dd>
            </div>
          </dl>

          <div class="ma-sheet-actions">
            <a class="btn btn--primary" href="#board">
              <span>MEET THE BOARD (<?= e((string)$totalBoardCount) ?>)</span>
              <?= erc_icon('arrow-up-right', ['width' => '16', 'height' => '16']) ?>
            </a>
            <a class="btn btn--secondary" href="<?= e(erc_media_url('files/ERC-Bylaws.pdf')) ?>">
              <?= erc_icon('file-text', ['width' => '16', 'height' => '16']) ?>
              <span>FOUNDATION BYLAWS (PDF)</span>
            </a>
          </div>
        </div>

        <div class="ma-about-hero__media">
          <figure class="ma-story-frame">
            <div class="ma-story-frame__top">
              <span class="mono">FIG. 01 &middot; 5621 BUNKER BLVD, WATAUGA TX</span>
              <span class="v2-note ma-story-frame__callout">
                <span>ribbon-cutting &amp; founding crew</span>
                <?= erc_curved_arrow('down-left') ?>
              </span>
            </div>
            <img
              class="ma-story-frame__img"
              src="<?= e(erc_media_url('images/our-story-group.png')) ?>"
              alt="Founding team and Lions District 2-E2 directors gathered outside the Watauga Eyeglass Recycling Center"
              loading="eager"
              decoding="async"
            >
            <figcaption class="ma-story-frame__caption">
              Founding Lions and Board team outside the Watauga workshop (rear entrance via east-side wooden walkway; free on-site parking).
            </figcaption>
          </figure>

          <div class="ma-workshop-strip">
            <figure class="ma-mini-frame">
              <img
                src="<?= e(erc_media_url('gallery/founders-workday.jpg')) ?>"
                alt="Founders and volunteers sorting eyewear during a Saturday workday in Watauga"
                loading="lazy"
                decoding="async"
              >
              <figcaption class="mono">FIG. 02 &middot; FOUNDERS WORKDAY</figcaption>
            </figure>
            <figure class="ma-mini-frame">
              <img
                src="<?= e(erc_media_url('gallery/past-district-governors.jpg')) ?>"
                alt="District 2-E2 Past District Governors gathered in service at the recycling center"
                loading="lazy"
                decoding="async"
              >
              <figcaption class="mono">FIG. 03 &middot; PAST DISTRICT GOVERNORS</figcaption>
            </figure>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. Five Core Values, Human Impact Groups & Foundation Governance -->
  <section class="section section--subtle" id="governance" aria-labelledby="governance-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>CHARTER, VALUES &amp; GOVERNANCE</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>LION YEAR JULY 1 &ndash; JUNE 30</span>
      </div>

      <div class="ma-section-intro">
        <h2 id="governance-title" class="v2-display v2-display--lg">
          PRINCIPLES &amp; BYLAWS<span class="v2-dot" aria-hidden="true"></span>
        </h2>
        <p class="lead">
          Corporate Bylaws approved by the Board of Directors on <strong>March 20, 2014</strong>, and ratified at the Annual Meeting on <strong>April 12, 2014</strong>.
        </p>
      </div>

      <div class="ma-values-grid">
        <?php foreach ($coreValues as $val): ?>
          <article class="ma-value-card">
            <span class="ma-value-card__num mono"><?= e($val['num']) ?></span>
            <h3 class="ma-value-card__title"><?= e($val['title']) ?></h3>
            <p class="ma-value-card__desc"><?= e($val['desc']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="ma-impact-strip" aria-label="Human Impact Groups">
        <?php foreach ($impactGroups as $group): ?>
          <article class="ma-impact-cell">
            <span class="mono ma-impact-cell__tag"><?= e($group['tag']) ?></span>
            <h3 class="ma-impact-cell__title"><?= e($group['title']) ?></h3>
            <p class="ma-impact-cell__desc"><?= e($group['desc']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>

      <article class="v2-schematic-card ma-bylaws-card">
        <div class="ma-bylaws-card__header">
          <div>
            <div class="v2-schematic-card__head">
              <span class="v2-schematic-card__num">CHARTER SPEC</span>
              <span>IRC 501(c)(3) &amp; 170(c)(2)</span>
            </div>
            <h3 class="v2-display v2-display--sm ma-bylaws-card__title">FOUNDATION GOVERNANCE STRUCTURE</h3>
          </div>
          <a class="btn btn--primary" href="<?= e(erc_media_url('files/ERC-Bylaws.pdf')) ?>">
            <?= erc_icon('download', ['width' => '16', 'height' => '16']) ?>
            <span>DOWNLOAD BYLAWS (PDF &middot; 10 PAGES)</span>
          </a>
        </div>

        <dl class="ma-bylaws-grid">
          <div class="ma-bylaws-row">
            <dt>ELECTED BOARD &amp; TERMS</dt>
            <dd><strong>9 or more</strong> elected directors serving staggered <strong>3-year terms</strong> (maximum <strong>2</strong> consecutive terms; no more than <strong>3</strong> directors from the same Lions Club).</dd>
          </div>
          <div class="ma-bylaws-row">
            <dt>NON-VOTING EX-OFFICIO</dt>
            <dd>Present <strong>District Governor</strong>, present <strong>Vice District Governor(s)</strong>, and <strong>immediate Past District Governor</strong> of District 2-E2.</dd>
          </div>
          <div class="ma-bylaws-row">
            <dt>EXECUTIVE COMMITTEE (5 ROLES)</dt>
            <dd><strong>President</strong>, <strong>First Vice President</strong>, <strong>Second Vice President</strong>, <strong>Secretary</strong>, and <strong>Treasurer</strong>.</dd>
          </div>
          <div class="ma-bylaws-row">
            <dt>MEETINGS &amp; COMPENSATION</dt>
            <dd>Regular Board meetings held <strong>monthly</strong>; Annual Meeting each <strong>June</strong>. Directors serve strictly <strong>without compensation</strong> (reimbursable only for pre-approved expenses).</dd>
          </div>
          <div class="ma-bylaws-row">
            <dt>ENDOWMENT &amp; MAIL</dt>
            <dd>Endowment funds kept in <strong>separate accounting records</strong> (<span class="mono">ERC-Bylaws.pdf</span>). Mailing &amp; check address: <span class="mono">P.O. Box 1854, Keller, TX 76244</span>.</dd>
          </div>
        </dl>
      </article>
    </div>
  </section>

  <!-- 3. Contrast Stage Sheet: Leadership Crew Roster (12 Current + 4 Previous Board Directors) -->
  <section class="v2-sheet ma-board-sheet" id="board" data-board-roster aria-labelledby="board-title">
    <div class="v2-sheet__inner">
      <div class="v2-eyebrow">
        <span>LEADERSHIP CREW ROSTER</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span><?= e((string)count($currentBoard)) ?> CURRENT &middot; <?= e((string)count($previousBoard)) ?> PREVIOUS</span>
      </div>

      <div class="ma-sheet-head">
        <div>
          <h2 id="board-title" class="v2-display v2-display--lg">
            BOARD OF <span class="v2-accent-text">DIRECTORS</span><span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="lead">
            Volunteer stewards overseeing Watauga collections, optical sorting, and humanitarian dispatches without compensation.
          </p>
        </div>
        <p class="v2-note">
          <span>unpaid Lions stewardship</span>
          <?= erc_curved_arrow('down-left') ?>
        </p>
      </div>

      <div class="ma-board-filters" role="group" aria-label="Filter Board of Directors roster">
        <button type="button" class="ma-board-filter is-active" data-board-filter="all" aria-pressed="true">
          ALL DIRECTORS (<?= e((string)$totalBoardCount) ?>)
        </button>
        <button type="button" class="ma-board-filter" data-board-filter="current" aria-pressed="false">
          CURRENT BOARD (<?= e((string)count($currentBoard)) ?>)
        </button>
        <button type="button" class="ma-board-filter" data-board-filter="previous" aria-pressed="false">
          PREVIOUS BOARD (<?= e((string)count($previousBoard)) ?>)
        </button>
      </div>

      <p class="visually-hidden" aria-live="polite" data-board-status>
        Showing all <?= e((string)$totalBoardCount) ?> Board of Directors records.
      </p>

      <div class="ma-board-group" data-board-group="current">
        <h3 class="ma-board-group__title">
          <span class="mono">CURRENT BOARD OF DIRECTORS</span>
          <span class="badge badge--brand"><?= e((string)count($currentBoard)) ?></span>
        </h3>
        <?php if (count($currentBoard) > 0): ?>
          <div class="ma-board-grid">
            <?php foreach ($currentBoard as $idx => $member): ?>
              <?php $serial = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT); ?>
              <article class="ma-board-card" data-board-card="current">
                <div class="ma-board-card__frame">
                  <img
                    class="ma-board-card__img"
                    src="<?= e($member['image']) ?>"
                    data-fallback-src="/images/placeholder-volunteer.svg"
                    alt="Portrait of <?= e($member['name']) ?>, Current Board Director"
                    loading="lazy"
                    decoding="async"
                  >
                </div>
                <div class="ma-board-card__meta">
                  <span class="ma-board-card__badge mono">DIR &middot; <?= e($serial) ?> &middot; CURRENT</span>
                  <h4 class="ma-board-card__name"><?= e($member['name']) ?></h4>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="callout callout--info">
            <?= erc_icon('info', ['class' => 'callout__icon', 'width' => '20', 'height' => '20']) ?>
            <div class="callout__body">
              <p>Current Board roster is temporarily unavailable.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <?php if (count($previousBoard) > 0): ?>
        <div class="ma-board-group" data-board-group="previous">
          <h3 class="ma-board-group__title">
            <span class="mono">PREVIOUS BOARD MEMBERS</span>
            <span class="badge"><?= e((string)count($previousBoard)) ?></span>
          </h3>
          <div class="ma-board-grid ma-board-grid--previous">
            <?php foreach ($previousBoard as $idx => $member): ?>
              <?php $serial = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT); ?>
              <article class="ma-board-card ma-board-card--previous" data-board-card="previous">
                <div class="ma-board-card__frame">
                  <img
                    class="ma-board-card__img"
                    src="<?= e($member['image']) ?>"
                    data-fallback-src="/images/placeholder-volunteer.svg"
                    alt="Portrait of <?= e($member['name']) ?>, Previous Board Member"
                    loading="lazy"
                    decoding="async"
                  >
                </div>
                <div class="ma-board-card__meta">
                  <span class="ma-board-card__badge mono">PREV &middot; <?= e($serial) ?> &middot; ALUMNI</span>
                  <h4 class="ma-board-card__name"><?= e($member['name']) ?></h4>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- 4. Helen Keller Award ("Knights of Sight") & ERC Life Membership -->
  <section class="section" id="recognition" aria-labelledby="recognition-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>LIONS RECOGNITION &amp; CREDENTIALS</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span>HONOR VOLUNTEERS &amp; CLUBS</span>
      </div>

      <div class="ma-section-intro">
        <h2 id="recognition-title" class="v2-display v2-display--lg">
          HELEN KELLER &amp; LIFE MEMBERSHIP<span class="v2-dot" aria-hidden="true"></span>
        </h2>
        <p class="lead">
          Recognize dedicated Lions and supporters with permanent Watauga ERC honors while sustaining global vision missions.
        </p>
      </div>

      <div class="ma-recognition-grid">
        <article class="v2-ticket ma-recognition-ticket">
          <div class="v2-ticket__main">
            <div class="v2-ticket__meta">
              <span>KNIGHTS OF SIGHT</span>
              <span>SERVICE HOURS OR CONTRIBUTION</span>
            </div>
            <h3 class="v2-ticket__title">Helen Keller Award</h3>
            <p>
              Lions Club members may apply by <strong>documenting volunteer service hours</strong> at the ERC or through one of three contribution tiers:
            </p>
            <div class="ma-tier-strip" aria-label="Helen Keller Award Contribution Tiers">
              <div class="ma-tier-box">
                <span class="mono ma-tier-box__label">BENEFACTOR</span>
                <span class="ma-tier-box__amount">&dollar;250</span>
              </div>
              <div class="ma-tier-box">
                <span class="mono ma-tier-box__label">ENDOWMENT</span>
                <span class="ma-tier-box__amount">&dollar;500</span>
              </div>
              <div class="ma-tier-box">
                <span class="mono ma-tier-box__label">PATRON</span>
                <span class="ma-tier-box__amount">&dollar;1,000</span>
              </div>
            </div>
            <p class="ma-ticket-note">
              <strong>Package:</strong> Award certificate/plaque, congratulatory letter, ERC Life Membership pin, and permanent engraving on a brass wall plate at the center (presented in person by an ERC Director or mailed).
            </p>
            <div class="cluster">
              <a class="btn btn--gold" href="<?= e(erc_media_url('files/Helen-Keller-Award-Application.pdf')) ?>">
                <?= erc_icon('download', ['width' => '16', 'height' => '16']) ?>
                <span>HELEN KELLER APPLICATION (PDF)</span>
              </a>
            </div>
          </div>
          <div class="v2-ticket__stub">
            <span class="v2-ticket__serial">AWARD &middot; 01</span>
            <?= erc_icon('award', ['width' => '22', 'height' => '22']) ?>
          </div>
        </article>

        <article class="v2-ticket ma-recognition-ticket">
          <div class="v2-ticket__main">
            <div class="v2-ticket__meta">
              <span>LIONS CLUB SPONSORSHIP</span>
              <span>PRESENTED BY ERC DIRECTOR OR MAILED</span>
            </div>
            <h3 class="v2-ticket__title">ERC Life Membership</h3>
            <p>
              Allows a Lions Club to sponsor an official <strong>ERC Life Membership</strong> recognition honoring an individual Lion&rsquo;s service to sight preservation.
            </p>
            <p class="ma-ticket-note">
              Awards may be presented in person at a club meeting by an ERC Director or mailed directly to the sponsoring Lions Club or recipient.
            </p>
            <div class="cluster">
              <a class="btn btn--primary" href="<?= e(erc_media_url('files/ERC-Life-Membership-Application.pdf')) ?>">
                <?= erc_icon('download', ['width' => '16', 'height' => '16']) ?>
                <span>LIFE MEMBERSHIP FORM (PDF)</span>
              </a>
              <a class="btn btn--secondary" href="/contact?subject=partnership">
                <?= erc_icon('mail', ['width' => '16', 'height' => '16']) ?>
                <span>COORDINATE PRESENTATION</span>
              </a>
            </div>
          </div>
          <div class="v2-ticket__stub">
            <span class="v2-ticket__serial">MEMBER &middot; 02</span>
            <?= erc_icon('building-2', ['width' => '22', 'height' => '22']) ?>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- 5. All 24 Documented Partners & Friends -->
  <section class="section section--subtle" id="partners" aria-labelledby="partners-title">
    <div class="container">
      <div class="v2-eyebrow">
        <span>COMMUNITY &amp; MISSION NETWORK</span>
        <span class="v2-eyebrow__rule" aria-hidden="true"></span>
        <span><?= e((string)count($partners)) ?> DOCUMENTED PARTNERS</span>
      </div>

      <div class="ma-partners-head">
        <div>
          <h2 id="partners-title" class="v2-display v2-display--lg">
            PARTNERS &amp; FRIENDS<span class="v2-dot" aria-hidden="true"></span>
          </h2>
          <p class="lead">
            Lions Clubs, student service chapters, congregations, and humanitarian mission organizations whose hands keep Watauga shipments moving.
          </p>
        </div>
        <a class="btn btn--secondary btn--lg" href="/contact?subject=partnership">
          <?= erc_icon('heart-handshake', ['width' => '18', 'height' => '18']) ?>
          <span>PARTNER WITH US</span>
        </a>
      </div>

      <?php if (count($partners) > 0): ?>
        <ol class="ma-partners-grid">
          <?php foreach ($partners as $idx => $partnerName): ?>
            <?php $serial = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT); ?>
            <li class="ma-partner-block">
              <span class="ma-partner-block__num mono">PTN &middot; <?= e($serial) ?></span>
              <span class="ma-partner-block__name"><?= e($partnerName) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
