<?php
// expects: for edit -> $vision (array), $kv (array of ['key'=>..,'value'=>..])
$isEdit = isset($vision);
?>
<h1><?= $isEdit ? 'Edit Vision' : 'Create a Vision' ?></h1>

<form action="<?= $isEdit ? '/visions/update' : '/visions/store' ?>" method="post" class="card">
  <?php if ($isEdit): ?>
    <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>">
  <?php endif; ?>

  <label>Title<br>
    <input name="title" type="text" required style="width:100%"
           value="<?= $isEdit ? htmlspecialchars($vision['title']) : '' ?>">
  </label>

  <br>

  <label>Description</label>
  <textarea id="desc" name="description" style="display:none"><?=
    $isEdit ? ($vision['description'] ?? '') : ''
  ?></textarea>

  <trix-toolbar id="trix-toolbar-custom" class="custom-trix-toolbar"></trix-toolbar>
  <trix-editor input="desc" toolbar="trix-toolbar-custom" class="trix-content" style="min-height:240px"></trix-editor>

  <hr style="margin:1rem 0; opacity:.2">

  <h3>Anchors (Key / Value)</h3>
  <div id="kv-list">
    <?php
      $rows = $kv ?? [];
      if (!$rows) $rows = [['key'=>'', 'value'=>'']];
      foreach ($rows as $i => $row):
    ?>
      <div class="repeat" style="display:flex; gap:.5rem; margin:.35rem 0;">
        <input name="vkey[]" placeholder="Key"   value="<?= htmlspecialchars($row['key'] ?? '') ?>"  style="flex:0 0 220px">
        <input name="vval[]" placeholder="Value" value="<?= htmlspecialchars($row['value'] ?? '') ?>" style="flex:1 1 auto">
        <button type="button" class="btn" onclick="removeKV(this)" aria-label="Remove">✖</button>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn" onclick="addKV()">＋ Add</button>

  <br><br>
  <div class="btn-group">
    <button class="btn primary"><?= $isEdit ? 'Save Changes' : 'Create Vision' ?></button>
    <?php if ($isEdit): ?>
      <a class="btn" href="/visions/<?= htmlspecialchars($vision['slug']) ?>" style="margin-left:.5rem">Cancel</a>
    <?php endif; ?>
  </div>
</form>

<script>
function addKV(){
  const row = document.createElement('div');
  row.className = 'repeat';
  row.style = 'display:flex; gap:.5rem; margin:.35rem 0;';
  row.innerHTML = `
    <input name="vkey[]" placeholder="Key" style="flex:0 0 220px">
    <input name="vval[]" placeholder="Value" style="flex:1 1 auto">
    <button type="button" class="btn" onclick="removeKV(this)" aria-label="Remove">✖</button>
  `;
  document.getElementById('kv-list').appendChild(row);
}
function removeKV(btn){
  const row = btn.closest('.repeat');
  if (row) row.remove();
}
</script>
