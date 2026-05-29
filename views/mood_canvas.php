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
	  <!-- SVG layer FIRST = behind content. Connector lines live here.
	       It must accept clicks for hit-line selection to work, so no
	       pointer-events:none on the container. -->
	  <svg id="canvasOverlay"
		   style="position:absolute; inset:0;">
		<g id="overlayContent" vector-effect="non-scaling-stroke"></g>
	  </svg>

	  <!-- Items live on top. pointer-events:none lets empty content space
	       fall through to the SVG below (so connector hit-lines can be
	       clicked between items); individual items keep auto by default
	       because pointer-events isn't inherited via CSS. -->
	  <div id="canvasContent"
		   style="position:absolute; inset:0; transform-origin:0 0; pointer-events:none;"></div>
	</div>
<style>
  /* Re-enable interaction on actual canvas items so they remain draggable
     even though their parent (#canvasContent) is pointer-events:none. */
  #canvasContent .canvas-item { pointer-events: auto; }
  /* The marquee selector is drawn into #canvasStage, not into content. */
  #canvasStage .marquee { pointer-events: none; }
</style>
<script>
// Expose the slug so the JS can construct API URLs
window.boardSlug = <?= json_encode($board['slug'] ?? '') ?>;
//console.log(window.boardSlug);
</script>
<!-- core js -->
<script src="/public/js/mood-canvas.js?v=15"></script>

<script src="/public/js/mood-canvas-highlight.js"></script>
<script src="/public/js/mood-canvas-media.js"></script>
<script src="/public/js/mood-canvas-input.js"></script>

