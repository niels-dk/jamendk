/* vision-edit.js – client logic for editing visions */

if (!window.VISION_EDIT_INITED) {
  window.VISION_EDIT_INITED = true;

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('visionForm');
    if (!form) return;

    const primaryBtn = form.querySelector('.btn.primary');
    const moreBtn    = document.getElementById('visionMoreBtn');
    const moreMenu   = document.getElementById('visionMoreMenu');
    let nextAction   = 'view';             // default redirect behaviour
    let dirty        = false;              // track unsaved changes

    /* Split-button logic */
    if (moreBtn && moreMenu) {
      moreBtn.onclick = () => {
        moreMenu.style.display = (moreMenu.style.display === 'block') ? 'none' : 'block';
      };
      moreMenu.addEventListener('click', e => {
        const go = e.target.dataset.go;
        if (!go) return;
        nextAction = go;                   // stay | view | dash
        // trigger the primary save
        primaryBtn.click();
        moreMenu.style.display = 'none';
      });
    }

    /* Mark form dirty on changes */
    const markDirty = () => { dirty = true; };
    form.querySelector('input[name="title"]').addEventListener('input', markDirty);
    const descInput = document.getElementById('vision-desc');
    if (descInput) {
      descInput.addEventListener('change', markDirty);
    }
    // Trix emits trix-change events on the editor element
    const trixEl = form.querySelector('trix-editor');
    if (trixEl) {
      trixEl.addEventListener('trix-change', markDirty);
    }
    // Anchors: mark dirty on blur of inputs/selects
    form.querySelectorAll('.anchors').forEach(anchorWrap => {
      anchorWrap.addEventListener('blur', markDirty, true);
      anchorWrap.addEventListener('change', markDirty, true);
    });

    /* Warn when navigating away with unsaved changes */
    window.addEventListener('beforeunload', e => {
      if (dirty) {
        e.preventDefault();
        e.returnValue = '';
      }
    });

    /* Submit handler – gather data and send JSON to API */
    form.addEventListener('submit', async ev => {
      ev.preventDefault();
      if (!primaryBtn) return;

      const slugInput = form.querySelector('input[name="slug"]');
      const slug      = slugInput ? slugInput.value : null;
      if (!slug) {
        alert('Missing slug');
        return;
      }

      // Build payload
      const titleEl = form.querySelector('input[name="title"]');
      const payload = {};
      if (titleEl) payload.title = titleEl.value.trim() || null;
      if (descInput) payload.description = descInput.value.trim() || null;
      // Build anchors array
      const anchors = [];
      form.querySelectorAll('.anchors .anchors-row').forEach(row => {
        let key;
        const keyField = row.querySelector('.anchor-key');
        if (!keyField) {
          key = '';
        } else if (keyField.tagName === 'INPUT') {
          key = keyField.value.trim();
        } else {
          key = keyField.value.trim();
        }
        const valField = row.querySelector('.anchor-value');
        const val = valField ? valField.value.trim() : '';
        if (key !== '' && val !== '') anchors.push({ key, value: val });
      });
      payload.anchors = anchors;

      try {
        primaryBtn.disabled = true;
        const origText = primaryBtn.textContent;
        primaryBtn.textContent = 'Saving…';

        const res  = await fetch(`/api/visions/${slug}/save`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.ok) {
          dirty = false;
          if (nextAction === 'stay') {
            // user wants to stay on the edit page
            primaryBtn.textContent = 'Saved!';
            setTimeout(() => primaryBtn.textContent = origText, 1200);
            nextAction = 'view';
          } else if (nextAction === 'dash') {
            window.location = '/dashboard';
          } else {
            window.location = `/visions/${slug}`;
          }
        } else {
          alert(json.error || 'Save failed');
          primaryBtn.textContent = origText;
        }
      } catch (err) {
        alert('Network error: ' + err);
      } finally {
        primaryBtn.disabled = false;
      }
    });

    /* Basics overlay – auto-save on change */
    const basicsForm = document.getElementById('basicsForm');
    if (basicsForm) {
      const saveBasics = () => {
        const visionIdField = basicsForm.querySelector('input[name="vision_id"]');
        if (!visionIdField) return;
        const data = new FormData(basicsForm);
        fetch('/api/visions/update-basics', { method:'POST', body:data })
          .catch(err => console.error('Basics save error', err));
      };
      ['change','input'].forEach(evt => basicsForm.addEventListener(evt, saveBasics));
    }
  });
}
