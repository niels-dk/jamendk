<?php
// views/home.php — the public landing page (sell the idea, then sign up).
// Signed-in visitors never reach it: '/' sends them to /dashboard, which is
// now the one start page (see home_controller::index).
require_once __DIR__ . '/partials/home_helpers.php';

global $isAuthenticated;
$isAnon       = empty($isAuthenticated);

// Public example trip. Set DEMO_TRIP_TOKEN in app/config.php to a published
// trip worth showing a stranger; the button hides itself until then, because
// a broken or half-filled example is worse than no example.
$demoTrip = (defined('DEMO_TRIP_TOKEN') && DEMO_TRIP_TOKEN)
    ? '/t/' . DEMO_TRIP_TOKEN : '';

$typeIcon = [
    'dream'  => '🌕',
    'vision' => '📄',
    'mood'   => '🎨',
    'trip'   => '🗺️',
];
?>

<style>
  .home {
    max-width: 1100px; margin: 0 auto; padding: 0 1.1rem 4rem;
  }

  /* ── Landing (anonymous) ─────────────────────────────────────────────── */

  .lp-hero {
    display: grid; grid-template-columns: 1fr; gap: 2.2rem;
    align-items: center;
    padding: 3rem 0 2.5rem;
  }
  @media (min-width: 880px) {
    .lp-hero { grid-template-columns: 1.15fr .85fr; gap: 3rem; padding: 4rem 0 3.5rem; }
  }
  .lp-eyebrow {
    display: inline-block; margin-bottom: 1rem;
    padding: .3rem .75rem; border-radius: 999px;
    background: rgba(232,176,74,.12); border: 1px solid rgba(232,176,74,.3);
    color: #e8b04a; font-size: .78rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
  }
  .lp-hero h1 {
    font-size: clamp(2.1rem, 5.2vw, 3.4rem);
    font-weight: 800; letter-spacing: -0.02em; line-height: 1.08;
    margin: 0 0 1rem; color: #f0f4fa;
    /* Even line lengths: without this "shot" sat alone on its own line. */
    text-wrap: balance;
  }
  .lp-hero h1 .accent { color: #8fb1d8; }
  .lp-hero .lp-sub {
    color: #9bb0c5; font-size: 1.08rem; line-height: 1.6;
    max-width: 34rem; margin: 0 0 1.7rem;
  }
  .lp-cta { display: flex; flex-wrap: wrap; gap: .6rem; }
  .home-btn {
    display: inline-block; padding: .75rem 1.3rem; border-radius: 10px;
    font-weight: 600; font-size: .98rem; text-decoration: none;
    border: 1px solid transparent; transition: transform .12s, background .12s;
  }
  .home-btn.primary { background: #3a76d2; color: #fff; }
  .home-btn.primary:hover { background: #2c5aa0; transform: translateY(-1px); }
  .home-btn.ghost   { background: transparent; color: #cfdbe8; border-color: #2b3346; }
  .home-btn.ghost:hover { background: rgba(255,255,255,.06); }
  .lp-trust {
    margin-top: 1rem; color: #6c7d92; font-size: .84rem;
  }

  /* Phone mock — a miniature of the real Trip page. Dark app, light output. */
  .lp-phone-wrap { display: flex; justify-content: center; }
  .lp-phone {
    width: 268px; flex-shrink: 0;
    border: 9px solid #232a38; border-radius: 30px;
    background: #f4f5f7; padding: .85rem .75rem 1.1rem;
    box-shadow: 0 24px 60px rgba(0,0,0,.45);
    transform: rotate(-1.5deg);
  }
  .lp-p-hero {
    background: #fff; border-radius: 8px; padding: .6rem .7rem; margin-bottom: .55rem;
  }
  .lp-p-title { font-size: .95rem; font-weight: 800; color: #0b1727; }
  .lp-p-meta  { font-size: .62rem; color: #5a6878; margin-top: .15rem; }
  .lp-p-pill {
    display: inline-block; margin-top: .35rem; padding: .1rem .45rem;
    background: #eaf1fb; color: #18467a; border-radius: 999px;
    font-size: .58rem; font-weight: 700;
  }
  .lp-p-day {
    font-size: .58rem; font-weight: 800; color: #5a6878;
    text-transform: uppercase; letter-spacing: .06em; margin: .5rem 0 .3rem;
  }
  .lp-p-shot {
    background: #fff; border-left: 3px solid #2c5aa0; border-radius: 6px;
    padding: .45rem .55rem; margin-bottom: .3rem;
    display: flex; gap: .4rem; align-items: flex-start;
  }
  .lp-p-box {
    width: 11px; height: 11px; border: 1.5px solid #b6c0cd; border-radius: 3px;
    flex-shrink: 0; margin-top: .1rem;
  }
  .lp-p-box.on {
    background: #2c5aa0; border-color: #2c5aa0; position: relative;
  }
  .lp-p-box.on::after {
    content: "✓"; position: absolute; inset: 0; color: #fff;
    font-size: 8px; line-height: 11px; text-align: center; font-weight: 900;
  }
  .lp-p-shot.done { opacity: .5; }
  .lp-p-shot.done .lp-p-st { text-decoration: line-through; }
  .lp-p-st { font-size: .68rem; font-weight: 700; color: #0b1727; line-height: 1.25; }
  .lp-p-chips { margin-top: .2rem; display: flex; flex-wrap: wrap; gap: .22rem; }
  .lp-p-chip {
    font-size: .53rem; color: #5a6878; background: #eef2f7;
    padding: .05rem .3rem; border-radius: 999px;
  }
  .lp-p-chip.must { background: #fdf3d7; color: #7c5a10; font-weight: 800; }

  /* Story band */
  .lp-story {
    background: rgba(255,255,255,.035); border: 1px solid #2b3346;
    border-left: 3px solid #e8b04a;
    border-radius: 14px; padding: 1.6rem 1.7rem; margin: 1rem 0 3rem;
  }
  .lp-story p {
    margin: 0 0 .8rem; color: #c3d0de; font-size: 1.02rem; line-height: 1.65;
  }
  .lp-story p:last-child { margin-bottom: 0; }
  .lp-story .who { color: #7a8aa0; font-size: .86rem; font-style: normal; }

  /* Section heads */
  .lp-h2 {
    font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; color: #f0f4fa;
    margin: 0 0 .5rem; letter-spacing: -.01em;
  }
  .lp-lead { color: #9bb0c5; font-size: 1rem; margin: 0 0 1.6rem; max-width: 42rem; }

  /* Steps */
  .lp-steps { display: grid; gap: .9rem; grid-template-columns: 1fr; }
  @media (min-width: 760px) { .lp-steps { grid-template-columns: repeat(3, 1fr); } }
  .lp-step {
    background: rgba(255,255,255,.04); border: 1px solid #2b3346;
    border-radius: 14px; padding: 1.4rem;
  }
  .lp-step .n {
    font-size: .7rem; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; color: #6c7d92; margin-bottom: .7rem;
  }
  .lp-step .ico {
    width: 44px; height: 44px; border-radius: 12px; font-size: 1.35rem;
    display: flex; align-items: center; justify-content: center; margin-bottom: .7rem;
  }
  .lp-step.s1 .ico { background: rgba(232,176,74,.14); }
  .lp-step.s2 .ico { background: rgba(143,177,216,.14); }
  .lp-step.s3 .ico { background: rgba(111,174,122,.14); }
  .lp-step h3 { margin: 0 0 .4rem; font-size: 1.15rem; color: #eaf0f7; }
  .lp-step p  { margin: 0; color: #9bb0c5; font-size: .93rem; line-height: 1.55; }
  .lp-step .k {
    margin-top: .7rem; font-size: .8rem; color: #7a8aa0;
    border-top: 1px solid rgba(255,255,255,.07); padding-top: .6rem;
  }

  /* Light payoff band — mirrors the real product: dark app, light share page */
  .lp-light {
    background: #f4f5f7; border-radius: 18px;
    padding: 2.4rem 1.6rem; margin: 3rem 0;
    color: #2a3548;
  }
  @media (min-width: 760px) { .lp-light { padding: 2.8rem 2.4rem; } }
  .lp-light h2 { color: #0b1727; font-size: clamp(1.4rem,2.6vw,1.85rem);
                 font-weight: 800; margin: 0 0 .5rem; letter-spacing: -.01em; }
  .lp-light .lp-lead { color: #5a6878; }
  .lp-feats { display: grid; gap: .7rem; grid-template-columns: 1fr; margin-top: 1.4rem; }
  @media (min-width: 640px) { .lp-feats { grid-template-columns: repeat(2, 1fr); } }
  .lp-feat {
    background: #fff; border: 1px solid #e4e7ec; border-radius: 10px;
    padding: .85rem 1rem; display: flex; gap: .7rem; align-items: flex-start;
  }
  .lp-feat .fi { font-size: 1.15rem; flex-shrink: 0; line-height: 1.3; }
  .lp-feat b { color: #0b1727; font-size: .93rem; display: block; }
  .lp-feat span { color: #5a6878; font-size: .86rem; line-height: 1.5; }

  /* Closing CTA */
  .lp-close {
    text-align: center; padding: 2.6rem 1.2rem 1rem;
  }
  .lp-close h2 { font-size: clamp(1.5rem,3vw,2.1rem); font-weight: 800;
                 color: #f0f4fa; margin: 0 0 .6rem; letter-spacing: -.01em; }
  .lp-close p { color: #9bb0c5; margin: 0 0 1.4rem; }

  .home-footer {
    margin-top: 3rem; padding-top: 1.2rem;
    border-top: 1px solid rgba(255,255,255,.06);
    color: #6c7d92; font-size: .85rem; text-align: center;
  }
</style>

<div class="home">

<?php if (!empty($_SESSION['flash_home'])): ?>
  <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.4);
              color:#f3b3b3;padding:.7rem 1rem;border-radius:8px;margin:1.4rem 0 0;
              font-size:.9rem;">
    <?= h_e($_SESSION['flash_home']) ?>
  </div>
  <?php unset($_SESSION['flash_home']); ?>
<?php endif; ?>

<?php if ($isAnon): ?>
  <!-- ══════════ 1. Anonymous: the landing page ══════════ -->

  <section class="lp-hero">
    <div>
      <span class="lp-eyebrow"><?= te('lp.eyebrow') ?></span>
      <h1><?= te('lp.h1_a') ?><br><span class="accent"><?= te('lp.h1_b') ?></span></h1>
      <p class="lp-sub"><?= te('lp.sub') ?></p>
      <div class="lp-cta">
        <a class="home-btn primary" href="/register"><?= te('lp.cta') ?></a>
        <?php if ($demoTrip): ?>
          <a class="home-btn ghost" href="<?= h_e($demoTrip) ?>"><?= te('lp.see_trip') ?> →</a>
        <?php endif; ?>
      </div>
      <p class="lp-trust"><?= te('lp.trust') ?></p>
    </div>

    <div class="lp-phone-wrap">
      <!-- Miniature of the actual Trip page, drawn in CSS -->
      <div class="lp-phone" aria-hidden="true">
        <div class="lp-p-hero">
          <div class="lp-p-title">Hilux · Brazil</div>
          <div class="lp-p-meta"><?= te('lp.mock_dates') ?></div>
          <span class="lp-p-pill">🎬 <?= te('trip.shots_captured', ['done' => 4, 'total' => 9]) ?></span>
        </div>
        <div class="lp-p-day">📅 <?= te('lp.mock_day') ?></div>

        <div class="lp-p-shot">
          <span class="lp-p-box"></span>
          <span>
            <span class="lp-p-st"><?= te('lp.mock_shot1') ?></span>
            <span class="lp-p-chips">
              <span class="lp-p-chip must">★ <?= te('trip.must') ?></span>
              <span class="lp-p-chip">🚁 <?= te('shots.type_drone') ?></span>
              <span class="lp-p-chip">🌅 <?= te('shots.light_sunrise') ?></span>
            </span>
          </span>
        </div>

        <div class="lp-p-shot">
          <span class="lp-p-box"></span>
          <span>
            <span class="lp-p-st"><?= te('lp.mock_shot2') ?></span>
            <span class="lp-p-chips">
              <span class="lp-p-chip">🎤 <?= te('shots.type_interview') ?></span>
              <span class="lp-p-chip">📍 Leblon</span>
            </span>
          </span>
        </div>

        <div class="lp-p-shot done">
          <span class="lp-p-box on"></span>
          <span>
            <span class="lp-p-st"><?= te('lp.mock_shot3') ?></span>
            <span class="lp-p-chips">
              <span class="lp-p-chip">🎥 <?= te('shots.type_broll') ?></span>
            </span>
          </span>
        </div>

        <div class="lp-p-day">✨ <?= te('shots.anytime') ?></div>
        <div class="lp-p-shot">
          <span class="lp-p-box"></span>
          <span><span class="lp-p-st"><?= te('lp.mock_shot4') ?></span></span>
        </div>
      </div>
    </div>
  </section>

  <section class="lp-story">
    <p><?= te('lp.story1') ?></p>
    <p><?= te('lp.story2') ?></p>
    <p class="who">— <?= te('lp.story_who') ?></p>
  </section>

  <h2 class="lp-h2"><?= te('lp.steps_h2') ?></h2>
  <p class="lp-lead"><?= te('lp.steps_lead') ?></p>

  <div class="lp-steps">
    <div class="lp-step s1">
      <div class="n"><?= te('lp.step_n', ['n' => 1]) ?></div>
      <div class="ico"><?= $typeIcon['dream'] ?></div>
      <h3><?= te('home.step1') ?></h3>
      <p><?= te('lp.step1_long') ?></p>
      <div class="k"><?= te('home.step1_k') ?></div>
    </div>
    <div class="lp-step s2">
      <div class="n"><?= te('lp.step_n', ['n' => 2]) ?></div>
      <div class="ico"><?= $typeIcon['vision'] ?></div>
      <h3><?= te('home.step2') ?></h3>
      <p><?= te('lp.step2_long') ?></p>
      <div class="k"><?= te('lp.step2_k') ?></div>
    </div>
    <div class="lp-step s3">
      <div class="n"><?= te('lp.step_n', ['n' => 3]) ?></div>
      <div class="ico"><?= $typeIcon['trip'] ?></div>
      <h3><?= te('home.step3') ?></h3>
      <p><?= te('lp.step3_long') ?></p>
      <div class="k"><?= te('lp.step3_k') ?></div>
    </div>
  </div>

  <section class="lp-light">
    <h2><?= te('lp.payoff_h2') ?></h2>
    <p class="lp-lead"><?= te('lp.payoff_lead') ?></p>
    <div class="lp-feats">
      <div class="lp-feat">
        <span class="fi">🎬</span>
        <span><b><?= te('lp.f1_t') ?></b><span><?= te('lp.f1_d') ?></span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">✈️</span>
        <span><b><?= te('lp.f2_t') ?></b><span><?= te('lp.f2_d') ?></span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">📍</span>
        <span><b><?= te('lp.f3_t') ?></b><span><?= te('lp.f3_d') ?></span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">🔗</span>
        <span><b><?= te('lp.f4_t') ?></b><span><?= te('lp.f4_d') ?></span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">📄</span>
        <span><b><?= te('lp.f5_t') ?></b><span><?= te('lp.f5_d') ?></span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">🖨️</span>
        <span><b><?= te('lp.f6_t') ?></b><span><?= te('lp.f6_d') ?></span></span>
      </div>
    </div>
  </section>

  <section class="lp-close">
    <h2><?= te('lp.close_h2') ?></h2>
    <p><?= te('lp.close_p') ?></p>
    <div class="lp-cta" style="justify-content:center;">
      <a class="home-btn primary" href="/register"><?= te('lp.cta') ?></a>
      <?php if ($demoTrip): ?>
        <a class="home-btn ghost" href="<?= h_e($demoTrip) ?>"><?= te('lp.see_trip') ?> →</a>
      <?php endif; ?>
    </div>
  </section>

<?php endif; ?>

  <div class="home-footer">
    <?= h_e(defined('SITE_NAME') ? SITE_NAME : 'Merely a Dream') ?> &middot; <?= te('home.step1') ?> &middot; <?= te('home.step2') ?> &middot; <?= te('home.step3') ?>
  </div>

</div>
