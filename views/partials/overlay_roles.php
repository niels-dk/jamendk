<?php
// views/partials/overlay_roles.php
// Expects: $vision (id, slug)
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
?>

<div class="overlay-header">
  <h2><?= te('sec.roles') ?></h2>
</div>

<div id="rolesWrap" data-slug="<?= $slug ?>">
  <div id="rolesList" class="roles-list"><div class="muted" style="opacity:.6;"><?= te('action.loading') ?></div></div>

  <div id="roleAddCard" class="card" hidden style="margin-top:1rem;">
    <h4 style="margin:.2rem 0 .6rem;"><?= te('roles.add_collab') ?></h4>
    <input id="roleEmail" type="text" placeholder="<?= te('roles.email_placeholder') ?>" autocomplete="off">
    <select id="roleSelect">
      <option value="viewer"><?= te('roles.viewer') ?></option>
      <option value="editor"><?= te('roles.editor') ?></option>
      <option value="co_owner"><?= te('roles.co_owner') ?></option>
      <option value="delegate"><?= te('roles.delegate') ?></option>
    </select>
    <div style="display:flex;align-items:center;gap:.6rem;margin-top:.6rem;">
      <button type="button" class="btn btn-primary" id="btnRoleAdd"><?= te('action.add') ?></button>
      <span id="roleStatus" style="opacity:.6;font-size:.85em;"></span>
    </div>
  </div>

  <div id="teamAddCard" class="card" hidden style="margin-top:.8rem;">
    <h4 style="margin:.2rem 0 .6rem;"><?= te('roles.add_team') ?></h4>
    <select id="teamPick"></select>
    <div style="display:flex;align-items:center;gap:.6rem;margin-top:.6rem;">
      <button type="button" class="btn btn-primary" id="btnTeamAdd"><?= te('action.add') ?></button>
      <span id="teamStatus" style="opacity:.6;font-size:.85em;"></span>
      <a href="/teams" style="margin-left:auto;font-size:.82em;color:#8fb1d8;"><?= te('roles.manage_teams') ?></a>
    </div>
  </div>
</div>

<style>
  #rolesWrap .roles-list { display:flex; flex-direction:column; gap:.5rem; }
  #rolesWrap .role-row {
    display:flex; align-items:center; justify-content:space-between; gap:.6rem;
    padding:.6rem .7rem; background:rgba(255,255,255,.04);
    border:1px solid #2b3346; border-radius:8px;
  }
  #rolesWrap .role-row .who { min-width:0; flex:1; }
  #rolesWrap .role-row .who .name { font-weight:600; color:#eaeaea; }
  #rolesWrap .role-row .who .mail {
    font-size:.82em; opacity:.65;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  #rolesWrap .role-row select {
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.35rem .5rem; border-radius:6px;
  }
  #rolesWrap .owner-pill {
    display:inline-block; padding:.15rem .6rem; border-radius:999px;
    background:#1f3a66; color:#8fb1d8; font-size:.78rem; font-weight:700;
  }
  #rolesWrap .role-remove {
    background:transparent; border:0; color:#aaa; font-size:1.15rem;
    cursor:pointer; padding:0 .35rem;
  }
  #rolesWrap .role-remove:hover { color:#f08792; }
  #rolesWrap #roleEmail, #rolesWrap #roleSelect, #rolesWrap #teamPick {
    width:100%; box-sizing:border-box;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.5rem .7rem; border-radius:8px; margin-bottom:.5rem;
  }
</style>

<script>
(() => {
  const wrap    = document.getElementById('rolesWrap');
  if (!wrap) return;
  const slug    = wrap.dataset.slug;
  const list    = wrap.querySelector('#rolesList');
  const addCard = wrap.querySelector('#roleAddCard');
  const email   = wrap.querySelector('#roleEmail');
  const roleSel = wrap.querySelector('#roleSelect');
  const addBtn  = wrap.querySelector('#btnRoleAdd');
  const status  = wrap.querySelector('#roleStatus');

  const T = <?= json_encode([
      'owner'=>t('roles.owner'),'co_owner_s'=>t('roles.co_owner_short'),
      'editor_s'=>t('roles.editor_short'),'viewer_s'=>t('roles.viewer_short'),
      'delegate_s'=>t('roles.delegate_short'),
      'no_members'=>t('roles.no_members'),'load_failed'=>t('roles.load_failed'),
      'enter_email'=>t('roles.enter_email'),'adding'=>t('roles.adding'),
      'added'=>t('roles.added'),'add_failed'=>t('roles.add_failed'),
      'unknown'=>t('roles.unknown_email'),'cancelled'=>t('roles.cancelled'),
      'confirm_remove'=>t('roles.confirm_remove'),'remove_failed'=>t('roles.remove_failed'),
      'update_failed'=>t('roles.update_failed'),'whole_team'=>t('roles.whole_team'),
      'already_on'=>t('roles.already_on'),'failed'=>t('status.save_failed'),
      'net_error'=>t('status.net_error'),
  ], JSON_UNESCAPED_UNICODE) ?>;
  const ROLE_LABELS = {
    owner:T.owner, co_owner:T.co_owner_s, editor:T.editor_s,
    viewer:T.viewer_s, delegate:T.delegate_s
  };

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function roleOptions(current) {
    return ['co_owner','editor','viewer','delegate'].map(r =>
      `<option value="${r}" ${r===current?'selected':''}>${ROLE_LABELS[r]}</option>`
    ).join('');
  }

  function render(data) {
    const canManage = !!data.can_manage;
    addCard.hidden = !canManage;
    if (canManage) loadTeams();
    const rows = (data.members || []).map(m => {
      const control = m.role === 'owner'
        ? `<span class="owner-pill">Owner</span>`
        : canManage
          ? `<select class="role-set" data-id="${m.id}">${roleOptions(m.role)}</select>
             <button type="button" class="role-remove" data-id="${m.id}" title="Remove">×</button>`
          : `<span class="owner-pill" style="background:#2a2d35;color:#bbb;">${ROLE_LABELS[m.role] || m.role}</span>`;
      return `
        <div class="role-row">
          <div class="who">
            <div class="name">${esc(m.name || '(no name)')}</div>
            <div class="mail">${esc(m.email || '')}</div>
          </div>
          <div style="display:flex;align-items:center;gap:.35rem;">${control}</div>
        </div>`;
    }).join('');
    list.innerHTML = rows || '<div class="muted" style="opacity:.6;">' + T.no_members + '</div>';
  }

  async function load() {
    try {
      const res = await fetch(`/api/visions/${slug}/roles`);
      render(await res.json());
    } catch {
      list.innerHTML = '<div class="error">' + T.load_failed + '</div>';
    }
  }

  // ack=true resends past the tier heads-up. Everything is free right now, so
  // the prompt informs and pre-educates — it never blocks.
  async function postAddRole(em, ack, role) {
    const p = new URLSearchParams();
    p.set('email', em);
    p.set('role', role || roleSel.value);
    if (ack) p.set('ack_tier', '1');
    const res = await fetch(`/api/visions/${slug}/roles/add`, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: p.toString()
    });
    return res.json();
  }

  addBtn?.addEventListener('click', async () => {
    const em = email.value.trim();
    if (!em) { status.textContent = T.enter_email; return; }
    status.textContent = T.adding;
    try {
      let j = await postAddRole(em, false);
      if (j && j.needs_tier_ack) {
        if (!confirm(j.message)) { status.textContent = T.cancelled; return; }
        status.textContent = 'Adding…';
        j = await postAddRole(em, true);
      }
      if (j && j.success && j.unknown) {
        // Ambiguous on purpose — never confirm whether an account exists
        status.textContent = '⚠ ' + T.unknown;
        email.value = '';
        load();
      } else if (j && j.success) {
        status.textContent = T.added;
        email.value = '';
        load();
      } else {
        status.textContent = '⚠ ' + (j?.error || T.add_failed);
      }
    } catch { status.textContent = '⚠ ' + T.net_error; }
  });
  email?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addBtn.click(); } });

  list.addEventListener('change', async e => {
    const sel = e.target.closest('.role-set');
    if (!sel) return;
    async function setRole(ack) {
      const p = new URLSearchParams(); p.set('role', sel.value);
      if (ack) p.set('ack_tier', '1');
      const res = await fetch(`/api/visions/${slug}/roles/${sel.dataset.id}`, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: p.toString()
      });
      return res.json();
    }
    let j = await setRole(false);
    // Upgrading a viewer to a working role can add a seat → confirm the tier
    if (j && j.needs_tier_ack) {
      if (!confirm(j.message)) { load(); return; }  // reload resets the select
      j = await setRole(true);
    }
    if (!j?.success) alert(j?.error || T.update_failed);
  });

  list.addEventListener('click', async e => {
    const btn = e.target.closest('.role-remove');
    if (!btn) return;
    if (!confirm(T.confirm_remove)) return;
    const res = await fetch(`/api/visions/${slug}/roles/${btn.dataset.id}/delete`, { method:'DELETE' });
    const j = await res.json();
    if (j?.success) load();
    else alert(j?.error || T.remove_failed);
  });

  // ── Teams integration: pick a member (with their default role) or a whole team ──
  const teamCard   = wrap.querySelector('#teamAddCard');
  const teamPick   = wrap.querySelector('#teamPick');
  const teamAddBtn = wrap.querySelector('#btnTeamAdd');
  const teamStatus = wrap.querySelector('#teamStatus');
  let teamsData = [];
  let teamsLoaded = false;

  async function loadTeams() {
    if (teamsLoaded) return;
    teamsLoaded = true;
    try {
      const res = await fetch('/api/teams');
      const j = await res.json();
      teamsData = j?.teams || [];
    } catch { teamsData = []; }
    if (!teamsData.length) return; // no teams → keep the card hidden
    teamPick.innerHTML = teamsData.map((t, ti) => {
      const opts = [
        `<option value="team:${t.id}">➕ Whole team — ${esc(t.name)} (${t.members.length})</option>`,
        ...t.members.map((m, mi) =>
          `<option value="member:${ti}:${mi}">${esc(m.name || m.email)} — ${ROLE_LABELS[m.default_role] || m.default_role}</option>`)
      ].join('');
      return `<optgroup label="${esc(t.name)}">${opts}</optgroup>`;
    }).join('');
    teamCard.hidden = false;
  }

  async function postTeamAdd(teamId, ack) {
    const p = new URLSearchParams(); p.set('team_id', teamId);
    if (ack) p.set('ack_tier', '1');
    const res = await fetch(`/api/visions/${slug}/roles/add-team`, {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()
    });
    return res.json();
  }

  teamAddBtn?.addEventListener('click', async () => {
    const v = teamPick.value;
    if (!v) return;
    teamStatus.textContent = T.adding;
    try {
      if (v.startsWith('team:')) {
        const teamId = v.slice(5);
        let j = await postTeamAdd(teamId, false);
        if (j && j.needs_tier_ack) {
          if (!confirm(j.message)) { teamStatus.textContent = T.cancelled; return; }
          teamStatus.textContent = T.adding;
          j = await postTeamAdd(teamId, true);
        }
        if (j?.success) {
          teamStatus.textContent = `Added ${j.added}` + (j.skipped ? ` (${j.skipped} already on the board)` : '');
          load();
        } else teamStatus.textContent = '⚠ ' + (j?.error || 'Failed');
      } else {
        const [, ti, mi] = v.split(':');
        const m = teamsData[+ti]?.members[+mi];
        if (!m) return;
        let j = await postAddRole(m.email, false, m.default_role);
        if (j && j.needs_tier_ack) {
          if (!confirm(j.message)) { teamStatus.textContent = T.cancelled; return; }
          j = await postAddRole(m.email, true, m.default_role);
        }
        if (j?.success) { teamStatus.textContent = T.added; load(); }
        else teamStatus.textContent = '⚠ ' + (j?.error || 'Failed');
      }
    } catch { teamStatus.textContent = '⚠ Network error'; }
  });

  load();
})();
</script>
