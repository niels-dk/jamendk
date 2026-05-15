<?php
/**
 * Vision Relations overlay
 * Expects: $vision (id, slug, mood_id, show_mood_on_dashboard, show_mood_on_trip)
 *          $linkedMood (slug, title) or null
 */
$visionSlug   = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$currentMood  = $vision['mood_id'] ?? '';
$showDash     = (int)($vision['show_mood_on_dashboard'] ?? 0);
$showTrip     = (int)($vision['show_mood_on_trip']      ?? 0);
?>

<div class="overlay-header">
  <h2>Relations</h2>
</div>

<form id="relationsForm" class="overlay-form" data-slug="<?= $visionSlug ?>">
  <label for="mood-search">Mood Board</label>

  <div class="mood-picker">
    <div id="mood-chip" class="mood-chip" style="<?= $linkedMood ? '' : 'display:none' ?>">
      <span class="chip-label">
        <?php if ($linkedMood): ?>
          <strong><?= htmlspecialchars($linkedMood['title']) ?></strong>
          <span class="chip-id"><?= htmlspecialchars($linkedMood['slug']) ?></span>
        <?php endif; ?>
      </span>
      <button type="button" class="chip-remove" aria-label="Remove">×</button>
    </div>

    <input id="mood-search" type="text" placeholder="Search by name or paste ID…"
           autocomplete="off" style="<?= $linkedMood ? 'display:none' : '' ?>">
    <input type="hidden" name="mood_id" value="<?= htmlspecialchars($currentMood, ENT_QUOTES) ?>">
    <div class="mood-suggestions" hidden></div>
  </div>

  <h4>Visibility</h4>

  <label class="switch switch-row">
    <span class="switch-label">Show on Dashboard</span>
    <input class="switch-input" type="checkbox" name="show_mood_on_dashboard" <?= $showDash ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>

  <label class="switch switch-row">
    <span class="switch-label">Show on Trip layer</span>
    <input class="switch-input" type="checkbox" name="show_mood_on_trip" <?= $showTrip ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>
</form>

<style>
  .mood-picker { position: relative; margin-bottom: 1rem; }
  .mood-chip {
    display: inline-flex; align-items: center; gap: .5rem;
    background: #1f2533; border: 1px solid #2b3346;
    padding: .35rem .35rem .35rem .7rem; border-radius: 999px;
  }
  .mood-chip .chip-id { opacity: .55; font-size: .85em; margin-left: .35rem; font-family: monospace; }
  .mood-chip .chip-remove {
    background: transparent; border: 0; color: #aaa; font-size: 1.1rem;
    cursor: pointer; line-height: 1; padding: 0 .35rem;
  }
  .mood-chip .chip-remove:hover { color: #fff; }
  .mood-suggestions {
    position: absolute; left: 0; right: 0; top: 100%;
    background: #1a1d24; border: 1px solid #2b3346; border-radius: 8px;
    margin-top: 4px; max-height: 240px; overflow-y: auto; z-index: 5;
  }
  .mood-suggestions button {
    display: block; width: 100%; text-align: left;
    background: transparent; border: 0; color: #ddd;
    padding: .55rem .7rem; cursor: pointer;
  }
  .mood-suggestions button:hover { background: #2a2f3a; }
  .mood-suggestions .sug-id { opacity: .55; font-size: .85em; font-family: monospace; margin-left: .5rem; }
</style>

<script>
(() => {
  const form    = document.getElementById('relationsForm');
  if (!form) return;
  const slug    = form.dataset.slug;
  const search  = form.querySelector('#mood-search');
  const hidden  = form.querySelector('input[name="mood_id"]');
  const sug     = form.querySelector('.mood-suggestions');
  const chip    = form.querySelector('#mood-chip');
  const chipLbl = chip.querySelector('.chip-label');
  const remove  = chip.querySelector('.chip-remove');

  function save() {
    const p = new URLSearchParams();
    p.set('mood_id', hidden.value);
    p.set('show_mood_on_dashboard', form.querySelector('[name="show_mood_on_dashboard"]').checked ? '1' : '0');
    p.set('show_mood_on_trip',      form.querySelector('[name="show_mood_on_trip"]').checked ? '1' : '0');
    fetch(`/api/visions/${slug}/relations`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: p.toString()
    }).then(r => r.json()).then(j => {
      if (!j.success) console.error('Relations save failed:', j);
    }).catch(e => console.error('Relations save error:', e));
  }

  function showChip(title, id) {
    chipLbl.innerHTML = `<strong>${title}</strong> <span class="chip-id">${id}</span>`;
    chip.style.display = '';
    search.style.display = 'none';
    sug.hidden = true;
  }
  function clearChip() {
    chip.style.display = 'none';
    search.style.display = '';
    search.value = '';
    hidden.value = '';
  }

  let timer;
  search.addEventListener('input', () => {
    clearTimeout(timer);
    const q = search.value.trim();
    if (q.length < 2) { sug.hidden = true; sug.innerHTML = ''; return; }
    timer = setTimeout(async () => {
      try {
        const res  = await fetch(`/api/moods/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (Array.isArray(data) && data.length) {
          sug.innerHTML = data.map(m =>
            `<button type="button" data-id="${m.id}" data-title="${m.title.replace(/"/g,'&quot;')}">${m.title}<span class="sug-id">${m.id}</span></button>`
          ).join('');
          sug.hidden = false;
        } else { sug.hidden = true; sug.innerHTML = ''; }
      } catch { sug.hidden = true; }
    }, 200);
  });

  sug.addEventListener('click', e => {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;
    hidden.value = btn.dataset.id;
    showChip(btn.dataset.title, btn.dataset.id);
    save();
  });

  remove.addEventListener('click', () => {
    clearChip();
    save();
  });

  form.querySelectorAll('.switch-input').forEach(cb => {
    cb.addEventListener('change', save);
  });
})();
</script>
