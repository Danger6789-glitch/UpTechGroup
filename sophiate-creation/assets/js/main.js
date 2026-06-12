/* ============================================================
   SOPHIATE CRÉATION — main.js
   Fonctions globales : cursor, navbar, toast, panier, menu mobile
   ============================================================ */

/* ── CURSOR CUSTOM ── */
(function initCursor() {
  const cur  = document.getElementById('cursor');
  const ring = document.getElementById('cursor-ring');
  if (!cur || !ring) return;

  document.addEventListener('mousemove', e => {
    cur.style.left  = e.clientX + 'px';
    cur.style.top   = e.clientY + 'px';
    setTimeout(() => {
      ring.style.left = e.clientX + 'px';
      ring.style.top  = e.clientY + 'px';
    }, 80);
  });

  document.querySelectorAll('a, button, [data-hover]').forEach(el => {
    el.addEventListener('mouseenter', () => cur.classList.add('hovered'));
    el.addEventListener('mouseleave', () => cur.classList.remove('hovered'));
  });
})();

/* ── NAVBAR SCROLL ── */
(function initNavbar() {
  const nav = document.getElementById('navbar');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
  });

  // Marquer le lien actif selon la page courante
  const page = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.getAttribute('href') === page) a.classList.add('active');
  });
})();

/* ── MOBILE MENU ── */
function toggleMenu() {
  const menu = document.getElementById('mobileMenu');
  if (menu) menu.classList.toggle('open');
}

/* ── TOAST NOTIFICATION ── */
function showToast(title, sub) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.querySelector('.t-title').textContent = title || '';
  toast.querySelector('.t-sub').textContent   = sub   || '';
  toast.classList.add('show');
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('show'), 3500);
}

/* ── PANIER ── */
const Cart = (function () {
  let items = JSON.parse(localStorage.getItem('sophiate_cart') || '[]');

  function save()   { localStorage.setItem('sophiate_cart', JSON.stringify(items)); }
  function count()  { return items.reduce((s, i) => s + i.qty, 0); }
  function total()  { return items.reduce((s, i) => s + (i.price * i.qty), 0); }

  function updateBadge() {
    document.querySelectorAll('.cart-badge').forEach(el => el.textContent = count());
  }

  function add(name, price) {
    const existing = items.find(i => i.name === name);
    if (existing) { existing.qty++; } else { items.push({ name, price: price || 0, qty: 1 }); }
    save(); updateBadge(); render();
    showToast('Ajouté au panier !', 'Livraison Gozem disponible');
  }

  function remove(idx) {
    items.splice(idx, 1);
    save(); updateBadge(); render();
  }

  function render() {
    const el     = document.getElementById('cartItems');
    const footer = document.getElementById('cartFooter');
    if (!el) return;

    if (!items.length) {
      el.innerHTML = '<p class="cart-empty">Votre panier est vide</p>';
      if (footer) footer.style.display = 'none';
      return;
    }

    if (footer) footer.style.display = 'block';

    el.innerHTML = items.map((item, idx) => `
      <div class="cart-item">
        <div>
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-qty">Qté : ${item.qty}${item.price ? ' · ' + (item.price * item.qty).toLocaleString() + ' FCFA' : ''}</div>
        </div>
        <button class="cart-item-remove" onclick="Cart.remove(${idx})">✕</button>
      </div>
    `).join('');

    const totalEl = document.getElementById('cartTotal');
    if (totalEl) {
      totalEl.textContent = total()
        ? total().toLocaleString() + ' FCFA'
        : count() + ' article(s)';
    }
  }

  function open()  {
    const modal = document.getElementById('cartModal');
    if (modal) { modal.classList.add('open'); render(); }
  }
  function close() {
    const modal = document.getElementById('cartModal');
    if (modal) modal.classList.remove('open');
  }

  // Fermer en cliquant en dehors
  document.addEventListener('click', e => {
    const modal = document.getElementById('cartModal');
    if (modal && e.target === modal) close();
  });

  // Init badge au chargement
  document.addEventListener('DOMContentLoaded', updateBadge);

  return { add, remove, open, close, render, count, total };
})();

/* ── SCROLL REVEAL ── */
(function initReveal() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
})();

/* ── NEWSLETTER ── */
function subscribeNewsletter() {
  const input = document.getElementById('nlEmail');
  if (!input) return;
  const val = input.value.trim();
  if (!val || !val.includes('@') || !val.includes('.')) {
    showToast('Email invalide', 'Veuillez saisir une adresse correcte');
    return;
  }
  /* TODO: remplacer par un vrai appel API (Mailchimp, Brevo, etc.)
     fetch('/api/newsletter', { method:'POST', body: JSON.stringify({email: val}) }) */
  showToast('Inscription réussie !', 'Bienvenue dans la communauté Sophiate');
  input.value = '';
}

/* ── WISHLIST (bouton favori) ── */
function toggleWishlist(btn) {
  const isFav = btn.dataset.fav === '1';
  btn.dataset.fav = isFav ? '0' : '1';
  btn.textContent = isFav ? '♡' : '♥';
  btn.style.color = isFav ? '' : 'var(--orange)';
  if (!isFav) showToast('Ajouté aux favoris', 'Retrouvez-le dans votre liste');
}
