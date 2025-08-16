<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>
	
<?php include __DIR__ . '/partials/topbar.php'; ?>

<?php if (!empty($noSidebar) && $noSidebar === true): ?>
  <!-- Dream or Dashboard layout (no sidebar) -->
  <div class="container">
    <main><?= $content ?? '' ?></main>
  </div>

  <!-- Dream board modal & scripts -->
  <?php include __DIR__ . '/partials/dream-modal.php'; ?>
  <script src="/public/js/mobile-dream.js?v=11"></script>
  <script src="/public/js/offline-ui.js?v=3"></script>
  <script src="/public/js/trix-loader.js?v=1"></script>
  <script defer src="/public/js/ui.js"></script>

<?php else: ?>
  <!-- Sidebar layout -->
  <div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- NEW: external toggle button so it never hides with the sidebar -->
    <button class="sidebar-toggle" type="button" aria-label="Toggle sidebar" title="Toggle sidebar">
      <span class="arrow">◀</span>
    </button>

    <div class="content">
      <?= $content ?? '' ?>
    </div>
  </div>
<?php endif; ?>

<!-- Fold-out menus (Boards / + New board) -->
<script>
  document.addEventListener('click', e => {
    const toggle = e.target.closest('.menu-toggle');
    if (toggle) {
      const container = toggle.parentElement;
      const menu = container ? container.querySelector('.card-menu') : null;
      if (menu) {
        document.querySelectorAll('.card-menu.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
        menu.classList.toggle('open');
      }
      e.stopPropagation();
      return;
    }
    document.querySelectorAll('.card-menu.open').forEach(menu => {
      if (!menu.contains(e.target) && !menu.previousElementSibling?.contains(e.target)) {
        menu.classList.remove('open');
      }
    });
  });
</script>

<!-- Sidebar collapse: mobile default, swipe gestures, keyboard, persistence -->
<script>
(function(){
  const sidebar = document.querySelector('.sidebar');
  const externalBtn = document.querySelector('.sidebar-toggle');
  if (!sidebar || !externalBtn) return;

  // Hide any old inline toggle inside the sidebar (if present)
  const oldBtn = sidebar.querySelector('.sidebar-collapse');
  if (oldBtn) oldBtn.style.display = 'none';

  const key = 'sidebarCollapsed';

  // Collapse by default on narrow screens (<=768px) unless user has a saved pref
  const saved = localStorage.getItem(key);
  if (saved === '1' || (saved === null && window.innerWidth <= 768)) {
    sidebar.classList.add('collapsed');
  }

  // Arrow icon + button position
  const setIcon = () => {
    externalBtn.querySelector('.arrow').textContent =
      sidebar.classList.contains('collapsed') ? '▶' : '◀';
    setPosition();
  };

  const setPosition = () => {
    // Place the button near the visible edge of the sidebar
    const left = sidebar.classList.contains('collapsed')
      ? 8 // near screen edge when closed
      : Math.min(sidebar.offsetWidth + 8, 360); // just outside sidebar when open
    externalBtn.style.left = left + 'px';
  };

  // Toggle handler
  const toggle = () => {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem(key, sidebar.classList.contains('collapsed') ? '1' : '0');
    setIcon();
  };

  // Wire up
  externalBtn.addEventListener('click', toggle);
  window.addEventListener('resize', setPosition);
  setIcon(); // initial

  // Keyboard: [ to collapse, ] to expand
  document.addEventListener('keydown', (e) => {
    if (e.key === '[') { if (!sidebar.classList.contains('collapsed')) toggle(); }
    else if (e.key === ']') { if (sidebar.classList.contains('collapsed')) toggle(); }
  });

  // Swipe gestures on touch devices
  let touchStartX = null;
  document.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, {passive:true});
  document.addEventListener('touchmove', (e) => {
    if (touchStartX === null) return;
    const dx = e.touches[0].clientX - touchStartX;
    if (sidebar.classList.contains('collapsed')) {

      if (touchStartX < 30 && dx > 50) { toggle(); touchStartX = null; }
    } else {
      if (touchStartX < sidebar.offsetWidth && dx < -50) { toggle(); touchStartX = null; }
    }
  }, {passive:true});
  document.addEventListener('touchend', () => { touchStartX = null; });

  // Lazy-load Trix (unchanged)
  if (document.querySelector('trix-editor') && !document.querySelector('link[data-trix]')) {
    const l = document.createElement('link'); l.rel='stylesheet';
    l.href='https://unpkg.com/trix@2.1.15/dist/trix.css'; l.setAttribute('data-trix',''); document.head.appendChild(l);
    const s = document.createElement('script'); s.defer = true;
    s.src='https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js'; s.setAttribute('data-trix',''); document.body.appendChild(s);
  }
})();
</script>

<!-- Anchors UI (unchanged) -->
<script>
(function(){
  const wrap = document.querySelector('.anchors');
  if (!wrap || wrap.dataset.enhanced === '1') return;
  wrap.dataset.enhanced = '1';
  let index = wrap.querySelectorAll('.anchors-row').length;

  wrap.addEventListener('click', e => {
    if (e.target.closest('.add-anchor')) {
      const tpl = wrap.querySelector('.anchors-row');
      const row = tpl.cloneNode(true);
      row.querySelectorAll('input,select').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
        el.name = el.name.replace(/\[\d+\]/, '[' + index + ']');
      });
      wrap.insertBefore(row, wrap.querySelector('.add-anchor'));
      index++;
    }
    if (e.target.closest('.remove-anchor')) {
      const rows = wrap.querySelectorAll('.anchors-row');
      if (rows.length > 1) e.target.closest('.anchors-row').remove();
    }
  });

  // Inline custom key morphing (unchanged)
  wrap.addEventListener('change', e => {
    const select = e.target.closest('select.anchor-key');
    if (!select || select.value !== '__custom') return;

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'anchor-key';
    input.placeholder = 'Custom key';
    input.style.width = select.offsetWidth + 'px';
    input.dataset.name = select.name;
    select.replaceWith(input);
    input.focus();

    const buildSelect = (keyValue) => {
      const s = document.createElement('select');
      s.className = 'anchor-key';
      s.name = input.dataset.name || '';
      s.innerHTML = `
        <option value="">Choose…</option>
        <option>locations</option>
        <option>brands</option>
        <option>people</option>
        <option>seasons</option>
        <option>time</option>
        <option value="__custom">Custom…</option>`;
      if (keyValue) {
        const opt = document.createElement('option');
        opt.value = keyValue; opt.textContent = keyValue;
        const customOpt = s.querySelector('option[value="__custom"]');
        s.insertBefore(opt, customOpt); s.value = keyValue;
      }
      return s;
    };

    const finish = (commit = true) => {
      const parent = input.parentNode, next = input.nextSibling;
      const keyVal = commit ? input.value.trim() : '';
      const newSelect = buildSelect(keyVal);
      if (input.isConnected) input.replaceWith(newSelect);
      else if (parent) parent.insertBefore(newSelect, next || null);
      input.removeEventListener('blur', onBlur);
      input.removeEventListener('keydown', onKey);
    };

    const onBlur = () => finish(true);
    const onKey  = (ev) => { if (ev.key === 'Enter'){ev.preventDefault();finish(true)}
                              if (ev.key==='Escape'){ev.preventDefault();finish(false)} };
    input.addEventListener('blur', onBlur);
    input.addEventListener('keydown', onKey);
  });
})();
</script>

<script>
(function(){
  // Overlays (unchanged)
  document.querySelectorAll('[data-overlay]').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const name = link.getAttribute('data-overlay');
      const overlay = document.getElementById('overlay-' + name);
      if (overlay) overlay.classList.remove('overlay-hidden');
    });
  });
  document.querySelectorAll('.close-overlay').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      btn.closest('[id^="overlay-"]').classList.add('overlay-hidden');
    });
  });

  // Basics AJAX (unchanged)
  const form = document.getElementById('basicsForm');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(form);
      try {
        const res = await fetch('/visions/update-basics', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const json = await res.json();
        if (json.success) {
          form.closest('[id^="overlay-"]').classList.add('overlay-hidden');
          alert('Basics saved!');
        } else {
          alert('Error: ' + (json.error || 'Unknown'));
        }
      } catch(err) {
        alert('Failed to save basics');
      }
    });
  }
})();
</script>

<script>
(function(){
  const injectTrix = () => {
    if (document.querySelector('trix-editor') && !document.querySelector('link[data-trix]')) {
      const l = document.createElement('link');
      l.rel = 'stylesheet';
      l.href = 'https://unpkg.com/trix@2.1.15/dist/trix.css';
      l.setAttribute('data-trix','');
      document.head.appendChild(l);
      const s = document.createElement('script');
      s.defer = true;
      s.src   = 'https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js';
      s.setAttribute('data-trix','');
      document.body.appendChild(s);
    }
  };
  injectTrix();
  document.addEventListener('DOMContentLoaded', injectTrix, { once:true });
  new MutationObserver(injectTrix).observe(document.documentElement, { childList:true, subtree:true });
})();
</script>
 <script defer src="/public/js/overlay-contacts.js?v=1"></script>

<div id="connectivity-banner"></div>
<div id="snackbar" class="snackbar"></div>

</body>
</html>
</html>
