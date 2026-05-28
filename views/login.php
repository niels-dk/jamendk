<?php
$title = 'Sign in';
ob_start();
?>
<div style="max-width:420px;margin:3rem auto;padding:0 1rem;">
  <h1 style="font-size:1.8rem;margin:0 0 1.2rem;">Welcome back</h1>

  <?php if (!empty($error)): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.4);
                color:#f3b3b3;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" style="display:flex;flex-direction:column;gap:.8rem;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <?php if (!empty($next)): ?>
      <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
    <?php endif; ?>

    <label style="display:flex;flex-direction:column;gap:.3rem;">
      <span style="font-size:.85rem;opacity:.8;">Email</span>
      <input type="email" name="email" required autofocus autocomplete="email"
             style="padding:.6rem .8rem;border:1px solid #2b3346;background:#15161A;
                    color:#ddd;border-radius:8px;font-size:1rem;">
    </label>

    <label style="display:flex;flex-direction:column;gap:.3rem;">
      <span style="font-size:.85rem;opacity:.8;">Password</span>
      <input type="password" name="password" required autocomplete="current-password"
             style="padding:.6rem .8rem;border:1px solid #2b3346;background:#15161A;
                    color:#ddd;border-radius:8px;font-size:1rem;">
    </label>

    <button type="submit"
            style="margin-top:.4rem;padding:.7rem 1rem;border:0;border-radius:8px;
                   background:#3a76d2;color:#fff;font-size:1rem;font-weight:600;
                   cursor:pointer;">
      Sign in
    </button>
  </form>

  <p style="margin-top:1.4rem;color:#8593a6;font-size:.9rem;text-align:center;">
    New here?
    <a href="/register<?= !empty($next) ? '?next=' . urlencode($next) : '' ?>"
       style="color:#8fb1d8;">Create a Creator account</a>
  </p>
</div>
<?php
$content   = ob_get_clean();
$noSidebar = true;
include __DIR__ . '/layout.php';
?>
