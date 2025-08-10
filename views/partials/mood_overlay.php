<!-- views/partials/mood_overlay.php -->
<div id="mood-settings-overlay" class="overlay-hidden">
  <div class="overlay-content">
    <button class="close-overlay">✕</button>
    <h3>Edit Selected Items</h3>
    <form id="moodSettingsForm">
      <label>Title/Caption</label>
      <input type="text" name="title" />
      <label>Tags</label>
      <input type="text" name="tags" placeholder="Comma-separated" />
      <!-- Add more fields: Colour, Pin/Hero toggle, Include in export, Source URL, Linked boards, Notes -->
      <div class="batch">
        <label>
          <input type="checkbox" name="apply_all" />
          Apply to all selected items
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
<script>
document.querySelector('.js-open-settings')?.addEventListener('click', () => {
  document.getElementById('mood-settings-overlay').classList.remove('overlay-hidden');
});
document.querySelector('.close-overlay')?.addEventListener('click', () => {
  document.getElementById('mood-settings-overlay').classList.add('overlay-hidden');
});
</script>
