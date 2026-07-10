<?php
// views/partials/overlay_roles.php
// Expects: $vision (id, slug)
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
?>

<div class="overlay-header">
  <h2>Roles &amp; Permissions</h2>
</div>

<div id="rolesWrap" data-slug="<?= $slug ?>">
  <div id="rolesList" class="roles-list"><div class="muted" style="opacity:.6;">Loading…</div></div>

  <div id="roleAddCard" class="card" hidden style="margin-top:1rem;">
    <h4 style="margin:.2rem 0 .6rem;">Add collaborator</h4>
    <input id="roleEmail" type="text" placeholder="Their account email…" autocomplete="off">
    <select id="roleSelect">
      <option value="viewer">Viewer — read-only</option>
      <option value="editor">Editor — can modify content</option>
      <option value="co_owner">Co-owner — full control incl. sharing</option>
      <option value="delegate">Delegate — acts on behalf of the owner</option>
    </select>
    <div style="display:flex;align-items:center;gap:.6rem;margin-top:.6rem;">
      <button type="button" class="btn btn-primary" id="btnRoleAdd">Add</button>
      <span id="roleStatus" style="opacity:.6;font-size:.85em;"></span>
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
  #rolesWrap #roleEmail, #rolesWrap #roleSelect {
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

  const ROLE_LABELS = {
    owner:'Owner', co_owner:'Co-owner', editor:'Editor',
    viewer:'Viewer', delegate:'Delegate'
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
    list.innerHTML = rows || '<div class="muted" style="opacity:.6;">No members.</div>';
  }

  async function load() {
    try {
      const res = await fetch(`/api/visions/${slug}/roles`);
      render(await res.json());
    } catch {
      list.innerHTML = '<div class="error">Failed to load members.</div>';
    }
  }

  addBtn?.addEventListener('click', async () => {
    const em = email.value.trim();
    if (!em) { status.textContent = 'Enter an email.'; return; }
    status.textContent = 'Adding…';
    try {
      const p = new URLSearchParams();
      p.set('email', em);
      p.set('role', roleSel.value);
      const res = await fetch(`/api/visions/${slug}/roles/add`, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: p.toString()
      });
      const j = await res.json();
      if (j && j.success) {
        status.textContent = 'Added';
        email.value = '';
        load();
      } else {
        status.textContent = '⚠ ' + (j?.error || 'Add failed');
      }
    } catch { status.textContent = '⚠ Network error'; }
  });
  email?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addBtn.click(); } });

  list.addEventListener('change', async e => {
    const sel = e.target.closest('.role-set');
    if (!sel) return;
    const p = new URLSearchParams(); p.set('role', sel.value);
    const res = await fetch(`/api/visions/${slug}/roles/${sel.dataset.id}`, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: p.toString()
    });
    const j = await res.json();
    if (!j?.success) alert(j?.error || 'Update failed');
  });

  list.addEventListener('click', async e => {
    const btn = e.target.closest('.role-remove');
    if (!btn) return;
    if (!confirm('Remove this collaborator?')) return;
    const res = await fetch(`/api/visions/${slug}/roles/${btn.dataset.id}/delete`, { method:'DELETE' });
    const j = await res.json();
    if (j?.success) load();
    else alert(j?.error || 'Remove failed');
  });

  load();
})();
</script>
