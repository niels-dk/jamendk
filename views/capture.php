<?php
// views/capture.php — the capture-first screen. Echoed standalone (no layout):
// this page's whole job is to appear instantly and take one thought.
// It is what the installed app opens to (manifest start_url).
function cp_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Catch it — DreamBoard</title>
  <meta name="theme-color" content="#1a1b1e">
  <link rel="manifest" href="/public/manifest.json">
  <link rel="icon" type="image/png" sizes="192x192" href="/public/icons/icon-192.png">
  <link rel="apple-touch-icon" href="/public/icons/apple-touch-icon.png">
  <style>
    * { box-sizing: border-box; }
    html, body { margin: 0; height: 100%; }
    body {
      background: #1a1b1e; color: #eaf0f7;
      font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex; flex-direction: column;
      padding: max(env(safe-area-inset-top), 14px) 16px max(env(safe-area-inset-bottom), 14px);
    }
    header {
      display: flex; align-items: baseline; justify-content: space-between;
      padding: 4px 2px 14px;
    }
    .brand { font-weight: 800; font-size: 1rem; color: #f0f4fa; text-decoration: none; }
    .brand .dot { color: #e8b04a; }
    .count { font-size: .8rem; color: #6c7d92; }
    main { flex: 1; display: flex; flex-direction: column; min-height: 0; }
    textarea {
      flex: 1; width: 100%; resize: none;
      background: transparent; border: 0; outline: none;
      color: #eaf0f7; caret-color: #e8b04a;
      font: 500 clamp(1.25rem, 4.5vw, 1.7rem)/1.45 inherit;
      padding: 6px 2px;
    }
    textarea::placeholder { color: #4a5568; }
    .hint { font-size: .78rem; color: #4f5d70; padding: 2px; }
    .bar {
      display: flex; gap: .6rem; align-items: center; padding-top: 12px;
    }
    button.catch {
      flex: 1; padding: 15px; border: 0; border-radius: 14px;
      background: #3a76d2; color: #fff; font: 700 1.05rem inherit;
      cursor: pointer; transition: transform .08s, background .12s;
    }
    button.catch:active { transform: scale(.98); }
    button.catch:disabled { opacity: .5; }
    .links {
      display: flex; gap: 1.2rem; justify-content: center;
      padding-top: 14px; font-size: .85rem;
    }
    .links a { color: #8fb1d8; text-decoration: none; }
    .offline-pill {
      display: none; font-size: .72rem; font-weight: 700;
      background: rgba(232,176,74,.15); color: #e8c267;
      border-radius: 999px; padding: .2rem .6rem;
    }
    body.is-offline .offline-pill { display: inline-block; }
    #snack {
      position: fixed; left: 50%; bottom: calc(max(env(safe-area-inset-bottom), 14px) + 84px);
      transform: translateX(-50%) translateY(20px);
      background: #2b3346; color: #eaf0f7; border-radius: 999px;
      padding: .55rem 1.1rem; font-size: .9rem; white-space: nowrap;
      opacity: 0; pointer-events: none; transition: opacity .18s, transform .18s;
      box-shadow: 0 8px 24px rgba(0,0,0,.4);
    }
    #snack.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    #snack a { color: #8fb1d8; }
  </style>
</head>
<body>
  <header>
    <a class="brand" href="/dashboard">DreamBoard<span class="dot">.</span></a>
    <span class="offline-pill">offline — still catching</span>
    <span class="count" id="count"></span>
  </header>

  <main>
    <textarea id="idea" autofocus autocomplete="off" autocapitalize="sentences"
      placeholder="What's the idea?&#10;&#10;“Sunrise drone over the red dunes, truck tiny in frame”"></textarea>
    <div class="hint">First line becomes the title. Enter catches it — Shift+Enter for a new line.</div>
    <div class="bar">
      <button class="catch" id="btnCatch" type="button">⚡ Catch it</button>
    </div>
    <div class="links">
      <a href="/dashboard">Dashboard</a>
      <a href="/dashboard/dream">My dreams</a>
    </div>
  </main>

  <div id="snack"></div>

<script>
(function () {
  var ta    = document.getElementById('idea');
  var btn   = document.getElementById('btnCatch');
  var snack = document.getElementById('snack');
  var count = document.getElementById('count');
  var QKEY  = 'dreamQueue';          // same queue offline-ui.js already syncs
  var caught = 0;
  var snackTimer;

  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');

  function setNet() {
    document.body.classList.toggle('is-offline', !navigator.onLine);
  }
  setNet();
  window.addEventListener('online', setNet);
  window.addEventListener('offline', setNet);

  function showSnack(html, ms) {
    clearTimeout(snackTimer);
    snack.innerHTML = html;
    snack.classList.add('show');
    snackTimer = setTimeout(function () { snack.classList.remove('show'); }, ms || 2200);
  }

  function bump() {
    caught++;
    count.textContent = caught + ' caught';
  }

  function split(text) {
    var nl = text.indexOf('\n');
    if (nl === -1) return { title: text.trim(), description: '' };
    return { title: text.slice(0, nl).trim(), description: text.slice(nl + 1).trim() };
  }

  function queueLocal(p) {
    var q;
    try { q = JSON.parse(localStorage.getItem(QKEY)) || []; } catch (e) { q = []; }
    q.push({ title: p.title, description: p.description });
    localStorage.setItem(QKEY, JSON.stringify(q));
  }

  var saving = false;
  function catchIt() {
    if (saving) return;
    var text = ta.value.trim();
    if (!text) { ta.focus(); return; }
    var p = split(text);

    // The contract of this screen: the idea is SAFE the moment you hit the
    // button. Offline (or any network failure) → the local queue that
    // offline-ui.js already syncs when you're back.
    if (!navigator.onLine) {
      queueLocal(p);
      ta.value = ''; bump();
      showSnack('⚡ Caught — saved on this phone, syncs when you\'re back');
      ta.focus();
      return;
    }

    saving = true; btn.disabled = true;
    var body = new URLSearchParams();
    body.set('title', p.title);
    body.set('description', p.description);
    fetch('/api/capture', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    }).then(function (r) { return r.json(); }).then(function (j) {
      saving = false; btn.disabled = false;
      if (j && j.ok) {
        ta.value = ''; bump();
        showSnack(j.slug
          ? '⚡ Caught — <a href="/dreams/' + j.slug + '">open it</a> or keep going'
          : '⚡ Caught');
      } else if (j && j.error === 'auth') {
        location.href = '/login?next=' + encodeURIComponent('/capture');
      } else {
        queueLocal(p); ta.value = ''; bump();
        showSnack('⚡ Caught — will sync shortly');
      }
      ta.focus();
    }).catch(function () {
      saving = false; btn.disabled = false;
      queueLocal(p); ta.value = ''; bump();
      showSnack('⚡ Caught — saved on this phone, syncs when you\'re back');
      ta.focus();
    });
  }

  btn.addEventListener('click', catchIt);
  ta.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); catchIt(); }
  });
})();
</script>
</body>
</html>
