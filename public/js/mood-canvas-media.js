/*! mood-canvas-media.js — Media overlay picker for frames (one media per frame) */
(function(){
  'use strict';

  // -------------------------
  // Config – tweak to your API & thumb URLs
  // -------------------------
  var API_MEDIA_SEARCH = '/api/media?limit=40';    // <- adjust path if different
  function getMediaThumbUrl(m){
    // You can route by UUID on your server (recommended):
    // return '/media/thumb/' + m.uuid + '?w=320&h=200&fit=crop';
    // Fallbacks:
    if (m.provider === 'youtube' && m.provider_id) {
      return 'https://img.youtube.com/vi/' + m.provider_id + '/hqdefault.jpg';
    }
    // If you already serve files by uuid:
    return '/storage/thumbs/' + m.uuid + '_thumb.jpg'; // adjust to your CDN route
  }

  // -------------------------
  // Overlay UI
  // -------------------------
  function injectStyle(){
    if (document.getElementById('mc-media-style')) return;
    var css = ''
      + '#mc-media-ov{position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:saturate(1.2) blur(1.5px);z-index:99998;display:none;}'
	  + '#mc-media-win{position:absolute;inset:24px;background:#0f172a;color:#e5e7eb;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.35);display:flex;flex-direction:column;}'
	  + '#mc-media-head{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08);}'
	  + '#mc-media-head h3{margin:0;font:600 16px/1.2 system-ui;}'
	  + '#mc-media-close{margin-left:auto;background:#1f2937;color:#fff;border:1px solid #374151;border-radius:10px;padding:6px 10px;cursor:pointer;}'
	  + '#mc-media-head input{flex:1;background:#0b1220;color:#e5e7eb;border:1px solid #23324a;border-radius:10px;padding:8px 10px;}'
	  + '#mc-media-body{padding:14px;overflow:auto;flex:1;}'

	  /* Masonry: use CSS columns */
	  + '#mc-media-grid{column-count:5;column-gap:12px;}'
	  + '@media (max-width:1400px){#mc-media-grid{column-count:4;}}'
	  + '@media (max-width:1100px){#mc-media-grid{column-count:3;}}'
	  + '@media (max-width:800px){#mc-media-grid{column-count:2;}}'
	  + '@media (max-width:520px){#mc-media-grid{column-count:1;}}'

	  /* Cards become inline-block so they flow in columns; avoid breaking across columns */
	  + '.mc-card{display:inline-block;width:100%;margin:0 0 12px 0;background:#111827;border:1px solid #293244;border-radius:12px;overflow:hidden;cursor:pointer;break-inside:avoid;box-shadow:0 1px 0 rgba(0,0,0,.08);}'

	  + '.mc-card:hover{outline:2px solid #60a5fa;outline-offset:0;}'
	  + '.mc-thumb{background:#0b1220;display:block;position:relative;}'
	  + '.mc-thumb img{width:100%;height:auto;display:block;opacity:0;transition:opacity .2s ease;}'
	  + '.mc-thumb img.is-loaded{opacity:1;}'
	  + '.mc-meta{padding:8px 10px;font:12px/1.3 system-ui;color:#cbd5e1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}'
	  + '.mc-empty{padding:24px;text-align:center;color:#94a3b8;}'
	  ;
    var s = document.createElement('style'); s.id='mc-media-style'; s.textContent = css;
    document.head.appendChild(s);
  }

  function buildOverlay(){
    var ov = document.getElementById('mc-media-ov');
    if (ov) return ov;

    ov = document.createElement('div'); ov.id = 'mc-media-ov';
    var win = document.createElement('div'); win.id = 'mc-media-win';
    var head = document.createElement('div'); head.id = 'mc-media-head';
    var title = document.createElement('h3'); title.textContent = 'Media Library';
    var search = document.createElement('input'); search.type='search'; search.placeholder='Search filename, provider, tags…';
    var close = document.createElement('button'); close.id='mc-media-close'; close.type='button'; close.textContent='✕';

    var body = document.createElement('div'); body.id='mc-media-body';
    var grid = document.createElement('div'); grid.id='mc-media-grid';
    body.appendChild(grid);

    head.appendChild(title); head.appendChild(search); head.appendChild(close);
    win.appendChild(head); win.appendChild(body); ov.appendChild(win);
    document.body.appendChild(ov);

    close.addEventListener('click', function(){ hideOverlay(); });
    ov.addEventListener('click', function(e){ if (e.target === ov) hideOverlay(); });
    document.addEventListener('keydown', function(e){ if (ov.style.display==='block' && e.key==='Escape') hideOverlay(); });

    // Simple search debounce
    var t = null;
    search.addEventListener('input', function(){
      clearTimeout(t);
      t = setTimeout(function(){ fetchAndRender(grid, search.value.trim()); }, 250);
    });

    // First load
    fetchAndRender(grid, '');

    return ov;
  }

  function showOverlay(){ var ov = buildOverlay(); ov.style.display='block'; }
  function hideOverlay(){ var ov = document.getElementById('mc-media-ov'); if (ov) ov.style.display='none'; }

  // -------------------------
  // Data fetch + render grid
  // -------------------------
  async function fetchAndRender(grid, q){
    grid.innerHTML = '<div class="mc-empty">Loading…</div>';
    try {
      var url = API_MEDIA_SEARCH + (q ? ('&q=' + encodeURIComponent(q)) : '');
      var res = await fetch(url, { headers:{'Accept':'application/json'} });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      var data = await res.json();
      renderGrid(grid, Array.isArray(data) ? data : []);
    } catch (e) {
      console.warn('[media] fetch error', e);
      grid.innerHTML = '<div class="mc-empty">Could not load media.</div>';
    }
  }

  function renderGrid(grid, items){
	  grid.innerHTML = '';
	  if (!items.length) {
		grid.innerHTML = '<div class="mc-empty">No media found.</div>';
		return;
	  }

	  items.forEach(function(m){
		var card = document.createElement('div'); card.className = 'mc-card';

		var th = document.createElement('div'); th.className = 'mc-thumb';
		var img = document.createElement('img');
		img.alt = m.file_name || m.uuid;
		img.loading = 'lazy';
		img.decoding = 'async';
		img.src = getMediaThumbUrl(m);
		img.addEventListener('load', function(){ img.classList.add('is-loaded'); });
		th.appendChild(img);

		var meta = document.createElement('div'); meta.className = 'mc-meta';
		meta.textContent = (m.provider ? '['+m.provider+'] ' : '') + (m.file_name || m.uuid);

		card.appendChild(th);
		card.appendChild(meta);
		card.addEventListener('click', function(){ selectMediaForFrame(m); });

		grid.appendChild(card);
	  });
	}


  // -------------------------
  // Selection: assign to the selected frame (one media per frame)
  // -------------------------
  function selectMediaForFrame(m){
	  var mc = window.moodCanvas;
	  if (!mc || !mc.getState) return;

	  var ids = Array.from(mc.getState().selectedItemIds || []);
	  if (!ids.length) return void alert('Select a frame first.');

	  var targetId = null;
	  for (var i=0;i<ids.length;i++){
		var el = document.querySelector('.canvas-item[data-id="'+ids[i]+'"]');
		if (el && el.dataset.kind === 'frame'){ targetId = Number(ids[i]); break; }
	  }
	  if (!targetId) return void alert('Please select a frame (media can only be attached to frames).');

	  // pass full media object for optimistic render + server PATCH
	  var ok = mc.setItemMedia(targetId, {
		id: m.id,
		uuid: m.uuid,
		mime_type: m.mime_type,
		provider: m.provider || null,
		provider_id: m.provider_id || null,
		file_name: m.file_name || null
	  });

	  // If API not available, you can still do a DOM-only fallback (optional):
	  // if (!ok) attachMediaDom(targetId, m);

	  // close overlay
	  (ok !== false) && hideOverlay();
	}


  function attachMediaDom(itemId, m){
    var el = document.querySelector('.canvas-item[data-id="'+itemId+'"]');
    if (!el) return;
    var body = el.querySelector('.item-body') || el;
    // Clear any previous media node
    var old = body.querySelector('.mc-media'); if (old) old.remove();

    var mediaEl = document.createElement('div');
    mediaEl.className = 'mc-media';
    mediaEl.style.position = 'absolute';
    mediaEl.style.inset = '0';
    mediaEl.style.borderRadius = getComputedStyle(body).borderRadius || '';
    mediaEl.style.overflow = 'hidden';

    if ((m.mime_type||'').startsWith('image/')) {
      var img = document.createElement('img');
      img.src = getMediaThumbUrl(m); // or full-size route if you prefer
      img.alt = m.file_name || m.uuid;
      img.style.width='100%'; img.style.height='100%'; img.style.objectFit='contain';
      mediaEl.appendChild(img);
    } else if ((m.mime_type||'').startsWith('video/')) {
      // youtube uses provider embed elsewhere; here we show thumb
      var img2 = document.createElement('img');
      img2.src = getMediaThumbUrl(m);
      img2.alt = (m.provider||'video') + ' — ' + (m.file_name||m.uuid);
      img2.style.width='100%'; img2.style.height='100%'; img2.style.objectFit='cover';
      mediaEl.appendChild(img2);
    }
    body.appendChild(mediaEl);
  }

  // -------------------------
  // Toolbar button
  // -------------------------
  function addToolbarButton(){
    var bar = document.getElementById('canvas-toolbar') || document.querySelector('[data-canvas-toolbar]');
    if (!bar) return;

    var siblingBtn = bar.querySelector('button');
    var group = document.createElement('span');
    var btn = document.createElement('button');
    btn.type = siblingBtn && siblingBtn.type ? siblingBtn.type : 'button';
    btn.className = siblingBtn ? siblingBtn.className : '';
    btn.textContent = 'Media';
    btn.addEventListener('click', function(){
      // Ensure a frame is selected before opening, UX sugar
      var mc = window.moodCanvas;
      var hasFrame = false;
      if (mc && mc.getState) {
        var ids = Array.from(mc.getState().selectedItemIds || []);
        for (var i=0;i<ids.length;i++){
          var el = document.querySelector('.canvas-item[data-id="'+ids[i]+'"]');
          if (el && el.dataset.kind === 'frame'){ hasFrame = true; break; }
        }
      }
      if (!hasFrame) { alert('Select a frame to attach media.'); return; }
      injectStyle(); showOverlay();
    });

    group.appendChild(btn);
    // insert after "Frame" button if present
    var after = bar.querySelector('button[data-action="frame"]');
    if (after && after.parentNode) after.parentNode.insertBefore(group, after.nextSibling);
    else bar.appendChild(group);
  }

  // -------------------------
  // Bus wiring
  // -------------------------
  function onReady(){
    addToolbarButton();
  }

  function waitBus(){
    if (window.moodCanvasBus && typeof window.moodCanvasBus.on === 'function') {
      window.moodCanvasBus.on('ready', onReady);
      // if v1 already emitted
      if (window.moodCanvas) setTimeout(onReady, 0);
    } else {
      // retry until v1 loads
      var t = setInterval(function(){
        if (window.moodCanvasBus && typeof window.moodCanvasBus.on === 'function') {
          clearInterval(t); window.moodCanvasBus.on('ready', onReady);
          if (window.moodCanvas) setTimeout(onReady, 0);
        }
      }, 200);
    }
  }

  waitBus();
})();
