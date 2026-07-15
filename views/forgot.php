<?php // views/forgot.php — request a password reset link (fragment; layout wraps it) ?>
<div style="max-width:420px;margin:3rem auto;padding:0 1rem;">
  <h1 style="font-size:1.8rem;margin:0 0 .6rem;">Forgot your password?</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.2rem;">
    Enter the address you signed up with and we'll email you a link to choose a new one.
  </p>

  <?php if (!empty($error)): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.4);
                color:#f3b3b3;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($notice)): ?>
    <div style="background:rgba(58,118,210,.15);border:1px solid rgba(58,118,210,.4);
                color:#a8c8ee;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;">
      <?= htmlspecialchars($notice) ?>
    </div>
  <?php endif; ?>

  <form method="post" style="display:flex;flex-direction:column;gap:.8rem;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <!-- Honeypot: real users never see this. -->
    <div style="position:absolute;left:-9999px;" aria-hidden="true">
      <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <label style="display:flex;flex-direction:column;gap:.3rem;">
      <span style="font-size:.85rem;opacity:.8;">Email</span>
      <input type="email" name="email" required autofocus autocomplete="email"
             style="padding:.6rem .8rem;border:1px solid #2b3346;background:#15161A;
                    color:#ddd;border-radius:8px;font-size:1rem;">
    </label>

    <button type="submit"
            style="margin-top:.4rem;padding:.7rem 1rem;border:0;border-radius:8px;
                   background:#3a76d2;color:#fff;font-size:1rem;font-weight:600;
                   cursor:pointer;">
      Email me a reset link
    </button>
  </form>

  <p style="margin-top:1.4rem;color:#8593a6;font-size:.9rem;text-align:center;">
    <a href="/login" style="color:#8fb1d8;">Back to sign in</a>
  </p>
</div>
