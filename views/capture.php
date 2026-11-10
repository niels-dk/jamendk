<?php
// views/capture.php — the capture-first screen. Echoed standalone (no layout):
// this page's whole job is to appear instantly and take one thought.
// It is what the installed app opens to (manifest start_url).
function cp_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?= htmlspecialchars(I18n::lang(), ENT_QUOTES) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= te('capture.title') ?> — Merely a Dream</title>
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
    /* Ideas saved on this device that the server does not have yet. */
    .pending {
      display: none; text-align: center; font-size: .8rem; color: #e8c267;
      padding: 8px 2px 0;
    }
    .pending.show { display: block; }
    .pending a { color: #8fb1d8; }
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
    <a class="brand" href="/dashboard">Merely a Dream<span class="dot">.</span></a>
    <span class="offline-pill"><?= te('capture.offline_pill') ?></span>
    <span class="count" id="count"></span>
  </header>

  <main>
    <textarea id="idea" autofocus autocomplete="off" autocapitalize="sentences"
      placeholder="<?= te('capture.placeholder') ?>"></textarea>
    <div class="hint"><?= te('capture.hint') ?></div>
    <div class="bar">
      <button class="catch" id="btnCatch" type="button">⚡ <?= te('capture.button') ?></button>
    </div>
    <div class="pending" id="pending"></div>
    <div class="links">
      <a href="/dashboard"><?= te('nav.dashboard') ?></a>
      <a href="/dashboard/dream"><?= te('capture.my_dreams') ?></a>
    </div>
  </main>

  <div id="snack"></div>

<!-- The offline queue and the one place that sends it. If this file cannot be
     loaded (first ever visit with no signal), the code below falls back to
     writing the same storage format, and the dashboard will send it later. -->
<script src="/public/js/dream-sync.js?v=1"></script>
<script>
(function () {
  var ta    = document.getElementById('idea');
  var btn   = document.getElementById('btnCatch');
  var snack = document.getElementById('snack');
  var count = document.getElementById('count');
  var QKEY  = 'dreamQueue';          // the queue dream-sync.js owns and sends
  var T = {
    caught:      <?= json_encode(t('capture.caught')) ?>,
    caughtOpen:  <?= json_encode(t('capture.caught_open')) ?>,
    offline:     <?= json_encode(t('capture.offline')) ?>,
    counter:     <?= json_encode(t('capture.counter')) ?>,
    waitingOne:  <?= json_encode(t('capture.waiting_one')) ?>,
    waitingMany: <?= json_encode(t('capture.waiting_many')) ?>,
    signIn:      <?= json_encode(t('capture.signin_to_sync')) ?>,
    syncedOne:   <?= json_encode(t('js.synced_one')) ?>,
    syncedMany:  <?= json_encode(t('js.synced_many')) ?>
  };
  var pending = document.getElementById('pending');
  var DS = window.DreamSync;
  var caught = 0;
  var snackTimer;

  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');

  function setNet() {
    document.body.classList.toggle('is-offline', !navigator.onLine);
  }
  setNet();
  window.addEventListener('online', function () { setNet(); syncQueue(); });
  window.addEventListener('offline', setNet);

  function showSnack(html, ms) {
    clearTimeout(snackTimer);
    snack.innerHTML = html;
    snack.classList.add('show');
    snackTimer = setTimeout(function () { snack.classList.remove('show'); }, ms || 2200);
  }

  function bump() {
    caught++;
    count.textContent = T.counter.replace(':n', caught);
  }

  function split(text) {
    var nl = text.indexOf('\n');
    if (nl === -1) return { title: text.trim(), description: '' };
    return { title: text.slice(0, nl).trim(), description: text.slice(nl + 1).trim() };
  }

  function queueLocal(p) {
    if (DS) { DS.add({ title: p.title, description: p.description }); }
    else {
      var q;
      try { q = JSON.parse(localStorage.getItem(QKEY)) || []; } catch (e) { q = []; }
      q.push({ title: p.title, description: p.description });
      localStorage.setItem(QKEY, JSON.stringify(q));
    }
    showPending();
  }

  function queued() {
    if (DS) return DS.count();
    try { return (JSON.parse(localStorage.getItem(QKEY)) || []).length; } catch (e) { return 0; }
  }

  // "2 waiting to sync" — and, when the reason is an expired login, the way
  // out. Without this the screen said "syncs when you're back" and then never
  // mentioned those ideas again.
  function showPending(needSignIn) {
    var n = queued();
    if (!n) { pending.className = 'pending'; pending.textContent = ''; return; }
    pending.textContent = n === 1 ? T.waitingOne : T.waitingMany.replace(':n', n);
    if (needSignIn) {
      var a = document.createElement('a');
      a.href = '/login?next=' + encodeURIComponent('/capture');
      a.textContent = T.signIn;
      pending.appendChild(document.createTextNode(' · '));
      pending.appendChild(a);
    }
    pending.className = 'pending show';
  }

  // Send what is waiting. This screen is what the installed app opens to, so
  // it cannot rely on the dashboard being visited to get ideas off the phone.
  function syncQueue() {
    if (!DS || !navigator.onLine || !queued()) { showPending(); return; }
    DS.flush().then(function (r) {
      if (r.synced > 0) {
        showSnack('✓ ' + (r.synced === 1 ? T.syncedOne : T.syncedMany.replace(':n', r.synced)));
      }
      showPending(r.reason === 'auth');
    });
  }

  function openLink(slug) {
    return slug
      ? '⚡ <a href="/dreams/' + encodeURIComponent(slug) + '">' + T.caughtOpen + '</a>'
      : '⚡ ' + T.caught;
  }

  // What to tell the person once a send attempt has finished.
  function afterSend(r, rounds) {
    // Ideas caught while that run was in flight are still waiting: go again.
    if (r.reason === 'done' && r.left > 0 && (rounds || 0) < 5) {
      return DS.flush().then(function (r2) {
        r2.synced += r.synced; r2.lastSlug = r2.lastSlug || r.lastSlug;
        return afterSend(r2, (rounds || 0) + 1);
      });
    }
    if (r.reason === 'auth') {
      // The login expired. Nothing is lost — it is all on this device and is
      // sent as soon as this screen opens again after signing in.
      showPending(true);
      location.href = '/login?next=' + encodeURIComponent('/capture');
      return;
    }
    if (r.left === 0 && r.synced > 0) showSnack(openLink(r.lastSlug));
    else if (r.left > 0)              showSnack('⚡ ' + T.offline);
    showPending(false);
  }

  // The contract of this screen: the idea is SAFE the moment you hit the
  // button. So it is written to the device first and sent second — the box
  // clears at once and the button never waits on the network. (It used to
  // send first and only fall back to the device when the request FAILED; on a
  // weak connection a request does not fail, it hangs, with the idea still
  // sitting unsaved in the box.)
  function catchIt() {
    var text = ta.value.trim();
    if (!text) { ta.focus(); return; }
    var p = split(text);

    queueLocal(p);
    ta.value = ''; bump(); ta.focus();

    if (!DS || !navigator.onLine) {
      showSnack('⚡ ' + T.offline);
      return;
    }
    DS.flush().then(function (r) { return afterSend(r, 0); });
  }

  btn.addEventListener('click', catchIt);
  syncQueue();   // anything left over from the last time there was no signal
  // A weak connection fails requests without the browser ever firing
  // "online" (it never thought it was offline), so also try once a minute.
  setInterval(function () { if (queued()) syncQueue(); }, 60000);
  ta.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); catchIt(); }
  });
})();
</script>
</body>
</html>
