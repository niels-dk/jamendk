<?php $isEdit = isset($vision); ?>

<div class="max-w-3xl mx-auto p-6 bg-gray-900 rounded-xl shadow-md border border-gray-700 text-white">
  <h1 class="text-2xl font-bold mb-6">
    <?= $isEdit ? 'Edit Vision' : 'Create a Vision' ?>
  </h1>

  <form id="visionForm" method="post" action="<?= $isEdit ? '/visions/update' : '/visions/store' ?>" class="space-y-6">

    <?php if ($isEdit): ?>
      <input type="hidden" name="vision_id" value="<?= $vision['id'] ?>">
      <input type="hidden" name="slug" value="<?= $vision['slug'] ?>">
    <?php endif; ?>

    <!-- Title -->
    <div>
      <label class="block text-sm font-semibold mb-1">Title</label>
      <input name="title" type="text" required
             class="w-full rounded-md bg-gray-800 border border-gray-700 text-white p-2"
             value="<?= $isEdit ? htmlspecialchars($vision['title']) : '' ?>">
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm font-semibold mb-1">Description</label>
      <textarea id="desc" name="description" style="display:none"><?= $isEdit ? $vision['description'] : '' ?></textarea>
      <trix-editor input="desc" class="trix-content bg-gray-800 p-4 rounded-md border border-gray-700 min-h-[240px] text-white"></trix-editor>
    </div>

    <!-- Dates -->
    <div class="flex gap-4">
      <div class="flex-1">
        <label class="block text-sm font-semibold mb-1">Start Date</label>
        <input name="start_date" type="date"
               class="w-full rounded-md bg-gray-800 border border-gray-700 text-white p-2"
               value="<?= $isEdit ? $vision['start_date'] : '' ?>">
      </div>
      <div class="flex-1">
        <label class="block text-sm font-semibold mb-1">End Date</label>
        <input name="end_date" type="date"
               class="w-full rounded-md bg-gray-800 border border-gray-700 text-white p-2"
               value="<?= $isEdit ? $vision['end_date'] : '' ?>">
      </div>
    </div>

    <!-- Fixed Anchors -->
    <div class="space-y-4">
      <h3 class="text-lg font-semibold">Anchors</h3>

      <?php
      function repeatInputs($name, $list) {
        if (!$list) $list = [''];
        foreach ($list as $i => $val) {
          $plus = $i === 0 ? '＋' : '✖';
          echo '<div class="flex gap-2 mb-2">
                  <input name="'.$name.'[]" value="'.htmlspecialchars($val).'" 
                         class="w-full rounded-md bg-gray-800 border border-gray-700 text-white p-2">
                  <button type="button" class="add bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">'.$plus.'</button>
                </div>';
        }
      }
      ?>

      <fieldset class="border border-gray-700 p-4 rounded">
        <legend class="text-blue-400 font-semibold">Locations</legend>
        <?php repeatInputs('location', $anchors['locations'] ?? []); ?>
      </fieldset>

      <fieldset class="border border-gray-700 p-4 rounded">
        <legend class="text-blue-400 font-semibold">Brands</legend>
        <?php repeatInputs('brand', $anchors['brands'] ?? []); ?>
      </fieldset>

      <fieldset class="border border-gray-700 p-4 rounded">
        <legend class="text-blue-400 font-semibold">People</legend>
        <?php repeatInputs('person', $anchors['people'] ?? []); ?>
      </fieldset>

      <fieldset class="border border-gray-700 p-4 rounded">
        <legend class="text-blue-400 font-semibold">Seasons / Time</legend>
        <?php repeatInputs('season', $anchors['seasons'] ?? []); ?>
      </fieldset>
    </div>

    <!-- Custom Anchors -->
    <div class="mt-6">
      <h3 class="text-lg font-semibold text-purple-400 mb-2">Custom Tags</h3>
      <div id="customAnchors">
        <?php
        $customKeys = $customAnchors['key'] ?? [''];
        $customVals = $customAnchors['value'] ?? [''];
        $count = max(count($customKeys), count($customVals));
        for ($i = 0; $i < $count; $i++):
        ?>
          <div class="flex gap-2 mb-2">
            <input name="custom_key[]" value="<?= htmlspecialchars($customKeys[$i] ?? '') ?>"
                   class="w-1/3 rounded-md bg-gray-800 border border-gray-700 text-white p-2"
                   placeholder="Key">
            <input name="custom_value[]" value="<?= htmlspecialchars($customVals[$i] ?? '') ?>"
                   class="flex-1 rounded-md bg-gray-800 border border-gray-700 text-white p-2"
                   placeholder="Value">
            <button type="button" class="remove bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">✖</button>
          </div>
        <?php endfor; ?>
      </div>
      <button type="button" id="addCustom" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded mt-2">
        ＋ Add Custom Tag
      </button>
    </div>

    <!-- Submit -->
    <div class="mt-6">
      <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
        <?= $isEdit ? 'Update Vision' : 'Create Vision' ?>
      </button>
    </div>
  </form>
</div>

<script>
  document.getElementById('addCustom').addEventListener('click', () => {
    const block = document.createElement('div');
    block.className = "flex gap-2 mb-2";
    block.innerHTML = `
      <input name="custom_key[]" placeholder="Key"
             class="w-1/3 rounded-md bg-gray-800 border border-gray-700 text-white p-2">
      <input name="custom_value[]" placeholder="Value"
             class="flex-1 rounded-md bg-gray-800 border border-gray-700 text-white p-2">
      <button type="button" class="remove bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">✖</button>
    `;
    document.getElementById('customAnchors').appendChild(block);
    block.querySelector('.remove').addEventListener('click', () => block.remove());
  });

  document.querySelectorAll('.remove').forEach(btn =>
    btn.addEventListener('click', () => btn.parentElement.remove()));
</script>
