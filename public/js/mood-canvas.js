/* mood-canvas.js
 * Minimal client‑side implementation for the mood board canvas.
 */
(function() {
  'use strict';
  const slug    = window.boardSlug || '';
  const apiBase = `/api/moods/${slug}/canvas/items`;
  const stage   = document.getElementById('canvasStage');
  const toolbar = document.getElementById('canvas-toolbar');
  let currentTool = 'select';
  let itemsById   = {};
  let isPanning   = false;
  let panStart    = {x: 0, y: 0};
  let stageOffset = {x: 0, y: 0};
  let draggingItem= null;
  let dragStart   = {x: 0, y: 0};

  async function fetchItems() {
    const res = await fetch(apiBase);
    return res.ok ? res.json() : [];
  }
  async function createItem(kind, x, y, w, h, payload) {
    const body = {kind, x, y, w, h, payload};
    const res  = await fetch(`${apiBase}/create`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(body)
    });
    return res.ok ? res.json() : null;
  }
  async function updateItem(id, fields) {
    await fetch(`${apiBase}/${id}`, {
      method: 'PATCH',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(fields)
    });
  }

  function addItemToStage(item) {
    const el    = document.createElement('div');
    el.className= 'canvas-item';
    el.dataset.id   = item.id;
    el.dataset.kind = item.kind;
    el.style.position = 'absolute';
    el.style.left   = `${item.x}px`;
    el.style.top    = `${item.y}px`;
    el.style.width  = `${item.w}px`;
    el.style.height = `${item.h}px`;
    el.style.border = '1px solid #999';
    el.style.background = '#f9f9f9';
    el.style.cursor    = 'move';
    if (item.payload && (item.kind === 'note' || item.kind === 'label')) {
      el.textContent   = item.payload.text || '';
      el.style.padding = '4px';
      el.style.fontSize= '14px';
    }
    stage.appendChild(el);
    itemsById[item.id] = {data: item, el};
  }

  function handleToolbarClick(e) {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    currentTool = btn.dataset.action;
    Array.from(toolbar.querySelectorAll('button'))
      .forEach(b => b.classList.toggle('active', b === btn));
  }

  function handleMouseDown(e) {
    const target = e.target;
    if (currentTool === 'pan') {
      isPanning   = true;
      panStart.x  = e.clientX;
      panStart.y  = e.clientY;
      stage.style.cursor = 'grab';
      return;
    }
    if (currentTool === 'text' && target === stage) {
      const rect = stage.getBoundingClientRect();
      const x    = e.clientX - rect.left;
      const y    = e.clientY - rect.top;
      createItem('note', x, y, 200, 100, {text: 'New note'})
        .then(item => { if (item) addItemToStage(item); });
      return;
    }
    if (currentTool === 'select' && target.classList.contains('canvas-item')) {
      draggingItem = itemsById[target.dataset.id];
      dragStart.x  = e.clientX - parseInt(target.style.left, 10);
      dragStart.y  = e.clientY - parseInt(target.style.top, 10);
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    }
  }

  function handleMouseMove(e) {
    if (isPanning) {
      const dx    = e.clientX - panStart.x;
      const dy    = e.clientY - panStart.y;
      stageOffset.x += dx;
      stageOffset.y += dy;
      stage.style.transform = `translate(${stageOffset.x}px, ${stageOffset.y}px)`;
      panStart.x  = e.clientX;
      panStart.y  = e.clientY;
      return;
    }
    if (!draggingItem) return;
    const el = draggingItem.el;
    const x  = e.clientX - dragStart.x;
    const y  = e.clientY - dragStart.y;
    el.style.left = `${x}px`;
    el.style.top  = `${y}px`;
  }

  async function handleMouseUp() {
    if (isPanning) {
      isPanning         = false;
      stage.style.cursor= 'default';
      return;
    }
    if (!draggingItem) return;
    const el = draggingItem.el;
    const id = draggingItem.data.id;
    const x  = parseInt(el.style.left, 10);
    const y  = parseInt(el.style.top, 10);
    draggingItem.data.x = x;
    draggingItem.data.y = y;
    await updateItem(id, {x, y});
    draggingItem = null;
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
  }

  async function init() {
    toolbar.addEventListener('click', handleToolbarClick);
    stage.addEventListener('mousedown', handleMouseDown);
    const items = await fetchItems();
    items.forEach(addItemToStage);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
