<?php
// views/partials/overlay_basics.php
// Expects: $vision (id, start_date, end_date, slug, trip_enabled), $presentationFlags (assoc)

$defaults = [
  'relations'=>1,'goals'=>1,'budget'=>1,'roles'=>0,'contacts'=>1,'documents'=>1,'workflow'=>1
];
$flags = array_replace($defaults, $presentationFlags ?? []);

$visionId    = (int)($vision['id'] ?? 0);
$visionSlug  = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$startDate   = (string)($vision['start_date'] ?? '');
$endDate     = (string)($vision['end_date']   ?? '');
$tripEnabled = !empty($vision['trip_enabled']);

// Public share link (token columns may not be migrated yet)
$tripToken   = (string)($vision['trip_token'] ?? '');
$tripExpires = (string)($vision['trip_token_expires_at'] ?? '');
$shareScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shareUrl    = $tripToken
    ? $shareScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/t/' . $tripToken
    : '';
?>

<div class="overlay-header">
  <h2>Vision Basics</h2>
</div>

<form id="basicsForm" class="overlay-form" action="/visions/update-basics" method="post" data-slug="<?= $visionSlug ?>">
  <input type="hidden" name="vision_id" value="<?= $visionId ?>">

  <label for="start-date">Start date</label>
  <input id="start-date" type="date" name="start_date" value="<?= htmlspecialchars($startDate, ENT_QUOTES) ?>">

  <label for="end-date">End date</label>
  <input id="end-date" type="date" name="end_date" value="<?= htmlspecialchars($endDate, ENT_QUOTES) ?>">

  <h4 style="margin-top:1.2rem;">Trip publishing</h4>

  <label class="switch switch-row" title="Master switch — when off, the trip page is not available.">
    <span class="switch-label">
      <strong>Publish as Trip</strong>
      <span style="display:block;opacity:.6;font-size:.8em;margin-top:.1rem;">
        Master switch — when off, /trips/<?= $visionSlug ?> shows "not published".
      </span>
    </span>
    <input class="switch-input" type="checkbox" name="trip_enabled" <?= $tripEnabled ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>

  <!-- Public share link (visible while published) -->
  <div id="tripShareBox" style="display:<?= $tripEnabled ? 'block' : 'none' ?>;
        margin-top:.6rem;padding:.7rem .8rem;border:1px solid #2b3f5f;
        border-radius:8px;background:rgba(58,118,210,.08);">
    <label style="font-size:.8em;opacity:.75;display:block;margin-bottom:.3rem;">
      Public share link — anyone with it can view the trip
    </label>
    <div style="display:flex;gap:.4rem;">
      <input id="tripShareUrl" type="text" readonly value="<?= htmlspecialchars($shareUrl, ENT_QUOTES) ?>"
             style="flex:1;min-width:0;background:#0f1014;border:1px solid #2b3346;color:#8fb1d8;
                    border-radius:6px;padding:.4rem .55rem;font-size:.82em;font-family:monospace;">
      <button type="button" class="btn" id="tripShareCopy" style="flex-shrink:0;padding:.35rem .7rem;">Copy</button>
    </div>
    <div style="display:flex;gap:.4rem;align-items:center;margin-top:.5rem;flex-wrap:wrap;">
      <select id="tripShareExpiry"
              style="background:#15161A;border:1px solid #2b3346;color:#ddd;
                     padding:.3rem .5rem;border-radius:6px;font-size:.85em;">
        <option value="0">Never expires</option>
        <option value="7">Expire in 7 days</option>
        <option value="30">Expire in 30 days</option>
      </select>
      <button type="button" class="btn" id="tripShareRegen"
              title="Mint a fresh link — the old one stops working"
              style="padding:.3rem .7rem;font-size:.85em;">↻ New link</button>
      <span id="tripShareStatus" style="opacity:.65;font-size:.8em;">
        <?= $tripExpires ? 'Expires ' . htmlspecialchars(date('M j, Y', strtotime($tripExpires)), ENT_QUOTES) : '' ?>
      </span>
    </div>
  </div>

  <h4 style="margin-top:1.2rem;">Show on Trip layer</h4>
  <p style="opacity:.6;font-size:.85em;margin:0 0 .6rem;">
    Choose which sections appear when this trip is published.
  </p>

  <?php foreach ($defaults as $section => $_): ?>
    <?php
      $id = 'flag_' . $section;
      $checked = !empty($flags[$section]) ? 'checked' : '';
      $label = ucfirst($section);
    ?>
    <label class="switch switch-row" style="opacity:<?= $tripEnabled ? '1' : '.45' ?>;">
      <span class="switch-label"><?= $label ?></span>
      <input class="switch-input" type="checkbox" name="<?= $section ?>" <?= $checked ?>>
      <span class="knob" aria-hidden="true"></span>
    </label>
  <?php endforeach; ?>
</form>

<script>
(() => {
  const form   = document.getElementById('basicsForm');
  if (!form) return;

  const slug   = form.dataset.slug || '';
  const start  = form.querySelector('#start-date');
  const end    = form.querySelector('#end-date');

  // ——— Dates: auto-save
  function saveDates() {
    const p = new URLSearchParams();
    p.set('vision_id', form.querySelector('[name="vision_id"]').value);
    p.set('start_date', start.value.trim());
    p.set('end_date',   end.value.trim());

    fetch('/api/visions/update-basics', {
      method:'POST',
      headers:{ 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
      body: p.toString()
    }).then(r => r.json()).then(j => {
      if (!j.success) console.error('Date save failed:', j);
    }).catch(e => console.error('Date save error:', e));
  }
  [start, end].forEach(el => el && el.addEventListener('change', saveDates));

  // ——— Public share link box ———
  const shareBox    = document.getElementById('tripShareBox');
  const shareUrl    = document.getElementById('tripShareUrl');
  const shareCopy   = document.getElementById('tripShareCopy');
  const shareRegen  = document.getElementById('tripShareRegen');
  const shareExpiry = document.getElementById('tripShareExpiry');
  const shareStatus = document.getElementById('tripShareStatus');

  function applyShare(s) {
    if (!s) return;
    if (s.url) shareUrl.value = s.url;
    if (shareStatus) {
      shareStatus.textContent = s.expires_at
        ? 'Expires ' + new Date(s.expires_at.replace(' ', 'T')).toLocaleDateString()
        : '';
    }
  }
  async function shareAction(params) {
    try {
      const res = await fetch(`/api/visions/${encodeURIComponent(slug)}/trip-share`, {
        method:'POST',
        headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params).toString()
      });
      const j = await res.json();
      if (j?.success) applyShare(j);
      else if (shareStatus) shareStatus.textContent = '⚠ ' + (j?.error || 'Failed');
      return j;
    } catch { if (shareStatus) shareStatus.textContent = '⚠ Network error'; }
  }
  shareCopy?.addEventListener('click', async () => {
    if (!shareUrl.value) return;
    try {
      await navigator.clipboard.writeText(shareUrl.value);
      shareCopy.textContent = '✓ Copied';
      setTimeout(() => shareCopy.textContent = 'Copy', 1200);
    } catch {
      shareUrl.select(); document.execCommand('copy');
    }
  });
  shareRegen?.addEventListener('click', async () => {
    if (!confirm('Mint a new link? The current one stops working immediately.')) return;
    await shareAction({ action: 'regenerate' });
  });
  shareExpiry?.addEventListener('change', () => {
    shareAction({ action: 'expiry', days: shareExpiry.value });
  });

  // ——— Section switches: auto-save per toggle to /api/visions/{slug}/basics
  // We send a tiny payload: flag=<section>&enabled=1|0
  function saveFlag(section, enabled) {
    if (!slug) return;
    const p = new URLSearchParams();
    p.set('flag', section);
    p.set('enabled', enabled ? '1' : '0');

    fetch(`/api/visions/${encodeURIComponent(slug)}/basics`, {
      method:'POST',
      headers:{ 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
      body: p.toString()
    }).then(r => r.json()).then(j => {
      // Publishing mints/returns the share link — reflect it live
      if (section === 'trip_enabled') {
        if (shareBox) shareBox.style.display = enabled ? 'block' : 'none';
        if (enabled && j?.share) applyShare(j.share);
      }
    }).catch(()=>{});
  }

  // Visually dim section toggles when the master switch is off
  function refreshSectionDim() {
    const master = form.querySelector('[name="trip_enabled"]');
    const on = master ? master.checked : true;
    form.querySelectorAll('.switch.switch-row').forEach(row => {
      const cb = row.querySelector('input[type="checkbox"]');
      if (!cb || cb.name === 'trip_enabled') return;
      row.style.opacity = on ? '1' : '.45';
    });
  }

  form.querySelectorAll('.switch-input').forEach(cb => {
    cb.addEventListener('change', () => {
      // name is the flag key: trip_enabled OR a section
      // (relations/goals/budget/roles/contacts/documents/workflow)
      saveFlag(cb.name, cb.checked);
      if (cb.name === 'trip_enabled') refreshSectionDim();
    });
  });
})();
</script>
