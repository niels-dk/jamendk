<?php
print "removed files and tries to commet files";
$pageTitle = $dream['title'] ?? 'Dream Detail';
ob_start();

$isEdit = isset($dream);
?>
<h1><?= $isEdit ? 'Edit Dream' : 'Create a Dream' ?></h1>

<form id="dreamForm" class="card">
  <?php if ($isEdit): ?>
    <input type="hidden" name="dream_id" value="<?= $dream['id'] ?>">
    <input type="hidden" name="slug"     value="<?= $dream['slug'] ?>">
  <?php endif; ?>

  <label>Title<br>
    <input name="title" type="text" style="width:100%"
           value="<?= $isEdit ? htmlspecialchars($dream['title']) : '' ?>" required>
  </label><br><br>

  <label>Description<br>
    <textarea name="description" rows="6" style="width:100%"><?= $isEdit ? htmlspecialchars($dream['description']) : '' ?></textarea>
  </label><br><br>

  <h3>Anchors</h3>

<?php
function repeatInputs($name, $list) {
    if (!$list) $list = [''];
    foreach ($list as $i=>$val) {
        $plus = $i===0 ? '＋' : '✖';
        echo '<div class="repeat"><input name="'.$name.'[]" value="'.htmlspecialchars($val).'"> <button type="button" class="add">'.$plus.'</button></div>';
    }
}
?>

  <fieldset class="anchor-block"><legend>Locations</legend>
    <?php repeatInputs('location', $anchors['locations'] ?? []); ?>
  </fieldset>

  <fieldset class="anchor-block"><legend>Brands</legend>
    <?php repeatInputs('brand', $anchors['brands'] ?? []); ?>
  </fieldset>

  <fieldset class="anchor-block"><legend>People</legend>
    <?php repeatInputs('person', $anchors['people'] ?? []); ?>
  </fieldset>

  <fieldset class="anchor-block"><legend>Seasons / Time</legend>
    <?php repeatInputs('season', $anchors['seasons'] ?? []); ?>
  </fieldset>

  <br>
<div class="btn-group">
  <button class="btn primary">Save Dream</button>
  <button type="button" class="btn split" id="moreBtn">▾</button>
  <div class="split-menu" id="moreMenu">
    <button type="button" data-go="stay">Save & stay</button>
    <button type="button" data-go="view">Save & view board</button>
    <button type="button" data-go="dash">Save & dashboard</button>
  </div>
</div>
</form>

<script src="/public/js/dream-new.js?v=2"></script>


<?php
	$content = ob_get_clean();
$pageTitle = $dream['title'] ?? 'Dream Detail';
if($isEdit){
	include __DIR__ . '/layout.php';
	}

