<!-- views/partials/overlay_documents.php -->
<h3>Documents</h3>

<div>
<div class="docs-card">
  <div class="docs-head">Documents</div>

  <div class="docs-topbar">
    <form id="documentUploadForm" enctype="multipart/form-data" action="javascript:void(0);">
      <input type="hidden" name="slug" value="<?= htmlspecialchars($vision['slug']) ?>">
      <input type="file" name="file[]" multiple>
      <button type="submit">Upload</button>
    </form>
    <div id="uploadStatus" class="hint"></div>
  </div>

  <table id="docsTable">
    <thead>
      <tr>
        <th style="width:55%;">File</th>
        <th style="width:10%;">Group</th>
        <th style="width:15%;">Status</th>
        <th style="width:20%;">Uploaded</th>
        <th style="width:10%;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($docs as $doc): ?>
        <tr>
          <td>
            <div class="doc-name"><?= htmlspecialchars($doc['file_name']) ?></div>
          </td>
          <td>
			  <span class="status-pill js-group"
					data-uuid="<?= $doc['uuid'] ?>"
					data-current="<?= isset($doc['group_id']) ? (int)$doc['group_id'] : '' ?>">
				<?= $doc['group_name'] ? htmlspecialchars($doc['group_name']) : '—' ?>
			  </span>
			  <select class="group-select" data-uuid="<?= $doc['uuid'] ?>" style="display:none"></select>
			  <button type="button" class="group-create-btn" style="display:none">+ New</button>
		  </td>
          <!--td><span class="status-pill"><?= htmlspecialchars(ucfirst($doc['status'])) ?></span></td-->
		  <td>
			  <span class="status-pill js-status" data-uuid="<?= $doc['uuid'] ?>">
				<?= htmlspecialchars(ucfirst($doc['status'])) ?>
			  </span>
			  <select class="status-select" data-uuid="<?= $doc['uuid'] ?>" style="display:none">
				<option value="draft"         <?= $doc['status']==='draft'?'selected':'' ?>>Draft</option>
				<option value="waiting_brand" <?= $doc['status']==='waiting_brand'?'selected':'' ?>>Waiting Brand</option>
				<option value="final"         <?= $doc['status']==='final'?'selected':'' ?>>Final</option>
				<option value="signed"        <?= $doc['status']==='signed'?'selected':'' ?>>Signed</option>
			  </select>
		  </td>

          <td class="doc-meta"><?= date('Y-m-d H:i', strtotime($doc['created_at'])) ?></td>
          <td><a class="action-link" href="/documents/<?= $doc['uuid'] ?>/download">Download</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
