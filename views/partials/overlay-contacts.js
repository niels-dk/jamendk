// /public/js/overlay-contacts.js
(() => {
  // Initialise the Contacts overlay once per insertion
  function initContacts(root) {
    if (!root || root.__contactsInit) return;
    root.__contactsInit = true;

    const wrap       = root.querySelector('#contactsWrap');
    if (!wrap) return;
    const slug       = wrap.dataset.slug;
    const list       = wrap.querySelector('#contactsList');
    const card       = wrap.querySelector('#contactFormCard');
    const form       = wrap.querySelector('#contactForm');
    const fieldsWrap = wrap.querySelector('#fieldsWrap');

    const keyOptions = ['Name','Company','Address','Mobile','Email','Country','Custom…'];

    const fieldRow = (key='', val='') => `
      <div class="field-row">
        <select class="field-key">
          ${keyOptions.map(op => `<option value="${op}" ${op===key?'selected':''}>${op}</option>`).join('')}
        </select>
        <input type="text" class="field-value" value="${val}">
        <button type="button" class="btn btn-ghost btn-remove-field">–</button>
      </div>
    `;

    function addFieldRow(k='',v='') { fieldsWrap.insertAdjacentHTML('beforeend', fieldRow(k,v)); }
    function clearForm() {
      form.reset();
      form.querySelector('[name="vc_id"]').value = '';
      fieldsWrap.innerHTML = '';
      addFieldRow('Name','');
    }
    function toggleForm(show){ card.hidden = !show; if (show) card.scrollIntoView({behavior:'smooth', block:'nearest'}); }

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
      fetch(`/api/visions/${encodeURIComponent(slug)}/contacts`, {credentials:'same-origin'})
        .then(r => r.json())
        .then(renderList)
        .catch(() => { list.innerHTML = '<div class="error">Failed to load contacts</div>'; });
    }

    // Delegated clicks inside the overlay root (works even if elements re-render)
    root.addEventListener('click', (e) => {
      // Add contact
      if (e.target.closest('#btnAddContact')) {
        clearForm(); toggleForm(true); return;
      }
      // Cancel form
      if (e.target.closest('#btnCancelContact')) {
        toggleForm(false); return;
      }
      // Add field row
      if (e.target.closest('#btnAddField')) {
        addFieldRow(); return;
      }
      // Remove field row
      if (e.target.closest('.btn-remove-field')) {
        e.target.closest('.field-row')?.remove(); return;
      }
      // Delete contact
      const delBtn = e.target.closest('.act-del');
      if (delBtn) {
        const id = delBtn.closest('.contact-item')?.dataset.id;
        if (!id) return;
        if (!confirm('Delete this contact?')) return;
        fetch(`/api/visions/${encodeURIComponent(slug)}/contacts/${encodeURIComponent(id)}/delete`, {
          method:'DELETE', credentials:'same-origin'
        })
        .then(r => r.json())
        .then(j => { if (j && j.success) loadList(); else alert(j?.error || 'Delete failed'); })
        .catch(() => alert('Delete failed'));
        return;
      }
      // Edit contact
      const editBtn = e.target.closest('.act-edit');
      if (editBtn) {
        const id = editBtn.closest('.contact-item')?.dataset.id;
        if (!id) return;
        fetch(`/api/visions/${encodeURIComponent(slug)}/contacts/${encodeURIComponent(id)}/get`, {credentials:'same-origin'})
          .then(r => r.json())
          .then(j => {
            clearForm();
            form.querySelector('[name="vc_id"]').value = j.id || '';
            (j.fields || []).forEach(f => addFieldRow(f.field_key, f.field_value));
            form.is_current.checked = (j.flags?.is_current || 0) == 1;
            form.is_main.checked    = (j.flags?.is_main    || 0) == 1;
            form.show_on_dashboard.checked = (j.flags?.show_on_dashboard || 0) == 1;
            form.show_on_trip.checked      = (j.flags?.show_on_trip      || 0) == 1;
            toggleForm(true);
          })
          .catch(() => alert('Failed to load contact'));
      }
    });

    // Form submit (create/update)
    root.addEventListener('submit', (e) => {
      const f = e.target.closest('#contactForm');
      if (!f) return;
      e.preventDefault();

      const vcId = f.querySelector('[name="vc_id"]').value.trim();
      const keys   = Array.from(f.querySelectorAll('.field-key')).map(el => el.value.trim());
      const values = Array.from(f.querySelectorAll('.field-value')).map(el => el.value.trim());

      const fd = new FormData();
      keys.forEach(k => fd.append('keys[]', k));
      values.forEach(v => fd.append('values[]', v));
      if (f.is_current.checked)        fd.append('is_current','1');
      if (f.is_main.checked)           fd.append('is_main','1');
      if (f.show_on_dashboard.checked) fd.append('show_on_dashboard','1');
      if (f.show_on_trip.checked)      fd.append('show_on_trip','1');

      const url = vcId
        ? `/api/visions/${encodeURIComponent(slug)}/contacts/${encodeURIComponent(vcId)}`
        : `/api/visions/${encodeURIComponent(slug)}/contacts/create`;

      fetch(url, { method:'POST', body: fd, credentials:'same-origin' })
        .then(r => r.json())
        .then(j => {
          if (j && j.success) { toggleForm(false); loadList(); }
          else alert(j?.error || 'Save failed');
        })
        .catch(() => alert('Save failed'));
    });

    // Field key "Custom…" → inline add
    root.addEventListener('change', (e) => {
      const sel = e.target.closest('select.field-key');
      if (!sel || sel.value !== 'Custom…') return;
      const newKey = prompt('Enter custom key');
      if (newKey) {
        const opt = document.createElement('option');
        opt.value = newKey; opt.textContent = newKey;
        sel.insertBefore(opt, Array.from(sel.options).find(o => o.value === 'Custom…'));
        sel.value = newKey;
      } else {
        sel.value = 'Name';
      }
    });

    // First load
    loadList();
  }

  // Initialise when the overlay node is inserted by AJAX
  const mo = new MutationObserver((muts) => {
    for (const m of muts) {
      m.addedNodes.forEach(n => {
        if (!(n instanceof HTMLElement)) return;
        if (n.id === 'overlay-contacts' || n.matches?.('#overlay-contacts')) {
          initContacts(n);
        }
        // Also initialise if the overlay is nested deeper
        const nested = n.querySelector?.('#overlay-contacts');
        if (nested) initContacts(nested);
      });
    }
  });
  mo.observe(document.body, { childList:true, subtree:true });

  // If it’s already in the DOM (pre-render), init immediately
  const existing = document.getElementById('overlay-contacts');
  if (existing) initContacts(existing);
})();
