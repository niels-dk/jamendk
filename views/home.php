<?php
// views/home.php — landing + smart welcome-back state
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

$hasActivity  = $hasActivity  ?? false;
$stats        = $stats        ?? ['dreams'=>0,'visions'=>0,'moods'=>0,'trips'=>0];
$recentBoards = $recentBoards ?? [];
$upcoming     = $upcoming     ?? [];
$userName     = $userName     ?? '';

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
    border-left-width: 3px;
    border-radius: 10px; padding: .7rem .85rem; text-decoration: none;
    color: inherit; transition: background .12s, transform .12s;
    display: block; min-width: 0;
  }
  .recent-tile:hover { background: rgba(255,255,255,.07); transform: translateY(-1px); }
  /* Type-colored left accent */
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

  /* Quick-create row */
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

  /* Compact welcome bar (returning users) */
  .home-welcome-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
    margin: 1.6rem 0 1.4rem;
  }
  .home-welcome-bar h1 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0; color: #f0f4fa; }
  .home-welcome-bar .sub { margin: .25rem 0 0; color: #9bb0c5; font-size: .95rem; }

  /* Trips-ready callout under the stats */
  .stat-callout {
    display: block; margin-top: .8rem; padding: .5rem .7rem;
    background: rgba(58,118,210,.14); border: 1px solid rgba(58,118,210,.35);
    border-radius: 8px; color: #a8c4ee; font-size: .85rem; font-weight: 600;
    text-decoration: none;
  }
  .stat-callout:hover { background: rgba(58,118,210,.22); }

  /* Upcoming / overdue dates */
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
  @media (max-width: 520px) {
    .u-when { display: none; }
  }

  .home-footer {
    margin-top: 3rem; padding-top: 1.2rem;
    border-top: 1px solid rgba(255,255,255,.06);
    color: #6c7d92; font-size: .85rem; text-align: center;
  }
</style>

<div class="home">

  <?php if ($hasActivity): ?>
    <!-- Compact welcome bar for returning users -->
    <section class="home-welcome-bar">
      <div>
        <h1>Welcome back<?= $userName ? ', ' . h_e($userName) : '' ?> 👋</h1>
        <p class="sub">Pick up where you left off, or start something new.</p>
      </div>
      <a class="home-btn primary" href="/dashboard">Go to dashboard</a>
    </section>
  <?php else: ?>
    <!-- Full marketing hero for new / logged-out visitors -->
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
        <a class="home-btn primary" href="/dreams/new">+ Start your first Dream</a>
        <a class="home-btn ghost"   href="/dashboard">Browse dashboard</a>
      </div>
      <div class="home-tagline">Dream &middot; Visualise &middot; Plan &middot; Create</div>
    </section>
  <?php endif; ?>

  <?php if ($hasActivity): ?>
    <!-- Quick create -->
    <div class="quick-create">
      <span class="qc-label">Create</span>
      <a class="qc-btn qc-dream"  href="/dreams/new">🌕 New Dream</a>
      <a class="qc-btn qc-vision" href="/visions/new">📄 New Vision</a>
      <a class="qc-btn qc-mood"   href="/moods/new">🎨 New Mood</a>
    </div>

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

    <?php if (!empty($upcoming)): ?>
      <!-- Upcoming / overdue Vision dates -->
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
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (!$hasActivity): ?>
    <!-- Three phases (only for new / logged-out visitors) -->
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
  <?php endif; ?>

  <div class="home-footer">
    Dream &middot; Visualise &middot; Plan &middot; Create
  </div>

</div>
