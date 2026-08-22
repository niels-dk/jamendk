<button id="fabNewDream" class="fab" aria-label="<?= te('home.new_dream') ?>">＋</button>

<div id="dreamModal" class="modal-hidden">
  <div class="modal-content">
    <button id="closeModal" class="modal-close" aria-label="<?= te('action.close') ?>">✕</button>
    <form id="dreamForm">
      <input name="title" type="text" placeholder="<?= te('dream.title_placeholder') ?>" required autofocus>

      <label><?= te('mood.description') ?></label>
      <textarea name="description" rows="4" placeholder="<?= te('dream.desc_placeholder') ?>"></textarea>

      <div class="anchors-mobile">
        <div class="anchor-group" data-anchor="locations">
          <label><?= te('anchor.locations') ?></label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="locations">＋</button>
        </div>
        <div class="anchor-group" data-anchor="brands">
          <label><?= te('anchor.brands') ?></label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="brands">＋</button>
        </div>
        <div class="anchor-group" data-anchor="people">
          <label><?= te('anchor.people') ?></label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="people">＋</button>
        </div>
        <div class="anchor-group" data-anchor="seasons">
          <label><?= te('anchor.seasons') ?></label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="seasons">＋</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><?= te('dream.save') ?></button>
    </form>
  </div>
</div>
