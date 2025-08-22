<!-- Library Drawer Root + Controls -->
<div id="mood-lib-root"
     data-vision-slug="<?= htmlspecialchars($board['vision_slug'] ?? '') ?>"
     data-board-slug="<?= htmlspecialchars($board['slug']) ?>"
     data-board-id="<?= (int)$board['id'] ?>">

  <div class="library-tabs">
    <button class="tab-btn active" data-scope="board">Board Files</button>
    <button class="tab-btn" data-scope="vision">All Vision Files</button>
  </div>

  <div class="library-actions">
    <button id="uploadBtn" type="button">Upload</button>
    <input id="mediaUploadInput" type="file" name="file[]" multiple style="display:none">
    <button id="linkBtn" type="button">Add Link</button>
    <div id="linkWrap" style="display:none;margin-top:8px;">
      <input id="linkUrl" type="url" placeholder="Paste YouTube URL…" style="width:100%">
      <button id="linkSubmit" type="button" style="margin-top:6px;">Add</button>
    </div>
  </div>

	<!-- Global upload pill -->
	<div id="uploadQueuePill" class="upl-pill" hidden>
	  <span class="upl-text">Uploading…</span>
	  <button class="upl-cancel" title="Cancel all">✕</button>
	</div>

	<!-- Hidden input for file picker (you likely already have one) -->
	<input id="libraryFileInput" type="file" multiple hidden
		   accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf" />

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
  </div>
	
  <div id="libraryGrid" class="masonry-cols"></div>
  <div id="mediaGrid" class="media-grid"></div>
  <div id="libraryGrid" class="media-grid masonry-cols"></div>
  <div id="libraryGrid" class="media-grid"></div>
  <div id="libraryStatus" class="hint"></div>
</div>

<!-- Canvas drop target somewhere in your editor -->
<div id="canvasDropZone" class="canvas-area">
  <!-- Your canvas content goes here -->
</div>
