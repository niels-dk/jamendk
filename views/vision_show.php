<?php
// expects: $vision, $anchors
ob_start();
?>
<h1><?= htmlspecialchars($vision['title']) ?></h1>

<div class="card">
  <?php if (!empty($vision['description'])): ?>
    <div class="prose prose-invert max-w-none">
      <?= $vision['description'] ?>
    </div>
    <br>
  <?php endif; ?>
	
	<?php if (!empty($presentationFlags['relations'])): ?>
	  <!-- existing Relations markup -->
	<?php endif; ?>
	
  <?php if (!empty($anchors)): ?>
    <div class="anchor-grid">
      <?php foreach ($anchors as $key => $values): ?>
        <section class="anchor-block-view">
          <h3><?= htmlspecialchars(ucfirst($key)) ?></h3>
          <?php foreach ($values as $v): ?>
            <span class="chip"><?= htmlspecialchars($v) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="margin-top:1rem">
    <small>Created <?= htmlspecialchars($vision['created_at']) ?></small>
  </p>

  <p>
    <a class="btn" href="/visions/<?= htmlspecialchars($vision['slug']) ?>/edit">Edit Vision</a>
    <a class="btn" href="/dashboard/vision" style="margin-left:.6rem">Back to dashboard</a>
  </p>
</div>

<?php
$content = ob_get_clean();
