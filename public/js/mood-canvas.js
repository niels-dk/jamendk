/* mood-canvas.js — stable, no dup declarations; connectors under items; pan/zoom in sync */
(function () {
  'use strict';

  // ---------- DOM ----------
  const stage   = document.getElementById('canvasStage');    // container DIV
  const content = document.getElementById('canvasContent');  // HTML items live here (absolute DIVs)
  const svgEl   = document.getElementById('canvasOverlay');  // <svg id="canvasOverlay">
  const toolbar = document.getElementById('canvas-toolbar') || document.querySelector('[data-canvas-toolbar]');
  const toolPill= document.getElementById('tool-pill');

  // Create/attach the two overlay groups once (back = permanent lines, front = dashed preview)
  let svgBack  = document.getElementById('overlayBack');
  let svgFront = document.getElementById('overlayFront');
  if (svgEl && !svgBack)  { svgBack  = document.createElementNS('http://www.w3.org/2000/svg','g'); svgBack.id  = 'overlayBack';  svgEl.appendChild(svgBack); }
  if (svgEl && !svgFront) { svgFront = document.createElementNS('http://www.w3.org/2000/svg','g'); svgFront.id = 'overlayFront'; svgEl.appendChild(svgFront); }

  const slug    = window.boardSlug || '';
  let apiBase = `/api/moods/${slug}/canvas/items`;
  if (!stage || !content || !svgEl || !svgBack || !svgFront) { console.error('[canvas] missing DOM nodes'); return; }

  // ---------- state ----------
  let currentTool = 'select';
  let snapToGrid  = false;

  let stageOffset = { x: 0, y: 0 }; // pan
  let stageScale  = 1;              // zoom
  
  // keep view state accessible to code outside the IIFE
  window.__moodCanvasView = window.__moodCanvasView || { scale: 1, offset: { x: 0, y: 0 } };

  let isPanning   = false;
  let panStart    = { x: 0, y: 0 };

  let draggingGroup = null;      // [{id, dx, dy}]
  // make selection accessible outside the IIFE (for public API / plugins)
  window.__moodCanvasSelection = window.__moodCanvasSelection || new Set();
  let selectedIds = window.__moodCanvasSelection;

  let resizing      = null;      // { id, handle, startMouse, startBox }
  let movingSingle  = null;      // { id, startMouse, startPos } (center move in resize tool)

  let selectedConnectorId = null; // connector selected via select tool

  // Drag-to-connect state
  let connectDrag   = null;      // { fromId, tempLine, hoverId }

  // Data + element maps
  const itemsById   = Object.create(null); // id -> { data, el }
  const linesById   = Object.create(null); // connectorId -> <line>
  const connectorsByItem = Object.create(null); // itemId -> Set(connectorIds)

  // Marquee
  let marquee = null, marqueeStart = null;

  // Space-to-pan: remembers the tool that was active before space was held
  let _spaceHeldPriorTool = null;

  // ---------- utils ----------
  const clampInt = (v) => (Number.isFinite(v) ? (v | 0) : 0);
  const snap     = (n) => snapToGrid ? Math.round(n / 8) * 8 : n;

  const isEmptySpace = (t) => (t === stage || t === content);
  const getItemFromTarget = (t) => t?.closest?.('.canvas-item') || null;

  function logicalFromClient(clientX, clientY) {
    const rect = stage.getBoundingClientRect();
    return {
      x: (clientX - rect.left - stageOffset.x) / stageScale,
      y: (clientY - rect.top  - stageOffset.y) / stageScale
    };
  }

  function ensureArrowMarker() {
    if (svgEl.querySelector('#arrowEnd')) return;
    const NS = 'http://www.w3.org/2000/svg';
    // NOTE: build the marker with createElementNS — setting innerHTML on an
    // SVG element doesn't reliably create SVG-namespaced children in every
    // browser. Static fill (#888) instead of context-stroke for the widest
    // browser support; we swap the line's stroke colour for selected state
    // but the arrow head stays gray (good enough, always visible).
    const defs   = document.createElementNS(NS, 'defs');
    const marker = document.createElementNS(NS, 'marker');
    marker.setAttribute('id', 'arrowEnd');
    marker.setAttribute('viewBox', '0 0 10 10');
    marker.setAttribute('refX', '9');
    marker.setAttribute('refY', '5');
    marker.setAttribute('markerWidth', '7');
    marker.setAttribute('markerHeight', '7');
    marker.setAttribute('orient', 'auto-start-reverse');
    const path = document.createElementNS(NS, 'path');
    path.setAttribute('d', 'M0,0 L10,5 L0,10 z');
    path.setAttribute('fill', '#888');
    path.setAttribute('stroke', 'none');
    marker.appendChild(path);
    defs.appendChild(marker);
    svgEl.insertBefore(defs, svgEl.firstChild);
  }

  function ensureOverlaySizing() {
    ensureArrowMarker();
    // make sure the SVG fills the stage and has a viewBox
    const w = stage.clientWidth || stage.offsetWidth || 1200;
    const h = stage.clientHeight || stage.offsetHeight || 800;
    svgEl.setAttribute('width',  String(w));
    svgEl.setAttribute('height', String(h));
    if (!svgEl.getAttribute('viewBox')) {
      svgEl.setAttribute('viewBox', `0 0 ${w} ${h}`);
      svgEl.setAttribute('preserveAspectRatio', 'xMinYMin meet');
    }
  }
  window.addEventListener('resize', ensureOverlaySizing);

  function applyTransforms() {
    // Move/scale HTML items
    content.style.transform = `translate(${stageOffset.x}px, ${stageOffset.y}px) scale(${stageScale})`;
    content.style.transformOrigin = '0 0';
    // Move/scale SVG groups (both back & front) so lines/ghost follow perfectly
    const tf = `translate(${stageOffset.x}, ${stageOffset.y}) scale(${stageScale})`;
    svgBack.setAttribute('transform', tf);
    svgFront.setAttribute('transform', tf);

	window.__moodCanvasView = window.__moodCanvasView || { scale: 1, offset: {x:0,y:0} };
	window.__moodCanvasView.scale  = stageScale;
	window.__moodCanvasView.offset = { x: stageOffset.x, y: stageOffset.y };

	// If a connector is selected, keep its toolbar anchored to the new midpoint
	if (selectedConnectorId !== null && window.__mc_repositionConnToolbar) {
	  window.__mc_repositionConnToolbar(selectedConnectorId);
	}
  }

  function centerFromData(d) {
    const cx = (Number(d.x)||0) + (Number(d.w)||0)/2;
    const cy = (Number(d.y)||0) + (Number(d.h)||0)/2;
    return { cx, cy };
  }

  /**
   * Return the point a small `margin` outside item d's bounding-box edge,
   * along the line from d's centre toward (tx, ty). Used so connector
   * endpoints (and their arrow heads) sit in the empty space outside the
   * item rather than under its background.
   */
  function edgePointToward(d, tx, ty, margin = 8) {
    const cx = (Number(d.x)||0) + (Number(d.w)||0)/2;
    const cy = (Number(d.y)||0) + (Number(d.h)||0)/2;
    const dx = tx - cx, dy = ty - cy;
    if (dx === 0 && dy === 0) return { x: cx, y: cy };
    const hw = Math.max(1, (Number(d.w)||1) / 2);
    const hh = Math.max(1, (Number(d.h)||1) / 2);
    const tX = dx === 0 ? Infinity : hw / Math.abs(dx);
    const tY = dy === 0 ? Infinity : hh / Math.abs(dy);
    const t  = Math.min(tX, tY);
    // Edge point
    const ex = cx + dx * t;
    const ey = cy + dy * t;
    // Push outward by `margin` along the unit direction so the arrow head
    // lands in the gap between items rather than under the item itself.
    const len = Math.sqrt(dx * dx + dy * dy);
    const ux = dx / len, uy = dy / len;
    return { x: ex + ux * margin, y: ey + uy * margin };
  }
  const ensureSet = (map, key) => (map[key] || (map[key] = new Set()));

  function setActiveToolButton(action) {
    // keep internal state in sync
    currentTool = action;
    if (toolbar) {
      toolbar.querySelectorAll('[data-action]')
        .forEach(b => b.classList.toggle('active', b.dataset.action === action));
    }
    stage.style.cursor =
      action === 'pan'    ? 'move' :
      action === 'select' ? 'default' :
      action === 'resize' ? 'nwse-resize' : 'crosshair';
    if (toolPill) toolPill.textContent = `Tool: ${action}${snapToGrid ? ' • Snap' : ''}`;
    updateSelectionUI();
  }
  // Simple public API for debugging / external UI
  window.moodCanvas = window.moodCanvas || {};
  window.moodCanvas.setTool = (action) => setActiveToolButton(action);
  window.moodCanvas.getTool = () => currentTool;

  // ---------- API helper ----------

	function isTypingTarget(el){
	  if (!el) return false;
	  if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') return true;
	  if (el.isContentEditable) return true;
	  // any nested input/textarea/contenteditable
	  return !!(el.closest && el.closest('input, textarea, [contenteditable="true"]'));
	}
	window.__mc_isEditingText = false;

  async function api(method, url, body) {
    let data = null, ok = false, status = 0;
    try {
      const res = await fetch(url, {
        method,
        headers: { 'Accept':'application/json', ...(body ? {'Content-Type':'application/json'} : {}) },
        body: body ? JSON.stringify(body) : undefined
      });
      status = res.status; ok = res.ok;
      try { data = await res.json(); } catch { data = null; }
    } catch (e) {
      console.warn('[canvas] network error:', method, url, e);
      return { ok:false, status:0, data:null };
    }
    if (!ok) console.warn('[canvas] API non-OK:', method, url, status, data);
    return { ok, status, data };
  }
  const apiGET    = (u)   => api('GET', u);
  const apiPOST   = (u,b) => api('POST', u, b);
  const apiPATCH  = (u,b) => api('PATCH', u, b);
  const apiDELETE = (u)   => api('DELETE', u);
	  
	  function __mc_renderMediaIntoFrame(el, media) {
		  if (!el || el.dataset.kind !== 'frame') return;

		  const body    = el.querySelector('.item-body') || el;
		  const titleEl = el.querySelector('.frame-title');

		  // Clear media → show title + restore top gap, then exit
		  if (!media) {
			if (titleEl) titleEl.style.display = '';
			if (body)    body.style.top = '26px';
			// Remove any existing media node(s)
			body.querySelectorAll('.mc-media').forEach(n => n.remove());
			return;
		  }

		  // Media present → hide title + remove top gap
		  if (titleEl) titleEl.style.display = 'none';
		  if (body)    body.style.top = '0';

		  // Replace existing media node(s) — be defensive, kill stacks from legacy data
		  body.querySelectorAll('.mc-media').forEach(n => n.remove());

		  // Pull image position from the item payload (default centered)
		  const itemId = Number(el.dataset.id);
		  const itemData = itemsById[itemId]?.data;
		  const pos = itemData?.payload?.image_pos || { x: 50, y: 50 };

		  const wrap = document.createElement('div');
		  wrap.className = 'mc-media';
		  wrap.style.cssText = 'position:absolute;inset:0;border-radius:inherit;overflow:hidden;';
		  wrap.style.pointerEvents = 'none'; // let frame receive drags/clicks

		  const makeImg = (src, alt, fit) => {
			const img = document.createElement('img');
			img.src = src;
			img.alt = alt || '';
			img.style.width = '100%';
			img.style.height = '100%';
			img.style.objectFit = fit;
			img.style.objectPosition = `${pos.x}% ${pos.y}%`;
			img.draggable = false;
			img.setAttribute('draggable', 'false');
			img.style.userSelect = 'none';
			img.style.webkitUserDrag = 'none';
			img.addEventListener('dragstart', e => e.preventDefault());
			return img;
		  };

		  if ((media.mime_type || '').startsWith('image/')) {
			wrap.appendChild(
			  makeImg('/storage/thumbs/' + media.uuid + '_thumb.jpg',
					  media.file_name || media.uuid, 'cover')
			);
		  } else if (media.provider === 'youtube' && media.provider_id) {
			wrap.appendChild(
			  makeImg('https://img.youtube.com/vi/' + media.provider_id + '/hqdefault.jpg',
					  media.file_name || media.provider_id, 'cover')
			);
		  }

		  body.appendChild(wrap);
		}



  // ---------- selection UI ----------
  function addHandles(el) {
	  if (!el || el.querySelector('.resize-handle')) return;
	  var positions = ['nw','n','ne','e','se','s','sw','w'];
	  for (var i=0;i<positions.length;i++) {
		var pos = positions[i];
		var h = document.createElement('div');
		h.className = 'resize-handle ' + pos;
		h.dataset.handle = pos;
		h.addEventListener('mousedown', startResize);
		el.appendChild(h);
	  }
	  // center move dot
	  var c = document.createElement('div');
	  c.className = 'resize-handle center';
	  c.dataset.handle = 'center';
	  c.title = 'Drag to move';
	  c.addEventListener('mousedown', startMoveCenter);
	  el.appendChild(c);
	}

	function removeHandles(el) {
	  if (!el) return;
	  var hs = el.querySelectorAll('.resize-handle');
	  for (var i=0;i<hs.length;i++) hs[i].remove();
	}

  function updateSelectionUI() {
	  for (const id in itemsById) {
		const entry = itemsById[id]; const el = entry && entry.el;
		if (!el) continue;
		el.classList.remove('selected'); el.classList.remove('connect-hover');
		removeHandles(el);
	  }
	  if (selectedIds.size === 1) {
		const id = [...selectedIds][0];
		const el = itemsById[id] && itemsById[id].el;
		if (el) {
		  el.classList.add('selected');
		  if (currentTool === 'resize') addHandles(el);
		}
	  } else if (selectedIds.size > 1) {
		selectedIds.forEach(id => { const el = itemsById[id]?.el; if (el) el.classList.add('selected'); });
	  }
	}

	function clearSelection() {
	  selectedIds.clear();
	  updateSelectionUI();
	  if (window.moodCanvasBus) window.moodCanvasBus.emit('selection:changed', { items: new Set(selectedIds) });
	}

	function addToSelection(id) {
	  selectedIds.add(id);
	  updateSelectionUI();
	  if (window.moodCanvasBus) window.moodCanvasBus.emit('selection:changed', { items: new Set(selectedIds) });
	}

	function setSingleSelection(id) {
	  // console.log('[v1] setSingleSelection', id);
	  selectedIds.clear();
	  selectedIds.add(id);            // <-- these lines belong here, not at top-level
	  updateSelectionUI();
	  if (window.moodCanvasBus) window.moodCanvasBus.emit('selection:changed', { items: new Set(selectedIds) });
	}
	  
 // selectedIds.clear(); selectedIds.add(id);
//  selectedIds = new Set([id]);
//  updateSelectionUI();

  // ---------- renderers ----------
  function renderItem(item) {
	  const el = document.createElement('div');
	  el.className = `canvas-item kind-${item.kind}` + (item.locked ? ' locked' : '');
	  el.dataset.id = String(item.id);
	  el.dataset.kind = item.kind;
	  el.style.cssText = [
		'position:absolute','box-sizing:border-box','user-select:none',
		`left:${clampInt(item.x)}px`, `top:${clampInt(item.y)}px`,
		`width:${Math.max(1, clampInt(item.w))}px`, `height:${Math.max(1, clampInt(item.h))}px`,
		`z-index:${clampInt(item.z)}`,
		'border:1px solid #999','background:#f9f9f9','padding:0','color:#000',
		'overflow:visible' // keep resize handles/outline visible
	  ].join(';');

	  // Inner body that clips content
	  const body = document.createElement('div');
	  body.className = 'item-body';
	  body.style.cssText = [
		'position:absolute','inset:0',
		'padding:4px','box-sizing:border-box',
		'overflow:clip' // clip text (use "auto" for scrollbars)
	  ].join(';');
	  el.appendChild(body);

	  if (item.kind === 'note' || item.kind === 'label') {
		body.textContent = (item.payload && item.payload.text)
		  ? item.payload.text
		  : (item.kind === 'label' ? 'Label' : 'Note');
		body.style.fontSize = item.kind === 'label' ? '13px' : '14px';
		if (item.kind === 'note') el.style.background = '#fffbe6';
		if (item.kind === 'label') { el.style.background = '#eef'; el.style.borderRadius = '10px'; }
		el.ondblclick = () => inlineEditText(item.id, el); // inline edit should target .item-body
	  } else if (item.kind === 'frame') {
		  el.style.background = '#fff';
		  el.style.border = '2px solid #666';

		  const title = document.createElement('div');
		  title.className = 'frame-title';
		  title.textContent = (item.payload && item.payload.title) ? item.payload.title : 'Frame';
		  title.style.cssText = 'font-weight:600;margin:4px 4px 6px 4px;font-size:14px;color:#000';
		  el.appendChild(title);

		  // Decide if this frame already has media (from JOIN or payload)
		  const mediaObj =
			item.media ||
			(item.payload && item.payload.media) ||
			null;

		  if (mediaObj) {
			// Media present → hide title and remove gap, then render media
			title.style.display = 'none';
			body.style.top = '0';
			__mc_renderMediaIntoFrame(el, mediaObj);
		  } else {
			// No media → show title and keep gap
			title.style.display = '';
			body.style.top = '26px';
		  }
		}


	  
	  // (Frame media is rendered above via __mc_renderMediaIntoFrame; older
	  // duplicate block removed to stop multiple stacked images.)

	  // Apply saved style from DB (highlight etc.)
	  if (item.style) {
		if (item.style.backgroundColor) el.style.backgroundColor = item.style.backgroundColor;
		if (item.style.borderColor)     el.style.borderColor     = item.style.borderColor;
		if (item.style.highlight)       el.style.boxShadow       = '0 0 12px ' + item.style.highlight;
	  }

	  content.appendChild(el);
	  itemsById[item.id] = { data: item, el };
	  return el;
	}
	  
  function renderConnector(item) {
    // Visible line
    const line = document.createElementNS('http://www.w3.org/2000/svg','line');
    line.dataset.id = String(item.id);
    line._payload = item.payload;
    line.setAttribute('stroke', '#888');
    line.setAttribute('stroke-width', '2');
    line.setAttribute('stroke-linecap', 'round');
    line.setAttribute('pointer-events', 'none');
    // marker-start / marker-end are managed by _applyConnectorStyle based on payload.arrows
    svgBack.appendChild(line);
    linesById[item.id] = line;

    // Wide transparent hit line so hover/click work without needing pixel-perfect aim
    const hit = document.createElementNS('http://www.w3.org/2000/svg','line');
    hit.setAttribute('stroke', 'transparent');
    hit.setAttribute('stroke-width', '20');
    hit.setAttribute('stroke-linecap', 'round');
    hit.setAttribute('pointer-events', 'stroke');
    hit.style.cursor = 'pointer';
    svgBack.appendChild(hit);
    line._hitLine = hit;

    hit.addEventListener('mouseenter', () => {
      line.setAttribute('stroke', '#4a90e2');
      line.setAttribute('stroke-width', '3');
    });
    hit.addEventListener('mouseleave', () => {
      line.setAttribute('stroke', '#888');
      line.setAttribute('stroke-width', '2');
    });
    hit.addEventListener('click', (e) => {
      e.stopPropagation();
      if (currentTool === 'delete') {
        deleteConnector(Number(item.id));
        return;
      }
      // Any other tool: tap-to-select. Opens the floating toolbar which
      // hosts the Delete button — so users don't have to switch to a
      // specific tool just to remove or restyle a connector.
      clearSelection();
      selectConnector(Number(item.id));
    });

    const aId = item.payload?.a?.item, bId = item.payload?.b?.item;
    if (aId) ensureSet(connectorsByItem, aId).add(item.id);
    if (bId) ensureSet(connectorsByItem, bId).add(item.id);
    updateConnectorLine(item.id);
    _applyConnectorStyle(item.id);
    _renderConnectorLabel(item.id);
  }

  function updateConnectorLine(connectorId) {
    const line = linesById[connectorId]; if (!line) return;
    const p = line._payload;
    const a = p?.a?.item ? itemsById[p.a.item]?.data : null;
    const b = p?.b?.item ? itemsById[p.b.item]?.data : null;
    if (!a || !b) {
      ['x1','y1','x2','y2'].forEach(k => {
        line.setAttribute(k, '-1000');
        if (line._hitLine) line._hitLine.setAttribute(k, '-1000');
      });
      return;
    }
    // Endpoints sit on each item's edge (not centre) so the arrow heads
    // — placed by marker-end / marker-start — land outside the items'
    // own backgrounds and remain visible.
    const ca = centerFromData(a), cb = centerFromData(b);
    const pa = edgePointToward(a, cb.cx, cb.cy);
    const pb = edgePointToward(b, ca.cx, ca.cy);
    const coords = { x1: String(pa.x), y1: String(pa.y), x2: String(pb.x), y2: String(pb.y) };
    Object.entries(coords).forEach(([k, v]) => {
      line.setAttribute(k, v);
      if (line._hitLine) line._hitLine.setAttribute(k, v);
    });
    if (line._labelEl) _renderConnectorLabel(connectorId);
    // Keep the floating toolbar anchored if this connector is selected
    if (selectedConnectorId === connectorId && window.__mc_repositionConnToolbar) {
      window.__mc_repositionConnToolbar(connectorId);
    }
  }

  function refreshAttachedConnectors(itemId) {
    const set = connectorsByItem[itemId]; if (!set) return;
    for (const cid of set) updateConnectorLine(cid);
  }
  function removeConnectorsAttachedTo(itemId) {
    const set = connectorsByItem[itemId]; if (!set) return;
    for (const cid of Array.from(set)) {
      const line = linesById[cid];
      if (line?.parentNode) line.parentNode.removeChild(line);
      if (line?._hitLine?.parentNode) line._hitLine.parentNode.removeChild(line._hitLine);
      delete linesById[cid];
      Object.keys(connectorsByItem).forEach(k => connectorsByItem[k]?.delete(cid));
      delete itemsById[cid];
      apiDELETE(`${apiBase}/${cid}/delete`).catch(console.warn);
    }
    delete connectorsByItem[itemId];
  }
  
	// ---------- updates (merge -> DOM -> persist) ----------
	async function updateItem(id, patch) {
		console.log('[v1] updateItem', id, patch);
											
	  const entry = itemsById[id];
	  if (!entry) return false;

	  const d = entry.data;

	  // 1) Merge scalar fields if present
	  ['x','y','w','h','z','rotation','locked','hidden'].forEach(function(k){
		if (Object.prototype.hasOwnProperty.call(patch, k)) d[k] = patch[k];
	  });

	  // 2) Merge payload (shallow)
	  if (patch.payload) d.payload = Object.assign({}, d.payload || {}, patch.payload);

	  // 3) Merge style (shallow)
	  if (patch.style) d.style = Object.assign({}, d.style || {}, patch.style);

	  // 4) Update DOM immediately (so UI reacts before server reply)
	  var el = entry.el;
	  if (el) {
		if ('x' in patch) el.style.left   = (d.x|0) + 'px';
		if ('y' in patch) el.style.top    = (d.y|0) + 'px';
		if ('w' in patch) el.style.width  = Math.max(1, d.w|0) + 'px';
		if ('h' in patch) el.style.height = Math.max(1, d.h|0) + 'px';

		if (patch.style) {
		  var s = d.style || patch.style;
		  // Choose the visuals you want to represent “highlight”
		  if ('borderColor' in patch.style)      el.style.outline      = s.borderColor ? ('3px solid ' + s.borderColor) : '';
		  if ('backgroundColor' in patch.style)  el.style.backgroundColor = s.backgroundColor || '';
		  if ('highlight' in patch.style)        el.style.boxShadow    = s.highlight ? ('0 0 12px ' + s.highlight) : '';
		}
	  }

	  // 5) Keep connectors in sync when size/pos changed
	  if ('x' in patch || 'y' in patch || 'w' in patch || 'h' in patch) {
		refreshAttachedConnectors(id);
	  }
	  
	  // optimistic media handling
	  if ('media' in patch || 'media_id' in patch || (patch.payload && 'media' in patch.payload)) {
		  const entry = itemsById[id];
		  if (entry) {
			const d  = entry.data;
			const el = entry.el;

			let media = null;
			if (patch.media && typeof patch.media === 'object') media = patch.media;
			else if (patch.payload && patch.payload.media)       media = patch.payload.media;
			else if (patch.media_id === null)                    media = null;

			// keep in item data so it survives until server echo
			if (media === null) {
			  if (d.payload) delete d.payload.media;
			  d.media = null;
			} else if (media) {
			  d.payload = Object.assign({}, d.payload||{}, { media });
			  d.media   = media;
			}

			if (el) __mc_renderMediaIntoFrame(el, media);
		  }
		}

	  // 6) Persist to server (non-blocking UI)
	  try {
		await apiPATCH(`${apiBase}/${id}`, patch);
	  } catch (e) {
		console.warn('[canvas] updateItem PATCH failed', id, patch, e);
	  }

	  return true;
	}
	  
	  // make updater visible to the public API block outside the IIFE
	  window.__mc_updateItem = updateItem;

  // ---------- create helpers ----------
  async function createNoteAt(x, y) {
    const r = await apiPOST(`${apiBase}/create`, { kind:'note', x:snap(x), y:snap(y), w:200, h:100, payload:{ text:'New note' } });
    const item = r.data && r.data.id ? r.data : { id: Date.now(), kind:'note', x:snap(x), y:snap(y), w:200, h:100, payload:{text:'New note'} };
    renderItem(item); setSingleSelection(item.id);
  }
  async function createFrameAt(x, y) {
    const r = await apiPOST(`${apiBase}/create`, { kind:'frame', x:snap(x), y:snap(y), w:300, h:200, payload:{ title:'Frame' } });
    const item = r.data && r.data.id ? r.data : { id: Date.now(), kind:'frame', x:snap(x), y:snap(y), w:300, h:200, payload:{title:'Frame'} };
    renderItem(item); setSingleSelection(item.id);
  }
  async function createConnectorBetween(aId, bId) {
    if (!aId || !bId || aId === bId) return;
    const r = await apiPOST(`${apiBase}/create`, {
      kind:'connector', x:0,y:0,w:0,h:0, payload:{ a:{item:Number(aId)}, b:{item:Number(bId)}, style:'straight' }
    });
    const conn = r.data && r.data.id ? r.data : { id: Date.now(), kind:'connector', payload:{ a:{item:Number(aId)}, b:{item:Number(bId)} } };
    itemsById[conn.id] = { data: conn };
    renderConnector(conn);
  }

  // ---------- inline edit (auto-grow) ----------
  async function inlineEditText(id, el) {
	  const item = itemsById[id]?.data; if (!item) return;
	  const body = el.querySelector('.item-body') || el; // fallback just in case

	  const start = (item.payload && item.payload.text) ? item.payload.text : '';
	  const ta = document.createElement('textarea');
	  ta.value = start;
	  ta.style.cssText = [
		'position:absolute','inset:0',
		'resize:none','border:1px solid #4a90e2','border-radius:4px',
		'padding:6px','font:14px/1.4 system-ui','box-sizing:border-box',
		'color:#000','background:#fff'
	  ].join(';');

	  // edit inside body; keep outer el free for handles/outline
	  body.innerHTML = '';
	  body.appendChild(ta);
	  ta.focus(); 
	  //ta.select();
	  
	  window.__mc_isEditingText = true;

		ta.addEventListener('keydown',   e => e.stopPropagation(), true);
		ta.addEventListener('keypress',  e => e.stopPropagation(), true);
		ta.addEventListener('keyup',     e => e.stopPropagation(), true);

	  function grow(){ ta.style.height='auto'; ta.style.height = ta.scrollHeight+'px'; }
	  grow(); ta.addEventListener('input', grow);

	  function commit(save){
		  ta.onblur = ta.onkeydown = null;
		  const text = save ? ta.value : start;
		  body.innerHTML=''; body.textContent = text;
		  itemsById[id].data.payload = { ...(itemsById[id].data.payload||{}), text };
		  apiPATCH(`${apiBase}/${id}`, { payload:{ text } }).catch(console.warn);
		  window.__mc_isEditingText = false;   // <— important
		}
	  
	  ta.onblur = () => commit(true);
	  ta.onkeydown = (e)=>{ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); commit(true);} if(e.key==='Escape'){e.preventDefault(); commit(false);} };
	  
	  // Focus without selecting all, place caret at end (iOS/Android/desktop safe)
		ta.focus({ preventScroll: true });
		const placeCaretAtEnd = () => {
		  try {
			const end = ta.value.length;
			ta.setSelectionRange(end, end);
		  } catch(_) {}
		};
		if ('requestAnimationFrame' in window) {
		  requestAnimationFrame(placeCaretAtEnd);
		} else {
		  setTimeout(placeCaretAtEnd, 0);
		}
	}


  // ---------- resize (handles + center move) ----------
  function startMoveCenter(e) {
    if (currentTool !== 'resize') return;
    e.preventDefault(); e.stopPropagation();
    const el = e.target.closest('.canvas-item'); if (!el) return;
    const id = Number(el.dataset.id);
    setSingleSelection(id);
    const m = logicalFromClient(e.clientX, e.clientY);
    movingSingle = { id, startMouse:m, startPos:{ x: parseInt(el.style.left,10), y: parseInt(el.style.top,10) } };
    document.addEventListener('mousemove', onMoveCenter);
    document.addEventListener('mouseup', onMoveCenterUp);
  }
  function onMoveCenter(e) {
    if (!movingSingle) return;
    const { id, startMouse, startPos } = movingSingle;
    const el = itemsById[id]?.el; if (!el) return;
    const m = logicalFromClient(e.clientX, e.clientY);
    const nx = snap(startPos.x + (m.x - startMouse.x));
    const ny = snap(startPos.y + (m.y - startMouse.y));
    el.style.left = `${nx}px`; el.style.top = `${ny}px`;
    const d = itemsById[id].data; d.x = nx; d.y = ny;
    refreshAttachedConnectors(id);
  }
  async function onMoveCenterUp() {
    if (!movingSingle) return;
    const { id } = movingSingle;
    movingSingle = null;
    document.removeEventListener('mousemove', onMoveCenter);
    document.removeEventListener('mouseup', onMoveCenterUp);
    const d = itemsById[id]?.data; if (!d) return;
    await apiPATCH(`${apiBase}/${id}`, { x:d.x, y:d.y }).catch(console.warn);
  }

  function startResize(e) {
    if (currentTool !== 'resize') return;
    e.preventDefault(); e.stopPropagation();
    const el = e.target.closest('.canvas-item'); if (!el) return;
    const handle = e.target.dataset.handle;
    if (handle === 'center') return; // handled by startMoveCenter
    const id = Number(el.dataset.id);
    if (itemsById[id]?.data?.locked) return; // locked items don't resize
    const box = {
      x: parseInt(el.style.left, 10),
      y: parseInt(el.style.top, 10),
      w: parseInt(el.style.width, 10),
      h: parseInt(el.style.height, 10),
    };
    const m = logicalFromClient(e.clientX, e.clientY);
    resizing = { id, handle, startMouse:m, startBox:box };
    document.addEventListener('mousemove', onResizeMove);
    document.addEventListener('mouseup', onResizeUp);
  }
  function onResizeMove(e) {
    if (!resizing) return;
    const { id, handle, startMouse, startBox } = resizing;
    const entry = itemsById[id]; const el = entry && entry.el; if (!el) return;
    const m  = logicalFromClient(e.clientX, e.clientY);
    let dx = m.x - startMouse.x, dy = m.y - startMouse.y;
    let x = startBox.x, y = startBox.y, w = startBox.w, h = startBox.h;
    if (handle.includes('e')) w = startBox.w + dx;
    if (handle.includes('s')) h = startBox.h + dy;
    if (handle.includes('w')) { w = startBox.w - dx; x = startBox.x + dx; }
    if (handle.includes('n')) { h = startBox.h - dy; y = startBox.y + dy; }
    w = Math.max(40, snap(w)); h = Math.max(30, snap(h));
    x = snap(x); y = snap(y);
    el.style.left = `${x}px`; el.style.top = `${y}px`;
    el.style.width = `${w}px`; el.style.height = `${h}px`;
    const d = entry.data; d.x=x; d.y=y; d.w=w; d.h=h;
    refreshAttachedConnectors(id);
  }
  async function onResizeUp() {
    if (!resizing) return;
    const { id } = resizing; resizing = null;
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', onResizeUp);
    const d = itemsById[id]?.data; if (!d) return;
    await apiPATCH(`${apiBase}/${id}`, { x:d.x, y:d.y, w:d.w, h:d.h }).catch(console.warn);
  }

  // ---------- drag-to-connect ----------
  function beginConnectDrag(fromId, clientX, clientY) {
    const line = document.createElementNS('http://www.w3.org/2000/svg','line');
    line.setAttribute('stroke', '#22c55e');
    line.setAttribute('stroke-width', '2');
    line.setAttribute('stroke-dasharray', '5,5');
    line.setAttribute('pointer-events', 'none');
    svgFront.appendChild(line);   // dashed preview
    connectDrag = { fromId, tempLine: line, hoverId: null };
    updateConnectDrag(clientX, clientY);
    document.addEventListener('mousemove', onConnectDragMove, true);
    document.addEventListener('mouseup', onConnectDragUp, true);
  }
  function updateConnectDrag(clientX, clientY) {
    if (!connectDrag) return;
    const fromEl = itemsById[connectDrag.fromId]?.el; if (!fromEl) return;
    const { cx, cy } = centerFromData(itemsById[connectDrag.fromId].data);
    connectDrag.tempLine.setAttribute('x1', cx);
    connectDrag.tempLine.setAttribute('y1', cy);
    const p = logicalFromClient(clientX, clientY);
    connectDrag.tempLine.setAttribute('x2', p.x);
    connectDrag.tempLine.setAttribute('y2', p.y);
    // hover highlight
    const hoverEl = document.elementFromPoint(clientX, clientY);
    const itemEl  = getItemFromTarget(hoverEl);
    const newHoverId = itemEl ? Number(itemEl.dataset.id) : null;
    if (connectDrag.hoverId != null && itemsById[connectDrag.hoverId]?.el) {
      itemsById[connectDrag.hoverId].el.classList.remove('connect-hover');
    }
    if (newHoverId != null && newHoverId !== connectDrag.fromId && itemsById[newHoverId]?.el) {
      itemsById[newHoverId].el.classList.add('connect-hover');
    }
    connectDrag.hoverId = newHoverId;
  }
  function endConnectDrag(clientX, clientY) {
    if (!connectDrag) return;
    const dropEl = getItemFromTarget(document.elementFromPoint(clientX, clientY));
    const toId = dropEl ? Number(dropEl.dataset.id) : null;
    if (connectDrag.hoverId != null && itemsById[connectDrag.hoverId]?.el) {
      itemsById[connectDrag.hoverId].el.classList.remove('connect-hover');
    }
    if (connectDrag.tempLine && connectDrag.tempLine.parentNode) connectDrag.tempLine.parentNode.removeChild(connectDrag.tempLine);
    const fromId = connectDrag.fromId; connectDrag = null;
    if (!toId || toId === fromId) return;
    if (itemsById[toId]?.data?.locked) return; // can't connect to a locked item
    createConnectorBetween(fromId, toId);
  }
  function onConnectDragMove(e) { updateConnectDrag(e.clientX, e.clientY); }
  function onConnectDragUp(e) {
    document.removeEventListener('mousemove', onConnectDragMove, true);
    document.removeEventListener('mouseup', onConnectDragUp, true);
    endConnectDrag(e.clientX, e.clientY);
  }

  // ---------- group drag / marquee ----------
  let marqueeAdditive = false;
  function beginMarquee(clientX, clientY, additive) {
    marqueeAdditive = !!additive;
    marqueeStart = logicalFromClient(clientX, clientY);
    marquee = document.createElement('div'); marquee.className = 'marquee';
    stage.appendChild(marquee);
    updateMarquee(clientX, clientY);
  }
  function updateMarquee(clientX, clientY) {
    if (!marquee || !marqueeStart) return;
    const cur = logicalFromClient(clientX, clientY);
    const x = Math.min(marqueeStart.x, cur.x), y = Math.min(marqueeStart.y, cur.y);
    const w = Math.abs(cur.x - marqueeStart.x), h = Math.abs(cur.y - marqueeStart.y);
    const left = x * stageScale + stageOffset.x;
    const top  = y * stageScale + stageOffset.y;
    marquee.style.left = `${left}px`; marquee.style.top = `${top}px`;
    marquee.style.width = `${w * stageScale}px`; marquee.style.height = `${h * stageScale}px`;
  }
  function endMarquee() {
    if (!marquee || !marqueeStart) return;
    const box = marquee.getBoundingClientRect();
    marquee.remove(); marquee = null; marqueeStart = null;
    const rect = stage.getBoundingClientRect();
    const lx = (box.left - rect.left - stageOffset.x) / stageScale;
    const ly = (box.top  - rect.top  - stageOffset.y) / stageScale;
    const lw = box.width  / stageScale;
    const lh = box.height / stageScale;
    if (!marqueeAdditive) clearSelection();
    for (const id in itemsById) {
      const entry = itemsById[id]; if (!entry || !entry.el) continue;
      const d = entry.data;
      const inter = !(d.x + d.w < lx || d.y + d.h < ly || d.x > lx + lw || d.y > ly + lh);
      if (inter && d.kind !== 'connector') addToSelection(Number(id));
    }
    marqueeAdditive = false;
  }
  function startGroupDragFromItem(itemEl, clientX, clientY) {
    const id = Number(itemEl.dataset.id);
    if (!selectedIds.has(id)) setSingleSelection(id);
    const mouse = logicalFromClient(clientX, clientY);
    // Skip locked items when building the drag list (selection still updated)
    draggingGroup = Array.from(selectedIds)
      .filter(sid => !itemsById[sid]?.data?.locked)
      .map(sid => {
        const d = itemsById[sid].data;
        return { id:sid, dx: mouse.x - d.x, dy: mouse.y - d.y };
      });
    if (!draggingGroup.length) { draggingGroup = null; return; }
    document.addEventListener('mousemove', onGroupDragMove);
    document.addEventListener('mouseup', onGroupDragUp);
  }
  function onGroupDragMove(e) {
    const m = logicalFromClient(e.clientX, e.clientY);
    draggingGroup.forEach(it => {
      const x = snap(m.x - it.dx), y = snap(m.y - it.dy);
      const entry = itemsById[it.id]; const el = entry && entry.el; if (!el) return;
      const d = entry.data;
      d.x = x; d.y = y; el.style.left = `${x}px`; el.style.top = `${y}px`;
      refreshAttachedConnectors(it.id);
    });
  }
  async function onGroupDragUp() {
    const group = draggingGroup; draggingGroup = null;
    document.removeEventListener('mousemove', onGroupDragMove);
    document.removeEventListener('mouseup', onGroupDragUp);
    for (const it of group) {
      const d = itemsById[it.id]?.data; if (!d) continue;
      await apiPATCH(`${apiBase}/${it.id}`, { x:d.x, y:d.y }).catch(console.warn);
    }
  }

  // ---------- delete ----------
  async function deleteItem(id) {
    const it = itemsById[id]; if (!it) return;
    if (it.data?.locked) return; // locked items can't be deleted
    await apiDELETE(`${apiBase}/${id}/delete`).catch(console.warn);
    if (it.el?.parentNode) it.el.parentNode.removeChild(it.el);
    delete itemsById[id]; removeConnectorsAttachedTo(id);
    selectedIds.delete(id); updateSelectionUI();
  }

  async function deleteConnector(id) {
    const line = linesById[id]; if (!line) return;
    if (line.parentNode) line.parentNode.removeChild(line);
    if (line._hitLine?.parentNode) line._hitLine.parentNode.removeChild(line._hitLine);
    if (line._labelGroup?.parentNode) line._labelGroup.parentNode.removeChild(line._labelGroup);
    delete linesById[id];
    Object.keys(connectorsByItem).forEach(k => connectorsByItem[k]?.delete(id));
    delete itemsById[id];
    if (selectedConnectorId === id) selectedConnectorId = null;
    await apiDELETE(`${apiBase}/${id}/delete`).catch(console.warn);
  }

  function selectConnector(id) {
    // Deselect previous
    if (selectedConnectorId !== null) {
      const prev = linesById[selectedConnectorId];
      if (prev) { prev.setAttribute('stroke', '#888'); prev.setAttribute('stroke-width', '2'); }
    }
    selectedConnectorId = id;
    if (id !== null) {
      const line = linesById[id];
      if (line) { line.setAttribute('stroke', '#4a90e2'); line.setAttribute('stroke-width', '3'); }
      window.__mc_showConnToolbar?.(id);
    } else {
      window.__mc_hideConnToolbar?.();
    }
  }

  // ---------- nudge / duplicate (Tier-1 keyboard helpers) ----------
  const _nudgeTimers = Object.create(null);
  function nudgeSelection(dx, dy) {
    if (!selectedIds.size) return;
    selectedIds.forEach(id => {
      const entry = itemsById[id]; if (!entry?.el || !entry.data) return;
      if (entry.data.locked) return; // locked items don't move
      const d = entry.data;
      d.x = (d.x | 0) + dx;
      d.y = (d.y | 0) + dy;
      entry.el.style.left = `${d.x}px`;
      entry.el.style.top  = `${d.y}px`;
      refreshAttachedConnectors(id);
      // Debounce the PATCH per item so holding the key doesn't flood the network
      clearTimeout(_nudgeTimers[id]);
      _nudgeTimers[id] = setTimeout(() => {
        apiPATCH(`${apiBase}/${id}`, { x: d.x, y: d.y }).catch(console.warn);
      }, 200);
    });
  }

  async function duplicateSelection() {
    if (!selectedIds.size) return;
    const sourceIds = Array.from(selectedIds);
    const newIds = [];
    for (const id of sourceIds) {
      const src = itemsById[id]?.data; if (!src || src.kind === 'connector') continue;
      if (src.locked) continue; // locked items don't duplicate
      const body = {
        kind: src.kind,
        x: snap((src.x | 0) + 20),
        y: snap((src.y | 0) + 20),
        w: src.w, h: src.h,
        payload: src.payload ? JSON.parse(JSON.stringify(src.payload)) : null,
      };
      const r = await apiPOST(`${apiBase}/create`, body);
      const item = r?.data && r.data.id ? r.data : { id: Date.now() + Math.random(), ...body };
      renderItem(item);
      newIds.push(Number(item.id));
    }
    if (newIds.length) {
      clearSelection();
      newIds.forEach(addToSelection);
    }
  }

  // ---------- z-order ----------
  function _itemListByZ() {
    const arr = [];
    for (const id in itemsById) {
      const d = itemsById[id]?.data;
      if (!d || d.kind === 'connector') continue;
      arr.push({ id: Number(id), z: clampInt(d.z) });
    }
    // Stable order: by z then by id, so ties resolve consistently
    arr.sort((a, b) => a.z - b.z || a.id - b.id);
    return arr;
  }
  async function setItemZ(id, z) {
    const entry = itemsById[id]; if (!entry?.el || !entry.data) return;
    entry.data.z = z;
    entry.el.style.zIndex = String(z);
    await apiPATCH(`${apiBase}/${id}`, { z }).catch(console.warn);
  }
  // Move above the immediate-next item (or do nothing if already on top)
  async function zBringForward(id) {
    const list = _itemListByZ();
    const idx = list.findIndex(x => x.id === id);
    if (idx === -1 || idx === list.length - 1) return;
    const above = list[idx + 1];
    await setItemZ(id, above.z + 1);
  }
  // Move below the immediate-previous item (or do nothing if already on bottom)
  async function zSendBack(id) {
    const list = _itemListByZ();
    const idx = list.findIndex(x => x.id === id);
    if (idx <= 0) return;
    const below = list[idx - 1];
    await setItemZ(id, below.z - 1);
  }
  async function zBringToFront(id) {
    const list = _itemListByZ();
    const top = list[list.length - 1];
    if (!top || top.id === id) return;
    await setItemZ(id, top.z + 1);
  }
  async function zSendToBack(id) {
    const list = _itemListByZ();
    const bot = list[0];
    if (!bot || bot.id === id) return;
    await setItemZ(id, bot.z - 1);
  }

  // ---------- lock ----------
  async function toggleLock(id) {
    const entry = itemsById[id]; if (!entry?.el || !entry.data) return;
    const next = !entry.data.locked;
    entry.data.locked = next;
    entry.el.classList.toggle('locked', next);
    await apiPATCH(`${apiBase}/${id}`, { locked: next ? 1 : 0 }).catch(console.warn);
  }

  // ---------- connector style + label ----------
  const CONNECTOR_ARROWS = ['none', 'end', 'start', 'both'];
  function _applyConnectorStyle(id) {
    const line = linesById[id]; if (!line) return;
    const it = itemsById[id]?.data; if (!it) return;
    // Dashed
    const dashed = !!it.payload?.dashed;
    if (dashed) line.setAttribute('stroke-dasharray', '6 4');
    else line.removeAttribute('stroke-dasharray');
    // Arrow heads (default: 'end' for backwards compat)
    let arrows = it.payload?.arrows;
    if (!CONNECTOR_ARROWS.includes(arrows)) arrows = 'end';
    if (arrows === 'end' || arrows === 'both') line.setAttribute('marker-end', 'url(#arrowEnd)');
    else line.removeAttribute('marker-end');
    if (arrows === 'start' || arrows === 'both') line.setAttribute('marker-start', 'url(#arrowEnd)');
    else line.removeAttribute('marker-start');
  }
  async function setConnectorDashed(id, dashed) {
    const it = itemsById[id]?.data; if (!it) return;
    it.payload = it.payload || {};
    it.payload.dashed = !!dashed;
    _applyConnectorStyle(id);
    await apiPATCH(`${apiBase}/${id}`, { payload: { dashed: !!dashed } }).catch(console.warn);
  }
  async function setConnectorArrows(id, arrows) {
    if (!CONNECTOR_ARROWS.includes(arrows)) arrows = 'end';
    const it = itemsById[id]?.data; if (!it) return;
    it.payload = it.payload || {};
    it.payload.arrows = arrows;
    _applyConnectorStyle(id);
    await apiPATCH(`${apiBase}/${id}`, { payload: { arrows } }).catch(console.warn);
  }
  async function reverseConnector(id) {
    const it = itemsById[id]?.data; if (!it) return;
    const p = it.payload || {};
    if (!p.a || !p.b) return;
    const a = p.a, b = p.b;
    it.payload = { ...p, a: b, b: a };
    // The visible line cached _payload too
    const line = linesById[id]; if (line) line._payload = it.payload;
    // Re-wire connectorsByItem mapping
    const aId = a.item, bId = b.item;
    if (aId) connectorsByItem[aId]?.delete(id);
    if (bId) connectorsByItem[bId]?.delete(id);
    if (b.item) ensureSet(connectorsByItem, b.item).add(id);
    if (a.item) ensureSet(connectorsByItem, a.item).add(id);
    updateConnectorLine(id);
    await apiPATCH(`${apiBase}/${id}`, { payload: { a: b, b: a } }).catch(console.warn);
  }
  function _renderConnectorLabel(id) {
    const it = itemsById[id]?.data; if (!it) return;
    const line = linesById[id]; if (!line) return;
    const text = (it.payload?.label || '').trim();

    // No label → remove any existing group and bail.
    if (!text) {
      if (line._labelGroup?.parentNode) line._labelGroup.parentNode.removeChild(line._labelGroup);
      line._labelGroup = null;
      line._labelEl    = null;
      line._labelRect  = null;
      return;
    }

    const NS = 'http://www.w3.org/2000/svg';

    // Lazy-create the group {rect, text} the first time
    if (!line._labelGroup) {
      const group = document.createElementNS(NS, 'g');
      group.style.pointerEvents = 'none';

      const rect = document.createElementNS(NS, 'rect');
      rect.setAttribute('rx', '6');
      rect.setAttribute('ry', '6');
      rect.setAttribute('fill', '#1a1d24');
      rect.setAttribute('stroke', '#3a76d2');
      rect.setAttribute('stroke-width', '1');

      const label = document.createElementNS(NS, 'text');
      label.setAttribute('text-anchor', 'middle');
      label.setAttribute('dominant-baseline', 'middle');
      label.setAttribute('fill', '#ddd');
      label.setAttribute('font-size', '12');
      label.setAttribute('font-family', 'system-ui, -apple-system, sans-serif');

      group.appendChild(rect);
      group.appendChild(label);
      svgBack.appendChild(group);

      line._labelGroup = group;
      line._labelRect  = rect;
      line._labelEl    = label;
    }

    const label = line._labelEl;
    const rect  = line._labelRect;

    const x1 = +line.getAttribute('x1') || 0;
    const y1 = +line.getAttribute('y1') || 0;
    const x2 = +line.getAttribute('x2') || 0;
    const y2 = +line.getAttribute('y2') || 0;
    const mx = (x1 + x2) / 2;
    const my = (y1 + y2) / 2;

    label.setAttribute('x', String(mx));
    label.setAttribute('y', String(my));
    label.textContent = text;

    // Measure the text once it's in the DOM and size the rect around it.
    try {
      const bbox = label.getBBox();
      const padX = 5, padY = 1;
      rect.setAttribute('x',      String(bbox.x - padX));
      rect.setAttribute('y',      String(bbox.y - padY));
      rect.setAttribute('width',  String(bbox.width + padX * 2));
      rect.setAttribute('height', String(bbox.height + padY * 2));
      rect.setAttribute('rx',     '4');
      rect.setAttribute('ry',     '4');
    } catch (e) { /* getBBox fails if the SVG isn't laid out yet — retry on next frame */ }
  }
  async function setConnectorLabel(id, label) {
    const it = itemsById[id]?.data; if (!it) return;
    it.payload = it.payload || {};
    it.payload.label = label || '';
    _renderConnectorLabel(id);
    await apiPATCH(`${apiBase}/${id}`, { payload: { label: label || '' } }).catch(console.warn);
  }

  // ---------- right-click context menu ----------
  const ctxMenu = document.createElement('div');
  ctxMenu.id = 'canvasCtxMenu';
  ctxMenu.hidden = true;
  document.body.appendChild(ctxMenu);

  // Inject menu styles once
  (function injectCtxStyles(){
    if (document.getElementById('canvasCtxMenuStyles')) return;
    const s = document.createElement('style'); s.id = 'canvasCtxMenuStyles';
    s.textContent = `
      #canvasCtxMenu {
        position:fixed; min-width:180px;
        background:#1a1d24; border:1px solid #2b3346; border-radius:8px;
        box-shadow:0 8px 24px rgba(0,0,0,.4);
        z-index:9999; padding:.25rem; overflow:hidden;
        font: 13px/1.3 system-ui, sans-serif; color:#ddd;
      }
      #canvasCtxMenu button {
        display:flex; align-items:center; justify-content:space-between; gap:.6rem;
        width:100%; text-align:left; background:transparent; border:0;
        color:#ddd; padding:.4rem .6rem; border-radius:6px; cursor:pointer;
        font: inherit;
      }
      #canvasCtxMenu button:hover { background:#2a2f3a; }
      #canvasCtxMenu button.is-active { background:#1f3a66; color:#fff; }
      #canvasCtxMenu button kbd {
        opacity:.55; font-size:.85em; font-family:monospace;
      }
      #canvasCtxMenu .menu-sep { height:1px; background:#2b3346; margin:.25rem 0; }
      .canvas-item.locked { outline:1px dashed rgba(255,200,80,.5); }
      .canvas-item.locked::after {
        content:"🔒"; position:absolute; top:2px; right:4px;
        font-size:11px; opacity:.85; pointer-events:none;
      }
    `;
    document.head.appendChild(s);
  })();

  function closeCtxMenu() { ctxMenu.hidden = true; ctxMenu.innerHTML = ''; }
  function openCtxMenu(clientX, clientY, html, handler) {
    ctxMenu.innerHTML = html;
    ctxMenu.hidden = false;
    // Position with viewport coords; keep on-screen
    const r = ctxMenu.getBoundingClientRect();
    let x = clientX, y = clientY;
    if (x + r.width > window.innerWidth - 8) x = window.innerWidth - r.width - 8;
    if (y + r.height > window.innerHeight - 8) y = window.innerHeight - r.height - 8;
    ctxMenu.style.left = `${x}px`;
    ctxMenu.style.top  = `${y}px`;
    ctxMenu._handler = handler;
  }

  document.addEventListener('click', (e) => {
    if (!ctxMenu.hidden && !ctxMenu.contains(e.target)) closeCtxMenu();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCtxMenu(); });
  ['scroll','resize'].forEach(ev => window.addEventListener(ev, closeCtxMenu, { passive:true }));

  ctxMenu.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-cmd]');
    if (!btn) return;
    const cmd = btn.dataset.cmd;
    const h = ctxMenu._handler;
    closeCtxMenu();
    if (h) h(cmd);
  });

  // ---------- frame image trim mode (drag to reposition object-position) ----------
  let trimming = null; // { id, el, img, startX, startY, startPos, ovX, ovY }

  // CSS for trim state
  (function injectTrimStyles(){
    if (document.getElementById('canvasTrimStyles')) return;
    const s = document.createElement('style'); s.id = 'canvasTrimStyles';
    s.textContent = `
      .canvas-item.is-trimming { outline: 2px dashed #3a76d2; outline-offset: -2px; cursor: grab; }
      .canvas-item.is-trimming .mc-media { pointer-events: auto; cursor: grab; }
      .canvas-item.is-trimming .mc-media.grabbing { cursor: grabbing; }
    `;
    document.head.appendChild(s);
  })();

  function _frameOverflow(itemEl) {
    const img = itemEl.querySelector('.mc-media img');
    if (!img) return null;
    const fw = itemEl.clientWidth, fh = itemEl.clientHeight;
    const nw = img.naturalWidth || 0, nh = img.naturalHeight || 0;
    if (!fw || !fh || !nw || !nh) return null;
    const fAsp = fw / fh, iAsp = nw / nh;
    let renderedW, renderedH;
    if (iAsp > fAsp) { renderedH = fh; renderedW = fh * iAsp; }
    else             { renderedW = fw; renderedH = fw / iAsp; }
    return { ovX: Math.max(0, renderedW - fw), ovY: Math.max(0, renderedH - fh) };
  }

  function enterTrim(id) {
    const entry = itemsById[id]; if (!entry?.el || entry.data?.kind !== 'frame') return;
    if (!entry.data.media && !entry.data.payload?.media) return;
    exitTrim();
    entry.el.classList.add('is-trimming');
    trimming = { id, el: entry.el };
  }
  function exitTrim() {
    if (!trimming) return;
    trimming.el.classList.remove('is-trimming');
    const mediaEl = trimming.el.querySelector('.mc-media');
    if (mediaEl) mediaEl.classList.remove('grabbing');
    trimming = null;
  }

  function _onTrimStart(clientX, clientY) {
    if (!trimming) return false;
    const entry = itemsById[trimming.id]; if (!entry?.data) return false;
    const ov = _frameOverflow(trimming.el);
    if (!ov) return false;
    const pos = entry.data.payload?.image_pos || { x: 50, y: 50 };
    trimming.startX = clientX;
    trimming.startY = clientY;
    trimming.startPos = { x: pos.x, y: pos.y };
    trimming.ovX = ov.ovX;
    trimming.ovY = ov.ovY;
    const m = trimming.el.querySelector('.mc-media');
    if (m) m.classList.add('grabbing');
    return true;
  }
  function _onTrimMove(clientX, clientY) {
    if (!trimming || trimming.startPos == null) return;
    // Drag distance in screen pixels → image pan in image pixels (accounting for canvas zoom)
    const dx = (clientX - trimming.startX) / stageScale;
    const dy = (clientY - trimming.startY) / stageScale;
    const ovX = trimming.ovX, ovY = trimming.ovY;
    // Dragging right reveals more of the LEFT side of the image → position decreases
    const dPctX = ovX > 0 ? (dx / ovX) * 100 : 0;
    const dPctY = ovY > 0 ? (dy / ovY) * 100 : 0;
    const nx = Math.max(0, Math.min(100, trimming.startPos.x - dPctX));
    const ny = Math.max(0, Math.min(100, trimming.startPos.y - dPctY));
    const img = trimming.el.querySelector('.mc-media img');
    if (img) img.style.objectPosition = `${nx}% ${ny}%`;
    trimming._currentPos = { x: nx, y: ny };
  }
  async function _onTrimEnd() {
    if (!trimming) return;
    const id = trimming.id;
    const pos = trimming._currentPos;
    const m = trimming.el.querySelector('.mc-media');
    if (m) m.classList.remove('grabbing');
    trimming.startPos = null;
    if (pos) {
      const entry = itemsById[id];
      if (entry?.data) {
        entry.data.payload = entry.data.payload || {};
        entry.data.payload.image_pos = pos;
      }
      await apiPATCH(`${apiBase}/${id}`, { payload: { image_pos: pos } }).catch(console.warn);
    }
  }

  // Double-click on a frame with media → enter trim mode
  // Single click on a frame → if in trim and clicked outside, exit
  content.addEventListener('dblclick', (e) => {
    const itemEl = getItemFromTarget(e.target); if (!itemEl) return;
    if (itemEl.dataset.kind !== 'frame') return;
    const id = Number(itemEl.dataset.id);
    const d = itemsById[id]?.data;
    if (!d?.media && !d?.payload?.media) return;
    e.preventDefault(); e.stopPropagation();
    enterTrim(id);
  });

  // Trim drag handlers — capture on the document so drag works edge-to-edge
  stage.addEventListener('mousedown', (e) => {
    if (!trimming) return;
    const itemEl = getItemFromTarget(e.target);
    if (!itemEl || itemEl !== trimming.el) { exitTrim(); return; }
    e.preventDefault(); e.stopPropagation();
    if (_onTrimStart(e.clientX, e.clientY)) {
      document.addEventListener('mousemove', _trimMoveDoc, true);
      document.addEventListener('mouseup',   _trimUpDoc,   true);
    }
  }, true);
  function _trimMoveDoc(e) { _onTrimMove(e.clientX, e.clientY); }
  function _trimUpDoc(e) {
    document.removeEventListener('mousemove', _trimMoveDoc, true);
    document.removeEventListener('mouseup',   _trimUpDoc,   true);
    _onTrimEnd();
  }
  // Touch
  stage.addEventListener('touchstart', (e) => {
    if (!trimming) return;
    if (e.touches.length !== 1) return;
    const t = e.touches[0];
    const itemEl = getItemFromTarget(document.elementFromPoint(t.clientX, t.clientY));
    if (!itemEl || itemEl !== trimming.el) { exitTrim(); return; }
    if (_onTrimStart(t.clientX, t.clientY)) {
      e.preventDefault();
      document.addEventListener('touchmove', _trimTouchMove, { passive:false });
      document.addEventListener('touchend',  _trimTouchEnd,  { passive:true });
    }
  }, { passive:false });
  function _trimTouchMove(e) {
    if (e.touches.length !== 1) return;
    e.preventDefault();
    _onTrimMove(e.touches[0].clientX, e.touches[0].clientY);
  }
  function _trimTouchEnd() {
    document.removeEventListener('touchmove', _trimTouchMove);
    document.removeEventListener('touchend',  _trimTouchEnd);
    _onTrimEnd();
  }
  // Esc exits trim
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && trimming) { e.stopPropagation(); exitTrim(); }
  }, true);

  // ---------- floating connector toolbar (touch-first) ----------
  const connToolbar = document.createElement('div');
  connToolbar.id = 'canvasConnToolbar';
  connToolbar.hidden = true;
  document.body.appendChild(connToolbar);

  (function injectConnToolbarStyles(){
    if (document.getElementById('canvasConnToolbarStyles')) return;
    const s = document.createElement('style'); s.id = 'canvasConnToolbarStyles';
    s.textContent = `
      #canvasConnToolbar {
        position:fixed; z-index:9998;
        display:flex; flex-wrap:wrap; align-items:center; gap:.25rem;
        background:#1a1d24; border:1px solid #3a76d2; border-radius:10px;
        box-shadow:0 10px 28px rgba(0,0,0,.5), 0 0 0 3px rgba(58,118,210,.15);
        padding:.35rem; transform:translate(-50%, 0);
        max-width:96vw;
        animation: connToolbarPop .12s ease-out;
      }
      @keyframes connToolbarPop {
        from { opacity:0; transform:translate(-50%, -4px); }
        to   { opacity:1; transform:translate(-50%, 0); }
      }
      #canvasConnToolbar button {
        min-width:40px; height:40px; padding:0 .35rem;
        background:transparent; border:1px solid transparent; border-radius:6px;
        color:#ddd; font: 14px/1 system-ui, sans-serif; cursor:pointer;
        display:inline-flex; align-items:center; justify-content:center;
      }
      #canvasConnToolbar button:hover  { background:#2a2f3a; }
      #canvasConnToolbar button.is-active {
        background:#1f3a66; border-color:#3a76d2; color:#fff;
      }
      #canvasConnToolbar .sep {
        width:1px; align-self:stretch; background:#2b3346; margin:.1rem .1rem;
      }
      #canvasConnToolbar .danger {
        color:#fff; background:#7a2030; border-color:#a01a36;
      }
      #canvasConnToolbar .danger:hover { background:#a01a36; }
      #canvasConnToolbar .close {
        width:32px; min-width:32px; height:32px;
        border-radius:50%; padding:0;
        background:rgba(255,255,255,.06); color:#aaa;
        font-size:13px;
      }
      #canvasConnToolbar .close:hover { background:rgba(255,255,255,.12); color:#fff; }
    `;
    document.head.appendChild(s);
  })();

  function hideConnToolbar() { connToolbar.hidden = true; }

  function renderConnToolbar(id) {
    const d = itemsById[id]?.data; if (!d) return;
    const arrows = (CONNECTOR_ARROWS.includes(d.payload?.arrows) ? d.payload.arrows : 'end');
    const dashed = !!d.payload?.dashed;
    const cls = (a) => arrows === a ? 'is-active' : '';
    const dcls = (b) => b ? 'is-active' : '';
    connToolbar.innerHTML = `
      <button data-cmd="close" class="close" title="Close">✕</button>
      <span class="sep"></span>
      <button data-cmd="arrows-none"  class="${cls('none')}"  title="No arrow">—</button>
      <button data-cmd="arrows-end"   class="${cls('end')}"   title="Arrow at end (one way)">→</button>
      <button data-cmd="arrows-start" class="${cls('start')}" title="Arrow at start (other way)">←</button>
      <button data-cmd="arrows-both"  class="${cls('both')}"  title="Both ends (bidirectional)">↔</button>
      <span class="sep"></span>
      <button data-cmd="solid"  class="${dcls(!dashed)}" title="Solid line">──</button>
      <button data-cmd="dashed" class="${dcls(dashed)}"  title="Dashed line">- -</button>
      <span class="sep"></span>
      <button data-cmd="reverse" title="Swap from / to">⇄</button>
      <button data-cmd="label" title="Edit label">A</button>
      <button data-cmd="delete" class="danger" title="Delete connector"
              style="min-width:auto;padding:0 .7rem;">× Delete</button>
    `;
  }

  function positionConnToolbar(id) {
    const line = linesById[id]; if (!line) return;
    // Midpoint in logical coords → viewport coords
    const x1 = +line.getAttribute('x1') || 0;
    const y1 = +line.getAttribute('y1') || 0;
    const x2 = +line.getAttribute('x2') || 0;
    const y2 = +line.getAttribute('y2') || 0;
    const mx = (x1 + x2) / 2;
    const my = (y1 + y2) / 2;
    const rect = stage.getBoundingClientRect();
    const cx = rect.left + mx * stageScale + stageOffset.x;
    const cy = rect.top  + my * stageScale + stageOffset.y;

    // Show first so we can measure
    connToolbar.hidden = false;
    const tw = connToolbar.offsetWidth || 360;
    const th = connToolbar.offsetHeight || 48;

    // Offset PERPENDICULAR to the line direction so the toolbar never sits
    // ON TOP of the items the connector joins. Horizontal-ish line → toolbar
    // goes below the midpoint. Vertical-ish line → toolbar goes to the right
    // (or left if there isn't room).
    const dx = x2 - x1, dy = y2 - y1;
    const horizontal = Math.abs(dx) >= Math.abs(dy);
    const gap = 30;
    let left, top;
    if (horizontal) {
      left = cx;
      top  = cy + gap;                     // below
      if (top + th > window.innerHeight - 8) top = cy - gap - th; // flip above
    } else {
      // Vertical-ish: side-offset
      left = cx + gap + tw / 2;            // right of midpoint
      top  = cy - th / 2;
      if (left + tw / 2 > window.innerWidth - 8) {
        left = cx - gap - tw / 2;          // flip to left
      }
    }

    // Clamp inside viewport (translateX(-50%) is applied via CSS)
    const half = tw / 2;
    if (left - half < 8)                      left = 8 + half;
    if (left + half > window.innerWidth - 8)  left = window.innerWidth - 8 - half;
    if (top < 8)                              top  = 8;
    if (top + th > window.innerHeight - 8)    top  = window.innerHeight - 8 - th;
    connToolbar.style.left = `${left}px`;
    connToolbar.style.top  = `${top}px`;
  }

  function showConnToolbar(id) {
    renderConnToolbar(id);
    positionConnToolbar(id);
    connToolbar._connId = id;
  }

  connToolbar.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-cmd]');
    if (!btn) return;
    e.stopPropagation();
    const cmd = btn.dataset.cmd;
    // Close needs to work whether or not a connector is currently bound
    if (cmd === 'close') {
      hideConnToolbar();
      selectConnector(null);
      return;
    }
    const id = connToolbar._connId; if (!id) return;
    if (cmd === 'arrows-none')  await setConnectorArrows(id, 'none');
    else if (cmd === 'arrows-end')   await setConnectorArrows(id, 'end');
    else if (cmd === 'arrows-start') await setConnectorArrows(id, 'start');
    else if (cmd === 'arrows-both')  await setConnectorArrows(id, 'both');
    else if (cmd === 'solid')   await setConnectorDashed(id, false);
    else if (cmd === 'dashed')  await setConnectorDashed(id, true);
    else if (cmd === 'reverse') await reverseConnector(id);
    else if (cmd === 'label') {
      const current = itemsById[id]?.data?.payload?.label || '';
      const next = prompt('Connector label (leave empty to remove):', current);
      if (next !== null) await setConnectorLabel(id, next.trim());
    }
    else if (cmd === 'delete') {
      await deleteConnector(id);
      hideConnToolbar();
      return;
    }
    // Refresh active-state on the toolbar
    renderConnToolbar(id);
    positionConnToolbar(id);
  });

  // Close toolbar on outside interactions / view changes.
  // Don't fight the connector's own hit-line click — those clicks reselect.
  function _isConnectorHit(target) {
    return target?.tagName?.toLowerCase?.() === 'line'
      && target.getAttribute?.('pointer-events') === 'stroke';
  }
  document.addEventListener('mousedown', (e) => {
    if (connToolbar.hidden) return;
    if (connToolbar.contains(e.target)) return;
    if (_isConnectorHit(e.target)) return;
    hideConnToolbar();
    selectConnector(null);
  });
  document.addEventListener('touchstart', (e) => {
    if (connToolbar.hidden) return;
    if (connToolbar.contains(e.target)) return;
    if (_isConnectorHit(e.target)) return;
    hideConnToolbar();
    selectConnector(null);
  }, { passive:true });
  ['scroll','resize'].forEach(ev => window.addEventListener(ev, () => {
    if (connToolbar._connId) positionConnToolbar(connToolbar._connId);
  }, { passive:true }));

  // Expose so selectConnector / updateConnectorLine / applyTransforms can drive the toolbar
  window.__mc_showConnToolbar = showConnToolbar;
  window.__mc_hideConnToolbar = hideConnToolbar;
  window.__mc_repositionConnToolbar = (id) => {
    if (connToolbar.hidden || connToolbar._connId !== id) return;
    positionConnToolbar(id);
  };

  // Right-click handler — covers items, connectors, and empty space
  stage.addEventListener('contextmenu', (e) => {
    // Allow native menu inside text-editing fields
    if (isTypingTarget(e.target)) return;
    e.preventDefault();

    const itemEl = getItemFromTarget(e.target);
    const hitConnector = e.target.tagName === 'line' && e.target.getAttribute('pointer-events') === 'stroke';

    if (itemEl) {
      const id = Number(itemEl.dataset.id);
      // If clicked item isn't in the multi-selection, single-select it
      if (!selectedIds.has(id)) setSingleSelection(id);
      const d = itemsById[id]?.data;

      // Locked: only show the unlock action — everything else is blocked
      if (d?.locked) {
        const html = `<button data-cmd="lock">🔓 Unlock</button>`;
        openCtxMenu(e.clientX, e.clientY, html, async () => toggleLock(id));
        return;
      }

      const html = `
        <button data-cmd="duplicate">Duplicate <kbd>Ctrl+D</kbd></button>
        <div class="menu-sep"></div>
        <button data-cmd="forward">Bring forward</button>
        <button data-cmd="back">Send back</button>
        <button data-cmd="front">Bring to front</button>
        <button data-cmd="bottom">Send to back</button>
        <div class="menu-sep"></div>
        <button data-cmd="lock">🔒 Lock</button>
        <div class="menu-sep"></div>
        <button data-cmd="delete">Delete <kbd>Del</kbd></button>
      `;
      openCtxMenu(e.clientX, e.clientY, html, async (cmd) => {
        // Only operate on the unlocked subset of the current selection
        const ids = Array.from(selectedIds).filter(sid => !itemsById[sid]?.data?.locked);
        switch (cmd) {
          case 'duplicate': return duplicateSelection();
          case 'forward':   return Promise.all(ids.map(zBringForward));
          case 'back':      return Promise.all(ids.map(zSendBack));
          case 'front':     return Promise.all(ids.map(zBringToFront));
          case 'bottom':    return Promise.all(ids.map(zSendToBack));
          case 'lock':      return Promise.all(Array.from(selectedIds).map(toggleLock));
          case 'delete':    return Promise.all(ids.map(deleteItem));
        }
      });
      return;
    }

    if (hitConnector) {
      const cid = Number(e.target.parentNode?.querySelector?.('line[stroke-width="2"]')?.dataset?.id
                       || e.target.dataset?.id
                       || selectedConnectorId);
      // Find the visible line that owns this hit — by walking siblings
      const visibleLine = (function(){
        for (const id in linesById) {
          if (linesById[id]._hitLine === e.target) return Number(id);
        }
        return null;
      })();
      const targetId = visibleLine ?? cid;
      if (!targetId) return;
      selectConnector(targetId);
      const d = itemsById[targetId]?.data;
      const dashed = !!d?.payload?.dashed;
      const html = `
        <button data-cmd="style-solid"  ${!dashed ? 'class="is-active"' : ''}>Solid line</button>
        <button data-cmd="style-dashed" ${ dashed ? 'class="is-active"' : ''}>Dashed line</button>
        <div class="menu-sep"></div>
        <button data-cmd="label">Edit label…</button>
        <div class="menu-sep"></div>
        <button data-cmd="delete">Delete <kbd>Del</kbd></button>
      `;
      openCtxMenu(e.clientX, e.clientY, html, async (cmd) => {
        switch (cmd) {
          case 'style-solid':  return setConnectorDashed(targetId, false);
          case 'style-dashed': return setConnectorDashed(targetId, true);
          case 'label': {
            const current = d?.payload?.label || '';
            const next = prompt('Connector label (leave empty to remove):', current);
            if (next === null) return;
            return setConnectorLabel(targetId, next.trim());
          }
          case 'delete': return deleteConnector(targetId);
        }
      });
      return;
    }

    // Empty canvas: simple "select all"
    const html = `
      <button data-cmd="select-all">Select all <kbd>Ctrl+A</kbd></button>
    `;
    openCtxMenu(e.clientX, e.clientY, html, (cmd) => {
      if (cmd === 'select-all') {
        clearSelection();
        for (const id in itemsById) {
          const d = itemsById[id]?.data;
          if (d && d.kind !== 'connector') addToSelection(Number(id));
        }
      }
    });
  });

  // ---------- toolbar ----------
  (toolbar || document).addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]'); if (!btn) return;
    const action = btn.dataset.action;
    if (action === 'snap') {
      snapToGrid = !snapToGrid;
      btn.classList.toggle('active', snapToGrid);
      (document.getElementById('canvasStage')||stage).classList.toggle('snap-on', snapToGrid);
      if (toolPill) toolPill.textContent = `Tool: ${currentTool}${snapToGrid ? ' • Snap' : ''}`;
      return;
    }
    if (action === 'zoom-in')  { stageScale = Math.min(2, stageScale + 0.1); applyTransforms(); return; }
    if (action === 'zoom-out') { stageScale = Math.max(0.4, stageScale - 0.1); applyTransforms(); return; }
    if (action === 'reset-view'){ stageScale = 1; stageOffset = {x:0,y:0}; applyTransforms(); return; }
    setActiveToolButton(action);
  }, true);

  // ---------- pointer wiring ----------
  function handleDown(e) {
    const itemEl = getItemFromTarget(e.target);
    const isItem = !!itemEl;
    const empty  = isEmptySpace(e.target);

    // pan
    if (currentTool === 'pan') { isPanning = true; panStart.x = e.clientX; panStart.y = e.clientY; return; }

    // resize: click to show handles, then drag handles or center move
    if (currentTool === 'resize' && isItem) { setSingleSelection(Number(itemEl.dataset.id)); return; }

    // select
    if (currentTool === 'select') {
      if (empty) {
        selectConnector(null);
        // shift+click on empty preserves selection; plain click clears via marquee
        return beginMarquee(e.clientX, e.clientY, e.shiftKey);
      }
      if (isItem && !e.target.classList.contains('resize-handle')) {
        const id = Number(itemEl.dataset.id);
        if (e.shiftKey) {
          // toggle membership; do not start drag
          if (selectedIds.has(id)) {
            selectedIds.delete(id);
            updateSelectionUI();
            if (window.moodCanvasBus) window.moodCanvasBus.emit('selection:changed', { items: new Set(selectedIds) });
          } else {
            addToSelection(id);
          }
          return;
        }
        return startGroupDragFromItem(itemEl, e.clientX, e.clientY);
      }
      return;
    }

    // create
    if ((currentTool === 'text' || currentTool === 'frame') && empty) {
      const p = logicalFromClient(e.clientX, e.clientY);
      return void (currentTool === 'text' ? createNoteAt(p.x, p.y) : createFrameAt(p.x, p.y));
    }

    // delete
    if (currentTool === 'delete' && isItem) { return void deleteItem(Number(itemEl.dataset.id)); }

    // connector: drag from item A to B
    if (currentTool === 'connector' && isItem) {
      const fromId = Number(itemEl.dataset.id);
      if (itemsById[fromId]?.data?.locked) return; // can't connect from a locked item
      e.preventDefault(); e.stopPropagation();
      beginConnectDrag(fromId, e.clientX, e.clientY);
      return;
    }
  }

  stage.addEventListener('mousedown', handleDown);
  //content.addEventListener('mousedown', handleDown);

  document.addEventListener('mousemove', (e) => {
    if (isPanning) {
      const dx = e.clientX - panStart.x, dy = e.clientY - panStart.y;
      stageOffset.x += dx; stageOffset.y += dy;
      panStart.x = e.clientX; panStart.y = e.clientY;
      applyTransforms(); return;
    }
    if (marquee) updateMarquee(e.clientX, e.clientY);
  });
  document.addEventListener('mouseup', () => {
    if (isPanning) isPanning = false;
    if (marquee) endMarquee();
  });

  document.addEventListener('keydown', (e) => {
	// ---- plugin keybindings (v1 owns the single keydown listener) ----
	// don’t trigger shortcuts while typing
  	if (window.__mc_isEditingText || isTypingTarget(e.target)) return;

	 // custom keybindings
	  (function(){
		const combo = [
		  (e.ctrlKey||e.metaKey) ? 'ctrl' : '',
		  e.shiftKey ? 'shift' : '',
		  e.altKey ? 'alt' : '',
		  (e.key||'').toLowerCase()
		].filter(Boolean).join('+');
		const fn = window.__mc_customKeyBindings && window.__mc_customKeyBindings.get(combo);
		if (fn) { e.preventDefault(); try { fn(e); } catch(err){ console.error(err); } return; }
	  })();

    // Delete/Backspace — remove selected items and/or selected connector
    if (e.key === 'Delete' || e.key === 'Backspace') {
      e.preventDefault();
      if (selectedIds.size > 0) Array.from(selectedIds).forEach(id => deleteItem(id));
      if (selectedConnectorId !== null) deleteConnector(selectedConnectorId);
      return;
    }

    // Escape — cancel tool first, then clear selection
    if (e.key === 'Escape') {
      e.preventDefault();
      if (currentTool !== 'select') { setActiveToolButton('select'); return; }
      if (selectedIds.size > 0)     { clearSelection(); return; }
      if (selectedConnectorId !== null) selectConnector(null);
      return;
    }

    // Ctrl/Cmd+A — select all non-connector items
    if ((e.ctrlKey || e.metaKey) && (e.key === 'a' || e.key === 'A')) {
      e.preventDefault();
      clearSelection();
      for (const id in itemsById) {
        const d = itemsById[id]?.data;
        if (d && d.kind !== 'connector') addToSelection(Number(id));
      }
      return;
    }

    // Ctrl/Cmd+D — duplicate current selection
    if ((e.ctrlKey || e.metaKey) && (e.key === 'd' || e.key === 'D')) {
      e.preventDefault();
      duplicateSelection();
      return;
    }

    // Arrow keys — nudge selected items (Shift = 10px steps)
    if (selectedIds.size && ['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) {
      e.preventDefault();
      const step = e.shiftKey ? 10 : 1;
      const dx = e.key === 'ArrowLeft' ? -step : e.key === 'ArrowRight' ? step : 0;
      const dy = e.key === 'ArrowUp'   ? -step : e.key === 'ArrowDown'  ? step : 0;
      nudgeSelection(dx, dy);
      return;
    }

    // Space — temporarily switch to pan tool while held
    if (e.key === ' ' && !e.repeat) {
      if (currentTool !== 'pan') {
        _spaceHeldPriorTool = currentTool;
        setActiveToolButton('pan');
        e.preventDefault();
      }
      return;
    }

    let action = null;
    if (e.key === 's') action = 'select';
    else if (e.key === 'p') action = 'pan';
    else if (e.key === 'r') action = 'resize';
    else if (e.key === 'c') action = 'connector';
    else if (e.key === 't') action = 'text';
    else if (e.key === 'f') action = 'frame';
    else if (e.key === 'd') action = 'delete';
    else if (e.key === '+') action = 'zoom-in';
    else if (e.key === '-') action = 'zoom-out';
    else if (e.key === '0') action = 'reset-view';
    else return;

    e.preventDefault();
    if (action === 'zoom-in' || action === 'zoom-out' || action === 'reset-view') {
      const control = (toolbar || document).querySelector(`[data-action="${action}"]`);
      if (control) control.click();
      else {
        if (action === 'zoom-in')  { stageScale = Math.min(2, stageScale + 0.1); applyTransforms(); }
        if (action === 'zoom-out') { stageScale = Math.max(0.4, stageScale - 0.1); applyTransforms(); }
        if (action === 'reset-view'){ stageScale = 1; stageOffset = {x:0,y:0}; applyTransforms(); }
      }
      return;
    }
    setActiveToolButton(action);
  }, true);

  // Restore previous tool when Space is released
  document.addEventListener('keyup', (e) => {
    if (e.key === ' ' && _spaceHeldPriorTool) {
      setActiveToolButton(_spaceHeldPriorTool);
      _spaceHeldPriorTool = null;
    }
  });

  // ---------- init ----------
  (async function init() {
    ensureOverlaySizing();

    // Probe both URL patterns; use whichever the server responds to
    for (const candidate of [
      `/api/moods/${slug}/canvas/items`,
      `/api/moods/${slug}/items`
    ]) {
      const probe = await apiGET(candidate);
      if (probe.ok) { apiBase = candidate; break; }
    }

    const r = await apiGET(apiBase);
    const items = Array.isArray(r.data) ? r.data : [];
    // items then connectors
    items.filter(i => i.kind !== 'connector').forEach(renderItem);
    items.filter(i => i.kind === 'connector').forEach(c => { itemsById[c.id] = { data:c }; renderConnector(c); });
    setActiveToolButton('select');
    applyTransforms();
  })();
})();


/* ============================
   Mood Canvas v1 – Public API + Bus
   ============================ */

// 1) Tiny global event bus
(function(){
  if (!window.moodCanvasBus) {
    const map = new Map();
    window.moodCanvasBus = {
      on(type, fn){ (map.get(type) || map.set(type, new Set()).get(type)).add(fn); return () => map.get(type)?.delete(fn); },
      off(type, fn){ map.get(type)?.delete(fn); },
      emit(type, payload){ map.get(type)?.forEach(fn => { try { fn(payload); } catch(e){ console.error('[moodCanvasBus]', e); } }); }
    };
  }
})();

// 2) Custom keybinding map
//const __mc_customKeyBindings = (typeof __mc_customKeyBindings !== 'undefined') ? __mc_customKeyBindings : new Map();
window.__mc_customKeyBindings = window.__mc_customKeyBindings || new Map();

// In your v1 keydown handler add:
//
// const combo = [
//   (e.ctrlKey||e.metaKey) ? 'ctrl' : '',
//   e.shiftKey ? 'shift' : '',
//   e.altKey ? 'alt' : '',
//   (e.key||'').toLowerCase()
// ].filter(Boolean).join('+');
// const fn = __mc_customKeyBindings.get(combo);
// if (fn) { e.preventDefault(); try { fn(e); } catch(err){} return; }

// 3) Public API surface (wrap your real v1 functions here)
window.moodCanvas = {
  getState(){
	  return {
		selectedItemIds: new Set(window.__moodCanvasSelection || []),
		selectedConnectorId: null,
		scale:  (window.__moodCanvasView && window.__moodCanvasView.scale)  || 1,
		offset: (window.__moodCanvasView && window.__moodCanvasView.offset) || { x:0, y:0 },
	  };
	},
  setTool: (t)=> (typeof setTool==='function'? setTool(t):null),
  zoomIn:  ()=> (typeof zoomIn==='function'? zoomIn():null),
  zoomOut: ()=> (typeof zoomOut==='function'? zoomOut():null),
  resetView:()=> (typeof resetView==='function'? resetView():null),

  addNoteAt:  (x,y)=> (typeof createNoteAt==='function'? createNoteAt(x,y):null),
  addFrameAt: (x,y)=> (typeof createFrameAt==='function'? createFrameAt(x,y):null),

  deleteConnector: (id)=> (typeof deleteConnector==='function'? deleteConnector(id):false),
  deleteItems:     (ids)=> (typeof deleteItems==='function'? deleteItems(ids):false),

  setItemStyle: (id, style) => (
	  typeof window.__mc_updateItem === 'function'
		? window.__mc_updateItem(id, { style })
		: false
	),
  setItemPayload: (id, payload) => (
  	typeof window.__mc_updateItem === 'function'
    	? window.__mc_updateItem(id, { payload })
    	: false
	),
	
  // pass full media object (id, uuid, mime_type, provider, provider_id, file_name)
	setItemMedia: (id, media) => (
	  typeof window.__mc_updateItem === 'function'
		? window.__mc_updateItem(id, { media })  // optimistic UI + PATCH
		: false
	),


  registerKeyBinding: (combo, fn)=>{ __mc_customKeyBindings.set(combo.toLowerCase(), fn); }
};

// 4) Announce ready
window.moodCanvasBus.emit('ready', { controller: window.moodCanvas });

if (window.moodCanvasBus && window.moodCanvas) {
  window.moodCanvasBus.emit('ready', { controller: window.moodCanvas });
}

