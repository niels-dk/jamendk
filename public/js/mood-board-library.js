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
  const $ = (sel, el) => (el || document).querySelector(sel);
  const $$ = (sel, el) => Array.from((el || document).querySelectorAll(sel));

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

//  const grid     = $('#mediaGrid');
  const statusEl = $('#libraryStatus');
  const typeSel  = $('#mediaTypeFilter');
  const sortSel  = $('#mediaSort');
  const qInput   = $('#mediaSearch');
  const grid = document.getElementById('libraryGrid') || document.getElementById('mediaGrid');
  if (!grid) { console.warn('[MediaLibrary] grid container not found'); }


  let currentScope = 'board'; // 'board' | 'vision'
  let items = []; // current fetched items

  function apiBaseSlug() {
    // For listing/uploading under a Vision context, we need a slug.
    // If visionSlug is not available (standalone), the backend accepts the board slug in place.
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
  // youtube
	  if (m.provider === 'youtube') {
		const pid = m.provider_id || ytIdFromUrl( 	m.external_url);
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

	// Handles watch?v=, youtu.be/, /embed/, /shorts/, with extra params
	function youtubeIdFromUrl(url){
	  if (!url) return null;
	  try {
		const u = new URL(url);
		const host = u.hostname.replace(/^www\./,'');
		// 1) youtu.be/<id>
		if (host === 'youtu.be') {
		  const id = u.pathname.split('/').filter(Boolean)[0];
		  return id || null;
		}
		// 2) youtube.com/watch?v=<id>
		if (host.endsWith('youtube.com')) {
		  if (u.searchParams.get('v')) return u.searchParams.get('v');
		  // 3) /embed/<id>
		  const m1 = u.pathname.match(/\/embed\/([A-Za-z0-9_-]{6,})/);
		  if (m1) return m1[1];
		  // 4) /shorts/<id>
		  const m2 = u.pathname.match(/\/shorts\/([A-Za-z0-9_-]{6,})/);
		  if (m2) return m2[1];
		}
	  } catch(_) {}
	  // 5) last‑resort regex (if url wasn’t parseable by URL())
	  const m = String(url).match(/(?:v=|youtu\.be\/|\/embed\/|\/shorts\/)([A-Za-z0-9_-]{6,})/);
	  return m ? m[1] : null;
	}
	

	function templateCard(m){
	  // discover possible link fields the API might use
	  const linkUrl = m.external_url || m.url || m.source_url || m.link_url || '';

	  // derive YT id even if provider/provider_id are missing
	  const ytId = m.provider_id || youtubeIdFromUrl(linkUrl);
	  const isYouTube = !!ytId;

	  // derive label
	  let label = 'file';
	  if (isYouTube || (m.mime_type && m.mime_type.startsWith('video/'))) label = 'video';
	  else if (m.mime_type === 'image/gif') label = 'gif';
	  else if (m.mime_type === 'application/pdf') label = 'pdf';
	  else if (m.mime_type && m.mime_type.startsWith('image/')) label = 'image';

	  const name = m.file_name || m.title || '';

	  // pick best thumbnail (YT first if we can)
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

	  return `
		<div class="media-card" data-id="${m.id}" data-type="${label}" draggable="true">
		  <button class="menu-toggle" aria-label="menu">⋮</button>
		  <div class="thumb">
			<img src="${thumb}" ${srcset ? `srcset="${srcset}" sizes="${sizes}"` : ''}
				 alt="${name.replace(/"/g,'&quot;')}" loading="lazy" decoding="async"
				 onerror="this.onerror=null; this.src='${m.large_url || ''}'; if(!this.src) this.closest('.thumb').innerHTML='<div class=\\'thumb-fallback\\'>No preview</div>';">
			${label === 'video' ? `<div class="play-badge">▶</div>` : ``}
		  </div>
		  <div class="meta">
			<div class="name" title="${name.replace(/"/g,'&quot;')}">${name}</div>
			<div class="badge">${label}</div>
		  </div>
		  <div class="card-menu"><ul>
			<!-- your attach/detach/delete items rendered as before -->
		  </ul></div>
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

  function fetchList() {
    const qs = new URLSearchParams();
    qs.set('scope', currentScope);
    if (boardId) qs.set('board_id', String(boardId));
    if (typeSel.value) qs.set('type', typeSel.value);
    if (sortSel.value) qs.set('sort', sortSel.value);
    if (qInput.value.trim()) qs.set('q', qInput.value.trim());

    setStatus('Loading…');
    fetch(`/api/visions/${encodeURIComponent(apiBaseSlug())}/media?` + qs.toString(), { credentials: 'same-origin' })
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

  function bindCardEvents() {
    // open/close menus
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

    // actions
    $$('.media-card .act-attach', grid).forEach(el => el.addEventListener('click', () => attach(el.dataset.id)));
    $$('.media-card .act-detach', grid).forEach(el => el.addEventListener('click', () => detach(el.dataset.id)));
    $$('.media-card .act-delete', grid).forEach(el => el.addEventListener('click', () => del(el.dataset.id)));

    // drag to canvas
    $$('.media-card', grid).forEach(card => {
      card.addEventListener('dragstart', (ev) => {
        ev.dataTransfer.setData('text/plain', card.dataset.id);
        ev.dataTransfer.effectAllowed = 'copy';
      });
    });
  }

  // Canvas drop binding (expects an element with id="canvasDropZone")
  const canvas = document.getElementById('canvasDropZone');
  if (canvas) {
    canvas.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect='copy'; });
    canvas.addEventListener('drop', (e) => {
      e.preventDefault();
      const mediaId = parseInt(e.dataTransfer.getData('text/plain'), 10);
      if (!mediaId) return;

      // Determine approximate drop coords relative to canvas
      const rect = canvas.getBoundingClientRect();
      const x = Math.max(0, Math.round(e.clientX - rect.left));
      const y = Math.max(0, Math.round(e.clientY - rect.top));

      // Ensure it’s attached to the board’s library
      const present = items.find(m => Number(m.id) === mediaId && (currentScope==='board' || m.attached_to_board == 1));
      const doPlace = () => {
        // Create a canvas item (your board items endpoint)
        const body = new FormData();
        body.append('media_id', String(mediaId));
        body.append('x', String(x));
        body.append('y', String(y));
        // type can be inferred server-side from mime/provider
        fetch(`/api/moods/${encodeURIComponent(boardSlug)}/items`, { method:'POST', body, credentials:'same-origin' })
          .then(r => r.json())
          .then(() => {
            // Up to you: re-render canvas, or request board items again
          });
      };

      if (present) return doPlace();

      // Attach then place
      const fd = new FormData();
      fd.append('media_id[]', String(mediaId));
      fetch(`/api/moods/${encodeURIComponent(boardSlug)}/library:attach`, { method:'POST', body:fd, credentials:'same-origin' })
        .then(() => doPlace());
    });
  }

  // UI controls
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
    // small debounce
    clearTimeout(qInput._t); qInput._t = setTimeout(fetchList, 250);
  });

  // ===== Upload with per-file progress (XHR) =====
	if (uploadBtn && uploadInput) {
	  uploadBtn.addEventListener('click', () => uploadInput.click());
	  uploadInput.addEventListener('change', () => {
		if (!uploadInput.files || !uploadInput.files.length) return;
		cancelledAll = false;
		enqueueUploads(Array.from(uploadInput.files));
		uploadInput.value = '';
	  });
	}
	// --- queue state (scoped to this IIFE) ---
	const MAX_PARALLEL = 3;
	const upQueue = [];
	let inFlight = 0;
	let cancelledAll = false;
	  
	//optional global pill
	const pill = document.getElementById('uploadQueuePill');
	pill?.querySelector('.upl-cancel')?.addEventListener('click', () => {
	  cancelledAll = true;
	  upQueue.length = 0;
	  // NOTE: running XHRs are not aborted here; add a reference store+abort if you need hard cancel
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
	  // reuse your templateCard()
	  const html = templateCard(mediaObj);
	  const wrap = document.createElement('div');
	  wrap.innerHTML = html.trim();
	  const real = wrap.firstElementChild;
	  tmpEl.replaceWith(real);
	  bindCardEvents?.(); // keep behaviors
	}
	  
	function uploadOneFile(file) {
	  inFlight++; pillUpdate();

	  const tmpEl = makeTempCard(file);
	  const bar = tmpEl.querySelector('.upl-bar i');
	  const msg = tmpEl.querySelector('.upl-msg');

	  const fd = new FormData();
	  // your endpoint accepts multiple: keep "file[]" but send one per XHR
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

			// Your backend sometimes returns just success, sometimes media; handle both:
			let mediaObj = null;

			// Case A: { success:true, media:[...] }
			if (res && res.success && Array.isArray(res.media) && res.media.length) {
			  mediaObj = res.media[0];
			}
			// Case B: a direct media object (id/uuid/mime/thumb_url/large_url…)
			else if (res && res.id && (res.thumb_url || res.uuid)) {
			  mediaObj = res;
			}

			if (mediaObj) {
			  // if upload implicitly attaches when board_id is present, we’re done
			  replaceTempWithReal(tmpEl, mediaObj);
			  // also update items in memory for fallback logic
			  try { items.unshift(mediaObj); } catch(_) {}
			} else {
			  // fallback: just refresh list
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

  // ensure grid has masonry class (one-time)
	document.addEventListener('DOMContentLoaded', () => {
	  const grid = document.getElementById('libraryGrid') || document.getElementById('mediaGrid');
	  if (!grid) { console.warn('[MediaLibrary] grid container not found'); }
		
		if (grid && !grid.classList.contains('masonry-cols')) {
		grid.classList.add('masonry-cols');
}
	});
	
  // Initial load
  fetchList();
})();
