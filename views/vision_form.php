<?php
// views/vision_form.php
$isEdit    = isset($vision);
$titleText = $isEdit ? 'Edit Vision' : 'Create a Vision';
?>
<link rel="stylesheet" href="/public/css/overlay.css?v=1">
<link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
<script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>

<style>
  /* Ensure hidden rows really hide even if inline display exists */
  .anchor-list [hidden] { display: none !important; }
</style>

<h1><?= htmlspecialchars($titleText, ENT_QUOTES) ?></h1>

<form id="visionForm" class="card">
  <?php if ($isEdit): ?>
    <input type="hidden" name="vision_id" value="<?= (int)($vision['id'] ?? 0) ?>">
    <input type="hidden" name="slug"     value="<?= htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES) ?>">
  <?php endif; ?> 

  <label>Vision Name
    <input name="title" type="text" placeholder="Vision title"
           value="<?= $isEdit ? htmlspecialchars($vision['title'] ?? '', ENT_QUOTES) : '' ?>">
  </label> 

  <label>Project Scope</label>
  <input id="vision-desc" type="hidden" name="description"
         value="<?= $isEdit ? htmlspecialchars($vision['description'] ?? '', ENT_QUOTES) : '' ?>">
  <trix-editor input="vision-desc" class="trix-vision"></trix-editor>

  <!-- Anchors header + toggle -->
  <label class="anchors-header" style="display:flex;gap:.5rem;align-items:center">
    Anchors 
    <span title="Quick, queryable tags like locations, brands, people, seasons/time. Helps search &amp; dashboards."
          style="opacity:.7;cursor:help;">?</span>
    <!-- Toggle appears only when there are >3 rows.  Initial text set via JS -->
    <button type="button"
            class="anchor-toggle"
            aria-expanded="false"
            aria-controls="anchor-list"
            style="background:none;border:0;color:var(--accent);cursor:pointer;font-size:0.85rem">
    </button>
  </label>

  <div class="anchors">
	  <div class="anchor-list" id="anchor-list" style="display:flex;flex-direction:column;gap:.5rem;">
		<?php
		  $rows = $kv ?? [];
		  if (!$rows) $rows = [['key' => '', 'value' => '']];
		  $i = 0;
		  foreach ($rows as $row):
			$rowKey = (string)($row['key'] ?? '');
			$rowVal = (string)($row['value'] ?? '');
		?>
		  <div class="anchors-row" style="display:flex;align-items:center;gap:.5rem;width:100%;">
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
			<input class="anchor-value"
				   name="anchors[<?= $i ?>][value]"
				   value="<?= htmlspecialchars($rowVal, ENT_QUOTES) ?>"
				   placeholder="e.g. Copenhagen / Adidas / Alice / Winter / Q1">
			<button type="button"
					class="btn btn-icon remove-anchor"
					aria-label="Remove"
					style="padding:0 .5rem;">✕</button>
		  </div>
		<?php $i++; endforeach; ?>
		<!-- Keep the Add button at the end of the flex column -->
		<button type="button" class="btn add-anchor">＋ Add</button>
	  </div>
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

<!-- anchor row management (+ collapse logic) -->
<script>
(function() {
  const wrap   = document.querySelector('.anchors');
  if (!wrap || wrap.dataset.enhanced === '1') return;
  wrap.dataset.enhanced = '1';

  const list      = wrap.querySelector('.anchor-list') || wrap;
  const addBtn    = list.querySelector('.add-anchor');
  // Scope the toggle to the header immediately above this anchors block
  const header    = wrap.previousElementSibling?.classList?.contains('anchors-header')
                    ? wrap.previousElementSibling
                    : document.querySelector('.anchors-header');
  const toggleBtn = header ? header.querySelector('.anchor-toggle') : null;

  let expanded = true;

  function getRows() {
    return Array.from(list.querySelectorAll('.anchors-row'));
  }

  function reindex() {
    getRows().forEach((row, idx) => {
      const k = row.querySelector('.anchor-key');
      const v = row.querySelector('.anchor-value');
      if (k) k.setAttribute('name', `anchors[${idx}][key]`);
      if (v) v.setAttribute('name', `anchors[${idx}][value]`);
    });
  }

  function updateToggle() {
    if (!toggleBtn) return;
    const total = getRows().length;
    if (total <= 3) {
      toggleBtn.style.display = 'none';
    } else {
      toggleBtn.style.display = 'inline';
      toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      toggleBtn.textContent = expanded
        ? `Hide ${total}`
        : `Show all ${total}`;
    }
  }

  function updateRowsVisibility() {
	  const rows  = getRows();
	  const total = rows.length;

	  // Rows
	  rows.forEach((row) => {
		const visible = !(total > 3 && !expanded); // collapsed + >3 → hide all rows
		if (visible) {
		  row.removeAttribute('hidden');
		  row.setAttribute('aria-hidden', 'false');
		  row.querySelectorAll('input,select,button').forEach(el => el.tabIndex = 0);
		} else {
		  row.hidden = true;
		  row.setAttribute('aria-hidden', 'true');
		  row.querySelectorAll('input,select,button').forEach(el => el.tabIndex = -1);
		}
	  });

	  // "+ Add" follows the same visibility rule as rows
	  if (addBtn) {
		const showAdd = !(total > 3 && !expanded);
		if (showAdd) {
		  addBtn.hidden = false;
		  addBtn.setAttribute('aria-hidden', 'false');
		  addBtn.tabIndex = 0;
		} else {
		  addBtn.hidden = true;
		  addBtn.setAttribute('aria-hidden', 'true');
		  addBtn.tabIndex = -1;
		}
	  }
	}

  function updateView() {
    updateToggle();
    updateRowsVisibility();
  }

  // Initial state: collapse when >3 rows, otherwise expanded
  expanded = getRows().length <= 3;
  updateView();

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      const total = getRows().length;
      if (total <= 3) return;
      expanded = !expanded;
      updateView();
    });
    toggleBtn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleBtn.click();
      }
    });
  }

 	// Add/remove rows
	list.addEventListener('click', e => {
	  if (e.target.closest('.add-anchor')) {
		const tmpl  = list.querySelector('.anchors-row');
		const clone = tmpl.cloneNode(true);
		clone.querySelectorAll('input,select').forEach(el => {
		  if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
		});
		list.insertBefore(clone, addBtn);
		reindex();

		// Keep current expanded/collapsed state — do NOT auto-collapse on 3→4
		updateView();

		// Optional: drop focus into the new value field
		const val = clone.querySelector('.anchor-value');
		if (val) val.focus();
	  }

	  if (e.target.closest('.remove-anchor')) {
		const rows = getRows();
		if (rows.length > 1) {
		  e.target.closest('.anchors-row').remove();
		  reindex();
		  const total = getRows().length;
		  if (total <= 3) expanded = true; // back to "always show"
		  updateView();
		}
	  }
	});


  // Custom key inline morphing
  list.addEventListener('change', e => {
    const sel = e.target.closest('select.anchor-key');
    if (sel && sel.value === '__custom') {
      const input = document.createElement('input');
      input.type        = 'text';
      input.className   = 'anchor-key';
      input.placeholder = 'Custom key';
      input.style.width = sel.offsetWidth + 'px';
      sel.replaceWith(input);
      input.focus();
      const finish = () => {
        const key    = input.value.trim();
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
          opt.value = key; opt.textContent = key;
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
})();
</script>


<!-- main and overlay scripts -->
<script src="/public/js/vision-edit.js?v=1"></script>
<script src="/public/js/vision-overlays.js?v=1"></script>
