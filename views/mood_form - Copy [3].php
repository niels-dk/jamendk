<?php
// views/mood_form.php
// Assumes $board is available with keys: id, slug, vision_slug (optional)
?>
<!-- Library Drawer Root + Controls -->
<div id="mood-lib-root"
     data-vision-slug="<?= htmlspecialchars($board['vision_slug'] ?? '') ?>"
     data-board-slug="<?= htmlspecialchars($board['slug'] ?? '') ?>"
     data-board-id="<?= (int)($board['id'] ?? 0) ?>">

  <!-- Unified top bar: tabs + actions in one row -->
  <div class="library-bar">
    <div class="library-tabs">
      <button class="tab-btn active" data-scope="board" type="button">Board Files</button>
      <button class="tab-btn" data-scope="vision" type="button">All Vision Files</button>
    </div>

    <div class="library-actions">
      <button id="uploadBtn" type="button" class="lib-btn">Upload</button>
      <input id="mediaUploadInput" type="file" name="file[]" multiple style="display:none">
      <button id="linkBtn" type="button" class="lib-btn">Add Link</button>
      <!-- Optional extra actions; keep for layout parity (can hide via CSS if not needed) -->
      <button id="addNoteBtn" type="button" class="lib-btn ghost" title="Add a sticky note to the canvas">Add Note</button>
      <button id="addConnectorBtn" type="button" class="lib-btn ghost" title="Add a connector/arrow">Add Connector</button>
    </div>
  </div>

  <!-- Filters -->
  <div class="library-filters">
    <input type="text" id="mediaSearch" placeholder="Search…">
    <select id="mediaTypeFilter">
      <option value="">All Types</option>
      <option value="image">Images</option>
      <option value="gif">GIFs</option>
      <option value="video">Videos</option>
      <option value="doc">Docs</option>
    </select>
    <select id="mediaSort">
      <option value="date">Newest</option>
      <option value="name">Name</option>
      <option value="type">Type</option>
      <option value="size">Size</option>
    </select>

    <input id="tagFilterInput" placeholder="Filter by tags (comma)…" />
    <select id="groupFilterSelect">
      <option value="">All groups</option>
    </select>

    <!-- Global upload pill -->
    <div id="uploadQueuePill" class="upl-pill" hidden>
      <span class="upl-text">Uploading…</span>
      <button class="upl-cancel" title="Cancel all" type="button">✕</button>
    </div>

    <!-- Hidden input for file picker -->
    <input id="libraryFileInput" type="file" multiple hidden accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf" />
  </div>

  <!-- Grid -->
  <div id="libraryGrid" class="media-grid masonry-cols"></div>
  <div id="libraryStatus" class="hint"></div>
</div>

<!-- Media Library Overlay (shared for Edit Tags / Change Group / Add Link) -->
<div id="ml-overlay" class="ml-overlay" hidden>
  <div class="ml-sheet" role="dialog" aria-modal="true" aria-labelledby="ml-title"></div>
</div>

<!-- Canvas drop target (unchanged) -->
<div id="canvasDropZone" class="canvas-area">
  <!-- Canvas content goes here -->
</div>

<!-- Page script includes (ensure this file is loaded after the DOM above) -->
<script src="/public/js/mood-board-library.js"></script>
