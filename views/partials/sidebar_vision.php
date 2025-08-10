<?php
// Context menu for Vision boards
?>
<nav class="board-nav">
  <a href="#relations">Relations</a>
  <a href="#goals">Goals &amp; Milestones</a>
  <a href="#budget">Budget</a>
  <a href="#roles">Roles &amp; Permissions</a>
  <a href="#contacts">Contacts</a>
  <a href="#documents">Documents</a>
  <a href="#workflow">Workflow</a>
  <?php if (!empty($tripSlug)): ?>
    <a href="/trips/<?= htmlspecialchars($tripSlug) ?>">Trip Layer</a>
  <?php else: ?>
    <a href="/visions/<?= htmlspecialchars($vision['slug']) ?>/attach-trip">Attach/Create Trip</a>
  <?php endif; ?>
</nav>
