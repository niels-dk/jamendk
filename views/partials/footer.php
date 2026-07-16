<?php
// views/partials/footer.php — site footer.
// Brand, contact and legal details come from constants so a domain or name
// change is a config edit, not a hunt through templates.
$f_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$siteName  = defined('SITE_NAME')      ? SITE_NAME      : 'DreamBoard';
$siteEmail = defined('SITE_EMAIL')     ? SITE_EMAIL     : 'hello@jamen.dk';
$siteInsta = defined('SITE_INSTAGRAM') ? SITE_INSTAGRAM : 'https://www.instagram.com/dreamboardapp/';
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
</style>

<footer class="site-footer">
  <div class="sf-inner">
    <div class="sf-brand">
      <div class="sf-logo"><?= $f_e($siteName) ?></div>
      <p>
        Catch the idea, grow it into a plan, and open the shot list when you're
        standing there. Built for filmmakers and creators.
      </p>
      <div class="sf-social">
        <a href="<?= $f_e($siteInsta) ?>" target="_blank" rel="noopener me">
          📷 Instagram
        </a>
        <a href="mailto:<?= $f_e($siteEmail) ?>">✉️ Email</a>
      </div>
    </div>

    <div class="sf-col">
      <h4>Product</h4>
      <ul>
        <li><a href="/">How it works</a></li>
        <?php if ($f_in): ?>
          <li><a href="/dashboard">Dashboard</a></li>
          <li><a href="/dreams/new">New Dream</a></li>
          <li><a href="/account">My account</a></li>
        <?php else: ?>
          <li><a href="/register">Create account</a></li>
          <li><a href="/login">Sign in</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="sf-col">
      <h4>Support</h4>
      <ul>
        <li><a href="/help">Help</a></li>
        <li><a href="/contact">Contact</a></li>
        <li><a href="mailto:<?= $f_e($siteEmail) ?>"><?= $f_e($siteEmail) ?></a></li>
      </ul>
    </div>

    <div class="sf-col">
      <h4>Legal</h4>
      <ul>
        <li><a href="/privacy">Privacy policy</a></li>
        <li><a href="/terms">Terms</a></li>
      </ul>
    </div>
  </div>

  <div class="sf-bottom">
    <span>&copy; <?= date('Y') ?> <?= $f_e($siteName) ?></span>
    <span>Made in Denmark 🇩🇰</span>
  </div>
</footer>
