<?php
// views/home.php — three states:
//   1. anonymous            → marketing landing (sell the idea, then sign up)
//   2. logged in, no boards → get-started
//   3. logged in, active    → welcome back
function h_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function h_dt($s) { return $s ? date('M j, Y', strtotime($s)) : ''; }

// Relative time: "just now", "3 days ago", "in 2 days", "yesterday", etc.
function h_rel($s) {
    if (!$s) return '';
    $t = strtotime($s);
    if (!$t) return '';
    $diff = $t - time();              // future = positive
    $past = $diff < 0;
    $a = abs($diff);
    if ($a < 60)        return $past ? 'just now' : 'in a moment';
    if ($a < 3600)      { $n = round($a/60);   return $past ? "$n min ago"  : "in $n min"; }
    if ($a < 86400)     { $n = round($a/3600); return $past ? "$n hr ago"   : "in $n hr"; }
    $days = round($a/86400);
    if ($days === 1)    return $past ? 'yesterday' : 'tomorrow';
    if ($days < 30)     return $past ? "$days days ago" : "in $days days";
    return date('M j, Y', $t);
}
// Days until a date (negative = overdue). Date-only granularity.
function h_days_until($s) {
    if (!$s) return null;
    $t = strtotime($s);
    if (!$t) return null;
    $today = strtotime('today');
    return (int) floor(($t - $today) / 86400);
}

global $isAuthenticated;
$isAnon       = empty($isAuthenticated);
$hasActivity  = $hasActivity  ?? false;
$stats        = $stats        ?? ['dreams'=>0,'visions'=>0,'moods'=>0,'trips'=>0];
$recentBoards = $recentBoards ?? [];
$upcoming     = $upcoming     ?? [];
$userName     = $userName     ?? '';

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

  /* Example-project offer on the empty dashboard */
  .lp-example {
    display: flex; flex-wrap: wrap; align-items: center; gap: 1.2rem;
    justify-content: space-between;
    background: rgba(232,176,74,.07);
    border: 1px solid rgba(232,176,74,.28);
    border-radius: 14px; padding: 1.2rem 1.4rem; margin: 0 0 1.8rem;
  }
  .lp-ex-copy { flex: 1; min-width: 260px; }
  .lp-example h3 { margin: 0 0 .35rem; font-size: 1.05rem; color: #eaf0f7; }
  .lp-example p  { margin: 0; color: #9bb0c5; font-size: .92rem; line-height: 1.55; max-width: 48rem; }
  .lp-example button { flex-shrink: 0; cursor: pointer; font-family: inherit; }

  /* ── Logged-in states ────────────────────────────────────────────────── */

  .welcome {
    display: grid; grid-template-columns: minmax(220px, 1.1fr) 2fr; gap: 1rem;
    margin-bottom: 2rem;
  }
  .welcome .stat-card,
  .welcome .recent-card {
    background: rgba(255,255,255,.04);
    border: 1px solid #2b3346; border-radius: 14px; padding: 1.1rem 1.2rem;
  }
  .welcome h3 {
    margin: 0 0 .9rem; font-size: .8rem; text-transform: uppercase;
    letter-spacing: .08em; color: #8593a6; font-weight: 700;
  }
  .stat-row {
    display: flex; flex-direction: column; gap: .35rem; font-size: .95rem;
  }
  .stat-row a {
    display: flex; align-items: center; justify-content: space-between;
    padding: .45rem .55rem; border-radius: 8px;
    color: #cfdbe8; text-decoration: none;
  }
  .stat-row a:hover { background: rgba(255,255,255,.06); }
  .stat-row .label { display: flex; align-items: center; gap: .5rem; }
  .stat-row .num {
    font-family: ui-monospace, monospace; font-weight: 700; color: #8fb1d8;
  }

  .recent-grid { display: grid; grid-template-columns: 1fr; gap: .5rem; }
  @media (min-width: 640px) {
    .recent-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
  }
  .recent-tile {
    background: rgba(255,255,255,.03); border: 1px solid #2b3346;
    border-left-width: 3px;
    border-radius: 10px; padding: .7rem .85rem; text-decoration: none;
    color: inherit; transition: background .12s, transform .12s;
    display: block; min-width: 0;
  }
  .recent-tile:hover { background: rgba(255,255,255,.07); transform: translateY(-1px); }
  .recent-tile.rt-dream  { border-left-color: #e8b04a; }
  .recent-tile.rt-vision { border-left-color: #8fb1d8; }
  .recent-tile.rt-mood   { border-left-color: #d87aa8; }
  .recent-tile .rt-type {
    font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .08em;
  }
  .recent-tile .rt-title {
    font-weight: 700; color: #eaf0f7; margin: .2rem 0 .25rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .recent-tile .rt-meta { font-size: .8rem; color: #7a8aa0; }

  .quick-create {
    display: flex; flex-wrap: wrap; align-items: center; gap: .5rem;
    margin: 0 0 1.4rem;
  }
  .quick-create .qc-label {
    font-size: .8rem; text-transform: uppercase; letter-spacing: .08em;
    color: #6c7d92; font-weight: 700; margin-right: .2rem;
  }
  .quick-create .qc-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .5rem .9rem; border-radius: 10px; text-decoration: none;
    font-weight: 600; font-size: .9rem; color: #eaf0f7;
    background: rgba(255,255,255,.05); border: 1px solid #2b3346;
    transition: background .12s, transform .12s, border-color .12s;
  }
  .quick-create .qc-btn:hover { background: rgba(255,255,255,.1); transform: translateY(-1px); }
  .quick-create .qc-dream:hover  { border-color: #e8b04a; }
  .quick-create .qc-vision:hover { border-color: #8fb1d8; }
  .quick-create .qc-mood:hover   { border-color: #d87aa8; }

  @media (max-width: 720px) { .welcome { grid-template-columns: 1fr; } }

  .home-section-title {
    color: #6c7d92; font-size: .8rem; letter-spacing: .12em;
    text-transform: uppercase; font-weight: 700;
    margin: 2.4rem 0 .9rem;
  }

  .home-welcome-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
    margin: 1.6rem 0 1.4rem;
  }
  .home-welcome-bar h1 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0; color: #f0f4fa; }
  .home-welcome-bar .sub { margin: .25rem 0 0; color: #9bb0c5; font-size: .95rem; }

  .stat-callout {
    display: block; margin-top: .8rem; padding: .5rem .7rem;
    background: rgba(58,118,210,.14); border: 1px solid rgba(58,118,210,.35);
    border-radius: 8px; color: #a8c4ee; font-size: .85rem; font-weight: 600;
    text-decoration: none;
  }
  .stat-callout:hover { background: rgba(58,118,210,.22); }

  .upcoming { display: flex; flex-direction: column; gap: .4rem; }
  .u-row {
    display: flex; align-items: center; gap: .7rem;
    padding: .55rem .8rem; border-radius: 10px; text-decoration: none;
    background: rgba(255,255,255,.04); border: 1px solid #2b3346;
    border-left-width: 3px;
    transition: background .12s, transform .12s;
  }
  .u-row:hover { background: rgba(255,255,255,.08); transform: translateY(-1px); }
  .u-row.u-overdue  { border-left-color: #e06a6a; }
  .u-row.u-ending   { border-left-color: #e8b04a; }
  .u-row.u-starting { border-left-color: #6fae7a; }
  .u-badge {
    font-size: .68rem; text-transform: uppercase; letter-spacing: .06em;
    font-weight: 700; padding: .12rem .5rem; border-radius: 999px; flex-shrink: 0;
  }
  .u-overdue  .u-badge { background: rgba(224,106,106,.18); color: #f0a0a0; }
  .u-ending   .u-badge { background: rgba(232,176,74,.18);  color: #e8c889; }
  .u-starting .u-badge { background: rgba(111,174,122,.18); color: #9bd6a6; }
  .u-title {
    flex: 1; min-width: 0; color: #eaf0f7; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .u-when { color: #8593a6; font-size: .85rem; flex-shrink: 0; }
  @media (max-width: 520px) { .u-when { display: none; } }

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
      <span class="lp-eyebrow">For filmmakers &amp; creators</span>
      <h1>You'll forget the shot<br><span class="accent">by the time you get there.</span></h1>
      <p class="lp-sub">
        New place, new light, a hundred things happening at once — and the idea you
        had three weeks ago is gone. DreamBoard catches it in seconds, grows it into
        a real plan, and puts the shot list in your hand when you're standing in
        the right place.
      </p>
      <div class="lp-cta">
        <a class="home-btn primary" href="/register">Create your free account</a>
        <?php if ($demoTrip): ?>
          <a class="home-btn ghost" href="<?= h_e($demoTrip) ?>">See a real Trip page →</a>
        <?php endif; ?>
      </div>
      <p class="lp-trust">Free · No card · Works offline when the signal doesn't</p>
    </div>

    <div class="lp-phone-wrap">
      <!-- Miniature of the actual Trip page, drawn in CSS -->
      <div class="lp-phone" aria-hidden="true">
        <div class="lp-p-hero">
          <div class="lp-p-title">Hilux · Brazil</div>
          <div class="lp-p-meta">Mar 14 — Apr 2 · Updated today</div>
          <span class="lp-p-pill">🎬 4 of 9 shots captured</span>
        </div>
        <div class="lp-p-day">📅 Saturday · Mar 14</div>

        <div class="lp-p-shot">
          <span class="lp-p-box"></span>
          <span>
            <span class="lp-p-st">Sunrise drone over Arpoador</span>
            <span class="lp-p-chips">
              <span class="lp-p-chip must">★ must</span>
              <span class="lp-p-chip">🚁 Drone</span>
              <span class="lp-p-chip">🌅 sunrise</span>
            </span>
          </span>
        </div>

        <div class="lp-p-shot">
          <span class="lp-p-box"></span>
          <span>
            <span class="lp-p-st">Piece to camera — why this road</span>
            <span class="lp-p-chips">
              <span class="lp-p-chip">🎤 Interview</span>
              <span class="lp-p-chip">📍 Leblon</span>
            </span>
          </span>
        </div>

        <div class="lp-p-shot done">
          <span class="lp-p-box on"></span>
          <span>
            <span class="lp-p-st">Refuel stop, wide, low angle</span>
            <span class="lp-p-chips">
              <span class="lp-p-chip">🎥 B-roll</span>
            </span>
          </span>
        </div>

        <div class="lp-p-day">✨ Anytime — keep an eye out</div>
        <div class="lp-p-shot">
          <span class="lp-p-box"></span>
          <span><span class="lp-p-st">Hilux covered in red dust</span></span>
        </div>
      </div>
    </div>
  </section>

  <section class="lp-story">
    <p>
      “I drove around Brazil with a camera. Every day was new, everything was
      worth filming — and by the time I got somewhere, the idea I'd had for
      that exact place was gone. Too much, too fast, nothing written down.”
    </p>
    <p>
      “So I built the thing I needed: catch the idea the second it lands, add
      the detail later when you're sitting still, and open the plan when you
      arrive.”
    </p>
    <p class="who">— Niels, who built DreamBoard after that trip</p>
  </section>

  <h2 class="lp-h2">Three steps, from spark to standing there</h2>
  <p class="lp-lead">
    Ideas arrive fast and messy. Plans take time. DreamBoard is built around that gap.
  </p>

  <div class="lp-steps">
    <div class="lp-step s1">
      <div class="n">Step 1</div>
      <div class="ico"><?= $typeIcon['dream'] ?></div>
      <h3>Catch it</h3>
      <p>
        A Dream is one line: “Get sponsored by Toyota and drive their Hilux
        across a continent.” No forms, no fields — just the thought before it
        evaporates.
      </p>
      <div class="k">Works with no signal. Syncs when you're back.</div>
    </div>
    <div class="lp-step s2">
      <div class="n">Step 2</div>
      <div class="ico"><?= $typeIcon['vision'] ?></div>
      <h3>Grow it</h3>
      <p>
        When you're ready, it becomes a Vision: dates, the people you're
        talking to, the contracts and bookings, the budget — and the shot list
        of what you actually want to film, with reference images pinned to each one.
      </p>
      <div class="k">Bring in your team, hand work back and forth.</div>
    </div>
    <div class="lp-step s3">
      <div class="n">Step 3</div>
      <div class="ico"><?= $typeIcon['trip'] ?></div>
      <h3>Take it with you</h3>
      <p>
        Publish a Trip page: one link, no login, your day-by-day plan and shot
        list. Tick shots off as you get them — even with no signal — and share
        the same link with a client or crew.
      </p>
      <div class="k">Or print it and use a pencil. Genuinely.</div>
    </div>
  </div>

  <section class="lp-light">
    <h2>The page you open when you're actually there</h2>
    <p class="lp-lead">
      Everything you planned, on your phone, at 6am on a roadside. Built for
      the moment you need it — not the meeting where you made it.
    </p>
    <div class="lp-feats">
      <div class="lp-feat">
        <span class="fi">🎬</span>
        <span><b>Shot list by day</b><span>What to film, the angle, the light,
          what to say to camera — with your mood-board references attached.</span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">✈️</span>
        <span><b>Works offline</b><span>The page keeps working with no signal.
          Tick shots off in the field; they sync themselves when you're back.</span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">📍</span>
        <span><b>Every place is a map link</b><span>Locations in your itinerary
          and shots open straight into Google Maps.</span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">🔗</span>
        <span><b>One link to share</b><span>Send it to a client, a brand or your
          crew. No account needed to read it. You choose what's visible.</span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">📄</span>
        <span><b>Contracts &amp; contacts, kept together</b><span>The deal, the
          bookings, and the person to call — attached to the project, not lost in email.</span></span>
      </div>
      <div class="lp-feat">
        <span class="fi">🖨️</span>
        <span><b>Print it</b><span>Some days the best device is paper and a
          pencil in your pocket. One button.</span></span>
      </div>
    </div>
  </section>

  <section class="lp-close">
    <h2>Your next idea is going to arrive at a bad time.</h2>
    <p>Have somewhere to put it.</p>
    <div class="lp-cta" style="justify-content:center;">
      <a class="home-btn primary" href="/register">Create your free account</a>
      <?php if ($demoTrip): ?>
        <a class="home-btn ghost" href="<?= h_e($demoTrip) ?>">See a real Trip page →</a>
      <?php endif; ?>
    </div>
  </section>

<?php elseif (!$hasActivity): ?>
  <!-- ══════════ 2. Logged in, nothing created yet ══════════ -->

  <section class="home-welcome-bar">
    <div>
      <h1>Welcome<?= $userName ? ', ' . h_e($userName) : '' ?> 👋</h1>
      <p class="sub">Start with a Dream — one line is enough. You can grow it later.</p>
    </div>
    <a class="home-btn primary" href="/dreams/new">+ Start your first Dream</a>
  </section>

  <!-- A blank dashboard plus four unfamiliar nouns is a bad first minute.
       One click gives them a finished project to take apart instead. -->
  <form method="post" action="/demo/load" class="lp-example">
    <input type="hidden" name="csrf_token" value="<?= h_e(csrf_token()) ?>">
    <div class="lp-ex-copy">
      <h3>🚚 Not sure where to start?</h3>
      <p>
        Load a complete example project — a Dream that grew into a real Vision
        with a shot list, budget, contacts and a published Trip page. It goes
        into your account, so you can click through it, change it, and delete
        it when you've got the idea.
      </p>
    </div>
    <button type="submit" class="home-btn primary">Load example project</button>
  </form>

  <div class="lp-steps">
    <div class="lp-step s1">
      <div class="n">Step 1</div>
      <div class="ico"><?= $typeIcon['dream'] ?></div>
      <h3>Catch it</h3>
      <p>A Dream is one line — the thought before it evaporates. No forms, no fields.</p>
      <div class="k">Works with no signal. Syncs when you're back.</div>
    </div>
    <div class="lp-step s2">
      <div class="n">Step 2</div>
      <div class="ico"><?= $typeIcon['vision'] ?></div>
      <h3>Grow it</h3>
      <p>Promote it to a Vision: dates, contacts, documents, budget, and the shot
         list of what you want to film.</p>
      <div class="k">Bring in your team when you need them.</div>
    </div>
    <div class="lp-step s3">
      <div class="n">Step 3</div>
      <div class="ico"><?= $typeIcon['trip'] ?></div>
      <h3>Take it with you</h3>
      <p>Publish a Trip page — one link, no login, works offline, ready to share.</p>
      <div class="k">Or print it and use a pencil.</div>
    </div>
  </div>

<?php else: ?>
  <!-- ══════════ 3. Logged in, active ══════════ -->

  <section class="home-welcome-bar">
    <div>
      <h1>Welcome back<?= $userName ? ', ' . h_e($userName) : '' ?> 👋</h1>
      <p class="sub">Pick up where you left off, or start something new.</p>
    </div>
    <a class="home-btn primary" href="/dashboard">Go to dashboard</a>
  </section>

  <div class="quick-create">
    <span class="qc-label">Create</span>
    <a class="qc-btn qc-dream"  href="/dreams/new">🌕 New Dream</a>
    <a class="qc-btn qc-vision" href="/visions/new">📄 New Vision</a>
    <a class="qc-btn qc-mood"   href="/moods/new">🎨 New Mood</a>
  </div>

  <div class="welcome">
    <div class="stat-card">
      <h3>Your boards</h3>
      <div class="stat-row">
        <a href="/dashboard/dream">
          <span class="label"><?= h_e($typeIcon['dream']) ?> Dreams</span>
          <span class="num"><?= $stats['dreams'] ?></span>
        </a>
        <a href="/dashboard/vision">
          <span class="label"><?= h_e($typeIcon['vision']) ?> Visions</span>
          <span class="num"><?= $stats['visions'] ?></span>
        </a>
        <a href="/dashboard/mood">
          <span class="label"><?= h_e($typeIcon['mood']) ?> Moods</span>
          <span class="num"><?= $stats['moods'] ?></span>
        </a>
        <a href="/dashboard/trip">
          <span class="label"><?= h_e($typeIcon['trip']) ?> Trips</span>
          <span class="num"><?= $stats['trips'] ?></span>
        </a>
      </div>
      <?php if ((int)$stats['trips'] > 0): ?>
        <a class="stat-callout" href="/dashboard/trip">
          ✨ <?= (int)$stats['trips'] ?> trip<?= $stats['trips'] == 1 ? '' : 's' ?> ready to share
        </a>
      <?php endif; ?>
    </div>

    <div class="recent-card">
      <h3>Recently updated</h3>
      <?php if (empty($recentBoards)): ?>
        <p style="color:#7a8aa0;font-size:.9em;margin:0;">No recent activity yet.</p>
      <?php else: ?>
        <div class="recent-grid">
          <?php foreach ($recentBoards as $rb): ?>
            <?php
              $type = $rb['type'] ?? 'dream';
              $href = '/' . $type . 's/' . h_e($rb['slug']);
              $label = ucfirst($type);
            ?>
            <a class="recent-tile rt-<?= h_e($type) ?>" href="<?= $href ?>">
              <div class="rt-type"><?= h_e($typeIcon[$type] ?? '') ?> <?= h_e($label) ?></div>
              <div class="rt-title"><?= h_e($rb['title'] ?: 'Untitled') ?></div>
              <div class="rt-meta">Updated <?= h_e(h_rel($rb['ts'] ?? null)) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php $taskDeadlines = $taskDeadlines ?? []; ?>
  <?php if (!empty($upcoming) || !empty($taskDeadlines)): ?>
    <div class="home-section-title">Upcoming dates</div>
    <div class="upcoming">
      <?php foreach ($upcoming as $u): ?>
        <?php
          // Prefer the nearest relevant date: an overdue/soon end date wins,
          // otherwise an upcoming start date.
          $endDays   = h_days_until($u['end_date'] ?? null);
          $startDays = h_days_until($u['start_date'] ?? null);
          $isComplete = ($u['workflow_status'] ?? '') === 'complete';

          $kind = null; $days = null; $dateStr = null;
          if ($u['end_date'] && $endDays !== null && ($endDays <= 10) && !$isComplete) {
            $kind = $endDays < 0 ? 'overdue' : 'ending';
            $days = $endDays; $dateStr = $u['end_date'];
          } elseif ($u['start_date'] && $startDays !== null && $startDays >= 0 && $startDays <= 10) {
            $kind = 'starting'; $days = $startDays; $dateStr = $u['start_date'];
          } elseif ($u['end_date'] && $endDays !== null) {
            $kind = $endDays < 0 ? 'overdue' : 'ending';
            $days = $endDays; $dateStr = $u['end_date'];
          }
          if ($kind === null) continue;

          if ($kind === 'overdue')      { $badge = 'Overdue';  $cls = 'u-overdue';  $rel = abs($days).' day'.(abs($days)==1?'':'s').' ago'; }
          elseif ($kind === 'ending')   { $badge = 'Ends';     $cls = 'u-ending';   $rel = $days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in $days days"); }
          else                          { $badge = 'Starts';   $cls = 'u-starting'; $rel = $days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in $days days"); }
        ?>
        <a class="u-row <?= $cls ?>" href="/visions/<?= h_e($u['slug']) ?>">
          <span class="u-badge"><?= $badge ?></span>
          <span class="u-title"><?= h_e($u['title'] ?: 'Untitled vision') ?></span>
          <span class="u-when"><?= h_e($rel) ?> · <?= h_e(h_dt($dateStr)) ?></span>
        </a>
      <?php endforeach; ?>

      <?php foreach ($taskDeadlines as $t): ?>
        <?php
          $days = h_days_until($t['due_date'] ?? null);
          if ($days === null) continue;
          $overdue = $days < 0;
          $cls = $overdue ? 'u-overdue' : ($days <= 2 ? 'u-ending' : 'u-starting');
          $badge = $t['kind'] === 'milestone' ? 'Milestone' : 'Goal';
          $rel = $overdue
            ? abs($days).' day'.(abs($days)==1?'':'s').' ago'
            : ($days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in $days days"));
          $who = !empty($t['assignee_name']) ? ' · '.$t['assignee_name'] : '';
        ?>
        <a class="u-row <?= $cls ?>" href="/visions/<?= h_e($t['vision_slug']) ?>">
          <span class="u-badge"><?= $badge ?></span>
          <span class="u-title">
            <?= h_e($t['title'] ?: 'Untitled') ?>
            <span style="opacity:.55;font-weight:400;">— <?= h_e($t['vision_title'] ?: 'vision') ?><?= h_e($who) ?></span>
          </span>
          <span class="u-when"><?= h_e($rel) ?> · <?= h_e(h_dt($t['due_date'])) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

  <div class="home-footer">
    DreamBoard &middot; Dream &middot; Visualise &middot; Plan &middot; Create
  </div>

</div>
