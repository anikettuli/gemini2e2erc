<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/icons.php';

$procEsc = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$procMedia = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$processStages = [
    [
        'num'   => '01',
        'title' => 'Collection',
        'body'  => 'Donated eyewear arrives from 214 North Texas drop boxes, Lions Clubs, corporate drives, and walk-in contributions.',
    ],
    [
        'num'   => '02',
        'title' => 'Inspection & Sorting',
        'body'  => 'Volunteers inspect usability and sort frames into non-prescription sunglasses, reading glasses, minus/plus single vision, and multifocals.',
    ],
    [
        'num'   => '03',
        'title' => 'Washing & Sanitizing',
        'body'  => 'Usable frames and lenses are thoroughly washed, sanitized, and placed on drying racks prior to optical measurement.',
    ],
    [
        'num'   => '04',
        'title' => '0.25D Lensometry',
        'body'  => 'Calibrated tabletop lensometers determine each lens prescription accurately in 0.25-diopter steps.',
    ],
    [
        'num'   => '05',
        'title' => 'Ultrasonic Cleaning',
        'body'  => 'Frames undergo deep cleaning around hinges and nose pads followed by a final quality check before bagging.',
    ],
    [
        'num'   => '06',
        'title' => '50-Pair Box & Dispatch',
        'body'  => 'Verified glasses are labeled, bagged, and packed 50 pairs per diopter box for free distribution to nonprofit missions.',
    ],
];
?>
<section id="process" class="section" aria-labelledby="process-title" tabindex="-1">
  <div class="container">
    <div class="section-head">
      <p class="kicker">02 &middot; Optical Refurbishment</p>
      <h2 id="process-title" class="h2">How every donated pair is sanitized and measured.</h2>
      <p class="lead">
        Six volunteer stations turn everyday donations into clinic-ready prescription eyewear packed by exact diopter strength.
      </p>
    </div>

    <ol class="process-steps" aria-label="Six stages of optical refurbishment">
<?php foreach ($processStages as $stage): ?>
      <li class="card process-step">
        <span class="process-step__num mono"><?= $procEsc($stage['num']) ?></span>
        <h3 class="process-step__title"><?= $procEsc($stage['title']) ?></h3>
        <p class="process-step__body"><?= $procEsc($stage['body']) ?></p>
      </li>
<?php endforeach; ?>
    </ol>

    <div class="process-photos">
      <figure class="process-photo">
        <div class="erc-rotated-frame erc-rotated-frame--landscape is-ccw90-no-exif">
          <img
            src="<?= $procEsc($procMedia('images/process-lensometers.jpg')) ?>"
            alt="Lions volunteers operating tabletop lensometers to measure eyeglass prescriptions at the Watauga workshop"
            width="1200"
            height="900"
            loading="eager"
            decoding="async"
          >
        </div>
        <figcaption class="process-photo__caption">
          Precision <span class="mono">0.25D</span> neutralization on calibrated tabletop lensometers.
        </figcaption>
      </figure>

      <figure class="process-photo">
        <div class="process-photo__frame">
          <img
            src="<?= $procEsc($procMedia('gallery/drying-glasses.jpg')) ?>"
            alt="Volunteers drying and inspecting washed eyeglasses on workshop tables"
            width="1200"
            height="900"
            loading="eager"
            decoding="async"
          >
        </div>
        <figcaption class="process-photo__caption">
          Sanitized eyewear drying and sorted into labeled workshop trays before boxing.
        </figcaption>
      </figure>
    </div>

    <div class="card process-spec-bar">
      <ul class="process-spec-list">
        <li class="process-spec-item">
          <span class="process-spec-item__key mono">+0.25 to &plusmn;5.00+</span>
          <span class="process-spec-item__val">Diopter grading across single-vision plus, single-vision minus, and reading glasses</span>
        </li>
        <li class="process-spec-item">
          <span class="process-spec-item__key mono">50 pairs</span>
          <span class="process-spec-item__val">Per labeled mission box, including non-prescription sunglasses</span>
        </li>
        <li class="process-spec-item">
          <span class="process-spec-item__key mono">Zero waste</span>
          <span class="process-spec-item__val">Broken and metal frames are repaired or recycled to help fund shipping</span>
        </li>
      </ul>

      <a
        class="btn btn--secondary process-spec-bar__cta"
        href="<?= $procEsc($procMedia('files/ERC-Brochure.pdf')) ?>"
        target="_blank"
        rel="noopener"
      >
        <?= erc_icon('file-text', ['width' => '17', 'height' => '17']) ?>
        <span>ERC Brochure (PDF)</span>
      </a>
    </div>
  </div>
</section>
