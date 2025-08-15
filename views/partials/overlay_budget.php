<?php
/**
 * Vision Budget overlay
 *
 * Capture a single budget for this vision.  Users may choose a currency
 * (with quick access to common ones) and enter a numeric amount.  They
 * can also decide where the budget appears.  This overlay stores its
 * data in the `vision_budgets` table.
 *
 * Expected variables:
 *   $vision            array  Vision record (for id/slug)
 *   $budget            array|null  Existing budget record (currency, amount, flags)
 */

// Prepopulate from existing budget or defaults
$currency = $budget['currency'] ?? '';
$amount   = $budget['amount']   ?? '';
$showDash = (int)($budget['show_on_dashboard'] ?? 0);
$showTrip = (int)($budget['show_on_trip']      ?? 0);

// Define a small set of common currencies.  These appear first, but
// the user can still type any ISO code into the search box.
$commonCurrencies = ['DKK','EUR','USD','GBP','SEK'];
?>

<div class="overlay-header">
  <h2>Budget</h2>
  <button class="close-overlay" aria-label="Close" title="Close">✕</button>
</div>

<form id="budgetForm" class="overlay-form" method="post" action="/api/visions/<?= htmlspecialchars($vision['slug'] ?? '') ?>/budget">
  <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>" />

  <div class="form-group">
    <label for="currency-input">Currency</label>
    <div class="currency-quick">
      <?php foreach ($commonCurrencies as $cur): ?>
        <button type="button" class="currency-btn" data-value="<?= $cur ?>" <?= $cur === $currency ? 'data-selected="1"' : '' ?>><?= $cur ?></button>
      <?php endforeach; ?>
    </div>
    <input id="currency-input" type="text" name="currency" value="<?= htmlspecialchars($currency) ?>" placeholder="e.g. USD" autocomplete="off" />
    <div class="currency-suggestions" style="display:none"></div>
  </div>

  <div class="form-group">
    <label for="amount">Amount</label>
    <input id="amount" type="number" name="amount" value="<?= htmlspecialchars($amount) ?>" step="0.01" min="0" />
  </div>

  <fieldset>
    <legend>Visibility</legend>
    <label class="checkbox"><input type="checkbox" name="show_on_dashboard" value="1" <?= $showDash ? 'checked' : '' ?> /> Show on Dashboard</label>
    <label class="checkbox"><input type="checkbox" name="show_on_trip"      value="1" <?= $showTrip ? 'checked' : '' ?> /> Show on Trip layer</label>
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>

<script>
(() => {
  const currencyInput   = document.getElementById('currency-input');
  const currencyButtons = document.querySelectorAll('.currency-btn');
  const suggestions     = document.querySelector('.currency-suggestions');
  // Activate quick buttons
  currencyButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      currencyButtons.forEach(b => b.removeAttribute('data-selected'));
      btn.setAttribute('data-selected','1');
      currencyInput.value = btn.dataset.value;
      suggestions.style.display = 'none';
    });
  });
  // Fetch currency suggestions when typing (min 2 chars)
  currencyInput.addEventListener('input', async () => {
    const q = currencyInput.value.trim().toUpperCase();
    if (q.length < 2) { suggestions.style.display = 'none'; return; }
    try {
      const res  = await fetch(`/api/currencies?q=${encodeURIComponent(q)}`);
      const list = await res.json();
      if (Array.isArray(list) && list.length) {
        suggestions.innerHTML = list.map(item => `<button type="button" data-value="${item.code}">${item.code} — ${item.name}</button>`).join('');
        suggestions.style.display = 'block';
      } else suggestions.style.display = 'none';
    } catch(err) { suggestions.style.display = 'none'; }
  });
  // Choose suggestion
  suggestions.addEventListener('click', e => {
    const btn = e.target.closest('button[data-value]');
    if (!btn) return;
    currencyButtons.forEach(b => b.removeAttribute('data-selected'));
    currencyInput.value = btn.dataset.value;
    suggestions.style.display = 'none';
  });
})();
</script>