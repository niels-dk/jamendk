<?php
// views/admin_users.php — expects $users (id, name, email, role, created_at, dreams, visions, moods)
function au_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
global $currentUserId;
?>

<h1 style="margin-bottom:.3rem;"><?= te('adm.users') ?></h1>
<p style="opacity:.65;margin-top:0;"><?= te('adm.users_sub') ?></p>

<input id="auSearch" type="search" placeholder="<?= te('adm.filter_users') ?>"
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
  /* Sortable headers */
  #adminUsers th.au-sort { cursor:pointer; user-select:none; }
  #adminUsers th.au-sort:hover { opacity:1; color:#fff; }
  #adminUsers th.au-sort::after {
    content:'⇅'; font-size:.85em; opacity:.25; margin-left:.35rem;
  }
  #adminUsers th.au-sort.sorted-asc::after  { content:'▲'; opacity:.9; color:#8fb1d8; }
  #adminUsers th.au-sort.sorted-desc::after { content:'▼'; opacity:.9; color:#8fb1d8; }

  /* Internal history. The (i) is always present, quiet when there is nothing
     to read — otherwise there would be no way to write the first entry on a
     user who has none. */
  #adminUsers .au-hist {
    background:none; border:0; cursor:pointer; padding:0 .15rem;
    margin-left:.3rem; font-size:.95rem; line-height:1;
    color:#5c6b80; vertical-align:baseline;
  }
  #adminUsers .au-hist:hover  { color:#8fb1d8; }
  #adminUsers .au-hist.has    { color:#8fb1d8; }
  /* The count is drawn from data-n rather than written into the cell, because
     the search box filters on the row's textContent — a literal "3" in there
     would make every user with three entries match a search for "3". */
  #adminUsers .au-hist.has::after {
    content: attr(data-n);
    font-size:.62rem; font-weight:700; vertical-align:super; margin-left:.05rem;
  }

  #uhOverlay {
    position:fixed; inset:0; z-index:900; display:flex;
    align-items:center; justify-content:center; padding:1.5rem;
    background:rgba(6,8,12,.66);
  }
  /* Must out-specify the line above. The browser hides [hidden] elements with
     an attribute selector, which loses to the ID selector on display:flex —
     so without this the overlay is open from the moment the page loads. */
  #uhOverlay[hidden] { display:none; }
  #uhPanel {
    width:100%; max-width:560px; max-height:82vh; overflow-y:auto;
    background:#12161f; border:1px solid #2b3346; border-radius:14px;
    padding:1.2rem 1.3rem; box-shadow:0 18px 50px rgba(0,0,0,.5);
  }
  #uhPanel .uh-head {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:1rem; margin-bottom:1rem;
  }
  #uhPanel .uh-who  { font-weight:700; color:#eaeaea; }
  #uhPanel .uh-mail { font-size:.82em; opacity:.6; }
  #uhPanel .uh-close {
    background:none; border:0; color:#8593a6; cursor:pointer;
    font-size:1rem; padding:.15rem .35rem;
  }
  #uhPanel .uh-close:hover { color:#eaeaea; }
  #uhPanel .uh-add textarea {
    width:100%; box-sizing:border-box; resize:vertical;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    border-radius:8px; padding:.55rem .7rem; font:inherit; font-size:.9rem;
  }
  #uhPanel .uh-add-row {
    display:flex; align-items:center; justify-content:space-between;
    gap:.8rem; margin:.5rem 0 1.2rem;
  }
  #uhPanel .uh-privacy { font-size:.72rem; opacity:.45; line-height:1.35; }
  #uhPanel .uh-empty   { opacity:.5; font-size:.9rem; margin:0; }
  #uhPanel .uh-list    { list-style:none; margin:0; padding:0; }
  #uhPanel .uh-item {
    padding:.7rem 0; border-top:1px solid #1e2230;
  }
  #uhPanel .uh-meta {
    display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
    font-size:.75rem; opacity:.75; margin-bottom:.25rem;
  }
  #uhPanel .uh-type {
    padding:.05rem .45rem; border-radius:999px;
    font-size:.7rem; font-weight:700;
  }
  #uhPanel .uh-body {
    font-size:.9rem; color:#cfdbe8; white-space:pre-wrap; word-break:break-word;
  }
</style>

<div id="uhOverlay" hidden><div id="uhPanel"></div></div>

<div class="card" style="padding:0;overflow-x:auto;">
  <table id="adminUsers">
    <thead>
      <tr>
        <th class="au-sort" data-col="0" data-type="num">#</th>
        <th class="au-sort" data-col="1" data-type="text"><?= te('adm.col_user') ?></th>
        <th class="au-sort" data-col="2" data-type="num"><?= te('adm.col_boards') ?></th>
        <th class="au-sort" data-col="3" data-type="num"><?= te('auth.email') ?></th>
        <th class="au-sort" data-col="4" data-type="text"><?= te('adm.col_role') ?></th>
        <th class="au-sort" data-col="5" data-type="text"><?= te('adm.col_joined') ?></th>
        <th class="au-sort" data-col="6" data-type="text"><?= te('adm.col_last_login') ?></th>
        <th><?= te('dash.actions') ?></th>
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
          <td style="opacity:.6;font-family:monospace;"
              data-sort="<?= (int)$u['id'] ?>"><?= (int)$u['id'] ?></td>
          <td data-sort="<?= au_e(mb_strtolower($u['name'] ?: ($u['email'] ?? ''))) ?>">
            <div class="u-name">
              <?= au_e($u['name'] ?: t('roles.no_name')) ?>
              <?php if (!empty($u['founding_creator_at'])): ?>
                <span title="<?= te('adm.founder_since', ['date' => fmt_date($u['founding_creator_at'])]) ?>"
                      style="cursor:default;">✨</span>
              <?php endif; ?>
              <?php if ($isSelf): ?><span style="opacity:.55;font-weight:400;"><?= te('adm.you') ?></span><?php endif; ?>
              <?php if (!empty($u['deactivated_at'])): ?>
                <span class="u-deact-tag" style="margin-left:.3rem;padding:.05rem .45rem;border-radius:999px;
                      background:rgba(224,106,106,.16);color:#f0a0a0;font-size:.7rem;font-weight:700;">
                  <?= te('adm.deactivated') ?></span>
              <?php endif; ?>
              <?php $evN = (int)(($eventCounts ?? [])[(int)$u['id']] ?? 0); ?>
              <button type="button" class="au-hist<?= $evN ? ' has' : '' ?>"
                      data-n="<?= $evN ?>" title="<?= te('adm.hist_tip') ?>">ⓘ</button>
            </div>
            <div class="u-mail" title="<?= au_e($u['email']) ?>"><?= au_e($u['email']) ?></div>
            <?php if ($extra !== ''): ?>
              <div class="u-extra"><?= au_e($extra) ?></div>
            <?php endif; ?>
          </td>
          <td class="u-boards" title="<?= te('adm.boards_tip') ?>"
              data-sort="<?= (int)$u['dreams'] + (int)$u['visions'] + (int)$u['moods'] ?>">
            🔮 <?= (int)$u['dreams'] ?> &nbsp; 👁️ <?= (int)$u['visions'] ?> &nbsp; 🎭 <?= (int)$u['moods'] ?>
          </td>
          <td class="u-verified" data-sort="<?=
                !array_key_exists('email_verified_at', $u) ? 2
                  : (!empty($u['email_verified_at']) ? 1 : 0) ?>">
            <?php if (!array_key_exists('email_verified_at', $u)): ?>
              <span style="opacity:.4;font-size:.82em;">n/a</span>
            <?php elseif (!empty($u['email_verified_at'])): ?>
              <span class="v-badge v-ok"
                    title="<?= te('adm.confirmed_on', ['when' => date('Y-m-d H:i', strtotime($u['email_verified_at']))]) ?>">
                ✓ <?= te('adm.verified') ?>
              </span>
            <?php else: ?>
              <span class="v-badge v-pending" title="<?= te('adm.pending_tip') ?>">
                ⏳ <?= te('adm.pending') ?>
              </span>
              <button type="button" class="btn au-verify"
                      title="<?= te('adm.verify_now_tip') ?>">
                <?= te('adm.verify_now') ?>
              </button>
            <?php endif; ?>
          </td>
          <td data-sort="<?= au_e($u['role'] ?? 'user') ?>">
            <select class="au-role" <?= $isSelf ? 'disabled title="' . te('adm.own_role_tip') . '"' : '' ?>>
              <option value="user"  <?= $u['role']==='user'  ? 'selected':''; ?>><?= te('adm.role_user') ?></option>
              <option value="admin" <?= $u['role']==='admin' ? 'selected':''; ?>><?= te('adm.role_admin') ?></option>
            </select>
          </td>
          <td class="u-date" data-sort="<?= $u['created_at'] ? date('Y-m-d H:i:s', strtotime($u['created_at'])) : '' ?>"><?= $u['created_at'] ? date('Y-m-d', strtotime($u['created_at'])) : '' ?></td>
          <td class="u-date" data-sort="<?= !empty($u['last_login_at']) ? date('Y-m-d H:i:s', strtotime($u['last_login_at'])) : '' ?>"><?= !empty($u['last_login_at']) ? date('Y-m-d H:i', strtotime($u['last_login_at'])) : '—' ?></td>
          <td class="u-actions">
            <?php if (!$isSelf): ?>
              <a class="btn" href="/admin/users/<?= (int)$u['id'] ?>/impersonate"
                 title="<?= te('adm.view_as_tip') ?>">👁 <?= te('adm.view_as') ?></a>
            <?php endif; ?>
            <button type="button" class="btn au-pass" title="<?= te('adm.reset_pass_tip') ?>"><?= te('adm.reset_pass') ?></button>
            <?php if (!$isSelf): ?>
              <button type="button" class="btn au-transfer"
                      title="<?= te('adm.transfer_tip') ?>"><?= te('adm.transfer') ?></button>
              <?php $isDeact = !empty($u['deactivated_at']); ?>
              <button type="button" class="btn au-deact" data-on="<?= $isDeact ? '1' : '0' ?>"
                      title="<?= $isDeact ? te('adm.reactivate_tip') : te('adm.deactivate_tip') ?>"
                      style="color:<?= $isDeact ? '#7fc98d' : '#e8c889' ?>;">
                <?= $isDeact ? te('adm.reactivate') : te('adm.deactivate') ?>
              </button>
              <button type="button" class="btn au-del" style="color:#f08792;"><?= te('action.delete') ?></button>
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
  const T = <?= json_encode([
    'sort_by'       => t('adm.sort_by'),
    'saving'        => t('status.saving'),
    'saved'         => t('status.saved'),
    'failed'        => t('common.failed'),
    'net_error'     => t('status.net_error'),
    'this_account'  => t('adm.this_account'),
    'confirm_verify'=> t('adm.confirm_verify'),
    'verified'      => t('adm.verified'),
    'pass_prompt'   => t('adm.pass_prompt'),
    'transfer_ask'  => t('adm.transfer_ask'),
    'transfer_deact'=> t('adm.transfer_deact'),
    'transfer_done' => t('adm.transfer_done'),
    'transfer_old'  => t('adm.transfer_old'),
    'confirm_block' => t('adm.confirm_block'),
    'confirm_del'   => t('adm.confirm_del'),
    'hist_ask'      => t('adm.hist_ask'),
  ], JSON_UNESCAPED_UNICODE) ?>;
  // Every write below carries this. Without it the six user-management
  // endpoints would accept a POST from any page the admin happens to visit.
  const CSRF = <?= json_encode(csrf_token()) ?>;

  // ── Column sorting ────────────────────────────────────────────────
  // Sorts on each cell's data-sort value rather than its rendered text, so
  // badges, selects and "—" placeholders order by their real meaning:
  // Email → Pending first (who needs attention), dates → true chronology,
  // never-logged-in → always last regardless of direction.
  const tbody = table.querySelector('tbody');
  function sortBy(th) {
    const col  = +th.dataset.col;
    const type = th.dataset.type;
    const asc  = !th.classList.contains('sorted-asc');

    table.querySelectorAll('th.au-sort')
         .forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
    th.classList.add(asc ? 'sorted-asc' : 'sorted-desc');

    const val = tr => {
      const cell = tr.children[col];
      const raw  = cell ? (cell.dataset.sort ?? cell.textContent.trim()) : '';
      return type === 'num' ? parseFloat(raw || '0') || 0 : raw.toLowerCase();
    };

    [...tbody.querySelectorAll('tr')]
      .sort((a, b) => {
        const A = val(a), B = val(b);
        // Empty (never logged in / no date) always sinks to the bottom
        const aEmpty = A === '' , bEmpty = B === '';
        if (aEmpty !== bEmpty) return aEmpty ? 1 : -1;
        if (A === B) return 0;
        return (A < B ? -1 : 1) * (asc ? 1 : -1);
      })
      .forEach(tr => tbody.appendChild(tr));  // re-append = reorder, keeps listeners
  }
  table.querySelectorAll('th.au-sort').forEach(th => {
    th.title = T.sort_by + ' ' + th.textContent.trim();
    th.addEventListener('click', () => sortBy(th));
  });

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
    status.textContent = T.saving;
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ ...params, csrf_token: CSRF }).toString()
      });
      const j = await res.json();
      // Returns the parsed response, not a bare true. Every caller tests it
      // for truthiness, so nothing breaks — but the two that read fields off
      // it (transfer's "moved :n boards", deactivate's reload) were reading
      // them off the boolean `true` and silently never firing.
      if (j && j.success) { status.textContent = T.saved; return j; }
      status.textContent = '⚠ ' + (j?.error || T.failed);
      return null;
    } catch {
      status.textContent = '⚠ ' + T.net_error;
      return null;
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
      const email = row.querySelector('.u-mail')?.textContent?.trim() || T.this_account;
      if (!confirm(T.confirm_verify.replace(':who', email))) return;
      if (await post(`/admin/users/${id}/verify`, {})) {
        row.querySelector('.u-verified').innerHTML =
          '<span class="v-badge v-ok">✓ ' + T.verified + '</span>';
      }
      return;
    }
    if (e.target.closest('.au-pass')) {
      const pass = prompt(T.pass_prompt);
      if (pass === null) return;
      await post(`/admin/users/${id}/password`, { password: pass });
      return;
    }
    if (e.target.closest('.au-transfer')) {
      const who = row.querySelector('.u-mail')?.textContent?.trim() || T.this_account;
      const toEmail = prompt(T.transfer_ask.replace(':who', who));
      if (!toEmail) return;
      const deact = confirm(T.transfer_deact.replace(':who', who));
      const j = await post(`/admin/users/${id}/transfer`, { to_email: toEmail.trim(), deactivate: deact ? 1 : 0 });
      if (j && j.success) {
        alert(T.transfer_done.replace(':n', j.moved).replace(':to', j.to)
              + (j.deactivated ? '\n' + T.transfer_old : ''));
        location.reload();
      }
      return;
    }
    if (e.target.closest('.au-deact')) {
      const btn = e.target.closest('.au-deact');
      const turningOn = btn.dataset.on !== '1';   // on = deactivated
      const who = row.querySelector('.u-mail')?.textContent?.trim() || T.this_account;
      // The prompt IS the confirmation — Cancel aborts, OK goes ahead with
      // whatever was typed. One dialog instead of two, and no ambiguity about
      // what cancelling a second one would have meant.
      const head = turningOn ? T.confirm_block.replace(':who', who) + '\n\n' : '';
      const note = prompt(head + T.hist_ask, '');
      if (note === null) return;
      const j = await post(`/admin/users/${id}/deactivate`,
                           { on: turningOn ? 1 : 0, note: note });
      if (j && j.success) location.reload();
      return;
    }
    if (e.target.closest('.au-del')) {
      const email = row.querySelector('.u-mail')?.textContent?.trim() || T.this_account;
      if (!confirm(T.confirm_del.replace(':who', email))) return;
      if (await post(`/admin/users/${id}/delete`, {})) row.remove();
    }
  });

  // ── Internal history ──────────────────────────────────────────────
  // The panel's HTML is rendered by the server (views/admin_user_history.php)
  // so dates and labels stay translated in one place. Reopening it after a
  // write is the cheapest correct way to refresh — the list is small.
  const ov    = document.getElementById('uhOverlay');
  const panel = document.getElementById('uhPanel');

  function closeHist() { ov.hidden = true; panel.innerHTML = ''; }

  async function openHist(id) {
    status.textContent = T.saving;
    try {
      const res = await fetch(`/admin/users/${id}/history`);
      if (!res.ok) { status.textContent = '⚠ ' + T.failed; return; }
      panel.innerHTML = await res.text();
      ov.hidden = false;
      status.textContent = '';
      panel.querySelector('textarea')?.focus();
    } catch { status.textContent = '⚠ ' + T.net_error; }
  }

  // Keep the badge honest without a page reload.
  function bumpCount(id) {
    const b = table.querySelector(`tr[data-id="${id}"] .au-hist`);
    if (!b) return;
    b.dataset.n = (parseInt(b.dataset.n, 10) || 0) + 1;
    b.classList.add('has');
  }

  table.addEventListener('click', e => {
    const b = e.target.closest('.au-hist');
    if (b) openHist(b.closest('tr').dataset.id);
  });

  ov.addEventListener('click', e => {
    if (e.target === ov || e.target.closest('.uh-close')) closeHist();
  });

  ov.addEventListener('submit', async e => {
    const form = e.target.closest('.uh-add');
    if (!form) return;
    e.preventDefault();
    const ta   = form.querySelector('textarea');
    const note = (ta.value || '').trim();
    if (!note) { ta.focus(); return; }
    const id = form.dataset.id;
    if (await post(`/admin/users/${id}/note`, { note })) {
      bumpCount(id);
      openHist(id);
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !ov.hidden) closeHist();
  });
})();
</script>
