<?php
// views/mood_edit.php
//
// This view renders a simple form for updating the basic details of a mood board.
// It expects that a `$board` array is available in the current scope with at
// least `slug`, `title` and optionally `description` keys.  The surrounding
// controller is responsible for passing this data and handling the form
// submission.

?>

<h1>Edit Mood Board</h1>

<form method="post" action="/moods/<?= htmlspecialchars($board['slug']) ?>/edit" class="form">
    <div class="form-group">
        <label for="title">Title</label>
        <input id="title"
               name="title"
               type="text"
               value="<?= htmlspecialchars($board['title'] ?? '') ?>"
               required
               class="form-control">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description"
                  name="description"
                  rows="5"
                  class="form-control">
<?= htmlspecialchars($board['description'] ?? '') ?>
        </textarea>
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
    <a href="/moods/<?= htmlspecialchars($board['slug']) ?>" class="btn btn-secondary">Cancel</a>
</form>