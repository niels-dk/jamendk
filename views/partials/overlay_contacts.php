<?php
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
?>

<div class="overlay-contacts" id="overlay-contacts">
  <div class="overlay-panel">
    <div class="overlay-header">
      <h2>Contacts</h2>
    </div>

    <div class="overlay-body" data-slug="<?= $slug ?>" id="contactsWrap">
      <!-- List -->
      <div id="contactsList" class="contact-list"></div>
      <button id="btnAddContact" class="btn btn-primary">+ Add contact</button>

      <!-- Form -->
      <div id="contactFormCard" class="card" hidden>
        <form id="contactForm" class="contact-form">
          <input type="hidden" name="vc_id" value="">

          <h4>Fields</h4>
          <div id="fieldsWrap">
            <!-- field rows inserted here -->
          </div>
          <button type="button" id="btnAddField" class="btn btn-secondary">Add field</button>

          <h4>Flags</h4>
          <label class="switch-row"><input type="checkbox" class="ui-switch" name="is_current"> <span class="switch-text">Current</span></label>
          <label class="switch-row"><input type="checkbox" class="ui-switch" name="is_main"> <span class="switch-text">Main</span></label>
          <label class="switch-row"><input type="checkbox" class="ui-switch" name="show_on_dashboard"> <span class="switch-text">Show on Dashboard</span></label>
          <label class="switch-row"><input type="checkbox" class="ui-switch" name="show_on_trip"> <span class="switch-text">Show on Trip layer</span></label>

          <div class="overlay-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-danger" id="btnCancelContact">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!--script>
(() => {
  const wrap   = document.getElementById('contactsWrap');
  const slug   = wrap.dataset.slug;
  const list   = document.getElementById('contactsList');
  const card   = document.getElementById('contactFormCard');
  const form   = document.getElementById('contactForm');
  const addBtn = document.getElementById('btnAddContact');
  const cancelBtn = document.getElementById('btnCancelContact');
  const addField = document.getElementById('btnAddField');
  const fieldsWrap = document.getElementById('fieldsWrap');

  const keyOptions = ['Name','Company','Address','Mobile','Email','Country','Custom…'];

  function fieldRow(key='', val='') {
    return `
      <div class="field-row">
        <select class="field-key">
          ${keyOptions.map(op => `<option value="${op}" ${op===key?'selected':''}>${op}</option>`).join('')}
        </select>
        <input type="text" class="field-value" value="${val}">
        <button type="button" class="btn btn-ghost btn-remove-field">–</button>
      </div>`;
  }

  function addFieldRow(k='',v='') {
    fieldsWrap.insertAdjacentHTML('beforeend', fieldRow(k,v));
  }

  function clearForm() {
    form.reset();
    form.vc_id.value = '';
    fieldsWrap.innerHTML = '';
    // start with Name field row by default
    addFieldRow('Name','');
  }

  function toggleForm(show) {
    card.hidden = !show;
    if (show) {
      // scroll the form into view if needed
      card.scrollIntoView({behavior:'smooth', block:'nearest'});
    }
  }

  function renderList(rows){
    if (!Array.isArray(rows) || rows.length === 0) {
      list.innerHTML = '<div class="muted">No contacts</div>';
      return;
    }
    list.innerHTML = rows.map(r => {
      const name = r.name || '';
      const cmp  = r.company || '';
      const email= r.email || '';
      const flags= [];
      if (r.is_main) flags.push('Main');
      if (r.is_current) flags.push('Current');
      const flagTxt = flags.length ? `<small class="badge">${flags.join(', ')}</small>` : '';
      return `
        <div class="contact-item" data-id="${r.vc_id}">
          <div class="info">
            <div><strong>${name}</strong> ${flagTxt}</div>
            <div class="small">${[cmp,email].filter(Boolean).join(' — ')}</div>
          </div>
          <div class="actions">
            <button class="btn btn-ghost act-edit">Edit</button>
            <button class="btn btn-danger act-del">Delete</button>
          </div>
        </div>`;
    }).join('');
  }

  function loadList() {
    fetch(`/api/visions/${encodeURIComponent(slug)}/contacts`)
      .then(r => r.json())
      .then(renderList)
      .catch(() => { list.innerHTML = '<div class="error">Failed to load contacts</div>'; });
  }

  addBtn.addEventListener('click', () => {
    clearForm();
    toggleForm(true);
  });

  cancelBtn.addEventListener('click', () => {
    toggleForm(false);
  });

  fieldsWrap.addEventListener('change', (e) => {
    // handle 'Custom…' → switch to free text key
    const sel = e.target.closest('select.field-key');
    if (!sel) return;
    if (sel.value === 'Custom…') {
      const newKey = prompt('Enter custom key');
      if (newKey) {
        const opt = document.createElement('option');
        opt.value = newKey;
        opt.textContent = newKey;
        // Insert before 'Custom…'
        sel.insertBefore(opt, sel.querySelector('option[value="Custom…"]'));
        sel.value = newKey;
      } else {
        sel.value = 'Name'; // fallback
      }
    }
  });

  fieldsWrap.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-remove-field')) {
      e.target.closest('.field-row').remove();
    }
  });

  addField.addEventListener('click', () => { addFieldRow(); });

  // Submit (create or update)
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const vcId = form.vc_id.value.trim();
    const keyEls = form.querySelectorAll('.field-key');
    const valEls = form.querySelectorAll('.field-value');

    const keys   = Array.from(keyEls).map(el => el.value.trim());
    const values = Array.from(valEls).map(el => el.value.trim());

    const fd = new FormData();
    keys.forEach(k => fd.append('keys[]', k));
    values.forEach(v => fd.append('values[]', v));
    if (form.is_current.checked) fd.append('is_current','1');
    if (form.is_main.checked)    fd.append('is_main','1');
    if (form.show_on_dashboard.checked) fd.append('show_on_dashboard','1');
    if (form.show_on_trip.checked)      fd.append('show_on_trip','1');

    const url = vcId
      ? `/api/visions/${encodeURIComponent(slug)}/contacts/${encodeURIComponent(vcId)}`
      : `/api/visions/${encodeURIComponent(slug)}/contacts/create`;

    fetch(url, { method:'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j && j.success) {
          toggleForm(false);
          loadList();
        } else {
          alert(j?.error || 'Save failed');
        }
      })
      .catch(() => { alert('Save failed'); });
  });

  // Edit / Delete buttons in list
  list.addEventListener('click', (e) => {
    const row = e.target.closest('.contact-item');
    if (!row) return;
    const id = row.dataset.id;

    // Delete
    if (e.target.classList.contains('act-del')) {
      if (!confirm('Delete this contact?')) return;
      fetch(`/api/visions/${encodeURIComponent(slug)}/contacts/${encodeURIComponent(id)}/delete`, { method:'DELETE' })
        .then(r => r.json())
        .then(j => {
          if (j && j.success) loadList(); else alert(j?.error || 'Delete failed');
        })
        .catch(() => { alert('Delete failed'); });
      return;
    }

    // Edit: load fields + flags
    if (e.target.classList.contains('act-edit')) {
      fetch(`/api/visions/${encodeURIComponent(slug)}/contacts/${encodeURIComponent(id)}/get`)
        .then(r => r.json())
        .then(j => {
          clearForm();
          form.vc_id.value = j.id || '';
          // Fields
          j.fields.forEach((f, i) => {
            addFieldRow(f.field_key, f.field_value);
          });
          // Flags
          form.is_current.checked = (j.flags.is_current || 0) == 1;
          form.is_main.checked    = (j.flags.is_main    || 0) == 1;
          form.show_on_dashboard.checked = (j.flags.show_on_dashboard || 0) == 1;
          form.show_on_trip.checked      = (j.flags.show_on_trip      || 0) == 1;
          toggleForm(true);
        })
        .catch(() => { alert('Failed to load contact'); });
    }
  });

  // Initial load
  loadList();
})();
</script>-
