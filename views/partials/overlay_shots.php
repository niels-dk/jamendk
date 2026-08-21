<?php
// views/partials/overlay_shots.php — the capture list: what to film, where,
// when, and how. Quick-add keeps idea capture under three seconds; details
// (type, day, light, references) are added later by tapping the shot.
// Expects: $vision (slug), $shotRefMedia (mood images for the ref picker).
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$refMediaJson = htmlspecialchars(json_encode($shotRefMedia ?? []), ENT_QUOTES);
?>

<div class="overlay-header">
  <h2><?= te('sec.shots') ?></h2>
</div>

<div id="shotsWrap" data-slug="<?= $slug ?>" data-refmedia="<?= $refMediaJson ?>">
  <p style="opacity:.6;font-size:.85em;margin:0 0 .7rem;">
    <?= te('shots.lead') ?>
  </p>

  <div style="display:flex;gap:.4rem;margin-bottom:.8rem;">
    <input id="shotQuickAdd" type="text" placeholder="<?= te('shots.quick_placeholder') ?>"
           style="flex:1;min-width:0;background:#15161A;border:1px solid #2b3346;color:#ddd;
                  padding:.5rem .7rem;border-radius:8px;">
    <button type="button" id="btnQuickAdd" class="btn btn-primary" style="flex-shrink:0;"><?= te('action.add') ?></button>
  </div>

  <div id="shotsProgress" style="opacity:.65;font-size:.82em;margin-bottom:.4rem;"></div>

  <div id="shotsList" style="display:flex;flex-direction:column;gap:.45rem;margin-bottom:.6rem;">
    <div style="opacity:.5;font-size:.9em;"><?= te('action.loading') ?></div>
  </div>

  <div id="shotFormCard" class="card" hidden style="margin-top:1rem;">
    <form id="shotForm">
      <input type="hidden" name="shot_id" value="">

      <label for="shotTitle"><?= te('shots.shot') ?></label>
      <input id="shotTitle" name="title" type="text" placeholder="<?= te('shots.title_placeholder') ?>">

      <div class="shot-meta-row">
        <div>
          <label for="shotType"><?= te('shots.type') ?></label>
          <select id="shotType" name="shot_type">
            <option value="">—</option>
            <option value="drone">🚁 <?= te('shots.type_drone') ?></option>
            <option value="broll">🎥 <?= te('shots.type_broll') ?></option>
            <option value="interview">🎤 <?= te('shots.type_interview') ?></option>
            <option value="timelapse">⏱ <?= te('shots.type_timelapse') ?></option>
            <option value="photo">📷 <?= te('shots.type_photo') ?></option>
            <option value="pov">🎬 <?= te('shots.type_pov') ?></option>
            <option value="other">✨ <?= te('shots.type_other') ?></option>
          </select>
        </div>
        <div>
          <label for="shotLight"><?= te('shots.light') ?></label>
          <select id="shotLight" name="light">
            <option value=""><?= te('shots.light_any') ?></option>
            <option value="sunrise">🌅 <?= te('shots.light_sunrise') ?></option>
            <option value="golden">🌇 <?= te('shots.light_golden') ?></option>
            <option value="midday">☀️ <?= te('shots.light_midday') ?></option>
            <option value="blue">🌆 <?= te('shots.light_blue') ?></option>
            <option value="night">🌙 <?= te('shots.light_night') ?></option>
          </select>
        </div>
      </div>

      <div class="shot-meta-row">
        <div>
          <label for="shotDay"><?= te('shots.day') ?> <span style="opacity:.5;">(<?= te('shots.day_hint') ?>)</span></label>
          <input id="shotDay" name="day_date" type="date">
        </div>
        <div>
          <label for="shotLocation"><?= te('shots.location') ?> <span style="opacity:.5;">(<?= te('account.optional') ?>)</span></label>
          <input id="shotLocation" name="location" type="text" placeholder="<?= te('shots.location_placeholder') ?>">
        </div>
      </div>

      <label for="shotHow"><?= te('shots.how') ?> <span style="opacity:.5;">(<?= te('shots.how_hint') ?>)</span></label>
      <textarea id="shotHow" name="how_notes" rows="2"
                placeholder="<?= te('shots.how_placeholder') ?>"></textarea>

      <label style="font-size:.8em;opacity:.7;display:block;margin-top:.2rem;">
        <?= te('shots.refs') ?> <span style="opacity:.6;">(<?= te('shots.refs_hint') ?>)</span>
      </label>
      <div id="shotRefPicker" class="shot-ref-picker"></div>

      <label class="switch switch-row" style="margin-top:.4rem;">
        <span class="switch-label">★ <?= te('shots.must') ?></span>
        <input class="switch-input" type="checkbox" name="priority">
        <span class="knob" aria-hidden="true"></span>
      </label>
      <label class="switch switch-row">
        <span class="switch-label"><?= te('sec.show_on_trip') ?></span>
        <input class="switch-input" type="checkbox" name="show_on_trip" checked>
        <span class="knob" aria-hidden="true"></span>
      </label>

      <div style="margin-top:1rem; display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;">
        <button type="button" class="btn" id="btnCloseShot"><?= te('action.close') ?></button>
        <button type="button" class="btn btn-primary" id="btnCaptureShot" hidden>✓ <?= te('shots.captured') ?></button>
        <button type="button" class="btn" id="btnReopenShot" hidden>↺ <?= te('shots.reopen') ?></button>
        <button type="button" class="btn btn-danger" id="btnDeleteShot" hidden><?= te('action.delete') ?></button>
        <span id="shotStatus" style="margin-left:auto;opacity:.6;font-size:.85em;"></span>
      </div>
    </form>
  </div>
</div>

<style>
  #shotsWrap .shot-day {
    font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    opacity:.6; margin:.5rem 0 .1rem;
  }
  #shotsWrap .shot-row {
    display:flex; align-items:flex-start; gap:.55rem; cursor:pointer;
    padding:.55rem .7rem; background:rgba(255,255,255,.04);
    border:1px solid #2b3346; border-radius:8px;
  }
  #shotsWrap .shot-row:hover { background:rgba(255,255,255,.06); }
  #shotsWrap .shot-row.is-captured { opacity:.5; }
  #shotsWrap .shot-row.is-captured .shot-title { text-decoration:line-through; }
  #shotsWrap .shot-row.is-hidden-on-trip { opacity:.55; }
  #shotsWrap .shot-check {
    flex-shrink:0; width:1.15rem; height:1.15rem; margin-top:.1rem;
    accent-color:#3a76d2; cursor:pointer;
  }
  #shotsWrap .shot-main { min-width:0; flex:1; }
  #shotsWrap .shot-title { font-weight:600; color:#eaeaea; }
  #shotsWrap .shot-sub { font-size:.8em; opacity:.6; margin-top:.15rem; display:block; }
  #shotsWrap .shot-thumbs { display:flex; gap:.25rem; margin-top:.3rem; }
  #shotsWrap .shot-thumbs img {
    width:34px; height:34px; object-fit:cover; border-radius:4px; border:1px solid #2b3346;
  }
  #shotsWrap .must-star { color:#e8c267; font-weight:700; }
  #shotForm input[type="text"], #shotForm input[type="date"],
  #shotForm select, #shotForm textarea {
    width:100%; box-sizing:border-box;
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.4rem .55rem; border-radius:6px; margin-bottom:.55rem;
  }
  #shotForm textarea { min-height:56px; resize:vertical; }
  #shotForm .shot-meta-row { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
  #shotForm .shot-meta-row label { font-size:.8em; opacity:.7; }
  #shotForm > label { font-size:.8em; opacity:.7; display:block; }
  .shot-ref-picker {
    display:flex; flex-wrap:wrap; gap:.35rem; margin:.3rem 0 .55rem;
    max-height:150px; overflow-y:auto;
  }
  .shot-ref-picker:empty::after {
    content:"No mood board linked yet — link one under Relations to pick reference images.";
    opacity:.45; font-size:.8em;
  }
  .shot-ref-picker img {
    width:56px; height:56px; object-fit:cover; border-radius:6px;
    border:2px solid transparent; cursor:pointer; opacity:.7;
  }
  .shot-ref-picker img.pinned { border-color:#3a76d2; opacity:1; }
</style>

<script>
(() => {
  const wrap     = document.getElementById('shotsWrap');
  if (!wrap) return;
  const slug     = wrap.dataset.slug;
  const refMedia = JSON.parse(wrap.dataset.refmedia || '[]');
  const list     = wrap.querySelector('#shotsList');
  const progress = wrap.querySelector('#shotsProgress');
  const card     = wrap.querySelector('#shotFormCard');
  const form     = wrap.querySelector('#shotForm');
  const status   = wrap.querySelector('#shotStatus');
  const quickIn  = wrap.querySelector('#shotQuickAdd');
  const quickBtn = wrap.querySelector('#btnQuickAdd');
  const closeBtn = wrap.querySelector('#btnCloseShot');
  const capBtn   = wrap.querySelector('#btnCaptureShot');
  const reopenBtn= wrap.querySelector('#btnReopenShot');
  const delBtn   = wrap.querySelector('#btnDeleteShot');
  const picker   = wrap.querySelector('#shotRefPicker');

  // Strings come from PHP so the list, chips and confirms speak the user's language
  const T = <?= json_encode([
      'type_drone'=>t('shots.type_drone'),'type_broll'=>t('shots.type_broll'),
      'type_interview'=>t('shots.type_interview'),'type_timelapse'=>t('shots.type_timelapse'),
      'type_photo'=>t('shots.type_photo'),'type_pov'=>t('shots.type_pov'),'type_other'=>t('shots.type_other'),
      'light_sunrise'=>t('shots.light_sunrise'),'light_golden'=>t('shots.light_golden'),
      'light_midday'=>t('shots.light_midday'),'light_blue'=>t('shots.light_blue'),'light_night'=>t('shots.light_night'),
      'must'=>t('shots.must'),'hidden_on_trip'=>t('sec.hidden_on_trip'),
      'anytime'=>t('shots.anytime'),'none_yet'=>t('shots.none_yet'),
      'progress'=>t('shots.progress'),'progress_must'=>t('shots.progress_must'),
      'saving'=>t('status.saving'),'saved'=>t('status.saved'),'save_failed'=>t('status.save_failed'),
      'net_error'=>t('status.net_error'),'load_failed'=>t('shots.load_failed'),
      'confirm_delete'=>t('shots.confirm_delete'),'delete_failed'=>t('status.delete_failed'),
      'migration'=>t('shots.migration'),
  ], JSON_UNESCAPED_UNICODE) ?>;
  const TYPE_LABEL = { drone:'🚁 '+T.type_drone, broll:'🎥 '+T.type_broll,
                       interview:'🎤 '+T.type_interview, timelapse:'⏱ '+T.type_timelapse,
                       photo:'📷 '+T.type_photo, pov:'🎬 '+T.type_pov, other:'✨ '+T.type_other };
  const LIGHT_LABEL = { sunrise:'🌅 '+T.light_sunrise, golden:'🌇 '+T.light_golden,
                        midday:'☀️ '+T.light_midday, blue:'🌆 '+T.light_blue, night:'🌙 '+T.light_night };

  let shots = [];
  let pinnedRefs = new Set();   // media ids pinned on the shot being edited

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

  function renderProgress() {
    const total = shots.length;
    if (!total) { progress.textContent = ''; return; }
    const done  = shots.filter(s => s.status === 'captured').length;
    const musts = shots.filter(s => +s.priority);
    const mustsDone = musts.filter(s => s.status === 'captured').length;
    progress.textContent = T.progress.replace(':done', done).replace(':total', total) +
      (musts.length ? ' · ' + T.progress_must.replace(':done', mustsDone).replace(':total', musts.length) : '');
  }

  function renderList() {
    renderProgress();
    if (!shots.length) {
      list.innerHTML = '<div style="opacity:.6;font-size:.9em;">' + T.none_yet + '</div>';
      return;
    }
    let html = '', lastDay = '·none·';
    shots.forEach(s => {
      const day = s.day_date || '';
      if (day !== lastDay) {
        lastDay = day;
        html += `<div class="shot-day">${day ? '📅 ' + esc(dayLabel(day)) : '✨ ' + T.anytime}</div>`;
      }
      const captured = s.status === 'captured';
      const sub = [
        s.shot_type ? TYPE_LABEL[s.shot_type] || s.shot_type : '',
        s.light ? LIGHT_LABEL[s.light] || s.light : '',
        s.location ? '📍 ' + s.location : '',
        !+s.show_on_trip ? T.hidden_on_trip : '',
      ].filter(Boolean).join(' · ');
      const thumbs = (s.refs || []).slice(0, 4)
        .map(r => `<img src="${esc(r.thumb)}" alt="" loading="lazy">`).join('');
      html += `
        <div class="shot-row ${captured ? 'is-captured' : ''} ${!+s.show_on_trip ? 'is-hidden-on-trip' : ''}" data-id="${s.id}">
          <input type="checkbox" class="shot-check" ${captured ? 'checked' : ''}
                 title="${captured ? 'Reopen' : 'Mark captured'}">
          <span class="shot-main">
            <span class="shot-title">${+s.priority ? '<span class="must-star" title="'+esc(T.must)+'">★</span> ' : ''}${esc(s.title)}</span>
            ${sub ? `<span class="shot-sub">${esc(sub)}</span>` : ''}
            ${thumbs ? `<span class="shot-thumbs">${thumbs}</span>` : ''}
          </span>
        </div>`;
    });
    list.innerHTML = html;
  }

  async function loadList() {
    try {
      const res = await fetch(`/api/visions/${slug}/shots`);
      const j = await res.json();
      shots = j?.shots || [];
      if (j?.migration_missing) {
        list.innerHTML = '<div style="color:#e8c267;font-size:.85em;">' + T.migration + '</div>';
        return;
      }
      renderList();
    } catch { list.innerHTML = '<div class="error">' + T.load_failed + '</div>'; }
  }

  function renderPicker() {
    picker.innerHTML = refMedia.map(m =>
      `<img src="${esc(m.thumb)}" data-mid="${m.media_id}"
            class="${pinnedRefs.has(m.media_id) ? 'pinned' : ''}" alt="" loading="lazy">`
    ).join('');
  }
  picker.addEventListener('click', e => {
    const img = e.target.closest('img[data-mid]');
    if (!img) return;
    const mid = +img.dataset.mid;
    pinnedRefs.has(mid) ? pinnedRefs.delete(mid) : pinnedRefs.add(mid);
    img.classList.toggle('pinned');
    autoSave();
  });

  function clearForm() {
    form.reset();
    form.querySelector('[name="shot_id"]').value = '';
    form.show_on_trip.checked = true;
    pinnedRefs = new Set();
    renderPicker();
    status.textContent = '';
    delBtn.hidden = capBtn.hidden = reopenBtn.hidden = true;
  }
  function showForm() { card.hidden = false; card.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
  function hideForm() { card.hidden = true; clearForm(); }

  function collect() {
    const fd = new URLSearchParams();
    fd.set('title',     form.title.value);
    fd.set('shot_type', form.shot_type.value);
    fd.set('light',     form.light.value);
    fd.set('day_date',  form.day_date.value);
    fd.set('location',  form.location.value);
    fd.set('how_notes', form.how_notes.value);
    if (form.priority.checked)     fd.set('priority', '1');
    if (form.show_on_trip.checked) fd.set('show_on_trip', '1');
    fd.set('refs', [...pinnedRefs].join(','));
    return fd;
  }

  let saveTimer;
  async function doSave() {
    if (!form.title.value.trim()) { status.textContent = ''; return; }
    const sid = form.querySelector('[name="shot_id"]').value.trim();
    const url = sid ? `/api/visions/${slug}/shots/${sid}` : `/api/visions/${slug}/shots/create`;
    status.textContent = T.saving;
    try {
      const res = await fetch(url, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: collect().toString()
      });
      const j = await res.json();
      if (j?.success) {
        if (!sid && j.shot_id) {
          form.querySelector('[name="shot_id"]').value = j.shot_id;
          delBtn.hidden = capBtn.hidden = false;
        }
        status.textContent = T.saved;
        loadList();
      } else status.textContent = '⚠ ' + (j?.error || T.save_failed);
    } catch { status.textContent = '⚠ ' + T.net_error; }
  }
  function autoSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(doSave, 500);
  }

  async function setStatus(id, newStatus) {
    try {
      const res = await fetch(`/api/visions/${slug}/shots/${id}/status`, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ status: newStatus }).toString()
      });
      const j = await res.json();
      if (j?.success) loadList();
    } catch {}
  }

  // Quick-add: title only, lands in "Anytime"
  async function quickAdd() {
    const title = quickIn.value.trim();
    if (!title) return;
    quickBtn.disabled = true;
    try {
      const res = await fetch(`/api/visions/${slug}/shots/create`, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ title, show_on_trip:'1' }).toString()
      });
      const j = await res.json();
      if (j?.success) { quickIn.value = ''; loadList(); }
    } catch {}
    quickBtn.disabled = false;
    quickIn.focus();
  }
  quickBtn.addEventListener('click', quickAdd);
  quickIn.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); quickAdd(); } });

  // Flush the pending save so a change right before Close isn't lost
  closeBtn.addEventListener('click', async () => {
    clearTimeout(saveTimer);
    await doSave();
    hideForm();
  });

  form.querySelectorAll('input, textarea, select').forEach(el => {
    el.addEventListener('change', autoSave);
    el.addEventListener('input', autoSave);
  });

  capBtn.addEventListener('click', async () => {
    const sid = form.querySelector('[name="shot_id"]').value.trim();
    if (!sid) return;
    await setStatus(sid, 'captured');
    capBtn.hidden = true; reopenBtn.hidden = false;
  });
  reopenBtn.addEventListener('click', async () => {
    const sid = form.querySelector('[name="shot_id"]').value.trim();
    if (!sid) return;
    await setStatus(sid, 'planned');
    capBtn.hidden = false; reopenBtn.hidden = true;
  });

  delBtn.addEventListener('click', async () => {
    const sid = form.querySelector('[name="shot_id"]').value.trim();
    if (!sid) return;
    if (!confirm(T.confirm_delete)) return;
    try {
      const res = await fetch(`/api/visions/${slug}/shots/${sid}/delete`, { method:'POST' });
      const j = await res.json();
      if (j?.success) { hideForm(); loadList(); }
      else alert(j?.error || T.delete_failed);
    } catch { alert(T.delete_failed); }
  });

  list.addEventListener('click', e => {
    const row = e.target.closest('.shot-row');
    if (!row) return;
    const s = shots.find(x => String(x.id) === row.dataset.id);
    if (!s) return;

    // Checkbox toggles captured state without opening the form
    if (e.target.classList.contains('shot-check')) {
      setStatus(s.id, s.status === 'captured' ? 'planned' : 'captured');
      return;
    }

    clearForm();
    form.querySelector('[name="shot_id"]').value = s.id;
    form.title.value     = s.title || '';
    form.shot_type.value = s.shot_type || '';
    form.light.value     = s.light || '';
    form.day_date.value  = s.day_date || '';
    form.location.value  = s.location || '';
    form.how_notes.value = s.how_notes || '';
    form.priority.checked     = !!+s.priority;
    form.show_on_trip.checked = !!+s.show_on_trip;
    pinnedRefs = new Set((s.refs || []).map(r => r.media_id));
    renderPicker();
    delBtn.hidden = false;
    capBtn.hidden = s.status === 'captured';
    reopenBtn.hidden = s.status !== 'captured';
    showForm();
  });

  renderPicker();
  loadList();
})();
</script>
