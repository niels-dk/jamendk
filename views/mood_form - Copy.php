<?php
/**
 * Mood board editing form
 *
 * Displays a simple form for entering a mood board title and associating
 * the board with a vision.  Future enhancements should include the
 * full canvas editor described in the product spec.  For now this form
 * provides a minimal UI so the new CRUD routes do not result in a 404.
 */
?>
<div class="card">
  <h2>Edit Mood Board</h2>
  <form method="post" action="/moods/update">
    <input type="hidden" name="mood_id" value="<?= htmlspecialchars($board['id']) ?>">
    <div class="field">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= htmlspecialchars($board['title'] ?? '') ?>" placeholder="e.g. Brand A mood">
    </div>
    <div class="field">
      <label for="vision_id">Vision (optional)</label>
      <input type="number" id="vision_id" name="vision_id" value="<?= htmlspecialchars($board['vision_id'] ?? '') ?>" placeholder="Vision ID">
    </div>
    <div class="btnbar">
      <button class="btn primary" type="submit">Save</button>
      <a class="btn ghost" href="/moods/<?= htmlspecialchars($board['slug']) ?>">Cancel</a>
    </div>
  </form>
</div>