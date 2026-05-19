<?php
// views/home.php — landing + smart welcome-back state
function h_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function h_dt($s) { return $s ? date('M j, Y', strtotime($s)) : ''; }
$hasActivity  = $hasActivity  ?? false;
$stats        = $stats        ?? ['dreams'=>0,'visions'=>0,'moods'=>0,'trips'=>0];
$recentBoards = $recentBoards ?? [];

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

  /* Hero */
  .home-hero {
    text-align: center; padding: 3.5rem 1rem 3rem;
    background: radial-gradient(ellipse at top, rgba(58,118,210,.18), transparent 60%);
    border-radius: 18px;
    margin: 1.4rem 0 2rem;
  }
  .home-hero h1 {
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 800; letter-spacing: -0.01em; line-height: 1.1;
    margin: 0 0 .6rem; color: #f0f4fa;
  }
  .home-hero h1 .accent { color: #8fb1d8; }
  .home-hero .sub {
    color: #9bb0c5; font-size: 1.1rem;
    max-width: 36rem; margin: 0 auto 1.6rem;
  }
  .home-hero .cta {
    display: inline-flex; flex-wrap: wrap; gap: .55rem; justify-content: center;
  }
  .home-btn {
    display: inline-block; padding: .65rem 1.1rem; border-radius: 10px;
    font-weight: 600; font-size: .95rem; text-decoration: none;
    border: 1px solid transparent; transition: transform .12s, background .12s;
  }
  .home-btn.primary { background: #3a76d2; color: #fff; }
  .home-btn.primary:hover { background: #2c5aa0; transform: translateY(-1px); }
  .home-btn.ghost   { background: transparent; color: #cfdbe8; border-color: #2b3346; }
  .home-btn.ghost:hover { background: rgba(255,255,255,.06); }
  .home-tagline {
    margin-top: 1.3rem; color: #6c7d92; font-size: .85rem; letter-spacing: .14em;
    text-transform: uppercase;
  }

  /* Welcome back */
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

  .recent-grid {
    display: grid; grid-template-columns: 1fr; gap: .5rem;
  }
  @media (min-width: 640px) {
    .recent-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
  }
  .recent-tile {
    background: rgba(255,255,255,.03); border: 1px solid #2b3346;
    border-radius: 10px; padding: .7rem .85rem; text-decoration: none;
    color: inherit; transition: background .12s, transform .12s;
    display: block; min-width: 0;
  }
  .recent-tile:hover { background: rgba(255,255,255,.07); transform: translateY(-1px); }
  .recent-tile .rt-type {
    font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .08em;
  }
  .recent-tile .rt-title {
    font-weight: 700; color: #eaf0f7; margin: .2rem 0 .25rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .recent-tile .rt-meta { font-size: .8rem; color: #7a8aa0; }

  @media (max-width: 720px) {
    .welcome { grid-template-columns: 1fr; }
  }

  /* Phases explainer */
  .phases {
    display: grid; gap: .9rem; margin-top: 1.5rem;
    grid-template-columns: 1fr;
  }
  @media (min-width: 760px) {
    .phases { grid-template-columns: repeat(3, 1fr); }
  }
  .phase {
    background: rgba(255,255,255,.04); border: 1px solid #2b3346;
    border-radius: 14px; padding: 1.3rem 1.3rem 1.2rem;
    display: flex; flex-direction: column; gap: .5rem;
  }
  .phase .ph-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(58,118,210,.16); color: #8fb1d8;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; margin-bottom: .2rem;
  }
  .phase h3 { margin: 0; font-size: 1.1rem; color: #eaf0f7; }
  .phase p  { margin: 0; color: #9bb0c5; font-size: .95rem; line-height: 1.5; }

  .home-section-title {
    color: #6c7d92; font-size: .8rem; letter-spacing: .12em;
    text-transform: uppercase; font-weight: 700;
    margin: 2.4rem 0 .9rem;
  }

  .home-footer {
    margin-top: 3rem; padding-top: 1.2rem;
    border-top: 1px solid rgba(255,255,255,.06);
    color: #6c7d92; font-size: .85rem; text-align: center;
  }
</style>

<div class="home">

  <!-- Hero -->
  <section class="home-hero">
    <h1>
      The future begins in the<br>
      <span class="accent">space between ideas</span>
    </h1>
    <p class="sub">
      Capture the spark, sketch the vision, build the trip.<br>
      A space to dream, plan and share what you're working toward.
    </p>
    <div class="cta">
      <?php if ($hasActivity): ?>
        <a class="home-btn primary" href="/dashboard">Go to dashboard</a>
        <a class="home-btn ghost"   href="/dreams/new">+ New Dream</a>
      <?php else: ?>
        <a class="home-btn primary" href="/dreams/new">+ Start your first Dream</a>
        <a class="home-btn ghost"   href="/dashboard">Browse dashboard</a>
      <?php endif; ?>
    </div>
    <div class="home-tagline">Dream &middot; Visualise &middot; Plan &middot; Create</div>
  </section>

  <?php if ($hasActivity): ?>
    <!-- Welcome-back panel -->
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
              <a class="recent-tile" href="<?= $href ?>">
                <div class="rt-type"><?= h_e($typeIcon[$type] ?? '') ?> <?= h_e($label) ?></div>
                <div class="rt-title"><?= h_e($rb['title'] ?: 'Untitled') ?></div>
                <div class="rt-meta">Updated <?= h_e(h_dt($rb['updated_at'])) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Three phases -->
  <div class="home-section-title">How it works</div>
  <div class="phases">
    <div class="phase">
      <div class="ph-icon"><?= $typeIcon['dream'] ?></div>
      <h3>Dream</h3>
      <p>Capture the rough idea — title, scope, and the people, places and brands it touches. Quick, low-friction.</p>
    </div>
    <div class="phase">
      <div class="ph-icon"><?= $typeIcon['vision'] ?></div>
      <h3>Vision</h3>
      <p>Promote a Dream into a Vision: add dates, goals, milestones, budget, contacts, documents and workflow notes.</p>
    </div>
    <div class="phase">
      <div class="ph-icon"><?= $typeIcon['trip'] ?></div>
      <h3>Trip</h3>
      <p>Pair a Vision with a Mood board and publish a shareable Trip page — mobile-friendly, ready to send.</p>
    </div>
  </div>

  <div class="home-footer">
    Dream &middot; Visualise &middot; Plan &middot; Create
  </div>

</div>
