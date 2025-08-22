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
			  const uploaded = (f.created_at || new Date().toISOString()).replace('T',' ').slice(0,16);
			  const statusVal = (f.status || 'draft');
			  const statusLabel = statusVal.charAt(0).toUpperCase()+statusVal.slice(1).replace('_',' ');
			  tr.innerHTML = `
				<td><div class="doc-name">${f.file_name}</div></td>
				<td>${f.version}</td>
				<td>
				  <span class="status-pill js-status" data-uuid="${f.uuid}">${statusLabel}</span>
				  <select class="status-select" data-uuid="${f.uuid}" style="display:none">
					<option value="draft" ${statusVal==='draft'?'selected':''}>Draft</option>
					<option value="waiting_brand" ${statusVal==='waiting_brand'?'selected':''}>Waiting Brand</option>
					<option value="final" ${statusVal==='final'?'selected':''}>Final</option>
					<option value="signed" ${statusVal==='signed'?'selected':''}>Signed</option>
				  </select>
				</td>
				<td class="doc-meta">${uploaded.slice(0,16)}</td>
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
		  
		// Inline status editor: click pill -> show select; change -> POST; ESC/blur -> cancel
		root.addEventListener('click', (e) => {
		  const pill = e.target.closest('.js-status');
		  if (!pill) return;
		  const sel = pill.nextElementSibling;
		  if (!sel || !sel.classList.contains('status-select')) return;
		  pill.style.display = 'none';
		  sel.style.display  = 'inline-block';
		  sel.focus();
		});

		root.addEventListener('keydown', (e) => {
		  if (e.target.classList && e.target.classList.contains('status-select') && e.key === 'Escape') {
			const sel  = e.target;
			const pill = sel.previousElementSibling;
			sel.style.display  = 'none';
			pill.style.display = 'inline-block';
		  }
		});

		root.addEventListener('blur', (e) => {
		  if (e.target.classList && e.target.classList.contains('status-select')) {
			// On blur without change, just switch back to pill (keeps last value in select)
			const sel  = e.target;
			const pill = sel.previousElementSibling;
			setTimeout(() => { // allow click on menu items first
			  if (document.activeElement !== sel) {
				sel.style.display  = 'none';
				pill.style.display = 'inline-block';
			  }
			}, 150);
		  }
		}, true);

		  let groupsCache = [];

			async function fetchGroups(slug) {
			  const res = await fetch(`/api/visions/${slug}/groups`);
			  const json = await res.json();
			  groupsCache = json.success ? json.groups : [];
			}

			function buildGroupSelect(selectEl, currentId) {
			  const opts = ['<option value="">— No Group —</option>']
				.concat(groupsCache.map(g => `<option value="${g.id}" ${String(g.id)===String(currentId)?'selected':''}>${g.name}</option>`))
				.concat('<option value="__create__">+ Create new…</option>');
			  selectEl.innerHTML = opts.join('');
			}

			root.addEventListener('click', async (e) => {
			  const pill = e.target.closest('.js-group');
			  if (!pill) return;
			  const sel = pill.nextElementSibling; // .group-select
			  const btn = sel?.nextElementSibling; // .group-create-btn
			  if (!sel) return;

			  await fetchGroups(slug);
			  buildGroupSelect(sel, pill.dataset.current || '');

			  pill.style.display = 'none';
			  sel.style.display  = 'inline-block';
			  if (btn) btn.style.display = 'inline-block';
			  sel.focus();
			});

			root.addEventListener('change', async (e) => {
			  if (!e.target.classList.contains('group-select')) return;
			  const sel  = e.target, pill = sel.previousElementSibling;
			  const uuid = sel.dataset.uuid;
			  const value = sel.value;

			  if (value === '__create__') {
				const name = prompt('New group name:');
				if (!name) { sel.value = pill.dataset.current || ''; return; }

				const form = new FormData(); form.append('name', name);
				const res1 = await fetch(`/api/visions/${slug}/groups:create`, { method:'POST', body: form });
				const j1   = await res1.json();
				if (!res1.ok || !j1.success) { alert(j1.error || 'Create failed'); sel.value = pill.dataset.current || ''; return; }

				// add to cache and set as selected
				groupsCache.push(j1.group);
				buildGroupSelect(sel, j1.group.id);
				sel.value = String(j1.group.id);
			  }

			  // set group on document
			  const fd = new URLSearchParams(); fd.set('group_id', sel.value === '' ? '' : sel.value);
			  const res2 = await fetch(`/api/documents/${uuid}/group`, { method:'POST', body: fd, headers:{'Content-Type':'application/x-www-form-urlencoded'} });
			  const j2   = await res2.json();
			  if (!res2.ok || !j2.success) { alert(j2.error || 'Update failed'); return; }

			  // update pill label + state
			  const gid = j2.group_id;
			  const found = groupsCache.find(g => String(g.id) === String(gid));
			  pill.textContent = found ? found.name : '—';
			  pill.dataset.current = gid || '';
			  sel.style.display = 'none';
			  if (sel.nextElementSibling) sel.nextElementSibling.style.display = 'none';
			  pill.style.display = 'inline-block';
			});

		  
		root.addEventListener('change', async (e) => {
		  if (!(e.target.classList && e.target.classList.contains('status-select'))) return;
		  const sel   = e.target;
		  const uuid  = sel.dataset.uuid;
		  const value = sel.value;

		  try {
			const res  = await fetch(`/api/documents/${uuid}/status`, {
			  method: 'POST',
			  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			  body: `status=${encodeURIComponent(value)}`
			});
			const json = await res.json();

			const pill = sel.previousElementSibling;
			if (res.ok && json.success) {
			  const label = value.charAt(0).toUpperCase()+value.slice(1).replace('_',' ');
			  pill.textContent = label;
			  // Optional: visual tweak for final/signed
			  pill.classList.toggle('is-final', value === 'final' || value === 'signed');
			} else {
			  alert(json.error || 'Failed to update status');
			  // revert select to pill’s current text
			  const current = (pill.textContent || 'Draft').toLowerCase().replace(' ','_');
			  sel.value = current;
			}
			sel.style.display  = 'none';
			pill.style.display = 'inline-block';
		  } catch (err) {
			console.error(err);
			alert('Network error updating status');
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
