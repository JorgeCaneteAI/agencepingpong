/**
 * app.js — Agence Ping Pong
 * Main application initialisation.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  /* ------------------------------------------------------------------ */
  /* 8. DEBOUNCE UTILITY                                                  */
  /* ------------------------------------------------------------------ */
  function debounce(fn, wait) {
    var timer;
    return function () {
      var ctx = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  /* ------------------------------------------------------------------ */
  /* 1. LENIS SMOOTH SCROLL                                               */
  /* ------------------------------------------------------------------ */
  var lenis = new Lenis({
    duration: 1.2,
    easing: function (t) {
      return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    },
    orientation: 'vertical',
    smoothWheel: true
  });

  lenis.on('scroll', ScrollTrigger.update);

  gsap.ticker.add(function (time) {
    lenis.raf(time * 1000);
  });

  gsap.ticker.lagSmoothing(0);

  // Expose globally so other modules can use it
  window.__lenis = lenis;

  /* ------------------------------------------------------------------ */
  /* 2. MENU BURGER                                                       */
  /* ------------------------------------------------------------------ */
  var burgerBtn = document.getElementById('burger-btn');
  var fullscreenMenu = document.getElementById('fullscreen-menu');
  var siteHeader = document.getElementById('site-header');

  function openMenu() {
    if (!fullscreenMenu || !burgerBtn || !siteHeader) return;
    fullscreenMenu.classList.add('fullscreen-menu--open');
    siteHeader.classList.add('site-header--menu-open');
    burgerBtn.setAttribute('aria-expanded', 'true');
    burgerBtn.setAttribute('aria-label', 'Fermer le menu');
    fullscreenMenu.setAttribute('aria-hidden', 'false');
    lenis.stop();
  }

  function closeMenu() {
    if (!fullscreenMenu || !burgerBtn || !siteHeader) return;
    fullscreenMenu.classList.remove('fullscreen-menu--open');
    siteHeader.classList.remove('site-header--menu-open');
    burgerBtn.setAttribute('aria-expanded', 'false');
    burgerBtn.setAttribute('aria-label', 'Ouvrir le menu');
    fullscreenMenu.setAttribute('aria-hidden', 'true');
    lenis.start();
  }

  if (burgerBtn) {
    burgerBtn.addEventListener('click', function () {
      var isOpen = fullscreenMenu.classList.contains('fullscreen-menu--open');
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    });
  }

  // Escape key closes menu
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMenu();
    }
  });

  /* ------------------------------------------------------------------ */
  /* 3. MENU LINKS — scroll to target after close                        */
  /* ------------------------------------------------------------------ */
  var menuLinks = document.querySelectorAll('.fullscreen-menu__link');
  menuLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var target = link.getAttribute('href');
      closeMenu();
      setTimeout(function () {
        lenis.scrollTo(target, { duration: 1.2 });
      }, 400);
    });
  });

  /* ------------------------------------------------------------------ */
  /* 4. HEADER DARK MODE                                                  */
  /* ------------------------------------------------------------------ */
  var darkSections = document.querySelectorAll('.section--dark');
  darkSections.forEach(function (section) {
    ScrollTrigger.create({
      trigger: section,
      start: 'top 60px',
      end: 'bottom 60px',
      onEnter: function () {
        siteHeader && siteHeader.classList.add('site-header--dark');
      },
      onLeave: function () {
        siteHeader && siteHeader.classList.remove('site-header--dark');
      },
      onEnterBack: function () {
        siteHeader && siteHeader.classList.add('site-header--dark');
      },
      onLeaveBack: function () {
        siteHeader && siteHeader.classList.remove('site-header--dark');
      }
    });
  });

  /* ------------------------------------------------------------------ */
  /* 5. LOGO SCROLL TO TOP                                               */
  /* ------------------------------------------------------------------ */
  var logoLink = document.querySelector('.site-header__logo');
  if (logoLink) {
    logoLink.addEventListener('click', function (e) {
      e.preventDefault();
      lenis.scrollTo(0, { duration: 1.4 });
    });
  }

  /* ------------------------------------------------------------------ */
  /* 6. CONTACT FORM AJAX                                                 */
  /* ------------------------------------------------------------------ */
  var contactForm = document.getElementById('contact-form');
  var contactSuccess = document.getElementById('contact-success');

  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var submitBtn = contactForm.querySelector('.form__submit');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours…';
      }

      var formData = new FormData(contactForm);

      fetch(contactForm.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (data.success) {
            contactForm.hidden = true;
            if (contactSuccess) {
              contactSuccess.hidden = false;
            }
          } else {
            var msg = data.message || 'Une erreur est survenue. Réessaie.';
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = 'Envoyer la balle';
            }
            // Show error below form
            var existing = contactForm.querySelector('.form__error-msg');
            if (!existing) {
              var errEl = document.createElement('p');
              errEl.className = 'form__error-msg';
              errEl.setAttribute('role', 'alert');
              errEl.textContent = msg;
              contactForm.appendChild(errEl);
            } else {
              existing.textContent = msg;
            }
          }
        })
        .catch(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Envoyer la balle';
          }
          var existing = contactForm.querySelector('.form__error-msg');
          if (!existing) {
            var errEl = document.createElement('p');
            errEl.className = 'form__error-msg';
            errEl.setAttribute('role', 'alert');
            errEl.textContent = 'Erreur réseau. Vérifie ta connexion et réessaie.';
            contactForm.appendChild(errEl);
          } else {
            existing.textContent = 'Erreur réseau. Vérifie ta connexion et réessaie.';
          }
        });
    });
  }

  /* ------------------------------------------------------------------ */
  /* 7. VH FIX — set --vh CSS custom property                            */
  /* ------------------------------------------------------------------ */
  function setVh() {
    var vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', vh + 'px');
  }

  setVh();
  window.addEventListener('resize', debounce(setVh, 150));

})();
