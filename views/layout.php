<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>

<header>
  <?php include __DIR__ . '/partials/topbar.php'; ?>
</header>

<?php
// Decide between Dream simple layout or Sidebar layout
if (!empty($noSidebar) && $noSidebar === true): ?>
  <!-- SIMPLE LAYOUT (Dream board) -->
  <div class="container">
    <main><?= $content ?? '' ?></main>
  </div>

<?php else: ?>
  <!-- SIDEBAR LAYOUT -->
  <div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="content">
      <?= $content ?? '' ?>
    </div>
  </div>
<?php endif; ?>

<!-- GLOBAL SCRIPTS -->
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

<?php if (!empty($noSidebar) && $noSidebar === true): ?>
  <!-- DREAM BOARD SCRIPTS & UI -->
  <?php include __DIR__ . '/partials/dream-modal.php'; ?>
  <script src="/public/js/mobile-dream.js?v=11"></script>
  <script src="/public/js/offline-ui.js?v=3"></script>
  <script src="/public/js/trix-loader.js?v=1"></script>
<?php endif; ?>

<div id="connectivity-banner"></div>
<div id="snackbar" class="snackbar"></div>

</body>
</html>
