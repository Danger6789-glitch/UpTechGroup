/* ============================================================
   SOPHIATE CRÉATION — gallery.js
   Lightbox galerie photos
   ============================================================ */

(function initGallery() {
  const items    = Array.from(document.querySelectorAll('.gallery-item[data-src]'));
  const lightbox = document.getElementById('lightbox');
  const lbImg    = document.getElementById('lb-img');
  const lbCap    = document.getElementById('lb-caption');
  if (!lightbox || !items.length) return;

  let current = 0;

  function open(idx) {
    current = idx;
    const item = items[idx];
    lbImg.src = item.dataset.src || '';
    lbImg.alt = item.dataset.alt || '';
    if (lbCap) lbCap.textContent = item.dataset.alt || '';
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
    lbImg.src = '';
  }

  function navigate(dir) {
    current = (current + dir + items.length) % items.length;
    open(current);
  }

  // Clic sur les items
  items.forEach((item, i) => item.addEventListener('click', () => open(i)));

  // Boutons lightbox
  document.getElementById('lb-close')?.addEventListener('click', close);
  document.getElementById('lb-prev')?.addEventListener('click', () => navigate(-1));
  document.getElementById('lb-next')?.addEventListener('click', () => navigate(1));

  // Clic fond
  lightbox.addEventListener('click', e => { if (e.target === lightbox) close(); });

  // Clavier
  document.addEventListener('keydown', e => {
    if (!lightbox.classList.contains('open')) return;
    if (e.key === 'Escape')      close();
    if (e.key === 'ArrowLeft')   navigate(-1);
    if (e.key === 'ArrowRight')  navigate(1);
  });

  // Swipe mobile
  let touchStartX = 0;
  lightbox.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; });
  lightbox.addEventListener('touchend',   e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) navigate(diff > 0 ? 1 : -1);
  });

  // Exposer pour usage externe
  window.GalleryLightbox = { open, close, navigate };
})();
