<?php
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
?>

<div class="overlay-header">
  <h2>Contacts</h2>
</div>

<div id="contactsWrap" data-slug="<?= $slug ?>">
  <div id="contactsList" class="contact-list"><div style="opacity:.5;font-size:.9em;">Loading…</div></div>
  <button type="button" id="btnAddContact" class="btn btn-primary">+ Add contact</button>

  <div id="contactFormCard" class="card" hidden style="margin-top:1rem;">
    <form id="contactForm" class="contact-form">
      <input type="hidden" name="vc_id" value="">

      <h4>Fields</h4>
      <div id="fieldsWrap"></div>
      <button type="button" id="btnAddField" class="btn btn-secondary">+ Add field</button>

      <h4 style="margin-top:1rem;">Flags</h4>
      <label class="switch switch-row">
        <span class="switch-label">Current</span>
        <input class="switch-input" type="checkbox" name="is_current">
        <span class="knob" aria-hidden="true"></span>
      </label>
      <label class="switch switch-row">
        <span class="switch-label">Main</span>
        <input class="switch-input" type="checkbox" name="is_main">
        <span class="knob" aria-hidden="true"></span>
      </label>
      <label class="switch switch-row">
        <span class="switch-label">Show on Dashboard</span>
        <input class="switch-input" type="checkbox" name="show_on_dashboard">
        <span class="knob" aria-hidden="true"></span>
      </label>
      <label class="switch switch-row">
        <span class="switch-label">Show on Trip layer</span>
        <input class="switch-input" type="checkbox" name="show_on_trip">
        <span class="knob" aria-hidden="true"></span>
      </label>

      <div style="margin-top:1rem;">
        <button type="button" class="btn" id="btnCloseContact">Close</button>
        <span id="contactSaveStatus" style="margin-left:.6rem;opacity:.6;font-size:.85em;"></span>
      </div>
    </form>
  </div>
</div>

<style>
  #contactsWrap .contact-item .info strong { margin-right:.4rem; }
  #contactsWrap .contact-item .badge {
    display:inline-block; padding:.1rem .45rem; border-radius:999px;
    background:#2a3346; font-size:.75em; opacity:.85;
  }
  #contactsWrap .contact-item .small { opacity:.7; font-size:.85em; margin-top:.15rem; }
  #contactsWrap .contact-item .actions { display:flex; gap:.4rem; }
  #contactsWrap .field-row { display:flex; gap:.5rem; align-items:center; margin-bottom:.4rem; }
  #contactsWrap .field-row select, #contactsWrap .field-row input {
    background:#15161A; border:1px solid #2b3346; color:#ddd;
    padding:.4rem .55rem; border-radius:6px;
  }
  #contactsWrap .field-row select.field-key { min-width:110px; }
  #contactsWrap .field-row input.field-value { flex:1; }
  #contactsWrap .btn-remove-field {
    background:transparent; border:0; color:#aaa; font-size:1.1rem;
    cursor:pointer; padding:0 .35rem;
  }
  #contactsWrap .btn-remove-field:hover { color:#fff; }
</style>

<script>
(() => {
  const wrap     = document.getElementById('contactsWrap');
  if (!wrap) return;
  const slug     = wrap.dataset.slug;
  const list     = wrap.querySelector('#contactsList');
  const card     = wrap.querySelector('#contactFormCard');
  const form     = wrap.querySelector('#contactForm');
  const fields   = wrap.querySelector('#fieldsWrap');
  const status   = wrap.querySelector('#contactSaveStatus');
  const addBtn   = wrap.querySelector('#btnAddContact');
  const addFld   = wrap.querySelector('#btnAddField');
  const closeBtn = wrap.querySelector('#btnCloseContact');

  const keyOptions = ['Name','Company','Address','Mobile','Email','Country','Custom…'];

  function fieldRow(key='', val='') {
    const opts = keyOptions.map(op => `<option value="${op}" ${op===key?'selected':''}>${op}</option>`).join('');
    const customOpt = (key && !keyOptions.includes(key))
      ? `<option value="${key}" selected>${key}</option>`
      : '';
    return `
      <div class="field-row">
        <select class="field-key">${customOpt}${opts}</select>
        <input type="text" class="field-value" value="${val.replace(/"/g,'&quot;')}">
        <button type="button" class="btn-remove-field" aria-label="Remove">×</button>
      </div>`;
  }
  function addFieldRow(k='', v='') { fields.insertAdjacentHTML('beforeend', fieldRow(k, v)); }

  function clearForm({ addNameRow = true } = {}) {
    form.reset();
    form.querySelector('[name="vc_id"]').value = '';
    fields.innerHTML = '';
    if (addNameRow) addFieldRow('Name', '');
    status.textContent = '';
  }
  function showForm() { card.hidden = false; }
  function hideForm() { card.hidden = true; clearForm({ addNameRow: false }); }

  function renderList(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      list.innerHTML = '<div class="muted" style="opacity:.6;">No contacts yet.</div>';
      return;
    }
    list.innerHTML = rows.map(r => {
      const cmp   = r.company || '';
      const email = r.email || '';
      // Fall back to email/company when no Name is set
      const primary = r.name || email || cmp || '(unnamed)';
      const secondary = r.name
        ? [cmp, email].filter(Boolean).join(' — ')
        : (primary === email ? cmp : [cmp, email].filter(v => v && v !== primary).join(' — '));
      const flags = [];
      if (r.is_main)    flags.push('Main');
      if (r.is_current) flags.push('Current');
      const flagTxt = flags.length ? `<small class="badge">${flags.join(', ')}</small>` : '';
      return `
        <div class="contact-item" data-id="${r.vc_id}">
          <div class="info">
            <div><strong>${primary}</strong>${flagTxt}</div>
            ${secondary ? `<div class="small">${secondary}</div>` : ''}
          </div>
          <div class="actions">
            <button type="button" class="btn act-edit">Edit</button>
            <button type="button" class="btn act-del">Delete</button>
          </div>
        </div>`;
    }).join('');
  }

  function loadList() {
    fetch(`/api/visions/${slug}/contacts`)
      .then(r => r.json()).then(renderList)
      .catch(() => { list.innerHTML = '<div class="error">Failed to load contacts.</div>'; });
  }

  function collectFormData() {
    const fd = new FormData();
    fields.querySelectorAll('.field-row').forEach(row => {
      const k = row.querySelector('.field-key').value.trim();
      const v = row.querySelector('.field-value').value.trim();
      fd.append('keys[]', k);
      fd.append('values[]', v);
    });
    if (form.is_current.checked)        fd.append('is_current','1');
    if (form.is_main.checked)           fd.append('is_main','1');
    if (form.show_on_dashboard.checked) fd.append('show_on_dashboard','1');
    if (form.show_on_trip.checked)      fd.append('show_on_trip','1');
    return fd;
  }

  function hasAnyValue() {
    return Array.from(fields.querySelectorAll('.field-value')).some(el => el.value.trim() !== '');
  }

  let saveTimer;
  function autoSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(async () => {
      if (!hasAnyValue()) { status.textContent = ''; return; }
      const vcId = form.querySelector('[name="vc_id"]').value.trim();
      const url  = vcId
        ? `/api/visions/${slug}/contacts/${vcId}`
        : `/api/visions/${slug}/contacts/create`;
      status.textContent = 'Saving…';
      try {
        const res = await fetch(url, { method: 'POST', body: collectFormData() });
        const j   = await res.json();
        if (j && j.success) {
          if (!vcId && j.vc_id) form.querySelector('[name="vc_id"]').value = j.vc_id;
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

  addBtn.addEventListener('click', () => { clearForm({ addNameRow: true }); showForm(); });
  closeBtn.addEventListener('click', hideForm);

  addFld.addEventListener('click', () => addFieldRow('', ''));

  fields.addEventListener('click', e => {
    if (e.target.closest('.btn-remove-field')) {
      e.target.closest('.field-row').remove();
      autoSave();
    }
  });

  fields.addEventListener('change', e => {
    const sel = e.target.closest('select.field-key');
    if (sel && sel.value === 'Custom…') {
      const newKey = prompt('Enter custom key');
      if (newKey) {
        const opt = document.createElement('option');
        opt.value = newKey; opt.textContent = newKey;
        sel.insertBefore(opt, sel.querySelector('option[value="Custom…"]'));
        sel.value = newKey;
      } else {
        sel.value = 'Name';
      }
    }
    autoSave();
  });

  fields.addEventListener('input', autoSave);
  form.querySelectorAll('.switch-input').forEach(cb => cb.addEventListener('change', autoSave));

  list.addEventListener('click', async e => {
    const row = e.target.closest('.contact-item');
    if (!row) return;
    const id = row.dataset.id;

    if (e.target.closest('.act-del')) {
      if (!confirm('Delete this contact?')) return;
      try {
        const res = await fetch(`/api/visions/${slug}/contacts/${id}/delete`, { method: 'DELETE' });
        const j   = await res.json();
        if (j && j.success) loadList();
        else alert(j?.error || 'Delete failed');
      } catch { alert('Delete failed'); }
      return;
    }

    if (e.target.closest('.act-edit')) {
      try {
        const res = await fetch(`/api/visions/${slug}/contacts/${id}/get`);
        const j   = await res.json();
        clearForm({ addNameRow: false });
        form.querySelector('[name="vc_id"]').value = j.id || '';
        (j.fields || []).forEach(f => addFieldRow(f.field_key, f.field_value));
        if (!fields.children.length) addFieldRow('Name', '');
        form.is_current.checked        = !!j.flags?.is_current;
        form.is_main.checked           = !!j.flags?.is_main;
        form.show_on_dashboard.checked = !!j.flags?.show_on_dashboard;
        form.show_on_trip.checked      = !!j.flags?.show_on_trip;
        showForm();
      } catch { alert('Failed to load contact'); }
    }
  });

  loadList();
})();
</script>
