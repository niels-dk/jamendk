<?php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$created = !empty($vision['created_at']) ? date('Y-m-d H:i:s', strtotime($vision['created_at'])) : '';
$updated = !empty($vision['updated_at']) ? date('Y-m-d H:i:s', strtotime($vision['updated_at'])) : '';
?>
<h1><?= e($vision['title'] ?? 'Vision') ?></h1>

<div class="card" style="padding:1.25rem 1.25rem 1rem; max-width:1200px;">
  <div class="prose" style="margin-bottom:1rem; color:#c7d2df;">
    <?php if (!empty($vision['description'])): ?>
      <!-- description can contain markup created by your editor -->
      <?= $vision['description'] ?>
    <?php else: ?>
  <p><?= htmlspecialchars($vision['description'] ?? 'This Vision board is under construction.') ?></p>
    <?php endif; ?>
  </div>

  <div style="opacity:.8; margin-bottom:1rem;">
    <?php if ($created): ?>Created <?= e($created) ?><?php endif; ?>
    <?php if ($updated): ?> · Updated <?= e($updated) ?><?php endif; ?>
  </div>

  <div class="btn-group">
    <a class="btn primary" href="/visions/<?= e($vision['slug']) ?>/edit">Edit Vision</a>
    <a class="btn" href="/dashboard/vision">Back to dashboard</a>
  </div>
</div>
