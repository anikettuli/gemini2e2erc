<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/icons.php';

$abtEsc = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$abtMedia = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$boardInitialsSvg = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $ch = function_exists('mb_substr')
                ? mb_substr($part, 0, 1, 'UTF-8')
                : substr($part, 0, 1);
            $initials .= strtoupper($ch);
            $len = function_exists('mb_strlen')
                ? mb_strlen($initials, 'UTF-8')
                : strlen($initials);
            if ($len >= 2) {
                break;
            }
        }
    }
    if ($initials === '') {
        $initials = '2E';
    }
    $safeInitials = htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160">'
        . '<rect width="160" height="160" rx="16" fill="#1D4E89"/>'
        . '<text x="80" y="92" text-anchor="middle" font-family="system-ui,sans-serif" '
        . 'font-size="48" font-weight="600" fill="#FFFFFF">' . $safeInitials . '</text>'
        . '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$boardRaw = function_exists('erc_load_json')
    ? erc_load_json(ERC_DATA_DIR . '/board.json', [])
    : [];
$boardMembers = is_array($boardRaw) ? array_values($boardRaw) : [];

$currentBoardCount = 0;
$previousBoardCount = 0;
foreach ($boardMembers as $member) {
    $tag = strtolower(trim((string) ($member['tag'] ?? 'current')));
    if ($tag === 'previous') {
        $previousBoardCount++;
    } else {
        $currentBoardCount++;
    }
}

$partnersRaw = function_exists('erc_load_json')
    ? erc_load_json(ERC_DATA_DIR . '/partners.json', [])
    : [];
$partnersList = is_array($partnersRaw) ? array_values($partnersRaw) : [];

$coreValues = [
    ['name' => 'Integrity',      'desc' => 'Transparency and honesty across all operations.'],
    ['name' => 'Compassion',     'desc' => 'Commitment to improving sight and lives globally.'],
    ['name' => 'Sustainability', 'desc' => 'Landfill reduction and responsible material reuse.'],
    ['name' => 'Community',      'desc' => 'Collective service by volunteers and civic partners.'],
    ['name' => 'Excellence',     'desc' => 'Consistent quality standards in sorting, lensometry, and packaging.'],
];

$impactGroups = [
    ['name' => 'Children',    'desc' => 'Stronger educational outcomes and healthy development.'],
    ['name' => 'Adults',      'desc' => 'Improved employability and household economic stability.'],
    ['name' => 'Seniors',     'desc' => 'Reduced dependence on others and preserved daily independence.'],
    ['name' => 'Communities', 'desc' => 'Broader capacity to work, learn, and thrive.'],
];
?>
<section id="about" class="section section--alt" aria-labelledby="about-title" tabindex="-1">
  <div class="container">
    <div class="section-head">
      <p class="kicker">05 &middot; About &amp; Governance</p>
      <h2 id="about-title" class="h2">Founded in 2013 and powered entirely by volunteers.</h2>
    </div>

    <div class="about-grid">
      <div class="about-narrative">
        <p class="lead">
          The District 2-E2 Lions Eyeglass Recycling Center Foundation operates within Lions Clubs International, Multiple District 2 (Texas), and District 2-E2 &mdash; serving Tarrant County and North Texas under the auspices of the Texas Lions Eyeglass Recycling Center.
        </p>
        <p class="about-narrative__body">
          Founded in <span class="mono">2013</span> and recognized as a <span class="mono">501(c)(3)</span> tax-exempt nonprofit in <span class="mono">2014</span>, the center is <span class="mono">100%</span> volunteer-run with zero paid staff. An elected Board of Directors (<span class="mono">9+</span> directors serving staggered <span class="mono">3</span>-year terms, alongside District 2-E2 ex-officio officers) governs operations under corporate Bylaws ratified April 12, 2014.
        </p>

        <div class="about-narrative__links">
          <a
            class="btn btn--secondary"
            href="<?= $abtEsc($abtMedia('files/ERC-Bylaws.pdf')) ?>"
            target="_blank"
            rel="noopener"
          >
            <?= erc_icon('file-text', ['width' => '17', 'height' => '17']) ?>
            <span>Organizational Bylaws (PDF)</span>
          </a>
        </div>
      </div>

      <div class="about-pillars">
        <div class="card about-pillar-card">
          <h3 class="h3 about-pillar-card__title">5 Core Values</h3>
          <dl class="about-def-list">
<?php foreach ($coreValues as $val): ?>
            <div class="about-def-row">
              <dt><?= $abtEsc($val['name']) ?></dt>
              <dd><?= $abtEsc($val['desc']) ?></dd>
            </div>
<?php endforeach; ?>
          </dl>
        </div>

        <div class="card about-pillar-card">
          <h3 class="h3 about-pillar-card__title">4 Impact Groups</h3>
          <dl class="about-def-list">
<?php foreach ($impactGroups as $group): ?>
            <div class="about-def-row">
              <dt><?= $abtEsc($group['name']) ?></dt>
              <dd><?= $abtEsc($group['desc']) ?></dd>
            </div>
<?php endforeach; ?>
          </dl>
        </div>
      </div>
    </div>

    <div class="about-recognition-grid">
      <article class="card about-recognition-card">
        <span class="badge badge--gold">Knights of Sight</span>
        <h3 class="h3">Helen Keller Award</h3>
        <p class="about-recognition-card__body">
          Recognizes dedicated financial supporters (<span class="mono">$250</span> Benefactor, <span class="mono">$500</span> Endowment, <span class="mono">$1,000</span> Patron) or Lions members documenting volunteer service hours at the ERC. Includes certificate/plaque, ERC Life Membership pin, and permanent brass wall plate engraving.
        </p>
        <a
          class="btn btn--secondary btn--sm about-recognition-card__cta"
          href="<?= $abtEsc($abtMedia('files/Helen-Keller-Award-Application.pdf')) ?>"
          target="_blank"
          rel="noopener"
        >
          <?= erc_icon('award', ['width' => '16', 'height' => '16']) ?>
          <span>Helen Keller Application (PDF)</span>
        </a>
      </article>

      <article class="card about-recognition-card">
        <span class="badge badge--gold">Club Sponsorship</span>
        <h3 class="h3">ERC Life Membership</h3>
        <p class="about-recognition-card__body">
          Allows any Lions Club to sponsor ERC Life Membership recognition for an individual Lion in appreciation of service to the sight mission. Awards can be presented in person by an ERC Director or mailed directly to the club or recipient.
        </p>
        <a
          class="btn btn--secondary btn--sm about-recognition-card__cta"
          href="<?= $abtEsc($abtMedia('files/ERC-Life-Membership-Application.pdf')) ?>"
          target="_blank"
          rel="noopener"
        >
          <?= erc_icon('file-text', ['width' => '16', 'height' => '16']) ?>
          <span>Life Membership Form (PDF)</span>
        </a>
      </article>
    </div>

    <div class="about-drawer-actions">
      <button
        type="button"
        class="btn btn--secondary btn--lg"
        data-open-drawer="boardDrawer"
      >
        <?= erc_icon('users', ['width' => '18', 'height' => '18']) ?>
        <span>Meet the Board of Directors (<span class="mono"><?= $abtEsc((string) count($boardMembers)) ?></span>)</span>
      </button>

      <button
        type="button"
        class="btn btn--secondary btn--lg"
        data-open-drawer="partnersDrawer"
      >
        <?= erc_icon('heart-handshake', ['width' => '18', 'height' => '18']) ?>
        <span>View Lions Clubs &amp; Partners (<span class="mono"><?= $abtEsc((string) count($partnersList)) ?></span>)</span>
      </button>
    </div>
  </div>
</section>

<dialog
  id="boardDrawer"
  class="drawer"
  aria-labelledby="boardDrawerTitle"
  closedby="any"
>
  <div class="drawer__header">
    <div>
      <p class="kicker">100% Volunteer Governance</p>
      <h3 id="boardDrawerTitle" class="drawer__title">
        Board of Directors (<span class="mono"><?= $abtEsc((string) count($boardMembers)) ?></span>)
      </h3>
    </div>
    <button
      type="button"
      class="drawer__close"
      data-close-drawer
      aria-label="Close Board of Directors"
      autofocus
    >
      <?= erc_icon('x', ['width' => '20', 'height' => '20']) ?>
    </button>
  </div>

  <div class="drawer__body board-drawer__body">
    <div class="pill-tabs" role="tablist" aria-label="Filter Board of Directors">
      <button
        type="button"
        class="pill-tab is-active"
        role="tab"
        aria-selected="true"
        data-board-filter="all"
      >
        All <span class="mono"><?= $abtEsc((string) count($boardMembers)) ?></span>
      </button>
      <button
        type="button"
        class="pill-tab"
        role="tab"
        aria-selected="false"
        data-board-filter="current"
      >
        Current <span class="mono"><?= $abtEsc((string) $currentBoardCount) ?></span>
      </button>
      <button
        type="button"
        class="pill-tab"
        role="tab"
        aria-selected="false"
        data-board-filter="previous"
      >
        Previous <span class="mono"><?= $abtEsc((string) $previousBoardCount) ?></span>
      </button>
    </div>

    <ul class="board-grid" id="boardGrid" aria-live="polite">
<?php foreach ($boardMembers as $member):
    $name = trim((string) ($member['name'] ?? ''));
    if ($name === '') {
        continue;
    }
    $rawTag = strtolower(trim((string) ($member['tag'] ?? 'current')));
    $tag = $rawTag === 'previous' ? 'previous' : 'current';
    $imagePath = trim((string) ($member['image'] ?? ''));
    $fallbackUri = $boardInitialsSvg($name);
    $imageUrl = $imagePath !== '' ? $abtMedia($imagePath) : $fallbackUri;
?>
      <li class="card board-card" data-board-tag="<?= $abtEsc($tag) ?>">
        <div class="board-card__avatar">
          <img
            src="<?= $abtEsc($imageUrl) ?>"
            data-fallback-src="<?= $abtEsc($fallbackUri) ?>"
            alt="<?= $abtEsc($name) ?>, Lions District 2-E2 ERC <?= $abtEsc($tag === 'previous' ? 'Previous' : 'Current') ?> Board Member"
            loading="eager"
            decoding="async"
          >
        </div>
        <div class="board-card__info">
          <span class="board-card__name"><?= $abtEsc($name) ?></span>
          <span class="badge <?= $tag === 'current' ? 'badge--brand' : '' ?>">
            <?= $abtEsc($tag === 'previous' ? 'Previous' : 'Current') ?>
          </span>
        </div>
      </li>
<?php endforeach; ?>
    </ul>
  </div>
</dialog>

<dialog
  id="partnersDrawer"
  class="drawer"
  aria-labelledby="partnersDrawerTitle"
  closedby="any"
>
  <div class="drawer__header">
    <div>
      <p class="kicker">District 2-E2 &amp; Mission Network</p>
      <h3 id="partnersDrawerTitle" class="drawer__title">
        Lions Clubs &amp; Partners (<span class="mono"><?= $abtEsc((string) count($partnersList)) ?></span>)
      </h3>
    </div>
    <button
      type="button"
      class="drawer__close"
      data-close-drawer
      aria-label="Close Lions Clubs and Partners"
      autofocus
    >
      <?= erc_icon('x', ['width' => '20', 'height' => '20']) ?>
    </button>
  </div>

  <div class="drawer__body partners-drawer__body">
    <p class="partners-drawer__intro">
      Civic clubs, Lions organizations, youth service chapters, and medical mission teams partnering with the Watauga center.
    </p>
    <ol class="partners-grid">
<?php foreach ($partnersList as $index => $partner):
    $partnerName = trim((string) $partner);
    if ($partnerName === '') {
        continue;
    }
?>
      <li class="card partners-card">
        <span class="partners-card__num mono"><?= $abtEsc(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
        <span class="partners-card__name"><?= $abtEsc($partnerName) ?></span>
      </li>
<?php endforeach; ?>
    </ol>
  </div>
</dialog>
