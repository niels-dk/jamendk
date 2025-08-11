<?php
// views/vision_form.php
// expects: $vision (optional), $kv (anchors array), $presentationFlags (optional)

$isEdit    = isset($vision);
$titleText = $isEdit ? 'Edit Vision' : 'Create a Vision';
?>
<h1><?= htmlspecialchars($titleText, ENT_QUOTES) ?></h1>

<form id="visionForm" class="card">
  <?php if ($isEdit): ?>
    <input type="hidden" name="vision_id" value="<?= (int)($vision['id'] ?? 0) ?>">
    <input type="hidden" name="slug" value="<?= htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES) ?>">
  <?php endif; ?>

  <label>Title
    <input name="title" type="text" placeholder="Vision title"
           value="<?= $isEdit ? htmlspecialchars($vision['title'] ?? '', ENT_QUOTES) : '' ?>">
  </label>

  <label>Description</label>
  <input id="vision-desc" type="hidden" name="description"
         value="<?= $isEdit ? htmlspecialchars($vision['description'] ?? '', ENT_QUOTES) : '' ?>">
  <trix-editor input="vision-desc" class="trix-vision"></trix-editor>

  <?php if (!empty($boardType) && $boardType === 'vision'): ?>
    <?php include __DIR__ . '/partials/overlay_basics.php'; ?>
    <?php include __DIR__ . '/partials/overlay_relations.php'; ?>
    <?php include __DIR__ . '/partials/overlay_goals.php'; ?>
    <?php include __DIR__ . '/partials/overlay_budget.php'; ?>
    <?php include __DIR__ . '/partials/overlay_roles.php'; ?>
    <?php include __DIR__ . '/partials/overlay_contacts.php'; ?>
    <?php include __DIR__ . '/partials/overlay_documents.php'; ?>
    <?php include __DIR__ . '/partials/overlay_workflow.php'; ?>
  <?php endif; ?>

  <label style="display:flex;gap:.5rem;align-items:center">
    Anchors
    <span title="Quick, queryable tags like locations, brands, people, seasons/time. Helps search & dashboards."
          style="opacity:.7;cursor:help;">?</span>
  </label>

  <div class="anchors">
    <?php
      // $kv is array of ['key'=>..., 'value'=>...]
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
            <option value="<?= htmlspecialchars($rowKey, ENT_QUOTES) ?>" selected>
              <?= htmlspecialchars($rowKey, ENT_QUOTES) ?>
            </option>
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

<script>
(function(){
  const wrap = document.querySelector('.anchors');
  if (!wrap) return;

  function reindexAnchors() {
    const rows = wrap.querySelectorAll('.anchors-row');
    rows.forEach((row, idx) => {
      const keyField = row.querySelector('.anchor-key');
      const valField = row.querySelector('.anchor-value');
      if (keyField) keyField.setAttribute('name', `anchors[${idx}][key]`);
      if (valField) valField.setAttribute('name', `anchors[${idx}][value]`);
    });
  }

  // Add/remove
  wrap.addEventListener('click', (e) => {
    if (e.target.closest('.add-anchor')) {
      const template = wrap.querySelector('.anchors-row');
      if (!template) return;
      const clone = template.cloneNode(true);
      clone.querySelectorAll('input,select').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
      });
      wrap.insertBefore(clone, wrap.querySelector('.add-anchor'));
      reindexAnchors();
    }
    if (e.target.closest('.remove-anchor')) {
      const row = e.target.closest('.anchors-row');
      const rows = wrap.querySelectorAll('.anchors-row');
      if (rows.length > 1 && row) {
        row.remove();
        reindexAnchors();
      }
    }
  });

  // Custom key inline editor
  wrap.addEventListener('change', (e) => {
    const select = e.target.closest('select.anchor-key');
    if (!select) return;
    if (select.value === '__custom') {
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'anchor-key';
      input.placeholder = 'Custom key';
      input.style.width = select.offsetWidth + 'px';
      select.replaceWith(input);
      input.focus();

      const finish = () => {
        const key = (input.value || '').trim();
        const newSelect = document.createElement('select');
        newSelect.className = 'anchor-key';
        newSelect.innerHTML = `
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
          const customOpt = newSelect.querySelector('option[value="__custom"]');
          newSelect.insertBefore(opt, customOpt);
          newSelect.value = key;
        } else {
          newSelect.value = '';
        }
        input.replaceWith(newSelect);
        reindexAnchors();
      };

      input.addEventListener('blur', finish);
      input.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') { ev.preventDefault(); finish(); }
      });
    }
  });

  reindexAnchors();
})();
</script>

<script src="/public/js/vision-edit.js?v=1"></script>
