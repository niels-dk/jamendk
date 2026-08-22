<?php
// views/partials/footer.php — site footer.
// Brand, contact and legal details come from constants so a domain or name
// change is a config edit, not a hunt through templates.
$f_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$siteName  = defined('SITE_NAME')      ? SITE_NAME      : 'Merely a Dream';
$siteEmail = defined('SITE_EMAIL')     ? SITE_EMAIL     : 'hello@merelyadream.com';
$siteInsta = defined('SITE_INSTAGRAM') ? SITE_INSTAGRAM : 'https://www.instagram.com/merely.a.dream/';
$f_in      = function_exists('is_logged_in') && is_logged_in();
?>
<style>
  .site-footer {
    margin-top: 4rem; padding: 2.4rem 1.1rem 1.6rem;
    border-top: 1px solid rgba(255,255,255,.07);
    background: rgba(0,0,0,.18);
    color: #8593a6; font-size: .9rem;
  }
  .sf-inner {
    max-width: 1100px; margin: 0 auto;
    display: grid; gap: 2rem; grid-template-columns: 1fr;
  }
  @media (min-width: 700px) {
    .sf-inner { grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 2.4rem; }
  }
  .sf-brand .sf-logo {
    font-size: 1.05rem; font-weight: 800; color: #eaf0f7; margin-bottom: .4rem;
  }
  .sf-brand p { margin: 0 0 .9rem; color: #7a8aa0; font-size: .88rem; line-height: 1.55; max-width: 22rem; }
  .sf-social { display: flex; gap: .5rem; }
  .sf-social a {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .7rem; border-radius: 8px;
    background: rgba(255,255,255,.05); border: 1px solid #2b3346;
    color: #cfdbe8; text-decoration: none; font-size: .84rem; font-weight: 600;
  }
  .sf-social a:hover { background: rgba(255,255,255,.1); }
  .sf-col h4 {
    margin: 0 0 .7rem; font-size: .74rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .1em; color: #6c7d92;
  }
  .sf-col ul { list-style: none; margin: 0; padding: 0; }
  .sf-col li { margin-bottom: .45rem; }
  .sf-col a { color: #9bb0c5; text-decoration: none; }
  .sf-col a:hover { color: #eaf0f7; text-decoration: underline; }
  .sf-bottom {
    max-width: 1100px; margin: 2rem auto 0; padding-top: 1.2rem;
    border-top: 1px solid rgba(255,255,255,.06);
    display: flex; flex-wrap: wrap; gap: .6rem 1.2rem;
    align-items: center; justify-content: space-between;
    color: #6c7d92; font-size: .82rem;
  }
  /* Inline SVG flags, not emoji — Windows ships no glyph for 🇩🇰/🇧🇷 and
     renders them as the bare letters "DK" / "BR". Same reason as the
     language picker. inline-flex keeps them on the text's centre line. */
  .sf-made { display: inline-flex; align-items: center; gap: .3rem; }
  .sf-made .sf-dot { opacity: .4; margin: 0 .15rem; }
  .sf-made .lang-flag { display: block; }
</style>

<footer class="site-footer">
  <div class="sf-inner">
    <div class="sf-brand">
      <div class="sf-logo"><?= $f_e($siteName) ?></div>
      <p><?= te('footer.blurb') ?></p>
      <div class="sf-social">
        <a href="<?= $f_e($siteInsta) ?>" target="_blank" rel="noopener me">
          📷 <?= te('footer.instagram') ?>
        </a>
        <a href="mailto:<?= $f_e($siteEmail) ?>">✉️ <?= te('footer.email') ?></a>
      </div>
    </div>

    <div class="sf-col">
      <h4><?= te('footer.product') ?></h4>
      <ul>
        <li><a href="/"><?= te('footer.how_it_works') ?></a></li>
        <li><a href="/pricing"><?= te('nav.pricing') ?></a></li>
        <?php if ($f_in): ?>
          <li><a href="/dashboard"><?= te('nav.dashboard') ?></a></li>
          <li><a href="/dreams/new"><?= te('home.new_dream') ?></a></li>
          <li><a href="/account"><?= te('nav.my_account') ?></a></li>
        <?php else: ?>
          <li><a href="/register"><?= te('nav.create_account') ?></a></li>
          <li><a href="/login"><?= te('nav.signin') ?></a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="sf-col">
      <h4><?= te('footer.support') ?></h4>
      <ul>
        <li><a href="/help"><?= te('footer.help') ?></a></li>
        <li><a href="/contact"><?= te('footer.contact') ?></a></li>
        <li><a href="mailto:<?= $f_e($siteEmail) ?>"><?= $f_e($siteEmail) ?></a></li>
      </ul>
    </div>

    <div class="sf-col">
      <h4><?= te('footer.legal') ?></h4>
      <ul>
        <li><a href="/privacy"><?= te('footer.privacy') ?></a></li>
        <li><a href="/terms"><?= te('footer.terms') ?></a></li>
      </ul>
    </div>
  </div>

  <div class="sf-bottom">
    <span>&copy; <?= date('Y') ?> <?= $f_e($siteName) ?></span>
    <span class="sf-made">
      <?= te('footer.made_in') ?> <?= I18n::flag('dk', 14) ?>
      <span class="sf-dot">·</span>
      <?= te('footer.tested_in') ?> <?= I18n::flag('br', 14) ?>
    </span>
  </div>
</footer>
