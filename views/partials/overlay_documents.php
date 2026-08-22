<?php
// Expects: $vision (slug), $docs (array of rows: uuid, file_name, status, version, group_id, group_name, created_at)
$slug = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$STATUSES = [
  'draft'         => t('docs.draft'),
  'waiting_brand' => t('docs.waiting_brand'),
  'final'         => t('docs.final'),
  'signed'        => t('docs.signed'),
];
?>

<div class="overlay-header">
  <h2><?= te('sec.documents') ?></h2>
</div>

<div id="docsWrap" data-slug="<?= $slug ?>">
  <form id="documentUploadForm" enctype="multipart/form-data">
    <input type="file" name="file[]" multiple>
    <button type="submit" class="btn primary"><?= te('docs.upload') ?></button>
    <span id="uploadStatus" class="hint"></span>
  </form>

  <div id="docsList" class="docs-list">
    <?php foreach (($docs ?? []) as $doc): ?>
      <?php
        $statusKey   = $doc['status'] ?? 'draft';
        $statusLabel = $STATUSES[$statusKey] ?? ucfirst($statusKey);
      ?>
      <?php $onTrip = !array_key_exists('show_on_trip', $doc) || (int)$doc['show_on_trip'] === 1; ?>
      <div class="doc-row" data-uuid="<?= htmlspecialchars($doc['uuid'], ENT_QUOTES) ?>">
        <div class="doc-main">
          <div class="doc-name" title="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($doc['file_name']) ?>
          </div>
          <div class="doc-meta">
            <span class="status-pill js-status" data-status="<?= htmlspecialchars($statusKey, ENT_QUOTES) ?>">
              <?= htmlspecialchars($statusLabel) ?>
            </span>
            <span class="group-pill js-group" data-current="<?= isset($doc['group_id']) ? (int)$doc['group_id'] : '' ?>">
              <?= !empty($doc['group_name']) ? htmlspecialchars($doc['group_name']) : te('docs.no_group') ?>
            </span>
            <button type="button" class="trip-pill js-trip"
                    data-on="<?= $onTrip ? '1' : '0' ?>"
                    title="<?= te('docs.trip_toggle_tip') ?>">
              <?= $onTrip ? '👁 ' . te('docs.on_trip') : '🚫 ' . te('docs.off_trip') ?>
            </button>
            <span class="doc-date"><?= date('Y-m-d H:i', strtotime($doc['created_at'])) ?></span>
          </div>
        </div>
        <div class="doc-actions">
          <a class="action-link" href="/documents/<?= htmlspecialchars($doc['uuid'], ENT_QUOTES) ?>/download"><?= te('docs.download') ?></a>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($docs)): ?>
      <div class="muted" style="opacity:.6;padding:.6rem 0;"><?= te('docs.none_yet') ?></div>
    <?php endif; ?>
  </div>

  <div id="docMenu" class="doc-menu" hidden></div>
</div>

<style>
  #docsWrap #documentUploadForm {
    display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
    padding:.6rem; background:#15161A; border:1px solid #2b3346; border-radius:8px;
    margin-bottom:.8rem;
  }
  #docsWrap #uploadStatus { font-size:.85em; opacity:.7; }
  #docsWrap .docs-list { display:flex; flex-direction:column; gap:.5rem; }
  #docsWrap .doc-row {
    display:flex; align-items:center; justify-content:space-between; gap:.6rem;
    padding:.6rem .7rem; background:rgba(255,255,255,.04);
    border:1px solid #2b3346; border-radius:8px;
  }
  #docsWrap .doc-main { min-width:0; flex:1; }
  #docsWrap .doc-name {
    font-weight:600; color:#eaeaea;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  #docsWrap .doc-meta {
    display:flex; flex-wrap:wrap; gap:.4rem; align-items:center;
    margin-top:.3rem; font-size:.82em;
  }
  #docsWrap .doc-date { opacity:.6; font-family:monospace; }
  #docsWrap .doc-actions { flex-shrink:0; }
  #docsWrap .action-link { color:#7ab7ff; text-decoration:none; font-size:.9em; }
  #docsWrap .action-link:hover { text-decoration:underline; }

  /* Status pills – color coded */
  #docsWrap .status-pill {
    display:inline-block; padding:.15rem .55rem;
    border-radius:999px; font-size:.78rem; cursor:pointer;
    border:1px solid transparent; user-select:none;
  }
  #docsWrap .status-pill[data-status="draft"]         { background:#2a2d35; color:#bbb; border-color:#3a3f4a; }
  #docsWrap .status-pill[data-status="waiting_brand"] { background:#3a2f10; color:#e8c267; border-color:#5a4818; }
  #docsWrap .status-pill[data-status="final"]         { background:#15351f; color:#7ed99a; border-color:#1e5530; }
  #docsWrap .status-pill[data-status="signed"]        { background:#152a45; color:#7ab7ff; border-color:#1f4070; }
  #docsWrap .status-pill:hover { filter:brightness(1.15); }

  /* Group pill */
  #docsWrap .group-pill {
    display:inline-block; padding:.15rem .55rem;
    border-radius:999px; font-size:.78rem; cursor:pointer;
    background:#15161A; border:1px solid #2b3346; color:#bbb;
  }
  #docsWrap .group-pill:hover { background:#1e2230; }

  /* Trip-visibility pill (per document) */
  #docsWrap .trip-pill {
    display:inline-block; padding:.15rem .55rem;
    border-radius:999px; font-size:.78rem; cursor:pointer;
    border:1px solid transparent; font: inherit;
  }
  #docsWrap .trip-pill[data-on="1"] { background:#15263a; color:#8fb1d8; border-color:#1f3a5a; }
  #docsWrap .trip-pill[data-on="0"] { background:#2a1f1f; color:#a06868; border-color:#4a2a2a; }
  #docsWrap .trip-pill:hover { filter:brightness(1.15); }

  /* Custom dropdown menu (replaces native select) */
  .doc-menu {
    position:fixed; min-width:160px;
    background:#1a1d24; border:1px solid #2b3346; border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.4); z-index:2000;
    padding:.25rem; overflow:hidden;
  }
  .doc-menu button {
    display:block; width:100%; text-align:left;
    background:transparent; border:0; color:#ddd;
    padding:.45rem .6rem; border-radius:6px; cursor:pointer;
    font-size:.85em;
  }
  .doc-menu button:hover { background:#2a2f3a; }
  .doc-menu button.is-active { background:#1f3a66; color:#fff; }
  .doc-menu .menu-sep { height:1px; background:#2b3346; margin:.25rem 0; }
</style>

<script>
(() => {
  const wrap   = document.getElementById('docsWrap');
  if (!wrap) return;
  const slug   = wrap.dataset.slug;
  const list   = wrap.querySelector('#docsList');
  const menu   = wrap.querySelector('#docMenu');
  const form   = wrap.querySelector('#documentUploadForm');
  const statusEl = wrap.querySelector('#uploadStatus');
  const T = <?= json_encode([
    'draft'         => t('docs.draft'),
    'waiting_brand' => t('docs.waiting_brand'),
    'final'         => t('docs.final'),
    'signed'        => t('docs.signed'),
    'no_group'      => t('docs.no_group'),
    'new_group'     => t('docs.new_group'),
    'new_group_prompt' => t('docs.new_group_prompt'),
    'on_trip'       => t('docs.on_trip'),
    'off_trip'      => t('docs.off_trip'),
    'download'      => t('docs.download'),
    'update_failed' => t('docs.update_failed'),
    'status_failed' => t('docs.status_failed'),
    'create_failed' => t('docs.create_failed'),
    'choose_file'   => t('docs.choose_file'),
    'uploaded'      => t('docs.uploaded'),
    'upload_failed' => t('docs.upload_failed'),
    'net_error'     => t('status.net_error'),
    'uploading'     => t('docs.uploading'),
  ], JSON_UNESCAPED_UNICODE) ?>;

  const STATUSES = [
    { key:'draft',         label:T.draft },
    { key:'waiting_brand', label:T.waiting_brand },
    { key:'final',         label:T.final },
    { key:'signed',        label:T.signed },
  ];

  let groupsCache = null;
  async function loadGroups() {
    if (groupsCache) return groupsCache;
    const res = await fetch(`/api/visions/${slug}/groups`);
    const j   = await res.json();
    groupsCache = j.success ? j.groups : [];
    return groupsCache;
  }

  function closeMenu() {
    menu.hidden = true;
    menu.innerHTML = '';
    menu._anchor = null;
  }

  // If the overlay panel scrolls or the window resizes, reposition or close the menu
  const overlayPanel = document.querySelector('#overlay-shell .overlay-panel');
  ['scroll','resize'].forEach(ev => window.addEventListener(ev, closeMenu, { passive:true }));
  if (overlayPanel) overlayPanel.addEventListener('scroll', closeMenu, { passive:true });
  function openMenuAt(anchor, html, handler) {
    // Move menu to <body> so it isn't clipped by the overlay panel's scroll
    if (menu.parentElement !== document.body) document.body.appendChild(menu);
    menu.innerHTML = html;
    menu.hidden = false;
    const r = anchor.getBoundingClientRect();
    // position:fixed → use viewport coords directly
    let top = r.bottom + 4;
    let left = r.left;
    const mw = Math.max(160, menu.offsetWidth || 200);
    if (left + mw > window.innerWidth - 8)  left = window.innerWidth - mw - 8;
    if (top + 200 > window.innerHeight - 8) top = Math.max(8, r.top - 8 - 200);
    menu.style.top  = `${top}px`;
    menu.style.left = `${left}px`;
    menu._anchor    = anchor;
    menu._handler   = handler;
  }

  // Close menu on outside click / Esc
  document.addEventListener('click', e => {
    if (!menu.hidden && !menu.contains(e.target) && e.target !== menu._anchor) closeMenu();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });

  // Click on status pill
  list.addEventListener('click', async e => {
    const sPill = e.target.closest('.js-status');
    const gPill = e.target.closest('.js-group');
    const tPill = e.target.closest('.js-trip');
    if (!sPill && !gPill && !tPill) return;
    e.stopPropagation();

    const row  = e.target.closest('.doc-row');
    const uuid = row.dataset.uuid;

    if (tPill) {
      const next = tPill.dataset.on === '1' ? 0 : 1;
      const fd = new URLSearchParams(); fd.set('show_on_trip', String(next));
      try {
        const res = await fetch(`/api/documents/${uuid}/trip`, {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body: fd.toString()
        });
        const j = await res.json();
        if (!res.ok || !j.success) { alert(j.error || T.update_failed); return; }
        tPill.dataset.on = String(next);
        tPill.textContent = next ? '👁 ' + T.on_trip : '🚫 ' + T.off_trip;
      } catch { alert(T.net_error); }
      return;
    }

    if (sPill) {
      const current = sPill.dataset.status;
      const html = STATUSES.map(s =>
        `<button type="button" data-key="${s.key}" class="${s.key===current?'is-active':''}">${s.label}</button>`
      ).join('');
      openMenuAt(sPill, html, async (key) => {
        const fd = new URLSearchParams(); fd.set('status', key);
        const res = await fetch(`/api/documents/${uuid}/status`, {
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: fd.toString()
        });
        const j = await res.json();
        if (!res.ok || !j.success) { alert(j.error || T.status_failed); return; }
        const found = STATUSES.find(s => s.key === key);
        sPill.dataset.status = key;
        sPill.textContent = found ? found.label : key;
      });
      return;
    }

    if (gPill) {
      const groups = await loadGroups();
      const current = gPill.dataset.current || '';
      const items = [
        `<button type="button" data-id="" class="${current===''?'is-active':''}">${T.no_group}</button>`,
        ...groups.map(g => `<button type="button" data-id="${g.id}" class="${String(g.id)===String(current)?'is-active':''}">${g.name}</button>`),
        `<div class="menu-sep"></div>`,
        `<button type="button" data-id="__create__">+ ${T.new_group}</button>`
      ].join('');
      openMenuAt(gPill, items, async (id) => {
        if (id === '__create__') {
          const name = prompt(T.new_group_prompt);
          if (!name) return;
          const fd = new FormData(); fd.append('name', name);
          const r1 = await fetch(`/api/visions/${slug}/groups:create`, { method:'POST', body: fd });
          const j1 = await r1.json();
          if (!r1.ok || !j1.success) { alert(j1.error || T.create_failed); return; }
          groupsCache.push(j1.group);
          id = String(j1.group.id);
        }
        const fd = new URLSearchParams(); fd.set('group_id', id);
        const r2 = await fetch(`/api/documents/${uuid}/group`, {
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: fd.toString()
        });
        const j2 = await r2.json();
        if (!r2.ok || !j2.success) { alert(j2.error || T.update_failed); return; }
        const g = (groupsCache || []).find(x => String(x.id) === String(id));
        gPill.dataset.current = id;
        gPill.textContent = g ? g.name : T.no_group;
      });
    }
  });

  menu.addEventListener('click', e => {
    const btn = e.target.closest('button[data-key], button[data-id]');
    if (!btn) return;
    const val = btn.dataset.key ?? btn.dataset.id;
    closeMenu();
    if (menu._handler) menu._handler(val);
  });

  // Upload handling
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    const files = fd.getAll('file[]');
    if (!files.length) { statusEl.textContent = T.choose_file; return; }
    statusEl.textContent = T.uploading;
    try {
      const res = await fetch(`/api/visions/${slug}/documents`, { method:'POST', body: fd });
      const j   = await res.json();
      if (!res.ok || !j.success) { statusEl.textContent = '⚠ ' + (j.error || T.upload_failed); return; }
      (j.files || []).forEach(f => {
        const uploaded = (f.created_at || new Date().toISOString()).replace('T',' ').slice(0,16);
        const statusKey   = f.status || 'draft';
        const statusLabel = STATUSES.find(s => s.key === statusKey)?.label || statusKey;
        const row = document.createElement('div');
        row.className = 'doc-row';
        row.dataset.uuid = f.uuid;
        row.innerHTML = `
          <div class="doc-main">
            <div class="doc-name" title="${f.file_name}">${f.file_name}</div>
            <div class="doc-meta">
              <span class="status-pill js-status" data-status="${statusKey}">${statusLabel}</span>
              <span class="group-pill js-group" data-current="">${T.no_group}</span>
              <span class="doc-date">${uploaded}</span>
            </div>
          </div>
          <div class="doc-actions"><a class="action-link" href="${f.download_url}">${T.download}</a></div>`;
        list.prepend(row);
      });
      statusEl.textContent = '✅ ' + T.uploaded;
      form.reset();
    } catch (err) {
      statusEl.textContent = '⚠ ' + T.upload_failed;
      console.error(err);
    }
  });
})();
</script>
