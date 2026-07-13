<?php
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$goalsUid = (int)($currentUserId ?? ($GLOBALS['currentUserId'] ?? 0));
?>

<div class="overlay-header">
  <h2>Goals &amp; Milestones</h2>
</div>

<div id="goalsWrap" data-slug="<?= $slug ?>" data-uid="<?= $goalsUid ?>">
  <div id="goalsList" class="goals-list"></div>
  <button type="button" id="btnAddGoal" class="btn btn-primary">+ Add goal</button>

  <div id="goalFormCard" class="card" hidden style="margin-top:1rem;">
    <form id="goalForm" class="goal-form">
      <input type="hidden" name="goal_id" value="">

      <label for="goalTitle">Title</label>
      <input id="goalTitle" name="title" type="text" placeholder="What needs to happen?">

      <label for="goalDescription">Description</label>
      <textarea id="goalDescription" name="description" rows="2" placeholder="Optional context, links, why this matters…"></textarea>

      <div class="goal-meta-row">
        <div>
          <label for="goalStatus">Status</label>
          <select id="goalStatus" name="status">
            <option value="not_started">Not started</option>
            <option value="in_progress">In progress</option>
            <option value="awaiting">Awaiting</option>
            <option value="done">Done</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label for="goalPriority">Priority</label>
          <select id="goalPriority" name="priority">
            <option value="1">P1 — Urgent</option>
            <option value="2">P2 — High</option>
            <option value="3" selected>P3 — Normal</option>
            <option value="4">P4 — Low</option>
            <option value="5">P5 — Lowest</option>
          </select>
        </div>
        <div>
          <label for="goalDue">Due date</label>
          <input id="goalDue" name="due_date" type="date">
        </div>
      </div>

      <label for="goalAssignee" style="font-size:.8em;opacity:.7;">Assign to</label>
      <select id="goalAssignee" name="assigned_user_id">
        <option value="">— Unassigned —</option>
      </select>

      <label class="switch switch-row" style="margin-top:.6rem;">
        <span class="switch-label">Show on Trip layer</span>
        <input class="switch-input" type="checkbox" name="show_on_trip" checked>
        <span class="knob" aria-hidden="true"></span>
      </label>

      <h4 style="margin-top:1rem;">Milestones</h4>
      <div id="milestonesWrap"></div>
      <button type="button" id="btnAddMilestone" class="btn btn-secondary">+ Add milestone</button>

      <!-- Assignee actions (resolve when it's yours) / owner reopen -->
      <div id="goalAssignActions" style="margin-top:.9rem; display:none; gap:.5rem; flex-wrap:wrap;">
        <button type="button" class="btn" id="btnResolveGoal"
                style="background:#15351f;border:1px solid #1e5530;color:#7ed99a;">✓ Mark resolved</button>
        <button type="button" class="btn" id="btnReopenGoal" hidden>↺ Reopen</button>
      </div>

      <!-- Comments -->
      <h4 style="margin-top:1.1rem;">Comments</h4>
      <div id="goalComments" style="display:flex;flex-direction:column;gap:.4rem;margin-bottom:.5rem;"></div>
      <div style="display:flex;gap:.4rem;align-items:flex-start;">
        <textarea id="goalCommentBox" rows="2" placeholder="Write a comment…"
                  style="flex:1;background:#15161A;border:1px solid #2b3346;color:#ddd;
                         border-radius:6px;padding:.4rem .55rem;resize:vertical;margin:0;"></textarea>
        <button type="button" class="btn btn-primary" id="btnAddComment" style="flex-shrink:0;">Send</button>
      </div>

      <div style="margin-top:1rem; display:flex; align-items:center; gap:.6rem;">
        <button type="button" class="btn" id="btnCloseGoal">Close</button>
        <button type="button" class="btn btn-danger" id="btnDeleteGoal" hidden>Delete goal</button>
        <span id="goalSaveStatus" style="margin-left:auto;opacity:.6;font-size:.85em;"></span>
      </div>
    </form>
  </div>
</div>

<style>
  #goalsWrap .goals-list { display:flex; flex-direction:column; gap:.5rem; margin-bottom:.6rem; }
  #goalsWrap .goal-row {
    display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem;
    padding:.6rem .7rem; background:rgba(255,255,255,.04);
    border:1px solid #2b3346; border-radius:8px; cursor:pointer;
  }
  #goalsWrap .goal-row:hover { background:rgba(255,255,255,.06); }
  #goalsWrap .goal-row.is-done .goal-title { text-decoration:line-through; opacity:.6; }
  #goalsWrap .goal-row.is-cancelled .goal-title { opacity:.5; }
  #goalsWrap .goal-row.is-overdue { border-color:#7a2030; }

  #goalsWrap .goal-main { min-width:0; flex:1; }
  #goalsWrap .goal-title { font-weight:600; color:#eaeaea; }
  #goalsWrap .goal-meta {
    display:flex; flex-wrap:wrap; gap:.4rem; align-items:center;
    margin-top:.3rem; font-size:.78rem;
  }
  #goalsWrap .goal-date { opacity:.7; font-family:monospace; }
  #goalsWrap .goal-date.is-overdue { color:#f08792; }
  #goalsWrap .goal-progress { opacity:.7; }
  #goalsWrap .assignee-pill {
    display:inline-block; padding:.05rem .45rem; border-radius:999px;
    font-size:.7rem; background:#1f2533; color:#8fb1d8; border:1px solid #2b3f5f;
  }
  #goalsWrap .returned-pill {
    display:inline-block; padding:.05rem .45rem; border-radius:999px;
    font-size:.7rem; background:#3a2310; color:#e8b067; border:1px solid #5a3818;
  }

  /* Pills */
  #goalsWrap .pri-pill {
    display:inline-block; padding:.05rem .4rem; border-radius:999px;
    font-size:.7rem; font-weight:700; font-family:monospace;
    background:#2a2d35; color:#bbb; border:1px solid #3a3f4a;
  }
  #goalsWrap .pri-pill[data-p="1"] { background:#4a1626; color:#f08792; border-color:#7a2030; }
  #goalsWrap .pri-pill[data-p="2"] { background:#3a2310; color:#e8b067; border-color:#5a3818; }
  #goalsWrap .pri-pill[data-p="3"] { background:#15263a; color:#8fb1d8; border-color:#1e3a5a; }
  #goalsWrap .pri-pill[data-p="4"] { background:#2a2d35; color:#aaa;    border-color:#3a3f4a; }
  #goalsWrap .pri-pill[data-p="5"] { background:#1f2128; color:#888;    border-color:#2e323a; }

  #goalsWrap .stat-pill {
    display:inline-block; padding:.05rem .45rem; border-radius:999px;
    font-size:.7rem; border:1px solid transparent;
  }
  #goalsWrap .stat-pill[data-s="not_started"] { background:#2a2d35; color:#bbb; border-color:#3a3f4a; }
  #goalsWrap .stat-pill[data-s="in_progress"] { background:#15263a; color:#8fb1d8; border-color:#1f3a5a; }
  #goalsWrap .stat-pill[data-s="awaiting"]    { background:#3a2f10; color:#e8c267; border-color:#5a4818; }
  #goalsWrap .stat-pill[data-s="done"]        { background:#15351f; color:#7ed99a; border-color:#1e5530; }
  #goalsWrap .stat-pill[data-s="cancelled"]   { background:#1f2128; color:#777;    border-color:#2e323a; }

  /* Form */
  #goalForm input[type="text"], #goalForm textarea, #goalForm select, #goalForm input[type="date"] {
    width:100%; box-sizing:border-box;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.4rem .55rem; border-radius:6px;
    margin-bottom:.55rem;
  }
  #goalForm textarea { min-height:60px; resize:vertical; }
  #goalForm .goal-meta-row {
    display:grid; grid-template-columns: 1fr 1fr 1fr; gap:.5rem;
  }
  #goalForm .goal-meta-row label { font-size:.8em; opacity:.7; }
  #goalsWrap .milestone-row {
    display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; margin-bottom:.45rem;
  }
  #goalsWrap .milestone-row input[type="text"] {
    flex:1 1 150px; margin-bottom:0;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.35rem .5rem; border-radius:6px;
  }
  #goalsWrap .milestone-row .ms-due {
    width:132px; margin-bottom:0;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.3rem .4rem; border-radius:6px; font-size:.85em;
  }
  #goalsWrap .milestone-row .ms-assignee {
    flex:0 1 130px; margin-bottom:0;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.3rem .4rem; border-radius:6px; font-size:.85em;
  }
  #goalsWrap .milestone-row.is-done input[type="text"] { text-decoration:line-through; opacity:.6; }
  #goalsWrap .milestone-row .ms-remove {
    background:transparent; border:0; color:#aaa; font-size:1.1rem; cursor:pointer; padding:0 .3rem;
  }
  #goalsWrap .milestone-row .ms-remove:hover { color:#fff; }
</style>

<script>
(() => {
  const wrap     = document.getElementById('goalsWrap');
  if (!wrap) return;
  const slug     = wrap.dataset.slug;
  const myUid    = String(wrap.dataset.uid || '');
  const list     = wrap.querySelector('#goalsList');
  const card     = wrap.querySelector('#goalFormCard');
  const form     = wrap.querySelector('#goalForm');
  const msWrap   = wrap.querySelector('#milestonesWrap');
  const status   = wrap.querySelector('#goalSaveStatus');
  const addBtn   = wrap.querySelector('#btnAddGoal');
  const closeBtn = wrap.querySelector('#btnCloseGoal');
  const delBtn   = wrap.querySelector('#btnDeleteGoal');
  const addMs    = wrap.querySelector('#btnAddMilestone');
  const assigneeSel = wrap.querySelector('#goalAssignee');
  const assignActions = wrap.querySelector('#goalAssignActions');
  const resolveBtn = wrap.querySelector('#btnResolveGoal');
  const reopenBtn  = wrap.querySelector('#btnReopenGoal');
  const commentsBox = wrap.querySelector('#goalComments');
  const commentInput = wrap.querySelector('#goalCommentBox');
  const addCommentBtn = wrap.querySelector('#btnAddComment');

  const STATUS_LABELS = {
    not_started: 'Not started', in_progress: 'In progress',
    awaiting:    'Awaiting',    done:        'Done', cancelled: 'Cancelled'
  };
  const today = new Date().toISOString().slice(0,10);

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  // Board members (owner + collaborators) for the assignee dropdowns.
  // Grouped by team so same-named people are distinguishable; label shows email.
  let members = [];      // [{user_id, name, email}]
  let teamOf  = {};      // user_id -> [team names]

  function memberLabel(m) {
    return (m.name || m.email) + (m.email ? ` — ${m.email}` : '');
  }
  // Build <optgroup>-based options: one group per team the members belong to,
  // plus a "Not in a team" group. A member in several teams appears once,
  // under their first team (keeps the list short and unambiguous enough).
  function buildOptions(selected, placeholder) {
    const sel = String(selected ?? '');
    const groups = {};            // teamName -> [members]
    const loose  = [];            // members on no team
    const seen   = new Set();
    members.forEach(m => {
      if (seen.has(m.user_id)) return;
      seen.add(m.user_id);
      const teams = teamOf[m.user_id] || [];
      if (teams.length) (groups[teams[0]] ||= []).push(m);
      else loose.push(m);
    });
    const opt = m =>
      `<option value="${m.user_id}" ${String(m.user_id) === sel ? 'selected' : ''}>${escapeHtml(memberLabel(m))}</option>`;
    let html = `<option value="">${placeholder}</option>`;
    Object.keys(groups).sort().forEach(tn => {
      html += `<optgroup label="👥 ${escapeHtml(tn)}">${groups[tn].map(opt).join('')}</optgroup>`;
    });
    if (loose.length) {
      html += `<optgroup label="Not in a team">${loose.map(opt).join('')}</optgroup>`;
    }
    return html;
  }
  function memberOptions(selected) { return buildOptions(selected, '—'); }

  async function loadMembers() {
    try {
      const res = await fetch(`/api/visions/${slug}/roles`);
      const j   = await res.json();
      members = (j?.members || []).map(m => ({ user_id: m.user_id, name: m.name, email: m.email }));
    } catch { members = []; }
    // Cross-reference the current user's teams to tag each board member
    try {
      const tr = await fetch('/api/teams');
      const tj = await tr.json();
      (tj?.teams || []).forEach(t => {
        (t.members || []).forEach(mm => {
          (teamOf[mm.user_id] ||= []).push(t.name);
        });
      });
    } catch { /* teams optional */ }
    assigneeSel.innerHTML = buildOptions('', '— Unassigned —');
  }

  function msRow(text = '', done = false, due = '', assignee = '') {
    return `
      <div class="milestone-row ${done?'is-done':''}">
        <input type="checkbox" class="ms-done" ${done?'checked':''}>
        <input type="text" class="ms-text" value="${escapeHtml(text)}" placeholder="Milestone…">
        <input type="date" class="ms-due" value="${escapeHtml(due || '')}" title="Milestone due date">
        <select class="ms-assignee" title="Assign this milestone">${memberOptions(assignee)}</select>
        <button type="button" class="ms-remove" aria-label="Remove">×</button>
      </div>`;
  }
  function addMsRow(t='', d=false, due='', assignee='') { msWrap.insertAdjacentHTML('beforeend', msRow(t, d, due, assignee)); }

  function clearForm() {
    form.reset();
    form.querySelector('[name="goal_id"]').value = '';
    msWrap.innerHTML = '';
    status.textContent = '';
    delBtn.hidden = true;
    if (assignActions) assignActions.style.display = 'none';
    if (commentsBox) commentsBox.innerHTML = '';
    if (commentInput) commentInput.value = '';
  }

  // ── Comments ──
  function renderComments(rows) {
    if (!commentsBox) return;
    if (!rows || !rows.length) {
      commentsBox.innerHTML = '<div style="opacity:.5;font-size:.85em;">No comments yet.</div>';
      return;
    }
    commentsBox.innerHTML = rows.map(c => {
      const mine = String(c.user_id) === myUid;
      const when = (c.created_at || '').replace('T',' ').slice(0,16);
      return `
        <div style="padding:.45rem .6rem;border-radius:8px;
                    background:${mine ? 'rgba(58,118,210,.14)' : 'rgba(255,255,255,.04)'};
                    border:1px solid ${mine ? 'rgba(58,118,210,.35)' : '#2b3346'};">
          <div style="font-size:.75em;opacity:.6;margin-bottom:.15rem;">
            ${escapeHtml(c.author || 'User')} · ${escapeHtml(when)}
          </div>
          <div style="font-size:.9em;white-space:pre-wrap;">${escapeHtml(c.body || '')}</div>
        </div>`;
    }).join('');
    commentsBox.scrollTop = commentsBox.scrollHeight;
  }
  async function loadComments(gid) {
    if (!gid) { renderComments([]); return; }
    try {
      const res = await fetch(`/api/visions/${slug}/goals/${gid}/comments`);
      const j = await res.json();
      renderComments(j?.comments || []);
    } catch { renderComments([]); }
  }
  addCommentBtn?.addEventListener('click', async () => {
    const gid = form.querySelector('[name="goal_id"]').value.trim();
    const body = commentInput.value.trim();
    if (!gid) { alert('Save the goal first, then comment.'); return; }
    if (!body) return;
    addCommentBtn.disabled = true;
    try {
      const p = new URLSearchParams(); p.set('body', body);
      const res = await fetch(`/api/visions/${slug}/goals/${gid}/comments/add`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()
      });
      const j = await res.json();
      if (j?.success) { commentInput.value = ''; loadComments(gid); }
      else alert(j?.error || 'Comment failed');
    } catch { alert('Network error'); }
    finally { addCommentBtn.disabled = false; }
  });

  // ── Resolve / reopen ──
  function updateAssignActions(g) {
    if (!assignActions) return;
    const gid = g.id;
    const iAmAssignee = String(g.assigned_user_id ?? '') === myUid && myUid !== '';
    const isDone = g.status === 'done' || g.assignment_status === 'resolved';
    // Show resolve to the assignee while it's still open; reopen to anyone with edit rights once done.
    let show = false;
    if (iAmAssignee && !isDone) { resolveBtn.hidden = false; show = true; } else { resolveBtn.hidden = true; }
    if (isDone) { reopenBtn.hidden = false; show = true; } else { reopenBtn.hidden = true; }
    assignActions.style.display = show ? 'flex' : 'none';
  }
  async function goalAction(kind) {
    const gid = form.querySelector('[name="goal_id"]').value.trim();
    if (!gid) return;
    const note = (kind === 'resolve')
      ? (prompt('Add a note for the assigner (optional):') ?? '')
      : '';
    if (kind === 'resolve' && note === null) return;
    try {
      const p = new URLSearchParams(); if (note) p.set('note', note);
      const res = await fetch(`/api/visions/${slug}/goals/${gid}/${kind}`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()
      });
      const j = await res.json();
      if (j?.success) { hideForm(); loadList(); }
      else alert(j?.error || 'Action failed');
    } catch { alert('Network error'); }
  }
  resolveBtn?.addEventListener('click', () => goalAction('resolve'));
  reopenBtn?.addEventListener('click', () => goalAction('reopen'));
  function showForm() { card.hidden = false; card.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
  function hideForm() { card.hidden = true; clearForm(); }

  function renderList(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      list.innerHTML = '<div class="muted" style="opacity:.6;">No goals yet. Add one to get started.</div>';
      return;
    }
    list.innerHTML = rows.map(g => {
      const total = +g.milestone_total || 0;
      const done  = +g.milestone_done  || 0;
      const pct   = total ? Math.round((done/total)*100) : null;
      const progress = total ? `${done}/${total} · ${pct}%` : '—';
      const active  = g.status !== 'done' && g.status !== 'cancelled';
      const overdue = g.due_date && g.due_date < today && active;
      const msDue   = g.next_milestone_due;
      const msOverdue = msDue && msDue < today && active;
      const cls = [
        g.status === 'done' ? 'is-done' : '',
        g.status === 'cancelled' ? 'is-cancelled' : '',
        (overdue || msOverdue) ? 'is-overdue' : '',
      ].filter(Boolean).join(' ');
      const dateLabel = g.due_date
        ? `<span class="goal-date ${overdue?'is-overdue':''}">📅 ${g.due_date}</span>`
        : '';
      const msDueLabel = (msDue && active)
        ? `<span class="goal-date ${msOverdue?'is-overdue':''}">⏳ next ${msDue}</span>`
        : '';
      const assignee = g.assignee_name
        ? `<span class="assignee-pill" title="${escapeHtml(g.assignee_email || '')}">👤 ${escapeHtml(g.assignee_name)}</span>`
        : '';
      const returned = (g.assignment_status === 'returned' && active)
        ? `<span class="returned-pill">↩ Returned</span>`
        : '';
      const resolved = (g.assignment_status === 'resolved')
        ? `<span class="assignee-pill" style="background:#15351f;color:#7ed99a;border-color:#1e5530;">✓ Resolved</span>`
        : '';
      return `
        <div class="goal-row ${cls}" data-id="${g.id}">
          <div class="goal-main">
            <div class="goal-title">${escapeHtml(g.title || '(untitled)')}</div>
            <div class="goal-meta">
              <span class="pri-pill" data-p="${g.priority}">P${g.priority}</span>
              <span class="stat-pill" data-s="${g.status}">${STATUS_LABELS[g.status] || g.status}</span>
              ${assignee}
              ${returned}
              ${resolved}
              ${dateLabel}
              ${msDueLabel}
              <span class="goal-progress">${progress}</span>
            </div>
          </div>
        </div>`;
    }).join('');
  }

  function loadList() {
    fetch(`/api/visions/${slug}/goals`)
      .then(r => r.json()).then(renderList)
      .catch(() => list.innerHTML = '<div class="error">Failed to load goals.</div>');
  }

  function collectFormData() {
    const fd = new FormData();
    fd.append('title',       form.title.value);
    fd.append('description', form.description.value);
    fd.append('status',      form.status.value);
    fd.append('priority',    form.priority.value);
    fd.append('due_date',    form.due_date.value);
    fd.append('assigned_user_id', assigneeSel.value || '');
    fd.append('show_on_trip', form.show_on_trip.checked ? '1' : '0');
    msWrap.querySelectorAll('.milestone-row').forEach(row => {
      fd.append('milestone_texts[]',     row.querySelector('.ms-text').value);
      fd.append('milestone_dones[]',     row.querySelector('.ms-done').checked ? '1' : '');
      fd.append('milestone_dues[]',      row.querySelector('.ms-due')?.value || '');
      fd.append('milestone_assignees[]', row.querySelector('.ms-assignee')?.value || '');
    });
    return fd;
  }

  let saveTimer;
  function autoSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(async () => {
      if (!form.title.value.trim()) { status.textContent = ''; return; }
      const gid = form.querySelector('[name="goal_id"]').value.trim();
      const url = gid
        ? `/api/visions/${slug}/goals/${gid}`
        : `/api/visions/${slug}/goals/create`;
      status.textContent = 'Saving…';
      try {
        const res = await fetch(url, { method:'POST', body: collectFormData() });
        const j   = await res.json();
        if (j && j.success) {
          if (!gid && j.goal_id) {
            form.querySelector('[name="goal_id"]').value = j.goal_id;
            delBtn.hidden = false;
          }
          status.textContent = 'Saved';
          loadList();
        } else {
          status.textContent = '⚠ ' + (j?.error || 'Save failed');
        }
      } catch (e) {
        status.textContent = '⚠ Network error';
        console.error(e);
      }
    }, 500);
  }

  addBtn.addEventListener('click', () => { clearForm(); showForm(); form.title.focus(); });
  closeBtn.addEventListener('click', hideForm);

  addMs.addEventListener('click', () => { addMsRow(''); });

  msWrap.addEventListener('click', e => {
    if (e.target.closest('.ms-remove')) {
      e.target.closest('.milestone-row').remove();
      autoSave();
    }
  });
  msWrap.addEventListener('change', e => {
    if (e.target.classList.contains('ms-done')) {
      e.target.closest('.milestone-row').classList.toggle('is-done', e.target.checked);
    }
    autoSave();
  });
  msWrap.addEventListener('input', autoSave);

  ['title','description','status','priority','due_date','assigned_user_id'].forEach(name => {
    const el = form.querySelector(`[name="${name}"]`);
    if (!el) return;
    el.addEventListener('change', autoSave);
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.addEventListener('input', autoSave);
  });

  delBtn.addEventListener('click', async () => {
    const gid = form.querySelector('[name="goal_id"]').value.trim();
    if (!gid) return;
    if (!confirm('Delete this goal and its milestones?')) return;
    try {
      const res = await fetch(`/api/visions/${slug}/goals/${gid}/delete`, { method:'DELETE' });
      const j   = await res.json();
      if (j && j.success) { hideForm(); loadList(); }
      else alert(j?.error || 'Delete failed');
    } catch { alert('Delete failed'); }
  });

  list.addEventListener('click', async e => {
    const row = e.target.closest('.goal-row');
    if (!row) return;
    const id = row.dataset.id;
    try {
      const res = await fetch(`/api/visions/${slug}/goals/${id}/get`);
      const g   = await res.json();
      clearForm();
      form.querySelector('[name="goal_id"]').value = g.id;
      form.title.value       = g.title || '';
      form.description.value = g.description || '';
      form.status.value      = g.status || 'not_started';
      form.priority.value    = g.priority || 3;
      form.due_date.value    = g.due_date || '';
      assigneeSel.value      = g.assigned_user_id || '';
      // Per-goal trip visibility (defaults to checked for new goals; respects DB for existing)
      form.show_on_trip.checked = (g.show_on_trip == null) ? true : !!+g.show_on_trip;
      (g.milestones || []).forEach(m => addMsRow(m.text, !!+m.done, m.due_date || '', m.assigned_user_id || ''));
      delBtn.hidden = false;
      updateAssignActions(g);
      loadComments(g.id);
      showForm();
    } catch { alert('Failed to load goal'); }
  });

  // Members must load before milestone rows render their assignee options
  loadMembers().then(loadList);
})();
</script>
