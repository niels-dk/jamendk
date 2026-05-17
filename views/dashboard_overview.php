<?php
// expects: $boardSets (dream|vision|mood|trip arrays), $sortValue, $limitValue
function t($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function human($t){ return ucfirst($t).'s'; }
function dt($s){ return $s ? date('M j, Y', strtotime($s)) : ''; }
?>
<div class="dash">

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
