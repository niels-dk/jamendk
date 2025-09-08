// offline-ui.js?v=3

document.addEventListener('DOMContentLoaded', () => {
  const banner   = document.getElementById('connectivity-banner');
  const snack    = document.getElementById('snackbar');
  const grid     = document.querySelector('.dashboard-grid');
  const queueKey = 'dreamQueue';

  function showBanner(text, cls) {
    console.log('[Offline-UI] banner →', text, cls);
    banner.textContent = text;
    banner.className = 'show ' + cls;
    setTimeout(() => banner.className = '', 3000);
  }
  function showSnack(msg, dur = 3000) {
    console.log('[Offline-UI] snackbar →', msg);
    snack.textContent = msg;
    snack.classList.add('show');
    setTimeout(() => snack.classList.remove('show'), dur);
  }

  function injectOfflineCards() {
    console.log('[Offline-UI] injectOfflineCards()');
    if (!grid) return;
    removeOfflineCards();
    const q = JSON.parse(localStorage.getItem(queueKey) || '[]');
    console.log('[Offline-UI] queue length =', q.length);
    q.forEach((pl, idx) => {
      const card = document.createElement('div');
      card.className = 'dashboard-card offline-card';
      card.dataset.offlineIndex = idx;

      const h3 = document.createElement('h3');
      h3.textContent = pl.title || 'Untitled';
      card.appendChild(h3);

      if (pl.description) {
        const p = document.createElement('div');
        p.className = 'card-excerpt';
        p.textContent = pl.description;
        card.appendChild(p);
      }

      ['locations','brands','people','seasons'].forEach(key => {
        (pl[key]||[]).forEach(val => {
          console.log(`[Offline-UI] card #${idx} has anchor [${key}] →`, val);
          const span = document.createElement('span');
          span.className = 'chip';
          span.textContent = val;
          card.appendChild(span);
        });
      });

      const badge = document.createElement('div');
      badge.className = 'offline-label';
      badge.textContent = 'Offline';
      card.appendChild(badge);

      grid.prepend(card);
    });
  }

  function removeOfflineCards() {
    document.querySelectorAll('.offline-card').forEach(el => el.remove());
  }

  async function syncQueuedDreams() {
    console.log('[Offline-UI] syncQueuedDreams() start');
    const q = JSON.parse(localStorage.getItem(queueKey) || '[]');
    console.log('[Offline-UI] syncQueuedreams queue =', q);
    if (!q.length) {
      console.log('[Offline-UI] nothing to sync!');
      return;
    }
    let synced = 0;
    for (let i = 0; i < q.length; i++) {
      const pl = q[i];
      console.log(`[Offline-UI] syncing item #${i}`, pl);

      const fd = new FormData();
      Object.entries(pl).forEach(([k,v]) => {
        if (Array.isArray(v)) {
          v.forEach(x => {
            console.log(`[Offline-UI]   append ${k}[] →`, x);
            fd.append(k+'[]', x);
          });
        } else {
          console.log(`[Offline-UI]   append ${k} →`, v);
          fd.append(k, v);
        }
      });
      try {
        const res  = await fetch('/api/dreams/store.php', { method:'POST', body:fd });
        const json = await res.json();
        console.log('[Offline-UI]   server response →', json);
        if (!json.ok) throw new Error(json.error || 'Sync failed');
        synced++;
      } catch (err) {
        console.error('[Offline-UI] sync error at item #'+i, err);
        break;
      }
    }
    if (synced > 0) {
      console.log('[Offline-UI] successfully synced', synced);
      localStorage.removeItem(queueKey);
      showBanner(`Back online — synced ${synced} dream${synced>1?'s':''}`, 'online');
      showSnack(`✓ ${synced} offline dream${synced>1?'s':''} synced`);
      removeOfflineCards();
    }
  }

  // Initial load
  if (navigator.onLine) {
    //console.log('[Offline-UI] init (online)');
    syncQueuedDreams();
  } else {
    //console.log('[Offline-UI] init (offline)');
    showBanner('You are offline', 'offline');
    injectOfflineCards();
  }

  window.addEventListener('online', () => {
    //console.log('[Offline-UI] event: online');
    showBanner('You are back online', 'online');
    syncQueuedDreams();
  });
  window.addEventListener('offline', () => {
    //console.log('[Offline-UI] event: offline');
    showBanner('You are offline', 'offline');
    injectOfflineCards();
  });
});
