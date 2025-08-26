// public/js/mood-board-library.js

// --- add at the very top ---
//if (window.__moodLibInit) { return; }
//window.__moodLibInit = true;
// ---------------------------

// Robust image fallback without inline quoting headaches
window.DBL_thumbError = function(img) {
  const card = img.closest('.media-card');
  const id = card?.dataset.id;
  const item = (window.__mediaItems || []).find(x => String(x.id) === String(id));
  const tried = Number(img.dataset.fallbackStep || 0);

  // Build fallbacks (thumb already failed)
  const ext = (item?.file_name || '').split('.').pop()?.toLowerCase() || 'jpg';
  const large = item?.large_url || (item?.uuid ? '/storage/thumbs/${item.uuid}_1280.jpg' : '');
  const orig  = (item?.uuid ? '/storage/private/${item.uuid}.${ext}' : '');
  const chain = [large, orig].filter(Boolean);

  if (tried < chain.length) {
    img.dataset.fallbackStep = String(tried + 1);
    img.src = chain[tried];
    return;
  }

  img.onerror = null;
  const box = img.closest('.thumb');
  if (box) box.innerHTML = '<div class="thumb-fallback">No preview</div>';
};

(function () {

  // === Tag/Group caches + loaders (keep before any call to loadGroups) ===
  let TAGS_CACHE = null;
  let GROUPS_CACHE = null;

  function loadTags() {
    if (TAGS_CACHE) return Promise.resolve(TAGS_CACHE);
    return fetch('/api/tags', { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(j => (TAGS_CACHE = (j.tags || [])))
      .catch(() => (TAGS_CACHE = []));
  }

  function loadGroups_OLD() {
    if (GROUPS_CACHE) return Promise.resolve(GROUPS_CACHE);
    // A) FIX: use moods/{boardSlug}/groups
    const base = `/api/moods/${encodeURIComponent(boardSlug)}/groups`;
    return fetch(base, { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(j => (GROUPS_CACHE = (j.groups || [])))
      .catch(() => (GROUPS_CACHE = []));
  }

  // ===== Modal infrastructure (unchanged UI) =====
  const overlay = document.getElementById('ml-overlay');
  const sheet   = overlay ? overlay.querySelector('.ml-sheet') : null;

  function openSheet(title, bodyHTML, actionsHTML) {
    if (!overlay || !sheet) return false;
    sheet.innerHTML = `
      <div class="ml-head">
        <div class="ml-title" id="ml-title">${title}</div>
        <button class="ml-close" aria-label="Close">✕</button>
      </div>
      <div class="ml-body">${bodyHTML}</div>
      <div class="ml-actions">${actionsHTML || ''}</div>
    `;
    overlay.hidden = false;
    sheet.querySelector('.ml-close')?.addEventListener('click', closeSheet);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeSheet(); }, { once:true });
    document.addEventListener('keydown', escCloseOnce, { once:true });
    return true;
  }
 
  function escCloseOnce(e){ if (e.key === 'Escape') closeSheet(); }
  function closeSheet(){ if (overlay) overlay.hidden = true; }

//	let GROUPS_CACHE = null;

	async function loadGroups() {
	  if (GROUPS_CACHE) return GROUPS_CACHE;
	  try {
		// If you already fetch groups elsewhere, keep that. This endpoint is the one you said works.
		const base = `/api/moods/${encodeURIComponent(boardSlug)}/groups`;
		const res  = await fetch(base, { credentials: 'same-origin' });
		if (!res.ok) throw new Error('Groups failed');
		const json = await res.json();
		GROUPS_CACHE = (json.groups || []).map(g => ({
		  id: String(g.id),
		  name: g.name,
		  slug: g.slug || ''
		}));
		// Fill the <select> if present
		if (groupSel) {
		  const cur = groupSel.value;
		  groupSel.innerHTML = `<option value="">All groups</option>`
			+ GROUPS_CACHE.map(g => `<option value="${g.id}">${g.name}</option>`).join('');
		  if (cur && GROUPS_CACHE.some(g => g.id === cur)) groupSel.value = cur;
		}
		return GROUPS_CACHE;
	  } catch {
		GROUPS_CACHE = [];
		return GROUPS_CACHE;
	  }
	}


  // ===== GROUP MODAL (unchanged UI; posts to /api/media/{id}/group) =====
  async function openGroupModal(mediaId, currentGroupId=null) {
    if (!overlay || !sheet) {
      const name = prompt('Add to group: type existing or new group name');
      if (!name) return;
      const fd = new FormData();
      fd.append('group_name', name);
	  fd.append('board_id', String(boardId)); // <— ensure server can resolve owner
      await fetch(`/api/media/${mediaId}/group`, { method:'POST', body:fd, credentials:'same-origin' });
      loadGroups(); fetchList();
	// After successful POST /api/media/:id/group …
	const one = items.find(x => Number(x.id) === Number(mediaId));
	if (one) {
	  one.group_id   = String(savedGroup.id || newGroupId || groupId || '');
	  one.group_name = savedGroup.name || newGroupName || one.group_name || '';
	  render(); // reapply filters
	}
      return;
    }
	  // after a successful POST:
	try {
	  // update in-memory item so the next open uses the new tags
	  const it = (window.__mediaItems || []).find(x => Number(x.id) === Number(mediaId));
	  if (it) it.tags = final.join(','); // store as CSV (matches DB), getTags() will normalize later
	} catch (_) {}

    const groups = await loadGroups();
    const options = ['<option value="">— No group —</option>']
      .concat(groups.map(g => `<option value="${g.id}" ${String(g.id)===String(currentGroupId)?'selected':''}>${g.name}</option>`))
      .join('');

    openSheet(
      'Add to group',
      `
        <label>Choose existing group</label>
        <select class="ml-select" id="ml-group-select">${options}</select>
        <div class="ml-hint">or create a new group below</div>
        <input class="ml-input" id="ml-group-new" placeholder="New group name">
      `,
      `
        <button class="ml-btn" id="ml-cancel">Cancel</button>
        <button class="ml-btn primary" id="ml-save">Save</button>
      `
    );

    sheet.querySelector('#ml-cancel').addEventListener('click', closeSheet);
    sheet.querySelector('#ml-save').addEventListener('click', async () => {
      const sel = sheet.querySelector('#ml-group-select').value;
      const newName = (sheet.querySelector('#ml-group-new').value || '').trim();

      const fd = new FormData();
      if (newName) {
        fd.append('group_name', newName);
      } else {
        fd.append('group_id', sel || '');
      }
	  fd.append('board_id', String(boardId)); // <— ensure server can resolve owner
      await fetch(`/api/media/${mediaId}/group`, { method:'POST', body:fd, credentials:'same-origin' });
      closeSheet();
      loadGroups();  // refresh cache
      fetchList();
    });
  }

	async function getTags(mediaId) {
	  // Expect backend to return either {tags:["a","b"]} OR {tags:"a,b"}
	  const res = await fetch(`/api/media/${mediaId}/tags`, { credentials: 'same-origin' });
	  if (!res.ok) throw new Error('Failed to load tags');
	  const j = await res.json();
	  return toTagsArray(j.tags);
	}


  // ===== TAGS MODAL (kept; posts to /api/media/{id}/tags) =====
  async function openTagsModal(mediaId, currentTags=[]) {
    if (!overlay || !sheet) {
      const input = prompt('Tags (comma separated):', (currentTags||[]).map(t=>t.name||t).join(', '));
      if (input == null) return;
      const fd = new FormData();
      fd.append('tags', input);
      await fetch(`/api/media/${mediaId}/tags`, { method:'POST', body:fd, credentials:'same-origin' });
      fetchList();
      return;
    }

    const tags = await loadTags();
    const list = normalizeTagsShape(currentTags);
	const existing = new Set(list.map(t => t.name.toLowerCase()));
    const chipHtml = (name) =>
      `<span class="ml-chip" data-name="${name}">
         ${name}
         <button type="button" aria-label="remove">×</button>
       </span>`;
    const availableList = (tags || []).filter(t => !existing.has((t.name||t).toLowerCase()))
        .map(t => `<option value="${t.name||t}">`).join('');
	const normalized = toTagsArray(currentTags);

    openSheet(
      'Edit tags',
      `
        <div>
          <div class="ml-hint">Current tags</div>
          <div class="ml-chips" id="ml-tags">${
			  normalized.length
				? normalized.map(t => chipHtml(t)).join('')
				: '<span class="ml-hint">None yet</span>'
			}</div>
        </div>
        <div>
          <div class="ml-hint">Add tag</div>
          <input id="ml-tag-input" class="ml-input" list="ml-tag-datalist" placeholder="Type and press Enter">
          <datalist id="ml-tag-datalist">${availableList}</datalist>
        </div>
      `,
      `
        <button class="ml-btn" id="ml-cancel">Cancel</button>
        <button class="ml-btn primary" id="ml-save">Save</button>
      `
    );
	//const existing = new Set(normalized.map(t => t.toLowerCase()));

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
        if (!has) chipBox.insertAdjacentHTML('beforeend', chipHtml(val));
        input.value = '';
      }
    });

    sheet.querySelector('#ml-cancel').addEventListener('click', closeSheet);
    sheet.querySelector('#ml-save').addEventListener('click', async () => {
      const final = Array.from(chipBox.querySelectorAll('.ml-chip')).map(c => c.dataset.name);
      const fd = new FormData();
      fd.append('tags', final.join(','));
      // B) FIX: use /api/media/{id}/tags
      await fetch(`/api/media/${mediaId}/tags`, { method:'POST', body:fd, credentials:'same-origin' });
      closeSheet();
      loadTags();
      fetchList();
    });
  }

  const $ = (sel, el) => (el || document).querySelector(sel);
  const $$ = (sel, el) => Array.from((el || document).querySelectorAll(sel));
  
	 // Normalize "tags" from any backend shape into [{name:'tag'}...]
	function normalizeTagsShape(val) {
	  // Array already? allow ["a","b"] or [{name:"a"}]
	  if (Array.isArray(val)) {
		return val
		  .map(t => (typeof t === 'string' ? { name: t.trim() } : t))
		  .filter(t => t && typeof t.name === 'string' && t.name.trim() !== '');
	  }
	  // Comma-separated string (DB column)
	  if (typeof val === 'string') {
		return val
		  .split(',')
		  .map(s => ({ name: s.trim() }))
		  .filter(t => t.name);
	  }
	  // Wrapped object shapes like { tags: ... } or { success:true, data: ... }
	  if (val && typeof val === 'object') {
		if ('tags' in val) return normalizeTagsShape(val.tags);
		if ('data' in val) return normalizeTagsShape(val.data);
	  }
	  return [];
	}

	// Turn "tag1, tag2" or ["tag1","tag2"] or null into ["tag1","tag2"]
	function toTagsArray(val) {
	  if (!val) return [];
	  if (Array.isArray(val)) return val.map(String).map(s => s.trim()).filter(Boolean);
	  // string from DB column
	  return String(val)
		.split(',')
		.map(s => s.trim())
		.filter(Boolean);
	}


  // Expect these data-* attributes somewhere on the page:
  // <div id="mood-lib-root" data-vision-slug="xxxxxx" data-board-slug="yyyyyy" data-board-id="123"></div>
  const root = document.getElementById('mood-lib-root');
  if (!root) return;

  const visionSlug = root.dataset.visionSlug || ''; // may be empty for standalone
  const boardSlug  = root.dataset.boardSlug;        // required
  const boardId    = parseInt(root.dataset.boardId, 10);

  const tabs = {
    board:  $('[data-scope="board"]'),
    vision: $('[data-scope="vision"]')
  };
  const uploadBtn = $('#uploadBtn');
  const linkBtn   = $('#linkBtn');
  const uploadInput = $('#mediaUploadInput');
  const linkWrap    = $('#linkWrap');
  const linkUrl     = $('#linkUrl');
  const linkSubmit  = $('#linkSubmit');

  const statusEl = $('#libraryStatus');
  const typeSel  = $('#mediaTypeFilter');
  const sortSel  = $('#mediaSort');
  const qInput   = $('#mediaSearch');
  const grid = document.getElementById('libraryGrid') || document.getElementById('mediaGrid');
  if (!grid) { console.warn('[MediaLibrary] grid container not found'); }

  // NEW: group + tags filters
  const groupSel = document.getElementById('groupFilterSelect')
               || document.getElementById('mediaGroupFilter')
               || null;
  const tagsFilter = document.getElementById('tagFilterInput')
                 || document.getElementById('mediaTagFilter')
                 || null;
  const groupSearch = document.getElementById('groupSearch')
                  || document.getElementById('mediaGroupSearch')
                  || null;
  //const tagsInput   = document.getElementById('tagFilter');     // <input> "Filter by tags (comma)..."

  //const tagFilterInput   = document.getElementById('tagFilterInput');
  //const groupFilterSelect = document.getElementById('groupFilterSelect');
  //let tagsMode = 'or';

  //if (tagFilterInput) {
    //tagFilterInput.addEventListener('input', () => {
      //clearTimeout(tagFilterInput._t);
      //tagFilterInput._t = setTimeout(fetchList, 300);
    //});
  //}

  if (groupFilterSelect) {
    groupFilterSelect.addEventListener('change', fetchList);
  }

  let currentScope = 'board';
  let items = [];

  function apiBaseSlug() {
    return visionSlug || boardSlug;
  }

  function setStatus(msg, isError=false) {
    statusEl.textContent = msg || '';
    statusEl.classList.toggle('error', !!isError);
  }

  function deriveThumbUrl(m)  { return m.uuid ? `/storage/thumbs/${m.uuid}_thumb.jpg` : ''; }
  function deriveLargeUrl(m)  { return m.uuid ? `/storage/thumbs/${m.uuid}_1280.jpg` : ''; }
  function deriveOrigUrl(m) {
    if (!m.uuid || !m.file_name) return '';
    const ext = m.file_name.split('.').pop().toLowerCase();
    return `/storage/private/${m.uuid}.${ext}`;
  }

  function bestThumb(m) {
    if (m.provider === 'youtube') {
      const pid = m.provider_id || ytIdFromUrl( m.external_url);
      if (pid) return `https://img.youtube.com/vi/${pid}/hqdefault.jpg`;
    }
    return m.thumb_url || deriveThumbUrl(m) || '';
  }

  function bestLarge(m)  { return m.large_url || deriveLargeUrl(m) || ''; }
  function bestOrig(m)   { return deriveOrigUrl(m) || ''; }

  function deriveThumb(m){ return m.thumb_url || (m.uuid ? `/storage/thumbs/${m.uuid}_thumb.jpg` : ''); }
  function deriveLarge(m){ return m.large_url || (m.uuid ? `/storage/thumbs/${m.uuid}_1280.jpg` : ''); }
  function deriveOrig(m){
    if (!m.uuid || !m.file_name) return '';
    const ext = m.file_name.split('.').pop().toLowerCase();
    return `/storage/private/${m.uuid}.${ext}`;
  }

  function ytIdFromUrl(url) {
    if (!url) return '';
    try {
      const u = new URL(url);
      if (u.hostname.includes('youtu.be')) return u.pathname.split('/')[1];
      if (u.hostname.includes('youtube.com')) return u.searchParams.get('v') || '';
    } catch (_) {}
    return '';
  }

  function youtubeIdFromUrl(url){
    if(!url) return null;
    try {
      const u = new URL(url);
      const host = u.hostname.replace(/^www\./,'');
      if (host === 'youtu.be') {
        const id = u.pathname.split('/').filter(Boolean)[0];
        return id || null;
      }
      if (host.endsWith('youtube.com')) {
        if (u.searchParams.get('v')) return u.searchParams.get('v');
        const m1 = u.pathname.match(/\/embed\/([A-Za-z0-9_-]{6,})/);
        if (m1) return m1[1];
        const m2 = u.pathname.match(/\/shorts\/([A-Za-z0-9_-]{6,})/);
        if (m2) return m2[1];
      }
    } catch(_) {}
    const m = String(url).match(/(?:v=|youtu\.be\/|\/embed\/|\/shorts\/)([A-Za-z0-9_-]{6,})/);
    return m ? m[1] : null;
  }

  function renderTagChips(m) {
    const tags = Array.isArray(m.tags) ? m.tags : [];
    if (!tags.length) return '';
    const chips = tags.slice(0, 3).map(t => {
      const name = (t && (t.name || t.slug || '')).toString();
      return `<span class="chip">${name}</span>`;
    }).join('');
    const more = tags.length > 3 ? `<span class="chip more">+${tags.length - 3}</span>` : '';
    return `<div class="tags-row">${chips}${more}</div>`;
  }
  function renderGroupLine(m) {
    const groups = Array.isArray(m.groups) ? m.groups : [];
    if (!groups.length) return '';
    const names = groups.map(g => (g && (g.name || g.slug || '')).toString()).filter(Boolean).join(', ');
    if (!names) return '';
    return `<div class="groups-row">${names}</div>`;
  }

  function templateCard(m){
    const linkUrl = m.external_url || m.url || m.source_url || m.link_url || '';
    const ytId = m.provider_id || youtubeIdFromUrl(linkUrl);
    const isYouTube = !!ytId;

    let label = 'file';
    if (isYouTube || (m.mime_type && m.mime_type.startsWith('video/'))) label = 'video';
    else if (m.mime_type === 'image/gif') label = 'gif';
    else if (m.mime_type === 'application/pdf') label = 'pdf';
    else if (m.mime_type && m.mime_type.startsWith('image/')) label = 'image';

    const name = m.file_name || m.title || '';

    const ytThumb = isYouTube
      ? `https://i.ytimg.com/vi/${ytId}/hqdefault.jpg`
      : '';

    const thumb =
      m.thumb_url ||
      (ytThumb) ||
      (m.uuid ? `/storage/thumbs/${m.uuid}_thumb.jpg` : '') ||
      m.large_url ||
      (m.uuid && m.file_name
        ? `/storage/private/${m.uuid}.${(m.file_name.split('.').pop()||'jpg').toLowerCase()}`
        : '');

    const srcset = [
      ytThumb && `https://i.ytimg.com/vi/${ytId}/maxresdefault.jpg 1280w`,
      m.large_url && `${m.large_url} 1280w`
    ].filter(Boolean).join(', ');

    const sizes  = '(max-width: 700px) 48vw, (max-width: 1100px) 33vw, 260px';

    const chipsHtml  = renderTagChips(m);
    const groupsHtml = renderGroupLine(m);

    return `
      <div class="media-card" data-id="${m.id}" data-type="${label}" draggable="true">
        <button class="menu-toggle" aria-label="menu">⋮</button>
        <div class="thumb">
          <img src="${thumb}" ${srcset ? `srcset="${srcset}" sizes="${sizes}"` : ''}
               alt="${name.replace(/"/g,'&quot;')}" loading="lazy" decoding="async"
               onerror="this.onerror=null; this.src='${m.large_url || ''}'; if(!this.src) this.closest('.thumb').innerHTML='<div class=\\'thumb-fallback\\'>No preview</div>';"/>
          ${label === 'video' ? `<div class="play-badge">▶</div>` : ``}
        </div>
        <div class="meta">
          <div class="name" title="${name.replace(/"/g,'&quot;')}">${name}</div>
          <div class="badge">${label}</div>
        </div>
        ${chipsHtml}
        ${groupsHtml}
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
    window.__mediaItems = items;

    if (!items.length) {
      grid.innerHTML = `<div class="empty" style="opacity:.7;padding:12px">No files yet.</div>`;
      return;
    }
    grid.innerHTML = items.map(templateCard).join('');
    bindCardEvents?.();
  }
	
	function applyGroupFilters(list) {
	  let out = list;

	  // If the API supports group_id, we also set it in fetchList() (below). This
	  // client‑side filter is a safe fallback for older responses.
	  const gid = (groupSel && groupSel.value) ? String(groupSel.value) : '';
	  const gq  = (groupFind && groupFind.value.trim().toLowerCase()) || '';

	  if (gid) {
		out = out.filter(m => String(m.group_id || '') === gid);
	  }
	  if (gq) {
		out = out.filter(m => (m.group_name || '').toLowerCase().includes(gq));
	  }
	  return out;
	}


  // prime group filter options once
  loadGroups().then(gs => {
    const sel = groupFilterSelect;
    if (!sel || !gs) return;
    gs.forEach(g => {
      const opt = document.createElement('option');
      opt.value = g.id;
      opt.textContent = g.name;
      sel.appendChild(opt);
    });
  });

  function addListFilters(qs) {
    if (tagFilterInput && tagFilterInput.value.trim()) {
      qs.set('tags', tagFilterInput.value.trim());
      qs.set('tags_mode', tagsMode);
    }
    if (groupFilterSelect && groupFilterSelect.value) {
      qs.set('group_id', groupFilterSelect.value);
    }
  }

  function fetchList() {
	  const qs = new URLSearchParams();

	  // Scope
	  qs.set('scope', currentScope || 'board');

	  // Always include board_id for board scope
	  if ((currentScope || 'board') === 'board' && boardId) {
		qs.set('board_id', String(boardId));
	  }

	  // Existing filters
	  if (typeSel && typeSel.value)        qs.set('type', typeSel.value);
	  if (sortSel && sortSel.value)        qs.set('sort', sortSel.value);
	  if (qInput && qInput.value.trim())   qs.set('q', qInput.value.trim());

	  // NEW: group + tags filters
	  if (groupSel && groupSel.value && groupSel.value !== 'all') {
		qs.set('group_id', groupSel.value);
	  }
	  if (groupSearch && groupSearch.value.trim()) {
		// Optional: name search for group (backend may or may not support)
		qs.set('group_query', groupSearch.value.trim());
	  }
	  if (tagsFilter && tagsFilter.value.trim()) {
		// Comma/space list; backend can split
		qs.set('tags', tagsFilter.value.trim());
	  }

	  const url = `/api/visions/${encodeURIComponent(apiBaseSlug())}/media?${qs.toString()}`;
	  console.debug('[MediaLibrary] fetchList →', url); // <-- so we can verify in the console

	  setStatus('Loading…');
	  fetch(url, { credentials: 'same-origin' })
		.then(r => r.json())
		.then(j => {
		  if (!j.success) throw new Error(j.error || 'Failed');
		  items = j.media || [];
		  render();
		  setStatus('');
		})
		.catch(e => setStatus(e.message || 'Load failed', true));
	}




  function attach(mediaId) {
    const fd = new FormData();
    fd.append('media_id[]', String(mediaId));
    fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:attach`, { method:'POST', body:fd, credentials:'same-origin' })
      .then(r => r.json()).then(() => fetchList());
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

  function del(mediaId) {
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

  // Keep your original prompt-based fallbacks wired to the new modals
  function editTags(mediaId, current=[]) { return openTagsModal(mediaId, current); }
  function editGroups(mediaId, current=[]) {
    // use the first current group id if available
    return openGroupModal(mediaId, (current && current[0] && current[0].id) || null);
  }

  function bindCardEvents() {
    $$('.media-card .menu-toggle', grid).forEach(btn => {
	  btn.addEventListener('click', (e) => {
		e.stopPropagation();
		const card = btn.closest('.media-card');
		$$('.media-card .card-menu.open', grid).forEach(m => m.classList.remove('open'));
		$('.card-menu', card).classList.toggle('open');
	  });
	});
    document.addEventListener('click', () => {
	  $$('.media-card .card-menu.open', grid).forEach(m => m.classList.remove('open'));
	});

	$$('.media-card .act-attach', grid).forEach(el =>
	  el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); attach(el.dataset.id); })
	);
	$$('.media-card .act-detach', grid).forEach(el =>
	  el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); detach(el.dataset.id); })
	);
    $$('.media-card .act-delete', grid).forEach(el =>
	  el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); del(el.dataset.id); })
	);
	  
    $$('.media-card .act-tags', grid).forEach(el =>
	  el.addEventListener('click', async (e) => {
		e.preventDefault(); e.stopPropagation();
		const id = Number(el.dataset.id);
		if (!id) return;
		// close any open menu before opening the modal
		$$('.media-card .card-menu.open', grid).forEach(m => m.classList.remove('open'));
		await editTags(id); // your existing function that calls openTagsModal(...)
	  })
	);
	  
    $$('.media-card .act-groups', grid).forEach(el =>
	  el.addEventListener('click', (e) => {
		e.preventDefault(); e.stopPropagation();
		const id = Number(el.dataset.id);
		if (!id) return;
		$$('.media-card .card-menu.open', grid).forEach(m => m.classList.remove('open'));
		openGroupModal?.(id); // if you already have it; otherwise leave as-is
	  })
	);

    $$('.media-card', grid).forEach(card => {
      card.addEventListener('dragstart', (ev) => {
        ev.dataTransfer.setData('text/plain', card.dataset.id);
        ev.dataTransfer.effectAllowed = 'copy';
      });
    });
  }

  const canvas = document.getElementById('canvasDropZone');
  if (canvas) {
    canvas.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect='copy'; });
    canvas.addEventListener('drop', (e) => {
      e.preventDefault();
      const mediaId = parseInt(e.dataTransfer.getData('text/plain'), 10);
      if (!mediaId) return;

      const rect = canvas.getBoundingClientRect();
      const x = Math.max(0, Math.round(e.clientX - rect.left));
      const y = Math.max(0, Math.round(e.clientY - rect.top));

      const present = items.find(m => Number(m.id) === mediaId && (currentScope==='board' || m.attached_to_board == 1));
      const doPlace = () => {
        const body = new FormData();
        body.append('media_id', String(mediaId));
        body.append('x', String(x));
        body.append('y', String(y));
        fetch(`/api/moods/${encodeURIComponent(boardSlug)}/items`, { method:'POST', body, credentials:'same-origin' })
          .then(r => r.json())
          .then(() => {});
      };

      if (present) return doPlace();

      const fd = new FormData();
      fd.append('media_id[]', String(mediaId));
      fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:attach`, { method:'POST', body:fd, credentials:'same-origin' })
        .then(() => doPlace());
    });
  }

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

  // Group dropdown
	if (groupSel) {
	  groupSel.addEventListener('change', fetchList);
	}

	// Group text search (debounced)
	if (groupSearch) {
	  groupSearch.addEventListener('input', () => {
		clearTimeout(groupSearch._t);
		groupSearch._t = setTimeout(fetchList, 250);
	  });
	}
				
	// Debounce helper
	const debounce = (fn, ms=250) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };

	// Re-fetch when filters change
	if (groupSel)    groupSel.addEventListener('change', fetchList);
	if (groupSearch) groupSearch.addEventListener('input', debounce(fetchList, 300));
	if (tagsFilter)  tagsFilter.addEventListener('input',  debounce(fetchList, 300));

  // ===== Upload with per-file progress (kept) =====
  const MAX_PARALLEL = 3;
  const upQueue = [];
  let inFlight = 0;
  let cancelledAll = false;

  const pill = document.getElementById('uploadQueuePill');
  pill?.querySelector('.upl-cancel')?.addEventListener('click', () => {
    cancelledAll = true;
    upQueue.length = 0;
  });

  function enqueueUploads(files) {
    files.forEach(f => upQueue.push(f));
    pillUpdate();
    pumpUploads();
  }

  function pumpUploads() {
    while (inFlight < MAX_PARALLEL && upQueue.length && !cancelledAll) {
      const file = upQueue.shift();
      uploadOneFile(file);
    }
    pillUpdate();
  }

  function makeTempCard(file) {
    const el = document.createElement('div');
    el.className = 'media-card uploading';
    const imgPreview = file.type && file.type.startsWith('image/')
      ? `<img src="${URL.createObjectURL(file)}" alt="">`
      : `<div class="thumb-fallback">${(file.name.split('.').pop() || 'FILE').toUpperCase()}</div>`;
    el.innerHTML = `
      <div class="thumb">${imgPreview}</div>
      <div class="meta"><div class="name">${file.name}</div><div class="badge">upload</div></div>
      <div class="upl-overlay">
        <div class="upl-msg">Preparing…</div>
        <div class="upl-bar"><i style="width:0%"></i></div>
      </div>`;
    grid?.prepend(el);
    return el;
  }

  function replaceTempWithReal(tmpEl, mediaObj) {
    const wrap = document.createElement('div');
	console.log("ffff");
    //wrap.innerHTML = templateCard(mediaObj).trim();
	wrap.className = 'ml-modal-backdrop';   // keep your existing class name
	wrap.innerHTML = modalHtml;             // your existing markup build

	// Always append at the very end of <body> so it layers above the grid
	document.body.appendChild(wrap);

	// (optional but robust) ensure highest stacking context
	wrap.style.position = 'fixed';
	wrap.style.inset = '0';        // top:0;right:0;bottom:0;left:0
	wrap.style.zIndex = '9999';    // higher than cards/menus

    const real = wrap.firstElementChild;
    tmpEl.replaceWith(real);
    bindCardEvents?.();
  }

  function uploadOneFile(file) {
    inFlight++; pillUpdate();

    const tmpEl = makeTempCard(file);
    const bar = tmpEl.querySelector('.upl-bar i');
    const msg = tmpEl.querySelector('.upl-msg');

    const fd = new FormData();
    fd.append('file[]', file);
    if (boardId) fd.append('board_id', String(boardId));

    const url = `/api/visions/${encodeURIComponent(apiBaseSlug())}/media:upload`;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.withCredentials = true;

    xhr.upload.onprogress = (e) => {
      if (!e.lengthComputable) { msg.textContent = 'Uploading…'; return; }
      const pct = Math.max(1, Math.round((e.loaded / e.total) * 100));
      msg.textContent = `Uploading ${pct}%`;
      if (bar) bar.style.width = pct + '%';
    };

    xhr.onloadstart = () => { msg.textContent = 'Uploading…'; };
    xhr.onreadystatechange = () => { if (xhr.readyState === 3) msg.textContent = 'Processing…'; };

    xhr.onerror = fail;
    xhr.onabort = fail;

    xhr.onload = () => {
      try {
        if (xhr.status >= 200 && xhr.status < 300) {
          const res = JSON.parse(xhr.responseText || '{}');
          let mediaObj = null;
          if (res && res.success && Array.isArray(res.media) && res.media.length) {
            mediaObj = res.media[0];
          } else if (res && res.id && (res.thumb_url || res.uuid)) {
            mediaObj = res;
          }
          if (mediaObj) {
            replaceTempWithReal(tmpEl, mediaObj);
            try { items.unshift(mediaObj); } catch(_) {}
          } else {
            tmpEl.remove();
            fetchList();
          }
        } else {
          fail();
        }
      } catch (_) { fail(); }
      finally { done(); }
    };

    function fail() {
      tmpEl.classList.add('upl-error');
      const ov = tmpEl.querySelector('.upl-overlay');
      if (ov) ov.innerHTML = `
        <div class="upl-msg">Upload failed</div>
        <div><button type="button" class="retry"
          style="background:#fff;color:#111;border:0;border-radius:6px;padding:4px 8px;cursor:pointer">Retry</button></div>`;
      tmpEl.querySelector('.retry')?.addEventListener('click', () => {
        tmpEl.remove();
        upQueue.unshift(file);
        pumpUploads();
      });
    }

    function done() {
      inFlight--; pillUpdate();
      if (!upQueue.length && inFlight === 0) {
        if (pill) pill.hidden = true;
      } else {
        pumpUploads();
      }
    }

    xhr.send(fd);
  }

  function pillUpdate() {
    if (!pill) return;
    const total = upQueue.length + inFlight;
    if (total <= 0) { pill.hidden = true; return; }
    pill.hidden = false;
    pill.querySelector('.upl-text').textContent = `Uploading ${inFlight}/${total}…`;
  }

  if (uploadBtn && uploadInput) {
    uploadBtn.addEventListener('click', () => uploadInput.click());
    uploadInput.addEventListener('change', () => {
      if (!uploadInput.files || !uploadInput.files.length) return;
      cancelledAll = false;
      enqueueUploads(Array.from(uploadInput.files));
      uploadInput.value = '';
    });
  }

  if (linkBtn && linkWrap && linkUrl && linkSubmit) {
    linkBtn.addEventListener('click', () => {
      linkWrap.style.display = linkWrap.style.display === 'none' ? '' : 'none';
      if (linkWrap.style.display !== 'none') linkUrl.focus();
    });
    linkSubmit.addEventListener('click', () => {
      const url = linkUrl.value.trim();
      if (!url) return;
      const fd = new FormData();
      fd.append('url', url);
      fd.append('board_id', String(boardId));
      fetch(`/api/visions/${encodeURIComponent(apiBaseSlug())}/media:link`, { method:'POST', body:fd, credentials:'same-origin' })
        .then(r => r.json())
        .then(j => {
          if (!j.success && j.error) throw new Error(j.error);
          linkUrl.value = '';
          linkWrap.style.display = 'none';
          fetchList();
        })
        .catch(e => alert(e.message || 'Add link failed'));
    });
  }
											  
	// === Add Link modal (overlay) ===
	// Call this from the existing linkBtn click. It uses your existing apiBaseSlug(), boardId and fetchList().

	function openLinkModal() {
	  const modal = document.createElement('div');
	  modal.className = 'ml-modal';
	  modal.innerHTML = `
		<div class="ml-dialog" role="dialog" aria-modal="true" aria-label="Add link">
		  <div class="ml-head">
			<div class="ml-title">Add link</div>
			<button class="ml-close" aria-label="Close">✕</button>
		  </div>
		  <div class="ml-body">
			<label class="ml-label" for="ml-link-url">Paste YouTube URL</label>
			<input id="ml-link-url" type="url" placeholder="https://www.youtube.com/watch?v=…" class="ml-input" />
			<div class="ml-hint">Only YouTube links are supported in v1.</div>
			<div class="ml-error" style="display:none"></div>
		  </div>
		  <div class="ml-foot">
			<button class="ml-btn ghost ml-cancel">Cancel</button>
			<button class="ml-btn primary ml-save">Add</button>
		  </div>
		</div>
	  `;
	  document.body.appendChild(modal);

	  const close = () => modal.remove();
	  modal.querySelector('.ml-close').onclick = close;
	  modal.querySelector('.ml-cancel').onclick = close;

	  const urlInput = modal.querySelector('#ml-link-url');
	  const errBox   = modal.querySelector('.ml-error');
	  urlInput.focus();

	  async function doSubmit() {
		const url = urlInput.value.trim();
		if (!url) { errBox.textContent = 'Please paste a YouTube URL.'; errBox.style.display='block'; return; }

		const fd = new FormData();
		fd.append('url', url);
		if (typeof boardId !== 'undefined' && boardId) fd.append('board_id', String(boardId));

		try {
		  const res = await fetch(`/api/visions/${encodeURIComponent(apiBaseSlug())}/media:link`, {
			method: 'POST',
			body: fd,
			credentials: 'same-origin'
		  });
		  const j = await res.json();
		  if (!res.ok || j.error) throw new Error(j.error || 'Failed adding link');
		  close();
		  fetchList?.();
		} catch (e) {
		  errBox.textContent = e.message || 'Failed adding link';
		  errBox.style.display = 'block';
		}
	  }

	  modal.querySelector('.ml-save').onclick = doSubmit;
	  urlInput.addEventListener('keydown', e => {
		if (e.key === 'Enter') { e.preventDefault(); doSubmit(); }
	  });

	  // close on background click
	  modal.addEventListener('click', (e) => {
		if (e.target === modal) close();
	  });
	}

	// hook it up – replace your old linkWrap toggle
	//const linkBtn = document.getElementById('linkBtn');
	if (linkBtn) linkBtn.addEventListener('click', openLinkModal);

 function trySyncGroupSelectFromText() {
	  if (!groupSel || !groupSearch || !Array.isArray(window.GROUPS_CACHE)) return;
	  const t = groupSearch.value.trim().toLowerCase();
	  if (!t) return;
	  const hit = window.GROUPS_CACHE.find(g => (g.name || '').toLowerCase() === t);
	  if (hit) groupSel.value = String(hit.id);
	}

	if (groupSearch) {
	  groupSearch.addEventListener('blur', () => {
		trySyncGroupSelectFromText();
	  });
	}

  document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('libraryGrid') || document.getElementById('mediaGrid');
    if (!grid) { console.warn('[MediaLibrary] grid container not found'); }
    if (grid && !grid.classList.contains('masonry-cols')) {
      grid.classList.add('masonry-cols');
    }
  });

  // Initial load
  fetchList();

  loadGroups(); // fire & forget; select will be filled when ready

})();
