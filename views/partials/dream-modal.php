<button id="fabNewDream" class="fab" aria-label="New Dream">＋</button>

<div id="dreamModal" class="modal-hidden">
  <div class="modal-content">
    <button id="closeModal" class="modal-close" aria-label="Close">✕</button>
    <form id="dreamForm">
      <input name="title" type="text" placeholder="Dream title" required autofocus>

      <label>Description</label>
      <textarea name="description" rows="4" placeholder="Describe your dream…"></textarea>

      <div class="anchors-mobile">
        <div class="anchor-group" data-anchor="locations">
          <label>Locations</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="locations">＋</button>
        </div>
        <div class="anchor-group" data-anchor="brands">
          <label>Brands</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="brands">＋</button>
        </div>
        <div class="anchor-group" data-anchor="people">
          <label>People</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="people">＋</button>
        </div>
        <div class="anchor-group" data-anchor="seasons">
          <label>Seasons</label>
          <div class="anchor-list"></div>
          <button type="button" class="add-anchor" data-anchor="seasons">＋</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Save Dream</button>
    </form>
  </div>
</div>
