/* Mood Canvas – items + connectors via /canvas/items only (no /arrows) */
(function () {
  'use strict';

  // ---------- DOM ----------
  const stage   = document.getElementById('canvasStage');
  const content = document.getElementById('canvasContent');
  const svgEl   = document.getElementById('canvasOverlay');
  const toolbar = document.getElementById('canvas-toolbar') || document.querySelector('[data-canvas-toolbar]');
  const toolPill= document.getElementById('tool-pill');

  const slug    = window.boardSlug || '';
  const apiBase = `/api/moods/${slug}/canvas/items`;

  if (!stage || !content || !svgEl) {
    console.error('[canvas] missing DOM nodes'); return;
  }

  // Ensure back/front groups exist
  let svgBack  = document.getElementById('overlayBack');
  let svgFront = document.getElementById('overlayFront');
  if (!svgBack)  { svgBack  = document.createElementNS('http://www.w3.org/2000/svg','g'); svgBack.id  = 'overlayBack';  svgEl.appendChild(svgBack); }
  if (!svgFront) { svgFront = document.createElementNS('http://www.w3.org/2000/svg','g'); svgFront.id = 'overlayFront'; svgEl.appendChild(svgFront); }

  // Accept clicks on lines
  svgEl.style.pointerEvents = 'auto';
  svgBack.setAttribute('pointer-events', 'auto');
  svgFront.setAttribute('pointer-events', 'none');

  // ---------- state ----------
  let currentTool = 'select';
  let snapOn = false;
  let stageOffset = { x: 0, y: 0 };
  let stageScale  = 1;

  let isPanning   = false;
  let panStart    = { x: 0, y: 0 };

  let selectedIds = new Set();
  let selectedConnectorId = null; // single declaration

  // maps
  const itemsById   = Object.create(null); // id -> {data, el}
  const linesById   = Object.create(null); // connector id -> <line> (visible)
  const lineHitsById= Object.create(null); // connector id -> <line> (wide hit)
  const connectorsByItem = Object.create(null); // itemId -> Set(connectorIds)

  // marquee
  let marquee = null, marqueeStart = null;

  // ---------- utils ----------
  const snap = (n)=> snapOn ? Math.round(n/8)*8 : n;
  function logicalFromClient(cx, cy) {
    const r = stage.getBoundingClientRect();
    return { x:(cx - r.left - stageOffset.x)/stageScale, y:(cy - r.top - stageOffset.y)/stageScale };
  }
	
  function ensureOverlaySizing() {
    const w = stage.clientWidth || 1200, h = stage.clientHeight || 800;
    svgEl.setAttribute('width',  String(w));
    svgEl.setAttribute('height', String(h));
    if (!svgEl.getAttribute('viewBox')) {
      svgEl.setAttribute('viewBox', `0 0 ${w} ${h}`);
      svgEl.setAttribute('preserveAspectRatio', 'xMinYMin meet');
    }
  }
  window.addEventListener('resize', ensureOverlaySizing);

  function applyTransforms() {
    const tf = `translate(${stageOffset.x}px, ${stageOffset.y}px) scale(${stageScale})`;
    content.style.transform = tf;
    content.style.transformOrigin = '0 0';
    const tfSvg = `translate(${stageOffset.x}, ${stageOffset.y}) scale(${stageScale})`;
    svgBack.setAttribute('transform', tfSvg);
    svgFront.setAttribute('transform', tfSvg);
    // expose scale for helpers that read it
    window.stageScale = stageScale;
  }

  function setActiveTool(t) {
    currentTool = t;
    toolbar?.querySelectorAll('[data-action]').forEach(b => b.classList.toggle('active', b.dataset.action === t));
    if (toolPill) toolPill.textContent = `Tool: ${t}${snapOn?' • Snap':''}`;
    // 4-arrow cursor when pan is active
    stage.style.cursor = (t === 'pan') ? 'move' : '';
  }
  //window.moodCanvas = Object.assign(window.moodCanvas || {}, { setTool:setActiveTool });
	window.moodCanvas = Object.assign(window.moodCanvas || {}, {
	  setTool: setActiveTool,
	  debugDelete: (id) => deleteItemSmart(Number(id))
	});

  // ---------- API ----------
  async function api(method, url, body) {
	  const t0 = performance.now();
	  const pretty = (v) => { try { return JSON.stringify(v); } catch { return String(v); } };

	  console.groupCollapsed(`%c[canvas→API] ${method} ${url}`, 'color:#6b7280');
	  if (body !== undefined) console.debug('payload →', body);

	  try {
		const res = await fetch(url, {
		  method,
		  headers: { 'Accept':'application/json', ...(body ? {'Content-Type':'application/json'} : {}) },
		  body: body ? JSON.stringify(body) : undefined
		});
		const ms = (performance.now() - t0).toFixed(1);
		let data = null;
		try { data = await res.json(); } catch { /* non-JSON or empty */ }

		console.debug('status  ←', res.status, res.ok ? '(OK)' : '(NON-OK)');
		if (data !== null) console.debug('response ←', data);
		else               console.debug('response ← (no JSON)');

		if (!res.ok) console.warn('[canvas] API non-OK:', method, url, res.status, data);
		console.groupEnd();

		return { ok: res.ok, status: res.status, data };
	  } catch (e) {
		const ms = (performance.now() - t0).toFixed(1);
		console.error(`%c[canvas→API] network error after ${ms}ms`, 'color:#ef4444', e);
		console.groupEnd();
		return { ok:false, status:0, data:null };
	  }
	}

  const apiGET   = (u)=> api('GET', u);
  const apiPOST  = (u,b)=> api('POST', u, b);
  const apiPATCH = (u,b)=> api('POST', u, b); // router maps POST to update endpoint

 	// Delete: try BULK first (guaranteed route), then /:id/delete, then soft-delete (hidden:1)
	async function deleteItemSmart(id) {
	  
	  console.groupCollapsed(`%c[deleteItemSmart] id=${id}`, 'color:#0ea5e9');

	  // 1) BULK
	  const bulkPayload = { ops: [{ op:'delete', id:Number(id) }] };
	  console.debug('bulk →', bulkPayload);
	  const bulk = await api('POST', `${apiBase}/bulk`, bulkPayload);
	  if (bulk.ok && (bulk.data?.success === true || bulk.status === 200)) {
		console.info('[deleteItemSmart] bulk reported success');
		console.groupEnd();
		return bulk;
	  }
	  console.warn('[deleteItemSmart] bulk failed or not effective', bulk.status, bulk.data);

	  // 2) direct /:id/delete
	  const direct = await api('POST', `${apiBase}/${id}/delete`);
	  if (direct.ok && (direct.data?.success === true || direct.status === 200)) {
		console.info('[deleteItemSmart] direct /id/delete reported success');
		console.groupEnd();
		return direct;
	  }
	  console.warn('[deleteItemSmart] direct delete failed', direct.status, direct.data);

	  // 3) soft-delete fallback (hidden:1)
	  const soft = await api('POST', `${apiBase}/${id}`, { hidden: 1 });
	  if (soft.ok && (soft.data?.success === true || soft.status === 200)) {
		console.info('[deleteItemSmart] soft delete (hidden:1) reported success');
	  } else {
		console.error('[deleteItemSmart] soft delete failed too', soft.status, soft.data);
	  }
	  console.groupEnd();
					  return await api('POST', `/api/moods/${slug}/items/${id}:delete`);
	  return soft;
	}





  // ---------- renderers ----------
  function renderItem(item) {
    if (item.hidden) return; // respect soft-deletes on load

    const el = document.createElement('div');
    el.className = `canvas-item kind-${item.kind}`;
    el.dataset.id = String(item.id);
    el.dataset.kind = item.kind;
    el.style.position = 'absolute';
    el.style.left = `${item.x|0}px`;
    el.style.top  = `${item.y|0}px`;
    el.style.width  = `${Math.max(1, item.w|0)}px`;
    el.style.height = `${Math.max(1, item.h|0)}px`;
    el.style.boxSizing = 'border-box';
    el.style.userSelect = 'none';
    el.style.border = '1px solid #999';
    el.style.background = (item.kind === 'note') ? '#fffbe6' : '#fff';
    el.style.color = '#000';
    el.style.padding = '4px';

    if (item.kind === 'note' || item.kind === 'label') {
      const txt = item.payload?.text ?? (item.kind === 'label' ? 'Label' : 'New note');
      el.textContent = txt;
    } else if (item.kind === 'frame') {
      const title = document.createElement('div');
      title.textContent = item.payload?.title ?? 'Frame';
      title.style.fontWeight = '600';
      el.appendChild(title);
      el.style.border = '2px solid #666';
    }

    content.appendChild(el);
    itemsById[item.id] = { data:item, el };
  }

  function centerFromData(d) { return { cx:(d.x||0)+(d.w||0)/2, cy:(d.y||0)+(d.h||0)/2 }; }

  // render connector EXPECTING payload.a.item + payload.b.item
  function renderConnector(item) {
    if (item.hidden) return;
    // item.payload: { a:{item:idA}, b:{item:idB}, style:'straight' }
    const aId = item.payload?.a?.item, bId = item.payload?.b?.item;

    // Visible line
    const vis = document.createElementNS('http://www.w3.org/2000/svg','line');
    vis.dataset.id = String(item.id);
    vis._payload   = item.payload;                 // keep payload reference for updates
    vis.classList.add('connector-line');           // CSS hover/selected uses this
    vis.setAttribute('stroke', '#888');
    vis.setAttribute('stroke-width', '2');
    vis.setAttribute('stroke-linecap', 'round');
    vis.setAttribute('pointer-events', 'stroke');  // click/hover directly on visible line

    // Wide invisible hit line (easier clicking)
    const hit = document.createElementNS('http://www.w3.org/2000/svg','line');
    hit.dataset.id = String(item.id);
    hit.classList.add('connector-hit');
    hit.setAttribute('stroke', '#000');
    hit.setAttribute('stroke-opacity', '0');
    hit.setAttribute('stroke-width', '12');
    hit.setAttribute('pointer-events', 'stroke');

    // Append to back layer (under items visually; still clickable)
    // Order: hit first, then visible—so CSS like ".connector-hit:hover + .connector-line" could work.
    svgBack.appendChild(hit);
    svgBack.appendChild(vis);

    linesById[item.id]    = vis;
    lineHitsById[item.id] = hit;

    // reverse index: which connectors touch an item
    if (aId) (connectorsByItem[aId] ||= new Set()).add(item.id);
    if (bId) (connectorsByItem[bId] ||= new Set()).add(item.id);

    updateConnectorLine(item.id);
  }

  function updateConnectorLine(connId) {
    const line = linesById[connId]; if (!line) return;
    const hit  = lineHitsById[connId];
    const p = line._payload;
    const a = p?.a?.item ? itemsById[p.a.item]?.data : null;
    const b = p?.b?.item ? itemsById[p.b.item]?.data : null;
    if (!a || !b) return;
    const ax = (a.x||0) + (a.w||0)/2, ay = (a.y||0) + (a.h||0)/2;
    const bx = (b.x||0) + (b.w||0)/2, by = (b.y||0) + (b.h||0)/2;
    line.setAttribute('x1', String(ax));
    line.setAttribute('y1', String(ay));
    line.setAttribute('x2', String(bx));
    line.setAttribute('y2', String(by));
    if (hit) {
      hit.setAttribute('x1', String(ax));
      hit.setAttribute('y1', String(ay));
      hit.setAttribute('x2', String(bx));
      hit.setAttribute('y2', String(by));
    }
  }

  function refreshAttachedConnectors(itemId) {
    const set = connectorsByItem[itemId]; if (!set) return;
    set.forEach(cid => updateConnectorLine(cid));
  }

  // ---------- create helpers ----------
  async function createNoteAt(x,y) {
    const body = { kind:'note', x:snap(x), y:snap(y), w:200, h:100, payload:{ text:'New note' } };
    const r = await apiPOST(`${apiBase}/create`, body);
    const it = r.data && r.data.id ? r.data : Object.assign({ id: Date.now() }, body);
    renderItem(it);
  }
  async function createFrameAt(x,y) {
    const body = { kind:'frame', x:snap(x), y:snap(y), w:300, h:200, payload:{ title:'Frame' } };
    const r = await apiPOST(`${apiBase}/create`, body);
    const it = r.data && r.data.id ? r.data : Object.assign({ id: Date.now() }, body);
    renderItem(it);
  }
  async function createConnectorBetween(aId, bId) {
    if (!aId || !bId || aId === bId) return;
    const body = { kind:'connector', x:0,y:0,w:0,h:0, payload:{ a:{item:Number(aId)}, b:{item:Number(bId)}, style:'straight' } };
    const r = await apiPOST(`${apiBase}/create`, body);
    const conn = r.data && r.data.id ? r.data : Object.assign({ id: Date.now() }, body);
    itemsById[conn.id] = { data: conn }; // keep data map
    renderConnector(conn);
  }

  // ---------- delete helpers ----------
  async function deleteItem(id) {
	  console.groupCollapsed(`%c[deleteItem] id=${id}`, 'color:#10b981');
	  const entry = itemsById[id];
	  if (!entry) { console.warn('no entry found in itemsById'); console.groupEnd(); return; }

	  const r = await deleteItemSmart(id);
	  console.debug('server result →', r);

	  if (!r.ok) { console.warn('server did not OK delete; aborting DOM removal'); console.groupEnd(); return; }

	  // Remove DOM node
	  if (entry.el?.parentNode) {
		entry.el.parentNode.removeChild(entry.el);
		console.debug('removed element from DOM');
	  }
	  delete itemsById[id];

	  // Remove any connected lines
	  const set = connectorsByItem[id];
	  if (set) {
		set.forEach(cid => {
		  const L = linesById[cid]; if (L?.parentNode) L.parentNode.removeChild(L);
		  const H = lineHitsById?.[cid]; if (H?.parentNode) H.parentNode.removeChild(H);
		  delete linesById[cid];
		  if (lineHitsById) delete lineHitsById[cid];
		  Object.keys(connectorsByItem).forEach(k => connectorsByItem[k]?.delete(cid));
		  console.debug('removed connector', cid, 'linked to', id);
		});
		delete connectorsByItem[id];
	  }
	  console.groupEnd();
	}


  async function deleteConnector(id) {
	  console.groupCollapsed(`%c[deleteConnector] id=${id}`, 'color:#f59e0b');
	  const line = linesById[id];
	  if (!line) { console.warn('no <line> found for id', id); console.groupEnd(); return; }

	  const res = await deleteItemSmart(id);
	  console.debug('server result →', res);

	  if (!res?.ok) { console.warn('server did not OK delete; aborting DOM removal'); console.groupEnd(); return; }

	  const hit = lineHitsById?.[id];
	  if (hit?.parentNode) hit.parentNode.removeChild(hit);
	  if (line.parentNode) line.parentNode.removeChild(line);
	  if (lineHitsById) delete lineHitsById[id];
	  delete linesById[id];
	  Object.keys(connectorsByItem).forEach(k => connectorsByItem[k]?.delete(id));
	  delete itemsById[id]; // harmless if not present
	  if (selectedConnectorId === id) selectedConnectorId = null;

	  console.info('connector removed from DOM and maps');
	  console.groupEnd();
		return await api('POST', `/api/moods/${slug}/arrows/${id}:delete`);
	}


  // ---------- pointer + tools ----------
  stage.addEventListener('mousedown', onDown);
  function onDown(e) {
    const tgt = e.target;
    const itemEl = tgt.closest?.('.canvas-item');
    const empty  = (tgt === stage || tgt === content || tgt === svgEl || tgt === svgBack || tgt === svgFront);

    if (currentTool === 'pan') {
      isPanning = true; panStart.x = e.clientX; panStart.y = e.clientY;
      stage.style.cursor = 'grabbing';
      return;
    }

    if (currentTool === 'delete') {
      if (itemEl) return void deleteItem(Number(itemEl.dataset.id));
      // lines handled by svgBack click listener
      return;
    }

    if (currentTool === 'select') {
      if (empty) return beginMarquee(e.clientX, e.clientY);
      if (itemEl) return startDragItem(itemEl, e.clientX, e.clientY);
      return;
    }

    if (currentTool === 'text' && empty) {
      const p = logicalFromClient(e.clientX, e.clientY);
      return void createNoteAt(p.x, p.y);
    }
    if (currentTool === 'frame' && empty) {
      const p = logicalFromClient(e.clientX, e.clientY);
      return void createFrameAt(p.x, p.y);
    }

    if (currentTool === 'resize' && itemEl) {
      beginResizeItem(Number(itemEl.dataset.id), e.clientX, e.clientY);
      return;
    }

    // ✅ Connector tool: start drag from an item
    if (currentTool === 'connector' && itemEl) {
      beginConnectDrag(Number(itemEl.dataset.id), e.clientX, e.clientY);
      return;
    }
  }
		
  // Click on a connector (hit or visible line): delete (if Delete tool) or select (feedback)
  svgBack.addEventListener('click', (e) => {
    const lineEl = (e.target.closest && e.target.closest('line')) || null;
    if (!lineEl) return;
    const id = Number(lineEl.dataset.id);

    if (currentTool === 'delete') {
      e.stopPropagation();
      deleteConnector(id);
      return;
    }

    // selection feedback on visible line
    if (selectedConnectorId && linesById[selectedConnectorId]) {
      linesById[selectedConnectorId].classList.remove('connector-selected');
    }
    selectedConnectorId = id;
    const vis = linesById[id];
    if (vis) vis.classList.add('connector-selected');
  }, true);

  document.addEventListener('mousemove', (e) => {
    if (isPanning) {
      stageOffset.x += (e.clientX - panStart.x);
      stageOffset.y += (e.clientY - panStart.y);
      panStart.x = e.clientX; panStart.y = e.clientY;
      applyTransforms();
    }
    if (marquee) updateMarquee(e.clientX, e.clientY);
  });
  document.addEventListener('mouseup', () => {
    if (isPanning) {
      isPanning = false;
      if (currentTool === 'pan') stage.style.cursor = 'move';
    }
    if (marquee) endMarquee();
  });

  // toolbar
  (toolbar || document).addEventListener('click', (e) => {
    const b = e.target.closest?.('[data-action]'); if (!b) return;
    const a = b.dataset.action;
    if (a === 'snap') { snapOn = !snapOn; b.classList.toggle('active', snapOn); stage.classList.toggle('snap-on', snapOn); return; }
    if (a === 'zoom-in')  { stageScale = Math.min(2, stageScale + 0.1); applyTransforms(); return; }
    if (a === 'zoom-out') { stageScale = Math.max(0.4, stageScale - 0.1); applyTransforms(); return; }
    if (a === 'reset-view'){ stageScale = 1; stageOffset = {x:0,y:0}; applyTransforms(); return; }
    setActiveTool(a);
  }, true);

  // ---------- drag/marquee/connect ----------
  let dragGroup = null;
  function startDragItem(el, cx, cy) {
    const id = Number(el.dataset.id);
    if (!selectedIds.has(id)) { selectedIds = new Set([id]); }
    const m = logicalFromClient(cx, cy);
    dragGroup = Array.from(selectedIds).map(sid => {
      const d = itemsById[sid].data;
      return { id:sid, dx: m.x - d.x, dy: m.y - d.y };
    });
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragUp);
  }
  function onDragMove(e) {
    const m = logicalFromClient(e.clientX, e.clientY);
    dragGroup.forEach(it => {
      const d = itemsById[it.id].data;
      d.x = snap(m.x - it.dx); d.y = snap(m.y - it.dy);
      const el = itemsById[it.id].el;
      el.style.left = `${d.x}px`; el.style.top = `${d.y}px`;
      refreshAttachedConnectors(it.id);
    });
  }
  async function onDragUp() {
    const group = dragGroup; dragGroup = null;
    document.removeEventListener('mousemove', onDragMove);
    document.removeEventListener('mouseup', onDragUp);
    for (const it of group) {
      const d = itemsById[it.id].data;
      await apiPATCH(`${apiBase}/${it.id}`, { x:d.x, y:d.y });
    }
  }

  function beginResizeItem(id, cx, cy) {
    const entry = itemsById[id]; if (!entry) return;
    const d = entry.data;
    const start = { w:d.w||0, h:d.h||0, cx, cy };

    function onMove(ev) {
      const dx = (ev.clientX - start.cx) / (window.stageScale || 1);
      const dy = (ev.clientY - start.cy) / (window.stageScale || 1);
      d.w = Math.max(8, (start.w + dx)|0);
      d.h = Math.max(8, (start.h + dy)|0);
      entry.el.style.width  = d.w + 'px';
      entry.el.style.height = d.h + 'px';
      const set = connectorsByItem[id]; if (set) set.forEach(cid => updateConnectorLine(cid));
    }

    async function onUp() {
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup', onUp);
      await api('POST', `${apiBase}/${id}`, { w:entry.data.w, h:entry.data.h });
    }

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
  }

  function beginMarquee(cx, cy) {
    marqueeStart = logicalFromClient(cx, cy);
    marquee = document.createElement('div');
    marquee.className = 'marquee';
    stage.appendChild(marquee);
    updateMarquee(cx, cy);
  }
  function updateMarquee(cx, cy) {
    const cur = logicalFromClient(cx, cy);
    const x = Math.min(marqueeStart.x, cur.x), y = Math.min(marqueeStart.y, cur.y);
    const w = Math.abs(cur.x - marqueeStart.x), h = Math.abs(cur.y - marqueeStart.y);
    const left = x * stageScale + stageOffset.x;
    const top  = y * stageScale + stageOffset.y;
    marquee.style.left = `${left}px`; marquee.style.top = `${top}px`;
    marquee.style.width = `${w * stageScale}px`; marquee.style.height = `${h * stageScale}px`;
  }
  function endMarquee() {
    const box = marquee.getBoundingClientRect();
    marquee.remove(); marquee = null;
    const r = stage.getBoundingClientRect();
    const lx = (box.left - r.left - stageOffset.x)/stageScale;
    const ly = (box.top  - r.top  - stageOffset.y)/stageScale;
    const lw = box.width/stageScale, lh = box.height/stageScale;
    selectedIds.clear();
    for (const id in itemsById) {
      const d = itemsById[id].data;
      const inter = !(d.x + d.w < lx || d.y + d.h < ly || d.x > lx + lw || d.y > ly + lh);
      if (inter && d.kind !== 'connector') selectedIds.add(Number(id));
    }
  }

  // connector drag
  let connectDrag = null;
  function beginConnectDrag(fromId, cx, cy) {
    const line = document.createElementNS('http://www.w3.org/2000/svg','line');
    line.setAttribute('stroke', '#888'); line.setAttribute('stroke-width', '6'); line.setAttribute('stroke-dasharray', '5,5');
    line.setAttribute('pointer-events', 'stroke'); line.classList.add('connector-line');
    svgFront.appendChild(line);
    connectDrag = { fromId, tempLine: line, hoverId: null };
    updateConnectDrag(cx, cy);
    document.addEventListener('mousemove', onConnectDragMove, true);
    document.addEventListener('mouseup', onConnectDragUp, true);
  }
  function updateConnectDrag(cx, cy) {
    if (!connectDrag) return;
    const a = itemsById[connectDrag.fromId]?.data; if (!a) return;
    const ca = centerFromData(a); const p = logicalFromClient(cx, cy);
    connectDrag.tempLine.setAttribute('x1', String(ca.cx));
    connectDrag.tempLine.setAttribute('y1', String(ca.cy));
    connectDrag.tempLine.setAttribute('x2', String(p.x));
    connectDrag.tempLine.setAttribute('y2', String(p.y));
    // highlight hover
    const el = document.elementFromPoint(cx, cy);
    const hit = el?.closest?.('.canvas-item');
    const hid = hit ? Number(hit.dataset.id) : null;
    if (connectDrag.hoverId && itemsById[connectDrag.hoverId]?.el) {
      itemsById[connectDrag.hoverId].el.classList.remove('connect-hover');
    }
    if (hid && hid !== connectDrag.fromId && itemsById[hid]?.el) {
      itemsById[hid].el.classList.add('connect-hover');
    }
    connectDrag.hoverId = hid;
  }
  function onConnectDragMove(e){ updateConnectDrag(e.clientX, e.clientY); }
  function onConnectDragUp(e){
    document.removeEventListener('mousemove', onConnectDragMove, true);
    document.removeEventListener('mouseup', onConnectDragUp, true);
    const hid = connectDrag.hoverId, from = connectDrag.fromId;
    if (connectDrag.tempLine?.parentNode) connectDrag.tempLine.parentNode.removeChild(connectDrag.tempLine);
    connectDrag = null;
    if (hid && hid !== from) createConnectorBetween(from, hid);
  }

  // Keyboard delete / backspace
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Delete' || e.key === 'Backspace') {
      const tag = (e.target && e.target.tagName || '').toLowerCase();
      if (tag === 'input' || tag === 'textarea' || e.target?.isContentEditable) return;
      e.preventDefault();
      if (selectedIds && selectedIds.size) {
        [...selectedIds].forEach(id => deleteItem(id));
      } else if (selectedConnectorId) {
        deleteConnector(selectedConnectorId);
      }
    }
  }, true);

  // -------- init --------
  (async function init() {
    ensureOverlaySizing();
    const r = await apiGET(apiBase);
    const arr = Array.isArray(r.data) ? r.data : [];

    // render items then connectors (respect hidden)
    arr.filter(i => i.kind !== 'connector' && !i.hidden).forEach(renderItem);
    arr.filter(i => i.kind === 'connector' && !i.hidden).forEach(c => {
      itemsById[c.id] = { data: c };
      renderConnector(c);
    });

    setActiveTool('select');
    applyTransforms();
  })();

})();
