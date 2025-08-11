<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>

<header>
  <?php include __DIR__ . '/partials/topbar.php'; ?>
</header>

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

<?php else: ?>
  <!-- Sidebar layout -->
  <div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

	  <?php if (!empty($boardType) && $boardType === 'vision'): ?>
	  <?php include __DIR__ . '/partials/overlay_basics.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_relations.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_goals.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_budget.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_roles.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_contacts.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_documents.php'; ?>
	  <?php include __DIR__ . '/partials/overlay_workflow.php'; ?>
	<?php endif; ?>

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
        // Close other open menus first
        document.querySelectorAll('.card-menu.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
        menu.classList.toggle('open');
      }
      e.stopPropagation();
      return;
    }
    // Click outside → close all
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
    if (!sidebar) return;
    const key = 'sidebarCollapsed';

    // Collapse by default on narrow screens (<=768px) unless user has a saved pref
    const saved = localStorage.getItem(key);
    if (saved === '1' || (saved === null && window.innerWidth <= 768)) {
      sidebar.classList.add('collapsed');
    }

    // Chevron button
    const btn = sidebar.querySelector('.sidebar-collapse');
    const setIcon = () => { if (btn) btn.textContent = sidebar.classList.contains('collapsed') ? '⟩' : '⟨'; };
    setIcon();
    if (btn) {
      btn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem(key, sidebar.classList.contains('collapsed') ? '1' : '0');
        setIcon();
      });
    }

    // Keyboard: [ to collapse, ] to expand
    document.addEventListener('keydown', (e) => {
      if (e.key === '[') {
        sidebar.classList.add('collapsed'); localStorage.setItem(key,'1'); setIcon();
      } else if (e.key === ']') {
        sidebar.classList.remove('collapsed'); localStorage.setItem(key,'0'); setIcon();
      }
    });

    // Swipe gestures on touch devices
    let touchStartX = null;
    document.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, {passive:true});
    document.addEventListener('touchmove', (e) => {
      if (touchStartX === null) return;
      const dx = e.touches[0].clientX - touchStartX;
      if (sidebar.classList.contains('collapsed')) {
        // swipe right to open (start at screen edge)
        if (touchStartX < 30 && dx > 50) {
          sidebar.classList.remove('collapsed');
          localStorage.setItem(key,'0'); setIcon();
          touchStartX = null;
        }
      } else {
        // swipe left anywhere over the sidebar to collapse
        if (touchStartX < sidebar.offsetWidth && dx < -50) {
          sidebar.classList.add('collapsed');
          localStorage.setItem(key,'1'); setIcon();
          touchStartX = null;
        }
      }
    }, {passive:true});
    document.addEventListener('touchend', () => { touchStartX = null; });

    // Lazy-load Trix on any page that uses it
    if (document.querySelector('trix-editor') && !document.querySelector('link[data-trix]')) {
      const l = document.createElement('link');
      l.rel = 'stylesheet';
      l.href = 'https://unpkg.com/trix@2.1.15/dist/trix.css';
      l.setAttribute('data-trix','');
      document.head.appendChild(l);

      const s = document.createElement('script');
      s.defer = true;
      s.src = 'https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js';
      s.setAttribute('data-trix','');
      document.body.appendChild(s);
    }
  })();
</script>

<!-- Anchors UI (add/remove + inline custom key, robust against blur) -->
<script>
(function(){
	const wrap = document.querySelector('.anchors');
  if (!wrap || wrap.dataset.enhanced === '1') return;  // prevents double binding
  wrap.dataset.enhanced = '1';
  let index = wrap.querySelectorAll('.anchors-row').length;
	
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
  // Handle overlay toggling
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

  // Handle Basics form via AJAX
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

<div id="connectivity-banner"></div>
<div id="snackbar" class="snackbar"></div>

</body>
</html>
