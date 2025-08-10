<?php
// views/partials/overlay_basics.php
// Expects: $vision['id'] and $presentationFlags (array of booleans keyed by section)

if (!empty($vision['id'])):
  // Define defaults if not passed
  $defaults = [
    'relations' => true,
    'goals'     => true,
    'budget'    => true,
    'roles'     => false,
    'contacts'  => true,
    'documents' => true,
    'workflow'  => true,
  ];
  $flags = array_replace($defaults, $presentationFlags ?? []);
  ?>
  <div id="overlay-basics" class="overlay-hidden">
    <div class="overlay-content">
      <button class="close-overlay" aria-label="Close">✕</button>
      <h3>Vision Basics</h3>
      <form id="basicsForm">
        <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>">

        <label>Start date</label>
        <input type="date" name="start_date"
               value="<?= htmlspecialchars($vision['start_date'] ?? '') ?>">

        <label>End date</label>
        <input type="date" name="end_date"
               value="<?= htmlspecialchars($vision['end_date'] ?? '') ?>">

        <h4>Show on public view</h4>
        <?php foreach ($defaults as $section => $defaultOn): ?>
          <label class="show-flag">
            <input type="checkbox" name="show_<?= $section ?>"
                   value="1"
                   <?= !empty($flags[$section]) ? 'checked' : '' ?>>
            <?= ucfirst($section) ?>
          </label>
        <?php endforeach; ?>

        <div class="overlay-actions">
          <button type="submit" class="btn primary">Save</button>
          <button type="button" class="btn close-overlay">Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>
