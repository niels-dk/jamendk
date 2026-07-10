<?php
// views/account.php — expects $user (id, name, email, role, created_at), $notice, $error
function ac_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<div style="max-width:560px;margin:0 auto;">
  <h1 style="margin-bottom:.2rem;">My account</h1>
  <p style="opacity:.6;margin-top:0;font-size:.9em;">
    Signed in as <?= ac_e($user['email']) ?>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
      <span style="display:inline-block;margin-left:.3rem;padding:.05rem .5rem;border-radius:999px;
                   background:#1f3a66;color:#8fb1d8;font-size:.75rem;font-weight:700;">Admin</span>
    <?php endif; ?>
  </p>

  <?php if (!empty($notice)): ?>
    <div style="background:rgba(126,217,154,.12);border:1px solid rgba(126,217,154,.4);
                color:#7ed99a;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem;">
      <?= ac_e($notice) ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.4);
                color:#f3b3b3;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem;">
      <?= ac_e($error) ?>
    </div>
  <?php endif; ?>

  <div class="card" style="padding:1.1rem 1.2rem;margin-bottom:1rem;">
    <h3 style="margin-top:0;">Profile</h3>
    <form method="post" style="display:flex;flex-direction:column;gap:.7rem;">
      <input type="hidden" name="csrf_token" value="<?= ac_e(csrf_token()) ?>">
      <input type="hidden" name="action" value="profile">
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">Name</span>
        <input type="text" name="name" required value="<?= ac_e($user['name']) ?>"
               style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                      color:#ddd;border-radius:8px;">
      </label>
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">Email</span>
        <input type="text" name="email" required value="<?= ac_e($user['email']) ?>"
               style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                      color:#ddd;border-radius:8px;">
      </label>
      <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Save profile</button>
    </form>
  </div>

  <div class="card" style="padding:1.1rem 1.2rem;">
    <h3 style="margin-top:0;">Change password</h3>
    <form method="post" style="display:flex;flex-direction:column;gap:.7rem;">
      <input type="hidden" name="csrf_token" value="<?= ac_e(csrf_token()) ?>">
      <input type="hidden" name="action" value="password">
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">Current password</span>
        <input type="password" name="current_password" required autocomplete="current-password"
               style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                      color:#ddd;border-radius:8px;">
      </label>
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">New password (min 6 characters)</span>
        <input type="password" name="new_password" required autocomplete="new-password"
               style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                      color:#ddd;border-radius:8px;">
      </label>
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">Repeat new password</span>
        <input type="password" name="confirm_password" required autocomplete="new-password"
               style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                      color:#ddd;border-radius:8px;">
      </label>
      <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Change password</button>
    </form>
  </div>
</div>
