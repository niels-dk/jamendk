<?php
$badges = $sidebarBadges ?? [];
$badge = function (string $key) use ($badges): string {
    $n = (int)($badges[$key] ?? 0);
    if ($n <= 0) return '';
    return '<span class="board-nav-badge">' . $n . '</span>';
};
$dot = function (string $key) use ($badges): string {
    return !empty($badges[$key]) ? '<span class="board-nav-dot" title="In use"></span>' : '';
};
?>
<style>
  .board-nav a {
    display: flex; align-items: center; justify-content: space-between;
    gap: .5rem;
  }
  .board-nav-badge {
    display: inline-block; min-width: 1.4rem; padding: 0 .4rem;
    border-radius: 999px; font-size: .7rem; font-weight: 700;
    background: rgba(58,118,210,.2); color: #8fb1d8;
    text-align: center; line-height: 1.4rem;
  }
  .board-nav-dot {
    display: inline-block; width: .55rem; height: .55rem; border-radius: 50%;
    background: #3a76d2; box-shadow: 0 0 0 2px rgba(58,118,210,.18);
  }
</style>
<nav class="board-nav">
  <a href="#basics"    data-overlay="basics"><span>Basics</span></a>
  <a href="#relations" data-overlay="relations"><span>Relations</span><?= $dot('relations') ?></a>
  <a href="#goals"     data-overlay="goals"><span>Goals &amp; Milestones</span><?= $badge('goals') ?></a>
  <a href="#budget"    data-overlay="budget"><span>Budget</span><?= $dot('budget') ?></a>
  <a href="#roles"     data-overlay="roles"><span>Roles &amp; Permissions</span></a>
  <a href="#contacts"  data-overlay="contacts"><span>Contacts</span><?= $badge('contacts') ?></a>
  <a href="#documents" data-overlay="documents"><span>Documents</span><?= $badge('documents') ?></a>
  <a href="#workflow"  data-overlay="workflow"><span>Workflow</span><?= $dot('workflow') ?></a>
</nav>
