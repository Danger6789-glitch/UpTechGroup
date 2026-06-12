/* ===========================
   JEUNE DÉBROUILLARD — JS
   =========================== */

// --- NAV SCROLL ---
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 60);
});

// --- MOBILE MENU ---
const toggle = document.getElementById('navToggle');
const links = document.querySelector('.nav-links');

toggle.addEventListener('click', () => {
  links.classList.toggle('open');
  const spans = toggle.querySelectorAll('span');
  const isOpen = links.classList.contains('open');
  spans[0].style.transform = isOpen ? 'translateY(7.5px) rotate(45deg)' : '';
  spans[1].style.opacity = isOpen ? '0' : '1';
  spans[2].style.transform = isOpen ? 'translateY(-7.5px) rotate(-45deg)' : '';
});

links.querySelectorAll('a').forEach(a => {
  a.addEventListener('click', () => {
    links.classList.remove('open');
    toggle.querySelectorAll('span').forEach(s => {
      s.style.transform = '';
      s.style.opacity = '';
    });
  });
});

// --- TABS (Collection Homme/Femme) ---
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const target = tab.dataset.tab;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('products-femme').classList.toggle('hidden', target !== 'femme');
    document.getElementById('products-homme').classList.toggle('hidden', target !== 'homme');
  });
});

// --- INTERSECTION OBSERVER (ADN items) ---
const adnItems = document.querySelectorAll('.adn-item');

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const delay = entry.target.dataset.delay || 0;
      setTimeout(() => {
        entry.target.classList.add('visible');
      }, parseInt(delay));
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });

adnItems.forEach(item => observer.observe(item));

// --- PARALLAX HERO BG TEXT ---
const heroBgText = document.querySelector('.hero-bg-text');
window.addEventListener('scroll', () => {
  if (window.scrollY < window.innerHeight) {
    heroBgText.style.transform = `translate(-50%, calc(-50% + ${window.scrollY * 0.3}px))`;
  }
});

// --- SMOOTH REVEAL ON SCROLL ---
const revealEls = document.querySelectorAll('.about-grid, .manifesto-text, .contact-inner');
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

revealEls.forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(24px)';
  el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
  revealObserver.observe(el);
});
