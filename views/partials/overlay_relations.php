<?php
/**
 * Vision Relations overlay
 *
 * Link this vision to a single Mood board.  A vision may only have
 * one mood relation at a time; a mood may be linked to many visions.
 * You can also control whether the relation appears on the dashboard
 * and trip layer.
 *
 * Expected variables:
 *   $vision array  Vision row (including mood_id and its show flags)
 */

$currentMoodId  = $vision['mood_id'] ?? null;
$showMoodDash   = (int)($vision['show_mood_on_dashboard'] ?? 0);
$showMoodTrip   = (int)($vision['show_mood_on_trip']      ?? 0);

?>

<div class="overlay-header">
  <h2>Relations</h2>
</div>

<form id="relationsForm" class="overlay-form" method="post" action="/api/visions/<?= htmlspecialchars($vision['slug'] ?? '') ?>/relations">
  <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>" />

  <div class="form-group">
    <label for="mood-search">Mood Board</label>
    <input id="mood-search" type="text" placeholder="Search or paste mood ID…" autocomplete="off" />
    <input type="hidden" name="mood_id" value="<?= htmlspecialchars($currentMoodId) ?>" />
    <div class="mood-suggestions" style="display:none"></div>
    <?php if ($currentMoodId): ?>
      <p class="current-mood">Linked to mood ID <strong><?= htmlspecialchars($currentMoodId) ?></strong>. <button class="remove-mood" type="button">Remove</button></p>
    <?php endif; ?>
  </div>

  <fieldset>
    <legend>Visibility</legend>
    <label class="checkbox"><input type="checkbox" name="show_mood_on_dashboard" value="1" <?= $showMoodDash ? 'checked' : '' ?> /> Show on Dashboard</label>
    <label class="checkbox"><input type="checkbox" name="show_mood_on_trip"      value="1" <?= $showMoodTrip ? 'checked' : '' ?> /> Show on Trip layer</label>
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>

<!-- Minimal JS to fetch mood suggestions and handle removal.  Assumes
     fetch() is supported.  If no results, suggestions remain hidden. -->
<script>
(() => {
  const search = document.getElementById('mood-search');
  const hidden = document.querySelector('input[name="mood_id"]');
  const suggestions = document.querySelector('.mood-suggestions');
  const removeBtn = document.querySelector('.remove-mood');

  if (search) {
    search.addEventListener('input', async () => {
      const q = search.value.trim();
      if (q.length < 2) { suggestions.style.display = 'none'; return; }
      try {
        const res  = await fetch(`/api/moods/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (Array.isArray(data) && data.length) {
          suggestions.innerHTML = data.map(item => `<button type="button" data-id="${item.id}">${item.title}</button>`).join('');
          suggestions.style.display = 'block';
        } else suggestions.style.display = 'none';
      } catch(err) { suggestions.style.display = 'none'; }
    });
  }
  if (suggestions) {
    suggestions.addEventListener('click', e => {
      const btn = e.target.closest('button[data-id]');
      if (!btn) return;
      hidden.value = btn.dataset.id;
      search.value = btn.textContent;
      suggestions.style.display = 'none';
    });
  }
  if (removeBtn) {
    removeBtn.addEventListener('click', () => {
      hidden.value = '';
    });
  }
})();
</script>