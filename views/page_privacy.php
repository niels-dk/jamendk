<?php
// views/page_privacy.php (fragment; layout wraps it)
// Written to match what the app ACTUALLY stores — see the schema in
// db/migrations. If you add tracking, analytics or a third-party service,
// this page has to change with it.
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteName  = defined('SITE_NAME')     ? SITE_NAME     : 'Merely a Dream';
$siteEmail = defined('SITE_EMAIL')    ? SITE_EMAIL    : 'hello@merelyadream.com';
$siteOwner = defined('SITE_LEGAL_ENTITY') ? SITE_LEGAL_ENTITY : 'Niels, Denmark';
$updated   = '18 July 2026';
?>
<div class="doc">
  <h1><?= te('footer.privacy') ?></h1>
  <p class="doc-lead"><?= t('pg.priv_lead') ?></p>
  <p class="doc-meta"><?= te('pg.last_updated') ?> <?= $p_e($updated) ?></p>
  <?php if (I18n::lang() !== I18n::DEFAULT_LANG): ?>
    <p class="doc-meta"><?= t('pg.legal_language') ?></p>
  <?php endif; ?>

  <h2><?= te('pg.priv_h_who') ?></h2>
  <p><?= t('pg.priv_who', ['site' => $p_e($siteName), 'owner' => $p_e($siteOwner),
        'email' => '<a href="mailto:' . $p_e($siteEmail) . '">' . $p_e($siteEmail) . '</a>']) ?></p>

  <h2><?= te('pg.priv_h_store') ?></h2>

  <h3><?= te('pg.priv_h_account') ?></h3>
  <p><?= t('pg.priv_account_1') ?></p>
  <p><?= t('pg.priv_account_2') ?></p>

  <h3><?= te('pg.priv_h_content') ?></h3>
  <p><?= t('pg.priv_content_1') ?></p>
  <p><?= t('pg.priv_content_2') ?></p>

  <h3><?= te('pg.priv_h_maillog') ?></h3>
  <p><?= t('pg.priv_maillog') ?></p>

  <h3><?= te('pg.priv_h_logins') ?></h3>
  <p><?= t('pg.priv_logins') ?></p>

  <h3><?= te('pg.priv_h_stats') ?></h3>
  <p><?= t('pg.priv_stats_1') ?></p>
  <p><?= t('pg.priv_stats_2') ?></p>

  <h3><?= te('pg.priv_h_cookies') ?></h3>
  <p><?= t('pg.priv_cookies_1') ?></p>
  <p><?= t('pg.priv_cookies_2') ?></p>
  <p><?= t('pg.priv_cookies_3') ?></p>

  <h2><?= te('pg.priv_h_whoelse') ?></h2>
  <p><?= t('pg.priv_whoelse') ?></p>
  <ul>
    <li><?= t('pg.priv_we1') ?></li>
    <li><?= t('pg.priv_we2') ?></li>
  </ul>
  <p><?= t('pg.priv_admins') ?></p>

  <h2><?= te('pg.priv_h_where') ?></h2>
  <p><?= t('pg.priv_where') ?></p>

  <h2><?= te('pg.priv_h_howlong') ?></h2>
  <p><?= t('pg.priv_howlong') ?></p>

  <h2><?= te('pg.priv_h_rights') ?></h2>
  <p><?= t('pg.priv_rights', ['email' => '<a href="mailto:' . $p_e($siteEmail) . '">' . $p_e($siteEmail) . '</a>']) ?></p>
  <p><?= t('pg.priv_complain') ?></p>

  <h2><?= te('pg.priv_h_changes') ?></h2>
  <p><?= t('pg.priv_changes') ?></p>

  <div class="doc-cta">
    <p><?= te('pg.priv_cta') ?></p>
    <a class="doc-btn" href="/contact"><?= te('pg.priv_cta_btn') ?></a>
  </div>
</div>
