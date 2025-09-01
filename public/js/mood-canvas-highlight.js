/* mood-canvas-highlight.js — robust toolbar plugin (ES5) */
(function(){
  'use strict';

  var PLUGIN_KEY = 'mc-plugin-highlight-toolbar';
  // idempotency
  if (!window.__moodCanvasPlugins) {
    if (typeof Set !== 'undefined') window.__moodCanvasPlugins = new Set();
    else window.__moodCanvasPlugins = { _: {}, has:function(k){return !!this._[k];}, add:function(k){this._[k]=1;} };
  }
  if (window.__moodCanvasPlugins.has && window.__moodCanvasPlugins.has(PLUGIN_KEY)) return;
  if (window.__moodCanvasPlugins.add) window.__moodCanvasPlugins.add(PLUGIN_KEY);

  // simple logger
  function log(){ if (window.console && console.log) console.log.apply(console, ['[highlight]'].concat([].slice.call(arguments))); }
  function warn(){ if (window.console && console.warn) console.warn.apply(console, ['[highlight]'].concat([].slice.call(arguments))); }

  // minimal CSS
  function injectStyle(){
	  if (document.getElementById('mc-highlight-style')) return;
	  var css = ''
		+ '.mc-group{position:relative;display:inline-block;}'
		+ '.mc-pop{position:absolute;top:100%;right:0;margin-top:6px;background:#fff;border:1px solid #ccc;border-radius:8px;padding:8px;display:none;box-shadow:0 8px 20px rgba(0,0,0,.15);z-index:9999;}'
		+ '.mc-pop.open{display:block;}'
		+ '.mc-swatches{display:grid;grid-template-columns:repeat(6,20px);gap:6px;}'
		+ '.mc-swatch{width:20px;height:20px;border-radius:4px;border:1px solid rgba(0,0,0,.2);cursor:pointer;}'
		+ '.mc-actions{display:flex;justify-content:space-between;gap:8px;margin-top:8px;}'
		+ '.mc-link{font:12px sans-serif;color:#333;text-decoration:underline;cursor:pointer;}';
	  var s = document.createElement('style');
	  s.id = 'mc-highlight-style';
	  s.textContent = css;
	  document.head.appendChild(s);
	}

  function makeEl(tag, cls, text){
    var el = document.createElement(tag);
    if (cls) el.className = cls;
    if (text) el.textContent = text;
    return el;
  }

  function applyHighlight(mc, color){
	  var state = mc.getState ? mc.getState() : null;

	  // 1) collect ids
	  var ids = [];
	  if (state && state.selectedItemIds && state.selectedItemIds.forEach) {
		state.selectedItemIds.forEach(function(id){ ids.push(Number(id)); });
	  }
	  if (!ids.length) {
		var nodes = document.querySelectorAll('.canvas-item.selected, .canvas-item.is-selected');
		for (var i=0; i<nodes.length; i++) {
		  var el = nodes[i], id = el && el.dataset ? Number(el.dataset.id) : NaN;
		  if (!isNaN(id)) ids.push(id);
		}
	  }
	  if (!ids.length) { console.warn('[highlight] no selection'); return; }

	  // 2) normalize color
	  var rgba = color == null ? null : toRgba(color, 0.1);

	  // 3) apply
	  for (var j=0; j<ids.length; j++) {
		var id2 = ids[j];
		if (rgba) {
		  var alpha = 1.0; // <- was 0.2
		  var rgba = toRgba(color, alpha);
		  mc.setItemStyle(id2, { backgroundColor: rgba });
		} else {
		  mc.setItemStyle(id2, { backgroundColor: null });
		}
	  }
	  console.log('[highlight] applied to', ids.length, 'item(s)', 'rgba=', rgba);
	}


	// --- color helpers ---
	function hexToRgb(hex){
	  if (!hex) return null;
	  hex = String(hex).trim();
	  if (hex[0] === '#') hex = hex.slice(1);
	  if (hex.length === 3) {
		hex = hex.split('').map(function(ch){ return ch + ch; }).join('');
	  }
	  if (hex.length !== 6) return null;
	  var r = parseInt(hex.slice(0,2), 16);
	  var g = parseInt(hex.slice(2,4), 16);
	  var b = parseInt(hex.slice(4,6), 16);
	  if (isNaN(r)||isNaN(g)||isNaN(b)) return null;
	  return { r:r, g:g, b:b };
	}

	function toRgba(input, alpha){
	  if (input == null) return null;
	  // already an rgba()/rgb() string
	  var s = String(input).trim();
	  if (/^rgba?\(/i.test(s)) {
		// ensure alpha
		if (/^rgba\(/i.test(s)) return s;
		// rgb(...) -> rgba(..., alpha)
		return s.replace(/^rgb\((.*)\)$/i, 'rgba($1, ' + (alpha==null?0.2:alpha) + ')');
	  }
	  // hex like #fff or #ffcc00
	  if (s[0] === '#') {
		var rgb = hexToRgb(s);
		if (!rgb) return null;
		return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + (alpha==null?0.2:alpha) + ')';
	  }
	  // object {r,g,b}
	  if (typeof input === 'object' && input) {
		var r = input.r, g = input.g, b = input.b;
		if ([r,g,b].every(function(v){ return typeof v === 'number' && v >= 0; })) {
		  return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + (alpha==null?0.2:alpha) + ')';
		}
	  }
	  return null;
	}

  function buildButton(host, mc){
    var group = makeEl('span', 'mc-group');
    var btn   = makeEl('button', 'mc-btn', 'Highlight');
    btn.title = 'Highlight selection (Ctrl+H)';
    var pop   = makeEl('div', 'mc-pop');

    group.appendChild(btn);
    group.appendChild(pop);
    host.appendChild(group);

    // swatches
    var swWrap = makeEl('div', 'mc-swatches');
    var swatches = ['#ffff66','#ffed4a','#ffd166','#fca5a5','#f87171','#60a5fa','#34d399','#a78bfa','#f59e0b','#f43f5e','#10b981','#3b82f6'];
    for (var i=0;i<swatches.length;i++){
      var c = swatches[i];
      var sw = makeEl('div','mc-swatch');
      sw.style.background = c;
      (function(color){
        sw.addEventListener('click', function(){
          applyHighlight(mc, color);
          pop.classList.remove('open');
        });
      })(c);
      swWrap.appendChild(sw);
    }
    var actions = makeEl('div','mc-actions');
    var clearL  = makeEl('span','mc-link','Clear');
    clearL.addEventListener('click', function(){
      applyHighlight(mc, '#ffffff');
      pop.classList.remove('open');
    });
    var yellowL = makeEl('span','mc-link','Yellow');
    yellowL.addEventListener('click', function(){
      applyHighlight(mc, '#ffff66');
      pop.classList.remove('open');
    });
    actions.appendChild(clearL);
    actions.appendChild(yellowL);

    pop.appendChild(swWrap);
    pop.appendChild(actions);

    btn.addEventListener('click', function(e){
      e.stopPropagation();
      pop.classList.toggle('open');
    });
    document.addEventListener('click', function(){ pop.classList.remove('open'); });

    // keyboard shortcut via v1
    if (mc.registerKeyBinding) {
      mc.registerKeyBinding('ctrl+h', function(){ applyHighlight(mc, '#ffff66'); });
    }
  }

  function mountUI(mc){
    injectStyle();

    // 1) Try your toolbar
    var host = document.getElementById('canvas-toolbar');
    if (host) {
      log('toolbar found (#canvas-toolbar), injecting button');
      buildButton(host, mc);
      return;
    }

    // 2) Wait for toolbar if it’s created later
    var tried = 0, maxTries = 50;
    var poll = setInterval(function(){
      tried++;
      var el = document.getElementById('canvas-toolbar');
      if (el) {
        clearInterval(poll);
        log('toolbar appeared later, injecting button');
        buildButton(el, mc);
      } else if (tried >= maxTries) {
        clearInterval(poll);
        // 3) Fallback: floating mini-toolbar
        log('toolbar not found, creating floating palette');
        var floating = makeEl('div', '');
        floating.id = 'mc-toolbar-floating';
        floating.style.position = 'fixed';
        floating.style.top = '12px';
        floating.style.right = '12px';
        floating.style.background = 'rgba(255,255,255,.92)';
        floating.style.border = '1px solid #ddd';
        floating.style.borderRadius = '10px';
        floating.style.padding = '6px 8px';
        floating.style.boxShadow = '0 6px 18px rgba(0,0,0,.12)';
        document.body.appendChild(floating);
        buildButton(floating, mc);
      }
    }, 100);

    // Also watch DOM for toolbars inserted dynamically
    if (window.MutationObserver) {
      var obs = new MutationObserver(function(){
        var el = document.getElementById('canvas-toolbar');
        if (el) { try { obs.disconnect(); } catch(_){}; clearInterval(poll); buildButton(el, mc); }
      });
      try { obs.observe(document.documentElement, { childList:true, subtree:true }); } catch(_){}
    }
  }

  function onReady(payload){
    log('bus ready received');
    if (!payload || !payload.controller) { warn('no controller on ready'); return; }
    mountUI(payload.controller);
  }
	
  // If v1 is already ready (loaded earlier), run immediately
  if (window.moodCanvas && typeof window.moodCanvas.getState === 'function') {
    onReady({ controller: window.moodCanvas });
  }

  // Wait for bus.ready (and handle the case where ready fired earlier)
  (function ensureReadyListener(){
    if (window.moodCanvasBus && window.moodCanvasBus.on) {
      window.moodCanvasBus.on('ready', onReady);
      log('listening for bus ready');
    } else {
      var tries = 0, max = 50;
      var t = setInterval(function(){
        tries++;
        if (window.moodCanvasBus && window.moodCanvasBus.on) {
          clearInterval(t);
          window.moodCanvasBus.on('ready', onReady);
          log('bus found after delay, listening for ready');
        } else if (tries >= max) {
          clearInterval(t);
          warn('bus not found — is v1 loaded and emitting window.moodCanvasBus.emit("ready", {controller})?');
          // still try to mount UI when DOM is ready so you at least see the floating button
          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ mountUI({ getState:function(){return {};}, setItemStyle:function(){ warn('no v1 API'); } }); });
          } else {
            mountUI({ getState:function(){return {};}, setItemStyle:function(){ warn('no v1 API'); } });
          }
        }
      }, 100);
    }
  })();
})();
