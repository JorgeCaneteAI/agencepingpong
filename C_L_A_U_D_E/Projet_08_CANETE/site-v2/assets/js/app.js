/**
 * app.js — Agence Ping Pong
 * Main application initialisation.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  /* ------------------------------------------------------------------ */
  /* 0. MAC OS 8 CLOCK                                                    */
  /* ------------------------------------------------------------------ */
  var navClock = document.getElementById('nav-clock');
  if (navClock) {
    function updateClock() {
      var now = new Date();
      var h = now.getHours();
      var m = now.getMinutes();
      var suffix = h >= 12 ? 'PM' : 'AM';
      var h12 = h % 12 || 12;
      var mStr = m < 10 ? '0' + m : m;
      navClock.textContent = h12 + ':' + mStr + ' ' + suffix;
    }
    updateClock();
    setInterval(updateClock, 10000);
  }

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
  /* 1b. HEADER SCROLL EFFECT                                              */
  /* ------------------------------------------------------------------ */
  var headerEl = document.getElementById('site-header');
  var scrollThreshold = 50;

  lenis.on('scroll', function (e) {
    if (!headerEl) return;
    if (e.scroll > scrollThreshold) {
      headerEl.classList.add('site-header--scrolled');
    } else {
      headerEl.classList.remove('site-header--scrolled');
    }
  });

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
  /* 3b. DESKTOP NAV LINKS — smooth scroll + active state                */
  /* ------------------------------------------------------------------ */
  var navLinks = document.querySelectorAll('.site-nav__link');

  navLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var target = link.getAttribute('href');
      lenis.scrollTo(target, { duration: 1.2 });
    });
  });

  // Hero icons smooth scroll
  var heroIcons = document.querySelectorAll('.hero__icon');
  heroIcons.forEach(function (icon) {
    icon.addEventListener('click', function (e) {
      e.preventDefault();
      var target = icon.getAttribute('href');
      lenis.scrollTo(target, { duration: 1.2 });
    });
  });

  // Active state tracking via ScrollTrigger
  var navSections = document.querySelectorAll('[id]');
  navSections.forEach(function (section) {
    var sectionId = section.getAttribute('id');
    var matchingLink = document.querySelector('.site-nav__link[data-section="' + sectionId + '"]');
    if (!matchingLink) return;

    ScrollTrigger.create({
      trigger: section,
      start: 'top center',
      end: 'bottom center',
      onEnter: function () {
        navLinks.forEach(function (l) { l.classList.remove('site-nav__link--active'); });
        matchingLink.classList.add('site-nav__link--active');
      },
      onEnterBack: function () {
        navLinks.forEach(function (l) { l.classList.remove('site-nav__link--active'); });
        matchingLink.classList.add('site-nav__link--active');
      }
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

      var submitBtn = document.getElementById('contact-submit');
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
            // Clear saved draft
            localStorage.removeItem('pingpong_contact_draft');
          } else {
            var msg = data.message || 'Une erreur est survenue. Réessaie.';
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = '✉ Envoyer la balle';
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
  /* 7. BACK TO TOP BUTTON                                                */
  /* ------------------------------------------------------------------ */
  var backToTopBtn = document.getElementById('back-to-top');
  var navBackToTop = document.getElementById('nav-back-to-top');

  function scrollToTop(e) {
    e.preventDefault();
    lenis.scrollTo(0, { duration: 0.8 });
  }

  // Show/hide both back-to-top elements
  lenis.on('scroll', function (e) {
    if (e.scroll > 600) {
      if (backToTopBtn) backToTopBtn.classList.add('back-to-top--visible');
      if (navBackToTop) navBackToTop.classList.add('site-nav__icon--top--visible');
    } else {
      if (backToTopBtn) backToTopBtn.classList.remove('back-to-top--visible');
      if (navBackToTop) navBackToTop.classList.remove('site-nav__icon--top--visible');
    }
  });

  if (backToTopBtn) backToTopBtn.addEventListener('click', scrollToTop);
  if (navBackToTop) navBackToTop.addEventListener('click', scrollToTop);

  /* ------------------------------------------------------------------ */
  /* 8. VH FIX — set --vh CSS custom property                            */
  /* ------------------------------------------------------------------ */
  function setVh() {
    var vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', vh + 'px');
  }

  setVh();
  window.addEventListener('resize', debounce(setVh, 150));

  /* ------------------------------------------------------------------ */
  /* 9. CONCEPT ACCORDION                                                 */
  /* ------------------------------------------------------------------ */
  var accordionBtns = document.querySelectorAll('.concept__accordion-btn');
  accordionBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var body = btn.nextElementSibling;
      var isOpen = btn.classList.contains('is-open');

      // Close all
      accordionBtns.forEach(function (b) {
        b.classList.remove('is-open');
        b.querySelector('.concept__accordion-arrow').textContent = '▶';
        b.nextElementSibling.classList.remove('is-visible');
      });

      // Toggle clicked
      if (!isOpen) {
        btn.classList.add('is-open');
        btn.querySelector('.concept__accordion-arrow').textContent = '▼';
        body.classList.add('is-visible');
      }
    });
  });

  /* ------------------------------------------------------------------ */
  /* 10. SERVICES CHOOSER                                                 */
  /* ------------------------------------------------------------------ */
  var chooserServices = {
    web: [
      { name: 'Site vitrine', desc: 'Ton coup droit de base. Un site clair, rapide, qui tape o\u00f9 il faut.' },
      { name: 'Site e-commerce', desc: 'La boutique en ligne qui convertit. Panier, paiement, c\u2019est pli\u00e9.' },
      { name: 'Landing page', desc: 'Une page, un objectif. Frappe chirurgicale.' },
      { name: 'Refonte de site', desc: 'Ton ancien site prend la poussi\u00e8re\u202f? On le remet au filet.' },
      { name: 'R\u00e9f\u00e9rencement SEO', desc: 'On te fait monter dans Google. Effet lift\u00e9, r\u00e9sultat long terme.' },
      { name: 'Maintenance web', desc: 'Mises \u00e0 jour, s\u00e9curit\u00e9, sauvegardes. Ton site reste en forme.' },
      { name: 'H\u00e9bergement', desc: 'Serveur rapide, SSL, emails. On g\u00e8re la technique.' },
      { name: 'Blog / CMS', desc: 'Publie tes actus en toute autonomie. WordPress, sur mesure.' },
      { name: 'Design responsive', desc: 'Mobile, tablette, desktop. \u00c7a passe partout.' },
      { name: 'Analytics & suivi', desc: 'Comprendre tes visiteurs. Donn\u00e9es claires, d\u00e9cisions fut\u00e9es.' }
    ],
    print: [
      { name: 'Carte de visite', desc: 'Premier contact. Un petit carton qui fait grande impression.' },
      { name: 'Flyer / Tract', desc: 'Distribution massive. Le message arrive droit dans les mains.' },
      { name: 'Affiche', desc: 'Grand format, grand impact. On capte le regard.' },
      { name: 'Brochure', desc: 'Raconte ton histoire en plusieurs pages. Papier noble.' },
      { name: 'Plaquette commerciale', desc: 'L\u2019outil de vente papier. Pro, structur\u00e9, convaincant.' },
      { name: 'Menu restaurant', desc: 'Carte, ardoise, livret. Tes plats m\u00e9ritent un bel \u00e9crin.' },
      { name: 'Packaging', desc: 'L\u2019emballage qui fait vendre. Du carton au sticker.' },
      { name: 'Kak\u00e9mono / Roll-up', desc: 'Stand, salon, vitrine. Visible de loin.' },
      { name: 'Papeterie', desc: 'En-t\u00eate, enveloppe, tampon. Coh\u00e9rence dans le d\u00e9tail.' },
      { name: 'Signal\u00e9tique', desc: 'Panneaux, enseignes, fl\u00e9chage. On te trouve.' }
    ],
    identity: [
      { name: 'Logo', desc: 'Le smash. Ton identit\u00e9 en un coup d\u2019\u0153il. M\u00e9morable, unique.' },
      { name: 'Charte graphique', desc: 'Le rulebook. Couleurs, typos, usages. Tout est cadr\u00e9.' },
      { name: 'Direction artistique', desc: 'Le style qui te ressemble. On d\u00e9finit ton univers visuel.' },
      { name: 'Naming', desc: 'Trouver LE nom. Celui qui claque et qu\u2019on n\u2019oublie pas.' },
      { name: 'Univers de marque', desc: 'Plus qu\u2019un logo\u202f: une ambiance, une voix, une \u00e2me.' },
      { name: 'R\u00e9seaux sociaux', desc: 'Templates, banni\u00e8res, posts. Ton feed a du style.' },
      { name: 'Motion design', desc: 'Logo anim\u00e9, intro vid\u00e9o. Ton identit\u00e9 prend vie.' },
      { name: 'Photographie', desc: 'Shooting pro pour tes produits, ton \u00e9quipe, tes locaux.' },
      { name: 'Illustration', desc: 'Du sur-mesure dessin\u00e9. Mascotte, pictos, univers graphique.' },
      { name: 'Brand book', desc: 'Le guide complet de ta marque. Bible visuelle et \u00e9ditoriale.' }
    ]
  };

  var chooserCurrentCat = 'web';
  var chooserList = document.getElementById('chooser-service-list');
  var chooserIconBtns = document.querySelectorAll('.chooser__icon-btn');

  function chooserRenderList() {
    if (!chooserList) return;
    while (chooserList.firstChild) chooserList.removeChild(chooserList.firstChild);

    var items = chooserServices[chooserCurrentCat];
    items.forEach(function (service, index) {
      var div = document.createElement('div');
      div.className = 'chooser__list-item' + (index === 0 ? ' is-active' : '');
      div.dataset.index = String(index);
      div.textContent = service.name;
      div.addEventListener('click', function () {
        chooserList.querySelectorAll('.chooser__list-item').forEach(function (li) {
          li.classList.remove('is-active');
        });
        div.classList.add('is-active');
        var idx = parseInt(div.dataset.index);
        chooserUpdatePreview(idx);
        if (qtProgressFill) {
          var total = chooserServices[chooserCurrentCat].length;
          qtProgressFill.style.width = ((idx + 1) / total * 100) + '%';
        }
      });
      chooserList.appendChild(div);
    });

    chooserUpdatePreview(0);
  }

  function chooserUpdatePreview(index) {
    var service = chooserServices[chooserCurrentCat][index];
    var titleEl = document.getElementById('qt-title');
    var descEl = document.getElementById('qt-desc');
    var screenEl = document.getElementById('qt-screen');

    if (titleEl) titleEl.textContent = service.name;
    if (descEl) descEl.textContent = service.desc;

    if (screenEl) {
      while (screenEl.firstChild) screenEl.removeChild(screenEl.firstChild);
      var tpl = document.getElementById('tpl-' + chooserCurrentCat + '-' + index);
      if (tpl) {
        var clone = tpl.content.cloneNode(true);
        screenEl.appendChild(clone);
      }
    }
  }

  chooserIconBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      chooserIconBtns.forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      chooserCurrentCat = btn.dataset.category;
      chooserRenderList();
    });
  });

  if (chooserList) chooserRenderList();

  /* ------------------------------------------------------------------ */
  /* 11. CONTACT TOOLBAR — Dropdowns, Fonts, Colors, File, Brouillon     */
  /* ------------------------------------------------------------------ */

  // --- Dropdown toggle (Polices & Couleurs) ---
  var dropdowns = document.querySelectorAll('.contact-win__toolbar-dropdown');
  dropdowns.forEach(function (dd) {
    // Stop propagation on the whole dropdown so document click doesn't close it
    dd.addEventListener('click', function (e) {
      e.stopPropagation();
    });
    var trigger = dd.querySelector('.contact-win__toolbar-btn');
    if (!trigger) return;
    trigger.addEventListener('click', function () {
      var wasOpen = dd.classList.contains('is-open');
      // Close all dropdowns first
      dropdowns.forEach(function (d) { d.classList.remove('is-open'); });
      if (!wasOpen) dd.classList.add('is-open');
    });
  });
  // Close dropdowns on outside click
  document.addEventListener('click', function () {
    dropdowns.forEach(function (d) { d.classList.remove('is-open'); });
  });

  // --- Font switching ---
  var fontItems = document.querySelectorAll('#font-menu .contact-win__dropdown-item');
  var contactBody = document.getElementById('contact-form-wrapper');
  var fontMap = {
    chicago: "'ChicagoFLF', 'Geneva', sans-serif",
    grotesk: "'Space Grotesk', sans-serif",
    clash: "'Clash Display', sans-serif",
    mono: "'Monaco', 'Courier New', monospace"
  };
  fontItems.forEach(function (item) {
    item.addEventListener('click', function () {
      var font = item.dataset.font;
      if (!contactBody || !fontMap[font]) return;
      // Apply font to form body
      contactBody.style.fontFamily = fontMap[font];
      // Also apply to all inputs/textarea inside
      var inputs = contactBody.querySelectorAll('input, textarea');
      inputs.forEach(function (el) { el.style.fontFamily = fontMap[font]; });
      // Toggle active state
      fontItems.forEach(function (fi) { fi.classList.remove('is-active'); });
      item.classList.add('is-active');
      // Close dropdown
      document.getElementById('toolbar-fonts').classList.remove('is-open');
    });
  });

  // --- Color theme switching ---
  var colorItems = document.querySelectorAll('#color-menu .contact-win__dropdown-item');
  colorItems.forEach(function (item) {
    item.addEventListener('click', function () {
      var color = item.dataset.color;
      if (!contactBody) return;
      // Remove or set data-theme
      if (color === 'cream') {
        contactBody.removeAttribute('data-theme');
      } else {
        contactBody.setAttribute('data-theme', color);
      }
      colorItems.forEach(function (ci) { ci.classList.remove('is-active'); });
      item.classList.add('is-active');
      document.getElementById('toolbar-colors').classList.remove('is-open');
    });
  });

  // --- File upload (Joindre) ---
  var fileInput = document.getElementById('contact-file');
  var attachPreview = document.getElementById('attachment-preview');
  var attachName = document.getElementById('attachment-name');
  var attachRemove = document.getElementById('attachment-remove');
  var maxFileSize = 2 * 1024 * 1024; // 2 MB
  var allowedTypes = ['image/jpeg', 'image/png'];

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files[0];
      if (!file) return;

      // Validate type
      if (allowedTypes.indexOf(file.type) === -1) {
        alert('Seuls les fichiers JPG et PNG sont acceptés.');
        fileInput.value = '';
        return;
      }
      // Validate size
      if (file.size > maxFileSize) {
        alert('Le fichier ne doit pas dépasser 2 Mo.');
        fileInput.value = '';
        return;
      }

      // Show preview bar
      if (attachPreview && attachName) {
        attachName.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' Ko)';
        attachPreview.hidden = false;
      }
    });
  }

  if (attachRemove) {
    attachRemove.addEventListener('click', function () {
      if (fileInput) fileInput.value = '';
      if (attachPreview) attachPreview.hidden = true;
    });
  }

  // --- Brouillon (localStorage save/restore) ---
  var brouillonBtn = document.getElementById('toolbar-brouillon');
  var brouillonKey = 'pingpong_contact_draft';

  if (brouillonBtn && contactForm) {
    brouillonBtn.style.cursor = 'pointer';
    brouillonBtn.addEventListener('click', function () {
      var draft = {
        email: contactForm.querySelector('[name="email"]').value,
        name: contactForm.querySelector('[name="name"]').value,
        phone: contactForm.querySelector('[name="phone"]').value,
        subject: contactForm.querySelector('[name="subject"]').value,
        message: contactForm.querySelector('[name="message"]').value
      };
      localStorage.setItem(brouillonKey, JSON.stringify(draft));
      var original = brouillonBtn.textContent;
      brouillonBtn.textContent = '✓ Sauvé !';
      setTimeout(function () { brouillonBtn.textContent = original; }, 2000);
    });

    // Restore draft on load if exists
    try {
      var saved = localStorage.getItem(brouillonKey);
      if (saved) {
        var draft = JSON.parse(saved);
        var emailField = contactForm.querySelector('[name="email"]');
        var nameField = contactForm.querySelector('[name="name"]');
        var phoneField = contactForm.querySelector('[name="phone"]');
        var subjectField = contactForm.querySelector('[name="subject"]');
        var msgField = contactForm.querySelector('[name="message"]');
        if (draft.email && emailField) emailField.value = draft.email;
        if (draft.name && nameField) nameField.value = draft.name;
        if (draft.phone && phoneField) phoneField.value = draft.phone;
        if (draft.subject && subjectField) subjectField.value = draft.subject;
        if (draft.message && msgField) msgField.value = draft.message;
      }
    } catch (e) { /* ignore parse errors */ }
  }

  /* QuickTime player controls */
  var qtPlayBtn = document.querySelector('.qt-player__btn--play');
  var qtPrevBtn = document.querySelector('.qt-player__btn--prev');
  var qtNextBtn = document.querySelector('.qt-player__btn--next');
  var qtProgressFill = document.querySelector('.qt-player__progress-fill');
  var qtProgressBar = document.querySelector('.qt-player__progress');
  var chooserAutoplayTimer = null;
  var chooserIsPlaying = false;

  function chooserGetActiveIndex() {
    if (!chooserList) return 0;
    var active = chooserList.querySelector('.chooser__list-item.is-active');
    return active ? parseInt(active.dataset.index) : 0;
  }

  function chooserSelectByIndex(idx) {
    if (!chooserList) return;
    var items = chooserList.querySelectorAll('.chooser__list-item');
    var total = items.length;
    if (total === 0) return;
    if (idx < 0) idx = total - 1;
    if (idx >= total) idx = 0;
    items.forEach(function (li) { li.classList.remove('is-active'); });
    items[idx].classList.add('is-active');
    chooserUpdatePreview(idx);
    chooserUpdateProgress(idx, total);
  }

  function chooserUpdateProgress(idx, total) {
    if (!qtProgressFill) return;
    var pct = total > 1 ? ((idx + 1) / total) * 100 : 0;
    qtProgressFill.style.width = pct + '%';
  }

  // Prev button
  if (qtPrevBtn) {
    qtPrevBtn.addEventListener('click', function () {
      chooserSelectByIndex(chooserGetActiveIndex() - 1);
    });
  }

  // Next button
  if (qtNextBtn) {
    qtNextBtn.addEventListener('click', function () {
      chooserSelectByIndex(chooserGetActiveIndex() + 1);
    });
  }

  // Play/Pause — auto-advance every 2s
  if (qtPlayBtn) {
    qtPlayBtn.addEventListener('click', function () {
      if (chooserIsPlaying) {
        clearInterval(chooserAutoplayTimer);
        chooserAutoplayTimer = null;
        chooserIsPlaying = false;
        qtPlayBtn.textContent = '▶';
      } else {
        chooserIsPlaying = true;
        qtPlayBtn.textContent = '⏸';
        chooserAutoplayTimer = setInterval(function () {
          chooserSelectByIndex(chooserGetActiveIndex() + 1);
        }, 2000);
      }
    });
  }

  // Click on progress bar to seek
  if (qtProgressBar) {
    qtProgressBar.addEventListener('click', function (e) {
      var rect = qtProgressBar.getBoundingClientRect();
      var pct = (e.clientX - rect.left) / rect.width;
      var items = chooserList ? chooserList.querySelectorAll('.chooser__list-item') : [];
      var idx = Math.floor(pct * items.length);
      if (idx >= items.length) idx = items.length - 1;
      if (idx < 0) idx = 0;
      chooserSelectByIndex(idx);
    });
    qtProgressBar.style.cursor = 'pointer';
  }

  // Stop autoplay when changing category
  var origRenderList = chooserRenderList;
  chooserRenderList = function () {
    if (chooserIsPlaying && qtPlayBtn) {
      clearInterval(chooserAutoplayTimer);
      chooserAutoplayTimer = null;
      chooserIsPlaying = false;
      qtPlayBtn.textContent = '▶';
    }
    origRenderList();
  };

})();
