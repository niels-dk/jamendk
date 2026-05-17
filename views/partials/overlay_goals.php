<?php
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
?>

<div class="overlay-header">
  <h2>Goals &amp; Milestones</h2>
</div>

<div id="goalsWrap" data-slug="<?= $slug ?>">
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

      <label class="switch switch-row" style="margin-top:.6rem;">
        <span class="switch-label">Show on Trip layer</span>
        <input class="switch-input" type="checkbox" name="show_on_trip" checked>
        <span class="knob" aria-hidden="true"></span>
      </label>

      <h4 style="margin-top:1rem;">Milestones</h4>
      <div id="milestonesWrap"></div>
      <button type="button" id="btnAddMilestone" class="btn btn-secondary">+ Add milestone</button>

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
    display:flex; align-items:center; gap:.4rem; margin-bottom:.35rem;
  }
  #goalsWrap .milestone-row input[type="text"] {
    flex:1; margin-bottom:0;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.35rem .5rem; border-radius:6px;
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
  const list     = wrap.querySelector('#goalsList');
  const card     = wrap.querySelector('#goalFormCard');
  const form     = wrap.querySelector('#goalForm');
  const msWrap   = wrap.querySelector('#milestonesWrap');
  const status   = wrap.querySelector('#goalSaveStatus');
  const addBtn   = wrap.querySelector('#btnAddGoal');
  const closeBtn = wrap.querySelector('#btnCloseGoal');
  const delBtn   = wrap.querySelector('#btnDeleteGoal');
  const addMs    = wrap.querySelector('#btnAddMilestone');

  const STATUS_LABELS = {
    not_started: 'Not started', in_progress: 'In progress',
    awaiting:    'Awaiting',    done:        'Done', cancelled: 'Cancelled'
  };
  const today = new Date().toISOString().slice(0,10);

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function msRow(text='', done=false) {
    return `
      <div class="milestone-row ${done?'is-done':''}">
        <input type="checkbox" class="ms-done" ${done?'checked':''}>
        <input type="text" class="ms-text" value="${escapeHtml(text)}" placeholder="Milestone…">
        <button type="button" class="ms-remove" aria-label="Remove">×</button>
      </div>`;
  }
  function addMsRow(t='', d=false) { msWrap.insertAdjacentHTML('beforeend', msRow(t, d)); }

  function clearForm() {
    form.reset();
    form.querySelector('[name="goal_id"]').value = '';
    msWrap.innerHTML = '';
    status.textContent = '';
    delBtn.hidden = true;
  }
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
      const overdue = g.due_date && g.due_date < today && g.status !== 'done' && g.status !== 'cancelled';
      const cls = [
        g.status === 'done' ? 'is-done' : '',
        g.status === 'cancelled' ? 'is-cancelled' : '',
        overdue ? 'is-overdue' : '',
      ].filter(Boolean).join(' ');
      const dateLabel = g.due_date
        ? `<span class="goal-date ${overdue?'is-overdue':''}">📅 ${g.due_date}</span>`
        : '';
      return `
        <div class="goal-row ${cls}" data-id="${g.id}">
          <div class="goal-main">
            <div class="goal-title">${escapeHtml(g.title || '(untitled)')}</div>
            <div class="goal-meta">
              <span class="pri-pill" data-p="${g.priority}">P${g.priority}</span>
              <span class="stat-pill" data-s="${g.status}">${STATUS_LABELS[g.status] || g.status}</span>
              ${dateLabel}
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
    fd.append('show_on_trip', form.show_on_trip.checked ? '1' : '0');
    msWrap.querySelectorAll('.milestone-row').forEach(row => {
      const t = row.querySelector('.ms-text').value;
      const d = row.querySelector('.ms-done').checked ? '1' : '';
      fd.append('milestone_texts[]', t);
      fd.append('milestone_dones[]', d);
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

  ['title','description','status','priority','due_date'].forEach(name => {
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
      // Per-goal trip visibility (defaults to checked for new goals; respects DB for existing)
      form.show_on_trip.checked = (g.show_on_trip == null) ? true : !!+g.show_on_trip;
      (g.milestones || []).forEach(m => addMsRow(m.text, !!+m.done));
      delBtn.hidden = false;
      showForm();
    } catch { alert('Failed to load goal'); }
  });

  loadList();
})();
</script>
