/* assets/js/main.js */

document.addEventListener("DOMContentLoaded", () => {
  const y = document.getElementById("year");
  if (y) y.textContent = String(new Date().getFullYear());

  initMobileNav();
  initRevealAnimations();
  initHeroMotion();
  initBackToTop();
});

function initMobileNav() {
  const headerInner = document.querySelector(".header-inner");
  const nav = document.querySelector(".nav");
  if (!headerInner || !nav) return;

  let toggle = document.querySelector(".nav-toggle");
  if (!toggle) {
    toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "nav-toggle";
    toggle.setAttribute("aria-label", "Ouvrir le menu");
    toggle.setAttribute("aria-expanded", "false");
    toggle.innerHTML = '<span class="nav-toggle-bar"></span><span class="nav-toggle-bar"></span><span class="nav-toggle-bar"></span>';
    headerInner.appendChild(toggle);
  }

  let overlay = document.querySelector(".nav-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "nav-overlay";
    document.body.appendChild(overlay);
  }

  const closeNav = () => {
    nav.classList.remove("nav-open");
    overlay.classList.remove("nav-overlay-visible");
    toggle.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  };

  const openNav = () => {
    nav.classList.add("nav-open");
    overlay.classList.add("nav-overlay-visible");
    toggle.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
  };

  const mqMobile = window.matchMedia("(max-width: 1100px)");
  const syncNavInDom = () => {
    if (mqMobile.matches) {
      if (nav.parentElement !== document.body) {
        document.body.appendChild(nav);
      }
    } else {
      closeNav();
      if (nav.parentElement !== headerInner) {
        headerInner.insertBefore(nav, toggle);
      }
    }
  };

  syncNavInDom();
  if (typeof mqMobile.addEventListener === "function") {
    mqMobile.addEventListener("change", syncNavInDom);
  } else if (typeof mqMobile.addListener === "function") {
    mqMobile.addListener(syncNavInDom);
  }

  toggle.addEventListener("click", () => {
    if (nav.classList.contains("nav-open")) {
      closeNav();
    } else {
      openNav();
    }
  });

  overlay.addEventListener("click", closeNav);
  nav.querySelectorAll("a").forEach((link) => link.addEventListener("click", closeNav));

  window.addEventListener("resize", () => {
    if (window.innerWidth > 1100) closeNav();
  });
}

function initRevealAnimations() {
  const candidates = document.querySelectorAll(
    ".hero-visual, .home-hero-copy, .hero-console, .sub-hero-grid > *, .contact-hero .container, .signal-grid > div, .section, .card, .expertise-card, .editorial-card, .sector-matrix article, .process-timeline article, .case-visual, .case-content, .diagnosis-form, .aside-card, .cta-card"
  );
  if (!candidates.length) return;

  let delay = 1;
  candidates.forEach((el) => {
    if (!el.hasAttribute("data-reveal")) {
      el.setAttribute("data-reveal", "up");
      el.setAttribute("data-reveal-delay", String(delay));
      delay = delay >= 8 ? 1 : delay + 1;
    }
  });

  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    candidates.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
        } else {
          entry.target.classList.remove("is-visible");
        }
      });
    },
    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
  );

  candidates.forEach((el) => observer.observe(el));
}

function initHeroMotion() {
  const hero = document.querySelector(".hero-console");
  if (!hero || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

  hero.addEventListener("pointermove", (event) => {
    if (window.innerWidth < 1100) return;
    const rect = hero.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width - 0.5) * 8;
    const y = ((event.clientY - rect.top) / rect.height - 0.5) * -8;
    hero.style.transform = `perspective(900px) rotateY(${x}deg) rotateX(${y}deg) translateY(-2px)`;
  });

  hero.addEventListener("pointerleave", () => {
    hero.style.transform = "";
  });
}

function initBackToTop() {
  let button = document.querySelector(".back-to-top");
  if (!button) {
    button = document.createElement("button");
    button.type = "button";
    button.className = "back-to-top";
    button.setAttribute("aria-label", "Retour en haut");
    button.textContent = "\u2191";
    document.body.appendChild(button);
  }

  const toggleVisibility = () => {
    const shouldShow = window.scrollY > 300;
    button.style.opacity = shouldShow ? "1" : "0";
    button.style.pointerEvents = shouldShow ? "auto" : "none";
  };

  toggleVisibility();
  window.addEventListener("scroll", toggleVisibility, { passive: true });
  button.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}
