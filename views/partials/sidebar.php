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
    <a href="/dashboard/dream"    class="<?= $active('~^/dashboard/dream(/|$)~') ?>">Dreams</a>
    <a href="/dashboard/vision"   class="<?= $active('~^/dashboard/vision(/|$)~') ?>">Visions</a>
    <a href="/dashboard/mood"     class="<?= $active('~^/dashboard/mood(/|$)~') ?>">Mood Boards</a>
    <a href="/dashboard/trip"      class="<?= $active('~^/dashboard/trip(/|$)~') ?>">Trip Layer</a>
    <a href="/settings"  class="<?= $active('~^/settings(/|$)~') ?>">Settings</a>
  </nav>
</div>
