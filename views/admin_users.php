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

<div class="card" style="padding:0;overflow-x:auto;">
  <table id="adminUsers" style="width:100%;border-collapse:collapse;min-width:860px;">
    <thead>
      <tr style="text-align:left;border-bottom:1px solid #2b3346;">
        <th style="padding:.7rem .9rem;">#</th>
        <th style="padding:.7rem .9rem;">Name</th>
        <th style="padding:.7rem .9rem;">Email</th>
        <th style="padding:.7rem .9rem;">Boards</th>
        <th style="padding:.7rem .9rem;">Role</th>
        <th style="padding:.7rem .9rem;">Joined</th>
        <th style="padding:.7rem .9rem;">Last login</th>
        <th style="padding:.7rem .9rem;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <?php $isSelf = (int)$u['id'] === (int)$currentUserId; ?>
        <tr data-id="<?= (int)$u['id'] ?>" style="border-bottom:1px solid #1e2230;">
          <td style="padding:.6rem .9rem;opacity:.6;font-family:monospace;"><?= (int)$u['id'] ?></td>
          <td style="padding:.6rem .9rem;font-weight:600;">
            <?= au_e($u['name'] ?: '(no name)') ?>
            <?php if ($isSelf): ?><span style="opacity:.55;font-weight:400;"> (you)</span><?php endif; ?>
          </td>
          <td style="padding:.6rem .9rem;word-break:break-all;"><?= au_e($u['email']) ?></td>
          <td style="padding:.6rem .9rem;font-size:.85em;opacity:.8;">
            🔮 <?= (int)$u['dreams'] ?> · 👁️ <?= (int)$u['visions'] ?> · 🎭 <?= (int)$u['moods'] ?>
          </td>
          <td style="padding:.6rem .9rem;">
            <select class="au-role" <?= $isSelf ? 'disabled title="You can\'t change your own role"' : '' ?>
                    style="background:#15161A;border:1px solid #2b3346;color:#ddd;
                           padding:.3rem .5rem;border-radius:6px;">
              <option value="user"  <?= $u['role']==='user'  ? 'selected':''; ?>>User</option>
              <option value="admin" <?= $u['role']==='admin' ? 'selected':''; ?>>Admin</option>
            </select>
          </td>
          <td style="padding:.6rem .9rem;font-size:.85em;opacity:.7;">
            <?= $u['created_at'] ? date('Y-m-d', strtotime($u['created_at'])) : '' ?>
          </td>
          <td style="padding:.6rem .9rem;font-size:.85em;opacity:.7;white-space:nowrap;">
            <?= !empty($u['last_login_at']) ? date('Y-m-d H:i', strtotime($u['last_login_at'])) : '—' ?>
          </td>
          <td style="padding:.6rem .9rem;white-space:nowrap;">
            <?php if (!$isSelf): ?>
              <a class="btn" href="/admin/users/<?= (int)$u['id'] ?>/impersonate"
                 title="Browse the site as this user (support). Use the Return button to come back."
                 style="padding:.3rem .6rem;font-size:.85em;text-decoration:none;">👁 View as</a>
            <?php endif; ?>
            <button type="button" class="btn au-pass" style="padding:.3rem .6rem;font-size:.85em;">Reset password</button>
            <?php if (!$isSelf): ?>
              <button type="button" class="btn au-del"
                      style="padding:.3rem .6rem;font-size:.85em;color:#f08792;">Delete</button>
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
      const name  = tr.children[1]?.textContent?.toLowerCase() || '';
      const email = tr.children[2]?.textContent?.toLowerCase() || '';
      tr.style.display = (!q || name.includes(q) || email.includes(q)) ? '' : 'none';
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

    if (e.target.closest('.au-pass')) {
      const pass = prompt('New password (min 6 characters):');
      if (pass === null) return;
      await post(`/admin/users/${id}/password`, { password: pass });
      return;
    }
    if (e.target.closest('.au-del')) {
      const email = row.children[2]?.textContent?.trim() || 'this user';
      if (!confirm(`Delete ${email}?\n\nTheir boards stay in the database but become orphaned. This cannot be undone.`)) return;
      if (await post(`/admin/users/${id}/delete`, {})) row.remove();
    }
  });
})();
</script>
