// sw.js

const CACHE = 'shell-v10';
const SHELL_URLS = [
  '/', 
  '/dashboard', 
  '/dreams/new'
];

self.addEventListener('install', e => {
  // cache only the HTML shell
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL_URLS)));
  self.skipWaiting();
});

self.addEventListener('activate', e =>
  e.waitUntil(self.clients.claim())
);

self.addEventListener('fetch', evt => {
  const url = new URL(evt.request.url);

  // 0) Stub out any Trix CDN requests with an empty response
  if (url.hostname === 'unpkg.com' && url.pathname.includes('/trix@2.1.15/dist/')) {
    const isCss = url.pathname.endsWith('.css');
    const ct    = isCss ? 'text/css' : 'application/javascript';
    evt.respondWith(new Response('', { headers: { 'Content-Type': ct }}));
    return;
  }

  // … your existing fetch logic below …
  // 1) HTML navigations, 2) your CSS/JS caching, etc.
});


self.addEventListener('fetch', e => {
  const req = e.request;
  const url = new URL(req.url);

  // 1) HTML navigations: network-first. Trip pages (/t/… and /trips/…) are
  //    cached on every successful load so the shot list still opens in the
  //    field with no signal; anything else falls back to the app shell.
  if (req.mode === 'navigate') {
    e.respondWith((async () => {
      try {
        const netRes = await fetch(req);
        if (url.pathname.startsWith('/t/') || url.pathname.startsWith('/trips/')) {
          const cache = await caches.open(CACHE);
          cache.put(req, netRes.clone());
        }
        return netRes;
      } catch (_err) {
        return (await caches.match(req, { ignoreSearch: true }))
            || (await caches.match('/', { ignoreSearch: true }))
            || new Response('Offline', { status: 503 });
      }
    })());
    return;
  }

  // 1b) Thumbnails (mood refs, canvas images): cache-first so reference
  //     images on a cached trip page render offline too.
  if (req.method === 'GET' && url.origin === location.origin &&
      url.pathname.startsWith('/storage/thumbs/')) {
    e.respondWith((async () => {
      const cached = await caches.match(req);
      if (cached) return cached;
      try {
        const netRes = await fetch(req);
        const cache = await caches.open(CACHE);
        cache.put(req, netRes.clone());
        return netRes;
      } catch (_err) {
        return new Response('', { status: 504 });
      }
    })());
    return;
  }

  // 2) CSS / JS / ICO from your origin: network‐first + cache fallback
  if (req.method === 'GET' && url.origin === location.origin &&
     (url.pathname.endsWith('.css') ||
      url.pathname.endsWith('.js')  ||
      url.pathname.endsWith('.ico'))) {
    e.respondWith((async () => {
      try {
        const netRes = await fetch(req);
        // save a copy in cache
        const cache = await caches.open(CACHE);
        cache.put(req, netRes.clone());
        return netRes;
      } catch (_err) {
        // if network fails, try cache
        const cached = await caches.match(req);
        return cached || new Response('', { status: 504 });
      }
    })());
    return;
  }

  // 3) everything else: go to network
});
