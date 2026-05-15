<?php
// views/trip_show.php — standalone shareable trip page.
// Renders a complete HTML document. No site chrome.
function tr_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tr_date($s) { return $s ? date('M j, Y', strtotime($s)) : ''; }

$title       = $vision['title'] ?: 'Trip';
$startDate   = tr_date($vision['start_date'] ?? '');
$endDate     = tr_date($vision['end_date']   ?? '');
$updatedAt   = tr_date($vision['updated_at'] ?? '');
$dateRange   = trim(($startDate && $endDate) ? "$startDate — $endDate" : ($startDate ?: $endDate));

$STATUS_LABELS = [
    'not_started' => 'Not started',
    'in_progress' => 'In progress',
    'awaiting'    => 'Awaiting',
    'done'        => 'Done',
    'cancelled'   => 'Cancelled',
];
$PRIORITY_LABEL = function($p) {
    return 'P' . max(1, min(5, (int)$p));
};

$anchorOrder = ['locations','brands','people','seasons','time'];
$anchorIcon  = ['locations'=>'📍','brands'=>'🏷️','people'=>'👤','seasons'=>'📅','time'=>'⏱️'];

$mediaThumb = function(array $m): string {
    if (!empty($m['provider']) && $m['provider'] === 'youtube' && !empty($m['provider_id'])) {
        return 'https://img.youtube.com/vi/' . urlencode($m['provider_id']) . '/hqdefault.jpg';
    }
    if (!empty($m['uuid'])) {
        return '/storage/thumbs/' . urlencode($m['uuid']) . '_thumb.jpg';
    }
    return '';
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= tr_e($title) ?> — Trip</title>
  <style>
    /* Reset / base */
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      font: 16px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #1a2332; background: #f6f7f9;
      -webkit-font-smoothing: antialiased;
    }
    a { color: #2c5aa0; text-decoration: none; }
    a:hover { text-decoration: underline; }
    h1, h2, h3, h4 { margin: 0 0 .4em; line-height: 1.2; color: #0b1727; }
    h1 { font-size: 2rem; font-weight: 800; }
    h2 { font-size: 1.35rem; font-weight: 700; margin-top: 2rem; padding-bottom: .4rem;
         border-bottom: 1px solid #e4e7ec; }
    h3 { font-size: 1.05rem; font-weight: 700; }
    p  { margin: 0 0 .8em; }

    .wrap {
      max-width: 880px; margin: 0 auto;
      padding: 2rem 1.2rem 4rem;
    }
    .hero { margin-bottom: 1.5rem; }
    .hero .meta {
      display: flex; flex-wrap: wrap; gap: .4rem 1rem;
      color: #5a6878; font-size: .9rem; margin-top: .4rem;
    }
    .hero .desc { margin-top: .9rem; color: #2a3548; }
    .hero .desc p:last-child { margin-bottom: 0; }

    /* Anchor chips */
    .anchor-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem; margin: 1rem 0 .5rem;
    }
    .anchor-block { background: #fff; border: 1px solid #e4e7ec; border-radius: 10px; padding: .7rem .9rem; }
    .anchor-block h4 { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em;
                        color: #5a6878; margin: 0 0 .5rem; font-weight: 700; }
    .chip {
      display: inline-block; padding: .15rem .55rem; margin: .15rem .25rem .15rem 0;
      background: #eef2f7; color: #233047; border-radius: 999px; font-size: .85rem;
    }

    /* Mood gallery */
    .gallery {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: .6rem; margin-top: .8rem;
    }
    .gallery a {
      display: block; aspect-ratio: 1 / 1; background: #e4e7ec; border-radius: 8px;
      overflow: hidden; position: relative;
    }
    .gallery img {
      width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .gallery .badge {
      position: absolute; bottom: 6px; left: 6px;
      background: rgba(11,23,39,.7); color: #fff; padding: .1rem .45rem;
      border-radius: 4px; font-size: .7rem;
    }

    /* Goals */
    .goal {
      background: #fff; border: 1px solid #e4e7ec; border-radius: 10px;
      padding: .8rem 1rem; margin-bottom: .6rem;
    }
    .goal-title { font-weight: 700; color: #0b1727; }
    .goal-meta  { display: flex; flex-wrap: wrap; gap: .4rem .8rem;
                  color: #5a6878; font-size: .85rem; margin-top: .25rem; }
    .pill {
      display: inline-block; padding: .1rem .5rem; border-radius: 999px;
      font-size: .75rem; font-weight: 700;
    }
    .pill.pri      { background: #eef2f7; color: #233047; font-family: monospace; }
    .pill.pri-1    { background: #fde2e8; color: #a01a36; }
    .pill.pri-2    { background: #fce8c9; color: #7c4910; }
    .pill.status-not_started { background: #eef2f7; color: #4a5568; }
    .pill.status-in_progress { background: #d6e7fa; color: #18467a; }
    .pill.status-awaiting    { background: #fce8c9; color: #7c4910; }
    .pill.status-done        { background: #d2f0d8; color: #1b5a2c; }
    .progress-bar {
      margin-top: .5rem; height: 6px; background: #eef2f7; border-radius: 999px; overflow: hidden;
    }
    .progress-bar > span { display: block; height: 100%; background: #4a90e2; transition: width .2s; }
    .milestones { margin: .5rem 0 0; padding-left: 1.1rem; color: #2a3548; font-size: .9rem; }
    .milestones li { margin-bottom: .2rem; }
    .milestones li.done { color: #5a6878; text-decoration: line-through; }

    /* Budget */
    .budget {
      background: #fff; border: 1px solid #e4e7ec; border-radius: 10px;
      padding: .9rem 1.1rem; display: flex; align-items: baseline; gap: 1rem;
    }
    .budget .amount { font-size: 1.8rem; font-weight: 800; color: #0b1727; }
    .budget .cur    { color: #5a6878; font-weight: 600; }

    /* Contacts */
    .contacts-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: .6rem; margin-top: .5rem;
    }
    .contact {
      background: #fff; border: 1px solid #e4e7ec; border-radius: 10px;
      padding: .7rem .9rem;
    }
    .contact .name { font-weight: 700; }
    .contact .flag { display: inline-block; margin-left: .35rem;
                     font-size: .7rem; padding: .05rem .4rem; border-radius: 999px;
                     background: #d6e7fa; color: #18467a; }
    .contact .row  { font-size: .9rem; color: #2a3548; margin-top: .15rem; }
    .contact .row a { word-break: break-word; }

    /* Documents */
    .docs-list { list-style: none; padding: 0; margin: .5rem 0 0; }
    .docs-list li {
      background: #fff; border: 1px solid #e4e7ec; border-radius: 8px;
      padding: .55rem .8rem; margin-bottom: .35rem;
      display: flex; align-items: center; justify-content: space-between; gap: .6rem;
    }
    .docs-list .doc-name { font-weight: 600; word-break: break-word; }
    .docs-list .doc-meta { font-size: .8rem; color: #5a6878; }

    /* Workflow */
    .workflow {
      background: #fff; border: 1px solid #e4e7ec; border-radius: 10px;
      padding: .9rem 1.1rem;
    }
    .workflow .notes { margin-top: .5rem; color: #2a3548; white-space: pre-wrap; }

    /* Footer */
    footer {
      margin-top: 3rem; padding-top: 1rem; border-top: 1px solid #e4e7ec;
      color: #8593a6; font-size: .8rem; text-align: center;
    }

    /* Empty state */
    .empty {
      text-align: center; color: #5a6878; padding: 3rem 1rem;
    }

    /* Print */
    @media print {
      body { background: #fff; }
      h2 { break-after: avoid-page; }
      .goal, .contact, .budget, .workflow, .anchor-block, .docs-list li { break-inside: avoid; }
      .gallery a { break-inside: avoid; }
      a { color: inherit; text-decoration: none; }
    }

    /* Small-screen tweaks */
    @media (max-width: 480px) {
      .wrap { padding: 1.2rem .8rem 3rem; }
      h1 { font-size: 1.6rem; }
      .budget { flex-direction: column; align-items: flex-start; gap: .3rem; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <header class="hero">
    <h1><?= tr_e($title) ?></h1>
    <div class="meta">
      <?php if ($dateRange): ?><span><?= tr_e($dateRange) ?></span><?php endif; ?>
      <?php if ($updatedAt): ?><span>Updated <?= tr_e($updatedAt) ?></span><?php endif; ?>
      <?php if (!empty($workflow)): ?>
        <span class="pill status-<?= tr_e($workflow['status']) ?>">
          <?= tr_e($STATUS_LABELS[$workflow['status']] ?? $workflow['status']) ?>
        </span>
      <?php endif; ?>
    </div>
    <?php if (!empty($vision['description'])): ?>
      <div class="desc"><?= $vision['description'] /* trusted Trix HTML */ ?></div>
    <?php endif; ?>
  </header>

  <?php if (!$hasAnyContent): ?>
    <div class="empty">
      <p>This trip is empty.</p>
      <p style="font-size:.9em;opacity:.75;">
        Open the vision and toggle <strong>Show on Trip layer</strong> on the sections and items
        you'd like to publish here.
      </p>
    </div>
  <?php endif; ?>

  <?php if (!empty($anchors) && array_filter($anchors)): ?>
    <section>
      <h2>Anchors</h2>
      <div class="anchor-grid">
        <?php foreach ($anchorOrder as $key): ?>
          <?php if (empty($anchors[$key])) continue; ?>
          <div class="anchor-block">
            <h4><?= ($anchorIcon[$key] ?? '') ?> <?= tr_e(ucfirst($key)) ?></h4>
            <?php foreach ($anchors[$key] as $val): ?>
              <span class="chip"><?= tr_e($val) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <?php foreach ($anchors as $k => $vals): ?>
          <?php if (in_array($k, $anchorOrder, true) || empty($vals)) continue; ?>
          <div class="anchor-block">
            <h4><?= tr_e(ucfirst($k)) ?></h4>
            <?php foreach ($vals as $val): ?>
              <span class="chip"><?= tr_e($val) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($mood): ?>
    <section>
      <h2>Mood: <?= tr_e($mood['title'] ?: 'Untitled') ?></h2>
      <?php if (!empty($mood['description'])): ?>
        <div class="desc"><?= $mood['description'] ?></div>
      <?php endif; ?>
      <?php if (!empty($moodMedia)): ?>
        <div class="gallery">
          <?php foreach ($moodMedia as $m): ?>
            <?php
              $thumb = $mediaThumb($m);
              $href = !empty($m['provider']) && $m['provider'] === 'youtube' && !empty($m['provider_id'])
                ? 'https://www.youtube.com/watch?v=' . urlencode($m['provider_id'])
                : ($thumb ?: '#');
            ?>
            <a href="<?= tr_e($href) ?>" target="_blank" rel="noopener">
              <?php if ($thumb): ?>
                <img src="<?= tr_e($thumb) ?>" alt="<?= tr_e($m['file_name'] ?: $m['uuid']) ?>" loading="lazy">
              <?php endif; ?>
              <?php if (!empty($m['provider'])): ?>
                <span class="badge"><?= tr_e($m['provider']) ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if (!empty($goals)): ?>
    <section>
      <h2>Goals &amp; Milestones</h2>
      <?php foreach ($goals as $g): ?>
        <?php
          $total = count($g['milestones'] ?? []);
          $done  = 0;
          foreach (($g['milestones'] ?? []) as $m) if (!empty($m['done'])) $done++;
          $pct = $total ? round(($done / $total) * 100) : (($g['status'] === 'done') ? 100 : 0);
        ?>
        <div class="goal">
          <div class="goal-title"><?= tr_e($g['title']) ?></div>
          <div class="goal-meta">
            <span class="pill pri pri-<?= (int)$g['priority'] ?>"><?= tr_e($PRIORITY_LABEL($g['priority'])) ?></span>
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
            <div class="desc" style="margin-top:.4rem; font-size:.9rem;"><?= nl2br(tr_e($g['description'])) ?></div>
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
    </section>
  <?php endif; ?>

  <?php if ($budget): ?>
    <section>
      <h2>Budget</h2>
      <div class="budget">
        <span class="amount"><?= number_format(($budget['amount_cents'] ?? 0) / 100, 2, '.', ',') ?></span>
        <span class="cur"><?= tr_e($budget['currency'] ?? '') ?></span>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($contacts)): ?>
    <section>
      <h2>Contacts</h2>
      <div class="contacts-grid">
        <?php foreach ($contacts as $c): ?>
          <div class="contact">
            <div class="name">
              <?= tr_e($c['name'] ?: $c['email'] ?: '(unnamed)') ?>
              <?php if (!empty($c['is_main'])):    ?><span class="flag">Main</span><?php endif; ?>
              <?php if (!empty($c['is_current'])): ?><span class="flag">Current</span><?php endif; ?>
            </div>
            <?php if (!empty($c['company'])): ?>
              <div class="row"><?= tr_e($c['company']) ?></div>
            <?php endif; ?>
            <?php if (!empty($c['email'])): ?>
              <div class="row"><a href="mailto:<?= tr_e($c['email']) ?>"><?= tr_e($c['email']) ?></a></div>
            <?php endif; ?>
            <?php if (!empty($c['mobile'])): ?>
              <div class="row"><a href="tel:<?= tr_e($c['mobile']) ?>"><?= tr_e($c['mobile']) ?></a></div>
            <?php endif; ?>
            <?php if (!empty($c['address'])): ?>
              <div class="row"><?= nl2br(tr_e($c['address'])) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($documents)): ?>
    <section>
      <h2>Documents</h2>
      <ul class="docs-list">
        <?php foreach ($documents as $doc): ?>
          <li>
            <div>
              <div class="doc-name"><?= tr_e($doc['file_name'] ?? '') ?></div>
              <div class="doc-meta">
                <?= tr_e(ucfirst(str_replace('_', ' ', $doc['status'] ?? 'draft'))) ?>
                <?php if (!empty($doc['created_at'])): ?>
                  · <?= tr_e(tr_date($doc['created_at'])) ?>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($doc['uuid'])): ?>
              <a href="/documents/<?= tr_e($doc['uuid']) ?>/download">Download</a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php if (!empty($workflow) && !empty($workflow['notes'])): ?>
    <section>
      <h2>Workflow notes</h2>
      <div class="workflow">
        <div class="notes"><?= tr_e($workflow['notes']) ?></div>
      </div>
    </section>
  <?php endif; ?>

  <footer>
    Generated <?= tr_e(date('M j, Y · H:i')) ?>
  </footer>

</div>
</body>
</html>
