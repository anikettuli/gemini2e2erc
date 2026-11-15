<?php
declare(strict_types=1);

if (!headers_sent() && function_exists('erc_send_security_headers')) {
    erc_send_security_headers(true);
}

require_once __DIR__ . '/icons.php';

$headEscape = static function (mixed $value): string {
    if (function_exists('erc_escape')) {
        return erc_escape($value);
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$mediaUrl = static function (string $path): string {
    if (function_exists('erc_media_url')) {
        return erc_media_url($path);
    }
    return '/' . ltrim($path, '/');
};

$pageMeta = isset($pageMeta) && is_array($pageMeta) ? $pageMeta : [];
$slug = (string) ($pageMeta['slug'] ?? 'home');
$title = (string) ($pageMeta['title'] ?? 'Lions District 2-E2 Eyeglass Recycling Center');
$description = (string) ($pageMeta['description'] ?? 'Lions District 2-E2 Eyeglass Recycling Center collects, cleans, inspects, and sorts donated eyeglasses for humanitarian vision missions worldwide.');
$canonicalRaw = (string) ($pageMeta['canonical'] ?? '/');
$canonicalUrl = (str_starts_with($canonicalRaw, 'http://') || str_starts_with($canonicalRaw, 'https://'))
    ? $canonicalRaw
    : 'https://2e2erc.org' . (str_starts_with($canonicalRaw, '/') ? $canonicalRaw : '/' . $canonicalRaw);

$ogImageRaw = (string) ($pageMeta['og_image'] ?? $mediaUrl('e2e2rc_LOGO.png'));
$ogImageUrl = (str_starts_with($ogImageRaw, 'http://') || str_starts_with($ogImageRaw, 'https://'))
    ? $ogImageRaw
    : 'https://2e2erc.org' . (str_starts_with($ogImageRaw, '/') ? $ogImageRaw : '/' . $ogImageRaw);

$preloadImg = !empty($pageMeta['preload_img']) ? (string) $pageMeta['preload_img'] : '';
$extraCss = isset($pageMeta['css']) && is_array($pageMeta['css']) ? $pageMeta['css'] : [];
$noindex = !empty($pageMeta['noindex']);
$omitShell = !empty($pageMeta['omit_shell']);
$inlineNonceAttr = (function_exists('erc_csp_nonce') && ($cspNonce = (string) erc_csp_nonce()) !== '')
    ? ' nonce="' . $headEscape($cspNonce) . '"'
    : '';

$orgEmail = (string) ($GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com');
$orgPhone = (string) ($GLOBALS['GLOBAL_PHONE'] ?? '(817) 710-5403');
$orgPhoneLink = (string) ($GLOBALS['GLOBAL_PHONE_LINK'] ?? '+18177105403');

$baseSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'NGO',
    'name' => 'Lions District 2-E2 Eyeglass Recycling Center',
    'legalName' => 'District 2-E2 Lions Eyeglass Recycling Center Foundation',
    'url' => 'https://2e2erc.org',
    'logo' => 'https://2e2erc.org' . $mediaUrl('e2e2rc_LOGO.png'),
    'email' => $orgEmail,
    'telephone' => $orgPhoneLink,
    'foundingDate' => '2014',
    'nonprofitStatus' => 'Nonprofit501c3',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => '5621 Bunker Blvd',
        'addressLocality' => 'Watauga',
        'addressRegion' => 'TX',
        'postalCode' => '76148',
        'addressCountry' => 'US',
    ],
    'openingHoursSpecification' => [
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => 'Saturday',
            'opens' => '09:00',
            'closes' => '15:00',
        ],
    ],
];

$jsonLdSchemas = [$baseSchema];
if (!empty($pageMeta['json_ld']) && is_array($pageMeta['json_ld'])) {
    if (isset($pageMeta['json_ld']['@type'])) {
        $jsonLdSchemas[] = $pageMeta['json_ld'];
    } else {
        foreach ($pageMeta['json_ld'] as $schemaItem) {
            if (is_array($schemaItem)) {
                $jsonLdSchemas[] = $schemaItem;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" id="metaThemeColorLight" content="#faf8f3" media="(prefers-color-scheme: light)">
  <meta name="theme-color" id="metaThemeColorDark" content="#0d1522" media="(prefers-color-scheme: dark)">
  <script<?= $inlineNonceAttr ?>>
  (function () {
    try {
      var pref = localStorage.getItem('erc-color-scheme');
      var metaScheme = document.querySelector('meta[name="color-scheme"]');
      var metaLight = document.getElementById('metaThemeColorLight');
      var metaDark = document.getElementById('metaThemeColorDark');
      if (metaScheme && (pref === 'light' || pref === 'dark')) {
        metaScheme.setAttribute('content', pref);
        var resolvedHex = pref === 'dark' ? '#0d1522' : '#faf8f3';
        if (metaLight) { metaLight.setAttribute('content', resolvedHex); }
        if (metaDark) { metaDark.setAttribute('content', resolvedHex); }
      }
    } catch (e) {}
<?php if ($slug === 'home'): ?>
    var rawHash = (window.location.hash || '').toLowerCase();
    var hashRoutes = {
      '#home': '/',
      '#about': '/about',
      '#services': '/donate',
      '#get-involved': '/volunteer',
      '#locations': '/locations',
      '#contact': '/contact'
    };
    if (rawHash && Object.prototype.hasOwnProperty.call(hashRoutes, rawHash)) {
      var target = hashRoutes[rawHash];
      if (target === '/') {
        if (window.history && window.history.replaceState) {
          window.history.replaceState(null, '', '/');
        }
      } else {
        window.location.replace(target);
      }
    }
<?php endif; ?>
  })();
  </script>
  <title><?= $headEscape($title) ?></title>
  <meta name="description" content="<?= $headEscape($description) ?>">
  <link rel="canonical" href="<?= $headEscape($canonicalUrl) ?>">
<?php if ($noindex): ?>
  <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Lions District 2-E2 Eyeglass Recycling Center">
  <meta property="og:title" content="<?= $headEscape($title) ?>">
  <meta property="og:description" content="<?= $headEscape($description) ?>">
  <meta property="og:url" content="<?= $headEscape($canonicalUrl) ?>">
  <meta property="og:image" content="<?= $headEscape($ogImageUrl) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $headEscape($title) ?>">
  <meta name="twitter:description" content="<?= $headEscape($description) ?>">
  <meta name="twitter:image" content="<?= $headEscape($ogImageUrl) ?>">
  <link rel="icon" type="image/png" href="<?= $headEscape($mediaUrl('e2e2rc_LOGO.png')) ?>">
  <link rel="expect" href="#main-content" blocking="render">
  <link rel="preload" href="/fonts/newsreader-latin-opsz-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/fonts/atkinson-hyperlegible-next-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<?php if ($preloadImg !== ''): ?>
  <link rel="preload" href="<?= $headEscape($preloadImg) ?>" as="image" fetchpriority="high">
<?php endif; ?>
  <link rel="stylesheet" href="/fonts/fonts.css">
  <link rel="stylesheet" href="/css/tokens.css">
  <link rel="stylesheet" href="/css/base.css">
<?php if (!$omitShell): ?>
  <link rel="stylesheet" href="/css/shell.css">
<?php endif; ?>
<?php foreach ($extraCss as $cssHref): ?>
<?php
    $hrefStr = (string) $cssHref;
    if ($hrefStr === '') {
        continue;
    }
    if (!str_starts_with($hrefStr, '/') && !str_starts_with($hrefStr, 'http://') && !str_starts_with($hrefStr, 'https://')) {
        $hrefStr = '/' . ltrim($hrefStr, '/');
    }
?>
  <link rel="stylesheet" href="<?= $headEscape($hrefStr) ?>">
<?php endforeach; ?>
<?php foreach ($jsonLdSchemas as $schema): ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endforeach; ?>
</head>
