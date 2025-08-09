<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $title ?? 'Jamen' ?></title>

  <meta name="color-scheme" content="dark light">
  <meta name="theme-color" content="#1a1b1e">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js');
    }
  </script>

  <link rel="stylesheet" href="/public/css/style.css?v=9">
</head>
<body>

<header>
  <div class="container">
    <a href="/" style="font-weight:600;font-size:1.05rem">Jamen</a>
    <nav>
      <a href="/dashboard">Dashboard</a>
      <a href="/dreams/new" class="btn" style="margin-left:.8rem">+ New Dream</a>
    </nav>
  </div>
</header>

<div class="container">
  <main>
    <?= $content ?? '' ?>
  </main>
</div>

<script>
  document.addEventListener('click', e => {
    if (e.target.closest('.menu-toggle')) {
      const menuContainer = e.target.closest('.card-menu');
      menuContainer.classList.toggle('open');
      return;
    }
    document.querySelectorAll('.card-menu.open').forEach(c => {
      if (!c.contains(e.target)) c.classList.remove('open');
    });
  });
</script>

<button id="fabNewDream" class="fab" aria-label="New Dream">＋</button>

<div id="dreamModal" class="modal-hidden">
  <div class="modal-content">
    <button id="closeModal" class="modal-close" aria-label="Close">✕</button>
    <form id="dreamForm">
      <input name="title" type="text" placeholder="Dream title" required autofocus>

      <label>Description</label>
      <textarea name="description" rows="4" placeholder="Describe your dream…"></textarea>

      <div class="anchors-mobile">
        <div class="anchor-group" data-anchor="locations">
          <label>Locations</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="locations">＋</button>
        </div>
        <div class="anchor-group" data-anchor="brands">
          <label>Brands</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="brands">＋</button>
        </div>
        <div class="anchor-group" data-anchor="people">
          <label>People</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="people">＋</button>
        </div>
        <div class="anchor-group" data-anchor="seasons">
          <label>Seasons</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="seasons">＋</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Save Dream</button>
    </form>
  </div>
</div>

<script src="/public/js/mobile-dream.js?v=11"></script>
<script src="/public/js/offline-ui.js?v=3"></script>

<script>
  if (!navigator.onLine) {
    // alert('You are offline');
  }

  function loadTrix() {
    if (document.querySelector('link[data-trix]')) return;
    const l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = 'https://unpkg.com/trix@2.1.15/dist/trix.css';
    l.setAttribute('data-trix', '');
    document.head.appendChild(l);

    const s = document.createElement('script');
    s.defer = true;
    s.src = 'https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js';
    s.setAttribute('data-trix', '');
    document.body.appendChild(s);
  }

  if (navigator.onLine) loadTrix();
  window.addEventListener('online', () => {
    console.info('Back online — injecting Trix editor assets');
    loadTrix();
  });
</script>

<div id="connectivity-banner"></div>
<div id="snackbar" class="snackbar"></div>

</body>
</html>
