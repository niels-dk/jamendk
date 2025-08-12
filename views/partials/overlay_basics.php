<?php
// views/partials/overlay_basics.php
// Assumes $presentationFlags, $vision are in scope (provided by controller)
$defaults = ['relations'=>1,'goals'=>1,'budget'=>1,'roles'=>0,'contacts'=>1,'documents'=>1,'workflow'=>1];
$flags    = array_replace($defaults, $presentationFlags ?? []);
?>
<h3>Vision Basics</h3>
<form id="basicsForm">
  <input type="hidden" name="vision_id" value="<?= (int)($vision['id'] ?? 0) ?>">
  <label>Start date</label>
  <input type="date" name="start_date" value="<?= htmlspecialchars($vision['start_date'] ?? '') ?>">
  <label>End date</label>
  <input type="date" name="end_date" value="<?= htmlspecialchars($vision['end_date'] ?? '') ?>">
  <h4>Show on public view</h4>
  <?php foreach ($defaults as $section => $_): ?>
    <div class="switch" style="display:flex;align-items:center;gap:.5rem">
      <label style="min-width:120px" for="flag_<?= $section ?>"><?= ucfirst($section) ?></label>
      <input id="flag_<?= $section ?>" type="checkbox" name="<?= $section ?>"
             <?= !empty($flags[$section]) ? 'checked' : '' ?>>
      <span class="knob" aria-hidden="true"></span>
    </div>
  <?php endforeach; ?>
</form>
