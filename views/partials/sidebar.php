<?php
$uri    = $_SERVER['REQUEST_URI'] ?? '/';
$active = fn(string $pattern) => preg_match($pattern, $uri) ? 'active' : '';
?>
<div class="sidebar">

  <!-- Global fold-out navigation -->
  <div class="nav-top">
  <nav class="nav">
    <div class="new-board">
      <button type="button" class="menu-toggle btn"><?= te('dash.boards') ?> ▾</button>
      <div class="card-menu">
        <a href="/dashboard"><?= te('nav.dashboard') ?></a>
        <a href="/dashboard/dream"><?= te('board.dreams') ?></a>
        <a href="/dashboard/vision"><?= te('board.visions') ?></a>
        <a href="/dashboard/mood"><?= te('board.moods') ?></a>
        <a href="/dashboard/trip"><?= te('board.trips') ?></a>
      </div>
    </div>
    <div class="new-board" style="margin-top: 8px;">
      <button type="button" class="menu-toggle btn">＋ <?= te('nav.new_board') ?> ▾</button>
      <div class="card-menu">
        <a href="/capture">＋ <?= te('board.dream') ?></a>
        <a href="/visions/new">＋ <?= te('board.vision') ?></a>
        <a href="/moods/new">＋ <?= te('board.mood') ?></a>
      </div>
    </div>
  </nav>
	</div>

  <!-- Context-specific navigation -->
  <?php
  if (!empty($boardType) && $boardType !== 'dream') {
    $partial = __DIR__ . '/sidebar_' . $boardType . '.php';
    if (file_exists($partial)) {
      include $partial;
    }
  }
  ?>
</div>
