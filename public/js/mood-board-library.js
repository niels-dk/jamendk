// public/js/mood-board-library.js

(function () {
  // -----------------------------
  // Small DOM helpers
  // -----------------------------
  const $  = (sel, el) => (el || document).querySelector(sel);
  const $$ = (sel, el) => Array.from((el || document).querySelectorAll(sel));

  // -----------------------------
  // Root & context
  // -----------------------------
  const root = document.getElementById('mood-lib-root');
  if (!root) return;

  const visionSlug = root.dataset.visionSlug || '';  // may be empty for standalone boards
  const boardSlug  = root.dataset.boardSlug || '';   // required for board ops
  const boardId    = parseInt(root.dataset.boardId || '0', 10);

  const tabs = {
    board: $('[data-scope="board"]'),
    vision: $('[data-scope="vision"]')
  };

  const uploadBtn    = $('#uploadBtn');
  const uploadInput  = $('#mediaUploadInput');
  const linkBtn      = $('#linkBtn');

  const addNoteBtn       = $('#addNoteBtn');        // optional
  const addConnectorBtn  = $('#addConnectorBtn');   // optional

  const statusEl   = $('#libraryStatus');
  const typeSel    = $('#mediaTypeFilter');
  const sortSel    = $('#mediaSort');
  const qInput     = $('#mediaSearch');
  const groupSel   = document.getElementById('groupFilterSelect') || null;
  const tagsFilter = document.getElementById('tagFilterInput')    || null;

  const grid = document.getElementById('libraryGrid');
  if (!grid) { console.warn('[MediaLibrary] #libraryGrid not found'); }

  let currentScope = 'board';
  let items = [];

  // overlay
  const overlay = document.getElementById('ml-overlay');
  const sheet   = overlay ? overlay.querySelector('.ml-sheet') : null;

  // caches
  let TAGS_CACHE   = null;
  let GROUPS_CACHE = null;
	
  const btnBoard  = document.querySelector('[data-tab="board"]');
  const btnAll    = document.querySelector('[data-tab="all"]');
  const search    = document.querySelector('#media-search'); // optional
  let limit = 50, offset = 0;
	
  // -----------------------------
  // Network helpers
  // -----------------------------
  function setStatus(msg, isError=false) {
    if (!statusEl) return;
    statusEl.textContent = msg || '';
    statusEl.classList.toggle('error', !!isError);
  }

  function apiBaseSlug() {
    // For Vision-scoped endpoints we prefer visionSlug (if present),
    // else fall back to the board slug.
    return visionSlug || boardSlug;
  }

  function ytIdFromUrl(url) {
    if (!url) return '';
    try {
      const u = new URL(url);
      const host = u.hostname.replace(/^www\./,'');
      if (host === 'youtu.be') {
        const id = u.pathname.split('/').filter(Boolean)[0];
        return id || '';
      }
      if (host.endsWith('youtube.com')) {
        if (u.searchParams.get('v')) return u.searchParams.get('v') || '';
        const m1 = u.pathname.match(/\/embed\/([A-Za-z0-9_-]{6,})/);
        if (m1) return m1[1];
        const m2 = u.pathname.match(/\/shorts\/([A-Za-z0-9_-]{6,})/);
        if (m2) return m2[1];
      }
    } catch(_) {}
    const m = String(url).match(/(?:v=|youtu\.be\/|\/embed\/|\/shorts\/)([A-Za-z0-9_-]{6,})/);
    return m ? m[1] : '';
  }

  function toTagsArray(val) {
    if (!val) return [];
    if (Array.isArray(val)) return val.map(String).map(s=>s.trim()).filter(Boolean);
    return String(val).split(',').map(s=>s.trim()).filter(Boolean);
  }

  async function loadTags() {
    if (TAGS_CACHE) return TAGS_CACHE;
    try {
      const r = await fetch('/api/tags', { credentials:'same-origin' });
      const j = await r.json();
      TAGS_CACHE = (j.tags || []);
    } catch {
      TAGS_CACHE = [];
    }
    return TAGS_CACHE;
  }

  async function loadGroups() {
    if (GROUPS_CACHE) return GROUPS_CACHE;
    try {
      const base = `/api/moods/${encodeURIComponent(boardSlug)}/groups`;
      const r = await fetch(base, { credentials:'same-origin' });
      if (!r.ok) throw 0;
      const j = await r.json();
      GROUPS_CACHE = (j.groups || []).map(g => ({
        id: String(g.id),
        name: g.name,
        slug: g.slug || ''
      }));
      // prime the <select> if present
      if (groupSel) {
        const cur = groupSel.value;
        groupSel.innerHTML = `<option value="">All groups</option>` + GROUPS_CACHE.map(g => `<option value="${g.id}">${g.name}</option>`).join('');
        if (cur && GROUPS_CACHE.some(g => g.id === cur)) groupSel.value = cur;
      }
    } catch {
      GROUPS_CACHE = [];
    }
    return GROUPS_CACHE;
  }

  async function getTags(mediaId) {
    const r = await fetch(`/api/media/${mediaId}/tags`, { credentials:'same-origin' });
    if (!r.ok) throw new Error('Failed to load tags');
    const j = await r.json();
    return toTagsArray(j.tags);
  }
	  
  async function loadBoardFiles() {
	  const q    = search?.value?.trim() || '';
	  const type = typeSel?.value || '';
	  const url  = `/api/moods/${encodeURIComponent(window.moodSlug)}/media`
				 + `?limit=${limit}&offset=${offset}`
				 + (q ? `&q=${encodeURIComponent(q)}` : '')
				 + (type ? `&type=${encodeURIComponent(type)}` : '');
	  const res  = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
	  const json = await res.json();
	  if (json.success) renderMedia(json.items, json.total);
	}

	async function loadAllFiles() {
	  const q    = search?.value?.trim() || '';
	  const type = typeSel?.value || '';
	  const url  = `/api/media?limit=${limit}&offset=${offset}`
				 + (q ? `&q=${encodeURIComponent(q)}` : '')
				 + (type ? `&type=${encodeURIComponent(type)}` : '');
	  const res  = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
	  const json = await res.json();
	  if (json.success) renderMedia(json.items, json.total);
	}

	function setActive(tab) {
	  btnBoard.classList.toggle('active', tab === 'board');
	  btnAll.classList.toggle('active',   tab === 'all');
	}

  btnBoard?.addEventListener('click', () => { setActive('board'); offset = 0; loadBoardFiles(); });
  btnAll?.addEventListener('click',   () => { setActive('all');   offset = 0; loadAllFiles(); });

  // tie into search/type filters
  search?.addEventListener('input',  () => (btnBoard.classList.contains('active') ? loadBoardFiles() : loadAllFiles()));
  typeSel?.addEventListener('change',() => (btnBoard.classList.contains('active') ? loadBoardFiles() : loadAllFiles()));

  // -----------------------------
  // Overlay / Sheet
  // -----------------------------
  function openSheet(title, bodyHTML, actionsHTML) {
    if (!overlay || !sheet) return false;

    sheet.innerHTML = `
      <div class="ml-head">
        <div class="ml-title" id="ml-title">${title}</div>
        <button class="ml-close" aria-label="Close" type="button">✕</button>
      </div>
      <div class="ml-body">${bodyHTML || ''}</div>
      <div class="ml-actions">${actionsHTML || ''}</div>
    `;
    overlay.hidden = false;

    // Close handlers
    sheet.querySelector('.ml-close')?.addEventListener('click', closeSheet);
    overlay.addEventListener('click', onOverlayClickOnce, { once:true });
    document.addEventListener('keydown', onEscOnce, { once:true });

    // When opening a modal, also close any open kebab menus
    $$('.media-card .card-menu.open', grid).forEach(m => m.classList.remove('open'));

    return true;
  }
  function onEscOnce(e){ if (e.key === 'Escape') closeSheet(); }
  function onOverlayClickOnce(e){ if (e.target === overlay) closeSheet(); }
  function closeSheet(){ if (overlay) overlay.hidden = true; }

  // -----------------------------
  // Modals
  // -----------------------------
  async function openTagsModal(mediaId, preloadTags) {
    // Prefer fresh tags from API if not provided
    const normalized = Array.isArray(preloadTags) ? preloadTags : await getTags(mediaId);

    const tags = await loadTags();
    const existingSet = new Set(normalized.map(t => String(t).toLowerCase()));
    const options = (tags || [])
      .filter(t => !existingSet.has(String(t.name || t).toLowerCase()))
      .map(t => `<option value="${t.name || t}">`)
      .join('');

    openSheet(
      'Edit tags',
      `
        <div>
          <div class="ml-hint">Current tags</div>
          <div class="ml-chips" id="ml-tags">
            ${normalized.length ? normalized.map(name => `
              <span class="ml-chip" data-name="${name}">
                ${name}
                <button type="button" aria-label="remove">×</button>
              </span>`).join('') : '<span class="ml-hint">None yet</span>'}
          </div>
        </div>
        <div>
          <div class="ml-hint">Add tag</div>
          <input id="ml-tag-input" class="ml-input" list="ml-tag-datalist" placeholder="Type and press Enter">
          <datalist id="ml-tag-datalist">${options}</datalist>
        </div>
      `,
      `
        <button class="ml-btn" id="ml-cancel" type="button">Cancel</button>
        <button class="ml-btn primary" id="ml-save" type="button">Save</button>
      `
    );

    const chipBox = sheet.querySelector('#ml-tags');
    const input   = sheet.querySelector('#ml-tag-input');

    chipBox.addEventListener('click', (e) => {
      if (e.target.tagName === 'BUTTON') {
        e.target.closest('.ml-chip')?.remove();
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const val = (input.value || '').trim();
        if (!val) return;
        const has = Array.from(chipBox.querySelectorAll('.ml-chip')).some(c => c.dataset.name.toLowerCase() === val.toLowerCase());
        if (!has) chipBox.insertAdjacentHTML('beforeend', `
          <span class="ml-chip" data-name="${val}">
            ${val}
            <button type="button" aria-label="remove">×</button>
          </span>`);
        input.value = '';
      }
    });

    sheet.querySelector('#ml-cancel').addEventListener('click', closeSheet);
    sheet.querySelector('#ml-save').addEventListener('click', async () => {
      const final = Array.from(chipBox.querySelectorAll('.ml-chip')).map(c => c.dataset.name);
      const fd = new FormData();
      fd.append('tags', final.join(','));
      await fetch(`/api/media/${mediaId}/tags`, { method:'POST', body:fd, credentials:'same-origin' });
      closeSheet();
      await loadTags(); // refresh cache
      fetchList();
    });
  }

  async function openGroupModal(mediaId, currentGroupId=null) {
    const groups = await loadGroups();
    const options = ['<option value="">— No group —</option>']
      .concat(groups.map(g => `<option value="${g.id}" ${String(g.id)===String(currentGroupId)?'selected':''}>${g.name}</option>`))
      .join('');

    openSheet(
      'Change group',
      `
        <label>Choose existing group</label>
        <select class="ml-select" id="ml-group-select">${options}</select>
        <div class="ml-hint">or create a new group below</div>
        <input class="ml-input" id="ml-group-new" placeholder="New group name">
      `,
      `
        <button class="ml-btn" id="ml-cancel" type="button">Cancel</button>
        <button class="ml-btn primary" id="ml-save" type="button">Save</button>
      `
    );

    sheet.querySelector('#ml-cancel').addEventListener('click', closeSheet);
    sheet.querySelector('#ml-save').addEventListener('click', async () => {
      const sel  = sheet.querySelector('#ml-group-select');
      const name = sheet.querySelector('#ml-group-new').value.trim();
      const fd = new FormData();
      if (name) fd.append('group_name', name);
      if (sel && sel.value) fd.append('group_id', sel.value);
      fd.append('board_id', String(boardId));

      await fetch(`/api/media/${mediaId}/group`, { method:'POST', body:fd, credentials:'same-origin' });
      closeSheet();
      GROUPS_CACHE = null;
      await loadGroups();
      fetchList();
    });
  }

  function openLinkModal() {
    openSheet(
      'Add link',
      `
        <div class="ml-field">
          <label class="ml-label" for="ml-link-url">URL</label>
          <input id="ml-link-url" class="ml-input" type="url" placeholder="Paste YouTube, Vimeo or any URL…">
          <div class="ml-hint">We’ll try to detect the provider automatically.</div>
        </div>
      `,
      `
        <button class="ml-btn" id="ml-cancel" type="button">Cancel</button>
        <button class="ml-btn primary" id="ml-save" type="button">Add</button>
      `
    );

    sheet.querySelector('#ml-cancel').addEventListener('click', closeSheet);
    sheet.querySelector('#ml-save').addEventListener('click', async () => {
	  const urlStr = (sheet.querySelector('#ml-link-url').value || '').trim();
	  if (!urlStr) return;

	  // helper to try posting to one endpoint with a given field name
	  async function tryPost(endpoint, fieldName) {
		const fd = new FormData();
		fd.append(fieldName, urlStr);
		const r = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
		let data = null;
		try { data = await r.json(); } catch (_) {}
		const ok = r.ok && (data?.success !== false);
		return { ok, data, status: r.status };
	  }

	  const base = `/api/visions/${encodeURIComponent(apiBaseSlug())}`;
	  const attempts = [
		{ ep: `${base}/media:link`,   key: 'url' },
		{ ep: `${base}/media:link`,   key: 'external_url' },
		{ ep: `${base}/media:upload`, key: 'url' },
		{ ep: `${base}/media:upload`, key: 'external_url' },
	  ];

	  let resp = null;
	  for (const a of attempts) {
		try {
		  const r = await tryPost(a.ep, a.key);
		  if (r.ok && (r.data?.id || r.data?.media_id || r.data?.success)) { resp = r; break; }
		  // if 422 with message, keep last error to display
		  if (r.status === 422) resp = r;
		} catch (_) {}
	  }

	  if (!resp || !resp.ok) {
		const msg = (resp?.data?.error || resp?.data?.message || 'Could not add link (422)').toString();
		// show a tiny inline error under the input
		const hint = sheet.querySelector('.ml-hint');
		if (hint) hint.textContent = msg;
		return;
	  }

	  // attach to board if needed
	  const mediaId = resp.data?.media_id || resp.data?.id || (resp.data?.media && resp.data.media.id);
	  try {
		if (mediaId && currentScope === 'board') {
		  const fa = new FormData();
		  fa.append('media_id[]', String(mediaId));
		  await fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:attach`, {
			method: 'POST', body: fa, credentials: 'same-origin'
		  });
		}
	  } catch (_) {}

	  closeSheet();
	  if (typeof fetchList === 'function') fetchList();
	});

  }

  // -----------------------------
  // Templating
  // -----------------------------
  function bestThumb(m) {
    if (m.provider === 'youtube') {
      const pid = m.provider_id || ytIdFromUrl(m.external_url || m.url || '');
      if (pid) return `https://img.youtube.com/vi/${pid}/hqdefault.jpg`;
    }
    return m.thumb_url || (m.uuid ? `/storage/thumbs/${m.uuid}_thumb.jpg` : '');
  }
  function labelFor(m) {
	  // Prefer explicit type if already set
	  let t = (m.type || '').toLowerCase();
	  if (t) return capitalize(t);

	  // Else derive from mime_type
	  const mime = (m.mime_type || '').toLowerCase();

	  if (mime.startsWith('image/')) return 'Image';
	  if (mime.startsWith('video/')) return 'Video';
	  if (mime.startsWith('audio/')) return 'Audio';
	  if (mime.includes('pdf')) return 'PDF';
	  if (mime.includes('word') || mime.includes('doc')) return 'Document';
	  if (mime.includes('excel') || mime.includes('sheet')) return 'Spreadsheet';
	  if (mime.includes('ppt')) return 'Presentation';
	  if (mime.includes('text')) return 'Text';

	  // For external providers (e.g. video/youtube stored in mime_type)
	  if (mime.includes('youtube')) return 'YouTube';
	  if (mime.includes('vimeo')) return 'Vimeo';

	  return 'File';
	}

	function capitalize(s) {
	  return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
	}

  function renderTagChips(m) {
    const arr = Array.isArray(m.tags) ? m.tags : toTagsArray(m.tags);
    if (!arr || !arr.length) return '';
    const chips = arr.slice(0, 3).map(name => `<span class="chip">${(name.name || name).toString()}</span>`).join('');
    const more  = arr.length > 3 ? `<span class="chip more">+${arr.length-3}</span>` : '';
    return `<div class="tags-row">${chips}${more}</div>`;
  }

  function templateCard(m) {
    const thumb = bestThumb(m);
    const label = labelFor(m);
    const name  = (m.file_name || m.name || m.title || m.external_url || 'Untitled').toString();

    return `
      <div class="media-card" data-id="${m.id}" data-type="${label}" draggable="true">
        <button class="menu-toggle" aria-label="menu" type="button">⋮</button>
        <div class="thumb">
          ${thumb ? `<img src="${thumb}" alt="">` : `<div class="thumb-fallback">No preview</div>`}
          ${label === 'video' ? `<div class="play-badge">▶</div>` : ``}
        </div>
        <div class="meta">
          <div class="name" title="${name.replace(/"/g,'&quot;')}">${name}</div>
          <div class="badge">${label}</div>
        </div>
        ${renderTagChips(m)}
        <div class="card-menu">
          <ul>
            <li class="act-attach"  data-id="${m.id}">Attach to this board</li>
            <li class="act-detach"  data-id="${m.id}">Remove from this board</li>
            <li class="act-tags"    data-id="${m.id}">Edit tags</li>
            <li class="act-groups"  data-id="${m.id}">Change group</li>
            <li class="act-delete"  data-id="${m.id}">Delete</li>
          </ul>
        </div>
      </div>
    `;
  }

  function render() {
    if (!grid) return;
    if (!items.length) {
      grid.innerHTML = `<div class="empty" style="opacity:.7;padding:12px">No files yet.</div>`;
      return;
    }
    grid.innerHTML = items.map(templateCard).join('');
    bindCardEvents();
  }

  // -----------------------------
  // Events (cards, menus, filters)
  // -----------------------------
  function openMenuAt(btn, menu) {
	  // Close all other menus
	  document.querySelectorAll('.media-card .card-menu.open').forEach(m => {
		if (m !== menu) m.classList.remove('open');
	  });

	  // Show to measure
	  menu.classList.add('open');

	  // Fixed position at the button
	  const b = btn.getBoundingClientRect();
	  const vw = window.innerWidth;
	  const vh = window.innerHeight;
	  const padding = 8;

	  // Temp set to get size
	  menu.style.top = '-9999px';
	  menu.style.left = '-9999px';
	  const mRect = menu.getBoundingClientRect();

	  // Default: below, right-aligned
	  let top  = b.bottom + 8;
	  let left = b.right - mRect.width;

	  // Clamp X
	  left = Math.min(Math.max(left, padding), vw - mRect.width - padding);

	  // Flip up if not enough space below
	  const spaceBelow = vh - (b.bottom + 8);
	  if (spaceBelow < Math.min(mRect.height, vh * 0.5)) {
		top = Math.max(b.top - 8 - mRect.height, padding);
		menu.classList.add('drop-up');
	  } else {
		menu.classList.remove('drop-up');
	  }

	  menu.style.top = `${Math.round(top)}px`;
	  menu.style.left = `${Math.round(left)}px`;
	  menu.style.maxHeight = `calc(100vh - ${padding * 2}px)`;
	}

	function bindCardEvents() {
	  // kebab menus
	  $$('.media-card .menu-toggle', grid).forEach(btn => {
		  btn.addEventListener('click', (e) => {
			e.stopPropagation();
			const card = btn.closest('.media-card');

			// close any others first
			$$('.media-card .card-menu.open', grid).forEach(m => {
			  m.classList.remove('open');
			  m.closest('.media-card')?.classList.remove('menu-open'); // <-- remove booster
			});

			const menu = $('.card-menu', card);
			const nowOpen = !menu.classList.contains('open');
			menu.classList.toggle('open', nowOpen);
			card.classList.toggle('menu-open', nowOpen);               // <-- add booster
		  });
		});

	  // close menus on outside click / scroll / resize
	  const closeAllMenus = () => {
		document.querySelectorAll('.media-card .card-menu.open').forEach(m => m.classList.remove('open'));
	  };
	  document.addEventListener('click', closeAllMenus, { passive: true });
	  window.addEventListener('scroll', closeAllMenus, { passive: true });
	  window.addEventListener('resize', closeAllMenus, { passive: true });

	  // actions
	  $$('.media-card .act-attach', grid).forEach(el =>
		el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); attach(el.dataset.id); })
	  );
	  $$('.media-card .act-detach', grid).forEach(el =>
		el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); detach(el.dataset.id); })
	  );
	  $$('.media-card .act-delete', grid).forEach(el =>
		el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); delItem(el.dataset.id); })
	  );

	  // Edit tags — fetch current before opening
	  $$('.media-card .act-tags', grid).forEach(el =>
		el.addEventListener('click', async (e) => {
		  e.preventDefault(); e.stopPropagation();
		  const id = Number(el.dataset.id);
		  if (!id) return;
		  document.querySelectorAll('.media-card .card-menu.open').forEach(m => m.classList.remove('open'));
		  let preload = [];
		  try { preload = await getTags(id); } catch(_) {}
		  openTagsModal(id, preload);
		})
	  );

	  // Change group — preselect current group
	  $$('.media-card .act-groups', grid).forEach(el =>
		el.addEventListener('click', (e) => {
		  e.preventDefault(); e.stopPropagation();
		  const id = Number(el.dataset.id);
		  if (!id) return;
		  document.querySelectorAll('.media-card .card-menu.open').forEach(m => m.classList.remove('open'));
		  const one = items.find(x => Number(x.id) === id);
		  const cur = one ? (one.group_id || (one.groups && one.groups[0] && one.groups[0].id) || null) : null;
		  openGroupModal(id, cur);
		})
	  );
	} 

	// Top bar interactions
	if (tabs.board) tabs.board.addEventListener('click', () => {
	  currentScope = 'board';
	  tabs.board.classList.add('active');
	  if (tabs.vision) tabs.vision.classList.remove('active');
	  fetchList();
	});
	if (tabs.vision) tabs.vision.addEventListener('click', () => {
	  currentScope = 'vision';
	  tabs.vision.classList.add('active');
	  if (tabs.board) tabs.board.classList.remove('active');
	  fetchList();
	});


  if (typeSel) typeSel.addEventListener('change', fetchList);
  if (sortSel) sortSel.addEventListener('change', fetchList);
  if (qInput)  qInput.addEventListener('input', () => {
    clearTimeout(qInput._t); qInput._t = setTimeout(fetchList, 250);
  });
  if (groupSel)   groupSel.addEventListener('change', fetchList);
  if (tagsFilter) tagsFilter.addEventListener('input', () => {
    clearTimeout(tagsFilter._t); tagsFilter._t = setTimeout(fetchList, 300);
  });

  // Add Link → overlay
  if (linkBtn) linkBtn.addEventListener('click', openLinkModal);

  // Optional canvas helpers (no-op placeholders)
  if (addNoteBtn) addNoteBtn.addEventListener('click', () => {
    // hook into your canvas note creation
    alert('Add Note: hook this to your canvas logic');
  });
  if (addConnectorBtn) addConnectorBtn.addEventListener('click', () => {
    alert('Add Connector: hook this to your canvas logic');
  });
	
	// ---- Upload placeholders in grid (per-file progress) ----
	const uploadPlaceholders = new Map(); // key: tempId -> {el, file}

	function createUploadPlaceholder(file) {
	  if (!grid) return null;
	  const tempId = 'up_' + Math.random().toString(36).slice(2);
	  const isImg = /^image\//.test(file.type);
	  const previewURL = isImg ? URL.createObjectURL(file) : '';

	  const el = document.createElement('div');
	  el.className = 'media-card uploading';
	  el.dataset.tempId = tempId;
	  if (previewURL) el._previewURL = previewURL;   // so we can revoke later

	  el.innerHTML = `
		<div class="thumb">
		  ${previewURL
			? `<img src="${previewURL}" alt="">`
			: `<div class="thumb-fallback">Uploading…</div>`}
		  <div class="overlay">
			<div class="progress"><div class="bar" style="width:0%"></div></div>
		  </div>
		</div>
		<div class="meta">
		  <div class="name" title="${file.name.replace(/"/g,'&quot;')}">${file.name}</div>
		  <div class="badge">file</div>
		</div>
	  `;
	  grid.insertBefore(el, grid.firstChild);
	  uploadPlaceholders.set(tempId, { el, file });
	  return tempId;
	}

	function updateUploadPlaceholder(tempId, percent) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  const bar = rec.el.querySelector('.progress .bar');
	  if (bar) bar.style.width = Math.max(0, Math.min(100, percent)) + '%';
	}

	function markUploadPlaceholderDone(tempId) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  rec.el.classList.add('done');
	  updateUploadPlaceholder(tempId, 100);
	  const tf = rec.el.querySelector('.thumb .thumb-fallback');
	  if (tf) tf.textContent = 'Processing…';
	}

	function removeUploadPlaceholder(tempId) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  if (rec.el._previewURL) { try { URL.revokeObjectURL(rec.el._previewURL); } catch(_){} }
	  rec.el.remove();
	  uploadPlaceholders.delete(tempId);
	}

	function queueUploadWithPlaceholder(file) {
	  const tempId = createUploadPlaceholder(file);
	  upQueue.push({ file, tempId });
	}

	function markUploadPlaceholderDone(tempId) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  updateUploadPlaceholder(tempId, 100);
	  const tf = rec.el.querySelector('.thumb-fallback');
	  if (tf) tf.textContent = 'Processing…';
	  rec.el.classList.add('done');
	}
	
	function updateUploadPlaceholder(tempId, percent) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  const bar = rec.el.querySelector('.progress .bar');
	  if (bar) bar.style.width = Math.max(0, Math.min(100, percent)) + '%';
	}

	function removeUploadPlaceholder(tempId) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  rec.el.remove();
	  uploadPlaceholders.delete(tempId);
	}

	function queueUploadWithPlaceholder(file) {
	  const tempId = createUploadPlaceholder(file);
	  upQueue.push({ file, tempId });
	}


  // -----------------------------
  // Upload queue (lightweight)
  // -----------------------------
  const pill = document.getElementById('uploadQueuePill');
  const MAX_PARALLEL = 3;
  const upQueue = [];
  let inFlight = 0;
  let cancelledAll = false;
  let pendingUploads = 0; // how many files are in the current batch

  pill?.querySelector('.upl-cancel')?.addEventListener('click', () => {
    cancelledAll = true;
    upQueue.length = 0;
  });

  function pillUpdate() {
    if (!pill) return;
    const pending = upQueue.length + inFlight;
    pill.hidden = pending === 0;
    pill.querySelector('.upl-text').textContent = pending ? `Uploading… (${pending})` : 'Uploading…';
  }

	async function fetchMediaById(mediaId) {
	  // Try a direct endpoint first (if your API exposes it)
	  try {
		const r = await fetch(`/api/media/${mediaId}`, { credentials:'same-origin' });
		if (r.ok) return await r.json(); // should be a media object
	  } catch(_) {}

	  // Fallback: try to find it via the list endpoint (if it supports filtering by id)
	  try {
		const r2 = await fetch(`/api/visions/${encodeURIComponent(apiBaseSlug())}/media?id=${mediaId}`, { credentials:'same-origin' });
		if (r2.ok) {
		  const j = await r2.json();
		  const arr = j.media || [];
		  const m = arr.find(x => Number(x.id) === Number(mediaId));
		  if (m) return m;
		}
	  } catch(_){}

	  return null; // we’ll let the final refresh replace the placeholder
	}

	function bindCardEl(card) {
	  // kebab
	  const btn = card.querySelector('.menu-toggle');
	  if (btn) {
		btn.addEventListener('click', (e) => {
		  e.stopPropagation();
		  const menu = card.querySelector('.card-menu');
		  document.querySelectorAll('.media-card .card-menu.open').forEach(m => m.classList.remove('open'));
		  menu?.classList.toggle('open');
		});
	  }
		// clicking anywhere else closes & drops z-index back
		document.addEventListener('click', () => {
		  $$('.media-card .card-menu.open', grid).forEach(m => {
			m.classList.remove('open');
			m.closest('.media-card')?.classList.remove('menu-open');
		  });
		});

	  // actions
	  const id = card.dataset.id;
	  card.querySelector('.act-attach')?.addEventListener('click', (e)=>{ e.preventDefault(); e.stopPropagation(); attach(id); });
	  card.querySelector('.act-detach')?.addEventListener('click', (e)=>{ e.preventDefault(); e.stopPropagation(); detach(id); });
	  card.querySelector('.act-delete')?.addEventListener('click', (e)=>{ e.preventDefault(); e.stopPropagation(); delItem(id); });

	  card.querySelector('.act-tags')?.addEventListener('click', async (e)=>{
		e.preventDefault(); e.stopPropagation();
		let preload = [];
		try { preload = await getTags(id); } catch(_) {}
		openTagsModal(Number(id), preload);
	  });

	  card.querySelector('.act-groups')?.addEventListener('click', (e)=>{
		e.preventDefault(); e.stopPropagation();
		const one = items.find(x => Number(x.id) === Number(id));
		const cur = one ? (one.group_id || (one.groups && one.groups[0] && one.groups[0].id) || null) : null;
		openGroupModal(Number(id), cur);
	  });
	}

	function swapPlaceholderWithCard(tempId, mediaObj) {
	  const rec = uploadPlaceholders.get(tempId);
	  if (!rec) return;
	  // build card HTML using your existing template
	  const wrapper = document.createElement('div');
	  wrapper.innerHTML = templateCard(mediaObj);
	  const card = wrapper.firstElementChild;
	  rec.el.replaceWith(card);
	  if (rec.el._previewURL) { try { URL.revokeObjectURL(rec.el._previewURL); } catch(_){} }
	  uploadPlaceholders.delete(tempId);
	  bindCardEl(card);
	}

	function pumpUploads() {
	  while (inFlight < MAX_PARALLEL && upQueue.length && !cancelledAll) {
		const job = upQueue.shift(); // { file, tempId }
		if (!job) break;
		inFlight++;
		doUpload(job).finally(() => {
		  inFlight--;
		  pillUpdate();
		  if (upQueue.length === 0 && inFlight === 0 && pendingUploads === 0) {
			// One final refresh to ensure server-state matches exactly
			fetchList().then(() => {
			  uploadPlaceholders.forEach((_, id) => removeUploadPlaceholder(id));
			  pillUpdate();
			});
		  } else {
			pumpUploads();
		  }
		});
	  }
	}

	async function doUpload(job) {
	  const { file, tempId } = job;

	  // Build formdata once
	  const fd = new FormData();
	  fd.append('file[]', file);

	  // Use XHR to get upload progress
	  await new Promise((resolve) => {
		const xhr = new XMLHttpRequest();
		xhr.open('POST', `/api/visions/${encodeURIComponent(apiBaseSlug())}/media:upload`, true);
		xhr.withCredentials = true;

		xhr.upload.onprogress = (evt) => {
		  if (evt.lengthComputable) {
			const pct = Math.round((evt.loaded / evt.total) * 100);
			updateUploadPlaceholder(tempId, pct);
		  }
		};

		xhr.onreadystatechange = async () => {
		  if (xhr.readyState !== 4) return;
		  try {
			if (xhr.status >= 200 && xhr.status < 300) {
			  const j = JSON.parse(xhr.responseText || '{}');

			  // Attach to board if needed
			  let mediaId = j.media_id || j.id || null;
			  if (currentScope === 'board' && mediaId) {
				const fa = new FormData();
				fa.append('media_id[]', String(mediaId));
				await fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:attach`, {
				  method: 'POST', body: fa, credentials: 'same-origin'
				});
			  }

			  // Keep the preview visible, show "Processing…" while we fetch metadata
			  markUploadPlaceholderDone(tempId);

			  // Try to fetch media metadata and swap this placeholder immediately
			  if (!mediaId && j.media && j.media.id) mediaId = j.media.id;
			  if (mediaId) {
				const mediaObj = await fetchMediaById(mediaId);
				if (mediaObj) {
				  swapPlaceholderWithCard(tempId, mediaObj); // immediate appearance
				}
			  }
			} else {
			  console.error('Upload failed', xhr.status, xhr.responseText);
			  removeUploadPlaceholder(tempId);
			}
		  } catch (e) {
			console.error('Upload error', e);
			removeUploadPlaceholder(tempId);
		  } finally {
			pendingUploads = Math.max(0, pendingUploads - 1);
			resolve();
		  }
		};

		xhr.send(fd);
	  });
	}


  	// ---- Upload wiring: bind ONCE + guard against double-open ----
	if (uploadBtn && uploadInput && !uploadBtn.dataset.bound) {
	  uploadBtn.dataset.bound = '1';
	  uploadInput.dataset.bound = '1';

	  let picking = false;
	  let pickReleaseTimer = null;

	  function releasePickingSoon() {
		// Release after the picker closes (covers both select & cancel)
		if (pickReleaseTimer) clearTimeout(pickReleaseTimer);
		pickReleaseTimer = setTimeout(() => { picking = false; }, 150);
	  }

	  uploadBtn.addEventListener('click', (ev) => {
		ev.preventDefault();
		ev.stopPropagation();
		if (picking) return;          // prevent duplicate dialogs
		picking = true;
		// Defer to next tick so any other bubbling clicks finish first
		setTimeout(() => uploadInput.click(), 0);
	  }, { passive: true });

	  // Some browsers don't fire 'change' if user cancels → use focus to release
	  window.addEventListener('focus', releasePickingSoon);

	  uploadInput.addEventListener('click', (ev) => {
		// prevent any accidental bubbling re-triggers
		ev.stopPropagation();
	  }, { capture: true });

	  uploadInput.addEventListener('change', (e) => {
		const files = Array.from(e.target.files || []);
		releasePickingSoon();         // always release mutex after dialog closes

		if (!files.length) {
		  // user canceled: nothing to do
		  e.target.value = null;      // fully reset
		  return;
		}

		cancelledAll = false;
		files.forEach(f => { pendingUploads++; queueUploadWithPlaceholder(f); });
		pillUpdate();
		pumpUploads();

		e.target.value = null;        // reset reliably (allows same file again)
	  });
	}


  // -----------------------------
  // Library list fetch
  // -----------------------------
  function fetchList() {
	  const qs = new URLSearchParams();
	  qs.set('scope', currentScope || 'board');

	  if ((currentScope || 'board') === 'board' && boardId) {
		qs.set('board_id', String(boardId));
	  }
	  if (typeSel && typeSel.value)      qs.set('type', typeSel.value);
	  if (sortSel && sortSel.value)      qs.set('sort', sortSel.value);
	  if (qInput && qInput.value.trim()) qs.set('q', qInput.value.trim());
	  if (groupSel && groupSel.value)    qs.set('group_id', groupSel.value);
	  if (tagsFilter && tagsFilter.value.trim()) qs.set('tags', tagsFilter.value.trim());

	  const url = `/api/visions/${encodeURIComponent(apiBaseSlug())}/media?` + qs.toString();

	  setStatus('Loading…');
	  return fetch(url, { credentials:'same-origin' })
		.then(r => r.json())
		.then(j => {
		  if (!j.success) throw new Error(j.error || 'Failed');
		  items = j.media || [];
		  render();
		  setStatus('');
		})
		.catch(e => { setStatus(e.message || 'Load failed', true); });
	}

  // -----------------------------
  // Attach / Detach / Delete
  // -----------------------------
  function attach(mediaId) {
    const fd = new FormData();
    fd.append('media_id[]', String(mediaId));
    fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:attach`, { method:'POST', body:fd, credentials:'same-origin' })
      .then(() => fetchList());
  }

  function detach(mediaId) {
    const fd = new FormData();
    fd.append('media_id', String(mediaId));
    fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:detach`, { method:'POST', body:fd, credentials:'same-origin' })
      .then(r => r.json())
      .then(j => {
        if (!j.success && j.error) throw new Error(j.error);
        fetchList();
      })
      .catch(e => alert(e.message || 'Detach failed'));
  }

  function delItem(mediaId) {
    if (!confirm('Delete this file/link from the library? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('media_id', String(mediaId));
    fetch(`/api/visions/${encodeURIComponent(apiBaseSlug())}/media:delete`, { method:'POST', body:fd, credentials:'same-origin' })
      .then(r => r.json())
      .then(j => {
        if (!j.success && j.error) throw new Error(j.error);
        fetchList();
      })
      .catch(e => alert(e.message || 'Delete failed'));
  }

  // -----------------------------
  // Init
  // -----------------------------
  loadGroups(); // prefill group select
  fetchList();
  // Initial tab = Board Files
  setActive('board');
  loadBoardFiles();
})();
