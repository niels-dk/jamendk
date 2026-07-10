<?php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (!function_exists('icon')) {
  function icon($name) {
    $map = [
      'pin' => '<svg viewBox="0 0 24 24"><path d="M12 2a6 6 0 0 0-6 6c0 4.4 6 12 6 12s6-7.6 6-12a6 6 0 0 0-6-6zM12 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>',
      'bag' => '<svg viewBox="0 0 24 24"><path d="M6 7V6a6 6 0 1 1 12 0v1h3v15H3V7h3zm2 0h8V6a4 4 0 1 0-8 0v1z"/></svg>',
      'user' => '<svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z"/></svg>',
      'calendar' => '<svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14zm0-12H5V6h14z"/></svg>'
    ];
    return $map[$name] ?? '';
  }
}
$created = !empty($vision['created_at']) ? date('Y-m-d H:i:s', strtotime($vision['created_at'])) : '';
$updated = !empty($vision['updated_at']) ? date('Y-m-d H:i:s', strtotime($vision['updated_at'])) : '';
$anchors = $anchors ?? [];
?>
<h1><?= e($vision['title'] ?? 'Vision') ?></h1>

<div class="card" style="padding:1.25rem 1.25rem 1rem; max-width:1200px;">

  <?php if (!empty($sourceDream)): ?>
    <div style="margin-bottom:.9rem; padding:.5rem .8rem; border-radius:8px;
                background:rgba(58,118,210,.1); border:1px solid rgba(58,118,210,.3);
                font-size:.9em; opacity:.95;">
      🌕 From Dream:
      <a href="/dreams/<?= htmlspecialchars($sourceDream['slug']) ?>" style="margin-left:.25rem;">
        <?= htmlspecialchars($sourceDream['title'] ?: 'Untitled') ?>
      </a>
    </div>
  <?php endif; ?>

  <div class="prose" style="margin-bottom:1rem; color:#c7d2df;">
    <?php if (!empty($vision['description'])): ?>
      <?= $vision['description'] ?>
    <?php else: ?>
      <p><?= htmlspecialchars($vision['description'] ?? 'This Vision board is under construction.') ?></p>
    <?php endif; ?>
  </div>

  <?php if (array_filter($anchors)): ?>
    <div class="anchor-grid">

      <?php if (!empty($anchors['locations'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('pin') ?> Locations</h3>
          <?php foreach ($anchors['locations'] as $l): ?>
            <span class="chip"><?= htmlspecialchars($l) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($anchors['brands'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('bag') ?> Brands</h3>
          <?php foreach ($anchors['brands'] as $b): ?>
            <span class="chip"><?= htmlspecialchars($b) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($anchors['people'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('user') ?> People</h3>
          <?php foreach ($anchors['people'] as $p): ?>
            <span class="chip"><?= htmlspecialchars($p) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($anchors['seasons'])): ?>
        <section class="anchor-block-view">
          <h3><?= icon('calendar') ?> Seasons / Time</h3>
          <?php foreach ($anchors['seasons'] as $s): ?>
            <span class="chip"><?= htmlspecialchars($s) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

    </div>
  <?php endif; ?>

  <div style="opacity:.8; margin-bottom:1rem;">
    <?php if ($created): ?>Created <?= e($created) ?><?php endif; ?>
    <?php if ($updated): ?> · Updated <?= e($updated) ?><?php endif; ?>
  </div>

  <?php
    global $db, $currentUserId;
    $myRole      = function_exists('vision_role') ? vision_role($db, $vision) : '';
    $isCollab    = $myRole !== '' && (int)$vision['user_id'] !== (int)$currentUserId && !is_admin();
  ?>
  <div class="btn-group">
    <?php if (function_exists('vision_can') && vision_can($db, $vision, 'edit')): ?>
      <a class="btn primary" href="/visions/<?= e($vision['slug']) ?>/edit">Edit Vision</a>
    <?php endif; ?>
    <?php if ($isCollab): ?>
      <button type="button" class="btn" id="btnHandoff"
              title="Tell the owner you're done — they get a note on their next visit">
        📤 Send back to owner
      </button>
    <?php endif; ?>
    <a class="btn" href="/dashboard/vision">Back to dashboard</a>
  </div>

  <?php if ($isCollab): ?>
    <style>
      #handoffModal { position:fixed; inset:0; z-index:5000; align-items:center; justify-content:center; }
      #handoffModal { display:none; }
      #handoffModal.is-open { display:flex; }
    </style>
    <div id="handoffModal">
      <div style="position:absolute;inset:0;background:rgba(0,0,0,.55);" data-close></div>
      <div style="position:relative;max-width:440px;width:calc(100% - 2rem);
                  background:#15161A;border:1px solid #2b3346;border-radius:14px;
                  box-shadow:0 18px 50px rgba(0,0,0,.5);padding:1.2rem 1.3rem;">
        <h3 style="margin:0 0 .3rem;">Send back to owner</h3>
        <p style="margin:0 0 .8rem;opacity:.65;font-size:.9em;">
          The owner will see your note the next time they open their dashboard.
        </p>
        <textarea id="handoffNote" rows="4" placeholder="Optional note — what did you do, what's left, anything to look at…"
                  style="width:100%;box-sizing:border-box;background:#0f1014;border:1px solid #2b3346;
                         color:#ddd;border-radius:8px;padding:.6rem .7rem;resize:vertical;"></textarea>
        <div style="display:flex;align-items:center;gap:.6rem;margin-top:.8rem;">
          <button type="button" class="btn primary" id="handoffSend">Send</button>
          <button type="button" class="btn" data-close>Cancel</button>
          <span id="handoffStatus" style="opacity:.6;font-size:.85em;"></span>
        </div>
      </div>
    </div>
    <script>
    (() => {
      const modal  = document.getElementById('handoffModal');
      const openB  = document.getElementById('btnHandoff');
      const sendB  = document.getElementById('handoffSend');
      const note   = document.getElementById('handoffNote');
      const status = document.getElementById('handoffStatus');
      if (!modal || !openB) return;
      const open  = () => { modal.classList.add('is-open'); note.focus(); };
      const close = () => { modal.classList.remove('is-open'); };
      openB.addEventListener('click', open);
      modal.addEventListener('click', e => {
        if (e.target.closest('[data-close]')) close();
      });
      document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
      sendB.addEventListener('click', async () => {
        status.textContent = 'Sending…';
        sendB.disabled = true;
        try {
          const p = new URLSearchParams();
          p.set('note', note.value.trim());
          const res = await fetch(`/api/visions/<?= e($vision['slug']) ?>/handoff`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: p.toString()
          });
          const j = await res.json();
          if (j && j.success) {
            status.textContent = '✅ Sent';
            setTimeout(() => { close(); status.textContent = ''; note.value = ''; sendB.disabled = false; }, 900);
          } else {
            status.textContent = '⚠ ' + (j?.error || 'Failed');
            sendB.disabled = false;
          }
        } catch {
          status.textContent = '⚠ Network error';
          sendB.disabled = false;
        }
      });
    })();
    </script>
  <?php endif; ?>
</div>
