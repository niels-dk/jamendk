<?php
$pageTitle = $dream['title'] ?? 'Dream Detail';
ob_start();
?>

<div class="container">

  <h1 class="text-2xl font-bold text-white mb-6">
    <?= htmlspecialchars($dream['title']) ?>
  </h1>

  <?php if (!empty($dream['description'])): ?>
    <div class="trix-content text-gray-300 whitespace-pre-line mb-6">
      <?= $dream['description'] ?>
    </div>
  <?php endif; ?>

<div class="flex flex-col justify-between min-h-[140px] bg-gray-800 rounded-xl border border-gray-700 p-4">
  <?php if (!empty($anchors['locations'])): ?>
    <div class="flex flex-col justify-between min-h-[140px] bg-gray-800 rounded-xl border border-gray-700 p-4">
      <h3 class="text-blue-400 font-semibold mb-2"><?= icon('pin') ?> Locations</h3>
      <div class="card-chips">
        <?php foreach ($anchors['locations'] as $val): ?>
          <span class="chip"><?= htmlspecialchars($val) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($anchors['brands'])): ?>
    <div class="flex flex-col justify-between min-h-[140px] bg-gray-800 rounded-xl border border-gray-700 p-4">
      <h3 class="text-blue-400 font-semibold mb-2"><?= icon('bag') ?> Brands</h3>
      <div class="card-chips">
        <?php foreach ($anchors['brands'] as $val): ?>
          <span class="chip"><?= htmlspecialchars($val) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($anchors['people'])): ?>
    <div class="flex flex-col justify-between min-h-[140px] bg-gray-800 rounded-xl border border-gray-700 p-4">
      <h3 class="text-blue-400 font-semibold mb-2"><?= icon('user') ?> People</h3>
      <div class="card-chips">
        <?php foreach ($anchors['people'] as $val): ?>
          <span class="chip"><?= htmlspecialchars($val) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($anchors['seasons'])): ?>
    <div class="flex flex-col justify-between min-h-[140px] bg-gray-800 rounded-xl border border-gray-700 p-4">
      <h3 class="text-blue-400 font-semibold mb-2"><?= icon('calendar') ?> Seasons / Time</h3>
      <div class="card-chips">
        <?php foreach ($anchors['seasons'] as $val): ?>
          <span class="chip"><?= htmlspecialchars($val) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>



  <p class="text-gray-500 text-sm mb-6">
    Created <?= htmlspecialchars($dream['created_at']) ?>
  </p>

  <div class="flex gap-3">
    <a class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" href="/dreams/<?= htmlspecialchars($dream['slug']) ?>/edit">Edit Dream</a>
    <a class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" href="/dashboard">Back to dashboard</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';

function icon($name){
  $map = [
    'pin' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline align-text-bottom text-blue-400">
                <path d="M12 2a6 6 0 0 0-6 6c0 4.4 6 12 6 12s6-7.6 6-12a6 6 0 0 0-6-6zM12 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
              </svg>',

    'bag' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline align-text-bottom text-blue-400">
                <path d="M6 7V6a6 6 0 1 1 12 0v1h3v15H3V7h3zm2 0h8V6a4 4 0 1 0-8 0v1z"/>
              </svg>',

    'user' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline align-text-bottom text-blue-400">
                <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z"/>
              </svg>',

    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline align-text-bottom text-blue-400">
                    <path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14zm0-12H5V6h14z"/>
                  </svg>',
  ];

  return $map[$name] ?? '';
}



?>
