<?php
// views/partials/overlay_contacts.php
?>
<h3>Contacts</h3>
<label>Name</label>
<input type="text" name="contact_name">
<label>Email</label>
<input type="email" name="contact_email">
<label>Phone</label>
<input type="tel" name="contact_phone">
<div class="switch" style="display:flex;align-items:center;gap:.5rem">
  <label style="min-width:160px">Show section</label>
  <input type="checkbox" name="show_contacts" checked>
  <span class="knob" aria-hidden="true"></span>
</div>
