<?php
// Declare helper function BEFORE any output
if (!function_exists('icon')) {
  function icon($name) {
    $map = [
      'pin' => '<svg viewBox="0 0 24 24"><path d="M12 2a6 6 0 0 0-6 6c0 4.4 6 12 6 12s6-7.6 6-12a6 6 0 0 0-6-6zM12 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>',
      'bag' => '<svg viewBox="0 0 24 24"><path d="M6 7V6a6 6 0 1 1 12 0v1h3v15H3V7h3zm2 0h8V6a4 4 0 1 0-8 0v1z"/></svg>',
      'user' => '<svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z"/></svg>',
      'calendar' => '<svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14zm0-12H5V6h14z"/></svg>'
    ];
    return $map[$name] ?? '';
  }
}

ob_start();
?>

<h1><?= htmlspecialchars($dream['title']) ?></h1>

<div class="card">
  <div class="prose prose-invert max-w-none">
    <?= $dream['description'] ?>
  </div>

  <?php if (array_filter($anchors)): ?>
    <div class="anchor-grid">

      <?php if (!empty($anchors['locations'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('pin') ?> Locations</h3>
          <?php foreach ($anchors['locations'] as $l): ?>
            <span class="chip"><?= htmlspecialchars($l) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($anchors['brands'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('bag') ?> Brands</h3>
          <?php foreach ($anchors['brands'] as $b): ?>
            <span class="chip"><?= htmlspecialchars($b) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($anchors['people'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('user') ?> People</h3>
          <?php foreach ($anchors['people'] as $p): ?>
            <span class="chip"><?= htmlspecialchars($p) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($anchors['seasons'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('calendar') ?> Seasons / Time</h3>
          <?php foreach ($anchors['seasons'] as $s): ?>
            <span class="chip"><?= htmlspecialchars($s) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

    </div><!-- /grid -->
  <?php endif; ?>

  <p style="margin-top:1.4rem"><small>Created <?= $dream['created_at'] ?></small></p>

  <a class="btn" href="/dreams/<?= $dream['slug'] ?>/edit">Edit Dream</a>
  <a class="btn" href="/dashboard" style="margin-left:.6rem">Back to dashboard</a>
</div>

<?php
$content = ob_get_clean();
//	include __DIR__ . '/../views/layout.php';

