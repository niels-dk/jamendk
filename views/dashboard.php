<?php
// views/dashboard.php
// Expects: $dreams, $filter, $boardType, $boardTypes (from controller)

$boardLabels = [
  'dream'  => '🌕 ' . t('board.dreams'),
  'vision' => '📄 ' . t('board.visions'),
  'mood'   => '🎨 ' . t('board.moods'),
  'trip'   => '🗺️ ' . t('board.trips'),
];

// Fallbacks
$boardType   = $boardType ?? 'dream';
$filter      = $filter ?? 'active';
$filterLabels = [
  'active'         => t('filter.active'),
  'archived'       => t('filter.archived'),
  'trash'          => t('filter.trash'),
  'promoted'       => t('filter.promoted'),
  'shared-with-me' => t('filter.shared_with_me'),
  'shared-by-me'   => t('filter.shared_by_me'),
];

// Collaborator role shown on a shared card. ucfirst() on the raw column can
// never translate, so map the stored value onto the roles.* labels.
$sharedRoleLabels = [
  'co_owner' => t('roles.co_owner_short'),
  'editor'   => t('roles.editor_short'),
  'viewer'   => t('roles.viewer_short'),
  'delegate' => t('roles.delegate_short'),
];
$activeLabel = $filterLabels[$filter] ?? ucfirst($filter);
$currentTypeLabel = $boardLabels[$boardType] ?? t('dash.boards');
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

<input id="search" type="search" placeholder="<?= te('dash.search') ?>" style="width:100%;margin-bottom:1rem;padding:.6rem;border:1px solid var(--border);border-radius:4px;">

<!-- Big bold title with two dropdowns -->
<h1 class="dash-title">
  <span class="dash-dd" id="dd-type">
    <button type="button" aria-haspopup="menu" aria-expanded="false">
      <span><?= htmlspecialchars($currentTypeLabel) ?></span>
      <span class="chev">▾</span>
    </button>
    <div class="menu" role="menu" aria-label="<?= te('dash.choose_type') ?>">
      <a href="/dashboard/dream<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">🌕 <?= te('board.dreams') ?></a>
      <a href="/dashboard/vision<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">📄 <?= te('board.visions') ?></a>
      <a href="/dashboard/mood<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">🎨 <?= te('board.moods') ?></a>
      <a href="/dashboard/trip<?= $filter !== 'active' ? '/'.$filter : '' ?>" role="menuitem">🗺️ <?= te('board.trips') ?></a>
    </div>
  </span>

  <span class="sep">→</span>

  <span class="dash-dd" id="dd-state">
    <button type="button" aria-haspopup="menu" aria-expanded="false">
      <span><?= htmlspecialchars($activeLabel) ?></span>
      <span class="chev">▾</span>
    </button>
    <div class="menu" role="menu" aria-label="<?= te('dash.choose_state') ?>">
      <a href="/dashboard/<?= $boardType ?>" role="menuitem"><?= te('filter.active') ?></a>
      <a href="/dashboard/<?= $boardType ?>/archived" role="menuitem"><?= te('filter.archived') ?></a>
      <a href="/dashboard/<?= $boardType ?>/trash" role="menuitem"><?= te('filter.trash') ?></a>
      <?php if ($boardType === 'dream'): ?>
        <a href="/dashboard/<?= $boardType ?>/promoted" role="menuitem"><?= te('filter.promoted') ?></a>
      <?php endif; ?>
      <?php if ($boardType === 'vision' || $boardType === 'mood'): ?>
        <a href="/dashboard/<?= $boardType ?>/shared-with-me" role="menuitem">🤝 <?= te('filter.shared_with_me') ?></a>
        <a href="/dashboard/<?= $boardType ?>/shared-by-me" role="menuitem">📤 <?= te('filter.shared_by_me') ?></a>
      <?php endif; ?>
    </div>
  </span>
</h1>

<?php if (empty($dreams)): ?>
  <div class="empty-state">
    <p><?= te('dash.none_under', ['label' => $activeLabel]) ?></p>
    <?php if ($filter === 'active' && $boardType !== 'trip'): ?>
      <a class="btn" href="/<?= $boardType ?>s/new">➕ <?= te('dash.create_first', ['type' => t('board.' . $boardType)]) ?></a>
    <?php elseif ($boardType === 'trip'): ?>
      <span class="hint" style="opacity:.75;"><?= te('dash.trips_hint') ?></span>
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
            <?= htmlspecialchars($d['title'] ?: t('common.untitled')) ?>
          </a>
          <?php if ($boardType === 'dream' && !empty($d['is_promoted'])): ?>
            <span title="<?= te('dash.promoted_tip') ?>"
                  style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                         border-radius:999px;background:rgba(58,118,210,.18);
                         border:1px solid rgba(58,118,210,.45);color:#a8c4ee;
                         font-size:.7rem;vertical-align:middle;font-weight:600;">
              ✨ <?= te('dash.promoted') ?>
            </span>
          <?php endif; ?>
          <?php global $currentUserId;
                $roleLbl = !empty($d['my_shared_role'])
                  ? ' · ' . ($sharedRoleLabels[$d['my_shared_role']] ?? $d['my_shared_role']) : '';
                if (!empty($d['user_id']) && (int)$d['user_id'] !== (int)$currentUserId): ?>
            <span title="<?= te('dash.shared_tip') ?>"
                  style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                         border-radius:999px;background:rgba(126,217,154,.14);
                         border:1px solid rgba(126,217,154,.4);color:#7ed99a;
                         font-size:.7rem;vertical-align:middle;font-weight:600;">
              🤝 <?= te('dash.shared') ?><?= htmlspecialchars($roleLbl) ?>
            </span>
          <?php elseif (!empty($d['shared_with_names'])): ?>
            <span title="<?= te('dash.shared_by_tip', ['names' => $d['shared_with_names']]) ?>"
                  style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                         border-radius:999px;background:rgba(58,118,210,.14);
                         border:1px solid rgba(58,118,210,.4);color:#8fb1d8;
                         font-size:.7rem;vertical-align:middle;font-weight:600;">
              📤 <?= te('dash.shared_with', ['names' => $d['shared_with_names']]) ?>
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
            ? te('dash.deleted') . ' ' . date('Y-m-d', strtotime($d['deleted_at']))
            : te('vision.created') . ' ' . date('Y-m-d', strtotime($d['created_at'])) ?>
        </small>

		  <?php if ($boardType !== 'trip'): ?>
		  <button class="menu-toggle" aria-label="<?= te('dash.actions') ?>">&#8942;</button> <!-- ? -->
        <div class="card-menu">
          <ul class="menu">
            <?php
              $jsConfirm = fn(string $key) => htmlspecialchars(
                  json_encode(t($key), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            ?>
            <?php if ($filter === 'active'): ?>
              <li><button onclick="location='/<?= $boardType ?>s/<?= $d['slug'] ?>/archive'"><?= te('dash.archive') ?></button></li>
              <li><button onclick="if(confirm(<?= $jsConfirm('dash.confirm_delete') ?>)) location='/<?= $boardType ?>s/<?= $d['slug'] ?>/delete'"><?= te('action.delete') ?></button></li>
            <?php elseif ($filter === 'archived'): ?>
              <li><button onclick="location='/<?= $boardType ?>s/<?= $d['slug'] ?>/unarchive'"><?= te('dash.unarchive') ?></button></li>
              <li><button onclick="if(confirm(<?= $jsConfirm('dash.confirm_delete') ?>)) location='/<?= $boardType ?>s/<?= $d['slug'] ?>/delete'"><?= te('action.delete') ?></button></li>
            <?php elseif ($filter === 'trash'): ?>
              <li><button onclick="location='/<?= $boardType ?>s/<?= $d['slug'] ?>/restore'"><?= te('dash.restore') ?></button></li>
              <li><button onclick="if(confirm(<?= $jsConfirm('dash.confirm_delete_perm') ?>)) location='/<?= $boardType ?>s/<?= $d['slug'] ?>/delete'"><?= te('dash.delete_permanently') ?></button></li>
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
