<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$active = function (string $pattern) use ($uri) {
    return preg_match($pattern, $uri) ? 'active' : '';
};
?>
<div class="sidebar">
  <div class="brand">DreamBoard</div>

  <!-- Global navigation -->
  <nav class="nav">
    <a href="/dashboard"          class="<?= $active('~^/dashboard$~') ?>">Dashboard</a>
    <a href="/dashboard/dream"    class="<?= $active('~^/dashboard/dream(/|$)~') ?>">Dreams</a>
    <a href="/dashboard/vision"   class="<?= $active('~^/dashboard/vision(/|$)~') ?>">Visions</a>
    <a href="/dashboard/mood"     class="<?= $active('~^/dashboard/mood(/|$)~') ?>">Mood Boards</a>
    <a href="/dashboard/trip"     class="<?= $active('~^/dashboard/trip(/|$)~') ?>">Trip Layer</a>
    <a href="/settings"           class="<?= $active('~^/settings(/|$)~') ?>">Settings</a>

    <!-- “+ New Board” dropdown -->
    <div class="new-board">
      <button type="button" class="menu-toggle btn">＋ New Board</button>
      <div class="card-menu">
        <a href="/dreams/new">Dream</a>
        <a href="/visions/new">Vision</a>
        <a href="/moods/new">Mood</a>
      </div>
    </div>
  </nav>

  <!-- Board‑specific menu (appears when $boardType is set and not 'dream') -->
  <?php if (!empty($boardType) && $boardType !== 'dream'): ?>
    <nav class="board-nav" style="margin-top:1rem;">
      <?php if ($boardType === 'vision'): ?>
        <a href="#basics">Basics</a>
        <a href="#relations">Relations</a>
        <a href="#goals">Goals &amp; Milestones</a>
        <a href="#budget">Budget</a>
        <a href="#roles">Roles &amp; Permissions</a>
        <a href="#contacts">Contacts</a>
        <a href="#documents">Documents</a>
        <a href="#workflow">Workflow</a>
      <?php elseif ($boardType === 'mood'): ?>
        <a href="#info">Info</a>
        <a href="#media">Media</a>
        <a href="#colours">Colours</a>
        <a href="#references">References</a>
      <?php elseif ($boardType === 'trip'): ?>
        <a href="#itinerary">Itinerary</a>
        <a href="#expenses">Expenses</a>
        <a href="#notes">Notes</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
