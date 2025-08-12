<div id="overlay-budget" class="overlay-hidden">
  <div class="overlay-content">
    <button class="close-overlay" aria-label="Close">✕</button>
    <h3>Budget</h3>
    <div class="two-cols">
      <div>
        <label>Currency</label>
        <select>
          <option>EUR</option><option>USD</option><option>DKK</option>
        </select>
      </div>
      <div>
        <label>Total</label>
        <input type="number" placeholder="0.00" step="0.01" min="0">
      </div>
    </div>
    <label style="margin-top:12px">Notes</label>
    <textarea rows="4" placeholder="Constraints, sources, etc."></textarea>
    <div class="switch" style="margin-top:12px">
      <label for="budget_public" style="min-width:160px">Show section</label>
      <input id="budget_public" type="checkbox" checked>
      <span class="knob" aria-hidden="true"></span>
    </div>
  </div>
</div>
