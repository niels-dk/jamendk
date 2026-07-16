<?php
$me = function_exists('current_user') ? current_user() : null;
$loggedIn = (bool)$me;
?>
<header class="home-header">
  <div class="home-brand">
    <a href="/"><div class="logo-mark">
     <?php for ($i = 0; $i < 9; $i++) echo '<span></span>'; ?>
    </div></a>
    <strong class="brand-title">DreamBoard</strong>
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
      <a class="btn btn-ghost" href="/teams" title="My teams">👥 Teams</a>
      <?php if (function_exists('is_admin') && is_admin()): ?>
        <a class="btn btn-ghost" href="/admin/users" title="User management">⚙️ Users</a>
        <a class="btn btn-ghost" href="/admin/pricing" title="Shadow revenue">📈 Revenue</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="/logout">Logout</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="/login">Sign in</a>
      <a class="btn btn-primary" href="/register">Create account</a>
    <?php endif; ?>
  </nav>
</header>
