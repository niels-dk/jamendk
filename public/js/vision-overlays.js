/* public/js/vision-overlays.js – Lazy-loaded overlays for visions */
document.addEventListener('DOMContentLoaded', () => {
  const overlayShell   = document.getElementById('overlay-shell');
  const overlayPanel   = overlayShell?.querySelector('.overlay-panel');
  const overlayContent = document.getElementById('overlay-content');
  const overlayClose   = overlayShell?.querySelector('.close-overlay');
  if (!overlayShell || !overlayContent || !overlayClose) return;

  // ───────────────────────────────────────────────────────────────────────────
  // NEW: overlays that use custom endpoints → no generic autosave
  const SKIP_SECTIONS = new Set(['budget', 'contacts', 'relations', 'basics', 'documents']);
  // ───────────────────────────────────────────────────────────────────────────

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
    bindOverlay(section, slug);  // attach only the minimal wiring
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

  // Bind save-on-change / per-section initializers
  function bindOverlay(section, slug) {
    const panel = overlayPanel;

    // Documents overlay: attach its own upload handler,
    // but still skip the generic autosave logic.
    if (section === 'documents') {
      bindDocumentsOverlay(panel, slug);
      return;
    }

    // Skip autosave for custom sections
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

	// Documents overlay: multi-file upload + inline status + table prepend
	  function bindDocumentsOverlay(root, slug) {
		// Look inside the currently injected content
		const form      = root.querySelector('#documentUploadForm');
		const tableBody = root.querySelector('#docsTable tbody');
		const statusEl  = root.querySelector('#uploadStatus');

		if (!form || !tableBody || !statusEl) return;

		form.addEventListener('submit', async (ev) => {
		  ev.preventDefault();

		  const data  = new FormData(form);
		  const files = data.getAll('file[]');
		  if (!files || files.length === 0) {
			statusEl.textContent = 'Please choose at least one file.';
			return;
		  }

		  statusEl.textContent = 'Uploading…';

		  try {
			const res  = await fetch(`/api/visions/${slug}/documents`, { method: 'POST', body: data });
			const json = await res.json();

			if (!res.ok || !json.success) {
			  statusEl.textContent = '❌ ' + (json.error || 'Upload failed');
			  return;
			}

			(json.files || []).forEach(f => {
			  const tr = document.createElement('tr');
			  const uploaded = (f.created_at || '').replace('T',' ').slice(0,16); // fallback formatting
			  tr.innerHTML = `
				<td><div class="doc-name">${f.file_name}</div></td>
				<td>${f.version}</td>
				<td><span class="status-pill">${f.status.charAt(0).toUpperCase()+f.status.slice(1)}</span></td>
				<td class="doc-meta">${uploaded || new Date().toISOString().replace('T',' ').slice(0,16)}</td>
				<td><a class="action-link" href="${f.download_url}">Download</a></td>
			  `;
			  tableBody.prepend(tr);
			});


			if (json.errors && json.errors.length) {
			  statusEl.textContent = '✅ Uploaded with warnings for some files.';
			} else {
			  statusEl.textContent = '✅ Uploaded';
			}

			form.reset(); // keep overlay open, just clear selection
		  } catch (err) {
			console.error(err);
			statusEl.textContent = '❌ Upload failed';
		  }
		});
	  }

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
