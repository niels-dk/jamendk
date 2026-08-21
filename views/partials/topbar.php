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
        <a href="/admin/return" title="You are browsing as this user — click to return to your admin account"
           style="display:inline-flex;align-items:center;gap:.35rem;margin-right:.4rem;
                  padding:.3rem .7rem;border-radius:999px;text-decoration:none;
                  background:rgba(232,194,103,.15);border:1px solid rgba(232,194,103,.5);
                  color:#e8c267;font-size:.85em;font-weight:600;">
          👁 Viewing as <?= htmlspecialchars($me['name'] ?: $me['email']) ?> — Return to admin
        </a>
      <?php endif; ?>
      <a class="topbar-user" href="/account" title="My account"
         style="display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;
                color:#cfdbe8;font-size:.95em;margin-right:.4rem;">
        <span style="display:inline-flex;align-items:center;justify-content:center;
                     width:28px;height:28px;border-radius:50%;
                     background:#3a76d2;color:#fff;font-weight:700;font-size:.75rem;">
          <?= htmlspecialchars(strtoupper(substr($me['name'] ?: $me['email'], 0, 1))) ?>
        </span>
        <?= htmlspecialchars($me['name'] ?: $me['email']) ?>
      </a>
      <a class="btn btn-ghost" href="/capture" title="Catch an idea — fast">⚡ <?= te('nav.capture') ?></a>
      <a class="btn btn-ghost" href="/teams" title="My teams">👥 <?= te('nav.teams') ?></a>
      <?php if (function_exists('is_admin') && is_admin()): ?>
        <a class="btn btn-ghost" href="/admin/users" title="User management">⚙️ Users</a>
        <a class="btn btn-ghost" href="/admin/analytics" title="Traffic & usage">📊 Analytics</a>
        <a class="btn btn-ghost" href="/admin/links" title="Tracked links">🔗 Links</a>
        <a class="btn btn-ghost" href="/admin/pricing" title="Shadow revenue">📈 Revenue</a>
      <?php endif; ?>
      <?php
        // Language picker — flag + code, click for the list. Logged-in only
        // for now; anonymous visitors stay on English until translations are
        // reviewed (see I18n::ALLOW_ANON).
        $curLang  = I18n::lang();
        $curMeta  = I18n::LANGUAGES[$curLang] ?? I18n::LANGUAGES['en'];
        $backHere = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/dashboard', ENT_QUOTES);
      ?>
      <div class="lang-picker">
        <button type="button" class="btn btn-ghost lang-toggle"
                aria-haspopup="true" aria-expanded="false"
                title="<?= te('nav.language') ?>">
          <span class="lang-flag"><?= $curMeta['flag'] ?></span>
          <span class="lang-code"><?= htmlspecialchars(strtoupper(explode('-', $curLang)[0])) ?></span>
        </button>
        <div class="lang-menu" hidden>
          <div class="lang-menu-head"><?= te('nav.language') ?></div>
          <?php foreach (I18n::LANGUAGES as $code => $meta): ?>
            <form method="post" action="/account/language">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
              <input type="hidden" name="lang" value="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
              <input type="hidden" name="next" value="<?= $backHere ?>">
              <button type="submit" class="lang-item <?= $code === $curLang ? 'is-current' : '' ?>">
                <span class="lang-flag"><?= $meta['flag'] ?></span>
                <span class="lang-native"><?= htmlspecialchars($meta['native']) ?></span>
                <span class="lang-label"><?= htmlspecialchars($meta['label']) ?></span>
                <?php if ($code === $curLang): ?><span class="lang-check">✓</span><?php endif; ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      </div>
      <a class="btn btn-ghost" href="/logout"><?= te('nav.logout') ?></a>
    <?php else: ?>
      <a class="btn btn-ghost" href="/pricing">Pricing</a>
      <a class="btn btn-ghost" href="/login">Sign in</a>
      <a class="btn btn-primary" href="/register">Create account</a>
    <?php endif; ?>
  </nav>
</header>

<style>
  .lang-picker { position:relative; display:inline-block; }
  .lang-toggle { display:inline-flex; align-items:center; gap:.35rem; }
  .lang-flag { font-size:1.05rem; line-height:1; }
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
