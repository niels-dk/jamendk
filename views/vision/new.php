<?php
$pageTitle = 'Create Vision Board';
$type = 'vision';

// Capture form HTML
ob_start();
?>

<h1 class="text-2xl font-bold mb-4">Create a Vision Board</h1>

<form action="/vision/store" method="POST" class="space-y-4">

  <!-- Title -->
  <label class="block">
    <span class="text-gray-300">Title</span>
    <input name="title" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded" />
  </label>

  <!-- Description -->
  <label class="block">
    <span class="text-gray-300">Description</span>
    <textarea name="description" rows="5" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded"></textarea>
  </label>

  <!-- Custom Anchors -->
  <div id="custom-anchors" class="space-y-2">
    <h3 class="font-semibold text-lg">🔖 Custom Anchors</h3>
    <div class="flex space-x-2">
      <input name="custom_keys[]" placeholder="Key" class="w-1/2 p-2 bg-gray-700 rounded">
      <input name="custom_values[]" placeholder="Value" class="w-1/2 p-2 bg-gray-700 rounded">
    </div>
  </div>
  <button type="button" id="add-anchor" class="text-sm text-blue-400 hover:underline">+ Add More</button>

  <!-- Submit -->
  <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Vision Board</button>
</form>

<script>
document.getElementById('add-anchor').addEventListener('click', () => {
  const div = document.createElement('div');
  div.className = "flex space-x-2";
  div.innerHTML = `
    <input name="custom_keys[]" placeholder="Key" class="w-1/2 p-2 bg-gray-700 rounded">
    <input name="custom_values[]" placeholder="Value" class="w-1/2 p-2 bg-gray-700 rounded">
  `;
  document.getElementById('custom-anchors').appendChild(div);
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/board_editor.php';
?>
