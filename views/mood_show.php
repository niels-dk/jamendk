<?php
/**
 * Mood board display view
 *
 * Renders a placeholder for the mood board.  The actual mood board
 * canvas and interactive editor will be implemented later.  This view
 * simply shows the board title and serves as a stub for the route.
 */
?>
<div class="card">
  <h1><?= htmlspecialchars($board['title'] ?? 'Untitled Mood Board') ?></h1>
  <p><?= htmlspecialchars($board['description'] ?? 'This mood board is under construction.  An interactive canvas will appear here in a future update.') ?></p>
  <div class="btnbar">
    <a class="btn" href="/moods/<?= htmlspecialchars($board['slug']) ?>/edit">Edit</a>
	<a class="btn ghost" href="/dashboard/mood">Back to list</a>
  </div>
</div>