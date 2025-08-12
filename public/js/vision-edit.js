/* public/js/vision-edit.js – Save logic for main Vision form */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('visionForm');
  if (!form) return;

  const saveBtn  = form.querySelector('.btn.primary');
  const moreBtn  = document.getElementById('visionMoreBtn');
  const moreMenu = document.getElementById('visionMoreMenu');
  let nextAction = 'view';
  let dirty      = false;

  // Split menu toggling
  if (moreBtn) {
    moreBtn.addEventListener('click', () => {
      moreMenu.style.display = moreMenu.style.display === 'block' ? 'none' : 'block';
    });
    moreMenu.addEventListener('click', e => {
      const go = e.target.dataset.go;
      if (go) {
        nextAction = go;
        saveBtn.click();
        moreMenu.style.display = 'none';
      }
    });
  }

  // Mark dirty
  const markDirty = () => { dirty = true; };
  form.querySelector('input[name="title"]').addEventListener('input', markDirty);
  const descInput = document.getElementById('vision-desc');
  if (descInput) descInput.addEventListener('change', markDirty);
  const trix = form.querySelector('trix-editor');
  if (trix) trix.addEventListener('trix-change', markDirty);
  form.querySelectorAll('.anchors').forEach(wrap => {
    wrap.addEventListener('blur', markDirty, true);
    wrap.addEventListener('change', markDirty, true);
  });

  // Warn on navigation if unsaved
  window.addEventListener('beforeunload', e => {
    if (dirty) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // Submit handler
  form.addEventListener('submit', async ev => {
    ev.preventDefault();
    const slug = form.querySelector('input[name="slug"]')?.value;
    if (!slug) return;
    // Build payload
    const payload = {};
    payload.title = form.querySelector('input[name="title"]').value.trim() || null;
    payload.description = descInput.value.trim() || null;
    // Anchors
    const anchors = [];
    form.querySelectorAll('.anchors .anchors-row').forEach(row => {
      let key, val;
      const keyField = row.querySelector('.anchor-key');
      if (keyField) {
        if (keyField.tagName === 'INPUT') key = keyField.value.trim();
        else key = keyField.value.trim();
      } else key = '';
      const valField = row.querySelector('.anchor-value');
      val = valField ? valField.value.trim() : '';
      if (key && val) anchors.push({key, value: val});
    });
    payload.anchors = anchors;

    try {
      saveBtn.disabled = true;
      const orig = saveBtn.textContent;
      saveBtn.textContent = 'Saving…';
      const res = await fetch(`/api/visions/${slug}/save`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (!json.ok) {
        alert(json.error || 'Save failed');
        saveBtn.textContent = orig;
      } else {
        dirty = false;
        if (nextAction === 'stay') {
          saveBtn.textContent = 'Saved!';
          setTimeout(() => saveBtn.textContent = orig, 1200);
          nextAction = 'view';
        } else if (nextAction === 'dash') {
          window.location = '/dashboard';
        } else {
          window.location = `/visions/${slug}`;
        }
      }
    } catch (err) {
      alert('Save error: '+ err);
    } finally {
      saveBtn.disabled = false;
    }
  });
});
