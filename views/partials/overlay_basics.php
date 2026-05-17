<?php
// views/partials/overlay_basics.php
// Expects: $vision (id, start_date, end_date, slug, trip_enabled), $presentationFlags (assoc)

$defaults = [
  'relations'=>1,'goals'=>1,'budget'=>1,'roles'=>0,'contacts'=>1,'documents'=>1,'workflow'=>1
];
$flags = array_replace($defaults, $presentationFlags ?? []);

$visionId    = (int)($vision['id'] ?? 0);
$visionSlug  = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$startDate   = (string)($vision['start_date'] ?? '');
$endDate     = (string)($vision['end_date']   ?? '');
$tripEnabled = !empty($vision['trip_enabled']);
?>

<div class="overlay-header">
  <h2>Vision Basics</h2>
</div>

<form id="basicsForm" class="overlay-form" action="/visions/update-basics" method="post" data-slug="<?= $visionSlug ?>">
  <input type="hidden" name="vision_id" value="<?= $visionId ?>">

  <label for="start-date">Start date</label>
  <input id="start-date" type="date" name="start_date" value="<?= htmlspecialchars($startDate, ENT_QUOTES) ?>">

  <label for="end-date">End date</label>
  <input id="end-date" type="date" name="end_date" value="<?= htmlspecialchars($endDate, ENT_QUOTES) ?>">

  <h4 style="margin-top:1.2rem;">Trip publishing</h4>

  <label class="switch switch-row" title="Master switch — when off, the trip page is not available.">
    <span class="switch-label">
      <strong>Publish as Trip</strong>
      <span style="display:block;opacity:.6;font-size:.8em;margin-top:.1rem;">
        Master switch — when off, /trips/<?= $visionSlug ?> shows "not published".
      </span>
    </span>
    <input class="switch-input" type="checkbox" name="trip_enabled" <?= $tripEnabled ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>

  <h4 style="margin-top:1.2rem;">Show on Trip layer</h4>
  <p style="opacity:.6;font-size:.85em;margin:0 0 .6rem;">
    Choose which sections appear when this trip is published.
  </p>

  <?php foreach ($defaults as $section => $_): ?>
    <?php
      $id = 'flag_' . $section;
      $checked = !empty($flags[$section]) ? 'checked' : '';
      $label = ucfirst($section);
    ?>
    <label class="switch switch-row" style="opacity:<?= $tripEnabled ? '1' : '.45' ?>;">
      <span class="switch-label"><?= $label ?></span>
      <input class="switch-input" type="checkbox" name="<?= $section ?>" <?= $checked ?>>
      <span class="knob" aria-hidden="true"></span>
    </label>
  <?php endforeach; ?>
</form>

<script>
(() => {
  const form   = document.getElementById('basicsForm');
  if (!form) return;

  const slug   = form.dataset.slug || '';
  const start  = form.querySelector('#start-date');
  const end    = form.querySelector('#end-date');

  // ——— Dates: auto-save
  function saveDates() {
    const p = new URLSearchParams();
    p.set('vision_id', form.querySelector('[name="vision_id"]').value);
    p.set('start_date', start.value.trim());
    p.set('end_date',   end.value.trim());

    fetch('/api/visions/update-basics', {
      method:'POST',
      headers:{ 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
      body: p.toString()
    }).then(r => r.json()).then(j => {
      if (!j.success) console.error('Date save failed:', j);
    }).catch(e => console.error('Date save error:', e));
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

  // Visually dim section toggles when the master switch is off
  function refreshSectionDim() {
    const master = form.querySelector('[name="trip_enabled"]');
    const on = master ? master.checked : true;
    form.querySelectorAll('.switch.switch-row').forEach(row => {
      const cb = row.querySelector('input[type="checkbox"]');
      if (!cb || cb.name === 'trip_enabled') return;
      row.style.opacity = on ? '1' : '.45';
    });
  }

  form.querySelectorAll('.switch-input').forEach(cb => {
    cb.addEventListener('change', () => {
      // name is the flag key: trip_enabled OR a section
      // (relations/goals/budget/roles/contacts/documents/workflow)
      saveFlag(cb.name, cb.checked);
      if (cb.name === 'trip_enabled') refreshSectionDim();
    });
  });
})();
</script>
