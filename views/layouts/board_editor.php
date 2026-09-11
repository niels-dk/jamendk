<!-- views/layouts/board_editor.php -->
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::lang(), ENT_QUOTES) ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?? 'Board Editor' ?></title>
  <link href="/public/css/app.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white">
  <div class="flex h-screen">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-800 p-4 space-y-4">
      <h2 class="text-xl font-bold mb-4">🧠 Board Editor</h2>
      <nav class="space-y-2">
        <a href="/dashboard?type=dream" class="<?= $type === 'dream' ? 'text-blue-400' : 'text-gray-400' ?>">🌙 Dream Board</a>
        <a href="/dashboard?type=vision" class="<?= $type === 'vision' ? 'text-blue-400' : 'text-gray-400' ?>">👁️ Vision Board</a>
        <a href="/dashboard?type=mood" class="<?= $type === 'mood' ? 'text-blue-400' : 'text-gray-400' ?>">🎨 Mood Board</a>
        <a href="/dashboard?type=trip" class="<?= $type === 'trip' ? 'text-blue-400' : 'text-gray-400' ?>">🚐 Trip Layer</a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-6">
      <?= $content ?? '<p>Empty content.</p>' ?>
    </main>
  </div>
</body>
</html>
