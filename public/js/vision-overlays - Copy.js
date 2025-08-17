/* public/js/vision-overlays.js – Lazy-loaded overlays for visions */
document.addEventListener('DOMContentLoaded', () => {
  const overlayShell  = document.getElementById('overlay-shell');
  const overlayPanel  = overlayShell?.querySelector('.overlay-panel');
  const overlayContent= document.getElementById('overlay-content');
  const overlayClose  = overlayShell?.querySelector('.close-overlay');
  if (!overlayShell || !overlayContent || !overlayClose) return;

  // Cache: section -> {html, ts}
  const cache = {};
  const TTL = 60000; // 60s

  // Find nav links with data-overlay
  document.querySelectorAll('[data-overlay]').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const section = link.dataset.overlay;
      const slug = document.querySelector('input[name="slug"]')?.value;
      if (!slug) return;
      openOverlay(slug, section, link);
    });
  });

  // Open overlay
  async function openOverlay(slug, section, trigger) {
    // fetch from cache or server
    const now = Date.now();
    let html;
    if (cache[section] && now - cache[section].ts < TTL) {
      html = cache[section].html;
    } else {
      const res = await fetch(`/visions/${slug}/overlay/${section}`);
      if (!res.ok) {
        overlayContent.innerHTML = `<p>Error loading overlay.</p>`;
        showOverlay();
        return;
      }
      html = await res.text();
      cache[section] = {html, ts: now};
    }
    overlayContent.innerHTML = html;
    bindOverlay(section, slug);
    showOverlay(trigger);
  }

  function showOverlay(trigger) {
    overlayShell.classList.remove('overlay-hidden');
    document.body.style.overflow = 'hidden';
    // focus first interactive element
    setTimeout(() => {
      const focusable = overlayPanel.querySelector('input,select,button,textarea');
      if (focusable) focusable.focus();
    }, 0);
    overlayClose.onclick = () => closeOverlay(trigger);
    document.addEventListener('keydown', escClose);
  }

  function closeOverlay(trigger) {
    overlayShell.classList.add('overlay-hidden');
    document.body.style.overflow = '';
    document.removeEventListener('keydown', escClose);
    if (trigger) trigger.focus();
  }

  function escClose(e) {
    if (e.key === 'Escape') {
      closeOverlay(document.querySelector('[data-overlay]'));
    }
  }

  // Bind save-on-change
  function bindOverlay(section, slug) {
    const panel = overlayPanel;
    // Entire switch row toggles the checkbox
    panel.querySelectorAll('.switch').forEach(sw => {
      sw.addEventListener('click', ev => {
        const chk = sw.querySelector('input[type="checkbox"]');
        if (!chk || ev.target === chk) return;
        chk.checked = !chk.checked;
        chk.dispatchEvent(new Event('change', {bubbles:true}));
      });
    });
    // Save on change or blur
    panel.querySelectorAll('input,select,textarea').forEach(el => {
      el.addEventListener('change', () => sendSection(section, slug));
      el.addEventListener('blur', () => sendSection(section, slug));
    });
  }

  let saveTimers = {};
  function sendSection(section, slug) {
    clearTimeout(saveTimers[section]);
    saveTimers[section] = setTimeout(async () => {
      const data = new FormData(overlayPanel.querySelector('form') || overlayPanel);
      try {
        await fetch(`/api/visions/${slug}/${section}`, {method:'POST', body:data});
        // On success: no toast needed
      } catch (err) {
        console.error('Overlay save failed', err);
      }
    }, 300);
  }
});
