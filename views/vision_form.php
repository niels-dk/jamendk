<?php
    // expects: $vision (optional), $kv (anchors array)
    $isEdit    = isset($vision);
    $titleText = $isEdit ? 'Edit Vision' : 'Create a Vision';
    // We no longer use a server-side action; saving is handled via AJAX in vision-edit.js
    ?>
    <h1><?= $titleText ?></h1>

    <form id="visionForm" class="card">
      <?php if ($isEdit): ?>
        <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>">
        <input type="hidden" name="slug"     value="<?= htmlspecialchars($vision['slug']) ?>">
      <?php endif; ?>

      <label>Title
        <input name="title" type="text" placeholder="Vision title"
               value="<?= $isEdit ? htmlspecialchars($vision['title']) : '' ?>">
      </label>

      <label>Description</label>
      <input id="vision-desc" type="hidden" name="description"
             value="<?= $isEdit ? htmlspecialchars($vision['description'] ?? '') : '' ?>">
      <trix-editor input="vision-desc" class="trix-vision"></trix-editor>

        <?php if (!empty($boardType) && $boardType === 'vision'): ?>
          <?php include __DIR__.'/partials/overlay_basics.php'; ?>
          <?php include __DIR__.'/partials/overlay_relations.php'; ?>
          <?php include __DIR__.'/partials/overlay_goals.php'; ?>
          <?php include __DIR__.'/partials/overlay_budget.php'; ?>
          <?php include __DIR__.'/partials/overlay_roles.php'; ?>
          <?php include __DIR__.'/partials/overlay_contacts.php'; ?>
          <?php include __DIR__.'/partials/overlay_documents.php'; ?>
          <?php include __DIR__.'/partials/overlay_workflow.php'; ?>
        <?php endif; ?>

		<!-- Anchors heading -->
		<label style="display:flex;gap:.5rem;align-items:center">
		  Anchors <span title="Quick, queryable tags like locations, brands, people, seasons/time. Helps search & dashboards."
				style="opacity:.7;cursor:help;">?</span>
		</label>

      <div class="anchors">
        <?php
          // anchor rows: array of ['key'=>..., 'value'=>...]
          $rows = $kv ?? [];
          if (!$rows) $rows = [['key' => '', 'value' => '']];
          $i = 0;
          foreach ($rows as $row):
        ?>
        <div class="anchors-row">
          <select class="anchor-key">
            <option value="">Choose…</option>
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
          <input class="anchor-value"
                 value="<?= htmlspecialchars($row['value'] ?? '') ?>"
                 placeholder="e.g. Copenhagen / Adidas / Alice / Winter / Q1">
          <button type="button" class="btn btn-icon remove-anchor" aria-label="Remove">✕</button>
        </div>
        <?php $i++; endforeach; ?>
        <button type="button" class="btn add-anchor">＋ Add</button>
      </div>

      <div class="btn-group" style="margin-top:1rem">
        <button class="btn primary">Save Vision</button>
        <button type="button" class="btn split" id="visionMoreBtn">▾</button>
        <div class="split-menu" id="visionMoreMenu">
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
      let index = wrap.querySelectorAll('.anchors-row').length;

      // Add/remove rows
      wrap.addEventListener('click', e => {
        if (e.target.closest('.add-anchor')) {
          const template = wrap.querySelector('.anchors-row');
          const clone = template.cloneNode(true);
          clone.querySelectorAll('input,select').forEach(el => {
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
          });
          wrap.insertBefore(clone, wrap.querySelector('.add-anchor'));
          index++;
        }
        if (e.target.closest('.remove-anchor')) {
          const rows = wrap.querySelectorAll('.anchors-row');
          if (rows.length > 1) e.target.closest('.anchors-row').remove();
        }
      });

      // Custom key morphing
      wrap.addEventListener('change', e => {
        const select = e.target.closest('select.anchor-key');
        if (select && select.value === '__custom') {
          const row = select.closest('.anchors-row');
          const input = document.createElement('input');
          input.type = 'text';
          input.className = 'anchor-key';
          input.placeholder = 'Custom key';
          input.style.width = select.offsetWidth + 'px';
          // replace select with input
          select.replaceWith(input);
          input.focus();

          // commit function: replace input with a new select
          const finish = () => {
            const key = input.value.trim();
            const newSelect = document.createElement('select');
            newSelect.className = 'anchor-key';
            // populate default options
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
              // insert before "Custom…"
              const customOpt = newSelect.querySelector('option[value="__custom"]');
              newSelect.insertBefore(opt, customOpt);
              newSelect.value = key;
            } else {
              newSelect.value = '';
            }
            input.replaceWith(newSelect);
          };

          input.addEventListener('blur', finish);
          input.addEventListener('keydown', ev => {
            if (ev.key === 'Enter') {
              ev.preventDefault();
              finish();
            }
          });
        }
      });
    })();
    </script>
    <script src="/public/js/vision-edit.js?v=1"></script>
