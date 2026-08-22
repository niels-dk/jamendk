<?php
// Context menu for Mood boards
$slug = htmlspecialchars($board['slug'] ?? '');
?>
<nav class="board-nav">
  <a href="/moods/<?= $slug ?>"><?= te('mood.nav_info') ?></a>
  <a href="/moods/<?= $slug ?>/media"><?= te('mood.nav_media') ?></a>
  <a href="/moods/<?= $slug ?>/canvas"><?= te('mood.nav_canvas') ?></a>
  <a href="/moods/<?= $slug ?>/settings" class="js-open-settings">Settings</a>
</nav>
