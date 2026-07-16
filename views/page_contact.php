<?php
// views/page_contact.php (fragment; layout wraps it)
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteEmail = defined('SITE_EMAIL')     ? SITE_EMAIL     : 'hello@jamen.dk';
$siteInsta = defined('SITE_INSTAGRAM') ? SITE_INSTAGRAM : 'https://www.instagram.com/dreamboardapp/';
?>
<div class="doc">
  <h1>Contact</h1>
  <p class="doc-lead">
    A real person reads these. Bug reports, feature ideas and “this made no
    sense to me” are all equally welcome.
  </p>

  <div class="doc-cards">
    <a class="doc-card" href="mailto:<?= $p_e($siteEmail) ?>">
      <span class="dc-ico">✉️</span>
      <span>
        <b>Email</b>
        <span><?= $p_e($siteEmail) ?></span>
      </span>
    </a>
    <a class="doc-card" href="<?= $p_e($siteInsta) ?>" target="_blank" rel="noopener me">
      <span class="dc-ico">📷</span>
      <span>
        <b>Instagram</b>
        <span>@dreamboardapp — work in progress, in public</span>
      </span>
    </a>
  </div>

  <h2>Before you write</h2>
  <ul>
    <li><strong>Can't sign in / no confirmation email?</strong> Try the
        <a href="/verify-resend">resend link</a> and check your spam folder —
        that solves it most of the time.</li>
    <li><strong>Forgotten password?</strong> Use <a href="/forgot">forgot
        password</a>.</li>
    <li><strong>Not sure how something works?</strong> The
        <a href="/help">help page</a> covers the Dream → Vision → Trip flow.</li>
  </ul>

  <h2>Reporting a bug</h2>
  <p>
    The most useful thing you can send is: what you were doing, what you
    expected, and what happened instead. A screenshot beats a paragraph, and
    the page address helps more than you'd think.
  </p>

  <div class="doc-cta">
    <p>Want to help shape it?</p>
    <a class="doc-btn" href="mailto:<?= $p_e($siteEmail) ?>">Send us your idea</a>
  </div>
</div>
