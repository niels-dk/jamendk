<?php
// Expects $vision (slug) and optionally $budget (currency, amount_cents, show_on_dashboard, show_on_trip).
$slug     = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$currency = isset($budget['currency']) ? htmlspecialchars($budget['currency'], ENT_QUOTES) : '';
$amount   = isset($budget['amount_cents']) ? number_format(($budget['amount_cents'] / 100), 2, '.', '') : '';
$showDash = !empty($budget['show_on_dashboard']);
$showTrip = !empty($budget['show_on_trip']);
?>
<div id="overlay-budget" class="overlay">
  <div class="overlay-panel">
    <div class="overlay-header">
      <h2>Budget</h2>
    </div>
    <div class="overlay-body" data-slug="<?= $slug ?>" id="budgetWrap">
      <form id="budgetForm" class="form-grid">
        <div class="form-row">
          <label for="budgetCurrency">Currency</label>
          <div class="select-container">
            <input type="search" id="budgetCurrencySearch" placeholder="Search currency…" autocomplete="off">
            <div id="currencyList" class="dropdown-list" hidden></div>
            <input type="hidden" name="currency" id="budgetCurrency" value="<?= $currency ?>">
            <span id="currencyDisplay" class="selected-currency">
              <?= $currency ?: 'Select currency' ?>
            </span>
          </div>
        </div>
        <div class="form-row">
          <label for="budgetAmount">Amount</label>
          <input id="budgetAmount" name="amount" type="text" inputmode="decimal" placeholder="0.00" value="<?= $amount ?>">
        </div>
        <div class="form-section">
          <div class="section-title">Visibility</div>
          <label class="switch-row">
            <input type="checkbox" class="ui-switch" id="budgetDash" name="show_on_dashboard" value="1" <?= $showDash ? 'checked' : '' ?> />
            <span class="switch-text">Show on Dashboard</span>
          </label>
          <label class="switch-row">
            <input type="checkbox" class="ui-switch" id="budgetTrip" name="show_on_trip" value="1" <?= $showTrip ? 'checked' : '' ?> />
            <span class="switch-text">Show on Trip layer</span>
          </label>
        </div>
        <div class="overlay-actions">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
