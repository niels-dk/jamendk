<?php
// views/partials/overlay_basics.php
// Expects: $vision (id, start_date, end_date, slug), $presentationFlags (assoc)
// We keep your default flags and render the same “Show on public view” switches.

$defaults = [
  'relations'=>1,'goals'=>1,'budget'=>1,'roles'=>0,'contacts'=>1,'documents'=>1,'workflow'=>1
];
$flags = array_replace($defaults, $presentationFlags ?? []);

$visionId  = (int)($vision['id'] ?? 0);
$visionSlug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$startDate = (string)($vision['start_date'] ?? '');
$endDate   = (string)($vision['end_date']   ?? '');
?>

<div class="overlay-header">
  <h2>Vision Basics</h2>
  <!--button class="close-overlay" aria-label="Close" title="Close">×</button-->
</div>

<form id="basicsForm" class="overlay-form" action="/visions/update-basics" method="post" data-slug="<?= $visionSlug ?>">
  <input type="hidden" name="vision_id" value="<?= $visionId ?>">

  <label for="start-date">Start date</label>
  <input id="start-date" type="date" name="start_date" value="<?= htmlspecialchars($startDate, ENT_QUOTES) ?>">

  <label for="end-date">End date</label>
  <input id="end-date" type="date" name="end_date" value="<?= htmlspecialchars($endDate, ENT_QUOTES) ?>">

  <h4>Show on public view</h4>

  <?php foreach ($defaults as $section => $_): ?>
    <?php
      $id = 'flag_' . $section;
      $checked = !empty($flags[$section]) ? 'checked' : '';
      $label = ucfirst($section);
    ?>
    <div class="switch switch-row">
      <label class="switch-label" for="<?= $id ?>"><?= $label ?></label>
      <input id="<?= $id ?>" class="switch-input" type="checkbox" name="<?= $section ?>" <?= $checked ?>>
      <span class="knob" aria-hidden="true"></span>
    </div>
  <?php endforeach; ?>
</form>

<script>
(() => {
  const form   = document.getElementById('basicsForm');
  if (!form) return;

  const slug   = form.dataset.slug || '';
  const start  = form.querySelector('#start-date');
  const end    = form.querySelector('#end-date');

  // ——— Dates: auto-save to /visions/update-basics
  function saveDates() {
    const p = new URLSearchParams();
    p.set('vision_id', form.querySelector('[name="vision_id"]').value);
    p.set('start_date', start.value.trim());
    p.set('end_date',   end.value.trim());

    fetch(form.action, {
      method:'POST',
      headers:{ 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
      body: p.toString()
    }).catch(()=>{});
  }
  [start, end].forEach(el => el && el.addEventListener('change', saveDates));

  // ——— Section switches: auto-save per toggle to /api/visions/{slug}/basics
  // We send a tiny payload: flag=<section>&enabled=1|0
  function saveFlag(section, enabled) {
    if (!slug) return;
    const p = new URLSearchParams();
    p.set('flag', section);
    p.set('enabled', enabled ? '1' : '0');

    fetch(`/api/visions/${encodeURIComponent(slug)}/basics`, {
      method:'POST',
      headers:{ 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
      body: p.toString()
    }).catch(()=>{});
  }

  form.querySelectorAll('.switch-input').forEach(cb => {
    cb.addEventListener('change', () => {
      // name is the section key: relations/goals/budget/roles/contacts/documents/workflow
      saveFlag(cb.name, cb.checked);
    });
  });
})();
</script>
