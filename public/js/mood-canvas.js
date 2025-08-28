/*
 * mood-canvas.js
 *
 * Minimal client‑side implementation for the mood board canvas.  This
 * script fetches canvas items from the server, renders them as absolutely
 * positioned elements inside the #canvasStage, and provides simple
 * drag‑and‑drop editing.  It also wires up the toolbar to switch
 * tools (select, pan, text, frame, connector) and creates new note
 * elements when in text mode.  For brevity the implementation skips
 * advanced features such as grouping, keyboard shortcuts and undo/redo.
 */

(function() {
  'use strict';

  // Determine the base API URL using the boardSlug injected by PHP
  const slug = window.boardSlug || '';
  const apiBase = `/api/moods/${slug}/canvas/items`;

  // Elements
  const stage = document.getElementById('canvasStage');
  const toolbar = document.getElementById('canvas-toolbar');

  // Application state
  let currentTool = 'select';
  let itemsById = {};
  let isPanning = false;
  let panStart = {x: 0, y: 0};
  let stageOffset = {x: 0, y: 0};
  let draggingItem = null;
  let dragStart = {x: 0, y: 0};
  // Snap to grid flag; toggled via the Snap button.  When true,
  // positions will be aligned to an 8px grid on move or creation.
  let snapToGrid = false;
  // State for creating connectors: tracks the starting item ID when
  // the user clicks the first item while in connector mode.  When
  // the second item is clicked, a connector is created and this is reset.
  let connectorStart = null;

  /** API helper: fetch all items */
  async function fetchItems() {
    const res = await fetch(apiBase);
    return res.ok ? res.json() : [];
  }

  /** API helper: create a new item */
  async function createItem(kind, x, y, w, h, payload) {
    const body = {kind, x, y, w, h, payload};
    const res = await fetch(`${apiBase}/create`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(body)
    });
    return res.ok ? res.json() : null;
  }

  /** API helper: update a single item */
  async function updateItem(id, fields) {
    await fetch(`${apiBase}/${id}`, {
      method: 'PATCH',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(fields)
    });
  }

  /** API helper: delete an item */
  async function deleteItem(id) {
    await fetch(`${apiBase}/${id}/delete`, { method: 'DELETE' });
  }

  /** Create a DOM element for an item and append it to the stage */
  function addItemToStage(item) {
    const div = document.createElement('div');
    div.className = 'canvas-item';
    div.dataset.id = item.id;
    div.dataset.kind = item.kind;
    // Basic styles
    div.style.position = 'absolute';
    div.style.left = `${item.x}px`;
    div.style.top = `${item.y}px`;
    div.style.width = `${item.w}px`;
    div.style.height = `${item.h}px`;
    div.style.border = '1px solid #999';
    div.style.background = '#f9f9f9';
    div.style.cursor = 'move';
    // Display payload text for notes and labels
    if (item.payload && (item.kind === 'note' || item.kind === 'label')) {
      div.textContent = item.payload.text || '';
      div.style.padding = '4px';
      div.style.fontSize = '14px';
      div.style.overflow = 'hidden';
    }
    stage.appendChild(div);
    itemsById[item.id] = {data: item, el: div};
  }

  /** Switch the current tool based on toolbar clicks */
  function handleToolbarClick(e) {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;
    if (action === 'snap') {
      // Toggle snap mode without switching tools
      snapToGrid = !snapToGrid;
      btn.classList.toggle('active', snapToGrid);
      return;
    }
    currentTool = action;
    // Visual feedback: highlight the selected tool and clear others
    Array.from(toolbar.querySelectorAll('button')).forEach(b => {
      if (b.dataset.action === 'snap') {
        // Leave snap button highlighting to its own toggle state
        return;
      }
      b.classList.toggle('active', b === btn);
    });
  }

  /** Handle mousedown on the stage or item */
  function handleMouseDown(e) {
    const target = e.target;
    // Pan tool: start panning the stage
    if (currentTool === 'pan') {
      isPanning = true;
      panStart.x = e.clientX;
      panStart.y = e.clientY;
      stage.style.cursor = 'grab';
      return;
    }
    // Create text note
    if (currentTool === 'text' && target === stage) {
      const rect = stage.getBoundingClientRect();
      let x = e.clientX - rect.left;
      let y = e.clientY - rect.top;
      // Apply snapping on creation
      if (snapToGrid) {
        x = Math.round(x / 8) * 8;
        y = Math.round(y / 8) * 8;
      }
      createItem('note', x, y, 200, 100, {text: 'New note'}).then(item => {
        if (item) addItemToStage(item);
      });
      return;
    }

    // Create a frame
    if (currentTool === 'frame' && target === stage) {
      const rect = stage.getBoundingClientRect();
      let x = e.clientX - rect.left;
      let y = e.clientY - rect.top;
      if (snapToGrid) {
        x = Math.round(x / 8) * 8;
        y = Math.round(y / 8) * 8;
      }
      const defaultW = 300;
      const defaultH = 200;
      createItem('frame', x, y, defaultW, defaultH, {title: 'Frame'}).then(item => {
        if (item) addItemToStage(item);
      });
      return;
    }

    // Connector tool: capture start and end items
    if (currentTool === 'connector' && target.classList.contains('canvas-item')) {
      const id = target.dataset.id;
      if (!connectorStart) {
        // First click selects start; mark visually
        connectorStart = id;
        target.classList.add('connecting');
      } else {
        // Second click selects end; create connector
        const startId = connectorStart;
        const endId = id;
        // Remove visual marker
        const startEl = itemsById[startId].el;
        startEl.classList.remove('connecting');
        connectorStart = null;
        createItem('connector', 0, 0, 0, 0, {a: {item: startId}, b: {item: endId}}).then(item => {
          if (item) {
            // For now connectors have no visual representation; log to console
            console.log('Connector created:', item);
            alert('Connector created; visual lines are not implemented yet.');
          }
        });
      }
      return;
    }

    // Delete tool: remove an item
    if (currentTool === 'delete' && target.classList.contains('canvas-item')) {
      const id = target.dataset.id;
      deleteItem(id).then(() => {
        stage.removeChild(target);
        delete itemsById[id];
      });
      return;
    }
    // Start dragging an existing item
    if (currentTool === 'select' && target.classList.contains('canvas-item')) {
      draggingItem = itemsById[target.dataset.id];
      dragStart.x = e.clientX - parseInt(target.style.left, 10);
      dragStart.y = e.clientY - parseInt(target.style.top, 10);
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    }
  }

  function handleMouseMove(e) {
    if (isPanning) {
      const dx = e.clientX - panStart.x;
      const dy = e.clientY - panStart.y;
      stageOffset.x += dx;
      stageOffset.y += dy;
      stage.style.transform = `translate(${stageOffset.x}px, ${stageOffset.y}px)`;
      panStart.x = e.clientX;
      panStart.y = e.clientY;
      return;
    }
    if (!draggingItem) return;
    const el = draggingItem.el;
    const x = e.clientX - dragStart.x;
    const y = e.clientY - dragStart.y;
    el.style.left = `${x}px`;
    el.style.top = `${y}px`;
  }

  async function handleMouseUp(e) {
    if (isPanning) {
      isPanning = false;
      stage.style.cursor = 'default';
      return;
    }
    if (!draggingItem) return;
    // Persist the new position
    const el = draggingItem.el;
    const id = draggingItem.data.id;
    let x = parseInt(el.style.left, 10);
    let y = parseInt(el.style.top, 10);
    if (snapToGrid) {
      x = Math.round(x / 8) * 8;
      y = Math.round(y / 8) * 8;
      el.style.left = `${x}px`;
      el.style.top = `${y}px`;
    }
    draggingItem.data.x = x;
    draggingItem.data.y = y;
    await updateItem(id, {x, y});
    draggingItem = null;
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
  }

  /** Initialize the stage by loading items and attaching listeners */
  async function init() {
    toolbar.addEventListener('click', handleToolbarClick);
    stage.addEventListener('mousedown', handleMouseDown);
    const items = await fetchItems();
    items.forEach(addItemToStage);
  }

  // Kick off the initialization once DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();