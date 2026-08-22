<?php
// views/admin_mail.php — outbound mail log + a live send test (fragment).
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);
$TYPE_LABEL = [
  'verify'       => t('adm.mt_verify'),
  'reset'        => t('adm.mt_reset'),
  'reset_notice' => t('adm.mt_notice'),
  'test'         => t('adm.mt_test'),
];
?>
<div style="max-width:960px;margin:2rem auto;padding:0 1rem;">
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;"><?= te('adm.mail_log') ?></h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.2rem;">
    <?= te('adm.mail_sub') ?> <a href="/admin/users" style="color:#8fb1d8;"><?= te('adm.users') ?> →</a>
    &nbsp;·&nbsp; <a href="/admin/backups" style="color:#8fb1d8;"><?= te('adm.backups') ?> →</a>
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
      <?= te('adm.no_maillog_table') ?>
      <code>db/migrations/2026-07-15_email_verification.sql</code>.
    </div>
  <?php endif; ?>

  <?php if (!defined('MAIL_FROM') && !defined('MAIL_USER')): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.4);
                color:#f3b3b3;padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;line-height:1.5;">
      <strong><?= te('adm.mailfrom_head') ?></strong><br>
      <?= te('adm.mailfrom_body_1') ?> <code>MAIL_FROM</code> <?= te('adm.nor') ?>
      <code>MAIL_USER</code>, <?= te('adm.mailfrom_body_2') ?>
      <code>MAIL_FROM</code> <?= te('adm.mailfrom_body_3') ?>
      <code>hello@merelyadream.com</code>) <?= te('adm.in_file') ?> <code>app/config.php</code>.
      <?= te('adm.mailfrom_body_4') ?>
    </div>
  <?php elseif (!defined('MAIL_DRIVER') || MAIL_DRIVER !== 'smtp'): ?>
    <div style="background:rgba(232,194,103,.12);border:1px solid rgba(232,194,103,.4);
                color:#e8c267;padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;
                font-size:.9rem;line-height:1.5;">
      <strong><?= te('adm.phpmail_head') ?></strong><br>
      <?= te('adm.phpmail_body_1') ?>
      <code><?= $e(defined('MAIL_FROM') ? MAIL_FROM : MAIL_USER) ?></code>,
      <?= te('adm.phpmail_body_2') ?>
    </div>
  <?php endif; ?>

  <!-- Transport + test -->
  <div style="background:rgba(255,255,255,.04);border:1px solid #2b3346;
              border-radius:10px;padding:1rem 1.1rem;margin-bottom:1.2rem;">
    <div style="display:flex;flex-wrap:wrap;gap:1.4rem;font-size:.88rem;margin-bottom:.9rem;">
      <span><span style="opacity:.6;"><?= te('adm.driver') ?>:</span>
        <strong style="color:#eaeaea;"><?= $e($mailDriver) ?></strong></span>
      <span><span style="opacity:.6;"><?= te('adm.from') ?>:</span>
        <strong style="color:#eaeaea;"><?= $e($mailFrom) ?></strong></span>
      <span><span style="opacity:.6;"><?= te('adm.last_7') ?>:</span>
        <strong style="color:#7fc98d;"><?= te('adm.n_sent', ['n' => (int)($stats['sent'] ?? 0)]) ?></strong>
        <?php if (!empty($stats['failed'])): ?>
          · <strong style="color:#f08792;"><?= te('adm.n_failed', ['n' => (int)$stats['failed']]) ?></strong>
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
        <?= te('adm.send_test') ?>
      </button>
    </form>

    <!-- Config readout. Values are shown so typos are visible; the password
         is shown only as a length, which is what exposes a quoting bug. -->
    <details style="margin-top:.9rem;">
      <summary style="cursor:pointer;font-size:.84rem;color:#8fb1d8;">
        <?= te('adm.show_cfg') ?>
      </summary>
      <table style="margin-top:.6rem;font-size:.82rem;border-collapse:collapse;">
        <?php foreach ($mailCfg as $k => $v): ?>
          <tr>
            <td style="padding:.2rem .8rem .2rem 0;color:#8593a6;font-family:monospace;">
              <?= $e($k) ?>
            </td>
            <td style="padding:.2rem 0;font-family:monospace;
                       color:<?= $v === null ? '#e8c267' : '#eaeaea' ?>;">
              <?= $v === null ? te('adm.not_set') : $e($v) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td style="padding:.2rem .8rem .2rem 0;color:#8593a6;font-family:monospace;">
            MAIL_PASS
          </td>
          <td style="padding:.2rem 0;font-family:monospace;
                     color:<?= $mailPassLen === null ? '#e8c267' : '#eaeaea' ?>;">
            <?php if ($mailPassLen === null): ?>
              <?= te('adm.not_set') ?>
            <?php else: ?>
              <?= te('adm.set_chars', ['n' => (int)$mailPassLen]) ?>
              <span style="color:#8593a6;">
                — <?= te('adm.pass_len_hint') ?>
              </span>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </details>
  </div>

  <?php if (empty($rows)): ?>
    <p style="color:#8593a6;font-size:.9rem;">
      <?= te('adm.no_mail_yet') ?>
    </p>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
        <thead>
          <tr style="text-align:left;color:#8593a6;font-size:.78rem;
                     text-transform:uppercase;letter-spacing:.05em;">
            <th style="padding:.5rem .4rem;"><?= te('adm.col_when') ?></th>
            <th style="padding:.5rem .4rem;"><?= te('adm.col_to') ?></th>
            <th style="padding:.5rem .4rem;"><?= te('adm.col_type') ?></th>
            <th style="padding:.5rem .4rem;"><?= te('wf.status') ?></th>
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
                  <span style="color:#7fc98d;font-weight:600;">✓ <?= te('adm.sent') ?></span>
                <?php else: ?>
                  <span style="color:#f08792;font-weight:600;">✗ <?= te('adm.failed') ?></span>
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
