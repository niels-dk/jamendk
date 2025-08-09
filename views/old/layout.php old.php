<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $title ?? 'Jamen' ?></title>

    <!--  DARK-MODE DEFAULT  -->
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

	<main>
		<?php include __DIR__ . '/' . $view . '.php'; ?>
	</main>
<div class="container">
    <?php /*  Main view content injected here  */ ?>
    <?= $content ?>
</div>

<script>
document.addEventListener('click', e => {
  // Toggle menu when clicking on the ⋮ button
  if (e.target.closest('.menu-toggle')) {
    const menuContainer = e.target.closest('.card-menu');
    menuContainer.classList.toggle('open');
    return;
  }
  // Close all open menus when clicking outside
  document.querySelectorAll('.card-menu.open').forEach(c => {
    if (!c.contains(e.target)) c.classList.remove('open');
  });
});
</script>
	
<!-- FAB to open Dream modal -->
<button id="fabNewDream" class="fab" aria-label="New Dream">＋</button>

<!-- Full-screen Dream modal -->
<div id="dreamModal" class="modal-hidden">
  <div class="modal-content">
    <button id="closeModal" class="modal-close" aria-label="Close">✕</button>
    <form id="dreamForm">
      <input name="title" type="text"
             placeholder="Dream title" required autofocus>

      <label>Description</label>
      <textarea name="description" rows="4"
                placeholder="Describe your dream…"></textarea>

      <div class="anchors-mobile">
        <!-- Locations -->
        <div class="anchor-group" data-anchor="locations">
          <label>Locations</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="locations">＋</button>
        </div>
        <!-- Brands -->
        <div class="anchor-group" data-anchor="brands">
          <label>Brands</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="brands">＋</button>
        </div>
        <!-- People -->
        <div class="anchor-group" data-anchor="people">
          <label>People</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="people">＋</button>
        </div>
        <!-- Seasons -->
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
  // 1) Test your connection state:
  if (!navigator.onLine) {
    //	alert('You are offline — navigator.onLine=' + navigator.onLine);
  }

  // 2) Function to inject Trix assets
  function loadTrix() {
    if (document.querySelector('link[data-trix]')) return; 
    // CSS
    const l = document.createElement('link');
    l.rel       = 'stylesheet';
    l.href      = 'https://unpkg.com/trix@2.1.15/dist/trix.css';
    l.setAttribute('data-trix', '');
    document.head.appendChild(l);
    // JS
    const s = document.createElement('script');
    s.defer     = true;
    s.src       = 'https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js';
    s.setAttribute('data-trix', '');
    document.body.appendChild(s);
  }

  // 3) If we’re online right now, load Trix immediately
  if (navigator.onLine) {
    loadTrix();
  }

  // 4) When we come back online later, load Trix then too
  window.addEventListener('online', () => {
    console.info('Back online — injecting Trix editor assets');
    loadTrix();
  });
</script>


	
	<!-- Connectivity banner -->
	<div id="connectivity-banner"></div>

	<!-- Snackbar container -->
	<div id="snackbar" class="snackbar"></div>

</body>
</html>
