document.addEventListener('DOMContentLoaded', () => {
  const fab       = document.getElementById('fabNewDream');
  const modal     = document.getElementById('dreamModal');
  const closeBtn  = document.getElementById('closeModal');
  const form      = document.getElementById('dreamForm');
  const queueKey  = 'dreamQueue';
  const snack     = document.getElementById('snackbar');

  function showSnack(msg, dur = 3000) {
    if (!snack) return;
    snack.textContent = msg;
    snack.classList.add('show');
    setTimeout(() => snack.classList.remove('show'), dur);
  }

  function openModal() {
    modal.classList.remove('modal-hidden');
    form.title.focus();
  }
  function closeModal() {
    modal.classList.add('modal-hidden');
  }

  // Intercept header /dreams/new link when offline
  document.querySelectorAll('a[href="/dreams/new"]').forEach(link => {
    link.addEventListener('click', e => {
      if (!navigator.onLine) {
        e.preventDefault();
        openModal();
      }
    });
  });

  // FAB behavior: online → navigate, offline → open modal
  fab.addEventListener('click', () => {
    if (navigator.onLine) {
      location.href = '/dreams/new';
    } else {
      openModal();
    }
  });
  closeBtn.addEventListener('click', closeModal);

  // Anchor add/remove logic
  document.querySelectorAll('.add-anchor').forEach(btn => {
    btn.addEventListener('click', () => {
      const type   = btn.dataset.anchor; // locations, brands, people, seasons
      const listEl = document.querySelector(`.anchor-group[data-anchor="${type}"] .anchor-list`);
      const wrapper = document.createElement('div');
      wrapper.style = 'display:flex;align-items:center;gap:0.4rem';

      const input = document.createElement('input');
      input.name        = `${type}[]`;
      input.placeholder = type.charAt(0).toUpperCase() + type.slice(1);
      wrapper.appendChild(input);

      const rem = document.createElement('button');
      rem.type        = 'button';
      rem.textContent = '✕';
      rem.style       = 'background:var(--danger);color:#fff;border:none;border-radius:4px;padding:0 .6rem';
      rem.addEventListener('click', () => wrapper.remove());
      wrapper.appendChild(rem);

      listEl.appendChild(wrapper);
      input.focus();
    });
  });

  // Load last offline draft into modal
  const drafts = JSON.parse(localStorage.getItem(queueKey) || '[]');
  const last   = drafts[drafts.length - 1] || {};
  if (last.title)       form.title.value       = last.title;
  if (last.description) form.description.value = last.description;
  ['locations','brands','people','seasons'].forEach(key => {
    (last[key]||[]).forEach(v => {
      const btn = document.querySelector(`.add-anchor[data-anchor="${key}"]`);
      btn.click();
      const inputs = document.querySelectorAll(`.anchor-group[data-anchor="${key}"] .anchor-list input`);
      inputs[inputs.length - 1].value = v;
    });
  });

  // Intercept form submit when offline
  form.addEventListener('submit', e => {
    if (!navigator.onLine) {
      e.preventDefault();

      // Build payload, strip "[]" from keys
      const data = new FormData(form);
      const payload = {};
      data.forEach((v,k) => {
        if (k.endsWith('[]')) {
          const name = k.slice(0,-2);
          payload[name] = payload[name] || [];
          payload[name].push(v);
        } else {
          payload[k] = v;
        }
      });

      // Queue it
      const q = JSON.parse(localStorage.getItem(queueKey) || '[]');
      q.push(payload);
      localStorage.setItem(queueKey, JSON.stringify(q));

      // Reset form & anchors
      form.reset();
      document.querySelectorAll('.anchor-list').forEach(el => el.innerHTML = '');

      // Feedback & close
      showSnack('Dream saved locally!');
      closeModal();

      // Trigger offline-ui to update dashboard cards
      window.dispatchEvent(new Event('offline'));
    }
    // else: allow normal POST to /dreams/store.php
  });
});
