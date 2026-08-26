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
  <h1 style="font-size:1.7rem;margin:0 0 .3rem;"><?= te('adm.backups') ?></h1>
  <p style="color:#8593a6;font-size:.9rem;margin:0 0 1.4rem;">
    <?= te('adm.backups_sub') ?>
    <a href="/admin/users" style="color:#8fb1d8;"><?= te('adm.users') ?> →</a> &nbsp;·&nbsp;
    <a href="/admin/pricing" style="color:#8fb1d8;"><?= te('adm.revenue') ?> →</a> &nbsp;·&nbsp;
    <a href="/admin/mail" style="color:#8fb1d8;"><?= te('adm.mail') ?> →</a>
  </p>

  <?php if (!empty($runStatus) && empty($runStatus['ok'])): ?>
    <?php /* A failed attempt is not the same as no attempt, and the two need
             different fixes. Show the error the script itself reported. */ ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.5);
                color:#f3b3b3;padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.4rem;
                font-size:.95rem;line-height:1.6;">
      <strong style="font-size:1.05rem;">⚠ <?= te('adm.backup_last_failed') ?></strong><br>
      <span style="opacity:.85;"><?= $b_e(date('Y-m-d H:i', $runStatus['when'])) ?></span>
      <?php if ($runStatus['message'] !== ''): ?>
        <div style="margin-top:.5rem;font-family:monospace;font-size:.85rem;
                    word-break:break-word;"><?= $b_e($runStatus['message']) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($isStale): ?>
    <div style="background:rgba(208,80,80,.15);border:1px solid rgba(208,80,80,.5);
                color:#f3b3b3;padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.4rem;
                font-size:.95rem;line-height:1.6;">
      <strong style="font-size:1.05rem;">🔴 <?= te('adm.no_backup') ?></strong><br>
      <?php if (!$configured || $lastRun === null): ?>
        <?= te('adm.backup_never') ?>
        <ol style="margin:.6rem 0 0;padding-left:1.3rem;">
          <li><?= te('adm.step_mycnf_1') ?> <code>~/.my.cnf</code> <?= te('adm.step_mycnf_2') ?>
              <code>scripts/backup.sh</code>), <?= te('adm.step_mycnf_3') ?>
              <code>chmod 600 ~/.my.cnf</code></li>
          <li><?= te('adm.step_test') ?> <code>~/merelyadream.com/scripts/backup.sh</code>
              — <?= te('adm.step_test_2') ?></li>
          <li><?= te('adm.step_cron') ?></li>
        </ol>
      <?php else: ?>
        <?= te('adm.last_run') ?> <strong><?= $b_e($lastRun) ?></strong>
        <?= te('adm.stale_note', ['n' => number_format($staleHrs, 0)]) ?>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div style="background:rgba(127,201,141,.12);border:1px solid rgba(127,201,141,.4);
                color:#9bd6a6;padding:.8rem 1.1rem;border-radius:10px;margin-bottom:1.4rem;
                font-size:.95rem;">
      🟢 <?= te('adm.backup_ok') ?> <strong><?= $b_e($lastRun) ?></strong>
      (<?= te('adm.h_ago', ['n' => number_format($staleHrs, 1)]) ?>).
    </div>
  <?php endif; ?>

  <?php
    $sections = [
      [t('adm.db_dumps'),    $dbFiles, t('adm.db_dumps_sub')],
      [t('adm.file_archives'), $arFiles, t('adm.file_archives_sub')],
    ];
    foreach ($sections as [$label, $files, $sub]):
  ?>
    <h2 style="font-size:1rem;color:#cfdbe8;margin:1.6rem 0 .2rem;"><?= $b_e($label) ?>
      <span style="font-weight:400;color:#6c7d92;font-size:.85rem;">— <?= $b_e($sub) ?></span>
    </h2>
    <?php if (!$files): ?>
      <p style="color:#6c7d92;font-size:.9rem;margin:.4rem 0 0;"><?= te('adm.none_yet') ?></p>
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
    <?= te('adm.before_migration') ?>
    <code>~/merelyadream.com/scripts/backup.sh</code> — <?= te('adm.before_migration_2') ?>
    <?= te('adm.restore') ?>: <code>gunzip &lt; dump.sql.gz | mysql <?= $b_e($dbName) ?></code>.
  </p>
</div>
