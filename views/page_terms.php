<?php
// views/page_terms.php (fragment; layout wraps it)
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteName  = defined('SITE_NAME')  ? SITE_NAME  : 'Merely a Dream';
$siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@merelyadream.com';
$siteOwner = defined('SITE_LEGAL_ENTITY') ? SITE_LEGAL_ENTITY : 'Niels, Denmark';
$updated   = '15 July 2026';
?>
<div class="doc">
  <h1><?= te('pg.terms_h1') ?></h1>
  <p class="doc-lead"><?= t('pg.terms_lead', ['site' => $p_e($siteName)]) ?></p>
  <p class="doc-meta"><?= te('pg.last_updated') ?> <?= $p_e($updated) ?></p>
  <?php if (I18n::lang() !== I18n::DEFAULT_LANG): ?>
    <p class="doc-meta"><?= t('pg.legal_language') ?></p>
  <?php endif; ?>

  <h2>1. <?= te('pg.terms_h_who') ?></h2>
  <p><?= t('pg.terms_who', ['site' => $p_e($siteName), 'owner' => $p_e($siteOwner),
        'email' => '<a href="mailto:' . $p_e($siteEmail) . '">' . $p_e($siteEmail) . '</a>']) ?></p>

  <h2>2. <?= te('pg.terms_h_account') ?></h2>
  <p><?= t('pg.terms_account_1') ?></p>
  <p><?= t('pg.terms_account_2') ?></p>

  <h2>3. <?= te('pg.terms_h_free') ?></h2>
  <p><?= t('pg.terms_free_1', ['site' => $p_e($siteName)]) ?></p>
  <p><?= t('pg.terms_free_2') ?></p>

  <h2>4. <?= te('pg.terms_h_content') ?></h2>
  <p><?= t('pg.terms_content_1') ?></p>
  <p><?= t('pg.terms_content_2') ?></p>

  <h2>5. <?= te('pg.terms_h_sharing') ?></h2>
  <p><?= t('pg.terms_sharing') ?></p>

  <h2>6. <?= te('pg.terms_h_notdo') ?></h2>
  <ul>
    <li><?= t('pg.terms_nd1') ?></li>
    <li><?= t('pg.terms_nd2') ?></li>
    <li><?= t('pg.terms_nd3') ?></li>
    <li><?= t('pg.terms_nd4') ?></li>
    <li><?= t('pg.terms_nd5') ?></li>
  </ul>
  <p><?= t('pg.terms_notdo_p') ?></p>

  <h2>7. <?= te('pg.terms_h_ending') ?></h2>
  <p><?= t('pg.terms_ending') ?></p>

  <h2>8. <?= te('pg.terms_h_warranty') ?></h2>
  <p><?= t('pg.terms_warranty') ?></p>

  <h2>9. <?= te('pg.terms_h_liability') ?></h2>
  <p><?= t('pg.terms_liability') ?></p>

  <h2>10. <?= te('pg.terms_h_changes') ?></h2>
  <p><?= t('pg.terms_changes') ?></p>

  <h2>11. <?= te('pg.terms_h_law') ?></h2>
  <p><?= t('pg.terms_law') ?></p>

  <div class="doc-cta">
    <p><?= te('pg.terms_cta') ?></p>
    <a class="doc-btn" href="/contact"><?= te('pg.terms_cta_btn') ?></a>
  </div>
</div>
