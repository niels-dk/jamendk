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
        <input type="text" value="<?= ac_e($user['email']) ?>" disabled
               title="Email changes will be possible once verification emails are in place"
               style="padding:.55rem .8rem;border:1px solid #232838;background:#101116;
                      color:#8593a6;border-radius:8px;cursor:not-allowed;">
        <span style="font-size:.75rem;opacity:.5;">Email can't be changed yet — verification mail is coming later.</span>
      </label>
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">Company <span style="opacity:.5;">(optional)</span></span>
        <input type="text" name="company" value="<?= ac_e($user['company'] ?? '') ?>"
               style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                      color:#ddd;border-radius:8px;">
      </label>
      <label style="display:flex;flex-direction:column;gap:.3rem;">
        <span style="font-size:.85rem;opacity:.8;">Organisation <span style="opacity:.5;">(optional)</span></span>
        <input type="text" name="organisation" value="<?= ac_e($user['organisation'] ?? '') ?>"
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

  <!-- Account handover -->
  <div class="card" style="padding:1.1rem 1.2rem;margin-top:1rem;border-color:rgba(224,106,106,.3);">
    <h3 style="margin-top:0;">Hand over this account</h3>
    <?php if (!empty($pendingTransfer)): ?>
      <p style="color:#e8c889;font-size:.9rem;margin:0 0 .6rem;">
        ⏳ Waiting for
        <strong><?= ac_e($pendingTransfer['to_name'] ?: $pendingTransfer['to_email']) ?></strong>
        to accept. Your <?= ac_e($ownSummaryText) ?> will move to them once they do.
        Nothing has moved yet.
      </p>
      <form method="post" action="/account/transfer/cancel">
        <input type="hidden" name="csrf_token" value="<?= ac_e(csrf_token()) ?>">
        <button type="submit" class="btn">Cancel transfer</button>
      </form>
    <?php else: ?>
      <p style="opacity:.7;font-size:.9rem;margin:0 0 .8rem;">
        Leaving, or handing the work to someone else? Transfer everything you own —
        your <?= ac_e($ownSummaryText) ?> — to another creator's account. They'll
        get a request and have to accept; <strong>boards shared with you stay put</strong>,
        and nothing moves until they say yes. This can't be undone once accepted.
      </p>
      <form method="post" action="/account/transfer"
            onsubmit="return confirm('Send a transfer request? Once they accept, everything you own moves to them and can\'t be moved back by you.');"
            style="display:flex;flex-direction:column;gap:.6rem;">
        <input type="hidden" name="csrf_token" value="<?= ac_e(csrf_token()) ?>">
        <label style="display:flex;flex-direction:column;gap:.3rem;">
          <span style="font-size:.85rem;opacity:.8;">Recipient's account email</span>
          <input type="email" name="email" required placeholder="new-owner@example.com"
                 style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                        color:#ddd;border-radius:8px;">
        </label>
        <label style="display:flex;flex-direction:column;gap:.3rem;">
          <span style="font-size:.85rem;opacity:.8;">Note <span style="opacity:.5;">(optional)</span></span>
          <input type="text" name="note" maxlength="500" placeholder="A word for them, e.g. why you're handing over"
                 style="padding:.55rem .8rem;border:1px solid #2b3346;background:#15161A;
                        color:#ddd;border-radius:8px;">
        </label>
        <button type="submit" class="btn" style="align-self:flex-start;
                background:#7a2e2e;color:#fff;border:0;">Request transfer</button>
      </form>
    <?php endif; ?>
  </div>
</div>
