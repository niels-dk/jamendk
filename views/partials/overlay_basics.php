<?php
// views/partials/overlay_basics.php
$hasId = !empty($vision['id']);
$defaults = [
  'relations'=>1,'goals'=>1,'budget'=>1,'roles'=>0,'contacts'=>1,'documents'=>1,'workflow'=>1
];
$flags = array_replace($defaults, $presentationFlags ?? []);
?>
<div id="overlay-basics" class="overlay-hidden">
  <div class="overlay-content">
    <button class="close-overlay" aria-label="Close">✕</button>
    <h3>Vision Basics</h3>

    <?php if (!$hasId): ?>
      <p style="opacity:.8;margin:0 0 12px">
        Save the Vision first, then you can edit Basics.
      </p>
    <?php endif; ?>

    <form id="basicsForm">
      <?php if ($hasId): ?>
        <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>">
      <?php endif; ?>

      <label>Start date</label>
      <input type="date" name="start_date"
             value="<?= htmlspecialchars($vision['start_date'] ?? '') ?>" <?= $hasId?'':'disabled' ?>>

      <label>End date</label>
      <input type="date" name="end_date"
             value="<?= htmlspecialchars($vision['end_date'] ?? '') ?>" <?= $hasId?'':'disabled' ?>>

      <h4>Show on public view</h4>
      <?php foreach ($defaults as $section => $_): ?>
        <label class="show-flag">
          <input type="checkbox" name="show_<?= $section ?>" value="1"
                 <?= !empty($flags[$section]) ? 'checked' : '' ?> <?= $hasId?'':'disabled' ?>>
          <?= ucfirst($section) ?>
        </label>
      <?php endforeach; ?>

      <div class="overlay-actions">
        <button type="submit" class="btn primary" <?= $hasId?'':'disabled' ?>>Save</button>
        <button type="button" class="btn close-overlay">Cancel</button>
      </div>
    </form>
  </div>
</div>
