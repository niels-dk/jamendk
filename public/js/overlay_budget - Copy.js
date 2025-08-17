/* Budget overlay: searchable currency list + amount + switches (AJAX) */
(function(){
	// keep dropdown closed on overlay open
//	if (searchInput) searchInput.value = '';
//	if (list) list.hidden = true;
	
  function initBudgetOverlay() {
    const overlay = document.getElementById('overlay-budget');
    if (!overlay || overlay.dataset.enhanced === '1') return; // guard/once
    overlay.dataset.enhanced = '1';

    const wrap        = overlay.querySelector('#budgetWrap');
    const slug        = wrap?.dataset.slug || '';
    const form        = overlay.querySelector('#budgetForm');
    const curHidden   = overlay.querySelector('#budgetCurrency');
    const curDisplay  = overlay.querySelector('#currencyDisplay');
    const searchInput = overlay.querySelector('#budgetCurrencySearch');
    const list        = overlay.querySelector('#currencyList');
    const amountInput = overlay.querySelector('#budgetAmount');
    const dashSwitch  = overlay.querySelector('#budgetDash');
    const tripSwitch  = overlay.querySelector('#budgetTrip');

    const COMMON = ['DKK','EUR','USD','GBP','SEK'];
    let catalog = [];

    /* --- UI helpers --- */
    function renderList(q) {
      if (!catalog.length) return;
      const query = (q||'').trim().toUpperCase();

      const match = (c) =>
        c.code.indexOf(query) !== -1 || c.name.toUpperCase().indexOf(query) !== -1;

      const common = catalog.filter(c => COMMON.includes(c.code)).filter(match);
      const other  = catalog.filter(c => !COMMON.includes(c.code)).filter(match);

      const rows = [...common, ...other];
      if (!rows.length) {
        list.innerHTML = '<div class="dropdown-item muted">No results</div>';
        list.hidden = false;
        return;
      }
      list.innerHTML = rows.map(c => {
        const label = `${c.code} — ${c.name}`;
        return `<div class="dropdown-item" data-code="${c.code}" data-label="${label}">${label}</div>`;
      }).join('');
      list.hidden = false;
    }

    function setCurrency(code) {
      curHidden.value = code;
      curDisplay.textContent = code || 'Select currency';
    }

    function setAmountCents(cents) {
      if (typeof cents === 'number' && cents >= 0) {
        amountInput.value = (cents / 100).toFixed(2);
      }
    }

    /* --- Events --- */
    searchInput.addEventListener('input', e => renderList(e.target.value));

    list.addEventListener('click', e => {
      const item = e.target.closest('.dropdown-item');
      if (!item) return;
      setCurrency(item.dataset.code);
      list.hidden = true;
      searchInput.value = '';
    });

    curDisplay.addEventListener('click', () => {
      list.hidden = !list.hidden;
      if (!list.hidden) searchInput.focus();
    });

    // amount sanitize -> ##.##
    amountInput.addEventListener('blur', () => {
      let raw = amountInput.value.replace(/\s/g,'').replace(',', '.');
      if (raw === '') return;
      const num = parseFloat(raw);
      amountInput.value = (!isNaN(num) && num >= 0) ? num.toFixed(2) : '';
    });

    // Save
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const code = curHidden.value.trim().toUpperCase();
      if (!code) { alert('Select a currency'); return; }
      const n = parseFloat((amountInput.value || '0').replace(',', '.'));
      if (isNaN(n) || n < 0) { alert('Invalid amount'); return; }
      const cents = Math.round(n * 100);

      const body = new URLSearchParams();
      body.set('currency', code);
      body.set('amount_cents', String(cents));
      body.set('show_on_dashboard', (dashSwitch.checked ? '1' : '0'));
      body.set('show_on_trip',      (tripSwitch.checked ? '1' : '0'));

      fetch(`/api/visions/${encodeURIComponent(slug)}/budget`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest',
                   'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      }).then(r => r.json()).then(j => {
        if (!j || !j.success) throw new Error(j?.error || 'Save failed');
        // close (or keep open if you prefer)
        overlay.classList.add('overlay-hidden');
      }).catch(err => alert(err.message || 'Save failed'));
    });

    /* --- Prefill + catalog --- */
    (async () => {
      try {
        const [curRes, budRes] = await Promise.all([
          fetch('/api/currencies', { headers: { 'X-Requested-With':'XMLHttpRequest' } }),
          fetch(`/api/visions/${encodeURIComponent(slug)}/budget/get`,
                { headers: { 'X-Requested-With':'XMLHttpRequest' } })
        ]);

        catalog = await curRes.json();
        renderList('');

        if (budRes.ok) {
          const b = await budRes.json();
          setCurrency(b?.currency || curHidden.value || 'DKK'); // sensible default
          setAmountCents(typeof b?.amount_cents === 'number' ? b.amount_cents : null);
          dashSwitch.checked = !!b?.show_on_dashboard;
          tripSwitch.checked = !!b?.show_on_trip;
        } else {
          // defaults for first-time
          setCurrency(curHidden.value || 'DKK');
        }
      } catch (e) {
        console.warn('Budget prefill/catalog failed', e);
      }
    })();
  }

  // Initialize when the overlay fragment is injected or made visible
  const boot = () => initBudgetOverlay();
  new MutationObserver(boot).observe(document.body, { childList:true, subtree:true });
  document.addEventListener('DOMContentLoaded', boot, { once:true });
})();