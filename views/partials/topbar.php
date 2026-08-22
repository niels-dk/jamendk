<?php
$me = function_exists('current_user') ? current_user() : null;
$loggedIn = (bool)$me;
// One place decides the product name everywhere (see SITE_NAME in config).
$brandName = defined('SITE_NAME') ? SITE_NAME : 'Merely a Dream';
?>
<header class="home-header">
  <div class="home-brand">
    <a href="/" class="brand-link" title="<?= htmlspecialchars($brandName, ENT_QUOTES) ?>">
      <div class="logo-mark">
       <?php for ($i = 0; $i < 9; $i++) echo '<span></span>'; ?>
      </div>
      <strong class="brand-title"><?= htmlspecialchars($brandName, ENT_QUOTES) ?></strong>
    </a>
  </div>

  <nav class="home-actions">
    <?php if ($loggedIn): ?>
      <?php if (!empty($_SESSION['impersonator_id'])): ?>
        <a href="/admin/return" title="<?= te('nav.viewing_as_tip') ?>"
           style="display:inline-flex;align-items:center;gap:.35rem;margin-right:.4rem;
                  padding:.3rem .7rem;border-radius:999px;text-decoration:none;
                  background:rgba(232,194,103,.15);border:1px solid rgba(232,194,103,.5);
                  color:#e8c267;font-size:.85em;font-weight:600;">
          👁 <?= te('nav.viewing_as', ['name' => $me['name'] ?: $me['email']]) ?>
        </a>
      <?php endif; ?>
      <a class="topbar-user" href="/account" title="<?= te('nav.my_account') ?>"
         style="display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;
                color:#cfdbe8;font-size:.95em;margin-right:.4rem;">
        <span style="display:inline-flex;align-items:center;justify-content:center;
                     width:28px;height:28px;border-radius:50%;
                     background:#3a76d2;color:#fff;font-weight:700;font-size:.75rem;">
          <?= htmlspecialchars(strtoupper(substr($me['name'] ?: $me['email'], 0, 1))) ?>
        </span>
        <?= htmlspecialchars($me['name'] ?: $me['email']) ?>
      </a>
      <a class="btn btn-ghost" href="/capture" title="<?= te('nav.capture_tip') ?>">⚡ <?= te('nav.capture') ?></a>
      <a class="btn btn-ghost" href="/teams" title="<?= te('teams.im_on') ?>">👥 <?= te('nav.teams') ?></a>
      <?php if (function_exists('is_admin') && is_admin()): ?>
        <a class="btn btn-ghost" href="/admin/users" title="<?= te('adm.users_sub') ?>">⚙️ <?= te('nav.users') ?></a>
        <a class="btn btn-ghost" href="/admin/analytics" title="<?= te('adm.analytics_sub') ?>">📊 <?= te('nav.analytics') ?></a>
        <a class="btn btn-ghost" href="/admin/links" title="<?= te('adm.links_sub') ?>">🔗 <?= te('nav.links') ?></a>
        <a class="btn btn-ghost" href="/admin/pricing" title="<?= te('adm.revenue') ?>">📈 <?= te('nav.revenue') ?></a>
      <?php endif; ?>
      <?php include __DIR__ . '/lang-picker.php'; ?>
      <a class="btn btn-ghost" href="/logout"><?= te('nav.logout') ?></a>
    <?php else: ?>
      <a class="btn btn-ghost" href="/pricing"><?= te('nav.pricing') ?></a>
      <?php include __DIR__ . '/lang-picker.php'; ?>
      <a class="btn btn-ghost" href="/login"><?= te('nav.signin') ?></a>
      <a class="btn btn-primary" href="/register"><?= te('nav.create_account') ?></a>
    <?php endif; ?>
  </nav>
</header>

<style>
  .lang-picker { position:relative; display:inline-block; }
  .lang-toggle { display:inline-flex; align-items:center; gap:.35rem; }
  .lang-flag { display:block; flex-shrink:0; border-radius:50%; }
  .lang-code { font-size:.82rem; font-weight:700; letter-spacing:.03em; }
  .lang-menu {
    position:absolute; right:0; top:calc(100% + .4rem); z-index:200;
    min-width:220px; padding:.4rem;
    background:#12161f; border:1px solid #2b3346; border-radius:12px;
    box-shadow:0 12px 32px rgba(0,0,0,.5);
  }
  .lang-menu[hidden] { display:none; }
  .lang-menu-head {
    font-size:.68rem; text-transform:uppercase; letter-spacing:.08em;
    color:#6c7d92; font-weight:700; padding:.35rem .6rem .45rem;
  }
  .lang-menu form { margin:0; }
  .lang-item {
    display:flex; align-items:center; gap:.6rem; width:100%;
    padding:.5rem .6rem; border:0; border-radius:8px; cursor:pointer;
    background:transparent; color:#cfdbe8; font:inherit; font-size:.9rem;
    text-align:left;
  }
  .lang-item:hover { background:rgba(255,255,255,.07); }
  .lang-item.is-current { color:#eaf0f7; font-weight:600; }
  .lang-native { flex:1; }
  .lang-label  { color:#6c7d92; font-size:.78rem; }
  .lang-check  { color:#7fc98d; font-weight:700; }
</style>
<script>
(function () {
  var picker = document.querySelector('.lang-picker');
  if (!picker) return;
  var btn  = picker.querySelector('.lang-toggle');
  var menu = picker.querySelector('.lang-menu');
  function close() { menu.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    menu.hidden = !menu.hidden;
    btn.setAttribute('aria-expanded', menu.hidden ? 'false' : 'true');
  });
  document.addEventListener('click', function (e) {
    if (!picker.contains(e.target)) close();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') close();
  });
})();
</script>
