<?php
// views/page_help.php (fragment; layout wraps it)
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@merelyadream.com';
?>
<div class="doc">
  <h1><?= te('footer.help') ?></h1>
  <p class="doc-lead"><?= t('pg.help_lead') ?></p>

  <h2>🌕 <?= te('pg.help_dream_h') ?></h2>
  <p><?= t('pg.help_dream_1') ?></p>
  <p><?= t('pg.help_dream_2') ?></p>

  <h2>📄 <?= te('pg.help_vision_h') ?></h2>
  <p><?= t('pg.help_vision_1') ?></p>
  <ul>
    <li><?= t('pg.help_v_itinerary') ?></li>
    <li><?= t('pg.help_v_shots') ?></li>
    <li><?= t('pg.help_v_goals') ?></li>
    <li><?= t('pg.help_v_budget') ?></li>
    <li><?= t('pg.help_v_contacts') ?></li>
    <li><?= t('pg.help_v_roles') ?></li>
  </ul>

  <h2>🎨 <?= te('pg.help_mood_h') ?></h2>
  <p><?= t('pg.help_mood_1') ?></p>

  <h2>🗺️ <?= te('pg.help_trip_h') ?></h2>
  <p><?= t('pg.help_trip_1') ?></p>
  <ul>
    <li><?= t('pg.help_t_offline') ?></li>
    <li><?= t('pg.help_t_tick') ?></li>
    <li><?= t('pg.help_t_copy') ?></li>
    <li><?= t('pg.help_t_print') ?></li>
  </ul>
  <p><?= t('pg.help_trip_2') ?></p>

  <h2><?= te('pg.help_faq') ?></h2>

  <h3><?= te('pg.help_q_free') ?></h3>
  <p><?= t('pg.help_a_free') ?></p>

  <h3><?= te('pg.help_q_who') ?></h3>
  <p><?= t('pg.help_a_who') ?></p>

  <h3><?= te('pg.help_q_email') ?></h3>
  <p><?= t('pg.help_a_email') ?></p>

  <h3><?= te('pg.help_q_change') ?></h3>
  <p><?= t('pg.help_a_change') ?></p>

  <h3><?= te('pg.help_q_delete') ?></h3>
  <p><?= t('pg.help_a_delete', ['email' => $p_e($siteEmail)]) ?></p>

  <div class="doc-cta">
    <p><?= te('pg.help_cta') ?></p>
    <a class="doc-btn" href="/contact"><?= te('pg.help_cta_btn') ?></a>
  </div>
</div>
