<?php
// views/page_contact.php (fragment; layout wraps it)
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteEmail = defined('SITE_EMAIL')     ? SITE_EMAIL     : 'hello@merelyadream.com';
$siteInsta = defined('SITE_INSTAGRAM') ? SITE_INSTAGRAM : 'https://www.instagram.com/merely.a.dream/';
?>
<div class="doc">
  <h1><?= te('footer.contact') ?></h1>
  <p class="doc-lead"><?= t('pg.contact_lead') ?></p>

  <div class="doc-cards">
    <a class="doc-card" href="mailto:<?= $p_e($siteEmail) ?>">
      <span class="dc-ico">✉️</span>
      <span>
        <b><?= te('footer.email') ?></b>
        <span><?= $p_e($siteEmail) ?></span>
      </span>
    </a>
    <a class="doc-card" href="<?= $p_e($siteInsta) ?>" target="_blank" rel="noopener noreferrer me">
      <span class="dc-ico">📷</span>
      <span>
        <b><?= te('footer.instagram') ?></b>
        <span><?= t('pg.contact_insta') ?></span>
      </span>
    </a>
  </div>

  <h2><?= te('pg.contact_before') ?></h2>
  <ul>
    <li><?= t('pg.contact_b1') ?></li>
    <li><?= t('pg.contact_b2') ?></li>
    <li><?= t('pg.contact_b3') ?></li>
  </ul>

  <h2><?= te('pg.contact_bug') ?></h2>
  <p><?= t('pg.contact_bug_p') ?></p>

  <div class="doc-cta">
    <p><?= te('pg.contact_cta') ?></p>
    <a class="doc-btn" href="mailto:<?= $p_e($siteEmail) ?>"><?= te('pg.contact_cta_btn') ?></a>
  </div>
</div>
