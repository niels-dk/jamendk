<?php
// views/admin_backups.php — backup health (fragment; layout wraps it).
// Expects: $configured, $isStale, $lastRun, $staleHrs, $dbFiles, $arFiles, $base
$b_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$fmtSize = function (int $b): string {
    if ($b >= 1048576) return number_format($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return number_format($b / 1024) . ' KB';
    return $b . ' B';
};
?>
<div style="max-width:900px;margin:2rem auto;padding:0 1rem;">
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;">Backups</h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.4rem;">
    Nightly database dump, weekly file archive — written by
    <code>scripts/backup.sh</code> via cron.
    <a href="/admin/users" style="color:#8fb1d8;">Users →</a> &nbsp;·&nbsp;
    <a href="/admin/pricing" style="color:#8fb1d8;">Revenue →</a> &nbsp;·&nbsp;
    <a href="/admin/mail" style="color:#8fb1d8;">Mail →</a>
  </p>

  <?php if ($isStale): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.5);
                color:#f3b3b3;padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.4rem;
                font-size:.95rem;line-height:1.6;">
      <strong style="font-size:1.05rem;">🔴 No recent backup.</strong><br>
      <?php if (!$configured || $lastRun === null): ?>
        The backup script hasn't completed successfully yet. Over SSH:
        <ol style="margin:.6rem 0 0;padding-left:1.3rem;">
          <li>Create <code>~/.my.cnf</code> with the database credentials
              (template in the header of <code>scripts/backup.sh</code>), then
              <code>chmod 600 ~/.my.cnf</code></li>
          <li>Test it once by hand: <code>~/jamen.dk/scripts/backup.sh</code>
              — silence means success, then reload this page</li>
          <li>Add it as a daily cron job in the DreamHost panel
              (Advanced → Cron Jobs), with email-on-output left ON</li>
        </ol>
      <?php else: ?>
        Last successful run: <strong><?= $b_e($lastRun) ?></strong>
        (<?= number_format($staleHrs, 0) ?> hours ago — nightly cron should never
        exceed ~30). Check the cron job in the DreamHost panel and your email
        for a failure message from it.
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div style="background:rgba(127,201,141,.12);border:1px solid rgba(127,201,141,.4);
                color:#9bd6a6;padding:.8rem 1.1rem;border-radius:10px;margin-bottom:1.4rem;
                font-size:.95rem;">
      🟢 Healthy — last successful run <strong><?= $b_e($lastRun) ?></strong>
      (<?= number_format($staleHrs, 1) ?> h ago).
    </div>
  <?php endif; ?>

  <?php
    $sections = [
      ['Database dumps', $dbFiles, 'one per day, kept 30 days'],
      ['File archives',  $arFiles, 'storage/ weekly, kept ~5 weeks'],
    ];
    foreach ($sections as [$label, $files, $sub]):
  ?>
    <h2 style="font-size:1rem;color:#cfdbe8;margin:1.6rem 0 .2rem;"><?= $b_e($label) ?>
      <span style="font-weight:400;color:#6c7d92;font-size:.85rem;">— <?= $b_e($sub) ?></span>
    </h2>
    <?php if (!$files): ?>
      <p style="color:#6c7d92;font-size:.9rem;margin:.4rem 0 0;">None yet.</p>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
        <?php foreach ($files as $f): ?>
          <tr style="border-top:1px solid #2b3346;">
            <td style="padding:.45rem .4rem;font-family:monospace;color:#eaeaea;">
              <?= $b_e($f['name']) ?></td>
            <td style="padding:.45rem .4rem;color:#8fb1d8;font-family:monospace;
                       text-align:right;white-space:nowrap;">
              <?= $b_e($fmtSize((int)$f['size'])) ?></td>
            <td style="padding:.45rem .4rem;color:#8593a6;text-align:right;white-space:nowrap;">
              <?= $b_e(date('M j · H:i', (int)$f['mtime'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  <?php endforeach; ?>

  <p style="color:#6c7d92;font-size:.82rem;margin-top:1.8rem;line-height:1.6;">
    Before running a migration in phpMyAdmin, take a fresh dump first:
    SSH in and run <code>~/jamen.dk/scripts/backup.sh</code> — it overwrites
    today's file, so you always restore to the moment before the change.
    Restore: <code>gunzip &lt; dump.sql.gz | mysql jamen_dk</code>.
  </p>
</div>
