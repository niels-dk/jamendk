<?php
// views/mood_form.php
// Assumes $board is available with keys: id, slug, vision_slug (optional)
?>
<script>
  window.moodSlug = <?= json_encode($mood['slug'] ?? $board['slug'] ?? $slug ?? '') ?>;

  // Strings for public/js/mood-board-library.js — a static asset that cannot
  // call t(). Every read there falls back to the English literal.
  window.LIB_T = <?= json_encode([
    'all_groups'    => t('mood.all_groups'),
    'close'         => t('action.close'),
    'remove'        => t('action.remove'),
    'cancel'        => t('action.cancel'),
    'save'          => t('action.save'),
    'add'           => t('action.add'),
    'delete'        => t('action.delete'),
    'edit_tags'     => t('lib.edit_tags'),
    'current_tags'  => t('lib.current_tags'),
    'none_yet'      => t('lib.none_yet'),
    'add_tag'       => t('lib.add_tag'),
    'tag_placeholder'=> t('lib.tag_placeholder'),
    'change_group'  => t('lib.change_group'),
    'choose_group'  => t('lib.choose_group'),
    'new_group_ph'  => t('lib.new_group_ph'),
    'url'           => t('lib.url'),
    'url_placeholder'=> t('lib.url_placeholder'),
    'no_preview'    => t('lib.no_preview'),
    'attach'        => t('lib.attach'),
    'detach'        => t('lib.detach'),
    'no_files'      => t('lib.no_files'),
    'uploading'     => t('docs.uploading'),
  ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<!-- Library Drawer Root + Controls -->
<div id="mood-lib-root"
     data-vision-slug="<?= htmlspecialchars($board['vision_slug'] ?? '') ?>"
     data-board-slug="<?= htmlspecialchars($board['slug'] ?? '') ?>"
     data-board-id="<?= (int)($board['id'] ?? 0) ?>">

  <!-- Unified top bar: tabs + actions in one row -->
  <div class="library-bar">
    <div class="library-tabs">
      <!--button class="tab-btn active" data-scope="board" type="button">Board Files</button-->
      <!--button class="tab-btn" data-scope="vision" type="button">All Media Files</button-->
		
		<button data-tab="board" class="btn active"><?= te('mood.board_files') ?></button>
<button data-tab="all"   class="btn"><?= te('mood.all_media') ?></button>
    </div>

    <div class="library-actions">
      <button id="uploadBtn" type="button" class="lib-btn"><?= te('docs.upload') ?></button>
      <input id="mediaUploadInput" type="file" name="file[]" multiple style="display:none">
      <button id="linkBtn" type="button" class="lib-btn"><?= te('mood.add_link') ?></button>
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
		<input id="mediaSearch" type="search" placeholder="<?= te('mood.search_files') ?>" aria-label="<?= te('mood.search_files') ?>">
		<!-- Type select (id unchanged) -->
		<select id="mediaTypeFilter" aria-label="<?= te('mood.type') ?>">
		  <option value=""><?= te('mood.all_types') ?></option>
		  <option value="image"><?= te('mood.t_images') ?></option>
		  <option value="gif"><?= te('mood.t_gifs') ?></option>
		  <option value="video"><?= te('mood.t_videos') ?></option>
		  <option value="doc"><?= te('mood.t_docs') ?></option>
		</select>
	  </label>

	  <!-- Groups searchable dropdown (keeps existing ID) -->
	  <label class="pill group-pill">
		<!--svg class="pill-icon" aria-hidden="true" viewBox="0 0 20 20">
		  <path fill="currentColor"
			d="M12 14a5 5 0 11-10 0 5 5 0 0110 0zm7-5.5V16a4 4 0 01-4 4h-3v-2h3a2 2 0 002-2V8.5h2z"></path>
		</svg-->
		<select id="groupFilterSelect" aria-label="<?= te('mood.groups') ?>">
		  <option value=""><?= te('mood.all_groups') ?></option>
		  <!-- existing group options will populate here via PHP -->
		</select>
	  </label>

	  <!-- Filter by tags (no chevron) -->
	  <label class="pill tag-pill">
		<svg class="pill-icon" aria-hidden="true" viewBox="0 0 20 20">
		  <path fill="currentColor"
			d="M19.5 18.1l-4.6-4.6a7.5 7.5 0 10-1.4 1.4l4.6 4.6a1 1 0 001.4-1.4zM8.5 14a5.5 5.5 0 110-11 5.5 5.5 0 010 11z"></path>
		</svg>
		<input id="tagFilterInput" type="text" placeholder="<?= te('mood.filter_tags') ?>" aria-label="<?= te('mood.filter_tags') ?>">
	  </label>

	  <!-- Sort (View) select -->
	  <label class="pill" for="mediaSort"><?= te('mood.view_sort') ?>
		<select id="mediaSort" aria-label="<?= te('mood.sort') ?>">
		  <option value="date"><?= te('mood.s_newest') ?></option>
		  <option value="name"><?= te('mood.s_name') ?></option>
		  <option value="type"><?= te('mood.s_type') ?></option>
		  <option value="size"><?= te('mood.s_size') ?></option>
		</select>
	  </label>
	</div>




    <!-- Global upload pill -->
    <div id="uploadQueuePill" class="upl-pill" hidden>
      <span class="upl-text"><?= te('docs.uploading') ?></span>
      <button class="upl-cancel" title="<?= te('mood.cancel_all') ?>" type="button">✕</button>
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
<script src="/public/js/mood-board-library.js?v=2"></script>
