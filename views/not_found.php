<?php
// views/not_found.php — the 404 page.
//
// Standalone HTML rather than the site layout on purpose: a 404 can be raised
// from anywhere, including before the layout's globals exist, and a 404 that
// throws its own error is worse than a plain one.
//
// The wording is deliberately vague. require_admin() and require_owner() both
// answer 404 rather than 403 so a stranger cannot use the response to learn
// that /admin/analytics or someone else's board exists — saying "you don't have
// permission" here would give that away.
$nfIn = function_exists('is_logged_in') && is_logged_in();
$nfE  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="<?= $nfE(class_exists('I18n') ? I18n::lang() : 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $nfE(function_exists('t') ? t('nf.title') : 'Page not found') ?></title>
  <style>
    html, body { margin:0; padding:0; }
    body {
      font: 16px/1.55 Inter, Roboto, system-ui, -apple-system, sans-serif;
      color: #cfdbe8; background: #0b0f17;
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 2rem;
    }
    .nf {
      max-width: 460px; width: 100%; text-align: center;
      background: rgba(255,255,255,.04); border: 1px solid #2b3346;
      border-radius: 14px; padding: 2.4rem 2rem;
    }
    .nf .code { font-size: .78rem; letter-spacing: .12em; text-transform: uppercase;
                color: #6c7d92; font-weight: 700; }
    .nf h1 { margin: .5rem 0 .4rem; font-size: 1.35rem; color: #f0f4fa; }
    .nf p  { margin: 0 0 1.4rem; color: #8593a6; font-size: .95rem; }
    .nf a  { display: inline-block; padding: .6rem 1.2rem; border-radius: 10px;
             background: #3a76d2; color: #fff; text-decoration: none; font-weight: 600; }
    .nf a:hover { background: #2c5aa0; }
  </style>
</head>
<body>
  <div class="nf">
    <div class="code">404</div>
    <h1><?= $nfE(function_exists('t') ? t('nf.title') : 'Page not found') ?></h1>
    <p><?= $nfE(function_exists('t') ? t('nf.body') : "That page doesn't exist, or you don't have access to it.") ?></p>
    <a href="<?= $nfIn ? '/dashboard' : '/' ?>">
      <?= $nfE(function_exists('t')
            ? ($nfIn ? t('vision.back_dash') : t('nf.back_home'))
            : ($nfIn ? 'Back to dashboard' : 'Back to the front page')) ?>
    </a>
  </div>
</body>
</html>
