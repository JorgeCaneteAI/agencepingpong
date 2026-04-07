/* ========================================
   APP.JS — Init Lenis, navigation, scroll
   ======================================== */

(function () {
    'use strict';

    // --- Lenis smooth scroll ---
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smoothWheel: true,
    });

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // Sync Lenis with GSAP ScrollTrigger
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    // --- VH unit fix (mobile) ---
    function setVh() {
        document.documentElement.style.setProperty('--vh', window.innerHeight * 0.01 + 'px');
        document.documentElement.style.setProperty('--inner-width', window.innerWidth + 'px');
    }
    setVh();
    window.addEventListener('resize', setVh);

    // --- Navigation desktop : active state ---
    const navLinks = document.querySelectorAll('.site-nav__link');
    const sections = document.querySelectorAll('.section');

    sections.forEach((section) => {
        ScrollTrigger.create({
            trigger: section,
            start: 'top center',
            end: 'bottom center',
            onEnter: () => setActiveNav(section.id),
            onEnterBack: () => setActiveNav(section.id),
        });
    });

    function setActiveNav(sectionId) {
        navLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.section === sectionId);
        });
    }

    // --- Navigation : smooth scroll to section ---
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);
            if (target) {
                lenis.scrollTo(target, { offset: 0 });
                closeMobileMenu();
            }
        });
    });

    // --- Mobile menu ---
    const burger = document.querySelector('.site-header__burger');
    const mobileMenu = document.querySelector('.mobile-menu');
    const blocker = document.querySelector('.mobile-menu__blocker');

    function openMobileMenu() {
        burger.classList.add('is-open');
        mobileMenu.classList.add('is-open');
        blocker.classList.add('is-visible');
        lenis.stop();
    }

    function closeMobileMenu() {
        burger.classList.remove('is-open');
        mobileMenu.classList.remove('is-open');
        blocker.classList.remove('is-visible');
        lenis.start();
    }

    if (burger) {
        burger.addEventListener('click', () => {
            if (mobileMenu.classList.contains('is-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (blocker) {
        blocker.addEventListener('click', closeMobileMenu);
    }

    // --- Contact form (AJAX) ---
    const contactForm = document.querySelector('.contact__form');
    const contactSuccess = document.querySelector('.contact__success');

    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(contactForm);

            try {
                const response = await fetch(contactForm.action, {
                    method: 'POST',
                    body: formData,
                });
                const result = await response.json();

                if (result.success) {
                    contactForm.hidden = true;
                    contactSuccess.hidden = false;
                }
            } catch (err) {
                console.error('Erreur envoi formulaire:', err);
            }
        });
    }
})();
