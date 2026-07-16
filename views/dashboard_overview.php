<?php
// expects: $boardSets (dream|vision|mood|trip arrays), $sortValue, $limitValue
function t($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function human($t){ return ucfirst($t).'s'; }
function dt($s){ return $s ? date('M j, Y', strtotime($s)) : ''; }

// Sign-in lands here, not on the home page — so this is where a brand-new
// user actually meets the product. Four "No X yet" boxes is a poor first
// minute; offer them the worked example instead.
$dashEmpty = empty($boardSets['dream']) && empty($boardSets['vision'])
          && empty($boardSets['mood'])  && empty($boardSets['trip']);
?>
<div class="dash">

<?php if (!empty($_SESSION['flash_home'])): ?>
  <div class="dash__flash"><?= t($_SESSION['flash_home']) ?></div>
  <?php unset($_SESSION['flash_home']); ?>
<?php endif; ?>

<?php
// Founding Creator badge — in-app value anchor, no email (your call).
// Frames the free plan as a gift with a promise, never a countdown to a bill.
if (!empty($pricing) && !empty($pricing['is_founder'])):
  $pl_paid  = $pricing['tier']['is_paid'];
  $pl_saved = (int)$pricing['saved_cents'];
  $pl_since = $pricing['founding_at']
      ? date('M Y', strtotime($pricing['founding_at'])) : '';
?>
  <style>
    .founder-badge {
      display:flex; flex-wrap:wrap; align-items:center; gap:.6rem 1.1rem;
      background:linear-gradient(90deg, rgba(232,176,74,.10), rgba(58,118,210,.08));
      border:1px solid rgba(232,176,74,.28); border-radius:12px;
      padding:.7rem 1.1rem; margin:0 0 1.4rem; font-size:.9rem;
    }
    .founder-badge .fb-star { font-size:1.05rem; }
    .founder-badge .fb-title { font-weight:700; color:#eaf0f7; }
    .founder-badge .fb-sep { color:#3a4657; }
    .founder-badge .fb-plan { color:#9bb0c5; }
    .founder-badge .fb-plan b { color:#cfdbe8; font-weight:600; }
    .founder-badge .fb-saved { color:#e8c889; font-weight:600; }
    .founder-badge .fb-free {
      margin-left:auto; color:#7fc98d; font-weight:700; font-size:.82rem;
      white-space:nowrap;
    }
    .founder-badge a { color:#8fb1d8; text-decoration:none; }
    .founder-badge a:hover { text-decoration:underline; }
    @media (max-width:560px){ .founder-badge .fb-free { margin-left:0; } }
  </style>
  <div class="founder-badge">
    <span class="fb-star">✨</span>
    <span class="fb-title">Founding Creator</span>
    <span class="fb-sep">·</span>
    <span class="fb-plan">
      <?= t($pricing['tier']['label']) ?> plan —
      <b><?= (int)$pricing['seats'] ?> <?= $pricing['seats'] === 1 ? 'person' : 'people' ?></b><?php
      if ($pl_paid): ?>, normally <b><?= t(Pricing::money($pricing['monthly_cents'])) ?>/mo</b><?php endif; ?>
    </span>
    <?php if ($pl_paid && $pl_saved > 0): ?>
      <span class="fb-sep">·</span>
      <span class="fb-saved">
        <?= t(Pricing::money($pl_saved)) ?> saved<?= $pl_since ? ' since ' . t($pl_since) : '' ?>
      </span>
    <?php endif; ?>
    <span class="fb-free" title="You joined while it was free. It stays free at your team size — a thank-you for believing in it early.">
      Free forever for you 💛
    </span>
  </div>
<?php endif; ?>

<?php if ($dashEmpty): ?>
  <style>
    .dash__starter {
      display: flex; flex-wrap: wrap; align-items: center; gap: 1.2rem;
      justify-content: space-between;
      background: rgba(232,176,74,.07); border: 1px solid rgba(232,176,74,.28);
      border-radius: 14px; padding: 1.3rem 1.5rem; margin: 0 0 1.6rem;
    }
    .dash__starter h2 { margin: 0 0 .35rem; font-size: 1.1rem; color: #eaf0f7; }
    .dash__starter p  { margin: 0; color: #9bb0c5; font-size: .92rem;
                        line-height: 1.55; max-width: 46rem; }
    .dash__starter .ds-copy { flex: 1; min-width: 260px; }
    .dash__starter button {
      flex-shrink: 0; cursor: pointer; font-family: inherit;
      padding: .7rem 1.2rem; border: 0; border-radius: 10px;
      background: #3a76d2; color: #fff; font-weight: 600; font-size: .95rem;
    }
    .dash__starter button:hover { background: #2c5aa0; }
    .dash__flash {
      background: rgba(208,80,80,.15); border: 1px solid rgba(208,80,80,.4);
      color: #f3b3b3; padding: .7rem 1rem; border-radius: 8px;
      margin: 0 0 1.2rem; font-size: .9rem;
    }
  </style>
  <form method="post" action="/demo/load" class="dash__starter">
    <input type="hidden" name="csrf_token" value="<?= t(csrf_token()) ?>">
    <div class="ds-copy">
      <h2>🚚 New here? Start with a finished project.</h2>
      <p>
        Load a complete example — a Dream that grew into a Vision with a shot
        list, budget, contacts and a published Trip page. It lands in your
        account, so you can click through it, change it, and delete it once
        you've got the idea.
      </p>
    </div>
    <button type="submit">Load example project</button>
  </form>
<?php endif; ?>


  <div class="dash__topbar">
    <h1 class="dash__title">Where Dreams Connect</h1>

    <form method="get" class="dash__sort">
	  <label for="sort" class="dash__sort-label">Sort</label>
	  <select id="sort" name="sort" class="dash__select" onchange="this.form.submit()">
		<option value="latest"    <?= $sortValue==='latest'?'selected':''; ?>>Latest edit</option>
		<option value="newest"    <?= $sortValue==='newest'?'selected':''; ?>>Newest</option>
		<option value="favorites" <?= $sortValue==='favorites'?'selected':''; ?>>Favorites</option>
	  </select>

	  <label for="limit_each" class="dash__sort-label" style="margin-left:10px;">Show</label>
	  <select id="limit_each" name="limit_each" class="dash__select" onchange="this.form.submit()">
		<option value="0" <?= (int)$limitValue===0 ? 'selected':''; ?>>All</option>
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
        <h2 class="dash__section-title"><?= human($type) ?></h2>
        <a class="dash__seeall" href="/dashboard/<?= $type ?>">See all</a>
      </div>

      <?php if (!$items): ?>
        <?php if ($type === 'trip'): ?>
          <div class="dash__empty">
            <p>No trips ready yet.</p>
            <p style="opacity:.7;font-size:.9em;max-width:36rem;">
              A Trip is a shareable view generated from a Vision plus its Mood board.
              Open any Vision, link a Mood board in <strong>Relations</strong>, and the
              Vision will appear here. Use the <strong>Show on Trip layer</strong>
              toggles inside the Vision to choose which items publish.
            </p>
          </div>
        <?php else: ?>
          <div class="dash__empty">
            <p>No <?= strtolower(human($type)) ?> yet.</p>
            <a class="dash__btn" href="/<?= $type ?>s/new">Create <?= rtrim(human($type),'s') ?></a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <ul class="dash__grid">
          <?php foreach ($items as $row): ?>
            <li class="dash-card">
              <h3 class="dash-card__title">
                <a href="/<?= $type ?>s/<?= t($row['slug'] ?? '') ?>">
                  <?= t($row['title'] ?? 'Untitled') ?>
                </a>
                <?php if ($type === 'dream' && !empty($row['is_promoted'])): ?>
                  <span title="Promoted to a Vision"
                        style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                               border-radius:999px;background:rgba(58,118,210,.18);
                               border:1px solid rgba(58,118,210,.45);color:#a8c4ee;
                               font-size:.7rem;vertical-align:middle;font-weight:600;">
                    ✨ Promoted
                  </span>
                <?php endif; ?>
                <?php global $currentUserId;
                      $roleLbl = !empty($row['my_shared_role'])
                        ? ' · ' . ucfirst(str_replace('_', '-', $row['my_shared_role'])) : '';
                      if (!empty($row['user_id']) && (int)$row['user_id'] !== (int)$currentUserId): ?>
                  <span title="Shared with you (or another user's board, if you're admin)"
                        style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                               border-radius:999px;background:rgba(126,217,154,.14);
                               border:1px solid rgba(126,217,154,.4);color:#7ed99a;
                               font-size:.7rem;vertical-align:middle;font-weight:600;">
                    🤝 Shared<?= t($roleLbl) ?>
                  </span>
                <?php elseif (!empty($row['shared_with_names'])): ?>
                  <span title="You shared this board with: <?= t($row['shared_with_names']) ?>"
                        style="display:inline-block;margin-left:.4rem;padding:.05rem .4rem;
                               border-radius:999px;background:rgba(58,118,210,.14);
                               border:1px solid rgba(58,118,210,.4);color:#8fb1d8;
                               font-size:.7rem;vertical-align:middle;font-weight:600;">
                    📤 Shared with <?= t($row['shared_with_names']) ?>
                  </span>
                <?php endif; ?>
              </h3>

              <?php if ($type === 'trip'): ?>
                <?php
                  $bits = [];
                  if (!empty($row['mood_title'])) $bits[] = '🎨 ' . t($row['mood_title']);
                  $cn = (int)($row['contact_count'] ?? 0);
                  if ($cn > 0) $bits[] = '👥 ' . $cn . ' contact' . ($cn === 1 ? '' : 's');
                  if (!empty($row['has_budget'])) $bits[] = '💰 Budget';
                ?>
                <?php if ($bits): ?>
                  <p class="dash-card__snippet">
                    <?= implode(' &nbsp;·&nbsp; ', $bits) /* intentional raw — emojis */ ?>
                  </p>
                <?php endif; ?>
              <?php else: ?>
                <?php
                  $desc = $row['description'] ?? '';
                  if ($desc) $desc = mb_strimwidth(strip_tags($desc), 0, 130, '…', 'UTF-8');
                ?>
                <?php if ($desc): ?>
                  <p class="dash-card__snippet"><?= t($desc) ?></p>
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
                    <span><?= (!empty($row['start_date']) || !empty($row['end_date'])) ? ' · ' : '' ?>Updated <?= dt($row['updated_at']) ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span>Created <?= dt($row['created_at'] ?? null) ?></span>
                  <?php if (!empty($row['updated_at'])): ?>
                    <span> · Updated <?= dt($row['updated_at']) ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <?php if (!empty($row['is_favorite'])): ?>
                <div class="dash-card__fav" title="Favorite">★</div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

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
        $NOTICE_META = [
          'goal_assigned' => ['📋', 'assigned you'],
          'goal_resolved' => ['✅', 'resolved'],
          'goal_returned' => ['↩',  'sent back'],
          'goal_comment'  => ['💬', 'commented on'],
        ];
      ?>
      <h2 style="margin:0 0 .2rem;font-size:1.25rem;">🔔 Task updates</h2>
      <p style="margin:0 0 .9rem;opacity:.65;font-size:.9em;">
        Check each item once you've seen it — unchecked items will show again next time.
      </p>
      <div style="display:flex;flex-direction:column;gap:.45rem;margin-bottom:1rem;">
        <?php foreach ($notices as $n): ?>
          <?php [$icon, $verb] = $NOTICE_META[$n['type']] ?? ['🔔', $n['type']]; ?>
          <div class="notice-row" data-id="<?= (int)$n['id'] ?>"
               style="padding:.6rem .7rem;background:rgba(255,255,255,.04);
                      border:1px solid #2b3346;border-radius:8px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem;">
              <span style="min-width:0;">
                <span style="display:block;font-size:.95em;color:#eaeaea;">
                  <?= $icon ?> <strong><?= t($n['from_name'] ?: 'Someone') ?></strong>
                  <?= t($verb) ?>:
                  <strong><?= t($n['goal_title'] ?: 'a goal') ?></strong>
                </span>
                <span style="display:block;font-size:.8em;opacity:.6;margin-top:.15rem;">
                  <?php if (!empty($n['vision_slug'])): ?>
                    on <a href="/visions/<?= t($n['vision_slug']) ?>"
                          style="color:#8fb1d8;"><?= t($n['vision_title'] ?: 'Untitled') ?></a> ·
                  <?php endif; ?>
                  <?= t(date('M j, H:i', strtotime($n['created_at']))) ?>
                </span>
              </span>
              <button type="button" class="notice-ack"
                      style="flex-shrink:0;padding:.3rem .7rem;border:1px solid #1e5530;
                             border-radius:999px;background:#15351f;color:#7ed99a;
                             font-size:.8rem;font-weight:700;cursor:pointer;">
                ✓ Check
              </button>
            </div>
            <?php if (!empty($n['note'])): ?>
              <div style="margin-top:.45rem;padding:.5rem .6rem;background:#0f1014;
                          border-radius:6px;font-size:.88em;color:#c7d2df;white-space:pre-wrap;"><?= t($n['note']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($handoffs)): ?>
      <h2 style="margin:0 0 .2rem;font-size:1.25rem;">📥 Work returned to you</h2>
      <p style="margin:0 0 .9rem;opacity:.65;font-size:.9em;">
        Collaborators finished their part. Check each item once you've reviewed it —
        unchecked items will show again next time.
      </p>
      <div id="handoffList" style="display:flex;flex-direction:column;gap:.45rem;margin-bottom:1rem;">
        <?php foreach ($handoffs as $h): ?>
          <div class="handoff-row" data-id="<?= (int)$h['id'] ?>"
               style="padding:.6rem .7rem;background:rgba(255,255,255,.04);
                      border:1px solid #2b3346;border-radius:8px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem;">
              <span style="min-width:0;">
                <a href="/visions/<?= t($h['slug']) ?>"
                   style="display:block;font-weight:600;color:#8fb1d8;text-decoration:none;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= t($h['title'] ?: 'Untitled') ?>
                </a>
                <span style="display:block;font-size:.8em;opacity:.6;">
                  from <?= t($h['from_name'] ?: 'a collaborator') ?>
                  · <?= t(date('M j, H:i', strtotime($h['created_at']))) ?>
                </span>
              </span>
              <button type="button" class="handoff-ack"
                      style="flex-shrink:0;padding:.3rem .7rem;border:1px solid #1e5530;
                             border-radius:999px;background:#15351f;color:#7ed99a;
                             font-size:.8rem;font-weight:700;cursor:pointer;">
                ✓ Check
              </button>
            </div>
            <?php if (!empty($h['note'])): ?>
              <div style="margin-top:.45rem;padding:.5rem .6rem;background:#0f1014;
                          border-radius:6px;font-size:.88em;color:#c7d2df;white-space:pre-wrap;"><?= t($h['note']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($newShares)): ?>
      <h2 style="margin:0 0 .2rem;font-size:1.25rem;">🤝 New boards shared with you</h2>
      <p style="margin:0 0 .9rem;opacity:.65;font-size:.9em;">
        Since your last visit, these boards were shared with you:
      </p>
      <div style="display:flex;flex-direction:column;gap:.45rem;max-height:280px;overflow-y:auto;">
        <?php foreach ($newShares as $ns): ?>
          <a href="/visions/<?= t($ns['slug']) ?>"
             style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;
                    padding:.55rem .7rem;background:rgba(255,255,255,.04);
                    border:1px solid #2b3346;border-radius:8px;
                    color:inherit;text-decoration:none;">
            <span style="min-width:0;">
              <span style="display:block;font-weight:600;color:#eaeaea;
                           white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= t($ns['title'] ?: 'Untitled') ?>
              </span>
              <span style="display:block;font-size:.8em;opacity:.6;">
                from <?= t($ns['owner_name'] ?: 'someone') ?>
              </span>
            </span>
            <span style="flex-shrink:0;padding:.1rem .55rem;border-radius:999px;
                         background:#1f3a66;color:#8fb1d8;font-size:.75rem;font-weight:700;">
              <?= t(ucfirst(str_replace('_', '-', $ns['role']))) ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <button type="button" data-dismiss
            style="margin-top:1rem;width:100%;padding:.65rem;border:0;border-radius:8px;
                   background:#3a76d2;color:#fff;font-size:1rem;font-weight:600;cursor:pointer;">
      Close
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
