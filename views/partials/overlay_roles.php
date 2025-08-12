<?php
// views/partials/overlay_roles.php
?>
<h3>Roles &amp; Permissions</h3>
<label>Add collaborator by email</label>
<input type="email" placeholder="you@example.com">
<label>Role</label>
<select><option>Editor</option><option>Viewer</option><option>Co‑owner</option></select>
<div class="switch" style="display:flex;align-items:center;gap:.5rem">
  <label style="min-width:160px">Show section</label>
  <input type="checkbox" name="show_roles" checked>
  <span class="knob" aria-hidden="true"></span>
</div>
