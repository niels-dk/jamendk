<?php
// views/partials/overlay_budget.php
?>
<h3>Budget</h3>
<label>Currency</label>
<select name="currency"><option>DKK</option><option>EUR</option><option>USD</option></select>
<label>Total</label>
<input type="number" name="total" placeholder="0.00" step="0.01">
<label>Notes</label>
<textarea name="notes"></textarea>
<div class="switch" style="display:flex;align-items:center;gap:.5rem">
  <label style="min-width:160px">Show section</label>
  <input type="checkbox" name="show_budget" checked>
  <span class="knob" aria-hidden="true"></span>
</div>
