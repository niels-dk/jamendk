// public/js/dream-sync.js — the offline queue for Dreams, and the one place
// that sends it.
//
// Ideas caught without signal wait in localStorage under "dreamQueue". This
// file owns that queue: pages add to it with DreamSync.add() and ask for it to
// be sent with DreamSync.flush(). It is loaded by the layout (dashboard pages)
// AND by the capture screen, so an idea no longer has to wait for a visit to
// the dashboard before it leaves the phone.
//
// What the previous sync (inside offline-ui.js) got wrong, each of which was
// reproduced against the live site in Nov 2026:
//
//   - A dropped connection half way through deleted the WHOLE queue as soon as
//     one item had gone through. Three ideas waiting, the second one fails:
//     "1 synced", and ideas two and three were gone.
//     → an item leaves the queue only when the server has confirmed THAT item.
//
//   - Two triggers close together (page load + the browser's "online" event,
//     or two open tabs) each read the same queue and sent everything twice.
//     → one run at a time per page, and a short lock shared between tabs.
//       (The server also treats an identical Dream sent again within ten
//       minutes as the one it already has — see api/dreams/store.php.)
//
//   - An expired login. The endpoint did not check for one, answered "ok",
//     and the idea was saved with no owner while the phone deleted its copy.
//     → the server now answers 401; here that stops the run and KEEPS every
//       remaining idea until someone is signed in again.
(function () {
  'use strict';

  var QKEY = 'dreamQueue';
  var LOCK = 'dreamQueueLock';
  var LOCK_MS = 20000;            // a crashed tab cannot block sync for longer
  var ENDPOINT = '/api/dreams/store.php';
  // A weak connection does not fail, it hangs. Give up after this long and
  // leave the idea queued; the server recognises it if it did arrive.
  var TIMEOUT_MS = 8000;

  function load() {
    try {
      var q = JSON.parse(localStorage.getItem(QKEY));
      return Array.isArray(q) ? q : [];
    } catch (e) { return []; }
  }

  function save(q) {
    try {
      if (q.length) localStorage.setItem(QKEY, JSON.stringify(q));
      else localStorage.removeItem(QKEY);
    } catch (e) { /* storage full or blocked: nothing more we can do here */ }
  }

  function newId() {
    return Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
  }

  // Items queued by an older version of the site have no id yet.
  function withIds(q) {
    var changed = false;
    q.forEach(function (it) { if (it && !it.cid) { it.cid = newId(); changed = true; } });
    if (changed) save(q);
    return q;
  }

  function announce(detail) {
    try { window.dispatchEvent(new CustomEvent('dreamsync', { detail: detail })); } catch (e) {}
  }

  /** Put one idea in the queue. Returns the stored item. */
  function add(item) {
    var copy = {};
    Object.keys(item || {}).forEach(function (k) { copy[k] = item[k]; });
    if (!copy.cid) copy.cid = newId();
    var q = load();
    q.push(copy);
    save(q);
    announce({ type: 'queued', left: q.length });
    return copy;
  }

  function count() { return load().length; }

  // Remove exactly one item. The queue is re-read first: the user may have
  // caught another idea while this one was on its way.
  function removeById(cid) {
    var q = load().filter(function (it) { return !it || it.cid !== cid; });
    save(q);
    return q.length;
  }

  function lockTaken() {
    var t = parseInt(localStorage.getItem(LOCK) || '0', 10);
    return t && (Date.now() - t) < LOCK_MS;
  }
  function takeLock()    { try { localStorage.setItem(LOCK, String(Date.now())); } catch (e) {} }
  function releaseLock() { try { localStorage.removeItem(LOCK); } catch (e) {} }

  function toForm(item) {
    var fd = new FormData();
    Object.keys(item).forEach(function (k) {
      if (k === 'cid') return;
      var v = item[k];
      if (Array.isArray(v)) v.forEach(function (x) { fd.append(k + '[]', x); });
      else if (v !== null && v !== undefined) fd.append(k, v);
    });
    return fd;
  }

  // One item → { outcome: 'ok' | 'drop' | 'auth' | 'offline' | 'server', slug }
  function sendOne(item) {
    var opts = { method: 'POST', body: toForm(item), credentials: 'same-origin' };
    var timer = null;
    if (typeof AbortController !== 'undefined') {
      var ac = new AbortController();
      opts.signal = ac.signal;
      timer = setTimeout(function () { ac.abort(); }, TIMEOUT_MS);
    }
    return fetch(ENDPOINT, opts)
      .then(function (res) {
        return res.text().then(function (txt) {
          var json = null;
          try { json = JSON.parse(txt); } catch (e) {}
          if (res.status === 401 || (json && json.error === 'auth')) return { outcome: 'auth' };
          if (res.ok && json && json.ok) return { outcome: 'ok', slug: json.slug || '' };
          // The server understood and said this can never be saved (nothing
          // in it). Keeping it would block everything queued behind it.
          if (res.status === 400 && json && json.ok === false) return { outcome: 'drop' };
          return { outcome: 'server' };   // 5xx, a login page, anything unexpected: keep
        });
      })
      .catch(function () { return { outcome: 'offline' }; })   // no signal, or timed out
      .then(function (r) { if (timer) clearTimeout(timer); return r; });
  }

  var running = null;

  /**
   * Send what is waiting. Resolves with
   *   { synced, dropped, left, reason, lastSlug }
   *       reason: 'done' | 'empty' | 'auth' | 'offline' | 'server' | 'busy'
   *       lastSlug: the Dream the last synced idea became (to link to it)
   * Never rejects.
   */
  function flush() {
    if (running) return running;

    var q = withIds(load());
    if (!q.length) return Promise.resolve({ synced: 0, dropped: 0, left: 0, reason: 'empty' });
    if (lockTaken()) return Promise.resolve({ synced: 0, dropped: 0, left: q.length, reason: 'busy' });

    takeLock();
    var result = { synced: 0, dropped: 0, left: q.length, reason: 'done', lastSlug: '' };
    var order = q.map(function (it) { return it.cid; });

    function next() {
      var cid = order.shift();
      if (!cid) return Promise.resolve();
      var item = load().filter(function (it) { return it && it.cid === cid; })[0];
      if (!item) return next();                 // already gone (another tab)
      takeLock();                               // still alive
      return sendOne(item).then(function (r) {
        if (r.outcome === 'ok') {
          result.synced++; result.lastSlug = r.slug || result.lastSlug;
          result.left = removeById(cid); return next();
        }
        if (r.outcome === 'drop') { result.dropped++; result.left = removeById(cid); return next(); }
        result.reason = r.outcome;              // stop; everything left stays queued
        result.left = load().length;
      });
    }

    running = next()
      .catch(function () { result.reason = 'server'; result.left = load().length; })
      .then(function () {
        releaseLock();
        running = null;
        announce({ type: 'flushed', synced: result.synced, dropped: result.dropped,
                   left: result.left, reason: result.reason, lastSlug: result.lastSlug });
        return result;
      });
    return running;
  }

  window.DreamSync = { add: add, count: count, load: load, flush: flush };
})();
