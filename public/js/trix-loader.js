function loadTrix() {
  if (document.querySelector('link[data-trix]')) return;
  const l = document.createElement('link');
  l.rel = 'stylesheet';
  l.href = 'https://unpkg.com/trix@2.1.15/dist/trix.css';
  l.setAttribute('data-trix', '');
  document.head.appendChild(l);

  const s = document.createElement('script');
  s.defer = true;
  s.src = 'https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js';
  s.setAttribute('data-trix', '');
  document.body.appendChild(s);
}

if (navigator.onLine) loadTrix();
window.addEventListener('online', () => {
  console.info('Back online — injecting Trix editor assets');
  loadTrix();
});
