<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$active = function (string $pattern) use ($uri) {
    return preg_match($pattern, $uri) ? 'active' : '';
};
?>
<div class="sidebar">
  <div class="brand">DreamBoard</div>
  <nav class="nav">
    <a href="/dashboard" class="<?= $active('~^/dashboard$~') ?>">Dashboard</a>
    <a href="/dreams"    class="<?= $active('~^/dreams(/|$)~') ?>">Dreams</a>
    <a href="/visions"   class="<?= $active('~^/visions(/|$)~') ?>">Visions</a>
    <a href="/moods"     class="<?= $active('~^/moods(/|$)~') ?>">Mood Boards</a>
    <a href="/trip"      class="<?= $active('~^/trip(/|$)~') ?>">Trip Layer</a>
    <a href="/settings"  class="<?= $active('~^/settings(/|$)~') ?>">Settings</a>
  </nav>
</div>
