<?php
// expects: $vision (optional), $kv (anchors array)
$isEdit    = isset($vision);
$titleText = $isEdit ? 'Edit Vision' : 'Create a Vision';
$action    = $isEdit ? '/visions/update' : '/visions/store';
?>
<h1><?= $titleText ?></h1>

<form action="<?= $action ?>" method="post" class="card">
  <?php if ($isEdit): ?>
    <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>">
  <?php endif; ?>

  <label>Title
    <input name="title" type="text" required placeholder="Vision title"
           value="<?= $isEdit ? htmlspecialchars($vision['title']) : '' ?>">
  </label>

  <label>Description</label>
  <input id="vision-desc" type="hidden" name="description"
         value="<?= $isEdit ? htmlspecialchars($vision['description'] ?? '') : '' ?>">
  <trix-editor input="vision-desc" class="trix-vision"></trix-editor>

  <div class="two-cols">
    <div>
      <label>Start date</label>
      <input type="date" name="start_date"
             value="<?= $isEdit ? htmlspecialchars($vision['start_date'] ?? '') : '' ?>">
    </div>
    <div>
      <label>End date</label>
      <input type="date" name="end_date"
             value="<?= $isEdit ? htmlspecialchars($vision['end_date'] ?? '') : '' ?>">
    </div>
  </div>

  <label style="display:flex;gap:.5rem;align-items:center">
    Anchors <span title="Quick, queryable tags like locations, brands, people, seasons/time. Helps search & dashboards."
          style="opacity:.7;cursor:help;">?</span>
  </label>

  <div class="anchors">
    <?php
      $rows = $kv ?? [];
      if (!$rows) $rows = [['key' => '', 'value' => '']];
      $i = 0;
      foreach ($rows as $row):
    ?>
    <div class="anchors-row">
      <select name="anchors[<?= $i ?>][key]" class="anchor-key">
        <option value="">Choose key…</option>
        <option <?= ($row['key'] ?? '') === 'locations' ? 'selected' : '' ?>>locations</option>
        <option <?= ($row['key'] ?? '') === 'brands'    ? 'selected' : '' ?>>brands</option>
        <option <?= ($row['key'] ?? '') === 'people'    ? 'selected' : '' ?>>people</option>
        <option <?= ($row['key'] ?? '') === 'seasons'   ? 'selected' : '' ?>>seasons</option>
        <option <?= ($row['key'] ?? '') === 'time'      ? 'selected' : '' ?>>time</option>
        <?php
        $key = $row['key'] ?? '';
        if ($key && !in_array($key, ['locations','brands','people','seasons','time'])):
          echo '<option value="'.htmlspecialchars($key).'" selected>'.htmlspecialchars($key).'</option>';
        endif;
        ?>
        <option value="__custom">Custom…</option>
      </select>
      <input name="anchors[<?= $i ?>][value]" class="anchor-value"
             value="<?= htmlspecialchars($row['value'] ?? '') ?>"
             placeholder="e.g. Copenhagen / Adidas / Alice / Winter / Q1">
      <button type="button" class="btn btn-icon remove-anchor" aria-label="Remove">✕</button>
    </div>
    <?php $i++; endforeach; ?>
    <button type="button" class="btn add-anchor">＋ Add</button>
  </div>

  <button class="btn primary"><?= $isEdit ? 'Save Changes' : 'Create Vision' ?></button>
  <?php if ($isEdit): ?>
    <a class="btn" href="/visions/<?= htmlspecialchars($vision['slug']) ?>" style="margin-left:.5rem">Cancel</a>
  <?php endif; ?>
</form>

<script>
(function(){
  const wrap = document.querySelector('.anchors');
  if (!wrap) return;
  let index = wrap.querySelectorAll('.anchors-row').length;

  wrap.addEventListener('click', e => {
    if (e.target.closest('.add-anchor')) {
      const row = wrap.querySelector('.anchors-row').cloneNode(true);
      row.querySelectorAll('input,select').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
        el.name = el.name.replace(/\\[\\d+\\]/, '[' + index + ']');
      });
      wrap.insertBefore(row, wrap.querySelector('.add-anchor'));
      index++;
    }
    if (e.target.closest('.remove-anchor')) {
      const rows = wrap.querySelectorAll('.anchors-row');
      if (rows.length > 1) e.target.closest('.anchors-row').remove();
    }
  });

  wrap.addEventListener('change', e => {
    const sel = e.target.closest('select.anchor-key');
    if (sel && sel.value === '__custom') {
      const v = prompt('Custom key name?');
      sel.value = '';
      if (v) {
        const opt = document.createElement('option');
        opt.textContent = v;
        opt.value = v;
        sel.insertBefore(opt, sel.querySelector('option[value=\"__custom\"]'));
        sel.value = v;
      }
    }
  });
})();
</script>
