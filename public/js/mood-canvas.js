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
  const apiBase = `/api/moods/${slug}/canvas/items`;
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

  // Drag-to-connect state
  let connectDrag   = null;      // { fromId, tempLine, hoverId }

  // Data + element maps
  const itemsById   = Object.create(null); // id -> { data, el }
  const linesById   = Object.create(null); // connectorId -> <line>
  const connectorsByItem = Object.create(null); // itemId -> Set(connectorIds)

  // Marquee
  let marquee = null, marqueeStart = null;

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

  function ensureOverlaySizing() {
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

  }

  function centerFromData(d) {
    const cx = (Number(d.x)||0) + (Number(d.w)||0)/2;
    const cy = (Number(d.y)||0) + (Number(d.h)||0)/2;
    return { cx, cy };
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
	  el.className = `canvas-item kind-${item.kind}`;
	  el.dataset.id = String(item.id);
	  el.dataset.kind = item.kind;
	  el.style.cssText = [
		'position:absolute','box-sizing:border-box','user-select:none',
		`left:${clampInt(item.x)}px`, `top:${clampInt(item.y)}px`,
		`width:${Math.max(1, clampInt(item.w))}px`, `height:${Math.max(1, clampInt(item.h))}px`,
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
		title.textContent = (item.payload && item.payload.title) ? item.payload.title : 'Frame';
		title.style.cssText = 'font-weight:600;margin:4px 4px 6px 4px;font-size:14px;color:#000';
		el.appendChild(title);

		// Body becomes frame content area under the title
		body.style.top = '26px'; // leave space for title (adjust if you change title styles)
	  }

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
    const line = document.createElementNS('http://www.w3.org/2000/svg','line');
    line.dataset.id = String(item.id);
    line._payload = item.payload;
    line.setAttribute('stroke', '#888');
    line.setAttribute('stroke-width', '2');
    line.setAttribute('stroke-linecap', 'round');
    line.setAttribute('pointer-events', 'none');
    svgBack.appendChild(line); // permanent lines under items
    linesById[item.id] = line;

    const aId = item.payload?.a?.item, bId = item.payload?.b?.item;
    if (aId) ensureSet(connectorsByItem, aId).add(item.id);
    if (bId) ensureSet(connectorsByItem, bId).add(item.id);
    updateConnectorLine(item.id);
  }

  function updateConnectorLine(connectorId) {
    const line = linesById[connectorId]; if (!line) return;
    const p = line._payload;
    const a = p?.a?.item ? itemsById[p.a.item]?.data : null;
    const b = p?.b?.item ? itemsById[p.b.item]?.data : null;
    if (!a || !b) { ['x1','y1','x2','y2'].forEach(k => line.setAttribute(k, '-1000')); return; }
    const ca = centerFromData(a), cb = centerFromData(b);
    line.setAttribute('x1', String(ca.cx));
    line.setAttribute('y1', String(ca.cy));
    line.setAttribute('x2', String(cb.cx));
    line.setAttribute('y2', String(cb.cy));
  }

  function refreshAttachedConnectors(itemId) {
    const set = connectorsByItem[itemId]; if (!set) return;
    for (const cid of set) updateConnectorLine(cid);
  }
  function removeConnectorsAttachedTo(itemId) {
    const set = connectorsByItem[itemId]; if (!set) return;
    for (const cid of Array.from(set)) {
      const line = linesById[cid];
      if (line && line.parentNode) line.parentNode.removeChild(line);
      delete linesById[cid];
      Object.keys(connectorsByItem).forEach(k => connectorsByItem[k]?.delete(cid));
      delete itemsById[cid];
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
	  ta.focus(); ta.select();
	  
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
    if (toId && toId !== fromId) createConnectorBetween(fromId, toId);
  }
  function onConnectDragMove(e) { updateConnectDrag(e.clientX, e.clientY); }
  function onConnectDragUp(e) {
    document.removeEventListener('mousemove', onConnectDragMove, true);
    document.removeEventListener('mouseup', onConnectDragUp, true);
    endConnectDrag(e.clientX, e.clientY);
  }

  // ---------- group drag / marquee ----------
  function beginMarquee(clientX, clientY) {
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
    clearSelection();
    for (const id in itemsById) {
      const entry = itemsById[id]; if (!entry || !entry.el) continue;
      const d = entry.data;
      const inter = !(d.x + d.w < lx || d.y + d.h < ly || d.x > lx + lw || d.y > ly + lh);
      if (inter && d.kind !== 'connector') addToSelection(Number(id));
    }
  }
  function startGroupDragFromItem(itemEl, clientX, clientY) {
    const id = Number(itemEl.dataset.id);
    if (!selectedIds.has(id)) setSingleSelection(id);
    const mouse = logicalFromClient(clientX, clientY);
    draggingGroup = Array.from(selectedIds).map(sid => {
      const d = itemsById[sid].data;
      return { id:sid, dx: mouse.x - d.x, dy: mouse.y - d.y };
    });
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
    await apiDELETE(`${apiBase}/${id}/delete`).catch(console.warn);
    if (it.el?.parentNode) it.el.parentNode.removeChild(it.el);
    delete itemsById[id]; removeConnectorsAttachedTo(id);
    selectedIds.delete(id); updateSelectionUI();
  }

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
      if (empty) return beginMarquee(e.clientX, e.clientY);
      if (isItem && !e.target.classList.contains('resize-handle')) {
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
      e.preventDefault(); e.stopPropagation();
      const fromId = Number(itemEl.dataset.id);
      beginConnectDrag(fromId, e.clientX, e.clientY);
      return;
    }
  }

  stage.addEventListener('mousedown', handleDown);
  content.addEventListener('mousedown', handleDown);

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

  // ---------- init ----------
  (async function init() {
    ensureOverlaySizing();
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

  registerKeyBinding: (combo, fn)=>{ __mc_customKeyBindings.set(combo.toLowerCase(), fn); }
};

// 4) Announce ready
window.moodCanvasBus.emit('ready', { controller: window.moodCanvas });

if (window.moodCanvasBus && window.moodCanvas) {
  window.moodCanvasBus.emit('ready', { controller: window.moodCanvas });
}

