document.addEventListener('DOMContentLoaded', () => {
  // Left overlay sidebar (peek + toggle)
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.backdrop');
  const openSidebar = () => { if(sidebar){ sidebar.classList.add('open'); backdrop?.classList.add('show'); } };
  const closeSidebar = () => { if(sidebar){ sidebar.classList.remove('open'); backdrop?.classList.remove('show'); } };

  document.querySelectorAll('[data-open-sidebar]').forEach(btn => btn.addEventListener('click', openSidebar));
  document.querySelectorAll('[data-close-sidebar]').forEach(btn => btn.addEventListener('click', closeSidebar));

  // Right Relations drawer
  const drawer = document.querySelector('.drawer');
  const openDrawer = () => { if(drawer){ drawer.classList.add('open'); backdrop?.classList.add('show'); } };
  const closeDrawer = () => { if(drawer){ drawer.classList.remove('open'); backdrop?.classList.remove('show'); } };
  document.querySelectorAll('[data-open-drawer]').forEach(btn => btn.addEventListener('click', openDrawer));
  document.querySelectorAll('[data-close-drawer]').forEach(btn => btn.addEventListener('click', closeDrawer));

  // Backdrop + ESC handling
  backdrop?.addEventListener('click', () => { closeSidebar(); closeDrawer(); });
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape'){ closeSidebar(); closeDrawer(); } });

  // “+ New Dream” menu
  const plus = document.querySelector('[data-menu-btn]');
  const menu = document.querySelector('[data-menu]');
  if(plus && menu){
    const toggle = () => menu.classList.toggle('open');
    plus.addEventListener('click', toggle);
    document.addEventListener('click', (e)=>{ if(!menu.contains(e.target) && e.target !== plus) menu.classList.remove('open'); });
  }
});
