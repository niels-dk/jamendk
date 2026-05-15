<?php
// Expects: $vision (slug), $budget (currency, amount_cents, show_on_dashboard, show_on_trip) or null
$slug     = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$currency = htmlspecialchars((string)($budget['currency'] ?? ''), ENT_QUOTES);
$amount   = isset($budget['amount_cents']) ? number_format(($budget['amount_cents'] / 100), 2, '.', '') : '';
$showDash = !empty($budget['show_on_dashboard']);
$showTrip = !empty($budget['show_on_trip']);
?>

<div class="overlay-header">
  <h2>Budget</h2>
</div>

<form id="budgetForm" class="overlay-form" data-slug="<?= $slug ?>">
  <label for="budgetCurrencySearch">Currency</label>
  <div class="currency-picker">
    <input type="text" id="budgetCurrencySearch" placeholder="<?= $currency ?: 'Search currency…' ?>"
           autocomplete="off" value="">
    <input type="hidden" name="currency" id="budgetCurrency" value="<?= $currency ?>">
    <div id="currencyList" class="dropdown-list" hidden></div>
  </div>

  <label for="budgetAmount">Amount</label>
  <input id="budgetAmount" name="amount" type="text" inputmode="decimal" placeholder="0.00" value="<?= $amount ?>">

  <h4>Visibility</h4>

  <label class="switch switch-row">
    <span class="switch-label">Show on Dashboard</span>
    <input class="switch-input" type="checkbox" name="show_on_dashboard" <?= $showDash ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>

  <label class="switch switch-row">
    <span class="switch-label">Show on Trip layer</span>
    <input class="switch-input" type="checkbox" name="show_on_trip" <?= $showTrip ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>
</form>

<style>
  .currency-picker { position: relative; margin-bottom: 1rem; }
  #budgetCurrencySearch, #budgetAmount {
    width: 100%; box-sizing: border-box;
    background: #15161A; border: 1px solid #2b3346; color: #ddd;
    padding: .5rem .7rem; border-radius: 8px; min-height: 42px;
    margin-bottom: 1rem;
  }
  .currency-picker .dropdown-list {
    position: absolute; left: 0; right: 0; top: calc(100% - 1rem);
    background: #1a1d24; border: 1px solid #2b3346; border-radius: 8px;
    max-height: 240px; overflow-y: auto; z-index: 5;
  }
  .currency-picker .dropdown-list button {
    display: block; width: 100%; text-align: left;
    background: transparent; border: 0; color: #ddd;
    padding: .55rem .7rem; cursor: pointer;
  }
  .currency-picker .dropdown-list button:hover { background: #2a2f3a; }
  .currency-picker .dropdown-list .cur-code { font-family: monospace; opacity: .8; margin-right: .6rem; }
</style>

<script>
(() => {
  const form    = document.getElementById('budgetForm');
  if (!form) return;
  const slug    = form.dataset.slug;
  const search  = form.querySelector('#budgetCurrencySearch');
  const hidden  = form.querySelector('#budgetCurrency');
  const list    = form.querySelector('#currencyList');
  const amount  = form.querySelector('#budgetAmount');

  function toCents(val) {
    const n = parseFloat((val || '').replace(',', '.'));
    if (!isFinite(n) || n < 0) return null;
    return Math.round(n * 100);
  }

  function save() {
    const cur   = (hidden.value || '').trim().toUpperCase();
    const cents = toCents(amount.value);
    if (!cur || !/^[A-Z]{3}$/.test(cur)) return; // need a valid currency
    if (cents === null) return;                  // need a valid amount
    const p = new URLSearchParams();
    p.set('currency', cur);
    p.set('amount_cents', String(cents));
    p.set('show_on_dashboard', form.querySelector('[name="show_on_dashboard"]').checked ? '1' : '0');
    p.set('show_on_trip',      form.querySelector('[name="show_on_trip"]').checked ? '1' : '0');
    fetch(`/api/visions/${slug}/budget`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: p.toString()
    }).then(r => r.json()).then(j => {
      if (!j.success) console.error('Budget save failed:', j);
    }).catch(e => console.error('Budget save error:', e));
  }

  async function loadList(q) {
    const url = q ? `/api/currencies?q=${encodeURIComponent(q)}` : '/api/currencies';
    const res = await fetch(url);
    const data = await res.json();
    if (!Array.isArray(data) || !data.length) { list.hidden = true; list.innerHTML = ''; return; }
    list.innerHTML = data.map(c =>
      `<button type="button" data-code="${c.code}"><span class="cur-code">${c.code}</span>${c.name}</button>`
    ).join('');
    list.hidden = false;
  }

  search.addEventListener('focus', () => loadList(search.value.trim()));
  search.addEventListener('input', () => loadList(search.value.trim()));
  document.addEventListener('click', (e) => {
    if (!form.contains(e.target)) list.hidden = true;
  });

  list.addEventListener('click', e => {
    const btn = e.target.closest('button[data-code]');
    if (!btn) return;
    hidden.value = btn.dataset.code;
    search.value = '';
    search.placeholder = btn.dataset.code;
    list.hidden = true;
    save();
  });

  let amtTimer;
  amount.addEventListener('input', () => {
    clearTimeout(amtTimer);
    amtTimer = setTimeout(save, 400);
  });
  amount.addEventListener('blur', save);

  form.querySelectorAll('.switch-input').forEach(cb => {
    cb.addEventListener('change', save);
  });
})();
</script>
