<?php
/*
 * Canvas page for a mood board.
 * Renders a toolbar and an empty stage.  JavaScript handles the rest.
 */
?>
<div class="card">
  <h1><?= htmlspecialchars($board['title'] ?? 'Untitled Mood Board') ?></h1>
  <div id="canvas-toolbar" class="canvas-toolbar">
    <button data-action="select">Select</button>
    <button data-action="pan">Pan</button>
    <button data-action="text">Text</button>
    <button data-action="frame">Frame</button>
    <button data-action="connector">Connector</button>
    <button data-action="delete">Delete</button>
    <button data-action="snap">Snap</button>
  </div>
  <div id="canvasStage" class="canvas-stage"
       style="width:100%;height:600px;border:1px solid #ccc;position:relative;overflow:hidden;">
  </div>
</div>
<script>
// Expose the slug so the JS can construct API URLs
window.boardSlug = <?= json_encode($board['slug'] ?? '') ?>;
</script>
<script src="/public/js/mood-canvas.js"></script>
