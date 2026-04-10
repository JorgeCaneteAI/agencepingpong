/**
 * portrait.js — Agence Ping Pong
 * Portrait parallax with SVG layer loading, reveal animation,
 * mouse parallax (desktop) and mobile idle animation.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isMobile = window.innerWidth < 768;

  var container = document.getElementById('portrait-container');
  if (!container) return;

  var layers = Array.prototype.slice.call(container.querySelectorAll('.portrait-layer[data-layer]'));

  /* ------------------------------------------------------------------ */
  /* 1. LOAD SVG LAYERS — fetch & parse with DOMParser (not innerHTML)   */
  /* ------------------------------------------------------------------ */
  // The SVGs are already inlined by PHP in the HTML, so the layers already
  // contain their SVG elements. We just need to work with what is already
  // in the DOM. If a layer is empty (e.g. decorations), we skip it.
  // For a robust implementation we also support dynamically fetching SVGs
  // if the layer div is empty.

  function fetchSvgLayer(layerEl) {
    var layerName = layerEl.getAttribute('data-layer');
    // If already has content, resolve immediately
    if (layerEl.childElementCount > 0) {
      return Promise.resolve(layerEl);
    }
    // Otherwise try to fetch from assets
    var svgPath = 'assets/svg/portrait/portrait-' + layerName + '.svg';
    return fetch(svgPath)
      .then(function (res) {
        if (!res.ok) return layerEl; // skip silently if not found
        return res.text();
      })
      .then(function (text) {
        if (typeof text !== 'string') return layerEl;
        var parser = new DOMParser();
        var doc = parser.parseFromString(text, 'image/svg+xml');
        var svgEl = doc.querySelector('svg');
        if (svgEl) {
          layerEl.appendChild(document.adoptNode(svgEl));
        }
        return layerEl;
      })
      .catch(function () {
        return layerEl;
      });
  }

  var loadPromises = layers.map(function (layer) {
    return fetchSvgLayer(layer);
  });

  Promise.all(loadPromises).then(function () {
    revealLayers();
    if (prefersReducedMotion) return;
    if (isMobile) {
      startMobileIdle();
    } else {
      startMouseParallax();
    }
  });

  /* ------------------------------------------------------------------ */
  /* 2. REVEAL ANIMATION — staggered fade + translateY                   */
  /* ------------------------------------------------------------------ */
  function revealLayers() {
    layers.forEach(function (layer) {
      layer.style.opacity = '0';
      layer.style.transform = 'translateY(20px)';
      layer.style.transition = 'none';
    });

    if (prefersReducedMotion) {
      layers.forEach(function (layer) {
        layer.style.opacity = '1';
        layer.style.transform = 'translateY(0)';
      });
      return;
    }

    layers.forEach(function (layer, i) {
      var delay = i * 100; // ms stagger
      setTimeout(function () {
        layer.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        layer.style.opacity = '1';
        layer.style.transform = 'translateY(0)';
      }, delay);
    });
  }

  /* ------------------------------------------------------------------ */
  /* 3. MOUSE PARALLAX (desktop)                                          */
  /* ------------------------------------------------------------------ */
  var mouseX = 0;
  var mouseY = 0;
  var currentX = 0;
  var currentY = 0;
  var rafId = null;
  var lerpFactor = 0.08;

  function lerp(a, b, t) {
    return a + (b - a) * t;
  }

  function onMouseMove(e) {
    // Normalise to -1 / +1
    mouseX = (e.clientX / window.innerWidth) * 2 - 1;
    mouseY = (e.clientY / window.innerHeight) * 2 - 1;
  }

  function parallaxLoop() {
    currentX = lerp(currentX, mouseX, lerpFactor);
    currentY = lerp(currentY, mouseY, lerpFactor);

    layers.forEach(function (layer) {
      var depth = parseFloat(layer.getAttribute('data-depth')) || 0.03;
      var tx = currentX * depth * 100; // px
      var ty = currentY * depth * 80;  // px
      layer.style.transform = 'translate(' + tx + 'px, ' + ty + 'px)';
    });

    rafId = requestAnimationFrame(parallaxLoop);
  }

  function startMouseParallax() {
    document.addEventListener('mousemove', onMouseMove);
    rafId = requestAnimationFrame(parallaxLoop);
  }

  /* ------------------------------------------------------------------ */
  /* 4. MOBILE IDLE — gentle floating animation via GSAP                 */
  /* ------------------------------------------------------------------ */
  function startMobileIdle() {
    if (typeof gsap === 'undefined') return;
    var idleObj = { y: 0 };
    gsap.to(idleObj, {
      y: 8,
      duration: 3,
      ease: 'sine.inOut',
      yoyo: true,
      repeat: -1,
      onUpdate: function () {
        var s = window.__portraitScale || 1;
        container.style.transform = 'translateY(' + idleObj.y + 'px) scale(' + s + ')';
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* 5. SCROLL SHRINK — portrait gets smaller as you scroll down         */
  /* ------------------------------------------------------------------ */
  function setupScrollShrink() {
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);

    var minScale = window.innerWidth < 768 ? 0.3 : 0.4;

    if (isMobile) {
      // Mobile: store scroll scale, combine with idle animation
      window.__portraitScale = 1;
      function onScroll() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        if (maxScroll <= 0) return;
        var progress = Math.min(scrollTop / maxScroll, 1);
        window.__portraitScale = 1 - progress * (1 - minScale);
      }
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    } else {
      gsap.fromTo(container, {
        scale: 1
      }, {
        scale: minScale,
        ease: 'none',
        scrollTrigger: {
          trigger: '#site-content',
          start: 'top top',
          end: 'bottom bottom',
          scrub: 0.5
        }
      });
    }
  }

  if (!prefersReducedMotion) {
    setupScrollShrink();
  }

  /* ------------------------------------------------------------------ */
  /* 6. SPEECH BUBBLE — appears near contact section                     */
  /* ------------------------------------------------------------------ */
  var speechBubble = document.getElementById('speech-bubble');
  var portraitWrapper = document.querySelector('.hero__portrait');

  function positionSpeechBubble() {
    if (!speechBubble || !portraitWrapper) return;
    var rect = portraitWrapper.getBoundingClientRect();
    var bubbleWidth = 220;
    // Center bubble on the left edge of the portrait
    speechBubble.style.left = (rect.left - bubbleWidth / 2) + 'px';
    speechBubble.style.bottom = (window.innerHeight - rect.top + 16) + 'px';
  }

  var bubbleDismissed = false;

  if (speechBubble && typeof ScrollTrigger !== 'undefined') {
    ScrollTrigger.create({
      trigger: '#pong-game',
      start: 'top 80%',
      onEnter: function () {
        if (bubbleDismissed) return;
        positionSpeechBubble();
        speechBubble.classList.add('speech-bubble--visible');
      },
      onLeaveBack: function () {
        speechBubble.classList.remove('speech-bubble--visible');
      },
      onUpdate: function () {
        if (!bubbleDismissed && speechBubble.classList.contains('speech-bubble--visible')) {
          positionSpeechBubble();
        }
      }
    });

    var closeBtn = document.getElementById('speech-bubble-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        speechBubble.classList.remove('speech-bubble--visible');
        bubbleDismissed = true;
      });
    }
  }

  /* ------------------------------------------------------------------ */
  /* Cleanup on resize crossing breakpoint                               */
  /* ------------------------------------------------------------------ */
  window.addEventListener('resize', function () {
    var nowMobile = window.innerWidth < 768;
    if (nowMobile !== isMobile) {
      isMobile = nowMobile;
      if (isMobile) {
        // Switch to mobile idle
        document.removeEventListener('mousemove', onMouseMove);
        if (rafId) cancelAnimationFrame(rafId);
        if (!prefersReducedMotion) startMobileIdle();
      } else {
        // Switch to mouse parallax
        if (typeof gsap !== 'undefined') gsap.killTweensOf(container);
        if (!prefersReducedMotion) startMouseParallax();
      }
    }
  });

})();
