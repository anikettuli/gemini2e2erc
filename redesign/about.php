<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

$pageMeta = [
    'slug'        => 'about',
    'title'       => 'About, Governance, Board & Lions Recognition | Lions District 2-E2 ERC',
    'description' => 'Founded in 2013, 501(c)(3) governance and Bylaws, core values, current and previous Board of Directors, 24 partner organizations, Helen Keller Award, and ERC Life Membership.',
    'canonical'   => '/about',
    'preload_img' => '',
    'og_image'    => '/images/our-story-group.png',
    'css'         => ['/css/pages/editorial.css'],
    'js_modules'  => ['/js/pages/editorial.js'],
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

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="site-main ed-page" tabindex="-1">
  <!-- Editorial Masthead -->
  <section class="ed-masthead" aria-labelledby="about-title">
    <div class="ed-wrap">
      <div class="ed-masthead__grid ed-masthead__grid--split">
        <div>
          <span class="ed-kicker"><?= erc_icon('landmark') ?> About &amp; Stewardship</span>
          <h1 id="about-title" class="ed-display">
            100% Volunteer-Led Since 2013, Serving Tarrant County &amp; the World
          </h1>
          <p class="ed-lead">
            The <strong>District 2-E2 Lions Eyeglass Recycling Center Foundation</strong> operates within Lions Clubs International, Multiple District 2 (Texas), District 2-E2, and under the auspices of the Lions Clubs International&ndash;sanctioned Texas Lions Eyeglass Recycling Center.
          </p>
        </div>

        <aside class="ed-masthead__aside" aria-label="Legal & Tax-Exempt Snapshot">
          <h2 class="ed-h4">Corporate &amp; Nonprofit Profile</h2>
          <p class="ed-prose">
            <strong>Legal Name:</strong> District 2-E2 Lions Eyeglass Recycling Center Foundation<br>
            <strong>Status:</strong> 501(c)(3) &amp; 170(c)(2) Texas Nonprofit (<span class="ed-mono">2014</span>)<br>
            <strong>Fiscal / Lion Year:</strong> <span class="ed-mono">July 1 – June 30</span><br>
            <strong>Staffing:</strong> 100% unpaid volunteers
          </p>
        </aside>
      </div>

      <nav aria-label="On this page">
        <ul class="ed-jump-nav">
          <li><a href="#story-values">Story &amp; Core Values</a></li>
          <li><a href="#governance">Governance &amp; Bylaws</a></li>
          <li><a href="#board">Board of Directors</a></li>
          <li><a href="#partners">Partners &amp; Friends</a></li>
          <li><a href="#recognition">Helen Keller &amp; Life Membership</a></li>
        </ul>
      </nav>
    </div>
  </section>

  <!-- Founding Story, Values & Human Impact Groups -->
  <section id="story-values" class="ed-band ed-band--subtle" aria-labelledby="story-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('book-open') ?> Founding &amp; Lions Heritage</span>
          <h2 id="story-title" class="ed-h2">Built by North Texas Lions to Turn Spare Glasses into Sight</h2>
          <p class="ed-lead">
            Founded in <strong>2013</strong> and recognized as a <span class="ed-mono">501(c)(3)</span> nonprofit in <strong>2014</strong>, our Watauga center runs entirely through unpaid volunteer service—no paid staff, with directors serving without compensation.
          </p>
        </div>
      </div>

      <div class="ed-workshop-pair ed-workshop-pair--flush-top">
        <figure class="ed-media-frame">
          <img
            class="ed-media-frame__img"
            src="/gallery/founders-workday.jpg"
            alt="Founders and volunteer Lions sorting and preparing eyeglasses during a center workday"
            loading="lazy"
            decoding="async"
          >
          <figcaption class="ed-media-frame__caption">
            Founders and volunteer members collaborating on optical processing in Watauga.
          </figcaption>
        </figure>

        <figure class="ed-media-frame">
          <img
            class="ed-media-frame__img"
            src="/gallery/past-district-governors.jpg"
            alt="District 2-E2 Past District Governors gathered in service at the Eyeglass Recycling Center"
            loading="lazy"
            decoding="async"
          >
          <figcaption class="ed-media-frame__caption">
            District 2-E2 Past District Governors supporting Saturday eyeglass recycling operations.
          </figcaption>
        </figure>
      </div>

      <h3 class="ed-h3">Five Core Organizational Values</h3>
      <div class="ed-grid ed-grid--3 ed-mt-md ed-mb-xl">
        <article class="ed-card">
          <div class="ed-card__icon-box">
            <?= erc_icon('shield-check') ?>
          </div>
          <h4 class="ed-h4">1. Integrity</h4>
          <p class="ed-prose">Transparency and honesty across all operations, stewardship, and partner reporting.</p>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('heart') ?>
          </div>
          <h4 class="ed-h4">2. Compassion</h4>
          <p class="ed-prose">Steadfast commitment to improving sight and daily dignity for neighbors near and far.</p>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box ed-card__icon-box--emerald">
            <?= erc_icon('recycle') ?>
          </div>
          <h4 class="ed-h4">3. Sustainability</h4>
          <p class="ed-prose">Meaningful landfill reduction and responsible material reuse across eyewear and electronics.</p>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box">
            <?= erc_icon('users') ?>
          </div>
          <h4 class="ed-h4">4. Community</h4>
          <p class="ed-prose">Collective service uniting Lions Clubs, student groups, churches, and civic partners.</p>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('sparkles') ?>
          </div>
          <h4 class="ed-h4">5. Excellence</h4>
          <p class="ed-prose">Consistent optical quality standards across inspection, sanitizing, lensometry, and packing.</p>
        </article>
      </div>

      <h3 class="ed-h3">Who Clear Vision Reaches</h3>
      <div class="ed-grid ed-grid--4 ed-mt-md">
        <article class="ed-card ed-card--subtle">
          <h4 class="ed-h4">Children</h4>
          <p class="ed-prose">Stronger educational outcomes in classrooms and healthy early development.</p>
        </article>
        <article class="ed-card ed-card--subtle">
          <h4 class="ed-h4">Adults</h4>
          <p class="ed-prose">Improved employability, safer craftwork, and stronger household economic stability.</p>
        </article>
        <article class="ed-card ed-card--subtle">
          <h4 class="ed-h4">Seniors</h4>
          <p class="ed-prose">Reduced dependence on others and preserved daily independence at home.</p>
        </article>
        <article class="ed-card ed-card--subtle">
          <h4 class="ed-h4">Communities</h4>
          <p class="ed-prose">Broader collective capacity to work, learn, read, and thrive together.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- Governance & Bylaws Summary -->
  <section id="governance" class="ed-band ed-band--surface" aria-labelledby="governance-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('scale') ?> Corporate Charter &amp; Accountability</span>
          <h2 id="governance-title" class="ed-h2">Governance &amp; Foundation Bylaws</h2>
          <p class="ed-lead">
            Adopted by the Board of Directors on <strong>March 20, 2014</strong>, and ratified at the Annual Meeting on <strong>April 12, 2014</strong>.
          </p>
        </div>
        <a class="ed-btn ed-btn--primary" href="/files/ERC-Bylaws.pdf">
          <?= erc_icon('download') ?>
          <span>Download ERC Bylaws (PDF · 10 pages)</span>
        </a>
      </div>

      <div class="ed-card">
        <dl class="ed-dl ed-dl--two">
          <div class="ed-dl__row">
            <dt>Elected Board &amp; Term Limits</dt>
            <dd>
              Governed by <strong>9 or more</strong> elected directors serving staggered <strong>3-year terms</strong> (maximum <strong>2</strong> consecutive terms; no more than <strong>3</strong> directors from the same Lions Club).
            </dd>
          </div>

          <div class="ed-dl__row">
            <dt>Non-Voting Ex-Officio Members</dt>
            <dd>
              Present <strong>District Governor</strong>, present <strong>Vice District Governor(s)</strong>, and <strong>immediate Past District Governor</strong> of Lions District 2-E2.
            </dd>
          </div>

          <div class="ed-dl__row">
            <dt>Executive Committee / Officers (5 Roles)</dt>
            <dd>
              <strong>President</strong>, <strong>First Vice President</strong>, <strong>Second Vice President</strong>, <strong>Secretary</strong>, and <strong>Treasurer</strong>.
            </dd>
          </div>

          <div class="ed-dl__row">
            <dt>Meetings &amp; Fiscal Year</dt>
            <dd>
              Regular Board meetings held <strong>monthly</strong>; Annual Meeting held each <strong>June</strong>. Fiscal / Lion Year runs <span class="ed-mono">July 1 – June 30</span>.
            </dd>
          </div>

          <div class="ed-dl__row">
            <dt>Director Compensation</dt>
            <dd>
              Directors serve strictly <strong>without compensation</strong> (reimbursable only for pre-approved expenses).
            </dd>
          </div>

          <div class="ed-dl__row">
            <dt>Endowment Stewardship</dt>
            <dd>
              Endowment funds are maintained in <strong>separate accounting records</strong> per <span class="ed-mono">ERC-Bylaws.pdf</span>.
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </section>

  <!-- Board of Directors (Dynamic from data/board.json) -->
  <section id="board" class="ed-band" aria-labelledby="board-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('users') ?> Volunteer Leadership</span>
          <h2 id="board-title" class="ed-h2">Board of Directors</h2>
          <p class="ed-lead">
            Serving without compensation to steward collections, volunteer training, and humanitarian dispatches across District 2-E2.
          </p>
        </div>
      </div>

      <h3 class="ed-h3">Current Board (<?= e((string)count($currentBoard)) ?>)</h3>
      <?php if (count($currentBoard) > 0): ?>
        <div class="ed-board-grid ed-my-board">
          <?php foreach ($currentBoard as $member): ?>
            <article class="ed-board-card">
              <img
                class="ed-board-card__photo"
                src="<?= e($member['image']) ?>"
                data-fallback-src="/images/placeholder-volunteer.svg"
                alt="Portrait of <?= e($member['name']) ?>, Current Board member"
                loading="lazy"
                decoding="async"
              >
              <div class="ed-board-card__body">
                <h4 class="ed-board-card__name"><?= e($member['name']) ?></h4>
                <span class="ed-pill ed-pill--tint">Current Board</span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="ed-callout ed-callout--tint ed-my-board">
          <?= erc_icon('info') ?>
          <p class="ed-prose">Current Board roster is temporarily unavailable.</p>
        </div>
      <?php endif; ?>

      <h3 class="ed-h3">Previous Board Members (<?= e((string)count($previousBoard)) ?>)</h3>
      <?php if (count($previousBoard) > 0): ?>
        <div class="ed-board-grid ed-mt-md">
          <?php foreach ($previousBoard as $member): ?>
            <article class="ed-board-card">
              <img
                class="ed-board-card__photo"
                src="<?= e($member['image']) ?>"
                data-fallback-src="/images/placeholder-volunteer.svg"
                alt="Portrait of <?= e($member['name']) ?>, Previous Board member"
                loading="lazy"
                decoding="async"
              >
              <div class="ed-board-card__body">
                <h4 class="ed-board-card__name"><?= e($member['name']) ?></h4>
                <span class="ed-pill ed-pill--gold">Previous Board</span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Partners & Friends (Dynamic from data/partners.json) -->
  <section id="partners" class="ed-band ed-band--subtle" aria-labelledby="partners-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('heart-handshake') ?> Community &amp; Mission Network</span>
          <h2 id="partners-title" class="ed-h2">Partners &amp; Friends (<?= e((string)count($partners)) ?>)</h2>
          <p class="ed-lead">
            Lions Clubs, student service chapters, congregations, and humanitarian mission groups whose hands make every shipment possible.
          </p>
        </div>
        <a class="ed-btn ed-btn--secondary" href="/contact?subject=partnership">
          <?= erc_icon('mail') ?>
          <span>Partner With Us</span>
        </a>
      </div>

      <?php if (count($partners) > 0): ?>
        <ul class="ed-partners-grid">
          <?php foreach ($partners as $partnerName): ?>
            <li class="ed-partner-item">
              <?= erc_icon('check-circle-2') ?>
              <span><?= e($partnerName) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>

  <!-- Lions Recognition Programs: Helen Keller Award & ERC Life Membership -->
  <section id="recognition" class="ed-band ed-band--tint ed-band--last" aria-labelledby="recognition-title">
    <div class="ed-wrap">
      <div class="ed-section-head">
        <div class="ed-section-head__copy">
          <span class="ed-kicker"><?= erc_icon('award') ?> Lions Club Honors &amp; Sponsorships</span>
          <h2 id="recognition-title" class="ed-h2">Lions Recognition Programs</h2>
          <p class="ed-lead">
            Honor dedicated Lions and community supporters through permanent Watauga ERC recognition while sustaining global vision missions.
          </p>
        </div>
      </div>

      <div class="ed-grid ed-grid--2">
        <article class="ed-card">
          <div class="ed-card__icon-box ed-card__icon-box--gold">
            <?= erc_icon('award') ?>
          </div>
          <h3 class="ed-h3">Helen Keller Award (&ldquo;Knights of Sight&rdquo;)</h3>
          <p class="ed-prose">
            Lions Club members may earn recognition either by <strong>documenting volunteer service hours</strong> at the ERC or through one of three contribution tiers on the application form:
          </p>

          <div class="ed-tier-grid" aria-label="Helen Keller Award Contribution Tiers">
            <div class="ed-tier-box">
              <span class="ed-muted">Benefactor</span>
              <span class="ed-tier-amount">$250</span>
            </div>
            <div class="ed-tier-box">
              <span class="ed-muted">Endowment</span>
              <span class="ed-tier-amount">$500</span>
            </div>
            <div class="ed-tier-box">
              <span class="ed-muted">Patron</span>
              <span class="ed-tier-amount">$1,000</span>
            </div>
          </div>

          <p class="ed-prose">
            <strong>Recognition Package:</strong> Award certificate/plaque, congratulatory letter, ERC Life Membership pin, and permanent engraving on a brass wall plate at the center (presented in person by an ERC Director or mailed).
          </p>

          <div class="ed-actions ed-actions--end">
            <a class="ed-btn ed-btn--gold" href="/files/Helen-Keller-Award-Application.pdf">
              <?= erc_icon('download') ?>
              <span>Helen Keller Application (PDF · 1 page)</span>
            </a>
          </div>
        </article>

        <article class="ed-card">
          <div class="ed-card__icon-box">
            <?= erc_icon('building-2') ?>
          </div>
          <h3 class="ed-h3">ERC Life Membership</h3>
          <p class="ed-prose">
            Allows a Lions Club to sponsor an official <strong>ERC Life Membership</strong> recognition honoring an individual Lion&rsquo;s service to sight preservation.
          </p>
          <p class="ed-prose">
            Awards may be presented in person at a club meeting by an ERC Director or mailed directly to the sponsoring Lions Club or recipient.
          </p>
          <div class="ed-actions ed-actions--end">
            <a class="ed-btn ed-btn--primary" href="/files/ERC-Life-Membership-Application.pdf">
              <?= erc_icon('download') ?>
              <span>Life Membership Application (PDF · 1 page)</span>
            </a>
            <a class="ed-btn ed-btn--secondary" href="/contact?subject=partnership">
              <?= erc_icon('mail') ?>
              <span>Coordinate Presentation</span>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
