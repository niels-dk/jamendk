/* mood-canvas-input.js — unified input (mouse + touch) for desktop & mobile
   - 1 finger: select, move, marquee (forwards as mouse events to v1)
   - 2 fingers: pan + pinch to zoom (synthetic pan + zoomIn/Out)
   - Prevents native page scroll/zoom within canvas stage
*/
(function(){
  'use strict';

  var LOG = '[input]';
  function log(){ try{ console.log.apply(console, [LOG].concat([].slice.call(arguments))); }catch(e){} }
  function warn(){ try{ console.warn.apply(console, [LOG].concat([].slice.call(arguments))); }catch(e){} }

  // Wait until v1 announces it's ready
  function onReady(fn){
    if (window.moodCanvasBus && window.moodCanvas) return fn();
    var tries = 0;
    var t = setInterval(function(){
      tries++;
      if (window.moodCanvasBus && window.moodCanvas) { clearInterval(t); fn(); }
      else if (tries > 200) { clearInterval(t); warn('v1 bus/controller not found'); }
    }, 25);
    // also listen once if bus appears and emits
    if (window.moodCanvasBus && window.moodCanvasBus.on) {
      window.moodCanvasBus.on('ready', fn);
    }
  }
	
  var LONGPRESS_MS   = 450;  // delay to trigger edit
  var MOVE_THRESHOLD = 6;    // px (cancel long-press if user moves)

  function isEditableItemEl(el) {
    var item = el && el.closest ? el.closest('.canvas-item') : null;
    if (!item) return false;
    var k = item.dataset && item.dataset.kind;
    // enable for notes & labels; optional: also allow frames (title edit)
    return (k === 'note' || k === 'label'); // add || k === 'frame' if you want title edit
  }

  function dispatchDblClick(target, x, y) {
    var evt = new MouseEvent('dblclick', {
      bubbles: true, cancelable: true, clientX: x, clientY: y
    });
    (target || document).dispatchEvent(evt);
  }


  onReady(function init(){
    log('ready');

    var mc = window.moodCanvas;
    var stage   = document.getElementById('canvasStage');
    var content = document.getElementById('canvasContent');
    var toolbar = document.getElementById('canvas-toolbar') || document.querySelector('[data-canvas-toolbar]');
    if (!stage || !content) { warn('stage/content not found'); return; }

    injectStyles();

    // Track active pointers
    var pointers = new Map(); // pointerId -> {x,y,target}
    var single = null;        // {pointerId, start:{x,y}, last:{x,y}, target, mousedownSent}
    var multi  = null;        // {ids:[id1,id2], startDist, lastDist, startMid, lastMid, prevTool, panDownSent, zoomBucket}

    // Helpers
    function synthMouse(type, x, y, target){
      var evt = new MouseEvent(type, {
        bubbles:true, cancelable:true,
        clientX:x, clientY:y, buttons: (type==='mouseup'?0:1)
      });
      (target || stage).dispatchEvent(evt);
    }
    function pt(e){ return { x:e.clientX, y:e.clientY, target:e.target }; }
    function dist(a,b){ var dx=a.x-b.x, dy=a.y-b.y; return Math.hypot(dx,dy); }
    function mid(a,b){ return { x:(a.x+b.x)/2, y:(a.y+b.y)/2 }; }
    function elementAt(x,y){ return document.elementFromPoint(x,y) || stage; }

    function setToolSafely(t){
      try { if (mc && typeof mc.setTool === 'function') mc.setTool(t); } catch(e){}
    }
    function getToolSafely(){
      try { return (mc && typeof mc.getTool === 'function') ? mc.getTool() : null; } catch(e){ return null; }
    }
    function zoomIn(){ try{ mc && mc.zoomIn && mc.zoomIn(); }catch(e){} }
    function zoomOut(){ try{ mc && mc.zoomOut && mc.zoomOut(); }catch(e){} }

    // Prevent native scroll/zoom over the canvas area
    ['touchstart','touchmove','gesturestart','gesturechange','gestureend'].forEach(function(ev){
      stage.addEventListener(ev, function(e){ e.preventDefault(); }, {passive:false});
      content.addEventListener(ev, function(e){ e.preventDefault(); }, {passive:false});
    });

    // Pointer listeners (we use pointer events for touch; leave mouse as-is)
    stage.addEventListener('pointerdown', onPointerDown, {passive:false});
    stage.addEventListener('pointermove', onPointerMove, {passive:false});
    stage.addEventListener('pointerup',   onPointerUp,   {passive:false});
    stage.addEventListener('pointercancel', onPointerUp, {passive:false});
    // We also listen on content to catch pointers starting on children
    content.addEventListener('pointerdown', onPointerDown, {passive:false});
    content.addEventListener('pointermove', onPointerMove, {passive:false});
    content.addEventListener('pointerup',   onPointerUp,   {passive:false});
    content.addEventListener('pointercancel', onPointerUp, {passive:false});

    function onPointerDown(e){
      if (e.pointerType !== 'touch') return; // mouse/pen handled natively by v1
      e.preventDefault();

      // Track pointer
      var P = pt(e);
      pointers.set(e.pointerId, P);

      if (pointers.size === 1) {
		  var target = elementAt(P.x, P.y);
		  single = {
			pointerId: e.pointerId,
			start: { x: P.x, y: P.y },
			last:  { x: P.x, y: P.y },
			target: target,
			mousedownSent: false,
			lpTimer: null,
			lpFired: false
		  };

		  // Start long-press only if touching an editable item
		  if (isEditableItemEl(target)) {
			single.lpTimer = setTimeout(function(){
			  // Long-press fired → end any drag then dispatch dblclick
			  single.lpFired = true;
			  if (single.mousedownSent) synthMouse('mouseup', single.last.x, single.last.y, single.target);
			  // Send a dblclick to the item itself (lets your existing handler run)
			  var itemEl = single.target.closest('.canvas-item') || single.target;
			  dispatchDblClick(itemEl, single.last.x, single.last.y);
			}, LONGPRESS_MS);
		  }

		  // For responsiveness, we still send mousedown immediately
		  synthMouse('mousedown', P.x, P.y, target);
		  single.mousedownSent = true;
		}

      else if (pointers.size === 2) {
        // Start two-finger pan/zoom
        var ids = Array.from(pointers.keys());
        var A = pointers.get(ids[0]), B = pointers.get(ids[1]);
        multi = {
          ids: ids.slice(0,2),
          startDist: dist(A,B),
          lastDist: dist(A,B),
          startMid: mid(A,B),
          lastMid: mid(A,B),
          prevTool: getToolSafely(),
          panDownSent: false,
          zoomBucket: 0
        };

        // If a single-finger drag was started, finish it to avoid conflicts
        if (single && single.mousedownSent) {
          synthMouse('mouseup', single.last.x, single.last.y, single.target);
        }
        single = null;

        // Enter pan tool & send synthetic mousedown at midpoint
        setToolSafely('pan');
        synthMouse('mousedown', multi.startMid.x, multi.startMid.y, stage);
        multi.panDownSent = true;
      }
      // >2 fingers: ignore extras for now
    }

    function onPointerMove(e){
      if (e.pointerType !== 'touch') return;
      e.preventDefault();
      var rec = pointers.get(e.pointerId);
      if (!rec) return;

      // Update stored pointer
      rec.x = e.clientX; rec.y = e.clientY; rec.target = e.target;

      if (multi && multi.ids.includes(e.pointerId)) {
        // Two-finger pan + pinch
        var A = pointers.get(multi.ids[0]), B = pointers.get(multi.ids[1]);
        if (!A || !B) return;
        var newMid  = mid(A,B);
        var newDist = dist(A,B);

        // Pan: move synthetic mouse while pan tool is active
        if (multi.panDownSent) synthMouse('mousemove', newMid.x, newMid.y, stage);

        // Zoom: bucketed zoom steps to avoid jitter (every ~8% scale change)
        var scale = newDist / (multi.lastDist || newDist);
        if (!isFinite(scale) || scale === 0) scale = 1;
        // Accumulate into bucket; on threshold, fire zoom
        var threshold = 0.08;
        var delta = Math.log(scale); // symmetric around 0
        multi.zoomBucket += delta;
        while (multi.zoomBucket > threshold) {
          zoomIn(); multi.zoomBucket -= threshold;
        }
        while (multi.zoomBucket < -threshold) {
          zoomOut(); multi.zoomBucket += threshold;
        }

        multi.lastMid = newMid;
        multi.lastDist = newDist;
        return;
      }

      if (single && e.pointerId === single.pointerId) {
		  // Cancel long-press if user moves too much
		  var dx = e.clientX - single.start.x;
		  var dy = e.clientY - single.start.y;
		  if (single.lpTimer && (Math.abs(dx) > MOVE_THRESHOLD || Math.abs(dy) > MOVE_THRESHOLD)) {
			clearTimeout(single.lpTimer); single.lpTimer = null;
		  }
		  // If long-press already fired, do not keep dragging
		  if (single.lpFired) return;

		  // Forward as mousemove (normal drag)
		  synthMouse('mousemove', e.clientX, e.clientY, document);
		  single.last.x = e.clientX; single.last.y = e.clientY;
		}
    }

    function onPointerUp(e){
      if (e.pointerType !== 'touch') return;
      e.preventDefault();

      // Remove pointer from map
      var wasIn = pointers.has(e.pointerId);
      var P = pointers.get(e.pointerId);
      pointers.delete(e.pointerId);

      // End two-finger gesture if any of the two lifted
      if (multi && multi.ids.includes(e.pointerId)) {
        // Finish pan
        if (multi.panDownSent) synthMouse('mouseup', (multi.lastMid||P||{x:0,y:0}).x, (multi.lastMid||P||{y:0}).y, stage);
        // Restore previous tool
        if (multi.prevTool) setToolSafely(multi.prevTool);
        multi = null;

        // If one finger remains down, we *could* transition to single mode,
        // but to keep things predictable, wait for a fresh touchstart.
        single = null;
        return;
      }

      // Finish single-finger forwarded drag
      if (single && e.pointerId === single.pointerId) {
		  // stop LP timer if still pending
		  if (single.lpTimer) { clearTimeout(single.lpTimer); single.lpTimer = null; }
		  // If long-press triggered, we already ended the drag and opened editor
		  if (single.lpFired) { single = null; return; }

		  // Otherwise finish the drag normally
		  synthMouse('mouseup', e.clientX, e.clientY, document);
		  single = null;
		  return;
		}
    }

    // Desktop wheel → let v1 handle if already wired. If you want smoother zoom,
    // you could intercept here and call zoomIn/zoomOut based on deltaY.

    log('attached');
  });

  // Inject touch-friendly styles (hit targets, toolbar)
  function injectStyles(){
    var css = ''
      + '#canvasStage, #canvasContent{touch-action:none;}'  // prevent native scroll/zoom
      // bigger (invisible) hit areas for resize handles on touch
      + '@media (pointer:coarse){'
      + '  .resize-handle{width:24px;height:24px;margin:-12px 0 0 -12px;opacity:0.9;}'
      + '  .resize-handle.center{width:28px;height:28px;margin:-14px 0 0 -14px;}'
      + '  .canvas-toolbar button{min-height:44px;min-width:44px;padding:10px 12px;}'
      + '}'
      ;
    var el = document.createElement('style');
    el.id = 'mc-input-styles';
    el.type = 'text/css';
    el.appendChild(document.createTextNode(css));
    document.head.appendChild(el);
  }
})();
