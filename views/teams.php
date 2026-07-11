<?php
// views/teams.php — expects:
//   $ownTeams        teams I manage (admin: every team, with owner_name/owner_email)
//   $memberTeams     teams I'm a member of (read-only except member-add)
//   $boardsByTeamUser[team_id][user_id] = [ ['slug','title','role'], … ]
//   optional $migrationMissing
function tm_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$ROLE_LABELS = ['co_owner'=>'Co-owner','editor'=>'Editor','viewer'=>'Viewer','delegate'=>'Delegate'];
global $currentUserId;
$isAdminView = function_exists('is_admin') && is_admin();
?>

<h1 style="margin-bottom:.3rem;"><?= $isAdminView ? 'All teams' : 'My teams' ?></h1>
<p style="opacity:.65;margin-top:0;max-width:44rem;">
  <?php if ($isAdminView): ?>
    As admin you see every user's teams and can manage all of them. Click a team to unfold its members.
  <?php else: ?>
    Teams are your private collaborator groups. Set each member's usual role once, and you can add
    a whole team to any Vision from its <strong>Roles &amp; Permissions</strong> panel in one click.
    Click a team to unfold its members.
  <?php endif; ?>
</p>

<?php if (!empty($migrationMissing)): ?>
  <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
              color:#e8c267;padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;">
    The teams tables haven't been created yet — run
    <code>db/migrations/2026-07-10_teams.sql</code> in phpMyAdmin.
  </div>
<?php endif; ?>

<style>
  .team-card { margin-bottom:.7rem; padding:.7rem 1.1rem; }
  .team-head { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
  .team-toggle {
    flex:1; min-width:200px; display:flex; align-items:center; gap:.5rem;
    background:none; border:0; color:inherit; cursor:pointer; text-align:left;
    font-size:1.1rem; font-weight:700; padding:.15rem 0;
  }
  .team-toggle .chev { display:inline-block; transition:transform .12s ease; opacity:.6; font-size:.85em; }
  .team-card.open .team-toggle .chev { transform:rotate(90deg); }
  .team-toggle .t-meta {
    font-weight:400; font-size:.8rem; opacity:.55; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis;
  }
  .team-body { margin-top:.6rem; }
  .team-owner { font-size:.82em; opacity:.6; margin:0 0 .6rem; }
  .team-card table { width:100%; border-collapse:collapse; min-width:640px; }
  .team-card thead th {
    text-align:left; padding:.5rem .7rem; border-bottom:1px solid #2b3346;
    font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; opacity:.65; white-space:nowrap;
  }
  .team-card tbody td { padding:.55rem .7rem; border-bottom:1px solid #1e2230; vertical-align:top; }
  .team-card .m-name { font-weight:600; color:#eaeaea; white-space:nowrap; }
  .team-card .m-mail { font-size:.82em; opacity:.6; }
  .team-card .m-extra { font-size:.78em; opacity:.5; }
  .team-card select {
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.3rem .5rem; border-radius:6px;
  }
  .team-card .m-boards { display:flex; flex-wrap:wrap; gap:.3rem; max-width:320px; }
  .team-card .board-chip {
    display:inline-block; padding:.1rem .5rem; border-radius:999px;
    background:rgba(58,118,210,.14); border:1px solid rgba(58,118,210,.35);
    color:#8fb1d8; font-size:.75rem; text-decoration:none;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;
  }
  .team-card .none { opacity:.45; font-size:.85em; }
  .team-card .row-actions button {
    background:transparent; border:0; color:#aaa; cursor:pointer; font-size:1.05rem; padding:0 .3rem;
  }
  .team-card .row-actions button:hover { color:#f08792; }
  .team-inline-form { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.8rem; align-items:center; }
  .team-inline-form input[type="text"] {
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.45rem .7rem; border-radius:8px; min-width:220px;
  }
  .role-pill {
    display:inline-block; padding:.1rem .5rem; border-radius:999px;
    background:#2a2d35; border:1px solid #3a3f4a; color:#bbb; font-size:.78rem;
  }
  h2.section-title { margin:2rem 0 .8rem; font-size:1.25rem; }
</style>

<!-- Create team -->
<div class="card" style="padding:1rem 1.2rem;margin-bottom:1.2rem;">
  <div class="team-inline-form" style="margin-top:0;">
    <input type="text" id="newTeamName" placeholder="New team name… (e.g. Film crew)">
    <button type="button" class="btn btn-primary" id="btnCreateTeam">＋ Create team</button>
    <span id="teamsStatus" style="opacity:.6;font-size:.85em;"></span>
  </div>
</div>

<?php if (empty($ownTeams) && empty($memberTeams) && empty($migrationMissing)): ?>
  <div class="card" style="padding:1.4rem;opacity:.7;">
    No teams yet. Create one above, add your collaborators, and they'll be one click away
    whenever you share a board.
  </div>
<?php endif; ?>

<?php foreach ($ownTeams as $team): ?>
  <?php
    $isMine = (int)$team['owner_user_id'] === (int)$currentUserId;
    $count  = count($team['members'] ?? []);
    $meta   = $count . ' member' . ($count === 1 ? '' : 's')
            . ((!$isMine && !empty($team['owner_name'])) ? ' · Owner: ' . $team['owner_name'] : '');
  ?>
  <div class="card team-card" data-team-id="<?= (int)$team['id'] ?>">
    <div class="team-head">
      <button type="button" class="team-toggle">
        <span class="chev">▶</span>
        <span>👥 <?= tm_e($team['name']) ?></span>
        <span class="t-meta">· <?= tm_e($meta) ?></span>
      </button>
      <button type="button" class="btn t-rename" style="padding:.3rem .6rem;font-size:.82em;">Rename</button>
      <button type="button" class="btn t-delete" style="padding:.3rem .6rem;font-size:.82em;color:#f08792;">Delete team</button>
    </div>

    <div class="team-body" hidden>
      <?php if (!$isMine && !empty($team['owner_name'])): ?>
        <div class="team-owner">Owner: <?= tm_e($team['owner_name']) ?> — <?= tm_e($team['owner_email']) ?></div>
      <?php endif; ?>

      <?php if (empty($team['members'])): ?>
        <p class="none" style="margin:.2rem 0 .4rem;">No members yet — add someone below.</p>
      <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>Member</th>
              <th>Last active</th>
              <th>Default role</th>
              <th>On <?= $isMine ? 'my' : "the owner's" ?> boards</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($team['members'] as $m): ?>
              <?php
                $extra  = trim(implode(' · ', array_filter([$m['company'] ?? '', $m['organisation'] ?? ''])));
                $boards = $boardsByTeamUser[(int)$team['id']][(int)$m['user_id']] ?? [];
              ?>
              <tr data-member-id="<?= (int)$m['id'] ?>">
                <td>
                  <div class="m-name"><?= tm_e($m['name'] ?: '(no name)') ?></div>
                  <div class="m-mail"><?= tm_e($m['email']) ?></div>
                  <?php if ($extra !== ''): ?><div class="m-extra"><?= tm_e($extra) ?></div><?php endif; ?>
                </td>
                <td style="white-space:nowrap;font-size:.85em;opacity:.7;">
                  <?= !empty($m['last_login_at']) ? date('Y-m-d', strtotime($m['last_login_at'])) : '—' ?>
                </td>
                <td>
                  <select class="m-role">
                    <?php foreach ($ROLE_LABELS as $rv => $rl): ?>
                      <option value="<?= $rv ?>" <?= $m['default_role'] === $rv ? 'selected' : '' ?>><?= $rl ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <?php if ($boards): ?>
                    <div class="m-boards">
                      <?php foreach ($boards as $b): ?>
                        <a class="board-chip" href="/visions/<?= tm_e($b['slug']) ?>"
                           title="<?= tm_e(($b['title'] ?: 'Untitled') . ' — ' . ($ROLE_LABELS[$b['role']] ?? $b['role'])) ?>">
                          <?= tm_e($b['title'] ?: 'Untitled') ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="none">No boards yet</span>
                  <?php endif; ?>
                </td>
                <td class="row-actions">
                  <button type="button" class="m-remove" title="Remove from team">×</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>

      <div class="team-inline-form">
        <input type="text" class="t-add-email" placeholder="Member's account email…">
        <select class="t-add-role">
          <option value="viewer">Viewer — read-only</option>
          <option value="editor">Editor — can modify content</option>
          <option value="co_owner">Co-owner — full control incl. sharing</option>
          <option value="delegate">Delegate — acts on behalf of the owner</option>
        </select>
        <button type="button" class="btn t-add-member">Add member</button>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php if (!empty($memberTeams)): ?>
  <h2 class="section-title">Teams I'm on</h2>
  <?php foreach ($memberTeams as $team): ?>
    <?php
      $count = count($team['members'] ?? []);
      $meta  = $count . ' member' . ($count === 1 ? '' : 's')
             . (!empty($team['owner_name']) ? ' · Owner: ' . $team['owner_name'] : '');
    ?>
    <div class="card team-card" data-team-id="<?= (int)$team['id'] ?>">
      <div class="team-head">
        <button type="button" class="team-toggle">
          <span class="chev">▶</span>
          <span>👥 <?= tm_e($team['name']) ?></span>
          <span class="t-meta">· <?= tm_e($meta) ?></span>
        </button>
        <button type="button" class="btn t-leave" style="padding:.3rem .6rem;font-size:.82em;color:#f08792;">Leave team</button>
      </div>

      <div class="team-body" hidden>
        <div class="team-owner">Owner: <?= tm_e($team['owner_name'] ?: '') ?> — <?= tm_e($team['owner_email'] ?: '') ?></div>

        <?php if (!empty($team['members'])): ?>
          <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>Member</th>
                <th>Last active</th>
                <th>Role on this team</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($team['members'] as $m): ?>
                <?php $extra = trim(implode(' · ', array_filter([$m['company'] ?? '', $m['organisation'] ?? '']))); ?>
                <tr>
                  <td>
                    <div class="m-name">
                      <?= tm_e($m['name'] ?: '(no name)') ?>
                      <?php if ((int)$m['user_id'] === (int)$currentUserId): ?>
                        <span style="opacity:.55;font-weight:400;">(you)</span>
                      <?php endif; ?>
                    </div>
                    <div class="m-mail"><?= tm_e($m['email']) ?></div>
                    <?php if ($extra !== ''): ?><div class="m-extra"><?= tm_e($extra) ?></div><?php endif; ?>
                  </td>
                  <td style="white-space:nowrap;font-size:.85em;opacity:.7;">
                    <?= !empty($m['last_login_at']) ? date('Y-m-d', strtotime($m['last_login_at'])) : '—' ?>
                  </td>
                  <td><span class="role-pill"><?= tm_e($ROLE_LABELS[$m['default_role']] ?? $m['default_role']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>

        <!-- Any member may add people to the team -->
        <div class="team-inline-form">
          <input type="text" class="t-add-email" placeholder="Add a member by account email…">
          <select class="t-add-role">
            <option value="viewer">Viewer — read-only</option>
            <option value="editor">Editor — can modify content</option>
            <option value="co_owner">Co-owner — full control incl. sharing</option>
            <option value="delegate">Delegate — acts on behalf of the owner</option>
          </select>
          <button type="button" class="btn t-add-member">Add member</button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<script>
(() => {
  const status = document.getElementById('teamsStatus');

  async function post(url, params) {
    if (status) status.textContent = 'Saving…';
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params).toString()
      });
      const j = await res.json();
      if (j && j.success) { if (status) status.textContent = ''; return j; }
      alert(j?.error || 'Failed');
      if (status) status.textContent = '';
      return null;
    } catch {
      alert('Network error');
      if (status) status.textContent = '';
      return null;
    }
  }

  // Create team
  document.getElementById('btnCreateTeam')?.addEventListener('click', async () => {
    const name = document.getElementById('newTeamName').value.trim();
    if (!name) return alert('Enter a team name first.');
    if (await post('/api/teams/create', { name })) location.reload();
  });

  document.querySelectorAll('.team-card').forEach(card => {
    const teamId = card.dataset.teamId;

    // Fold / unfold
    card.querySelector('.team-toggle')?.addEventListener('click', () => {
      const body = card.querySelector('.team-body');
      if (!body) return;
      body.hidden = !body.hidden;
      card.classList.toggle('open', !body.hidden);
    });

    card.addEventListener('click', async e => {
      if (e.target.closest('.t-rename')) {
        const current = card.querySelector('.team-toggle span:nth-child(2)')?.textContent.replace('👥','').trim();
        const name = prompt('New team name:', current);
        if (name && await post(`/api/teams/${teamId}/rename`, { name })) location.reload();
        return;
      }
      if (e.target.closest('.t-delete')) {
        if (!confirm('Delete this team? Board access already granted stays in place — only the group itself is removed.')) return;
        if (await post(`/api/teams/${teamId}/delete`, {})) location.reload();
        return;
      }
      if (e.target.closest('.t-leave')) {
        if (!confirm('Leave this team? Any board access you already have stays.')) return;
        if (await post(`/api/teams/${teamId}/leave`, {})) location.reload();
        return;
      }
      if (e.target.closest('.t-add-member')) {
        const email = card.querySelector('.t-add-email').value.trim();
        const role  = card.querySelector('.t-add-role').value;
        if (!email) return alert('Enter the member\'s account email.');
        if (await post(`/api/teams/${teamId}/members/add`, { email, role })) location.reload();
        return;
      }
      const rm = e.target.closest('.m-remove');
      if (rm) {
        const row = rm.closest('tr');
        if (!confirm('Remove this member from the team? Their existing board access stays.')) return;
        if (await post(`/api/teams/${teamId}/members/${row.dataset.memberId}/delete`, {})) row.remove();
      }
    });

    card.addEventListener('change', async e => {
      const sel = e.target.closest('.m-role');
      if (!sel) return;
      const row = sel.closest('tr');
      await post(`/api/teams/${teamId}/members/${row.dataset.memberId}/role`, { role: sel.value });
    });
  });
})();
</script>
