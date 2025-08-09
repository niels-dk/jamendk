/* prevent double-execution */
if (!window.DREAM_FORM_INITED) {
  window.DREAM_FORM_INITED = true;   // mark as loaded

document.addEventListener('DOMContentLoaded',()=>{

  const form = document.getElementById('dreamForm');
    if (!form) return;               // nothing to do on other pages

  /* + duplicate inputs (unchanged) */
  document.querySelectorAll('.anchor-block').forEach(b=>{
    b.addEventListener('click',e=>{
      if(!e.target.classList.contains('add'))return;
      const r=e.target.closest('.repeat');const c=r.cloneNode(true);
      c.querySelector('input').value='';r.after(c);
    });
  });

    /* split-menu toggle  */
  const moreBtn  = document.getElementById('moreBtn');
  const moreMenu = document.getElementById('moreMenu');
  let   nextAction = 'view';           // default after save

  if (moreBtn && moreMenu) {
    moreBtn.onclick = () => {
      moreMenu.style.display = (moreMenu.style.display === 'block') ? 'none' : 'block';
    };
    moreMenu.addEventListener('click', e => {
      if (!e.target.dataset.go) return;
      nextAction = e.target.dataset.go;   // stay | view | dash
      document.querySelector('.btn.primary').click();
      moreMenu.style.display = 'none';
    });
  }

  /* AJAX form submit */
  const form = document.getElementById('dreamForm');
  form.addEventListener('submit', async e=>{
    e.preventDefault();

    const primary = form.querySelector('.btn.primary');
    primary.disabled=true; primary.textContent='Saving…';

    const endpoint = form.querySelector('input[name=dream_id]')
      ? '/api/dreams/update.php' : '/api/dreams/store.php';

    try{
      const res  = await fetch(endpoint,{method:'POST',body:new FormData(form)});
      const json = await res.json();

      if(json.ok){
        switch(nextAction){
          case 'stay':
            primary.textContent='Updated!';
            setTimeout(()=>{ primary.textContent='Update Dream'; },1000);
            nextAction='view';
            break;
          case 'dash':
            window.location='/dashboard'; break;
          default:
            window.location='/dreams/'+json.slug;
        }
      }else alert(json.error||'Save failed');

    }catch(err){ alert('Network error: '+err); }

    primary.disabled=false;
  });
});

}