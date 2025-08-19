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
  <h2>Your Mood Boards</h2>
  <?php if (!$boards): ?>
    <p>You don’t have any mood boards yet.</p>
  <?php else: ?>
    <ul>
    <?php foreach ($boards as $mb): ?>
      <li>
        <a href="/moods/<?= htmlspecialchars($mb['slug']) ?>">
          <?= htmlspecialchars($mb['title'] ?: 'Untitled Mood Board') ?>
        </a>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <div class="btnbar" style="margin-top: 1rem;">
    <a class="btn primary" href="/moods/new">Create New Mood Board</a>
  </div>
</div>