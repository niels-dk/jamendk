<?php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$created = !empty($board['created_at']) ? date('Y-m-d H:i:s', strtotime($board['created_at'])) : '';
$updated = !empty($board['updated_at']) ? date('Y-m-d H:i:s', strtotime($board['updated_at'])) : '';

$itemCounts = $itemCounts ?? [];
$totalItems = array_sum($itemCounts);

// Friendly singular/plural labels for canvas item kinds
// Singular/plural per kind. Adding 's' is an English rule, not a universal
// one, so both forms come from the language file.
$kindLabel = function (string $kind, int $n) : string {
    $known = ['image', 'note', 'label', 'frame', 'connector', 'text'];
    if (!in_array($kind, $known, true)) return $n . ' ' . $kind;
    return $n . ' ' . t('mood.k_' . $kind . ($n === 1 ? '_one' : '_many'));
};
?>

<h1><?= e($board['title'] ?: t('mood.untitled')) ?></h1>

<div class="card" style="padding:1.25rem 1.25rem 1rem; max-width:1200px;">
  <div class="prose" style="margin-bottom:1rem; color:#c7d2df;">
    <?php if (!empty($board['description'])): ?>
      <?= $board['description'] ?>
    <?php else: ?>
      <p style="opacity:.6;"><?= te('mood.no_description') ?></p>
    <?php endif; ?>
  </div>

  <?php if ($linkedVision): ?>
    <div class="mood-linked-vision" style="margin-bottom:1rem;">
      <span style="opacity:.7;"><?= te('mood.linked_vision') ?></span>
      <a href="/visions/<?= e($linkedVision['slug']) ?>" class="chip"
         style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .7rem;
                background:#1f2533;border:1px solid #3a76d2;border-radius:999px;
                color:inherit;text-decoration:none;margin-left:.4rem;">
        <strong><?= e($linkedVision['title'] ?: t('common.untitled')) ?></strong>
        <span style="opacity:.55;font-size:.85em;font-family:monospace;"><?= e($linkedVision['slug']) ?></span>
      </a>
    </div>
  <?php endif; ?>

  <?php if ($totalItems > 0): ?>
    <div class="mood-item-counts" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
      <?php foreach ($itemCounts as $kind => $n): ?>
        <span class="chip" style="padding:.25rem .65rem;border-radius:999px;
              background:rgba(255,255,255,.05);border:1px solid #2b3346;
              font-size:.85em;">
          <?= e($kindLabel($kind, $n)) ?>
        </span>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="opacity:.6;margin-bottom:1rem;font-size:.9em;">
      <?= te('mood.canvas_empty') ?>
    </div>
  <?php endif; ?>

  <div style="opacity:.8; margin-bottom:1rem; font-size:.9em;">
    <?php if ($created): ?><?= te('vision.created') ?> <?= e($created) ?><?php endif; ?>
    <?php if ($updated && $updated !== $created): ?> · <?= te('home.updated') ?> <?= e($updated) ?><?php endif; ?>
  </div>

  <div class="btn-group" style="display:flex;flex-wrap:wrap;gap:.5rem;">
    <a class="btn primary" href="/moods/<?= e($board['slug']) ?>/canvas"><?= te('mood.open_canvas') ?></a>
    <a class="btn" href="/moods/<?= e($board['slug']) ?>/media"><?= te('mood.media_library') ?></a>
    <a class="btn" href="/moods/<?= e($board['slug']) ?>/edit"><?= te('mood.edit_info') ?></a>
    <a class="btn" href="/dashboard/mood"><?= te('vision.back_dash') ?></a>
  </div>
</div>
