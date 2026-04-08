/**
 * contact-interaction.js — Agence Ping Pong
 * Contact section interaction: cursor raquette, confetti smash, form reveal.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var contactSection = document.getElementById('contact');
  var contactGame = document.getElementById('contact-game');
  var contactFormWrapper = document.getElementById('contact-form-wrapper');
  var confettiContainer = document.getElementById('confetti-container');
  var tapBtn = document.getElementById('contact-tap-btn');

  if (!contactSection || !contactGame || !contactFormWrapper) return;

  /* ------------------------------------------------------------------ */
  /* 1. CURSOR RAQUETTE (desktop)                                        */
  /* ------------------------------------------------------------------ */
  var isMobile = window.innerWidth < 768;

  if (!isMobile) {
    contactSection.addEventListener('mouseenter', function () {
      contactSection.style.cursor = 'url("assets/svg/game/racket-cursor.svg") 16 16, crosshair';
    });

    contactSection.addEventListener('mouseleave', function () {
      contactSection.style.cursor = '';
    });
  }

  /* ------------------------------------------------------------------ */
  /* 2. CONFETTI CREATION                                                 */
  /* ------------------------------------------------------------------ */
  var PALETTE = [
    'var(--color-coral, #E84040)',
    'var(--color-turquoise, #40C9A2)',
    'var(--color-navy, #1a1a2e)',
    '#ffffff'
  ];

  function randomBetween(min, max) {
    return Math.random() * (max - min) + min;
  }

  function createConfetti() {
    if (!confettiContainer) return;

    var count = isMobile ? 18 : 40;

    // Get origin: centre of contact game element
    var originEl = contactGame;
    var rect = originEl.getBoundingClientRect();
    var scrollY = window.scrollY || window.pageYOffset;
    var originX = rect.left + rect.width / 2;
    var originY = rect.top + scrollY + rect.height / 2;

    for (var i = 0; i < count; i++) {
      (function () {
        var size = randomBetween(6, 12);
        var isCircle = Math.random() > 0.5;
        var color = PALETTE[Math.floor(Math.random() * PALETTE.length)];

        var piece = document.createElement('div');
        piece.style.position = 'absolute';
        piece.style.left = originX + 'px';
        piece.style.top = originY + 'px';
        piece.style.width = size + 'px';
        piece.style.height = size + 'px';
        piece.style.backgroundColor = color;
        piece.style.borderRadius = isCircle ? '50%' : '0';
        piece.style.pointerEvents = 'none';
        piece.style.zIndex = '9998';
        piece.style.transform = 'translate(-50%, -50%)';
        confettiContainer.appendChild(piece);

        // Random spread direction
        var angle = randomBetween(0, 360);
        var distance = randomBetween(80, 240);
        var rad = (angle * Math.PI) / 180;
        var tx = Math.cos(rad) * distance;
        var ty = Math.sin(rad) * distance;

        if (prefersReducedMotion) {
          piece.style.opacity = '0';
          return;
        }

        gsap.to(piece, {
          x: tx,
          y: ty + randomBetween(80, 150), // gravity pull
          rotation: randomBetween(-360, 360),
          opacity: 0,
          duration: randomBetween(0.7, 1.4),
          ease: 'power2.out',
          delay: randomBetween(0, 0.2),
          onComplete: function () {
            if (piece.parentNode) piece.parentNode.removeChild(piece);
          }
        });
      })();
    }
  }

  /* ------------------------------------------------------------------ */
  /* 3. triggerSmash()                                                    */
  /* ------------------------------------------------------------------ */
  var smashTriggered = false;

  function triggerSmash() {
    if (smashTriggered) return;
    smashTriggered = true;

    // Hide ball
    var ballContainer = window.__ballContainer;
    if (ballContainer && !prefersReducedMotion) {
      gsap.to(ballContainer, {
        opacity: 0,
        scale: 0,
        duration: 0.35,
        ease: 'power2.in',
        transformOrigin: '50% 50%'
      });
    }

    // Create confetti
    createConfetti();

    // Fade out contactGame
    if (!prefersReducedMotion) {
      gsap.to(contactGame, {
        opacity: 0,
        scale: 0.9,
        duration: 0.4,
        ease: 'power2.in',
        onComplete: function () {
          contactGame.hidden = true;
          revealForm();
        }
      });
    } else {
      contactGame.hidden = true;
      revealForm();
    }
  }

  function revealForm() {
    contactFormWrapper.hidden = false;
    contactFormWrapper.setAttribute('aria-hidden', 'false');

    if (!prefersReducedMotion) {
      gsap.fromTo(
        contactFormWrapper,
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.55, ease: 'power2.out' }
      );
    }
  }

  /* ------------------------------------------------------------------ */
  /* 4. EVENT LISTENERS                                                   */
  /* ------------------------------------------------------------------ */
  if (!isMobile) {
    // Desktop: click on the game container
    contactGame.addEventListener('click', function () {
      triggerSmash();
    });
  }

  // Mobile: click on tap button (stopPropagation to avoid double-trigger)
  if (tapBtn) {
    tapBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      triggerSmash();
    });
  }

})();
