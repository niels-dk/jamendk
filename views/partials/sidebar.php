<?php
/**
 * views/partials/sidebar.php
 * Left sidebar navigation for Dream/Vision/Mood/Trip boards
 */
?>
<div class="sidebar">
  <div class="brand">DreamBoard</div>
  <nav class="nav">
    <a href="/dashboard" class="<?= ($_SERVER['REQUEST_URI'] === '/dashboard') ? 'active' : '' ?>">Dashboard</a>
    <a href="/dreams" class="<?= (strpos($_SERVER['REQUEST_URI'], '/dreams') === 0) ? 'active' : '' ?>">Dreams</a>
    <a href="/visions" class="<?= (strpos($_SERVER['REQUEST_URI'], '/visions') === 0) ? 'active' : '' ?>">Visions</a>
    <a href="/moods" class="<?= (strpos($_SERVER['REQUEST_URI'], '/moods') === 0) ? 'active' : '' ?>">Mood Boards</a>
    <a href="/trip" class="<?= (strpos($_SERVER['REQUEST_URI'], '/trip') === 0) ? 'active' : '' ?>">Trip Layer</a>
    <a href="/settings" class="<?= (strpos($_SERVER['REQUEST_URI'], '/settings') === 0) ? 'active' : '' ?>">Settings</a>
  </nav>
</div>
