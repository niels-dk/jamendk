<?php
// views/admin_user_history.php — the per-user timeline panel.
//
// A fragment, not a page: it is fetched and injected into the overlay on
// /admin/users, so no layout and no <html>. Rendered server-side rather than
// as JSON so date formatting and translation stay where fmt_date() and t()
// already live.
//
// Expects $target (id, name, email) and $events (newest first) from
// admin_controller::userHistory().
$uh = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// fmt_date() gives the localised date but no clock. On a support timeline the
// time of day is half the point — "deactivated at 14:32, right after she
// wrote in" — so the hour is appended.
$uhWhen = function ($ts) {
    $t = strtotime((string)$ts);
    return $t ? fmt_date($ts) . ' · ' . date('H:i', $t) : '';
};

// Past-tense labels for the entry types. A type with no label here still
// renders — it shows its raw type — so adding a new kind of entry later
// cannot produce a blank row.
$uhLabel = [
    'deactivated' => t('adm.deactivated'),
    'reactivated' => t('adm.hist_reactivated'),
    'note'        => t('adm.hist_note'),
];
$uhColor = [
    'deactivated' => ['rgba(224,106,106,.16)', '#f0a0a0'],
    'reactivated' => ['rgba(127,201,141,.15)', '#7fc98d'],
    'note'        => ['rgba(143,177,216,.15)', '#8fb1d8'],
];
?>
<div class="uh-head">
  <div>
    <div class="uh-who"><?= $uh($target['name'] ?: t('roles.no_name')) ?></div>
    <div class="uh-mail"><?= $uh($target['email']) ?></div>
  </div>
  <button type="button" class="btn uh-close" title="<?= te('action.close') ?>">✕</button>
</div>

<form class="uh-add" data-id="<?= (int)$target['id'] ?>">
  <textarea name="note" rows="3" maxlength="4000"
            placeholder="<?= te('adm.hist_ph') ?>"></textarea>
  <div class="uh-add-row">
    <span class="uh-privacy"><?= te('adm.hist_privacy') ?></span>
    <button type="submit" class="btn uh-save"><?= te('adm.hist_add') ?></button>
  </div>
</form>

<?php if (!$events): ?>
  <p class="uh-empty"><?= te('adm.hist_empty') ?></p>
<?php else: ?>
  <ol class="uh-list">
    <?php foreach ($events as $ev): ?>
      <?php
        $type = (string)$ev['type'];
        [$bg, $fg] = $uhColor[$type] ?? ['rgba(255,255,255,.07)', '#9fb0c4'];
        $who = trim((string)($ev['author_name'] ?? ''))
             ?: trim((string)($ev['author_email'] ?? ''))
             ?: t('adm.hist_system');
      ?>
      <li class="uh-item">
        <div class="uh-meta">
          <span class="uh-type" style="background:<?= $uh($bg) ?>;color:<?= $uh($fg) ?>;">
            <?= $uh($uhLabel[$type] ?? $type) ?></span>
          <span class="uh-when"><?= $uh($uhWhen($ev['created_at'])) ?></span>
          <span class="uh-by"><?= te('adm.hist_by', ['who' => $who]) ?></span>
        </div>
        <?php if (trim((string)$ev['body']) !== ''): ?>
          <div class="uh-body"><?= nl2br($uh($ev['body'])) ?></div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
