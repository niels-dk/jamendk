<?php
// Sidebar wrapper with global navigation and board-specific content.
$uri    = $_SERVER['REQUEST_URI'] ?? '/';
$active = fn(string $pattern) => preg_match($pattern, $uri) ? 'active' : '';
?>
<div class="sidebar">
  <div class="brand">DreamBoard</div>

  <!-- Global fold-out navigation -->
  <nav class="nav">
    <div class="new-board">
      <button type="button" class="menu-toggle btn">Boards ▾</button>
      <div class="card-menu">
        <a href="/dashboard">Dashboard</a>
        <a href="/dashboard/dream">Dreams</a>
        <a href="/dashboard/vision">Visions</a>
        <a href="/dashboard/mood">Moods</a>
        <a href="/dashboard/trip">Trip</a>
      </div>
    </div>
    <div class="new-board" style="margin-top: 8px;">
      <button type="button" class="menu-toggle btn">＋ New board ▾</button>
      <div class="card-menu">
        <a href="/dreams/new">＋ Dream</a>
        <a href="/visions/new">＋ Vision</a>
        <a href="/moods/new">＋ Mood</a>
      </div>
    </div>
  </nav>

  <!-- Context-specific sidebar content -->
  <?php
  // Only show context menu for non-dream boards
  if (!empty($boardType) && $boardType !== 'dream') {
    $partial = __DIR__ . '/sidebar_' . $boardType . '.php';
    if (file_exists($partial)) {
      include $partial;
    }
  }
  ?>
</div>
