<?php
// views/dashboard_overview.php — the one start page for a signed-in user.
//
// It used to be one of three (a "welcome back" page at "/", this overview at
// /dashboard, and the per-type lists), each with its own look. "/" now
// redirects here, and the welcome line, the create buttons and the upcoming
// dates that only the old page had are part of this one.
//
// expects: $boardSets, $boardCounts, $sortValue, $limitValue, $upcoming,
//          $taskDeadlines, $userName, $pricing, $incomingTransfers,
//          $newShares, $handoffs, $notices
require_once __DIR__ . '/partials/home_helpers.php';

function dash_e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
// Board-type names come from the language file: 'dream' → Dreams / Drømme.
function human($t){ return t('board.' . $t . 's'); }
function human1($t){ return t('board.' . $t); }
// Localised ("28. maj 2026"). date('M j, Y') only ever speaks English.
function dt($s){ return $s ? fmt_date($s) : ''; }
// A translated string for an inline JS call, e.g. inside confirm( … ).
function dash_js(string $s): string {
    return htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

$boardCounts   = $boardCounts   ?? [];
$upcoming      = $upcoming      ?? [];
$taskDeadlines = $taskDeadlines ?? [];
$userName      = $userName      ?? '';

// A brand-new user meets the product here. Four "No X yet" boxes is a poor
// first minute; they get a welcome and the worked example instead.
$dashEmpty = empty($boardSets['dream']) && empty($boardSets['vision'])
          && empty($boardSets['mood'])  && empty($boardSets['trip']);

$dashIcon = ['dream' => '🌕', 'vision' => '📄', 'mood' => '🎨', 'trip' => '🗺️'];

// Role shown on a shared card. ucfirst() on the stored value can never
// translate, so map it onto the roles.* labels.
$dashRole = [
    'co_owner' => t('roles.co_owner_short'),
    'editor'   => t('roles.editor_short'),
    'viewer'   => t('roles.viewer_short'),
    'delegate' => t('roles.delegate_short'),
];
?>
<div class="dash">

<?php if (!empty($_SESSION['flash_home'])): ?>
  <div class="dash__flash"><?= dash_e($_SESSION['flash_home']) ?></div>
  <?php unset($_SESSION['flash_home']); ?>
<?php endif; ?>

<?php foreach (($incomingTransfers ?? []) as $it): ?>
  <div class="dash-transfer">
    <div class="dash-transfer__title">
      🤝 <?= te('dash.transfer_wants', ['name' => $it['from_name'] ?: $it['from_email']]) ?>
    </div>
    <p>
      <?= te('dash.transfer_body', ['summary' => $it['summary_text']]) ?>
      <?php if (!empty($it['note'])): ?>
        <br><span class="dash-transfer__note">“<?= dash_e($it['note']) ?>”</span>
      <?php endif; ?>
    </p>
    <div class="dash-transfer__actions">
      <form method="post" action="/transfer/<?= (int)$it['id'] ?>/accept"
            onsubmit="return confirm(<?= dash_js(t('dash.transfer_confirm', ['summary' => $it['summary_text']])) ?>);">
        <input type="hidden" name="csrf_token" value="<?= dash_e(csrf_token()) ?>">
        <button type="submit" class="dash-btn-primary"><?= te('dash.accept') ?></button>
      </form>
      <form method="post" action="/transfer/<?= (int)$it['id'] ?>/decline">
        <input type="hidden" name="csrf_token" value="<?= dash_e(csrf_token()) ?>">
        <button type="submit" class="dash-btn-ghost"><?= te('dash.decline') ?></button>
      </form>
    </div>
  </div>
<?php endforeach; ?>

  <!-- Who you are, and the one thing most visits start with: making something. -->
  <section class="dash-welcome">
    <div>
      <h1><?= te($dashEmpty ? 'home.welcome' : 'home.welcome_back') ?><?= $userName !== '' ? ', ' . dash_e($userName) : '' ?> 👋</h1>
      <p class="sub"><?= te($dashEmpty ? 'home.welcome_sub' : 'home.welcome_back_sub') ?></p>
    </div>
    <?php if ($dashEmpty): ?>
      <a class="dash-btn-primary" href="/capture">+ <?= te('home.first_dream') ?></a>
    <?php endif; ?>
  </section>

<?php if ($dashEmpty): ?>
  <!-- A blank dashboard plus four unfamiliar nouns is a bad first minute.
       One click gives them a finished project to take apart instead. -->
  <form method="post" action="/demo/load" class="dash-example">
    <input type="hidden" name="csrf_token" value="<?= dash_e(csrf_token()) ?>">
    <div class="dash-example__copy">
      <h2>🚚 <?= te('home.example_title') ?></h2>
      <p><?= te('home.example_body') ?></p>
    </div>
    <button type="submit" class="dash-btn-primary"><?= te('home.example_btn') ?></button>
  </form>
<?php else: ?>
  <!-- New Dream goes to the one-line capture screen: a Dream is a single
       thought, and a form with five fields is the opposite of that. -->
  <div class="dash-create">
    <span class="dash-create__label"><?= te('home.create') ?></span>
    <a class="dash-create__btn is-dream"  href="/capture"><?= $dashIcon['dream'] ?> <?= te('home.new_dream') ?></a>
    <a class="dash-create__btn is-vision" href="/visions/new"><?= $dashIcon['vision'] ?> <?= te('home.new_vision') ?></a>
    <a class="dash-create__btn is-mood"   href="/moods/new"><?= $dashIcon['mood'] ?> <?= te('home.new_mood') ?></a>
  </div>
<?php endif; ?>

<?php
// Founding Creator badge — in-app value anchor, no email (your call).
// Frames the free plan as a gift with a promise, never a countdown to a bill.
if (!empty($pricing) && !empty($pricing['is_founder'])):
  $pl_paid  = $pricing['tier']['is_paid'];
  $pl_saved = (int)$pricing['saved_cents'];
  $pl_seats = (int)$pricing['seats'];
  // "Aug 2026", with the month name from the language file.
  $pl_since = '';
  if ($pricing['founding_at'] && ($pl_ts = strtotime($pricing['founding_at']))) {
      $pl_since = t('month.' . strtolower(date('M', $pl_ts))) . ' ' . date('Y', $pl_ts);
  }
?>
  <div class="founder-badge">
    <span class="fb-star">✨</span>
    <span class="fb-title">Founding Creator</span>
    <span class="fb-sep">·</span>
    <span class="fb-plan">
      <?= te('dash.founder_plan', ['tier' => $pricing['tier']['label']]) ?> —
      <b><?= te($pl_seats === 1 ? 'dash.person_one' : 'dash.person_many', ['n' => $pl_seats]) ?></b><?php
      if ($pl_paid): ?>, <?= te('dash.founder_normally', ['price' => Pricing::money($pricing['monthly_cents'])]) ?><?php endif; ?>
    </span>
    <?php if ($pl_paid && $pl_saved > 0): ?>
      <span class="fb-sep">·</span>
      <span class="fb-saved">
        <?= $pl_since
              ? te('dash.founder_saved_since', ['amount' => Pricing::money($pl_saved), 'date' => $pl_since])
              : te('dash.founder_saved', ['amount' => Pricing::money($pl_saved)]) ?>
      </span>
    <?php endif; ?>
    <span class="fb-free" title="<?= te('dash.founder_tip') ?>">
      <?= te('dash.founder_free') ?> 💛
    </span>
  </div>
<?php endif; ?>

<?php if (!empty($upcoming) || !empty($taskDeadlines)): ?>
  <div class="dash-kicker"><?= te('home.upcoming') ?></div>
  <div class="upcoming">
    <?php foreach ($upcoming as $u): ?>
      <?php
        // Prefer the nearest relevant date: an overdue/soon end date wins,
        // otherwise an upcoming start date.
        $endDays    = h_days_until($u['end_date'] ?? null);
        $startDays  = h_days_until($u['start_date'] ?? null);
        $isComplete = ($u['workflow_status'] ?? '') === 'complete';

        $kind = null; $days = null; $dateStr = null;
        if ($u['end_date'] && $endDays !== null && ($endDays <= 10) && !$isComplete) {
          $kind = $endDays < 0 ? 'overdue' : 'ending';
          $days = $endDays; $dateStr = $u['end_date'];
        } elseif ($u['start_date'] && $startDays !== null && $startDays >= 0 && $startDays <= 10) {
          $kind = 'starting'; $days = $startDays; $dateStr = $u['start_date'];
        } elseif ($u['end_date'] && $endDays !== null) {
          $kind = $endDays < 0 ? 'overdue' : 'ending';
          $days = $endDays; $dateStr = $u['end_date'];
        }
        if ($kind === null) continue;

        if ($kind === 'overdue')      { $badge = t('home.overdue'); $cls = 'u-overdue';  $rel = h_ago($days); }
        elseif ($kind === 'ending')   { $badge = t('home.ends');    $cls = 'u-ending';   $rel = h_ahead($days); }
        else                          { $badge = t('home.starts');  $cls = 'u-starting'; $rel = h_ahead($days); }
      ?>
      <a class="u-row <?= $cls ?>" href="/visions/<?= h_e($u['slug']) ?>">
        <span class="u-badge"><?= h_e($badge) ?></span>
        <span class="u-title"><?= h_e($u['title'] ?: t('home.untitled_vision')) ?></span>
        <span class="u-when"><?= h_e($rel) ?> · <?= h_e(h_dt($dateStr)) ?></span>
      </a>
    <?php endforeach; ?>

    <?php foreach ($taskDeadlines as $td): ?>
      <?php
        $days = h_days_until($td['due_date'] ?? null);
        if ($days === null) continue;
        $overdue = $days < 0;
        $cls   = $overdue ? 'u-overdue' : ($days <= 2 ? 'u-ending' : 'u-starting');
        $badge = t($td['kind'] === 'milestone' ? 'home.milestone' : 'home.goal');
        $rel   = $overdue ? h_ago($days) : h_ahead($days);
        $who   = !empty($td['assignee_name']) ? ' · ' . $td['assignee_name'] : '';
      ?>
      <a class="u-row <?= $cls ?>" href="/visions/<?= h_e($td['vision_slug']) ?>">
        <span class="u-badge"><?= h_e($badge) ?></span>
        <span class="u-title">
          <?= h_e($td['title'] ?: t('common.untitled')) ?>
          <span class="u-sub">— <?= h_e($td['vision_title'] ?: t('board.vision')) ?><?= h_e($who) ?></span>
        </span>
        <span class="u-when"><?= h_e($rel) ?> · <?= h_e(h_dt($td['due_date'])) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$dashEmpty): ?>
  <div class="dash__topbar">
    <h2 class="dash__title"><?= te('home.your_boards') ?></h2>

    <form method="get" class="dash__sort">
      <label for="sort" class="dash__sort-label"><?= dash_e(t('dash.sort')) ?></label>
      <select id="sort" name="sort" class="dash__select" onchange="this.form.submit()">
        <option value="latest"    <?= $sortValue==='latest'?'selected':''; ?>><?= dash_e(t('dash.sort_latest')) ?></option>
        <option value="newest"    <?= $sortValue==='newest'?'selected':''; ?>><?= dash_e(t('dash.sort_newest')) ?></option>
        <option value="favorites" <?= $sortValue==='favorites'?'selected':''; ?>><?= dash_e(t('dash.sort_favorites')) ?></option>
      </select>

      <label for="limit_each" class="dash__sort-label"><?= dash_e(t('dash.show')) ?></label>
      <select id="limit_each" name="limit_each" class="dash__select" onchange="this.form.submit()">
        <option value="0" <?= (int)$limitValue===0 ? 'selected':''; ?>><?= te('dash.all') ?></option>
        <option value="2" <?= (int)$limitValue===2 ? 'selected':''; ?>>2</option>
        <option value="4" <?= (int)$limitValue===4 ? 'selected':''; ?>>4</option>
        <option value="6" <?= (int)$limitValue===6 ? 'selected':''; ?>>6</option>
      </select>
      <input type="hidden" name="limit" value="<?= (int)$limitValue ?>">
    </form>
  </div>

  <?php foreach (['dream','vision','mood','trip'] as $type): ?>
    <?php $items = $boardSets[$type] ?? []; ?>
    <section class="dash__section">

      <div class="dash__section-head">
        <h2 class="dash__section-title">
          <span class="dash__section-icon"><?= $dashIcon[$type] ?></span>
          <?= human($type) ?>
          <?php if (!empty($boardCounts[$type])): ?>
            <span class="dash__count"><?= (int)$boardCounts[$type] ?></span>
          <?php endif; ?>
        </h2>
        <a class="dash__seeall" href="/dashboard/<?= $type ?>"><?= dash_e(t('action.see_all')) ?></a>
      </div>

      <?php if (!$items): ?>
        <?php if ($type === 'trip'): ?>
          <div class="dash__empty">
            <p><?= dash_e(t('dash.no_trips')) ?></p>
            <p style="opacity:.7;font-size:.9em;max-width:36rem;">
              <?= dash_e(t('dash.no_trips_help')) ?>
            </p>
          </div>
        <?php else: ?>
          <div class="dash__empty">
            <p><?= dash_e(t('dash.no_items', ['type' => mb_strtolower(human($type))])) ?></p>
            <a class="dash__btn" href="<?= $type === 'dream' ? '/capture' : '/' . $type . 's/new' ?>"><?= dash_e(t('dash.create', ['type' => human1($type)])) ?></a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <ul class="dash__grid">
          <?php foreach ($items as $row): ?>
            <li class="dash-card">
              <h3 class="dash-card__title">
                <a href="/<?= $type ?>s/<?= dash_e($row['slug'] ?? '') ?>">
                  <?= dash_e(($row['title'] ?? '') !== '' ? $row['title'] : t('common.untitled')) ?>
                </a>
                <?php if ($type === 'dream' && !empty($row['is_promoted'])): ?>
                  <span class="dash-tag is-promoted" title="<?= te('dash.promoted_tip') ?>">
                    ✨ <?= te('dash.promoted') ?>
                  </span>
                <?php endif; ?>
                <?php global $currentUserId;
                      $roleLbl = !empty($row['my_shared_role'])
                        ? ' · ' . ($dashRole[$row['my_shared_role']] ?? $row['my_shared_role']) : '';
                      if (!empty($row['user_id']) && (int)$row['user_id'] !== (int)$currentUserId): ?>
                  <span class="dash-tag is-shared" title="<?= te('dash.shared_tip') ?>">
                    🤝 <?= te('dash.shared') ?><?= dash_e($roleLbl) ?>
                  </span>
                <?php elseif (!empty($row['shared_with_names'])): ?>
                  <span class="dash-tag is-sharing" title="<?= te('dash.shared_by_tip', ['names' => $row['shared_with_names']]) ?>">
                    📤 <?= te('dash.shared_with', ['names' => $row['shared_with_names']]) ?>
                  </span>
                <?php endif; ?>
              </h3>

              <?php if ($type === 'trip'): ?>
                <?php
                  $bits = [];
                  if (!empty($row['mood_title'])) $bits[] = '🎨 ' . dash_e($row['mood_title']);
                  $cn = (int)($row['contact_count'] ?? 0);
                  if ($cn > 0) $bits[] = '👥 ' . dash_e(t($cn === 1 ? 'dash.contact_one' : 'dash.contact_many', ['n' => $cn]));
                  if (!empty($row['has_budget'])) $bits[] = '💰 ' . dash_e(t('dash.budget'));
                ?>
                <?php if ($bits): ?>
                  <p class="dash-card__snippet">
                    <?= implode(' &nbsp;·&nbsp; ', $bits) /* each bit is escaped above */ ?>
                  </p>
                <?php endif; ?>
              <?php else: ?>
                <?php
                  $desc = $row['description'] ?? '';
                  // html_entity_decode: descriptions come from the rich-text
                  // editor, and strip_tags alone left "&nbsp;" on the cards.
                  if ($desc) $desc = mb_strimwidth(trim(html_entity_decode(strip_tags($desc), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 130, '…', 'UTF-8');
                ?>
                <?php if ($desc): ?>
                  <p class="dash-card__snippet"><?= dash_e($desc) ?></p>
                <?php endif; ?>
              <?php endif; ?>

              <div class="dash-card__meta">
                <?php if ($type === 'trip'): ?>
                  <?php if (!empty($row['start_date']) || !empty($row['end_date'])): ?>
                    <span>
                      <?= dt($row['start_date'] ?? null) ?>
                      <?php if (!empty($row['end_date'])): ?>
                        — <?= dt($row['end_date']) ?>
                      <?php endif; ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($row['updated_at'])): ?>
                    <span><?= (!empty($row['start_date']) || !empty($row['end_date'])) ? ' · ' : '' ?><?= te('home.updated') ?> <?= dt($row['updated_at']) ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span><?= te('vision.created') ?> <?= dt($row['created_at'] ?? null) ?></span>
                  <?php if (!empty($row['updated_at'])): ?>
                    <span> · <?= te('home.updated') ?> <?= dt($row['updated_at']) ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <?php if (!empty($row['is_favorite'])): ?>
                <div class="dash-card__fav" title="<?= te('dash.favorite') ?>">★</div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
<?php endif; ?>

</div>

<?php if (!empty($newShares) || !empty($handoffs) || !empty($notices)): ?>
<!-- Login notice: task updates + returned work (persist until checked) + new shares (one-time) -->
<div id="sharesNotice"
     style="position:fixed;inset:0;z-index:5000;display:flex;align-items:center;justify-content:center;">
  <div style="position:absolute;inset:0;background:rgba(0,0,0,.55);" data-dismiss></div>
  <div style="position:relative;max-width:520px;width:calc(100% - 2rem);
              background:#15161A;border:1px solid #2b3346;border-radius:14px;
              box-shadow:0 18px 50px rgba(0,0,0,.5);padding:1.3rem 1.4rem;
              max-height:calc(100vh - 4rem);overflow-y:auto;">

    <?php if (!empty($notices)): ?>
      <?php
        // Icon + the sentence it belongs to. Whole sentences are keyed
        // (":name assigned you: :goal") because word order moves between
        // languages; the names go in already escaped and in <strong>.
        $NOTICE_META = [
          'goal_assigned' => ['📋', 'dash.n_goal_assigned'],
          'goal_resolved' => ['✅', 'dash.n_goal_resolved'],
          'goal_returned' => ['↩',  'dash.n_goal_returned'],
          'goal_comment'  => ['💬', 'dash.n_goal_comment'],
        ];
      ?>
      <h2 style="margin:0 0 .2rem;font-size:1.25rem;">🔔 <?= te('dash.n_task_updates') ?></h2>
      <p style="margin:0 0 .9rem;opacity:.65;font-size:.9em;">
        <?= te('dash.n_check_hint') ?>
      </p>
      <div style="display:flex;flex-direction:column;gap:.45rem;margin-bottom:1rem;">
        <?php foreach ($notices as $n): ?>
          <?php [$icon, $nKey] = $NOTICE_META[$n['type']] ?? ['🔔', 'dash.n_goal_other']; ?>
          <div class="notice-row" data-id="<?= (int)$n['id'] ?>"
               style="padding:.6rem .7rem;background:rgba(255,255,255,.04);
                      border:1px solid #2b3346;border-radius:8px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem;">
              <span style="min-width:0;">
                <span style="display:block;font-size:.95em;color:#eaeaea;">
                  <?= $icon ?> <?= t($nKey, [
                      'name' => '<strong>' . dash_e($n['from_name'] ?: t('dash.n_someone')) . '</strong>',
                      'goal' => '<strong>' . dash_e($n['goal_title'] ?: t('dash.n_a_goal')) . '</strong>',
                  ]) /* raw on purpose: both values are escaped just above */ ?>
                </span>
                <span style="display:block;font-size:.8em;opacity:.6;margin-top:.15rem;">
                  <?php if (!empty($n['vision_slug'])): ?>
                    <a href="/visions/<?= dash_e($n['vision_slug']) ?>"
                       style="color:#8fb1d8;"><?= dash_e($n['vision_title'] ?: t('common.untitled')) ?></a> ·
                  <?php endif; ?>
                  <?= dash_e(dt($n['created_at']) . ', ' . date('H:i', strtotime($n['created_at']))) ?>
                </span>
              </span>
              <button type="button" class="notice-ack"
                      style="flex-shrink:0;padding:.3rem .7rem;border:1px solid #1e5530;
                             border-radius:999px;background:#15351f;color:#7ed99a;
                             font-size:.8rem;font-weight:700;cursor:pointer;">
                ✓ <?= te('dash.n_check') ?>
              </button>
            </div>
            <?php if (!empty($n['note'])): ?>
              <div style="margin-top:.45rem;padding:.5rem .6rem;background:#0f1014;
                          border-radius:6px;font-size:.88em;color:#c7d2df;white-space:pre-wrap;"><?= dash_e($n['note']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($handoffs)): ?>
      <h2 style="margin:0 0 .2rem;font-size:1.25rem;">📥 <?= te('dash.n_returned_title') ?></h2>
      <p style="margin:0 0 .9rem;opacity:.65;font-size:.9em;">
        <?= te('dash.n_returned_hint') ?>
      </p>
      <div id="handoffList" style="display:flex;flex-direction:column;gap:.45rem;margin-bottom:1rem;">
        <?php foreach ($handoffs as $h): ?>
          <div class="handoff-row" data-id="<?= (int)$h['id'] ?>"
               style="padding:.6rem .7rem;background:rgba(255,255,255,.04);
                      border:1px solid #2b3346;border-radius:8px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem;">
              <span style="min-width:0;">
                <a href="/visions/<?= dash_e($h['slug']) ?>"
                   style="display:block;font-weight:600;color:#8fb1d8;text-decoration:none;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= dash_e($h['title'] ?: t('common.untitled')) ?>
                </a>
                <span style="display:block;font-size:.8em;opacity:.6;">
                  <?= te('dash.n_from', ['name' => $h['from_name'] ?: t('dash.n_a_collaborator')]) ?>
                  · <?= dash_e(dt($h['created_at']) . ', ' . date('H:i', strtotime($h['created_at']))) ?>
                </span>
              </span>
              <button type="button" class="handoff-ack"
                      style="flex-shrink:0;padding:.3rem .7rem;border:1px solid #1e5530;
                             border-radius:999px;background:#15351f;color:#7ed99a;
                             font-size:.8rem;font-weight:700;cursor:pointer;">
                ✓ <?= te('dash.n_check') ?>
              </button>
            </div>
            <?php if (!empty($h['note'])): ?>
              <div style="margin-top:.45rem;padding:.5rem .6rem;background:#0f1014;
                          border-radius:6px;font-size:.88em;color:#c7d2df;white-space:pre-wrap;"><?= dash_e($h['note']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($newShares)): ?>
      <h2 style="margin:0 0 .2rem;font-size:1.25rem;">🤝 <?= te('dash.n_shares_title') ?></h2>
      <p style="margin:0 0 .9rem;opacity:.65;font-size:.9em;">
        <?= te('dash.n_shares_hint') ?>
      </p>
      <div style="display:flex;flex-direction:column;gap:.45rem;max-height:280px;overflow-y:auto;">
        <?php foreach ($newShares as $ns): ?>
          <a href="/visions/<?= dash_e($ns['slug']) ?>"
             style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;
                    padding:.55rem .7rem;background:rgba(255,255,255,.04);
                    border:1px solid #2b3346;border-radius:8px;
                    color:inherit;text-decoration:none;">
            <span style="min-width:0;">
              <span style="display:block;font-weight:600;color:#eaeaea;
                           white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= dash_e($ns['title'] ?: t('common.untitled')) ?>
              </span>
              <span style="display:block;font-size:.8em;opacity:.6;">
                <?= te('dash.n_from', ['name' => $ns['owner_name'] ?: mb_strtolower(t('dash.n_someone'))]) ?>
              </span>
            </span>
            <span style="flex-shrink:0;padding:.1rem .55rem;border-radius:999px;
                         background:#1f3a66;color:#8fb1d8;font-size:.75rem;font-weight:700;">
              <?= dash_e($dashRole[$ns['role']] ?? $ns['role']) ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <button type="button" data-dismiss
            style="margin-top:1rem;width:100%;padding:.65rem;border:0;border-radius:8px;
                   background:#3a76d2;color:#fff;font-size:1rem;font-weight:600;cursor:pointer;">
      <?= te('action.close') ?>
    </button>
  </div>
</div>
<script>
(() => {
  const notice = document.getElementById('sharesNotice');
  if (!notice) return;
  <?php if (!empty($newShares)): ?>
  // Shares are announced exactly once: mark seen the moment they render.
  fetch('/api/shares/seen', { method: 'POST' }).catch(() => {});
  <?php endif; ?>
  notice.addEventListener('click', async e => {
    // Per-item acknowledge for returned work
    const ack = e.target.closest('.handoff-ack');
    if (ack) {
      const row = ack.closest('.handoff-row');
      ack.disabled = true;
      try {
        const res = await fetch(`/api/handoffs/${row.dataset.id}/ack`, { method: 'POST' });
        const j = await res.json();
        if (j && j.success) row.remove();
        else ack.disabled = false;
      } catch { ack.disabled = false; }
      return;
    }
    // Per-item acknowledge for task updates
    const nack = e.target.closest('.notice-ack');
    if (nack) {
      const row = nack.closest('.notice-row');
      nack.disabled = true;
      try {
        const res = await fetch(`/api/notifications/${row.dataset.id}/ack`, { method: 'POST' });
        const j = await res.json();
        if (j && j.success) row.remove();
        else nack.disabled = false;
      } catch { nack.disabled = false; }
      return;
    }
    if (e.target.closest('a')) return; // let board links navigate
    if (e.target.closest('[data-dismiss]')) notice.remove();
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') notice.remove();
  });
})();
</script>
<?php endif; ?>
