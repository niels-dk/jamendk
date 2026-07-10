<?php
// views/dashboard.php
// Expects: $dreams, $filter, $boardType, $boardTypes (from controller)

$boardLabels = [
  'dream'  => '🌕 Dreams',
  'vision' => '📄 Visions',
  'mood'   => '🎨 Moods',
  'trip'   => '🗺️ Trips',
];

// Fallbacks
$boardType   = $boardType ?? 'dream';
$filter      = $filter ?? 'active';
$filterLabels = [
  'active'         => 'Active',
  'archived'       => 'Archived',
  'trash'          => 'Trash',
  'promoted'       => 'Promoted',
  'shared-with-me' => 'Shared with me',
  'shared-by-me'   => 'Shared by me',
];
$activeLabel = $filterLabels[$filter] ?? ucfirst($filter);
$currentTypeLabel = $boardLabels[$boardType] ?? 'Boards';
?>

<style>
  /* Header dropdown look */
  .dash-title {
    display:flex; align-items:center; gap:.6rem;
    font-weight:800; font-size:2.1rem; line-height:1.15;
    margin: .6rem 0 1rem;
  }
  .dash-title .sep { opacity:.65; font-weight:700; }
  .dash-dd { position:relative; display:inline-block; }
  .dash-dd button {
    display:inline-flex; align-items:center; gap:.45rem;
    background:transparent; border:0; color:inherit;
    font: inherit; padding:0; cursor:pointer;
  }
  .dash-dd .chev {
    display:inline-block; transform:translateY(1px);
    opacity:.7; transition:transform .12s ease;
  }
  .dash-dd.open .chev { transform:translateY(1px) rotate(180deg); }
  .dash-dd .menu {
    position:absolute; top:110%; left:0; min-width:200px;
    background: var(--card, #1f2126);
    border: 1px solid var(--border, #2b2f36);
    border-radius:10px; padding:.35rem; box-shadow:0 10px 30px rgba(0,0,0,.35);
    z-index: 40; display:none;
  }
  .dash-dd.open .menu { display:block; }
  .dash-dd .menu a {
    display:flex; align-items:center; gap:.5rem;
    padding:.5rem 0rem; border-radius:.5rem; text-decoration:none;
    color:inherit;
  }
  .dash-dd .menu a:hover { background:rgba(255,255,255,.06); }
  .dash-dd .menu .hint {
    display:block; padding:.5rem .6rem; opacity:.7; font-size:.9rem;
  }

  /* Keep search spacing tidy with new header */
  #search { margin-top:.4rem!important; }
	
	@media (max-width: 600px) {
		.dash-title {
			font-size: 1.4rem; /* smaller than desktop’s 2.1rem */
			gap: .4rem;        /* tighten spacing between items */
			}
			.dash-title .sep {
			font-size: 1.2rem; /* adjust arrow size proportionally */
			}
			.dash-dd button {
			font-size: 1.4rem; /* keep dropdown text in sync */
		}
	}

</style>

<input id="search" type="search" placeholder="Search boards…" style="width:100%;margin-bottom:1rem;padding:.6rem;border:1px solid var(--border);border-radius:4px;">

<!-- Big bold title with two dropdowns -->
<h1 class="dash-title">
  <span class="dash-dd" id="dd-type">
    <button type="button" aria-haspopup="menu" aria-expanded="false">
      <span><?= htmlspecialchars($currentTypeLabel) ?></span>
      <span class="chev">▾</span>
    </button>
    <div class="menu" role="menu" aria-label="Choose board type">
      <a href="/dashboard/dream<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">🌕 Dreams</a>
      <a href="/dashboard/vision<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">📄 Visions</a>
      <a href="/dashboard/mood<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">🎨 Moods</a>
      <a href="/dashboard/trip<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">🗺️ Trips</a>
    </div>
  </span>

  <span class="sep">→</span>

  <span class="dash-dd" id="dd-state">
    <button type="button" aria-haspopup="menu" aria-expanded="false">
      <span><?= htmlspecialchars($activeLabel) ?></span>
      <span class="chev">▾</span>
    </button>
    <div class="menu" role="menu" aria-label="Choose board state">
      <a href="/dashboard/<?= $boardType ?>" role="menuitem">Active</a>
      <a href="/dashboard/<?= $boardType ?>/archived" role="menuitem">Archived</a>
      <a href="/dashboard/<?= $boardType ?>/trash" role="menuitem">Trash</a>
      <?php if ($boardType === 'dream'): ?>
        <a href="/dashboard/<?= $boardType ?>/promoted" role="menuitem">Promoted</a>
      <?php endif; ?>
      <?php if ($boardType === 'vision' || $boardType === 'mood'): ?>
        <a href="/dashboard/<?= $boardType ?>/shared-with-me" role="menuitem">🤝 Shared with me</a>
        <a href="/dashboard/<?= $boardType ?>/shared-by-me" role="menuitem">📤 Shared by me</a>
      <?php endif; ?>
    </div>
  </span>
</h1>

<?php if (empty($dreams)): ?>
  <div class="empty-state">
    <p>No boards found under “<?= htmlspecialchars($activeLabel) ?>”.</p>
    <?php if ($filter === 'active' && $boardType !== 'trip'): ?>
      <a class="btn" href="/<?= $boardType ?>s/new">➕ Create Your First <?= htmlspecialchars($boardTypes[$boardType] ?? ucfirst($boardType)) ?></a>
    <?php elseif ($boardType === 'trip'): ?>
      <span class="hint" style="opacity:.75;">Trips are generated when a Vision is paired with a Mood board.</span>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="dashboard-grid">
    <?php foreach ($dreams as $d): ?>
      <div class="dashboard-card">
        <h3>
          <?php
            // Visions: deep-link to the editor only when it's your own board
            // (or you're admin). Shared boards land on the show page, which
            // offers Edit when the collaborator's role allows it.
            global $currentUserId;
            $ownBoard = !empty($d['user_id']) && ((int)$d['user_id'] === (int)$currentUserId
                        || (function_exists('is_admin') && is_admin()));
            $cardHref = '/' . $boardType . 's/' . htmlspecialchars($d['slug'])
                      . (($boardType === 'vision' && $ownBoard) ? '/edit' : '');
          ?>
          <a href="<?= $cardHref ?>">
            <span class="board-tag board-tag-<?= $boardType ?>">
              <?= htmlspecialchars(mb_substr($boardLabels[$boardType] ?? '❓', 0, 2)) ?>
            </span>
            <?= htmlspecialchars($d['title'] ?: 'Untitled') ?>
          </a>
          <?php if ($boardType === 'dream' && !empty($d['is_promoted'])): ?>
            <span title="Promoted to a Vision"
                  style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                         border-radius:999px;background:rgba(58,118,210,.18);
                         border:1px solid rgba(58,118,210,.45);color:#a8c4ee;
                         font-size:.7rem;vertical-align:middle;font-weight:600;">
              ✨ Promoted
            </span>
          <?php endif; ?>
          <?php global $currentUserId;
                if (!empty($d['user_id']) && (int)$d['user_id'] !== (int)$currentUserId): ?>
            <span title="Shared with you (or another user's board, if you're admin)"
                  style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                         border-radius:999px;background:rgba(126,217,154,.14);
                         border:1px solid rgba(126,217,154,.4);color:#7ed99a;
                         font-size:.7rem;vertical-align:middle;font-weight:600;">
              🤝 Shared
            </span>
          <?php endif; ?>
        </h3>

        <?php if (!empty($d['description'])): ?>
          <div class="card-excerpt">
            <?= htmlspecialchars(strip_tags($d['description'])) ?>
          </div>
        <?php endif; ?>

        <div class="card-chips">
          <?php foreach (($d['anchors'] ?? []) as $list): ?>
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

		  <?php if ($boardType !== 'trip'): ?>
		  <button class="menu-toggle" aria-label="Actions">&#8942;</button> <!-- ? -->
        <div class="card-menu">
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
		  <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
  // Simple dropdown toggles with outside click close
  (function () {
    const dds = document.querySelectorAll('.dash-dd');
    dds.forEach(dd => {
      const btn = dd.querySelector('button');
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dds.forEach(x => { if (x !== dd) x.classList.remove('open'); });
        dd.classList.toggle('open');
        btn.setAttribute('aria-expanded', dd.classList.contains('open') ? 'true':'false');
      });
    });
    document.addEventListener('click', () => dds.forEach(dd => dd.classList.remove('open')));
  })();

  // Client-side search (unchanged)
  document.getElementById('search').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.dashboard-card').forEach(card => {
      const text = card.innerText.toLowerCase();
      card.style.display = text.includes(q) ? '' : 'none';
    });
  });
</script>
