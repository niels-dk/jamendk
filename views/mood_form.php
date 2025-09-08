<?php
// views/mood_form.php
// Assumes $board is available with keys: id, slug, vision_slug (optional)
?>
<script>
  window.moodSlug = <?= json_encode($mood['slug'] ?? $board['slug'] ?? $slug ?? '') ?>;
</script>
<!-- Library Drawer Root + Controls -->
<div id="mood-lib-root"
     data-vision-slug="<?= htmlspecialchars($board['vision_slug'] ?? '') ?>"
     data-board-slug="<?= htmlspecialchars($board['slug'] ?? '') ?>"
     data-board-id="<?= (int)($board['id'] ?? 0) ?>">

  <!-- Unified top bar: tabs + actions in one row -->
  <div class="library-bar">
    <div class="library-tabs">
      <button class="tab-btn active" data-scope="board" type="button">Board Files</button>
      <button class="tab-btn" data-scope="vision" type="button">All Media Files</button>
		
		<button data-tab="board" class="btn active">Board Files 2</button>
<button data-tab="all"   class="btn">All Media Files 2</button>
    </div>

    <div class="library-actions">
      <button id="uploadBtn" type="button" class="lib-btn">Upload</button>
      <input id="mediaUploadInput" type="file" name="file[]" multiple style="display:none">
      <button id="linkBtn" type="button" class="lib-btn">Add Link</button>
      <!-- Optional extra actions; keep for layout parity (can hide via CSS if not needed) -->
      <!--button id="addNoteBtn" type="button" class="lib-btn ghost" title="Add a sticky note to the canvas">Add Note</button-->
      <!--button id="addConnectorBtn" type="button" class="lib-btn ghost" title="Add a connector/arrow">Add Connector</button-->
    </div>
  </div>

 	<!-- Filter toolbar -->
	<div class="filter-toolbar">
	  <!-- Search + Type combined pill -->
	  <label class="pill search-pill">
		<!-- Magnifier icon -->
		<svg class="pill-icon" aria-hidden="true" viewBox="0 0 20 20">
		  <path fill="currentColor"
			d="M19.5 18.1l-4.6-4.6a7.5 7.5 0 10-1.4 1.4l4.6 4.6a1 1 0 001.4-1.4zM8.5 14a5.5 5.5 0 110-11 5.5 5.5 0 010 11z"></path>
		</svg>
		<!-- Search input (id unchanged) -->
		<input id="mediaSearch" type="search" placeholder="Search files…" aria-label="Search files">
		<!-- Type select (id unchanged) -->
		<select id="mediaTypeFilter" aria-label="Type">
		  <option value="">All Types</option>
		  <option value="image">Images</option>
		  <option value="gif">GIFs</option>
		  <option value="video">Videos</option>
		  <option value="doc">Docs</option>
		</select>
	  </label>

	  <!-- Groups searchable dropdown (keeps existing ID) -->
	  <label class="pill group-pill">
		<!--svg class="pill-icon" aria-hidden="true" viewBox="0 0 20 20">
		  <path fill="currentColor"
			d="M12 14a5 5 0 11-10 0 5 5 0 0110 0zm7-5.5V16a4 4 0 01-4 4h-3v-2h3a2 2 0 002-2V8.5h2z"></path>
		</svg-->
		<select id="groupFilterSelect" aria-label="Groups">
		  <option value="">All groups</option>
		  <!-- existing group options will populate here via PHP -->
		</select>
	  </label>

	  <!-- Filter by tags (no chevron) -->
	  <label class="pill tag-pill">
		<svg class="pill-icon" aria-hidden="true" viewBox="0 0 20 20">
		  <path fill="currentColor"
			d="M19.5 18.1l-4.6-4.6a7.5 7.5 0 10-1.4 1.4l4.6 4.6a1 1 0 001.4-1.4zM8.5 14a5.5 5.5 0 110-11 5.5 5.5 0 010 11z"></path>
		</svg>
		<input id="tagFilterInput" type="text" placeholder="Filter by tags" aria-label="Filter by tags">
	  </label>

	  <!-- Sort (View) select -->
	  <label class="pill" for="mediaSort">View sort:
		<select id="mediaSort" aria-label="Sort">
		  <option value="date">Newest</option>
		  <option value="name">Name</option>
		  <option value="type">Type</option>
		  <option value="size">Size</option>
		</select>
	  </label>
	</div>




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
