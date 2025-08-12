<?php
// views/vision_form.php
$isEdit    = isset($vision);
$titleText = $isEdit ? 'Edit Vision' : 'Create a Vision';
?>
<link rel="stylesheet" href="/public/css/overlay.css?v=1">

<h1><?= htmlspecialchars($titleText, ENT_QUOTES) ?></h1>

<form id="visionForm" class="card">
  <?php if ($isEdit): ?>
    <input type="hidden" name="vision_id" value="<?= (int)($vision['id'] ?? 0) ?>">
    <input type="hidden" name="slug"     value="<?= htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES) ?>">
  <?php endif; ?> 

  <label>Title
    <input name="title" type="text" placeholder="Vision title"
           value="<?= $isEdit ? htmlspecialchars($vision['title'] ?? '', ENT_QUOTES) : '' ?>">
  </label>

  <label>Description</label>
  <input id="vision-desc" type="hidden" name="description"
         value="<?= $isEdit ? htmlspecialchars($vision['description'] ?? '', ENT_QUOTES) : '' ?>">
  <trix-editor input="vision-desc" class="trix-vision"></trix-editor>

  <!-- Sidebar nav items render in layout.php; anchor section stays here -->
  <label style="display:flex;gap:.5rem;align-items:center">
    Anchors
    <span title="Quick, queryable tags like locations, brands, people, seasons/time. Helps search & dashboards."
          style="opacity:.7;cursor:help;">?</span>
  </label>

  <div class="anchors">
    <?php
      $rows = $kv ?? [];
      if (!$rows) $rows = [['key' => '', 'value' => '']];
      $i = 0;
      foreach ($rows as $row):
        $rowKey = (string)($row['key'] ?? '');
        $rowVal = (string)($row['value'] ?? '');
    ?>
      <div class="anchors-row" style="display:flex;align-items:center;gap:.5rem">
        <select class="anchor-key" name="anchors[<?= $i ?>][key]">
          <option value="">Choose…</option>
          <option <?= $rowKey === 'locations' ? 'selected' : '' ?>>locations</option>
          <option <?= $rowKey === 'brands'    ? 'selected' : '' ?>>brands</option>
          <option <?= $rowKey === 'people'    ? 'selected' : '' ?>>people</option>
          <option <?= $rowKey === 'seasons'   ? 'selected' : '' ?>>seasons</option>
          <option <?= $rowKey === 'time'      ? 'selected' : '' ?>>time</option>
          <?php if ($rowKey && !in_array($rowKey, ['locations','brands','people','seasons','time'], true)): ?>
            <option value="<?= htmlspecialchars($rowKey, ENT_QUOTES) ?>" selected><?= htmlspecialchars($rowKey, ENT_QUOTES) ?></option>
          <?php endif; ?>
          <option value="__custom">Custom…</option>
        </select>
        <input class="anchor-value" name="anchors[<?= $i ?>][value]"
               value="<?= htmlspecialchars($rowVal, ENT_QUOTES) ?>"
               placeholder="e.g. Copenhagen / Adidas / Alice / Winter / Q1">
        <button type="button" class="btn btn-icon remove-anchor" aria-label="Remove" style="padding:0 .5rem;">✕</button>
      </div>
    <?php $i++; endforeach; ?>
    <button type="button" class="btn add-anchor">＋ Add</button>
  </div>

  <div class="btn-group" style="margin-top:1rem">
    <button class="btn primary">Save Vision</button>
    <button type="button" class="btn split" id="visionMoreBtn">▾</button>
    <div class="split-menu" id="visionMoreMenu" style="display:none">
      <button type="button" data-go="stay">Save &amp; stay</button>
      <button type="button" data-go="view">Save &amp; close</button>
      <button type="button" data-go="dash">Save &amp; dashboard</button>
    </div>
  </div>
</form>

<!-- Single overlay shell (hidden by default) -->
<div id="overlay-shell" class="overlay-hidden">
  <div class="overlay-backdrop"></div>
  <div class="overlay-panel">
    <button class="close-overlay" aria-label="Close">✕</button>
    <div id="overlay-content"></div>
  </div>
</div>

<!-- anchor row management -->
<script>
(function(){
  const wrap = document.querySelector('.anchors');
  if (!wrap) return;
  function reindex() {
    wrap.querySelectorAll('.anchors-row').forEach((row, idx) => {
      const k = row.querySelector('.anchor-key');
      const v = row.querySelector('.anchor-value');
      if (k) k.setAttribute('name', `anchors[${idx}][key]`);
      if (v) v.setAttribute('name', `anchors[${idx}][value]`);
    });
  }
  wrap.addEventListener('click', e => {
    if (e.target.closest('.add-anchor')) {
      const tmpl = wrap.querySelector('.anchors-row');
      const clone = tmpl.cloneNode(true);
      clone.querySelectorAll('input,select').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
      });
      wrap.insertBefore(clone, wrap.querySelector('.add-anchor'));
      reindex();
    }
    if (e.target.closest('.remove-anchor')) {
      const rows = wrap.querySelectorAll('.anchors-row');
      if (rows.length > 1) {
        e.target.closest('.anchors-row').remove();
        reindex();
      }
    }
  });
  wrap.addEventListener('change', e => {
    const sel = e.target.closest('select.anchor-key');
    if (sel && sel.value === '__custom') {
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'anchor-key';
      input.placeholder = 'Custom key';
      input.style.width = sel.offsetWidth + 'px';
      sel.replaceWith(input);
      input.focus();
      const finish = () => {
        const key = input.value.trim();
        const newSel = document.createElement('select');
        newSel.className = 'anchor-key';
        newSel.innerHTML = `
          <option value="">Choose…</option>
          <option>locations</option>
          <option>brands</option>
          <option>people</option>
          <option>seasons</option>
          <option>time</option>
          <option value="__custom">Custom…</option>`;
        if (key) {
          const opt = document.createElement('option');
          opt.value = key;
          opt.textContent = key;
          const customOpt = newSel.querySelector('option[value="__custom"]');
          newSel.insertBefore(opt, customOpt);
          newSel.value = key;
        } else newSel.value = '';
        input.replaceWith(newSel);
        reindex();
      };
      input.addEventListener('blur', finish);
      input.addEventListener('keydown', ev => {
        if (ev.key === 'Enter') { ev.preventDefault(); finish(); }
      });
    }
  });
  reindex();
})();
</script>

<!-- main and overlay scripts -->
<script src="/public/js/vision-edit.js?v=1"></script>
<script src="/public/js/vision-overlays.js?v=1"></script>