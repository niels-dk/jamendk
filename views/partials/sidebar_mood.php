<?php
// Context menu for Mood boards
$slug = htmlspecialchars($board['slug'] ?? '');
?>
<nav class="board-nav">
  <a href="/moods/<?= $slug ?>">Info</a>
  <a href="/moods/<?= $slug ?>/media">Media</a>
  <a href="/moods/<?= $slug ?>/canvas">Canvas</a>
  <a href="/moods/<?= $slug ?>/settings" class="js-open-settings">Settings</a>
</nav>
