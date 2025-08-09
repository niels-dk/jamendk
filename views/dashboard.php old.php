<input id="search" type="search" placeholder="Search boards…" style="width:100%;margin-bottom:1rem;padding:.6rem;border:1px solid var(--border);border-radius:4px;">

<?php
// views/dashboard.php
// Expects: $dreams, $filter, $boardType, $boardTypes
$boardIcons = [
  'dream' => '🌕 Dreams',
  'vision' => '📄 Visions',
  'mood' => '🎨 Moods',
  'trip' => '🗺️ Trips'
];
$boardTitle = $boardIcons[$boardType] ?? 'Boards';
?>

<!-- Tabs to switch between Dream / Vision / Mood / Trip -->
<nav class="board-type-tabs" style="margin-bottom: 1rem;">
  <?php foreach ($boardTypes as $key => $label): ?>
    <a href="/dashboard/<?= $key ?><?= $filter !== 'active' ? '/' . $filter : '' ?>"
       class="<?= $boardType === $key ? 'active' : '' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
</nav>

<h1><?= $boardTitle ?> – <?= ucfirst($filter) ?></h1>

<!-- Tabs to switch between Active / Archived / Trash -->
<nav class="dashboard-tabs">
  <a href="/dashboard/<?= $boardType ?>" class="<?= $filter === 'active' ? 'active' : '' ?>">Active</a>
  <a href="/dashboard/<?= $boardType ?>/archived" class="<?= $filter === 'archived' ? 'active' : '' ?>">Archived</a>
  <a href="/dashboard/<?= $boardType ?>/trash" class="<?= $filter === 'trash' ? 'active' : '' ?>">Trash</a>
</nav>

<?php if (empty($dreams)): ?>
  <div class="empty-state">
    <p>No <?= $filter ?> boards found.</p>
    <?php if ($filter === 'active'): ?>
      <a class="btn" href="/<?= $boardType ?>/new">
        ➕ Create Your First <?= $boardTypes[$boardType] ?? ucfirst($boardType) ?>
      </a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="dashboard-grid">
    <?php foreach ($dreams as $d): ?>
      <div class="dashboard-card">
        <h3>
          <a href="/<?= $boardType ?>s/<?= htmlspecialchars($d['slug']) ?>">
            <span class="board-tag board-tag-<?= $boardType ?>">
              <?= $boardIcons[$boardType][0] ?? '❓' ?>
            </span>
            <?= htmlspecialchars($d['title']) ?>
          </a>
        </h3>

        <?php if (!empty($d['description'])): ?>
          <div class="card-excerpt">
            <?= htmlspecialchars(strip_tags($d['description'])) ?>
          </div>
        <?php endif; ?>

        <div class="card-chips">
          <?php foreach ($d['anchors'] as $list): ?>
            <?php foreach ($list as $val): ?>
              <span class="chip"><?= htmlspecialchars($val) ?></span>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <small class="text-muted">
          <?= $filter === 'trash'
            ? 'Deleted ' . date('Y-m-d', strtotime($d['deleted_at']))
            : 'Created ' . date('Y-m-d', strtotime($d['created_at'])) ?>
        </small>

        <!-- Card dropdown menu -->
        <div class="card-menu">
          <button class="menu-toggle" aria-label="Actions">⋮</button>
          <ul class="menu">
            <?php if ($filter === 'active'): ?>
              <li><button onclick="location='/<?= $boardType ?>s/<?= $d['slug'] ?>/archive'">Archive</button></li>
              <li><button onclick="if(confirm('Delete forever?')) location='/<?= $boardType ?>s/<?= $d['slug'] ?>/delete'">Delete</button></li>
            <?php elseif ($filter === 'archived'): ?>
              <li><button onclick="location='/<?= $boardType ?>s/<?= $d['slug'] ?>/unarchive'">Unarchive</button></li>
              <li><button onclick="if(confirm('Delete forever?')) location='/<?= $boardType ?>s/<?= $d['slug'] ?>/delete'">Delete</button></li>
            <?php elseif ($filter === 'trash'): ?>
              <li><button onclick="location='/<?= $boardType ?>s/<?= $d['slug'] ?>/restore'">Restore</button></li>
              <li><button onclick="if(confirm('Permanently delete?')) location='/<?= $boardType ?>s/<?= $d['slug'] ?>/delete'">Delete Permanently</button></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
document.getElementById('search').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('.dashboard-card').forEach(card => {
    const text = card.innerText.toLowerCase();
    card.style.display = text.includes(q) ? '' : 'none';
  });
});
</script>
