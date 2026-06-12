/* ============================================================
   SOPHIATE CRÉATION — filter.js
   Filtres produits par catégorie
   ============================================================ */

(function initFilters() {
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      // Bouton actif
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      const filter = this.dataset.filter;

      document.querySelectorAll('.product-card').forEach(card => {
        const match = filter === 'all' || card.dataset.cat === filter;
        card.style.display = match ? '' : 'none';

        // Animation d'apparition
        if (match) {
          card.style.animation = 'none';
          card.offsetHeight; // reflow
          card.style.animation = 'fadeUp .4s ease both';
        }
      });
    });
  });
})();
