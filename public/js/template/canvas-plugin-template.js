/* mood-canvas-<feature>.js — plugin for v1 */
(function(){
  'use strict';

  const PLUGIN_KEY = 'mc-plugin-<feature>';
  if (window.__moodCanvasPlugins?.has(PLUGIN_KEY)) return;
  (window.__moodCanvasPlugins ||= new Set()).add(PLUGIN_KEY);

  function onReady({ controller: mc }) {
    // mc is the public API from v1
    // Examples:
    // mc.addNoteAt(100,100);
    // mc.setItemStyle(id, { borderColor:'#f00' });
    // mc.registerKeyBinding('ctrl+g', () => { mc.group([...mc.getState().selectedItemIds]); });

    window.moodCanvas_<feature> = {
      // expose your plugin functions here
      example(){
        console.log('Plugin <feature> active, current state:', mc.getState());
      }
    };
  }

  if (window.moodCanvasBus) {
    window.moodCanvasBus.on('ready', onReady);
  } else {
    const t = setInterval(() => {
      if (window.moodCanvasBus) { clearInterval(t); window.moodCanvasBus.on('ready', onReady); }
    }, 30);
    setTimeout(() => clearInterval(t), 5000);
  }
})();
