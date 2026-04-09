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
      var isHero = el.classList.contains('hero__title');

      if (isHero) {
        // Split into individual letter spans using safe DOM methods
        var textParts = [];
        el.childNodes.forEach(function (node) {
          if (node.nodeType === 3) { // text
            textParts.push({ type: 'text', value: node.textContent });
          } else if (node.nodeName === 'BR') {
            textParts.push({ type: 'br' });
          } else {
            textParts.push({ type: 'text', value: node.textContent });
          }
        });

        // Clear and rebuild with spans, grouped by word
        while (el.firstChild) el.removeChild(el.firstChild);

        textParts.forEach(function (part) {
          if (part.type === 'br') {
            el.appendChild(document.createElement('br'));
          } else {
            // Split text into words to prevent mid-word line breaks
            var words = part.value.split(/(\s+)/);
            words.forEach(function (word) {
              if (!word) return;
              if (/^\s+$/.test(word)) {
                el.appendChild(document.createTextNode(word));
                return;
              }
              var wordWrap = document.createElement('span');
              wordWrap.style.display = 'inline-block';
              wordWrap.style.whiteSpace = 'nowrap';
              for (var c = 0; c < word.length; c++) {
                var span = document.createElement('span');
                span.className = 'hero-letter';
                span.style.display = 'inline-block';
                span.style.opacity = '0';
                span.textContent = word[c];
                wordWrap.appendChild(span);
              }
              el.appendChild(wordWrap);
            });
          }
        });

        var letters = el.querySelectorAll('.hero-letter');

        // Initial state: huge, rotated, invisible
        gsap.set(letters, {
          opacity: 0,
          scale: 3,
          rotation: function () { return (Math.random() - 0.5) * 40; },
          y: function () { return (Math.random() - 0.5) * 80; },
          filter: 'blur(12px)'
        });

        var tl = gsap.timeline({ delay: 0.2 });

        // Letters slam in one by one
        tl.to(letters, {
          opacity: 1,
          scale: 1,
          rotation: 0,
          y: 0,
          filter: 'blur(0px)',
          duration: 0.45,
          ease: 'back.out(2.5)',
          stagger: 0.06
        });

        // Micro-bounce on the whole title after all letters land
        tl.fromTo(el,
          { scale: 1 },
          { scale: 1.03, duration: 0.12, ease: 'power2.in', yoyo: true, repeat: 1 },
          '-=0.15'
        );

        // Show ball after title — pops out from the "i"
        var ballEl = document.getElementById('ball-container');
        if (ballEl) {
          gsap.set(ballEl, { opacity: 0, scale: 0 });
          tl.to(ballEl, {
            opacity: 1,
            scale: 1,
            duration: 0.5,
            ease: 'elastic.out(1, 0.4)',
          }, '-=0.2');
        }

        // Scroll animation: letters disperse as you scroll past hero
        var lettersArray = Array.from(letters);
        lettersArray.forEach(function (letter, i) {
          var randY = -60 - Math.random() * 120;
          var randX = (Math.random() - 0.5) * 100;
          var randRot = (Math.random() - 0.5) * 30;
          var randScale = 0.6 + Math.random() * 0.3;

          gsap.fromTo(letter, {
            y: 0,
            x: 0,
            rotation: 0,
            scale: 1,
            opacity: 1,
            filter: 'blur(0px)'
          }, {
            y: randY,
            x: randX,
            rotation: randRot,
            scale: randScale,
            opacity: 0,
            filter: 'blur(4px)',
            ease: 'none',
            scrollTrigger: {
              trigger: '#hero',
              start: '60% top',
              end: 'bottom top',
              scrub: 1
            }
          });
        });

      } else {
        // Other split-reveal elements: word-based fade up
        var words = el.textContent.split(/\s+/);
        while (el.firstChild) el.removeChild(el.firstChild);

        words.forEach(function (w, i) {
          if (i > 0) el.appendChild(document.createTextNode(' '));
          var span = document.createElement('span');
          span.style.display = 'inline-block';
          span.style.opacity = '0';
          span.style.transform = 'translateY(30px)';
          span.textContent = w;
          el.appendChild(span);
        });

        var wordSpans = el.querySelectorAll('span');
        gsap.to(wordSpans, {
          opacity: 1,
          y: 0,
          duration: 0.6,
          ease: 'power2.out',
          stagger: 0.05,
          scrollTrigger: {
            trigger: el,
            start: 'top 80%',
            once: true
          }
        });
      }
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
