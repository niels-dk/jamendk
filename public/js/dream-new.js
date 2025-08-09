/* dream-new.js  – runs ONCE */

if (!window.DREAM_FORM_INITED) {
  window.DREAM_FORM_INITED = true;

  document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('dreamForm');
    if (!form) return;                 // quit on pages without the form

    /* + duplicate anchor inputs */
    document.querySelectorAll('.anchor-block').forEach(block => {
      block.addEventListener('click', e => {
        if (!e.target.classList.contains('add')) return;
        const row   = e.target.closest('.repeat');
        const clone = row.cloneNode(true);
        clone.querySelector('input').value = '';
        row.after(clone);
      });
    });

    /* split-button (optional) */
    const moreBtn  = document.getElementById('moreBtn');
    const moreMenu = document.getElementById('moreMenu');
    let   next = 'view';                       // default

    if (moreBtn && moreMenu) {
      moreBtn.onclick = () =>
        moreMenu.style.display = (moreMenu.style.display === 'block') ? 'none' : 'block';

      moreMenu.addEventListener('click', e => {
        if (!e.target.dataset.go) return;
        next = e.target.dataset.go;           // stay | view | dash
        document.querySelector('.btn.primary').click();
        moreMenu.style.display = 'none';
      });
    }

    /* AJAX save (create / update) */
    form.addEventListener('submit', async ev => {
      ev.preventDefault();

      const primary = form.querySelector('.btn.primary');
      primary.disabled = true;
      primary.textContent = 'Saving…';

      const endpoint = form.querySelector('input[name=dream_id]')
        ? '/api/dreams/update.php'
        : '/api/dreams/store.php';

      try {
        const res  = await fetch(endpoint, { method:'POST', body:new FormData(form) });
        const json = await res.json();

        if (json.ok) {
          if (next === 'stay') {
            primary.textContent = 'Updated!';
            setTimeout(() => primary.textContent = 'Update Dream', 1200);
            next = 'view';
          } else if (next === 'dash') {
            window.location = '/dashboard';
          } else {
            window.location = '/dreams/' + json.slug;
          }
        } else alert(json.error || 'Save failed');

      } catch (err) { alert('Network error: ' + err); }

      primary.disabled = false;
    });
  });
}
