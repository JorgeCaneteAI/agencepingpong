/* ========================================
   ANIMATIONS.JS — GSAP ScrollTrigger animations
   ======================================== */

(function () {
    'use strict';

    gsap.registerPlugin(ScrollTrigger, SplitText);

    // --- Wait for DOM + fonts ---
    window.addEventListener('load', initAnimations);

    function initAnimations() {
        animateSplitReveal();
        animateFadeUp();
        animateHeroPin();
        animateServicesStagger();
        animateRealisationsStagger();
    }

    // --- SplitText reveal (titres + textes) ---
    function animateSplitReveal() {
        const elements = document.querySelectorAll('[data-animate="split-reveal"]');

        elements.forEach((el) => {
            const split = new SplitText(el, { type: 'lines,words' });

            gsap.set(split.words, {
                yPercent: 110,
                opacity: 0,
            });

            ScrollTrigger.create({
                trigger: el,
                start: 'top 85%',
                once: true,
                onEnter: () => {
                    gsap.to(split.words, {
                        yPercent: 0,
                        opacity: 1,
                        duration: 0.8,
                        ease: 'power3.out',
                        stagger: 0.04,
                    });
                },
            });
        });
    }

    // --- Fade up (éléments génériques) ---
    function animateFadeUp() {
        const elements = document.querySelectorAll('[data-animate="fade-up"]');

        elements.forEach((el) => {
            gsap.set(el, {
                y: 60,
                opacity: 0,
            });

            ScrollTrigger.create({
                trigger: el,
                start: 'top 90%',
                once: true,
                onEnter: () => {
                    gsap.to(el, {
                        y: 0,
                        opacity: 1,
                        duration: 0.8,
                        ease: 'power2.out',
                    });
                },
            });
        });
    }

    // --- Hero pin (section fixe pendant le scroll) ---
    function animateHeroPin() {
        const hero = document.querySelector('#hero');
        if (!hero) return;

        ScrollTrigger.create({
            trigger: hero,
            start: 'top top',
            end: 'bottom top',
            pin: '.hero',
            pinSpacing: false,
        });

        // Fade out hero content on scroll
        gsap.to('.hero__content', {
            opacity: 0,
            y: -100,
            ease: 'none',
            scrollTrigger: {
                trigger: hero,
                start: '30% top',
                end: '60% top',
                scrub: true,
            },
        });
    }

    // --- Services stagger ---
    function animateServicesStagger() {
        const items = document.querySelectorAll('.services__item');
        if (!items.length) return;

        gsap.set(items, { y: 40, opacity: 0 });

        ScrollTrigger.create({
            trigger: '.services__grid',
            start: 'top 80%',
            once: true,
            onEnter: () => {
                gsap.to(items, {
                    y: 0,
                    opacity: 1,
                    duration: 0.6,
                    ease: 'power2.out',
                    stagger: 0.15,
                });
            },
        });
    }

    // --- Réalisations stagger ---
    function animateRealisationsStagger() {
        const cards = document.querySelectorAll('.realisations__card');
        if (!cards.length) return;

        gsap.set(cards, { y: 60, opacity: 0 });

        ScrollTrigger.create({
            trigger: '.realisations__grid',
            start: 'top 80%',
            once: true,
            onEnter: () => {
                gsap.to(cards, {
                    y: 0,
                    opacity: 1,
                    duration: 0.7,
                    ease: 'power2.out',
                    stagger: 0.12,
                });
            },
        });
    }
})();
