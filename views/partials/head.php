<?php
// One product name everywhere: the tab, the header and the signup button all
// said something different before (Jamen / DreamBoard / Creator).
$__title = $title ?? $pageTitle ?? 'DreamBoard';
if (stripos($__title, 'DreamBoard') === false) $__title .= ' · DreamBoard';
$__desc = $metaDescription
    ?? 'Catch the idea the second it lands, grow it into a real plan, and open '
     . 'the shot list when you are standing there. A planning tool for filmmakers '
     . 'and creators — works offline in the field.';
$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$__url    = $__scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'jamen.dk')
          . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($__title, ENT_QUOTES) ?></title>
<meta name="description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#1a1b1e">

<!-- Installable app: the "catch it on a roadside" promise needs DreamBoard on
     the home screen, not lost in browser tabs. -->
<link rel="manifest" href="/public/manifest.json">
<link rel="icon" type="image/png" sizes="192x192" href="/public/icons/icon-192.png">
<link rel="apple-touch-icon" href="/public/icons/apple-touch-icon.png">

<!-- Link previews (Slack / WhatsApp / iMessage / socials). Without these a
     shared link renders as a bare URL, which reads like spam. -->
<meta property="og:type"        content="website">
<meta property="og:site_name"   content="DreamBoard">
<meta property="og:title"       content="<?= htmlspecialchars($__title, ENT_QUOTES) ?>">
<meta property="og:description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">
<meta property="og:url"         content="<?= htmlspecialchars($__url, ENT_QUOTES) ?>">
<meta name="twitter:card"       content="summary_large_image">
<meta name="twitter:title"      content="<?= htmlspecialchars($__title, ENT_QUOTES) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
</script>
<link rel="stylesheet" href="/public/css/style.css?v=10">
<link rel="stylesheet" href="/public/css/app.css?v=2">
