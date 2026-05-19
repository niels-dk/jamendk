/* public/js/vision-overlays.js – Lazy-loaded overlays for visions */
document.addEventListener('DOMContentLoaded', () => {
  const overlayShell   = document.getElementById('overlay-shell');
  const overlayPanel   = overlayShell?.querySelector('.overlay-panel');
  const overlayContent = document.getElementById('overlay-content');
  const overlayClose   = overlayShell?.querySelector('.close-overlay');
  if (!overlayShell || !overlayContent || !overlayClose) return;

  // ───────────────────────────────────────────────────────────────────────────
  // NEW: overlays that use custom endpoints → no generic autosave
  const SKIP_SECTIONS = new Set(['budget', 'contacts', 'relations', 'basics', 'documents', 'workflow', 'goals']);
  // ───────────────────────────────────────────────────────────────────────────

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

  // Open overlay – always fetch fresh so values reflect latest DB state
  async function openOverlay(slug, section, trigger) {
    const res = await fetch(`/visions/${slug}/overlay/${section}`, { cache: 'no-store' });
    if (!res.ok) {
      overlayContent.innerHTML = `<p>Error loading overlay.</p>`;
      showOverlay();
      return;
    }
    const html = await res.text();
    overlayContent.innerHTML = html;
    executeInlineScripts(overlayContent);
    bindOverlay(section, slug);  // attach only the minimal wiring
    // On small screens, the left sidebar would overlap the overlay — collapse it
    if (window.innerWidth <= 760) {
      document.querySelector('.sidebar')?.classList.add('collapsed');
    }
    showOverlay(trigger);
  }

  // <script> tags inserted via innerHTML do not execute. Re-create them so they do.
  function executeInlineScripts(container) {
    container.querySelectorAll('script').forEach(oldScript => {
      const newScript = document.createElement('script');
      for (const attr of oldScript.attributes) newScript.setAttribute(attr.name, attr.value);
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
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

  // Bind save-on-change / per-section initializers
  function bindOverlay(section, slug) {
    const panel = overlayPanel;

    // Skip autosave for custom sections (they wire up their own handlers)
    if (SKIP_SECTIONS.has(section)) return;

    // Entire switch row toggles the checkbox
    panel.querySelectorAll('.switch, .switch-row').forEach(sw => {
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
      el.addEventListener('blur',   () => sendSection(section, slug));
    });
  }

  // Documents overlay owns its own behavior via inline script in overlay_documents.php

  let saveTimers = {};
  function sendSection(section, slug) {
    clearTimeout(saveTimers[section]);
    saveTimers[section] = setTimeout(async () => {

      // NEW: get the form inside this overlay, and skip if empty
      const root = overlayPanel.querySelector(`#overlay-${section}`) || overlayPanel;
      const form = root.querySelector('form');
      if (!form) return;
      const data = new FormData(form);
      if ([...data.keys()].length === 0) return; // nothing to send → avoid 422

      try {
        await fetch(`/api/visions/${slug}/${section}`, { method: 'POST', body: data });
        // no toast needed
      } catch (err) {
        console.error('Overlay save failed', err);
      }
    }, 300);
  }
});
