// sw.js — what still works with no signal.
//
// The promise is narrow and this file keeps to it:
//   - the capture screen ALWAYS opens — no signal, weak signal, signed in or
//     not — from the first visit to the site onwards, and saves ideas on the
//     device. Catching an idea must never depend on the network or a login.
//   - a Trip page you have opened once (/t/… or /trips/…) opens again offline
//   - their images (thumbnails) and the scripts they need are kept too
// Everything else needs the network, and says so with a small offline page
// instead of pretending.
//
// Rewritten Nov 2026 after checking what was actually in the cache:
//   - '/dashboard' and '/dreams/new' were pre-cached at install. For anyone
//     who was signed out at that moment (a first visit) the "dashboard" stored
//     was the LOGIN page, reached by redirect — and a redirected response
//     cannot be used to answer a page load at all. It was also never refreshed.
//   - Any response was stored for Trip and capture pages, including an error
//     or a redirect to the login form, overwriting the good copy.
//   - The fallback for every other page was the cached front page: offline,
//     tapping "Dashboard" showed the marketing site.
//   - Every ?v= of every stylesheet and script stayed in the cache forever.

// Unchanged on purpose. This cache holds people's offline Trip pages; a new
// name would throw those away until each page was opened online again.
const CACHE = 'shell-v11';

// Safe to fetch signed in or out, and needed by the capture screen offline.
const STATIC = ['/public/js/dream-sync.js'];

// Entries an older version of this file stored and should not have.
const STALE = ['/dashboard', '/dreams/new'];

const isTripOrCapture = (path) =>
  path.startsWith('/t/') || path.startsWith('/trips/') || path === '/capture';

// A response worth keeping as a page: the real thing, not an error, not the
// result of being bounced to the login form.
const keepable = (res) => res && res.ok && res.type === 'basic' && !res.redirected;

self.addEventListener('install', (e) => {
  e.waitUntil((async () => {
    const cache = await caches.open(CACHE);
    // One at a time and never fatal: a missing file must not block the update.
    for (const url of STATIC) {
      try {
        const res = await fetch(url, { cache: 'reload' });
        if (keepable(res)) await cache.put(url, res);
      } catch (_) { /* offline during install: the next page load fetches it */ }
    }
    // The capture screen, for everyone. It needs no login (see
    // dream_controller::capture), so it can be saved the first time anyone
    // visits any page — not only if they happen to open it while signed in.
    try {
      const res = await fetch('/capture', { credentials: 'same-origin', cache: 'reload' });
      if (keepable(res)) await cache.put('/capture', res);
    } catch (_) {}
  })());
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil((async () => {
    const cache = await caches.open(CACHE);
    for (const url of STALE) await cache.delete(url);
    // Anything else that was stored after a redirect or as an error.
    for (const req of await cache.keys()) {
      const res = await cache.match(req);
      if (res && (res.redirected || !res.ok)) await cache.delete(req);
    }
    await self.clients.claim();
  })());
});

// ── The page shown when a page is neither reachable nor saved ──────────────
const OFFLINE_TEXT = {
  en: { t: "You're offline", p: "This page isn't saved on this device. Trip pages you have opened and the capture screen still work without signal.",
        c: 'Catch an idea', r: 'Try again' },
  da: { t: 'Du er offline', p: 'Denne side er ikke gemt på enheden. Trip-sider, du har åbnet, og fang-skærmen virker stadig uden signal.',
        c: 'Fang en idé', r: 'Prøv igen' },
  pt: { t: 'Você está offline', p: 'Esta página não está salva neste aparelho. As páginas de Trip que você já abriu e a tela de captura continuam funcionando sem sinal.',
        c: 'Capturar uma ideia', r: 'Tentar de novo' },
};

async function offlinePage() {
  const lang = ((self.navigator && self.navigator.language) || 'en').slice(0, 2).toLowerCase();
  const T = OFFLINE_TEXT[lang] || OFFLINE_TEXT.en;
  const hasCapture = !!(await caches.match('/capture', { ignoreSearch: true }));
  const html = `<!doctype html>
<html lang="${lang}"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${T.t} — Merely a Dream</title>
<style>
  html,body{margin:0;height:100%}
  body{background:#0b0f17;color:#eaf0f7;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
       display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box}
  main{max-width:26rem;text-align:center}
  h1{font-size:1.6rem;margin:0 0 .6rem}
  p{color:#9bb0c5;margin:0 0 1.6rem}
  a,button{display:block;width:100%;box-sizing:border-box;margin:0 0 .6rem;padding:.8rem 1rem;border-radius:12px;
           font:inherit;font-weight:600;text-decoration:none;cursor:pointer}
  a{background:#3a76d2;color:#fff;border:1px solid #3a76d2}
  button{background:transparent;color:#cfdbe8;border:1px solid #2b3346}
</style></head>
<body><main>
  <h1>${T.t}</h1>
  <p>${T.p}</p>
  ${hasCapture ? `<a href="/capture">${T.c}</a>` : ''}
  <button type="button" onclick="location.reload()">${T.r}</button>
</main></body></html>`;
  return new Response(html, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

// How long a page load may wait on the network before a saved copy is shown
// instead. A weak connection does not fail, it hangs — and a shot list that
// takes a minute to appear on a roadside is as good as missing.
const SLOW_MS = 4000;

// The capture screen: saved copy first, refreshed in the background. It holds
// nothing that changes between visits, so there is no reason to make the one
// screen that must open instantly wait for the network.
async function capturePage(e, req) {
  const cache = await caches.open(CACHE);
  const saved = await cache.match('/capture', { ignoreSearch: true });
  const fresh = fetch(req).then((res) => {
    if (keepable(res)) cache.put('/capture', res.clone());
    return res;
  });
  if (saved && !saved.redirected) {
    e.waitUntil(fresh.catch(() => {}));
    return saved;
  }
  try { return await fresh; } catch (_) { return offlinePage(); }
}

// Every other page: network first. A Trip page that is already saved is shown
// from the device if the network has not answered within SLOW_MS (the request
// carries on and refreshes the saved copy for next time).
async function page(e, req, url) {
  const trip = isTripOrCapture(url.pathname);
  const found = await caches.match(req, { ignoreSearch: true });
  const saved = found && !found.redirected ? found : null;

  const net = fetch(req).then(async (res) => {
    if (trip && keepable(res)) {
      const cache = await caches.open(CACHE);
      await cache.put(req, res.clone());
    }
    return res;
  });

  if (!saved) {
    try { return await net; } catch (_) { return offlinePage(); }
  }
  if (!trip) {
    try { return await net; } catch (_) { return saved; }
  }
  try {
    const first = await Promise.race([
      net,
      new Promise((resolve) => setTimeout(() => resolve(null), SLOW_MS)),
    ]);
    if (first) return first;
    e.waitUntil(net.catch(() => {}));
    return saved;
  } catch (_) {
    return saved;
  }
}

// After storing a stylesheet or script, drop the older ?v= copies of it.
async function pruneVersions(cache, url) {
  for (const req of await cache.keys()) {
    const u = new URL(req.url);
    if (u.pathname === url.pathname && u.search !== url.search) await cache.delete(req);
  }
}

self.addEventListener('fetch', (e) => {
  const req = e.request;
  const url = new URL(req.url);

  // 0) Trix from the CDN (v2.1.15) is answered with nothing: the editor is
  //    loaded from another path where it is needed, and this request must
  //    never hang a page that is offline.
  if (url.hostname === 'unpkg.com' && url.pathname.includes('/trix@2.1.15/dist/')) {
    const ct = url.pathname.endsWith('.css') ? 'text/css' : 'application/javascript';
    e.respondWith(new Response('', { headers: { 'Content-Type': ct } }));
    return;
  }

  // 1) Pages. The capture screen comes from the device; everything else from
  //    the network, with the saved copy (Trip pages) or the offline page as
  //    the fallback. Trip pages are stored on every GOOD load.
  if (req.mode === 'navigate') {
    e.respondWith(url.pathname === '/capture' ? capturePage(e, req) : page(e, req, url));
    return;
  }

  if (req.method !== 'GET' || url.origin !== location.origin) return;

  // 1b) Thumbnails (mood refs, canvas images): cache first, so reference
  //     images on a saved Trip page render offline too.
  if (url.pathname.startsWith('/storage/thumbs/')) {
    e.respondWith((async () => {
      const cached = await caches.match(req);
      if (cached) return cached;
      try {
        const netRes = await fetch(req);
        if (netRes.ok) {
          const cache = await caches.open(CACHE);
          cache.put(req, netRes.clone());
        }
        return netRes;
      } catch (_err) {
        return new Response('', { status: 504 });
      }
    })());
    return;
  }

  // 2) Stylesheets, scripts, icons: network first, the saved copy when the
  //    network fails — any version of it rather than none.
  if (url.pathname.endsWith('.css') || url.pathname.endsWith('.js') || url.pathname.endsWith('.ico')) {
    e.respondWith((async () => {
      try {
        const netRes = await fetch(req);
        if (netRes.ok) {
          const cache = await caches.open(CACHE);
          await cache.put(req, netRes.clone());
          pruneVersions(cache, url);
        }
        return netRes;
      } catch (_err) {
        return (await caches.match(req))
            || (await caches.match(req, { ignoreSearch: true }))
            || new Response('', { status: 504 });
      }
    })());
    return;
  }

  // 3) everything else: straight to the network
});
