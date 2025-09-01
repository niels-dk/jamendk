<?php
/*
 * Canvas page for a mood board.
 * Renders a toolbar and an empty stage.  JavaScript handles the rest.
 */
?>
<div class="card">
  <h1><?= htmlspecialchars($board['title'] ?? 'Untitled Mood Board') ?></h1>
  <div id="canvas-toolbar" class="canvas-toolbar">
	  <div id="tool-pill" aria-live="polite"></div>
    <button data-action="select">Select</button>
    <button data-action="pan">Pan</button>
	<button data-action="zoom-out">Zoom −</button>
	<button data-action="zoom-in">Zoom +</button>
	<button data-action="reset-view">Reset</button>
    <button data-action="text">Text</button>
    <button data-action="frame">Frame</button>
	<button data-action="resize">Resize</button>
    <button data-action="connector">Connector</button>
    <button data-action="delete">Delete</button>
    <button data-action="snap">Snap</button>
  </div>
  <div id="canvasStage" class="canvas-stage"
		 style="width:100%; height:600px; border:1px solid #ccc; position:relative; overflow:hidden;">
	  <!-- All draggable items live here; this is what pan/zoom transforms -->
	  <div id="canvasContent"
		   style="position:absolute; inset:0; transform-origin:0 0;"></div>

	  <!-- The SVG itself stays fixed-size. We transform only the inner <g>. -->
	  <svg id="canvasOverlay"
		   style="position:absolute; inset:0; pointer-events:none;">
		<g id="overlayContent" vector-effect="non-scaling-stroke"></g>
	  </svg>
	</div>
<script>
// Expose the slug so the JS can construct API URLs
window.boardSlug = <?= json_encode($board['slug'] ?? '') ?>;
//console.log(window.boardSlug);
</script>
<!-- core js -->
<script src="/public/js/mood-canvas.js?v=2"></script>

<script src="/public/js/mood-canvas-highlight.js"></script>

