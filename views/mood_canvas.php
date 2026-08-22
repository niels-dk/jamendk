<?php
/*
 * Canvas page for a mood board.
 * Renders a toolbar and an empty stage.  JavaScript handles the rest.
 */
?>
<div class="card">
  <h1><?= htmlspecialchars($board['title'] ?? t('mood.untitled')) ?></h1>
  <div id="canvas-toolbar" class="canvas-toolbar">
	  <div id="tool-pill" aria-live="polite"></div>
    <button data-action="select"><?= te('canvas.select') ?></button>
    <button data-action="pan"><?= te('canvas.pan') ?></button>
	<button data-action="zoom-out"><?= te('canvas.zoom_out') ?> −</button>
	<button data-action="zoom-in"><?= te('canvas.zoom_in') ?> +</button>
	<button data-action="reset-view"><?= te('canvas.reset') ?></button>
    <button data-action="text"><?= te('canvas.text') ?></button>
    <button data-action="frame"><?= te('canvas.frame') ?></button>
	<button data-action="resize"><?= te('canvas.resize') ?></button>
    <button data-action="connector"><?= te('canvas.connector') ?></button>
    <button data-action="delete"><?= te('action.delete') ?></button>
    <button data-action="snap"><?= te('canvas.snap') ?></button>
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

// Strings for the canvas scripts. public/js/*.js are static assets and cannot
// call t(), so the page hands them over. Each script falls back to its English
// literal when a key is absent, so nothing breaks if this block is ever missing.
window.CANVAS_T = <?= json_encode([
  'tool'        => t('canvas.tool'),
  'snap'        => t('canvas.snap'),
  'tools'       => [
    'select'     => t('canvas.select'),
    'pan'        => t('canvas.pan'),
    'text'       => t('canvas.text'),
    'frame'      => t('canvas.frame'),
    'resize'     => t('canvas.resize'),
    'connector'  => t('canvas.connector'),
    'delete'     => t('action.delete'),
    'snap'       => t('canvas.snap'),
    'zoom-in'    => t('canvas.zoom_in'),
    'zoom-out'   => t('canvas.zoom_out'),
    'reset-view' => t('canvas.reset'),
  ],
  'frame_default'  => t('canvas.frame'),
  'drag_to_move'   => t('canvas.drag_to_move'),
  'close'          => t('action.close'),
  'arrow_none'     => t('canvas.arrow_none'),
  'arrow_start'    => t('canvas.arrow_start'),
  'swap'           => t('canvas.swap'),
  'edit_label'     => t('canvas.edit_label'),
  'edit_label_dot' => t('canvas.edit_label') . '…',
  'media_library'  => t('mood.media_library'),
  'media'          => t('canvas.media'),
  'select_frame'   => t('canvas.select_frame'),
  'select_frame_media' => t('canvas.select_frame_media'),
], JSON_UNESCAPED_UNICODE) ?>;
//console.log(window.boardSlug);
</script>
<!-- core js -->
<script src="/public/js/mood-canvas.js?v=16"></script>

<script src="/public/js/mood-canvas-highlight.js"></script>
<script src="/public/js/mood-canvas-media.js?v=2"></script>
<script src="/public/js/mood-canvas-input.js"></script>

