<?php
// views/trip_show.php — polished shareable trip page with embedded mood canvas.
function tr_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tr_date($s) { return $s ? date('M j, Y', strtotime($s)) : ''; }

$title       = $vision['title'] ?: 'Trip';
$startDate   = tr_date($vision['start_date'] ?? '');
$endDate     = tr_date($vision['end_date']   ?? '');
$updatedAt   = tr_date($vision['updated_at'] ?? '');
$dateRange   = ($startDate && $endDate) ? "$startDate — $endDate" : ($startDate ?: $endDate);

$STATUS_LABELS = [
    'not_started' => 'Not started',
    'in_progress' => 'In progress',
    'awaiting'    => 'Awaiting',
    'done'        => 'Done',
    'cancelled'   => 'Cancelled',
];
$priorityLabel = fn($p) => 'P' . max(1, min(5, (int)$p));

$anchorOrder = ['locations','brands','people','seasons','time'];
$anchorIcon  = ['locations'=>'📍','brands'=>'🏷️','people'=>'👤','seasons'=>'🗓️','time'=>'⏱️'];

$mediaThumb = function(array $m): string {
    if (!empty($m['provider']) && $m['provider'] === 'youtube' && !empty($m['provider_id'])) {
        return 'https://img.youtube.com/vi/' . urlencode($m['provider_id']) . '/hqdefault.jpg';
    }
    if (!empty($m['uuid'])) {
        return '/storage/thumbs/' . urlencode($m['uuid']) . '_thumb.jpg';
    }
    return '';
};

// Pick a cover image (first frame with media, or first image item) for the hero
$coverUrl = '';
if (!empty($canvasItems)) {
    foreach ($canvasItems as $ci) {
        if ($ci['kind'] === 'connector') continue;
        if (!empty($ci['media']) && !empty($ci['media']['uuid'])) {
            $coverUrl = $mediaThumb($ci['media']);
            break;
        }
    }
}
if (!$coverUrl && !empty($moodMedia)) {
    foreach ($moodMedia as $m) {
        if (($m['mime_type'] ?? '') && str_starts_with($m['mime_type'], 'image/')) {
            $coverUrl = $mediaThumb($m);
            break;
        }
    }
}

// Helpers for canvas snapshot positioning
$pctOf = function (int $v, int $base): string {
    return number_format(($v / max(1, $base)) * 100, 4, '.', '') . '%';
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= tr_e($title) ?> — Trip</title>
  <style>
    :root {
      --bg:        #f4f5f7;
      --surface:   #ffffff;
      --ink:       #0b1727;
      --ink-soft:  #2a3548;
      --muted:     #5a6878;
      --line:      #e4e7ec;
      --accent:    #2c5aa0;
      --accent-soft:#eaf1fb;
      --radius:    12px;
      --shadow:    0 1px 2px rgba(11,23,39,.04), 0 8px 24px rgba(11,23,39,.05);
    }
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      font: 16px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: var(--ink-soft); background: var(--bg);
      -webkit-font-smoothing: antialiased;
    }
    a { color: var(--accent); text-decoration: none; }
    a:hover { text-decoration: underline; }
    h1, h2, h3, h4 { margin: 0 0 .4em; line-height: 1.2; color: var(--ink); }
    h1 { font-size: 2.4rem; font-weight: 800; letter-spacing: -0.01em; }
    h2 {
      font-size: 1.05rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
      color: var(--muted); margin: 2.4rem 0 .9rem;
      display: flex; align-items: center; gap: .6rem;
    }
    h2::after {
      content: ""; flex: 1 1 auto; height: 1px; background: var(--line);
    }
    h3 { font-size: 1.05rem; font-weight: 700; }
    p  { margin: 0 0 .8em; }

    .wrap { max-width: 920px; margin: 0 auto; padding: 0 1.1rem 4rem; }

    /* Hero */
    .hero {
      position: relative; border-radius: var(--radius); overflow: hidden;
      background: var(--surface); box-shadow: var(--shadow);
      margin: 2rem 0 1.5rem;
    }
    .hero-cover {
      height: 220px; background-size: cover; background-position: center;
      background-color: #1a2332;
    }
    .hero-cover::after {
      content: ""; position: absolute; left: 0; right: 0; top: 0; height: 220px;
      background: linear-gradient(180deg, rgba(11,23,39,.05), rgba(11,23,39,.6));
    }
    .hero.no-cover .hero-cover { display: none; }
    .hero-body { padding: 1.6rem 1.8rem 1.4rem; position: relative; }
    .hero.has-cover .hero-body {
      margin-top: -60px; background: var(--surface); border-radius: var(--radius);
      box-shadow: 0 -1px 0 rgba(255,255,255,.4);
    }
    .hero-meta {
      display: flex; flex-wrap: wrap; gap: .4rem .9rem;
      color: var(--muted); font-size: .9rem; margin-top: .35rem;
    }
    .hero-desc { margin-top: 1rem; color: var(--ink-soft); }
    .hero-desc p:last-child { margin-bottom: 0; }

    /* Pills */
    .pill {
      display: inline-block; padding: .12rem .55rem; border-radius: 999px;
      font-size: .75rem; font-weight: 600;
    }
    .pill.status-not_started { background: #eef2f7; color: #4a5568; }
    .pill.status-in_progress { background: #d6e7fa; color: #18467a; }
    .pill.status-awaiting    { background: #fce8c9; color: #7c4910; }
    .pill.status-done        { background: #d2f0d8; color: #1b5a2c; }
    .pill.status-cancelled   { background: #eef2f7; color: #777; }
    .pill.pri { font-family: ui-monospace, "SF Mono", Menlo, monospace; }
    .pill.pri-1 { background: #fde2e8; color: #a01a36; }
    .pill.pri-2 { background: #fce8c9; color: #7c4910; }
    .pill.pri-3 { background: #d6e7fa; color: #18467a; }
    .pill.pri-4 { background: #eef2f7; color: #4a5568; }
    .pill.pri-5 { background: #eef2f7; color: #8593a6; }

    /* Section card */
    .card {
      background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
      padding: 1rem 1.15rem; box-shadow: var(--shadow);
    }
    .card + .card { margin-top: .55rem; }

    /* Anchors */
    .anchor-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .7rem;
    }
    .anchor-block { padding: .9rem 1rem; }
    .anchor-block h4 {
      font-size: .72rem; text-transform: uppercase; letter-spacing: .08em;
      color: var(--muted); margin: 0 0 .55rem; font-weight: 700;
      display: flex; align-items: center; gap: .35rem;
    }
    .chip {
      display: inline-block; padding: .15rem .55rem; margin: .15rem .25rem .15rem 0;
      background: var(--accent-soft); color: #18467a; border-radius: 999px; font-size: .85rem;
    }

    /* Goals */
    .goal { padding: .9rem 1.1rem; }
    .goal-title { font-weight: 700; color: var(--ink); }
    .goal-meta { display: flex; flex-wrap: wrap; gap: .4rem .8rem;
                 color: var(--muted); font-size: .85rem; margin-top: .25rem; }
    .progress-bar {
      margin-top: .55rem; height: 6px; background: #eef2f7;
      border-radius: 999px; overflow: hidden;
    }
    .progress-bar > span {
      display: block; height: 100%;
      background: linear-gradient(90deg, #4a90e2, #2c5aa0);
    }
    .milestones { margin: .55rem 0 0; padding-left: 1.1rem; color: var(--ink-soft); font-size: .9rem; }
    .milestones li { margin-bottom: .15rem; }
    .milestones li.done { color: var(--muted); text-decoration: line-through; }

    /* Budget */
    .budget {
      display: flex; align-items: baseline; gap: 1rem;
      padding: 1.1rem 1.3rem;
    }
    .budget .amount { font-size: 2rem; font-weight: 800; color: var(--ink); }
    .budget .cur    { color: var(--muted); font-weight: 600; }

    /* Contacts */
    .contacts-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: .6rem;
    }
    .contact { padding: .9rem 1rem; }
    .contact .name { font-weight: 700; color: var(--ink); }
    .contact .flag {
      display: inline-block; margin-left: .35rem;
      font-size: .7rem; padding: .05rem .45rem; border-radius: 999px;
      background: var(--accent-soft); color: #18467a; vertical-align: middle;
    }
    .contact .row { font-size: .9rem; color: var(--ink-soft); margin-top: .2rem; word-break: break-word; }

    /* Documents */
    .docs-list { list-style: none; padding: 0; margin: 0; }
    .docs-list li {
      padding: .65rem .9rem; margin-bottom: .35rem;
      display: flex; align-items: center; justify-content: space-between; gap: .6rem;
    }
    .docs-list .doc-icon {
      width: 36px; height: 36px; border-radius: 8px;
      background: var(--accent-soft); color: #18467a;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: .75rem; flex-shrink: 0;
    }
    .docs-list .doc-info { min-width: 0; flex: 1; }
    .docs-list .doc-name { font-weight: 600; word-break: break-word; color: var(--ink); }
    .docs-list .doc-meta { font-size: .8rem; color: var(--muted); margin-top: .15rem; }
    .docs-list .pill { font-size: .68rem; padding: .08rem .45rem; }
    .docs-list .download {
      flex-shrink: 0; padding: .35rem .65rem; border-radius: 8px;
      background: var(--accent); color: #fff; font-size: .8rem; font-weight: 600;
    }
    .docs-list .download:hover { background: #1d4789; text-decoration: none; }

    /* Workflow */
    .workflow { padding: 1rem 1.2rem; }
    .workflow .notes { color: var(--ink-soft); white-space: pre-wrap; line-height: 1.55; }

    /* ── Mood canvas snapshot ──────────────────────────── */
    .trip-canvas {
      position: relative; width: 100%;
      background: #fafbfc; border: 1px solid var(--line);
      border-radius: var(--radius); overflow: hidden;
      box-shadow: var(--shadow);
    }
    .trip-canvas .lines {
      position: absolute; inset: 0; width: 100%; height: 100%;
      pointer-events: none;
    }
    .ci {
      position: absolute; box-sizing: border-box;
      background: #fff; border: 1px solid #d8dde5; overflow: hidden;
      border-radius: 3px;
    }
    .ci.ci-note  { background: #fff8d6; border-color: #e7d77a; padding: 6px 7px;
                   font-size: 11px; line-height: 1.3; color: #4a3a00; }
    .ci.ci-label { background: #eef2f7; border-color: #c0cad6; padding: 4px 6px;
                   font-size: 11px; line-height: 1.3; color: var(--ink); }
    .ci.ci-frame { background: #fff; }
    .ci.ci-frame .frame-title {
      position: absolute; left: 0; right: 0; top: 0;
      padding: 2px 6px; font-size: 10px; font-weight: 600;
      background: rgba(255,255,255,.9); border-bottom: 1px solid #e4e7ec;
      color: var(--ink); pointer-events: none;
    }
    .ci.ci-frame img,
    .ci.ci-image img {
      width: 100%; height: 100%; display: block; user-select: none;
    }
    .ci.ci-image { border: none; }
    .canvas-empty {
      padding: 2.4rem 1rem; text-align: center; color: var(--muted); font-size: .9rem;
    }

    /* Footer */
    footer {
      margin-top: 3rem; padding-top: 1rem; border-top: 1px solid var(--line);
      color: #8593a6; font-size: .8rem; text-align: center;
    }

    /* Empty state */
    .empty { text-align: center; color: var(--muted); padding: 3rem 1rem; }

    /* Print */
    @media print {
      body { background: #fff; }
      .hero, .card, .trip-canvas { box-shadow: none; break-inside: avoid; }
      h2 { break-after: avoid-page; }
      .goal, .contact, .docs-list li { break-inside: avoid; }
      a { color: inherit; text-decoration: none; }
      .hero-cover { display: none; }
      .hero.has-cover .hero-body { margin-top: 0; }
    }

    /* Small-screen tweaks */
    @media (max-width: 540px) {
      .wrap { padding: 0 .7rem 3rem; }
      .hero { margin-top: 1rem; }
      .hero-cover { height: 160px; }
      .hero.has-cover .hero-body { margin-top: -50px; }
      .hero-body { padding: 1.1rem 1.2rem; }
      h1 { font-size: 1.7rem; }
      .budget { flex-direction: column; align-items: flex-start; gap: .25rem; padding: 1rem 1.2rem; }
      .docs-list li { flex-wrap: wrap; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <header class="hero <?= $coverUrl ? 'has-cover' : 'no-cover' ?>">
    <?php if ($coverUrl): ?>
      <div class="hero-cover" style="background-image:url('<?= tr_e($coverUrl) ?>');"></div>
    <?php endif; ?>
    <div class="hero-body">
      <h1><?= tr_e($title) ?></h1>
      <div class="hero-meta">
        <?php if ($dateRange): ?><span><?= tr_e($dateRange) ?></span><?php endif; ?>
        <?php if ($updatedAt): ?><span>Updated <?= tr_e($updatedAt) ?></span><?php endif; ?>
        <?php if (!empty($workflow)): ?>
          <span class="pill status-<?= tr_e($workflow['status']) ?>">
            <?= tr_e($STATUS_LABELS[$workflow['status']] ?? $workflow['status']) ?>
          </span>
        <?php endif; ?>
      </div>
      <?php if (!empty($vision['description'])): ?>
        <div class="hero-desc"><?= $vision['description'] ?></div>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!$hasAnyContent): ?>
    <div class="empty">
      <p>This trip is empty.</p>
      <p style="font-size:.9em;opacity:.75;">
        Open the vision and toggle <strong>Show on Trip layer</strong> on the sections and items
        you'd like to publish.
      </p>
    </div>
  <?php endif; ?>

  <?php if (!empty($anchors) && array_filter($anchors)): ?>
    <h2>Anchors</h2>
    <div class="anchor-grid">
      <?php foreach ($anchorOrder as $key): ?>
        <?php if (empty($anchors[$key])) continue; ?>
        <div class="card anchor-block">
          <h4><?= ($anchorIcon[$key] ?? '') ?> <?= tr_e(ucfirst($key)) ?></h4>
          <?php foreach ($anchors[$key] as $val): ?>
            <span class="chip"><?= tr_e($val) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <?php foreach ($anchors as $k => $vals): ?>
        <?php if (in_array($k, $anchorOrder, true) || empty($vals)) continue; ?>
        <div class="card anchor-block">
          <h4><?= tr_e(ucfirst($k)) ?></h4>
          <?php foreach ($vals as $val): ?>
            <span class="chip"><?= tr_e($val) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($goals)): ?>
    <h2>Goals &amp; Milestones</h2>
    <?php foreach ($goals as $g): ?>
      <?php
        $total = count($g['milestones'] ?? []);
        $done  = 0;
        foreach (($g['milestones'] ?? []) as $m) if (!empty($m['done'])) $done++;
        $pct = $total ? round(($done / $total) * 100) : (($g['status'] === 'done') ? 100 : 0);
      ?>
      <div class="card goal">
        <div class="goal-title"><?= tr_e($g['title']) ?></div>
        <div class="goal-meta">
          <span class="pill pri pri-<?= (int)$g['priority'] ?>"><?= tr_e($priorityLabel($g['priority'])) ?></span>
          <span class="pill status-<?= tr_e($g['status']) ?>">
            <?= tr_e($STATUS_LABELS[$g['status']] ?? $g['status']) ?>
          </span>
          <?php if (!empty($g['due_date'])): ?>
            <span>Due <?= tr_e(tr_date($g['due_date'])) ?></span>
          <?php endif; ?>
          <?php if ($total): ?>
            <span><?= $done ?>/<?= $total ?> milestones · <?= $pct ?>%</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($g['description'])): ?>
          <div style="margin-top:.45rem; font-size:.9rem; color:var(--ink-soft);"><?= nl2br(tr_e($g['description'])) ?></div>
        <?php endif; ?>
        <?php if ($total): ?>
          <div class="progress-bar"><span style="width: <?= $pct ?>%"></span></div>
          <ul class="milestones">
            <?php foreach ($g['milestones'] as $m): ?>
              <li class="<?= !empty($m['done']) ? 'done' : '' ?>"><?= tr_e($m['text']) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($budget): ?>
    <h2>Budget</h2>
    <div class="card budget">
      <span class="amount"><?= number_format(($budget['amount_cents'] ?? 0) / 100, 2, '.', ',') ?></span>
      <span class="cur"><?= tr_e($budget['currency'] ?? '') ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($contacts)): ?>
    <h2>Contacts</h2>
    <div class="contacts-grid">
      <?php foreach ($contacts as $c): ?>
        <div class="card contact">
          <div class="name">
            <?= tr_e($c['name'] ?: $c['email'] ?: '(unnamed)') ?>
            <?php if (!empty($c['is_main'])):    ?><span class="flag">Main</span><?php endif; ?>
            <?php if (!empty($c['is_current'])): ?><span class="flag">Current</span><?php endif; ?>
          </div>
          <?php if (!empty($c['company'])): ?><div class="row"><?= tr_e($c['company']) ?></div><?php endif; ?>
          <?php if (!empty($c['email'])):   ?><div class="row"><a href="mailto:<?= tr_e($c['email']) ?>"><?= tr_e($c['email']) ?></a></div><?php endif; ?>
          <?php if (!empty($c['mobile'])):  ?><div class="row"><a href="tel:<?= tr_e($c['mobile']) ?>"><?= tr_e($c['mobile']) ?></a></div><?php endif; ?>
          <?php if (!empty($c['address'])): ?><div class="row"><?= nl2br(tr_e($c['address'])) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($documents)): ?>
    <h2>Documents</h2>
    <ul class="docs-list">
      <?php foreach ($documents as $doc): ?>
        <?php
          $name = $doc['file_name'] ?? '';
          $ext  = pathinfo($name, PATHINFO_EXTENSION) ?: 'FILE';
          $status = $doc['status'] ?? 'draft';
        ?>
        <li class="card" style="padding:.65rem .9rem;">
          <div class="doc-icon"><?= tr_e(strtoupper(substr($ext, 0, 4))) ?></div>
          <div class="doc-info">
            <div class="doc-name"><?= tr_e($name) ?></div>
            <div class="doc-meta">
              <span class="pill status-<?= tr_e(in_array($status,['final','done']) ? 'done' : ($status==='waiting_brand'?'awaiting':($status==='signed'?'in_progress':'not_started'))) ?>">
                <?= tr_e(ucfirst(str_replace('_',' ',$status))) ?>
              </span>
              <?php if (!empty($doc['created_at'])): ?>
                <span> · <?= tr_e(tr_date($doc['created_at'])) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php if (!empty($doc['uuid'])): ?>
            <a class="download" href="/documents/<?= tr_e($doc['uuid']) ?>/download">Download</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!empty($workflow) && !empty($workflow['notes'])): ?>
    <h2>Workflow notes</h2>
    <div class="card workflow">
      <div class="notes"><?= tr_e($workflow['notes']) ?></div>
    </div>
  <?php endif; ?>

  <?php if ($mood): ?>
    <h2>Mood: <?= tr_e($mood['title'] ?: 'Untitled') ?></h2>
    <?php if (!empty($mood['description'])): ?>
      <div class="card" style="padding:.9rem 1.1rem; margin-bottom:.6rem;">
        <?= $mood['description'] ?>
      </div>
    <?php endif; ?>

    <?php if ($canvasBounds && !empty($canvasItems)): ?>
      <?php
        $cw = $canvasBounds['cw'];
        $ch = $canvasBounds['ch'];
        $mx = $canvasBounds['minX'];
        $my = $canvasBounds['minY'];

        // Sort items by z for layer order (connectors come from SVG, on the bottom layer)
        $sortedItems = $canvasItems;
        uasort($sortedItems, function ($a, $b) {
            if ($a['kind'] === 'connector' && $b['kind'] !== 'connector') return -1;
            if ($b['kind'] === 'connector' && $a['kind'] !== 'connector') return 1;
            if ($a['z'] !== $b['z']) return $a['z'] <=> $b['z'];
            return $a['id'] <=> $b['id'];
        });
      ?>
      <div class="trip-canvas" style="aspect-ratio: <?= $cw ?> / <?= $ch ?>;">
        <svg class="lines" viewBox="0 0 <?= $cw ?> <?= $ch ?>" preserveAspectRatio="none">
          <defs>
            <marker id="trip-arrow" viewBox="0 0 10 10" refX="9" refY="5"
                    markerWidth="7" markerHeight="7" orient="auto-start-reverse">
              <path d="M0,0 L10,5 L0,10 z" fill="context-stroke" stroke="none"></path>
            </marker>
          </defs>
          <?php foreach ($sortedItems as $ci): ?>
            <?php
              if ($ci['kind'] !== 'connector') continue;
              $aId = (int)($ci['payload']['a']['item'] ?? 0);
              $bId = (int)($ci['payload']['b']['item'] ?? 0);
              if (!isset($canvasItems[$aId]) || !isset($canvasItems[$bId])) continue;
              $a = $canvasItems[$aId];
              $b = $canvasItems[$bId];
              $ax = $a['x'] + $a['w'] / 2 - $mx;
              $ay = $a['y'] + $a['h'] / 2 - $my;
              $bx = $b['x'] + $b['w'] / 2 - $mx;
              $by = $b['y'] + $b['h'] / 2 - $my;
              $arrows = $ci['payload']['arrows'] ?? 'end';
              if (!in_array($arrows, ['none','end','start','both'], true)) $arrows = 'end';
              $dashed = !empty($ci['payload']['dashed']);
              $label  = trim((string)($ci['payload']['label'] ?? ''));
            ?>
            <line x1="<?= $ax ?>" y1="<?= $ay ?>" x2="<?= $bx ?>" y2="<?= $by ?>"
                  stroke="#7a8593" stroke-width="2" stroke-linecap="round"
                  <?= $dashed ? 'stroke-dasharray="6 4"' : '' ?>
                  <?= in_array($arrows, ['end','both'],   true) ? 'marker-end="url(#trip-arrow)"'   : '' ?>
                  <?= in_array($arrows, ['start','both'], true) ? 'marker-start="url(#trip-arrow)"' : '' ?> />
            <?php if ($label !== ''): ?>
              <text x="<?= ($ax + $bx) / 2 ?>" y="<?= ($ay + $by) / 2 ?>"
                    text-anchor="middle" dominant-baseline="middle"
                    font-size="14" fill="#2a3548"
                    paint-order="stroke" stroke="#fafbfc" stroke-width="4">
                <?= tr_e($label) ?>
              </text>
            <?php endif; ?>
          <?php endforeach; ?>
        </svg>

        <?php foreach ($sortedItems as $ci): ?>
          <?php
            if ($ci['kind'] === 'connector') continue;
            $left   = $pctOf($ci['x'] - $mx, $cw);
            $top    = $pctOf($ci['y'] - $my, $ch);
            $width  = $pctOf($ci['w'], $cw);
            $height = $pctOf($ci['h'], $ch);
            $rot    = (int)($ci['rotation'] ?? 0);
            $transform = $rot ? "transform:rotate({$rot}deg);" : '';
            $imgPos = $ci['payload']['image_pos'] ?? ['x'=>50,'y'=>50];
            $opx = is_numeric($imgPos['x'] ?? null) ? (float)$imgPos['x'] : 50;
            $opy = is_numeric($imgPos['y'] ?? null) ? (float)$imgPos['y'] : 50;
          ?>
          <div class="ci ci-<?= tr_e($ci['kind']) ?>"
               style="left:<?= $left ?>; top:<?= $top ?>; width:<?= $width ?>; height:<?= $height ?>; <?= $transform ?>">
            <?php if ($ci['kind'] === 'frame'): ?>
              <?php $thumb = $ci['media'] ? $mediaThumb($ci['media']) : ''; ?>
              <?php if ($thumb): ?>
                <img src="<?= tr_e($thumb) ?>" alt=""
                     style="object-fit:cover;object-position:<?= $opx ?>% <?= $opy ?>%;" loading="lazy">
              <?php else: ?>
                <div class="frame-title">
                  <?= tr_e($ci['payload']['title'] ?? 'Frame') ?>
                </div>
              <?php endif; ?>
            <?php elseif ($ci['kind'] === 'image'): ?>
              <?php $thumb = $ci['media'] ? $mediaThumb($ci['media']) : ''; ?>
              <?php if ($thumb): ?>
                <img src="<?= tr_e($thumb) ?>" alt="" style="object-fit:cover;" loading="lazy">
              <?php endif; ?>
            <?php elseif ($ci['kind'] === 'note' || $ci['kind'] === 'label' || $ci['kind'] === 'text'): ?>
              <?= tr_e($ci['payload']['text'] ?? '') ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php elseif (empty($canvasItems)): ?>
      <div class="card canvas-empty">The mood board has no items yet.</div>
    <?php endif; ?>
  <?php endif; ?>

  <footer>
    Generated <?= tr_e(date('M j, Y · H:i')) ?>
  </footer>

</div>
</body>
</html>
