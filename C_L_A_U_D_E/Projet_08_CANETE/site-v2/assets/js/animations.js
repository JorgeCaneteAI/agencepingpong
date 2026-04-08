/**
 * animations.js — Agence Ping Pong
 * Scroll-driven animations: text reveal, fade-up, concept net,
 * services blocks, portfolio horizontal scroll, portfolio cards stagger.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  /* ------------------------------------------------------------------ */
  /* Wait for DOM + GSAP to be ready                                     */
  /* ------------------------------------------------------------------ */
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {

    gsap.registerPlugin(ScrollTrigger);

    // ------------------------------------------------------------------ //
    // 1. SPLIT TEXT REVEAL                                                 //
    // ------------------------------------------------------------------ //
    var splitRevealEls = document.querySelectorAll('[data-animate="split-reveal"]');

    splitRevealEls.forEach(function (el) {
      if (typeof SplitText === 'undefined') {
        // Fallback: simple fade-up if SplitText not available
        gsap.from(el, {
          y: 40,
          opacity: 0,
          duration: 0.7,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: el,
            start: 'top 80%',
            once: true
          }
        });
        return;
      }

      var split = new SplitText(el, { type: 'words' });
      gsap.from(split.words, {
        y: 40,
        opacity: 0,
        duration: 0.65,
        ease: 'power2.out',
        stagger: 0.05,
        scrollTrigger: {
          trigger: el,
          start: 'top 80%',
          once: true
        }
      });
    });

    // ------------------------------------------------------------------ //
    // 2. FADE UP                                                           //
    // ------------------------------------------------------------------ //
    var fadeUpEls = document.querySelectorAll('[data-animate="fade-up"]');

    fadeUpEls.forEach(function (el) {
      gsap.from(el, {
        y: 30,
        opacity: 0,
        duration: 0.65,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: el,
          start: 'top 85%',
          once: true
        }
      });
    });

    // ------------------------------------------------------------------ //
    // 3. CONCEPT NET                                                       //
    // ------------------------------------------------------------------ //
    var conceptSection = document.getElementById('concept');
    var conceptNet = conceptSection ? conceptSection.querySelector('.concept__net') : null;

    if (conceptSection && conceptNet) {
      ScrollTrigger.create({
        trigger: conceptSection,
        start: 'top 60%',
        once: true,
        onEnter: function () {
          conceptNet.classList.add('concept__net--visible');
        }
      });
    }

    // ------------------------------------------------------------------ //
    // 4. SERVICES BLOCKS                                                   //
    // ------------------------------------------------------------------ //
    var serviceBlocks = document.querySelectorAll('.services__block');

    serviceBlocks.forEach(function (block, i) {
      ScrollTrigger.create({
        trigger: block,
        start: 'top 75%',
        once: true,
        onEnter: function () {
          setTimeout(function () {
            block.classList.add('services__block--revealed');
          }, i * 150); // staggered
        }
      });
    });

    // ------------------------------------------------------------------ //
    // 5. PORTFOLIO HORIZONTAL SCROLL (desktop only, >= 768px)             //
    // ------------------------------------------------------------------ //
    if (window.innerWidth >= 768) {
      var horizontalScrollEls = document.querySelectorAll('[data-scroll-horizontal]');

      horizontalScrollEls.forEach(function (carousel) {
        var track = carousel.querySelector('.realisations__track');
        if (!track) return;

        var carouselWidth = carousel.offsetWidth;
        var trackWidth = track.scrollWidth;
        var scrollDistance = trackWidth - carouselWidth;

        if (scrollDistance <= 0) return;

        gsap.to(track, {
          x: -scrollDistance,
          ease: 'none',
          scrollTrigger: {
            trigger: carousel,
            start: 'top center',
            end: '+=' + scrollDistance,
            scrub: 1,
            pin: true,
            anticipatePin: 1
          }
        });
      });
    }

    // ------------------------------------------------------------------ //
    // 6. PORTFOLIO CARDS STAGGER — per category                           //
    // ------------------------------------------------------------------ //
    var categories = document.querySelectorAll('.realisations__category');

    categories.forEach(function (category) {
      var cards = category.querySelectorAll('.realisations__card');
      if (!cards.length) return;

      gsap.from(cards, {
        y: 40,
        opacity: 0,
        duration: 0.6,
        ease: 'power2.out',
        stagger: 0.15,
        scrollTrigger: {
          trigger: category,
          start: 'top 80%',
          once: true
        }
      });
    });

  }); // end ready()

})();
