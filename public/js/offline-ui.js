// offline-ui.js — what the dashboard pages SHOW about being offline: the
// connectivity banner, the "saved on this device" cards, and the result of a
// sync. The queue itself, and sending it, live in dream-sync.js (loaded just
// before this file); this used to do both, and lost ideas doing it.

document.addEventListener('DOMContentLoaded', () => {
  const banner = document.getElementById('connectivity-banner');
  const snack  = document.getElementById('snackbar');
  const grid   = document.querySelector('.dashboard-grid');
  const AT     = window.APP_T || {};
  const DS     = window.DreamSync;

  function showBanner(text, cls) {
    if (!banner) return;
    banner.textContent = text;
    banner.className = 'show ' + cls;
    setTimeout(() => banner.className = '', 3000);
  }
  function showSnack(msg, dur = 3000) {
    if (!snack) return;
    snack.textContent = msg;
    snack.classList.add('show');
    setTimeout(() => snack.classList.remove('show'), dur);
  }

  function removeOfflineCards() {
    document.querySelectorAll('.offline-card').forEach(el => el.remove());
  }

  // Ideas still waiting on this device, shown at the top of the list so it is
  // obvious they exist and are not on the server yet.
  function injectOfflineCards() {
    removeOfflineCards();
    if (!grid || !DS) return;
    DS.load().forEach((pl, idx) => {
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

      ['locations', 'brands', 'people', 'seasons'].forEach(key => {
        (pl[key] || []).forEach(val => {
          const span = document.createElement('span');
          span.className = 'chip';
          span.textContent = val;
          card.appendChild(span);
        });
      });

      const badge = document.createElement('div');
      badge.className = 'offline-label';
      badge.textContent = AT.offline || 'Offline';
      card.appendChild(badge);

      grid.prepend(card);
    });
  }

  function plural(n, one, many) {
    return n === 1 ? one : many.replace(':n', n);
  }

  // Say what a sync did — including when it could NOT finish, which the old
  // code kept quiet about.
  function report(r) {
    if (r.synced > 0) {
      const msg = plural(r.synced,
        AT.synced_one  || '1 offline dream synced',
        AT.synced_many || ':n offline dreams synced');
      showBanner(msg, 'online');
      showSnack('✓ ' + msg);
    }
    if (r.left > 0 && r.reason === 'auth') {
      showSnack(plural(r.left,
        AT.signin_sync_one  || '1 idea is saved on this device — sign in to sync it',
        AT.signin_sync_many || ':n ideas are saved on this device — sign in to sync them'), 6000);
    }
    // Whatever is still waiting stays visible.
    if (r.left > 0) injectOfflineCards(); else removeOfflineCards();
  }

  function sync() {
    if (!DS) return;
    DS.flush().then(report);
  }

  // Initial load
  if (navigator.onLine) {
    sync();
  } else {
    showBanner(AT.you_offline || 'You are offline', 'offline');
    injectOfflineCards();
  }

  window.addEventListener('online', () => {
    showBanner(AT.back_online || 'You are back online', 'online');
    sync();
  });
  window.addEventListener('offline', () => {
    showBanner(AT.you_offline || 'You are offline', 'offline');
    injectOfflineCards();
  });
});
