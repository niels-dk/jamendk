<?php
// views/admin_users.php — expects $users (id, name, email, role, created_at, dreams, visions, moods)
function au_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
global $currentUserId;
?>

<h1 style="margin-bottom:.3rem;">User management</h1>
<p style="opacity:.65;margin-top:0;">Site-level accounts. Board sharing (Editor/Viewer per vision) is managed inside each Vision under Roles &amp; Permissions.</p>

<input id="auSearch" type="search" placeholder="Filter by name or email…"
       style="width:100%;max-width:360px;box-sizing:border-box;margin-bottom:.8rem;
              padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
              color:#ddd;border-radius:8px;">

<style>
  #adminUsers { width:100%; border-collapse:collapse; min-width:760px; }
  #adminUsers thead th {
    text-align:left; padding:.7rem .9rem; border-bottom:1px solid #2b3346;
    font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; opacity:.7;
    white-space:nowrap;
  }
  #adminUsers tbody td {
    padding:.65rem .9rem; border-bottom:1px solid #1e2230; vertical-align:middle;
  }
  #adminUsers .u-name { font-weight:600; color:#eaeaea; white-space:nowrap; }
  #adminUsers .u-mail {
    font-size:.82em; opacity:.6; max-width:260px;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  }
  #adminUsers .u-extra { font-size:.78em; opacity:.5; white-space:nowrap; }
  #adminUsers .u-boards { white-space:nowrap; font-size:.85em; opacity:.85; }
  #adminUsers .u-date { white-space:nowrap; font-size:.85em; opacity:.7; }
  #adminUsers select.au-role {
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.35rem .5rem; border-radius:6px; min-width:88px;
  }
  #adminUsers .u-verified { white-space:nowrap; }
  #adminUsers .v-badge {
    display:inline-block; padding:.1rem .5rem; border-radius:999px;
    font-size:.75em; font-weight:700;
  }
  #adminUsers .v-ok      { background:rgba(127,201,141,.15); color:#7fc98d; }
  #adminUsers .v-pending { background:rgba(232,194,103,.15); color:#e8c267; }
  #adminUsers .au-verify {
    display:inline-block; margin-left:.3rem; padding:.2rem .5rem;
    font-size:.75em; cursor:pointer;
  }
  #adminUsers .u-actions { white-space:nowrap; }
  #adminUsers .u-actions .btn, #adminUsers .u-actions a.btn {
    display:inline-block; padding:.3rem .6rem; font-size:.82em;
    margin-right:.25rem; text-decoration:none;
  }
</style>

<div class="card" style="padding:0;overflow-x:auto;">
  <table id="adminUsers">
    <thead>
      <tr>
        <th>#</th>
        <th>User</th>
        <th>Boards</th>
        <th>Email</th>
        <th>Role</th>
        <th>Joined</th>
        <th>Last login</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <?php
          $isSelf = (int)$u['id'] === (int)$currentUserId;
          $extra  = trim(implode(' · ', array_filter([
            $u['company'] ?? '', $u['organisation'] ?? ''
          ])));
        ?>
        <tr data-id="<?= (int)$u['id'] ?>">
          <td style="opacity:.6;font-family:monospace;"><?= (int)$u['id'] ?></td>
          <td>
            <div class="u-name">
              <?= au_e($u['name'] ?: '(no name)') ?>
              <?php if (!empty($u['founding_creator_at'])): ?>
                <span title="Founding Creator since <?= au_e(date('M j, Y', strtotime($u['founding_creator_at']))) ?> — free forever at their launch-day team size"
                      style="cursor:default;">✨</span>
              <?php endif; ?>
              <?php if ($isSelf): ?><span style="opacity:.55;font-weight:400;">(you)</span><?php endif; ?>
              <?php if (!empty($u['deactivated_at'])): ?>
                <span class="u-deact-tag" style="margin-left:.3rem;padding:.05rem .45rem;border-radius:999px;
                      background:rgba(224,106,106,.16);color:#f0a0a0;font-size:.7rem;font-weight:700;">
                  Deactivated</span>
              <?php endif; ?>
            </div>
            <div class="u-mail" title="<?= au_e($u['email']) ?>"><?= au_e($u['email']) ?></div>
            <?php if ($extra !== ''): ?>
              <div class="u-extra"><?= au_e($extra) ?></div>
            <?php endif; ?>
          </td>
          <td class="u-boards" title="Dreams · Visions · Moods">
            🔮 <?= (int)$u['dreams'] ?> &nbsp; 👁️ <?= (int)$u['visions'] ?> &nbsp; 🎭 <?= (int)$u['moods'] ?>
          </td>
          <td class="u-verified">
            <?php if (!array_key_exists('email_verified_at', $u)): ?>
              <span style="opacity:.4;font-size:.82em;">n/a</span>
            <?php elseif (!empty($u['email_verified_at'])): ?>
              <span class="v-badge v-ok"
                    title="Confirmed <?= au_e(date('Y-m-d H:i', strtotime($u['email_verified_at']))) ?>">
                ✓ Verified
              </span>
            <?php else: ?>
              <span class="v-badge v-pending" title="This account cannot sign in until the address is confirmed">
                ⏳ Pending
              </span>
              <button type="button" class="btn au-verify"
                      title="Confirm this address by hand — use when the email never arrived">
                Verify now
              </button>
            <?php endif; ?>
          </td>
          <td>
            <select class="au-role" <?= $isSelf ? 'disabled title="You can\'t change your own role"' : '' ?>>
              <option value="user"  <?= $u['role']==='user'  ? 'selected':''; ?>>User</option>
              <option value="admin" <?= $u['role']==='admin' ? 'selected':''; ?>>Admin</option>
            </select>
          </td>
          <td class="u-date"><?= $u['created_at'] ? date('Y-m-d', strtotime($u['created_at'])) : '' ?></td>
          <td class="u-date"><?= !empty($u['last_login_at']) ? date('Y-m-d H:i', strtotime($u['last_login_at'])) : '—' ?></td>
          <td class="u-actions">
            <?php if (!$isSelf): ?>
              <a class="btn" href="/admin/users/<?= (int)$u['id'] ?>/impersonate"
                 title="Browse the site as this user (support). Use the Return button to come back.">👁 View as</a>
            <?php endif; ?>
            <button type="button" class="btn au-pass" title="Set a new password for this account">Reset password</button>
            <?php if (!$isSelf): ?>
              <button type="button" class="btn au-transfer"
                      title="Move everything this account owns to another creator">Transfer</button>
              <?php $isDeact = !empty($u['deactivated_at']); ?>
              <button type="button" class="btn au-deact" data-on="<?= $isDeact ? '1' : '0' ?>"
                      title="<?= $isDeact ? 'Allow this account to sign in again' : 'Block this account from signing in' ?>"
                      style="color:<?= $isDeact ? '#7fc98d' : '#e8c889' ?>;">
                <?= $isDeact ? 'Reactivate' : 'Deactivate' ?>
              </button>
              <button type="button" class="btn au-del" style="color:#f08792;">Delete</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p id="auStatus" style="opacity:.6;font-size:.85em;min-height:1.2em;"></p>

<script>
(() => {
  const table  = document.getElementById('adminUsers');
  const status = document.getElementById('auStatus');
  if (!table) return;

  // Client-side filter by name/email
  const search = document.getElementById('auSearch');
  search?.addEventListener('input', () => {
    const q = search.value.toLowerCase().trim();
    table.querySelectorAll('tbody tr').forEach(tr => {
      // The User cell holds name + email + company/organisation
      const who = tr.children[1]?.textContent?.toLowerCase() || '';
      tr.style.display = (!q || who.includes(q)) ? '' : 'none';
    });
  });

  async function post(url, params) {
    status.textContent = 'Saving…';
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params).toString()
      });
      const j = await res.json();
      if (j && j.success) { status.textContent = 'Saved'; return true; }
      status.textContent = '⚠ ' + (j?.error || 'Failed');
      return false;
    } catch {
      status.textContent = '⚠ Network error';
      return false;
    }
  }

  table.addEventListener('change', async e => {
    const sel = e.target.closest('.au-role');
    if (!sel) return;
    const id = sel.closest('tr').dataset.id;
    const ok = await post(`/admin/users/${id}/role`, { role: sel.value });
    if (!ok) location.reload(); // revert visual state on failure
  });

  table.addEventListener('click', async e => {
    const row = e.target.closest('tr');
    if (!row) return;
    const id = row.dataset.id;

    if (e.target.closest('.au-verify')) {
      const email = row.querySelector('.u-mail')?.textContent?.trim() || 'this account';
      if (!confirm(`Confirm ${email} by hand?\n\nOnly do this if you know the address is real — it lets the account sign in without clicking the emailed link.`)) return;
      if (await post(`/admin/users/${id}/verify`, {})) {
        row.querySelector('.u-verified').innerHTML =
          '<span class="v-badge v-ok">✓ Verified</span>';
      }
      return;
    }
    if (e.target.closest('.au-pass')) {
      const pass = prompt('New password (min 6 characters):');
      if (pass === null) return;
      await post(`/admin/users/${id}/password`, { password: pass });
      return;
    }
    if (e.target.closest('.au-transfer')) {
      const who = row.querySelector('.u-mail')?.textContent?.trim() || 'this account';
      const toEmail = prompt(`Transfer everything ${who} owns to which creator?\n\nEnter the recipient's account email. This moves all their dreams, visions, moods and teams and cannot be undone.`);
      if (!toEmail) return;
      const deact = confirm(`Also block ${who} from signing in?\n\nOK = deactivate their login (they've left).\nCancel = leave their login active.`);
      const j = await post(`/admin/users/${id}/transfer`, { to_email: toEmail.trim(), deactivate: deact ? 1 : 0 });
      if (j && j.success) {
        alert(`Moved ${j.moved} to ${j.to}.` + (j.deactivated ? '\nOld login deactivated.' : ''));
        location.reload();
      }
      return;
    }
    if (e.target.closest('.au-deact')) {
      const btn = e.target.closest('.au-deact');
      const turningOn = btn.dataset.on !== '1';   // on = deactivated
      const who = row.querySelector('.u-mail')?.textContent?.trim() || 'this account';
      if (turningOn && !confirm(`Block ${who} from signing in?\n\nThey keep their data and history — they just can't log in until reactivated.`)) return;
      const j = await post(`/admin/users/${id}/deactivate`, { on: turningOn ? 1 : 0 });
      if (j && j.success) location.reload();
      return;
    }
    if (e.target.closest('.au-del')) {
      const email = row.querySelector('.u-mail')?.textContent?.trim() || 'this user';
      if (!confirm(`Delete ${email}?\n\nTheir boards stay in the database but become orphaned. This cannot be undone.`)) return;
      if (await post(`/admin/users/${id}/delete`, {})) row.remove();
    }
  });
})();
</script>
