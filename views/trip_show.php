<?php
// views/trip_show.php — polished shareable trip page with embedded mood canvas.
// Renders in two modes from the same markup:
//   live   ($export=false) — normal web page
//   export ($export=true)  — self-contained offline HTML: every image is
//                            inlined as base64 and documents become data:
//                            URIs (prepared by the controller in $docEmbeds).
function tr_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tr_date($s) { return $s ? date('M j, Y', strtotime($s)) : ''; }

$export     = $export     ?? false;
$docEmbeds  = $docEmbeds  ?? [];
$shareToken = $shareToken ?? null; // set when rendered via the public /t/{token} URL
$downloadHref = $shareToken
    ? '/t/' . $shareToken . '/download'
    : '/trips/' . ($vision['slug'] ?? '') . '/download';

// In export mode, turn any asset URL into a data: URI (local storage files
// read from disk; remote thumbs fetched with a short timeout). Falls back
// to the online URL if the asset can't be read.
$assetUrl = function (string $url) use ($export): string {
    static $cache = [];
    if (!$export || $url === '' || str_starts_with($url, 'data:')) return $url;
    if (isset($cache[$url])) return $cache[$url];
    $data = false;
    $mime = 'image/jpeg';
    if (str_starts_with($url, '/storage/')) {
        $fs = realpath(__DIR__ . '/..') . $url;
        if (is_file($fs)) {
            $data = @file_get_contents($fs);
            $mime = ['png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp',
                     'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','bmp'=>'image/bmp']
                    [strtolower(pathinfo($fs, PATHINFO_EXTENSION))] ?? 'image/jpeg';
        }
    } elseif (preg_match('~^https?://~', $url)) {
        $ctx  = stream_context_create(['http' => ['timeout' => 6]]);
        $data = @file_get_contents($url, false, $ctx);
    }
    return $cache[$url] = ($data !== false && $data !== null && $data !== '')
        ? 'data:' . $mime . ';base64,' . base64_encode($data)
        : $url;
};

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

$mediaThumb = function(array $m) use ($assetUrl): string {
    if (!empty($m['provider']) && $m['provider'] === 'youtube' && !empty($m['provider_id'])) {
        return $assetUrl('https://img.youtube.com/vi/' . urlencode($m['provider_id']) . '/hqdefault.jpg');
    }
    if (!empty($m['uuid'])) {
        return $assetUrl('/storage/thumbs/' . urlencode($m['uuid']) . '_thumb.jpg');
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

// Plain-text place → Google Maps search link (no API key needed).
// Offline the link needs internet to open, but the text stays readable.
$mapUrl = fn(string $place): string =>
    'https://www.google.com/maps/search/?api=1&query=' . urlencode($place);

$itinerary   = $itinerary   ?? [];
$budgetItems = $budgetItems ?? [];
$shots       = $shots       ?? [];
$canCheckShots = $canCheckShots ?? false;

// Group shots by day; NULL day = "Anytime". The day flow below merges
// itinerary entries and shots chronologically so a day reads as
// "where you'll be + what you came to capture there".
$shotsByDay = [];
$shotsAnytime = [];
foreach ($shots as $s) {
    if (!empty($s['day_date'])) $shotsByDay[$s['day_date']][] = $s;
    else $shotsAnytime[] = $s;
}
$itinByDay = [];
foreach ($itinerary as $en) $itinByDay[$en['day_date']][] = $en;
$allDays = array_unique(array_merge(array_keys($itinByDay), array_keys($shotsByDay)));
sort($allDays);

$shotDone  = count(array_filter($shots, fn($s) => $s['status'] === 'captured'));
$shotMusts = array_filter($shots, fn($s) => !empty($s['priority']));
$shotMustsDone = count(array_filter($shotMusts, fn($s) => $s['status'] === 'captured'));

$SHOT_TYPE_LABEL = ['drone'=>'🚁 Drone','broll'=>'🎥 B-roll','interview'=>'🎤 Interview',
                    'timelapse'=>'⏱ Timelapse','photo'=>'📷 Photo','pov'=>'🎬 POV','other'=>'✨'];
$SHOT_LIGHT_LABEL = ['sunrise'=>'🌅 sunrise','golden'=>'🌇 golden hour','midday'=>'☀️ midday',
                     'blue'=>'🌆 blue hour','night'=>'🌙 night'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= tr_e($title) ?> — Trip</title>
<?php if (!$export): ?>
  <!-- Same install identity as the app — crew keep this page on the home screen -->
  <link rel="manifest" href="/public/manifest.json">
  <link rel="icon" type="image/png" sizes="192x192" href="/public/icons/icon-192.png">
  <link rel="apple-touch-icon" href="/public/icons/apple-touch-icon.png">
<?php endif; ?>
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

    /* Hero action pills (offline copy / print) */
    .hero-actions {
      position: absolute; top: .9rem; right: .9rem; z-index: 2;
      display: flex; gap: .4rem;
    }
    .hero-pill {
      padding: .4rem .8rem; border-radius: 999px; border: 0; cursor: pointer;
      background: rgba(11,23,39,.72); color: #fff; font-size: .8rem; font-weight: 600;
      backdrop-filter: blur(4px); font-family: inherit;
    }
    .hero-pill:hover { background: rgba(11,23,39,.9); text-decoration: none; }
    .hero.no-cover .hero-pill { background: var(--accent); }
    @media print { .hero-actions { display: none; } }

    /* Itinerary */
    .itin-day-head {
      font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
      color: var(--muted); margin: 1rem 0 .35rem;
    }
    .itin-day-head:first-child { margin-top: 0; }
    .itin-entry {
      display: flex; align-items: flex-start; gap: .8rem;
      padding: .6rem .9rem;
    }
    .itin-entry .when {
      flex-shrink: 0; width: 52px; font-family: ui-monospace, "SF Mono", Menlo, monospace;
      font-size: .85rem; color: var(--muted); padding-top: .1rem;
    }
    .itin-entry .what { min-width: 0; flex: 1; }
    .itin-entry .what .t { font-weight: 700; color: var(--ink); }
    .itin-entry .what .loc { font-size: .85rem; margin-top: .15rem; }
    .itin-entry .what .n { font-size: .85rem; color: var(--muted); margin-top: .2rem; white-space: pre-wrap; }

    /* Shots (capture list) */
    .shot-entry {
      display: flex; align-items: flex-start; gap: .7rem;
      padding: .6rem .9rem;
      border-left: 3px solid var(--accent);
    }
    .shot-entry.done { opacity: .55; }
    .shot-entry.done .t { text-decoration: line-through; }
    .shot-cb {
      flex-shrink: 0; width: 1.25rem; height: 1.25rem; margin-top: .1rem;
      accent-color: var(--accent);
    }
    .shot-cb.live { cursor: pointer; }
    .shot-entry .what { min-width: 0; flex: 1; }
    .shot-entry .t { font-weight: 700; color: var(--ink); }
    .shot-entry .meta {
      display: flex; flex-wrap: wrap; gap: .3rem .6rem;
      font-size: .8rem; color: var(--muted); margin-top: .2rem;
    }
    .shot-entry .how { font-size: .88rem; color: var(--ink-soft); margin-top: .25rem; white-space: pre-wrap; }
    .shot-entry .refs { display: flex; gap: .3rem; margin-top: .4rem; flex-wrap: wrap; }
    .shot-entry .refs img {
      width: 64px; height: 64px; object-fit: cover; border-radius: 6px;
      border: 1px solid var(--line); cursor: zoom-in;
    }
    .must-tag {
      display: inline-block; padding: .05rem .45rem; border-radius: 999px;
      background: #fdf3d7; color: #7c5a10; font-size: .72rem; font-weight: 700;
    }
    .shots-progress {
      display: inline-flex; align-items: center; gap: .35rem;
      padding: .12rem .6rem; border-radius: 999px;
      background: var(--accent-soft); color: #18467a;
      font-size: .8rem; font-weight: 600;
    }

    /* Budget breakdown */
    .budget-items { width: 100%; border-collapse: collapse; margin-top: .2rem; }
    .budget-items td {
      padding: .45rem .2rem; border-bottom: 1px solid var(--line); font-size: .92rem;
    }
    .budget-items td.amt { text-align: right; font-family: ui-monospace, "SF Mono", Menlo, monospace; white-space: nowrap; }
    .budget-items td.paid { text-align: right; width: 60px; }
    .budget-items tr.total td { border-bottom: 0; font-weight: 800; color: var(--ink); padding-top: .6rem; }
    .paid-tag {
      display: inline-block; padding: .05rem .45rem; border-radius: 999px;
      background: #d2f0d8; color: #1b5a2c; font-size: .7rem; font-weight: 700;
    }

    /* Cap the canvas snapshot height; wrapper keeps the aspect ratio true */
    .trip-canvas-wrap { margin: 0 auto; }

    /* Click-to-zoom lightbox (works offline too) */
    #trip-lb {
      position: fixed; inset: 0; z-index: 999; display: none;
      align-items: center; justify-content: center;
      background: rgba(11,23,39,.88); cursor: zoom-out; padding: 2vh 2vw;
    }
    #trip-lb.open { display: flex; }
    #trip-lb img {
      max-width: 96vw; max-height: 96vh; border-radius: 8px;
      box-shadow: 0 20px 60px rgba(0,0,0,.5); background: #fff;
    }
    .ci img { cursor: zoom-in; }
    @media print { #trip-lb { display: none !important; } }

    /* Docs that couldn't be embedded in the offline copy */
    .doc-onlineonly {
      flex-shrink: 0; padding: .35rem .65rem; border-radius: 8px;
      background: #fce8c9; color: #7c4910; font-size: .78rem; font-weight: 600;
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
    <div class="hero-actions">
      <?php if (!$export): ?>
        <a class="hero-pill" href="<?= tr_e($downloadHref) ?>"
           title="Download a single-file copy that works without internet — images and documents included">
          ⬇ Offline copy
        </a>
      <?php endif; ?>
      <button type="button" class="hero-pill" onclick="window.print()"
              title="Print, or choose 'Save as PDF' as the printer">
        🖨 Print / PDF
      </button>
    </div>
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
        <?php if (!empty($sourceDream)): ?>
          <span>From Dream: <?= tr_e($sourceDream['title'] ?: 'Untitled') ?></span>
        <?php endif; ?>
        <?php if (!empty($shots)): ?>
          <span class="shots-progress" id="shotsProgressPill">
            🎬 <?= $shotDone ?> of <?= count($shots) ?> shots captured<?=
              $shotMusts ? ' · must-haves ' . $shotMustsDone . '/' . count($shotMusts) : '' ?>
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

  <?php
    // One shot card — shared by the day flow and the Anytime section.
    $renderShot = function (array $s) use ($canCheckShots, $mapUrl, $assetUrl, $SHOT_TYPE_LABEL, $SHOT_LIGHT_LABEL) {
        $done = $s['status'] === 'captured';
        ?>
        <div class="card shot-entry <?= $done ? 'done' : '' ?>" data-shot-id="<?= (int)$s['id'] ?>">
          <input type="checkbox" class="shot-cb <?= $canCheckShots ? 'live' : '' ?>"
                 <?= $done ? 'checked' : '' ?> <?= $canCheckShots ? '' : 'disabled' ?>
                 title="<?= $canCheckShots ? ($done ? 'Reopen' : 'Mark captured') : 'Captured status' ?>">
          <span class="what">
            <span class="t"><?= tr_e($s['title']) ?></span>
            <span class="meta">
              <?php if (!empty($s['priority'])): ?><span class="must-tag">★ must</span><?php endif; ?>
              <?php if (!empty($s['shot_type'])): ?><span><?= tr_e($SHOT_TYPE_LABEL[$s['shot_type']] ?? $s['shot_type']) ?></span><?php endif; ?>
              <?php if (!empty($s['light'])): ?><span><?= tr_e($SHOT_LIGHT_LABEL[$s['light']] ?? $s['light']) ?></span><?php endif; ?>
              <?php if (!empty($s['location'])): ?>
                <span>📍 <a href="<?= tr_e($mapUrl($s['location'])) ?>" target="_blank" rel="noopener"><?= tr_e($s['location']) ?></a></span>
              <?php endif; ?>
            </span>
            <?php if (!empty($s['how_notes'])): ?>
              <div class="how"><?= tr_e($s['how_notes']) ?></div>
            <?php endif; ?>
            <?php if (!empty($s['ref_thumbs'])): ?>
              <span class="refs">
                <?php foreach ($s['ref_thumbs'] as $thumb): ?>
                  <img src="<?= tr_e($assetUrl($thumb)) ?>" alt="Reference" loading="lazy">
                <?php endforeach; ?>
              </span>
            <?php endif; ?>
          </span>
        </div>
        <?php
    };
  ?>

  <?php if (!empty($allDays)): ?>
    <h2>Itinerary</h2>
    <?php foreach ($allDays as $day): ?>
      <div class="itin-day-head">📅 <?= tr_e(date('l · M j, Y', strtotime($day))) ?></div>
      <?php foreach ($itinByDay[$day] ?? [] as $en): ?>
        <div class="card itin-entry">
          <span class="when"><?= $en['start_time'] ? tr_e(substr($en['start_time'], 0, 5)) : '·' ?></span>
          <span class="what">
            <span class="t"><?= tr_e($en['title']) ?></span>
            <?php if (!empty($en['location'])): ?>
              <div class="loc">📍 <a href="<?= tr_e($mapUrl($en['location'])) ?>" target="_blank" rel="noopener"><?= tr_e($en['location']) ?></a></div>
            <?php endif; ?>
            <?php if (!empty($en['notes'])): ?>
              <div class="n"><?= tr_e($en['notes']) ?></div>
            <?php endif; ?>
          </span>
        </div>
      <?php endforeach; ?>
      <?php foreach ($shotsByDay[$day] ?? [] as $s) $renderShot($s); ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($shotsAnytime)): ?>
    <h2>Shots — keep an eye out</h2>
    <?php foreach ($shotsAnytime as $s) $renderShot($s); ?>
  <?php endif; ?>

  <?php if (!empty($anchors) && array_filter($anchors)): ?>
    <h2>Anchors</h2>
    <div class="anchor-grid">
      <?php foreach ($anchorOrder as $key): ?>
        <?php if (empty($anchors[$key])) continue; ?>
        <div class="card anchor-block">
          <h4><?= ($anchorIcon[$key] ?? '') ?> <?= tr_e(ucfirst($key)) ?></h4>
          <?php foreach ($anchors[$key] as $val): ?>
            <?php if ($key === 'locations'): ?>
              <a class="chip" href="<?= tr_e($mapUrl($val)) ?>" target="_blank" rel="noopener"
                 title="Open in Google Maps">📍 <?= tr_e($val) ?></a>
            <?php else: ?>
              <span class="chip"><?= tr_e($val) ?></span>
            <?php endif; ?>
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
    <?php if (!empty($budgetItems)): ?>
      <?php
        $biSum   = 0;
        foreach ($budgetItems as $bi) $biSum += (int)$bi['amount_cents'];
        $biTotal = (int)($budget['amount_cents'] ?? 0);
        if ($biTotal <= 0) $biTotal = $biSum;          // no manual budget → lines define it
        $biLeft  = $biTotal - $biSum;
        $fmt     = fn(int $c) => number_format($c / 100, 2, '.', ',');
      ?>
      <div class="card" style="padding:1rem 1.3rem;">
        <table class="budget-items">
          <?php foreach ($budgetItems as $bi): ?>
            <tr>
              <td><?= tr_e($bi['label']) ?></td>
              <td class="paid"><?= !empty($bi['paid']) ? '<span class="paid-tag">paid</span>' : '' ?></td>
              <td class="amt"><?= $fmt((int)$bi['amount_cents']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if ($biTotal !== $biSum): ?>
            <tr>
              <td style="color:var(--muted);">Planned so far</td>
              <td></td>
              <td class="amt" style="color:var(--muted);"><?= $fmt($biSum) ?></td>
            </tr>
            <tr>
              <td style="color:<?= $biLeft < 0 ? '#a01a36' : 'var(--muted)' ?>;">
                <?= $biLeft < 0 ? 'Over budget' : 'Remaining' ?>
              </td>
              <td></td>
              <td class="amt" style="color:<?= $biLeft < 0 ? '#a01a36' : 'var(--muted)' ?>;"><?= $fmt(abs($biLeft)) ?></td>
            </tr>
          <?php endif; ?>
          <tr class="total">
            <td>Total budget</td>
            <td></td>
            <td class="amt"><?= $fmt($biTotal) ?> <?= tr_e($budget['currency'] ?? '') ?></td>
          </tr>
        </table>
      </div>
    <?php else: ?>
      <div class="card budget">
        <span class="amount"><?= number_format(($budget['amount_cents'] ?? 0) / 100, 2, '.', ',') ?></span>
        <span class="cur"><?= tr_e($budget['currency'] ?? '') ?></span>
      </div>
    <?php endif; ?>
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
            <?php if (!$export): ?>
              <a class="download" href="/documents/<?= tr_e($doc['uuid']) ?>/download">Download</a>
            <?php elseif (!empty($docEmbeds[$doc['uuid']])): ?>
              <!-- Embedded in this file — works fully offline -->
              <a class="download" href="<?= $docEmbeds[$doc['uuid']] ?>"
                 download="<?= tr_e(basename($name)) ?>">Download</a>
            <?php else: ?>
              <span class="doc-onlineonly" title="This file was too large to embed in the offline copy — download it online before you go offline">
                ⚠ Online only
              </span>
            <?php endif; ?>
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
      <?php
        // Cap the snapshot's on-screen height at ~560px while preserving the
        // true aspect ratio: for tall canvases, narrow the wrapper instead.
        $capW = (int)round(560 * $cw / max(1, $ch));
      ?>
      <div class="trip-canvas-wrap" style="max-width:min(100%, <?= $capW ?>px);">
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
      </div><!-- /.trip-canvas-wrap -->
    <?php elseif (empty($canvasItems)): ?>
      <div class="card canvas-empty">The mood board has no items yet.</div>
    <?php endif; ?>
  <?php endif; ?>

  <footer>
    <?php
      // The one place strangers meet the product: every trip page is sent to
      // clients, brands and crew. A quiet link here is the growth loop.
      // Absolute URL so it also works from a downloaded offline copy.
      $brandHost = defined('MAIL_SITE_HOST') && MAIL_SITE_HOST
          ? MAIL_SITE_HOST : ($_SERVER['HTTP_HOST'] ?? 'jamen.dk');
      $brandUrl  = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
          . '://' . $brandHost . '/';
    ?>
    <div style="margin-bottom:.5rem;">
      <a href="<?= tr_e($brandUrl) ?>" style="color:#5a6878;font-weight:600;"
         title="DreamBoard — catch the idea, grow the plan, open the shot list when you're standing there">
        🎬 Planned with <span style="color:#2c5aa0;">DreamBoard</span> →
      </a>
    </div>
    <?php if ($export): ?>
      <?php
        // Point stale copies back at the live version (token from the vision
        // row — present whether the export came via slug preview or /t/ URL).
        $liveToken = $shareToken ?: (string)($vision['trip_token'] ?? '');
        $liveUrl   = $liveToken
          ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/t/' . $liveToken
          : '';
      ?>
      Offline copy of “<?= tr_e($title) ?>” · generated <?= tr_e(date('M j, Y · H:i')) ?>
      — images and documents are embedded in this file.
      <?php if ($liveUrl): ?>
        <br>Latest version: <a href="<?= tr_e($liveUrl) ?>"><?= tr_e($liveUrl) ?></a>
      <?php endif; ?>
    <?php else: ?>
      Generated <?= tr_e(date('M j, Y · H:i')) ?>
    <?php endif; ?>
  </footer>

</div>

<!-- Click-to-zoom for canvas images (inline, so it works offline too) -->
<div id="trip-lb"><img alt=""></div>
<script>
(function () {
  var lb = document.getElementById('trip-lb');
  if (!lb) return;
  var lbImg = lb.querySelector('img');
  document.addEventListener('click', function (e) {
    var img = e.target.closest('.ci img, .shot-entry .refs img');
    if (img) { lbImg.src = img.src; lb.classList.add('open'); return; }
    if (e.target === lb || e.target === lbImg) lb.classList.remove('open');
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') lb.classList.remove('open');
  });
})();
</script>

<?php if (!$export): ?>
<!-- Offline support: the service worker caches this page (and thumbnails)
     on every visit, so the shot list still opens in the field with no signal. -->
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function () {});
}
</script>
<?php endif; ?>

<?php if ($canCheckShots): ?>
<!-- Shot check-off: only rendered for logged-in editors; the API re-verifies
     permissions on every call, so this is a convenience, not the gate.
     Offline: ticks are applied locally, queued in localStorage, and synced
     automatically when the connection returns — the digital pencil. -->
<script>
(function () {
  var slug = <?= json_encode((string)($vision['slug'] ?? '')) ?>;
  if (!slug) return;
  var QKEY = 'shotQueue:' + slug;
  var pill = document.getElementById('shotsProgressPill');
  var pillBase = pill ? pill.textContent.trim() : '';

  function loadQ() {
    try { return JSON.parse(localStorage.getItem(QKEY)) || {}; } catch (e) { return {}; }
  }
  function saveQ(q) {
    var n = Object.keys(q).length;
    if (n) localStorage.setItem(QKEY, JSON.stringify(q));
    else localStorage.removeItem(QKEY);
    if (pill) pill.textContent = pillBase + (n ? ' · ' + n + ' waiting to sync' : '');
  }

  function send(id, status) {
    return fetch('/api/visions/' + slug + '/shots/' + id + '/status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ status: status }).toString()
    }).then(function (r) { return r.json(); });
  }

  var flushing = false;
  function flush() {
    if (flushing) return;
    var q = loadQ();
    var ids = Object.keys(q);
    if (!ids.length) return;
    flushing = true;
    var chain = Promise.resolve();
    ids.forEach(function (id) {
      chain = chain.then(function () {
        return send(id, q[id]).then(function (j) {
          if (j && j.success) { delete q[id]; saveQ(q); }
          // Server said no (e.g. rights revoked) → drop it rather than retry forever
          else { delete q[id]; saveQ(q); }
        });
        // Network still down → keep in queue, a later flush retries
      });
    });
    chain.catch(function () {}).then(function () { flushing = false; });
  }

  // Re-apply queued ticks to the UI (a cached page shows the server's last
  // known state — the queue holds what happened since).
  (function restore() {
    var q = loadQ();
    Object.keys(q).forEach(function (id) {
      var entry = document.querySelector('.shot-entry[data-shot-id="' + id + '"]');
      if (!entry) return;
      var done = q[id] === 'captured';
      entry.classList.toggle('done', done);
      var cb = entry.querySelector('.shot-cb');
      if (cb) cb.checked = done;
    });
    saveQ(q); // refreshes the "waiting to sync" hint
  })();

  document.addEventListener('change', function (e) {
    var cb = e.target;
    if (!cb.classList || !cb.classList.contains('shot-cb') || cb.disabled) return;
    var entry = cb.closest('.shot-entry');
    if (!entry) return;
    var id = entry.dataset.shotId;
    var status = cb.checked ? 'captured' : 'planned';
    entry.classList.toggle('done', status === 'captured'); // optimistic — like a pencil
    send(id, status).then(function (j) {
      if (j && !j.success) {
        // Real server rejection → revert
        cb.checked = !cb.checked;
        entry.classList.toggle('done', cb.checked);
      }
    }).catch(function () {
      // Offline → keep the tick, queue it, sync later
      var q = loadQ();
      q[id] = status;
      saveQ(q);
    });
  });

  window.addEventListener('online', flush);
  flush(); // sync anything left over from the last offline session
})();
</script>
<?php endif; ?>

<?php if ($export && !empty($shots)): ?>
<!-- Offline copy: checkboxes work like a pencil on a printout — ticks are
     saved on this device (localStorage) and restored when the file is
     reopened. They do NOT update the live page. -->
<script>
(function () {
  var KEY = 'tripTicks:' + <?= json_encode((string)($shareToken ?: ($vision['trip_token'] ?? $vision['slug'] ?? 'trip'))) ?>;
  var pill = document.getElementById('shotsProgressPill');

  function load() {
    try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; }
  }
  function save(t) { try { localStorage.setItem(KEY, JSON.stringify(t)); } catch (e) {} }

  function recount() {
    if (!pill) return;
    var entries = document.querySelectorAll('.shot-entry');
    var done = 0, musts = 0, mustsDone = 0;
    entries.forEach(function (en) {
      var isDone = en.classList.contains('done');
      var isMust = !!en.querySelector('.must-tag');
      if (isDone) done++;
      if (isMust) { musts++; if (isDone) mustsDone++; }
    });
    pill.textContent = '🎬 ' + done + ' of ' + entries.length + ' shots captured'
      + (musts ? ' · must-haves ' + mustsDone + '/' + musts : '')
      + ' · saved on this device';
  }

  var ticks = load();
  document.querySelectorAll('.shot-entry').forEach(function (entry) {
    var cb = entry.querySelector('.shot-cb');
    if (!cb) return;
    cb.disabled = false;
    cb.classList.add('live');
    cb.title = 'Tick like a pencil — saved on this device only';
    var id = entry.dataset.shotId;
    if (id in ticks) {
      var done = !!ticks[id];
      cb.checked = done;
      entry.classList.toggle('done', done);
    }
    cb.addEventListener('change', function () {
      entry.classList.toggle('done', cb.checked);
      ticks[id] = cb.checked;
      save(ticks);
      recount();
    });
  });
  recount();
})();
</script>
<?php endif; ?>
</body>
</html>
