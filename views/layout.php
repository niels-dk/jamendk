<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>

<header>
  <?php include __DIR__ . '/partials/topbar.php'; ?>
</header>

<?php if (!empty($noSidebar) && $noSidebar === true): ?>
  <!-- Dream or Dashboard layout (no sidebar) -->
  <div class="container">
    <main><?= $content ?? '' ?></main>
  </div>

  <!-- Dream board modal & scripts -->
  <?php include __DIR__ . '/partials/dream-modal.php'; ?>
  <script src="/public/js/mobile-dream.js?v=11"></script>
  <script src="/public/js/offline-ui.js?v=3"></script>
  <script src="/public/js/trix-loader.js?v=1"></script>

<?php else: ?>
  <!-- Sidebar layout -->
  <div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="content">
      <?= $content ?? '' ?>
    </div>
  </div>
<?php endif; ?>

<script>
  // Toggle a menu when its button is clicked
  document.addEventListener('click', e => {
    const toggle = e.target.closest('.menu-toggle');
    if (toggle) {
      const menu = toggle.nextElementSibling;
      if (menu) {
        menu.classList.toggle('open');
      }
      e.stopPropagation();
      return;
    }
    // Close any open menus if you click outside them
    document.querySelectorAll('.card-menu.open').forEach(menu => {
      if (!menu.contains(e.target) && !menu.previousElementSibling.contains(e.target)) {
        menu.classList.remove('open');
      }
    });
  });
</script>


<div id="connectivity-banner"></div>
<div id="snackbar" class="snackbar"></div>

</body>
</html>
