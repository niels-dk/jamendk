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
      <span class="topbar-user" style="display:inline-flex;align-items:center;gap:.4rem;
                                       color:#cfdbe8;font-size:.95em;margin-right:.4rem;">
        <span style="display:inline-flex;align-items:center;justify-content:center;
                     width:28px;height:28px;border-radius:50%;
                     background:#3a76d2;color:#fff;font-weight:700;font-size:.75rem;">
          <?= htmlspecialchars(strtoupper(substr($me['name'] ?: $me['email'], 0, 1))) ?>
        </span>
        <?= htmlspecialchars($me['name'] ?: $me['email']) ?>
      </span>
      <?php if (function_exists('is_admin') && is_admin()): ?>
        <a class="btn btn-ghost" href="/admin/users" title="User management">⚙️ Users</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="/logout">Logout</a>
    <?php else: ?>
      <a class="btn btn-primary" href="/login">Login</a>
      <a class="btn btn-ghost" href="/register">+ New Creator</a>
    <?php endif; ?>
  </nav>
</header>
