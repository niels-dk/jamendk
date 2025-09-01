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
const __mc_customKeyBindings = (typeof __mc_customKeyBindings !== 'undefined') ? __mc_customKeyBindings : new Map();

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
  getState(){ return { /* return your state here */ }; },
  setTool: (t)=> (typeof setTool==='function'? setTool(t):null),
  zoomIn:  ()=> (typeof zoomIn==='function'? zoomIn():null),
  zoomOut: ()=> (typeof zoomOut==='function'? zoomOut():null),
  resetView:()=> (typeof resetView==='function'? resetView():null),

  addNoteAt:  (x,y)=> (typeof createNoteAt==='function'? createNoteAt(x,y):null),
  addFrameAt: (x,y)=> (typeof createFrameAt==='function'? createFrameAt(x,y):null),

  deleteConnector: (id)=> (typeof deleteConnector==='function'? deleteConnector(id):false),
  deleteItems:     (ids)=> (typeof deleteItems==='function'? deleteItems(ids):false),

  setItemStyle: (id,style)=> (typeof updateItem==='function'? updateItem(id,{style}):false),

  registerKeyBinding: (combo, fn)=>{ __mc_customKeyBindings.set(combo.toLowerCase(), fn); }
};

// 4) Announce ready
window.moodCanvasBus.emit('ready', { controller: window.moodCanvas });
