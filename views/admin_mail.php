<?php
// views/admin_mail.php — outbound mail log + a live send test (fragment).
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);
$TYPE_LABEL = [
  'verify'       => 'Verification',
  'reset'        => 'Password reset',
  'reset_notice' => 'Already registered',
  'test'         => 'Test',
];
?>
<div style="max-width:960px;margin:2rem auto;padding:0 1rem;">
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;">Mail log</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.2rem;">
    Every send attempt the app has made. Check here first when someone says
    a link never arrived. <a href="/admin/users" style="color:#8fb1d8;">User management →</a>
  </p>

  <?php if ($flash): ?>
    <div style="background:rgba(58,118,210,.15);border:1px solid rgba(58,118,210,.4);
                color:#a8c8ee;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;"><?= $e($flash) ?></div>
  <?php endif; ?>

  <?php if (!empty($migrationMissing)): ?>
    <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
                color:#e8c267;padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;">
      The <code>mail_log</code> table doesn't exist yet — run
      <code>db/migrations/2026-07-15_email_verification.sql</code>.
    </div>
  <?php endif; ?>

  <?php if (!defined('MAIL_DRIVER') || MAIL_DRIVER !== 'smtp'): ?>
    <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
                color:#e8c267;padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;line-height:1.5;">
      <strong>SMTP is not configured — mail is going out via PHP mail().</strong><br>
      That means the envelope sender is your shell user at dreamhost.com, not
      <code>jamen.dk</code>, so the message isn't DKIM-signed for your domain and
      DMARC alignment fails. Gmail treats that as spam. Add the <code>MAIL_*</code>
      block from <code>app/config.sample.php</code> to <code>app/config.php</code>
      on the server to fix it.
    </div>
  <?php endif; ?>

  <!-- Transport + test -->
  <div style="background:rgba(255,255,255,.04);border:1px solid #2b3346;
              border-radius:10px;padding:1rem 1.1rem;margin-bottom:1.2rem;">
    <div style="display:flex;flex-wrap:wrap;gap:1.4rem;font-size:.88rem;margin-bottom:.9rem;">
      <span><span style="opacity:.6;">Driver:</span>
        <strong style="color:#eaeaea;"><?= $e($mailDriver) ?></strong></span>
      <span><span style="opacity:.6;">From:</span>
        <strong style="color:#eaeaea;"><?= $e($mailFrom) ?></strong></span>
      <span><span style="opacity:.6;">Last 7 days:</span>
        <strong style="color:#7fc98d;"><?= (int)($stats['sent'] ?? 0) ?> sent</strong>
        <?php if (!empty($stats['failed'])): ?>
          · <strong style="color:#f08792;"><?= (int)$stats['failed'] ?> failed</strong>
        <?php endif; ?>
      </span>
    </div>
    <form method="post" action="/admin/mail/test"
          style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;">
      <input type="hidden" name="csrf_token" value="<?= $e(csrf_token()) ?>">
      <input type="email" name="to" placeholder="you@example.com"
             value="<?= $e($currentUser['email'] ?? '') ?>"
             style="flex:1;min-width:220px;background:#15161A;border:1px solid #2b3346;
                    color:#ddd;padding:.45rem .7rem;border-radius:8px;font-size:.9rem;">
      <button type="submit"
              style="padding:.45rem 1rem;border:0;border-radius:8px;background:#3a76d2;
                     color:#fff;font-weight:600;cursor:pointer;font-size:.9rem;">
        Send test email
      </button>
    </form>
  </div>

  <?php if (empty($rows)): ?>
    <p style="color:#8593a6;font-size:.9rem;">
      Nothing sent yet. Use the test above to prove the transport works.
    </p>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
        <thead>
          <tr style="text-align:left;color:#8593a6;font-size:.78rem;
                     text-transform:uppercase;letter-spacing:.05em;">
            <th style="padding:.5rem .4rem;">When</th>
            <th style="padding:.5rem .4rem;">To</th>
            <th style="padding:.5rem .4rem;">Type</th>
            <th style="padding:.5rem .4rem;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr style="border-top:1px solid #2b3346;">
              <td style="padding:.5rem .4rem;color:#8593a6;white-space:nowrap;">
                <?= $e(date('M j · H:i', strtotime($r['created_at']))) ?>
              </td>
              <td style="padding:.5rem .4rem;color:#eaeaea;word-break:break-all;">
                <?= $e($r['to_email']) ?>
              </td>
              <td style="padding:.5rem .4rem;color:#a8c8ee;">
                <?= $e($TYPE_LABEL[$r['type']] ?? ($r['type'] ?: '—')) ?>
              </td>
              <td style="padding:.5rem .4rem;">
                <?php if ($r['status'] === 'sent'): ?>
                  <span style="color:#7fc98d;font-weight:600;">✓ sent</span>
                <?php else: ?>
                  <span style="color:#f08792;font-weight:600;">✗ failed</span>
                  <?php if (!empty($r['error'])): ?>
                    <div style="color:#8593a6;font-size:.78rem;margin-top:.2rem;
                                font-family:monospace;word-break:break-word;">
                      <?= $e($r['error']) ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
