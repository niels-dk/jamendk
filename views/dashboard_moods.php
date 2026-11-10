<?php
/**
 * Dashboard list for mood boards.
 *
 * Displays the active mood boards for the current user.  This view
 * mirrors the simple listing used for dreams and visions and serves
 * as a placeholder until the full dashboard filtering/filter UI is in
 * place.  The controller populates $boards from mood_model::listActive().
 */
?>
<div class="card">
  <h2><?= te('board.moods') ?></h2>
  <?php if (!$boards): ?>
    <p><?= te('dash.no_items', ['type' => mb_strtolower(t('board.moods'))]) ?></p>
  <?php else: ?>
    <ul>
    <?php foreach ($boards as $mb): ?>
      <li>
        <a href="/moods/<?= htmlspecialchars($mb['slug']) ?>">
          <?= htmlspecialchars($mb['title'] ?: t('common.untitled')) ?>
        </a>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <div class="btnbar" style="margin-top: 1rem;">
    <a class="btn primary" href="/moods/new"><?= te('dash.create', ['type' => t('board.mood')]) ?></a>
  </div>
</div>