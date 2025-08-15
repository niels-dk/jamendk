<?php
/**
 * Vision Contacts overlay
 *
 * Manage a list of contact persons associated with this vision.  Each
 * contact can carry flags (current, main, show on dashboard/trip).  The
 * overlay shows existing contacts with edit/delete actions and a form to
 * add or update a contact.  Custom fields can be added later.
 *
 * Expected variables:
 *   $vision   array  Vision record (for id/slug)
 *   $contacts array  List of contact rows with pivot flags and ids
 */

?>

<div class="overlay-header">
  <h2>Contacts</h2>
  <button class="close-overlay" aria-label="Close" title="Close">✕</button>
</div>

<div class="contacts-section">
  <div class="contact-list">
    <?php if (!empty($contacts)): ?>
      <?php foreach ($contacts as $c): ?>
        <div class="contact-item" data-vc-id="<?= (int)$c['vc_id'] ?>">
          <strong><?= htmlspecialchars($c['name']) ?></strong>
          <?php if (!empty($c['company'])): ?> — <?= htmlspecialchars($c['company']) ?><?php endif; ?><br/>
          <?php if (!empty($c['email'])): ?><?= htmlspecialchars($c['email']) ?><?php endif; ?>
          <?php if (!empty($c['mobile'])): ?> / <?= htmlspecialchars($c['mobile']) ?><?php endif; ?><br/>
          <?php
            $flags = [];
            if ($c['is_current']) $flags[] = 'Current';
            if ($c['is_main'])    $flags[] = 'Main';
            if ($c['show_on_dashboard']) $flags[] = 'Dashboard';
            if ($c['show_on_trip'])      $flags[] = 'Trip';
          ?>
          <?php if ($flags): ?>
            <small><?= implode(', ', $flags) ?></small><br/>
          <?php endif; ?>
          <button type="button" class="edit-contact" data-id="<?= (int)$c['vc_id'] ?>">Edit</button>
          <button type="button" class="delete-contact" data-id="<?= (int)$c['vc_id'] ?>">Delete</button>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No contacts yet.</p>
    <?php endif; ?>
  </div>

  <button id="add-contact" type="button" class="btn btn-primary" style="margin-top:1rem">Add contact</button>

  <div class="contact-form" style="display:none;margin-top:1rem">
    <h3 id="contactFormTitle">New Contact</h3>
    <form id="contactForm" action="" method="post">
      <input type="hidden" name="vision_id" value="<?= (int)$vision['id'] ?>" />
      <input type="hidden" name="vc_id" value="" />
      <div class="form-group"><label>Name</label><input type="text" name="name" required /></div>
      <div class="form-group"><label>Company</label><input type="text" name="company" /></div>
      <div class="form-group"><label>Address</label><input type="text" name="address" /></div>
      <div class="form-group"><label>Mobile</label><input type="text" name="mobile" /></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" /></div>
      <div class="form-group"><label>Country</label><input type="text" name="country" /></div>
      <fieldset>
        <legend>Flags</legend>
        <label class="checkbox"><input type="checkbox" name="is_current" value="1" /> Current</label>
        <label class="checkbox"><input type="checkbox" name="is_main"    value="1" /> Main</label>
        <label class="checkbox"><input type="checkbox" name="show_on_dashboard" value="1" /> Show on Dashboard</label>
        <label class="checkbox"><input type="checkbox" name="show_on_trip"      value="1" /> Show on Trip layer</label>
      </fieldset>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save contact</button>
        <button class="btn btn-ghost" type="button" id="cancelContact">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
(() => {
  const addBtn    = document.getElementById('add-contact');
  const formWrap  = document.querySelector('.contact-form');
  const form      = document.getElementById('contactForm');
  const title     = document.getElementById('contactFormTitle');
  const cancelBtn = document.getElementById('cancelContact');
  const list      = document.querySelector('.contact-list');

  // Show form for new contact
  addBtn?.addEventListener('click', () => {
    title.textContent = 'New Contact';
    form.reset();
    form.querySelector('input[name="vc_id"]').value = '';
    formWrap.style.display = 'block';
  });

  // Cancel contact editing
  cancelBtn?.addEventListener('click', () => {
    formWrap.style.display = 'none';
  });

  // Edit existing contact
  list?.addEventListener('click', e => {
    const edit = e.target.closest('.edit-contact');
    if (!edit) return;
    const id = edit.dataset.id;
    fetch(`/api/visions/<?= htmlspecialchars($vision['slug'] ?? '') ?>/contacts/${id}`)
      .then(res => res.json())
      .then(data => {
        if (!data || !data.id) return;
        title.textContent = 'Edit Contact';
        formWrap.style.display = 'block';
        form.querySelector('input[name="vc_id"]').value = data.vc_id;
        form.querySelector('input[name="name"]').value     = data.name || '';
        form.querySelector('input[name="company"]').value  = data.company || '';
        form.querySelector('input[name="address"]').value  = data.address || '';
        form.querySelector('input[name="mobile"]').value   = data.mobile || '';
        form.querySelector('input[name="email"]').value    = data.email || '';
        form.querySelector('input[name="country"]').value  = data.country || '';
        form.querySelector('input[name="is_current"]').checked        = !!data.is_current;
        form.querySelector('input[name="is_main"]').checked           = !!data.is_main;
        form.querySelector('input[name="show_on_dashboard"]').checked = !!data.show_on_dashboard;
        form.querySelector('input[name="show_on_trip"]').checked      = !!data.show_on_trip;
      });
  });

  // Delete contact
  list?.addEventListener('click', e => {
    const del = e.target.closest('.delete-contact');
    if (!del) return;
    const id = del.dataset.id;
    if (!confirm('Delete this contact?')) return;
    fetch(`/api/visions/<?= htmlspecialchars($vision['slug'] ?? '') ?>/contacts/${id}/delete`, { method:'DELETE' })
      .then(() => location.reload());
  });

  // Submit form (create or update)
  form?.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(form);
    const vcId = fd.get('vc_id');
    const url  = vcId ? `/api/visions/<?= htmlspecialchars($vision['slug'] ?? '') ?>/contacts/${vcId}` : `/api/visions/<?= htmlspecialchars($vision['slug'] ?? '') ?>/contacts/create`;
    const method = vcId ? 'PUT' : 'POST';
    fetch(url, { method, body: fd })
      .then(res => res.json())
      .then(data => {
        if (data && data.success) location.reload();
        else alert(data.error || 'Save failed');
      });
  });
})();
</script>