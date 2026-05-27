<?php
$title = 'Create a Creator account';
ob_start();
?>
<div style="max-width:440px;margin:3rem auto;padding:0 1rem;">
  <h1 style="font-size:1.8rem;margin:0 0 .4rem;">Create your Creator account</h1>
  <p style="color:#8593a6;margin:0 0 1.2rem;font-size:.95rem;">
    Start dreaming, planning, and publishing trips in a few seconds.
  </p>

  <?php if (!empty($error)): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.4);
                color:#f3b3b3;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" style="display:flex;flex-direction:column;gap:.8rem;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label style="display:flex;flex-direction:column;gap:.3rem;">
      <span style="font-size:.85rem;opacity:.8;">Name</span>
      <input type="text" name="name" required autofocus autocomplete="name"
             value="<?= htmlspecialchars($name ?? '') ?>"
             style="padding:.6rem .8rem;border:1px solid #2b3346;background:#15161A;
                    color:#ddd;border-radius:8px;font-size:1rem;">
    </label>

    <label style="display:flex;flex-direction:column;gap:.3rem;">
      <span style="font-size:.85rem;opacity:.8;">Email</span>
      <input type="email" name="email" required autocomplete="email"
             value="<?= htmlspecialchars($email ?? '') ?>"
             style="padding:.6rem .8rem;border:1px solid #2b3346;background:#15161A;
                    color:#ddd;border-radius:8px;font-size:1rem;">
    </label>

    <label style="display:flex;flex-direction:column;gap:.3rem;">
      <span style="font-size:.85rem;opacity:.8;">Password</span>
      <input type="password" name="password" required minlength="6" autocomplete="new-password"
             style="padding:.6rem .8rem;border:1px solid #2b3346;background:#15161A;
                    color:#ddd;border-radius:8px;font-size:1rem;">
      <span style="font-size:.75rem;opacity:.6;">Minimum 6 characters.</span>
    </label>

    <button type="submit"
            style="margin-top:.4rem;padding:.7rem 1rem;border:0;border-radius:8px;
                   background:#3a76d2;color:#fff;font-size:1rem;font-weight:600;
                   cursor:pointer;">
      Create account
    </button>
  </form>

  <p style="margin-top:1.4rem;color:#8593a6;font-size:.9rem;text-align:center;">
    Already have an account? <a href="/login" style="color:#8fb1d8;">Sign in</a>
  </p>
</div>
<?php
$content   = ob_get_clean();
$noSidebar = true;
include __DIR__ . '/layout.php';
?>
