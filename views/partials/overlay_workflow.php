<?php
// views/partials/overlay_workflow.php
?>
<h3>Workflow</h3>
<label>Status</label>
<select name="status"><option>Not started</option><option>In progress</option><option>Complete</option></select>
<label>Notes</label>
<textarea name="notes"></textarea>
<div class="switch" style="display:flex;align-items:center;gap:.5rem">
  <label style="min-width:160px">Show section</label>
  <input type="checkbox" name="show_workflow" checked>
  <span class="knob" aria-hidden="true"></span>
</div>
