<?php // views/reset_expired.php — dead reset link (fragment; layout wraps it) ?>
<div style="max-width:420px;margin:3rem auto;padding:0 1rem;text-align:center;">
  <div style="font-size:2.4rem;margin-bottom:.6rem;">⌛</div>
  <h1 style="font-size:1.6rem;margin:0 0 .6rem;">This link has expired</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.4rem;">
    <?= htmlspecialchars($error ?? 'That reset link is no longer valid.') ?>
    Reset links last one hour and can only be used once — request a fresh one below.
  </p>

  <a href="/forgot"
     style="display:inline-block;padding:.7rem 1.2rem;border-radius:8px;
            background:#3a76d2;color:#fff;font-size:1rem;font-weight:600;
            text-decoration:none;">
    Send a new reset link
  </a>

  <p style="margin-top:1.4rem;color:#8593a6;font-size:.9rem;">
    <a href="/login" style="color:#8fb1d8;">Back to sign in</a>
  </p>
</div>
