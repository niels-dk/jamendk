<?php
// One product name everywhere — driven by SITE_NAME so a rebrand is a config
// edit, not a hunt through templates.
$__brand = defined('SITE_NAME') ? SITE_NAME : 'Merely a Dream';
$__title = $title ?? $pageTitle ?? $__brand;
if (stripos($__title, $__brand) === false) $__title .= ' · ' . $__brand;
$__desc = $metaDescription
    ?? 'Catch the idea the second it lands, grow it into a real plan, and open '
     . 'the shot list when you are standing there. A planning tool for filmmakers '
     . 'and creators — works offline in the field.';

$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// Host pinned to config where possible. HTTP_HOST is whatever the client put
// in the Host header, and a canonical tag that echoes it back can be pointed
// at someone else's domain. MAIL_SITE_HOST already exists for the same reason
// on the links inside emails.
$__host = defined('MAIL_SITE_HOST') ? MAIL_SITE_HOST : ($_SERVER['HTTP_HOST'] ?? 'merelyadream.com');
$__origin = $__scheme . '://' . $__host;

// Path WITHOUT the query string. Tracked links put ?utm_source=... on the
// front page, and with the query left in, every campaign would announce itself
// to Google as a separate copy of the same page.
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__url  = $__origin . $__path;

/*
 * Link preview image.
 *
 * Falls back rather than going missing: a card that promises a big image and
 * shows an empty box reads worse than a small neat one. The square app icon
 * always exists, so there is always something.
 *
 *   1. $ogImage set by the page      — per-page art
 *   2. OG_IMAGE in config            — a site-wide override
 *   3. /public/img/og-default.png    — the 1200x630 slot; drop the file in and
 *                                      it takes over with no code change
 *   4. the 512px app icon            — always there
 *
 * Only the wide options claim summary_large_image. Twitter and Slack render a
 * square image in that layout as a blurred, cropped mess.
 */
$__docRoot = dirname(__DIR__, 2);
$__ogWide  = null;
if (!empty($ogImage))                                              $__ogWide = $ogImage;
elseif (defined('OG_IMAGE') && OG_IMAGE !== '')                    $__ogWide = OG_IMAGE;
elseif (is_file($__docRoot . '/public/img/og-default.png'))        $__ogWide = '/public/img/og-default.png';

$__ogImage = $__ogWide ?? '/public/icons/icon-512.png';
if (strncmp($__ogImage, 'http', 4) !== 0) $__ogImage = $__origin . $__ogImage;
$__card = $__ogWide ? 'summary_large_image' : 'summary';

$__e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $__e($__title) ?></title>
<meta name="description" content="<?= $__e($__desc) ?>">
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#1a1b1e">

<!-- One address per page, so a tracked link and a bare link are not counted
     as two pages that happen to look alike. -->
<link rel="canonical" href="<?= $__e($__url) ?>">

<!-- Installable app: the "catch it on a roadside" promise needs the app on
     the home screen, not lost in browser tabs. -->
<link rel="manifest" href="/public/manifest.json">
<link rel="icon" type="image/png" sizes="192x192" href="/public/icons/icon-192.png">
<link rel="apple-touch-icon" href="/public/icons/apple-touch-icon.png">

<!-- Link previews (Slack / WhatsApp / iMessage / socials). Without these a
     shared link renders as a bare URL, which reads like spam. -->
<meta property="og:type"         content="website">
<meta property="og:site_name"    content="<?= $__e($__brand) ?>">
<meta property="og:title"        content="<?= $__e($__title) ?>">
<meta property="og:description"  content="<?= $__e($__desc) ?>">
<meta property="og:url"          content="<?= $__e($__url) ?>">
<meta property="og:image"        content="<?= $__e($__ogImage) ?>">
<meta property="og:image:alt"    content="<?= $__e($__brand) ?>">
<meta name="twitter:card"        content="<?= $__e($__card) ?>">
<meta name="twitter:title"       content="<?= $__e($__title) ?>">
<meta name="twitter:description" content="<?= $__e($__desc) ?>">
<meta name="twitter:image"       content="<?= $__e($__ogImage) ?>">

<?php
// Structured data. Deliberately only what is verifiable — who runs this and
// what it is called. No ratings, no price, no claims we would have to keep
// true; a search engine that catches a site overstating itself trusts the
// rest of it less.
$__ld = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type' => 'Organization',
            '@id'   => $__origin . '/#organisation',
            'name'  => $__brand,
            'url'   => $__origin . '/',
            'logo'  => $__origin . '/public/icons/icon-512.png',
        ],
        [
            '@type'     => 'WebSite',
            '@id'       => $__origin . '/#website',
            'name'      => $__brand,
            'url'       => $__origin . '/',
            'publisher' => ['@id' => $__origin . '/#organisation'],
        ],
    ],
];
if (defined('SITE_INSTAGRAM') && SITE_INSTAGRAM !== '') {
    $__ld['@graph'][0]['sameAs'] = [SITE_INSTAGRAM];
}
?>
<script type="application/ld+json"><?= json_encode(
    $__ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) ?></script>

<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
</script>
<link rel="stylesheet" href="/public/css/style.css?v=10">
<link rel="stylesheet" href="/public/css/app.css?v=3">
