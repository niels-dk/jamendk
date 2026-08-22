<?php
// views/page_pricing.php (fragment; layout wraps it)
// Pricing class is already loaded by page_controller::pricing().
$p_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$in  = function_exists('is_logged_in') && is_logged_in();

// Human band label from min/max people.
$band = function (array $t): string {
    [, , $min, $max] = $t;
    if ($max === null) return t('pr.band_plus', ['n' => $min]);
    if ($min === $max) return t($min === 1 ? 'pr.band_one' : 'pr.band_n', ['n' => $min]);
    return t('pr.band_range', ['min' => $min, 'max' => $max]);
};
$blurbs = [
    'solo'       => t('pr.blurb_solo'),
    'crew'       => t('pr.blurb_crew'),
    'studio'     => t('pr.blurb_studio'),
    'production' => t('pr.blurb_production'),
    'network'    => t('pr.blurb_network'),
];
?>
<div class="doc pricing">
  <div style="text-align:center;">
    <span class="pr-beta">✨ <?= te('pr.beta') ?></span>
    <h1><?= te('pr.h1') ?></h1>
    <p class="doc-lead" style="margin:0 auto 2rem;max-width:34rem;">
      <?= t('pr.lead') ?>
    </p>
  </div>

  <div class="pr-grid">
    <?php foreach (Pricing::TIERS as $t): ?>
      <?php
        [$key, $label, $min, $max, $mCents] = $t;
        $paid = $mCents > 0;
        $feature = $key === 'studio'; // gently highlight the first paid tier
      ?>
      <div class="pr-card <?= $paid ? '' : 'pr-free' ?> <?= $feature ? 'pr-feature' : '' ?>">
        <div class="pr-name"><?= $p_e($label) ?></div>
        <div class="pr-band"><?= $p_e($band($t)) ?></div>
        <div class="pr-price">
          <?php if ($paid): ?>
            <span class="pr-was"><?= $p_e(Pricing::money($mCents)) ?><span class="pr-per">/mo</span></span>
            <span class="pr-now"><?= te('pr.free_now') ?></span>
          <?php else: ?>
            <span class="pr-now pr-now-free"><?= te('pr.free') ?></span>
            <span class="pr-per"><?= te('pr.always') ?></span>
          <?php endif; ?>
        </div>
        <p class="pr-blurb"><?= $p_e($blurbs[$key] ?? '') ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="pr-foot"><?= t('pr.foot') ?></p>

  <div class="pr-founder">
    <h2>💛 <?= te('pr.founder_h') ?></h2>
    <p><?= t('pr.founder_p') ?></p>
  </div>

  <h2><?= te('pr.questions') ?></h2>

  <h3><?= te('pr.q_person') ?></h3>
  <p><?= t('pr.a_person') ?></p>

  <h3><?= te('pr.q_grow') ?></h3>
  <p><?= t('pr.a_grow') ?></p>
  <p><?= t('pr.a_grow_2') ?></p>

  <h3><?= te('pr.q_locked') ?></h3>
  <p><?= t('pr.a_locked') ?></p>

  <h3><?= te('pr.q_free') ?></h3>
  <p><?= t('pr.a_free') ?></p>

  <div class="doc-cta">
    <p><?= $in ? te('pr.cta_in') : te('pr.cta_out') ?></p>
    <a class="doc-btn" href="<?= $in ? '/dashboard' : '/register' ?>">
      <?= $in ? te('home.go_dashboard') : te('lp.cta') ?>
    </a>
  </div>
</div>

<style>
  .pricing .pr-beta {
    display:inline-block; margin:0 0 1rem; padding:.3rem .8rem; border-radius:999px;
    background:rgba(232,176,74,.12); border:1px solid rgba(232,176,74,.3);
    color:#e8c267; font-size:.8rem; font-weight:700; letter-spacing:.04em;
  }
  .pricing .pr-grid {
    display:grid; gap:.8rem; grid-template-columns:1fr; margin:0 0 1.4rem;
  }
  @media (min-width:640px){ .pricing .pr-grid { grid-template-columns:repeat(2,1fr); } }
  @media (min-width:900px){ .pricing .pr-grid { grid-template-columns:repeat(5,1fr); } }
  .pricing .pr-card {
    background:rgba(255,255,255,.04); border:1px solid #2b3346; border-radius:14px;
    padding:1.2rem 1rem; display:flex; flex-direction:column; gap:.3rem;
  }
  .pricing .pr-card.pr-free   { border-color:rgba(127,201,141,.35); }
  .pricing .pr-card.pr-feature{ border-color:rgba(58,118,210,.5); box-shadow:0 0 0 1px rgba(58,118,210,.25); }
  .pricing .pr-name  { font-weight:800; color:#f0f4fa; font-size:1.05rem; }
  .pricing .pr-band  { color:#8593a6; font-size:.82rem; }
  .pricing .pr-price { margin:.5rem 0 .2rem; }
  .pricing .pr-was {
    display:block; color:#6c7d92; text-decoration:line-through;
    font-size:1.1rem; font-weight:700;
  }
  .pricing .pr-was .pr-per { text-decoration:none; }
  .pricing .pr-now { display:block; color:#7fc98d; font-weight:800; font-size:1.05rem; }
  .pricing .pr-now-free { font-size:1.6rem; }
  .pricing .pr-per { color:#6c7d92; font-size:.8rem; font-weight:600; }
  .pricing .pr-blurb { color:#9bb0c5; font-size:.86rem; line-height:1.5; margin:.4rem 0 0; }
  .pricing .pr-foot { text-align:center; color:#8593a6; font-size:.9rem; margin:0 0 2.4rem; }
  .pricing .pr-founder {
    background:rgba(58,118,210,.1); border:1px solid rgba(58,118,210,.3);
    border-radius:14px; padding:1.4rem 1.6rem; margin:0 0 2.4rem;
  }
  .pricing .pr-founder h2 { margin:0 0 .5rem; border:0; padding:0; }
  .pricing .pr-founder p  { margin:0; color:#c3d0de; }
</style>
