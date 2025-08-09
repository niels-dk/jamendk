<?php
$pageTitle = $dream['title'] ?? 'View Dream';
ob_start();
?>

<div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8">
  <a href="/dashboard" class="text-purple-400 hover:underline mb-4 inline-block">
    ← Back to dashboard
  </a>

  <div class="bg-gray-800 rounded-lg shadow-md p-6">
    <h1 class="text-2xl font-bold text-white mb-4">
      <?= htmlspecialchars($dream['title']) ?>
    </h1>

    <p class="text-gray-300 whitespace-pre-line mb-4">
      <?= htmlspecialchars($dream['description']) ?>
    </p>

    <p class="text-gray-500 text-sm mb-4">
      Created <?= htmlspecialchars($dream['created_at']) ?>
    </p>

    <div class="flex gap-2">
      <a href="/dreams/<?= $dream['id'] ?>/edit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
        Edit Dream
      </a>
      <a href="/dashboard" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
        Back to dashboard 1
      </a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
