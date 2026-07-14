<?php
// views/partials/overlay_itinerary.php — day-by-day trip schedule.
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
?>

<div class="overlay-header">
  <h2>Itinerary</h2>
</div>

<div id="itinWrap" data-slug="<?= $slug ?>">
  <p style="opacity:.6;font-size:.85em;margin:0 0 .7rem;">
    The day-by-day plan. Entries marked "Show on Trip layer" appear at the top
    of the published trip page.
  </p>

  <div id="itinList" style="display:flex;flex-direction:column;gap:.45rem;margin-bottom:.6rem;">
    <div style="opacity:.5;font-size:.9em;">Loading…</div>
  </div>
  <button type="button" id="btnAddItin" class="btn btn-primary">+ Add entry</button>

  <div id="itinFormCard" class="card" hidden style="margin-top:1rem;">
    <form id="itinForm">
      <input type="hidden" name="entry_id" value="">

      <div class="itin-meta-row">
        <div>
          <label for="itinDate">Date</label>
          <input id="itinDate" name="day_date" type="date">
        </div>
        <div>
          <label for="itinTime">Time <span style="opacity:.5;">(optional)</span></label>
          <input id="itinTime" name="start_time" type="time">
        </div>
      </div>

      <label for="itinTitle">What</label>
      <input id="itinTitle" name="title" type="text" placeholder="e.g. Drive to the dunes, sunrise shoot…">

      <label for="itinLocation">Location <span style="opacity:.5;">(optional — becomes a map link)</span></label>
      <input id="itinLocation" name="location" type="text" placeholder="Address or place name">

      <label for="itinNotes">Notes <span style="opacity:.5;">(optional)</span></label>
      <textarea id="itinNotes" name="notes" rows="2" placeholder="Gear to bring, who to call, backup plan…"></textarea>

      <label class="switch switch-row" style="margin-top:.4rem;">
        <span class="switch-label">Show on Trip layer</span>
        <input class="switch-input" type="checkbox" name="show_on_trip" checked>
        <span class="knob" aria-hidden="true"></span>
      </label>

      <div style="margin-top:1rem; display:flex; align-items:center; gap:.6rem;">
        <button type="button" class="btn" id="btnCloseItin">Close</button>
        <button type="button" class="btn btn-danger" id="btnDeleteItin" hidden>Delete entry</button>
        <span id="itinStatus" style="margin-left:auto;opacity:.6;font-size:.85em;"></span>
      </div>
    </form>
  </div>
</div>

<style>
  #itinWrap .itin-day {
    font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    opacity:.6; margin:.5rem 0 .1rem;
  }
  #itinWrap .itin-row {
    display:flex; align-items:flex-start; gap:.6rem; cursor:pointer;
    padding:.55rem .7rem; background:rgba(255,255,255,.04);
    border:1px solid #2b3346; border-radius:8px;
  }
  #itinWrap .itin-row:hover { background:rgba(255,255,255,.06); }
  #itinWrap .itin-row.is-hidden-on-trip { opacity:.55; }
  #itinWrap .itin-time {
    flex-shrink:0; width:52px; font-family:monospace; font-size:.85em; opacity:.75;
    padding-top:.1rem;
  }
  #itinWrap .itin-main { min-width:0; flex:1; }
  #itinWrap .itin-title { font-weight:600; color:#eaeaea; }
  #itinWrap .itin-sub { font-size:.8em; opacity:.6; margin-top:.15rem; }
  #itinForm input[type="text"], #itinForm input[type="date"], #itinForm input[type="time"],
  #itinForm textarea {
    width:100%; box-sizing:border-box;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.4rem .55rem; border-radius:6px; margin-bottom:.55rem;
  }
  #itinForm textarea { min-height:56px; resize:vertical; }
  #itinForm .itin-meta-row { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
  #itinForm .itin-meta-row label { font-size:.8em; opacity:.7; }
  #itinForm > label { font-size:.8em; opacity:.7; display:block; }
</style>

<script>
(() => {
  const wrap     = document.getElementById('itinWrap');
  if (!wrap) return;
  const slug     = wrap.dataset.slug;
  const list     = wrap.querySelector('#itinList');
  const card     = wrap.querySelector('#itinFormCard');
  const form     = wrap.querySelector('#itinForm');
  const status   = wrap.querySelector('#itinStatus');
  const addBtn   = wrap.querySelector('#btnAddItin');
  const closeBtn = wrap.querySelector('#btnCloseItin');
  const delBtn   = wrap.querySelector('#btnDeleteItin');

  let entries = [];

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function dayLabel(d) {
    try {
      return new Date(d + 'T00:00:00').toLocaleDateString(undefined,
        { weekday:'short', month:'short', day:'numeric', year:'numeric' });
    } catch { return d; }
  }

  function renderList() {
    if (!entries.length) {
      list.innerHTML = '<div style="opacity:.6;font-size:.9em;">No entries yet — build the day-by-day plan.</div>';
      return;
    }
    let html = '', lastDay = null;
    entries.forEach(en => {
      if (en.day_date !== lastDay) {
        lastDay = en.day_date;
        html += `<div class="itin-day">📅 ${esc(dayLabel(en.day_date))}</div>`;
      }
      const time = en.start_time ? en.start_time.slice(0, 5) : '·';
      const sub = [
        en.location ? '📍 ' + en.location : '',
        !+en.show_on_trip ? 'hidden on trip' : '',
      ].filter(Boolean).join(' · ');
      html += `
        <div class="itin-row ${!+en.show_on_trip ? 'is-hidden-on-trip' : ''}" data-id="${en.id}">
          <span class="itin-time">${esc(time)}</span>
          <span class="itin-main">
            <span class="itin-title">${esc(en.title)}</span>
            ${sub ? `<span class="itin-sub" style="display:block;">${esc(sub)}</span>` : ''}
          </span>
        </div>`;
    });
    list.innerHTML = html;
  }

  async function loadList() {
    try {
      const res = await fetch(`/api/visions/${slug}/itinerary`);
      const j = await res.json();
      entries = j?.entries || [];
      if (j?.migration_missing) {
        list.innerHTML = '<div style="color:#e8c267;font-size:.85em;">Run db/migrations/2026-07-13_itinerary_budget_items.sql first.</div>';
        return;
      }
      renderList();
    } catch { list.innerHTML = '<div class="error">Failed to load itinerary.</div>'; }
  }

  function clearForm() {
    form.reset();
    form.querySelector('[name="entry_id"]').value = '';
    form.show_on_trip.checked = true;
    status.textContent = '';
    delBtn.hidden = true;
  }
  function showForm() { card.hidden = false; card.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
  function hideForm() { card.hidden = true; clearForm(); }

  function collect() {
    const fd = new URLSearchParams();
    fd.set('day_date',   form.day_date.value);
    fd.set('start_time', form.start_time.value);
    fd.set('title',      form.title.value);
    fd.set('location',   form.location.value);
    fd.set('notes',      form.notes.value);
    if (form.show_on_trip.checked) fd.set('show_on_trip', '1');
    return fd;
  }

  let saveTimer;
  async function doSave() {
    if (!form.title.value.trim() || !form.day_date.value) { status.textContent = ''; return; }
    const eid = form.querySelector('[name="entry_id"]').value.trim();
    const url = eid
      ? `/api/visions/${slug}/itinerary/${eid}`
      : `/api/visions/${slug}/itinerary/create`;
    status.textContent = 'Saving…';
    try {
      const res = await fetch(url, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: collect().toString()
      });
      const j = await res.json();
      if (j?.success) {
        if (!eid && j.entry_id) {
          form.querySelector('[name="entry_id"]').value = j.entry_id;
          delBtn.hidden = false;
        }
        status.textContent = 'Saved';
        loadList();
      } else status.textContent = '⚠ ' + (j?.error || 'Save failed');
    } catch { status.textContent = '⚠ Network error'; }
  }
  function autoSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(doSave, 500);
  }

  addBtn.addEventListener('click', () => { clearForm(); showForm(); form.day_date.focus(); });
  // Flush any pending save so a date/time change right before Close isn't lost
  closeBtn.addEventListener('click', async () => {
    clearTimeout(saveTimer);
    await doSave();
    hideForm();
  });

  form.querySelectorAll('input, textarea').forEach(el => {
    el.addEventListener('change', autoSave);
    el.addEventListener('input', autoSave); // date/time/checkbox fire this too in modern browsers
  });

  delBtn.addEventListener('click', async () => {
    const eid = form.querySelector('[name="entry_id"]').value.trim();
    if (!eid) return;
    if (!confirm('Delete this itinerary entry?')) return;
    try {
      const res = await fetch(`/api/visions/${slug}/itinerary/${eid}/delete`, { method:'POST' });
      const j = await res.json();
      if (j?.success) { hideForm(); loadList(); }
      else alert(j?.error || 'Delete failed');
    } catch { alert('Delete failed'); }
  });

  list.addEventListener('click', e => {
    const row = e.target.closest('.itin-row');
    if (!row) return;
    const en = entries.find(x => String(x.id) === row.dataset.id);
    if (!en) return;
    clearForm();
    form.querySelector('[name="entry_id"]').value = en.id;
    form.day_date.value   = en.day_date || '';
    form.start_time.value = en.start_time ? en.start_time.slice(0, 5) : '';
    form.title.value      = en.title || '';
    form.location.value   = en.location || '';
    form.notes.value      = en.notes || '';
    form.show_on_trip.checked = !!+en.show_on_trip;
    delBtn.hidden = false;
    showForm();
  });

  loadList();
})();
</script>
