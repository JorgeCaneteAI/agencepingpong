/**
 * ball.js — Agence Ping Pong
 * THE MOST CRITICAL FILE.
 * Ball trajectory animation driven by ScrollTrigger + MotionPathPlugin.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  /* ------------------------------------------------------------------ */
  /* 1. SETUP                                                             */
  /* ------------------------------------------------------------------ */
  var ballContainer = document.getElementById('ball-container');
  var trajectoryPath = document.getElementById('ball-trajectory');
  var trajectoryContainer = document.getElementById('trajectory-container');

  if (!ballContainer || !trajectoryPath || !trajectoryContainer) return;

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) {
    ballContainer.style.display = 'none';
    trajectoryContainer.style.display = 'none';
    return;
  }

  // Register GSAP plugins
  gsap.registerPlugin(ScrollTrigger, MotionPathPlugin);

  var isMobile = window.innerWidth < 768;

  // Expose ball container globally (used by contact-interaction.js)
  window.__ballContainer = ballContainer;

  /* ------------------------------------------------------------------ */
  /* 2. getWaypoints() — section-based coordinate calculation            */
  /* ------------------------------------------------------------------ */
  function getWaypoints() {
    var vw = window.innerWidth;
    var waypoints = [];

    function sectionData(id) {
      var el = document.getElementById(id);
      if (!el) return null;
      var rect = el.getBoundingClientRect();
      var scrollY = window.scrollY || window.pageYOffset;
      return {
        top: rect.top + scrollY,
        height: rect.height,
        bottom: rect.bottom + scrollY
      };
    }

    var hero = sectionData('hero');
    var concept = sectionData('concept');
    var services = sectionData('services');
    var realisations = sectionData('realisations');
    var contact = sectionData('contact');

    if (isMobile) {
      // Simplified mobile waypoints — more centered, less horizontal spread
      if (hero) {
        waypoints.push({ x: vw * 0.55, y: hero.top + hero.height * 0.4 });
        waypoints.push({ x: vw * 0.45, y: hero.top + hero.height * 0.75 });
      }
      if (concept) {
        waypoints.push({ x: vw * 0.35, y: concept.top + concept.height * 0.3 });
        waypoints.push({ x: vw * 0.5,  y: concept.top + concept.height * 0.6 });
        waypoints.push({ x: vw * 0.6,  y: concept.top + concept.height * 0.85 });
      }
      if (services) {
        waypoints.push({ x: vw * 0.4,  y: services.top + services.height * 0.25 });
        waypoints.push({ x: vw * 0.55, y: services.top + services.height * 0.55 });
        waypoints.push({ x: vw * 0.4,  y: services.top + services.height * 0.8 });
      }
      if (realisations) {
        waypoints.push({ x: vw * 0.3,  y: realisations.top + realisations.height * 0.3 });
        waypoints.push({ x: vw * 0.65, y: realisations.top + realisations.height * 0.65 });
      }
      if (contact) {
        waypoints.push({ x: vw * 0.5,  y: contact.top + contact.height * 0.4 });
      }
    } else {
      // Desktop waypoints — full horizontal range
      if (hero) {
        // Start on racket area
        waypoints.push({ x: vw * 0.72, y: hero.top + hero.height * 0.4 });
        // Exit hero
        waypoints.push({ x: vw * 0.65, y: hero.top + hero.height * 0.8 });
      }
      if (concept) {
        // Arrives left
        waypoints.push({ x: vw * 0.25, y: concept.top + concept.height * 0.25 });
        // Passes centre (filet)
        waypoints.push({ x: vw * 0.5,  y: concept.top + concept.height * 0.5 });
        // Exits right
        waypoints.push({ x: vw * 0.75, y: concept.top + concept.height * 0.8 });
      }
      if (services) {
        // Zigzag between 3 service blocks
        var blockH = services.height / 3;
        waypoints.push({ x: vw * 0.25, y: services.top + blockH * 0.5 });
        waypoints.push({ x: vw * 0.75, y: services.top + blockH * 1.5 });
        waypoints.push({ x: vw * 0.25, y: services.top + blockH * 2.5 });
      }
      if (realisations) {
        // Horizontal sweep per category
        waypoints.push({ x: vw * 0.15, y: realisations.top + realisations.height * 0.2 });
        waypoints.push({ x: vw * 0.85, y: realisations.top + realisations.height * 0.35 });
        waypoints.push({ x: vw * 0.15, y: realisations.top + realisations.height * 0.55 });
        waypoints.push({ x: vw * 0.85, y: realisations.top + realisations.height * 0.75 });
      }
      if (contact) {
        waypoints.push({ x: vw * 0.5,  y: contact.top + contact.height * 0.4 });
      }
    }

    return waypoints;
  }

  /* ------------------------------------------------------------------ */
  /* 3. buildPath(waypoints) — SVG path with cubic Bézier curves         */
  /* ------------------------------------------------------------------ */
  function buildPath(waypoints) {
    if (!waypoints || waypoints.length < 2) return '';

    var d = 'M ' + waypoints[0].x + ' ' + waypoints[0].y;

    for (var i = 1; i < waypoints.length; i++) {
      var prev = waypoints[i - 1];
      var curr = waypoints[i];

      var dx = curr.x - prev.x;
      var dy = curr.y - prev.y;

      // Control points: 40% / 60% horizontal, 10% / 90% vertical offsets
      var cp1x = prev.x + dx * 0.4;
      var cp1y = prev.y + dy * 0.1;
      var cp2x = prev.x + dx * 0.6;
      var cp2y = prev.y + dy * 0.9;

      d += ' C ' + cp1x + ' ' + cp1y + ', ' + cp2x + ' ' + cp2y + ', ' + curr.x + ' ' + curr.y;
    }

    return d;
  }

  /* ------------------------------------------------------------------ */
  /* 4. setupTrajectoryDraw(pathD) — stroke-dashoffset draw animation    */
  /* ------------------------------------------------------------------ */
  function setupTrajectoryDraw(pathD) {
    trajectoryPath.setAttribute('d', pathD);
    trajectoryPath.setAttribute('stroke', 'var(--color-coral, #E84040)');
    trajectoryPath.setAttribute('stroke-width', '2');
    trajectoryPath.setAttribute('stroke-dasharray', '6 8');
    trajectoryPath.setAttribute('fill', 'none');
    trajectoryPath.setAttribute('opacity', '0.55');

    var totalLength = trajectoryPath.getTotalLength();
    trajectoryPath.style.strokeDasharray = totalLength;
    trajectoryPath.style.strokeDashoffset = totalLength;

    gsap.to(trajectoryPath, {
      strokeDashoffset: 0,
      ease: 'none',
      scrollTrigger: {
        trigger: 'body',
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* 5. setupBallMotion(pathD) — MotionPath following the trajectory     */
  /* ------------------------------------------------------------------ */
  var idleTween = null;

  function setupBallMotion(pathD) {
    // Position ball at start
    gsap.set(ballContainer, { xPercent: -50, yPercent: -50 });

    var tl = gsap.timeline({
      scrollTrigger: {
        trigger: 'body',
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1
      }
    });

    tl.to(ballContainer, {
      motionPath: {
        path: pathD,
        align: 'self',
        autoRotate: false,
        alignOrigin: [0.5, 0.5]
      },
      ease: 'none'
    });

    // Idle float when not scrolling
    idleTween = gsap.to(ballContainer, {
      y: '+=4',
      duration: 1.4,
      ease: 'sine.inOut',
      yoyo: true,
      repeat: -1,
      paused: false
    });
  }

  /* ------------------------------------------------------------------ */
  /* 6. setupTrajectoryColorSwitch() — coral on light, white on dark     */
  /* ------------------------------------------------------------------ */
  function setupTrajectoryColorSwitch() {
    var darkSections = document.querySelectorAll('.section--dark');
    darkSections.forEach(function (section) {
      ScrollTrigger.create({
        trigger: section,
        start: 'top center',
        end: 'bottom center',
        onEnter: function () {
          gsap.to(trajectoryPath, { stroke: '#ffffff', duration: 0.3 });
        },
        onLeave: function () {
          gsap.to(trajectoryPath, { stroke: 'var(--color-coral, #E84040)', duration: 0.3 });
        },
        onEnterBack: function () {
          gsap.to(trajectoryPath, { stroke: '#ffffff', duration: 0.3 });
        },
        onLeaveBack: function () {
          gsap.to(trajectoryPath, { stroke: 'var(--color-coral, #E84040)', duration: 0.3 });
        }
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* 7. setupImpacts() — shake, scale and impact star on ball-impact els */
  /* ------------------------------------------------------------------ */
  function createImpactStar(x, y) {
    var NS = 'http://www.w3.org/2000/svg';
    var svgEl = document.createElementNS(NS, 'svg');
    svgEl.setAttribute('xmlns', NS);
    svgEl.setAttribute('viewBox', '0 0 60 60');
    svgEl.setAttribute('width', '60');
    svgEl.setAttribute('height', '60');
    svgEl.style.position = 'fixed';
    svgEl.style.left = (x - 30) + 'px';
    svgEl.style.top = (y - 30) + 'px';
    svgEl.style.pointerEvents = 'none';
    svgEl.style.zIndex = '9999';
    svgEl.style.overflow = 'visible';

    // Build a simple 8-point star via polygon
    var polygon = document.createElementNS(NS, 'polygon');
    // Star points (outer r=28, inner r=12, 8 branches)
    var outerR = 28;
    var innerR = 12;
    var cx = 30;
    var cy = 30;
    var points = [];
    for (var i = 0; i < 16; i++) {
      var angle = (Math.PI / 8) * i - Math.PI / 2;
      var r = (i % 2 === 0) ? outerR : innerR;
      points.push((cx + r * Math.cos(angle)).toFixed(2) + ',' + (cy + r * Math.sin(angle)).toFixed(2));
    }
    polygon.setAttribute('points', points.join(' '));
    polygon.setAttribute('fill', 'var(--color-coral, #E84040)');
    svgEl.appendChild(polygon);
    document.body.appendChild(svgEl);

    gsap.fromTo(
      svgEl,
      { scale: 0, opacity: 1, transformOrigin: '50% 50%' },
      {
        scale: 1.5,
        opacity: 0,
        duration: 0.55,
        ease: 'power2.out',
        onComplete: function () {
          if (svgEl.parentNode) svgEl.parentNode.removeChild(svgEl);
        }
      }
    );
  }

  function setupImpacts() {
    var impactEls = document.querySelectorAll('[data-animate="ball-impact"]');
    impactEls.forEach(function (el) {
      ScrollTrigger.create({
        trigger: el,
        start: 'top 75%',
        once: true,
        onEnter: function () {
          // 1. Shake
          gsap.to(el, {
            x: -2,
            duration: 0.05,
            repeat: 5,
            yoyo: true,
            ease: 'none'
          });

          // 2. Scale-in with back.out
          gsap.fromTo(el,
            { scale: 0.95 },
            { scale: 1, duration: 0.35, ease: 'back.out(1.7)', delay: 0.1 }
          );

          // 3. Impact star at element centre
          var rect = el.getBoundingClientRect();
          var cx = rect.left + rect.width / 2;
          var cy = rect.top + rect.height / 2;
          createImpactStar(cx, cy);
        }
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* 8. resizeTrajectoryContainer() — full-document dimensions           */
  /* ------------------------------------------------------------------ */
  function resizeTrajectoryContainer() {
    var docH = Math.max(
      document.body.scrollHeight,
      document.documentElement.scrollHeight
    );
    var docW = window.innerWidth;
    trajectoryContainer.setAttribute('viewBox', '0 0 ' + docW + ' ' + docH);
    trajectoryContainer.setAttribute('width', docW);
    trajectoryContainer.setAttribute('height', docH);
    trajectoryContainer.style.position = 'absolute';
    trajectoryContainer.style.top = '0';
    trajectoryContainer.style.left = '0';
    trajectoryContainer.style.width = docW + 'px';
    trajectoryContainer.style.height = docH + 'px';
    trajectoryContainer.style.pointerEvents = 'none';
    trajectoryContainer.style.zIndex = '0';
  }

  /* ------------------------------------------------------------------ */
  /* 9. init()                                                            */
  /* ------------------------------------------------------------------ */
  function init() {
    isMobile = window.innerWidth < 768;
    resizeTrajectoryContainer();

    var waypoints = getWaypoints();
    var pathD = buildPath(waypoints);

    if (!pathD) return;

    setupTrajectoryDraw(pathD);
    setupBallMotion(pathD);
    setupTrajectoryColorSwitch();
    setupImpacts();
  }

  // Run on window load to ensure all layout is computed
  window.addEventListener('load', init);

  /* ------------------------------------------------------------------ */
  /* 10. RESIZE HANDLER — debounced, kill and re-init                    */
  /* ------------------------------------------------------------------ */
  function debounce(fn, wait) {
    var timer;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn, wait);
    };
  }

  window.addEventListener(
    'resize',
    debounce(function () {
      ScrollTrigger.getAll().forEach(function (st) { st.kill(); });
      if (idleTween) idleTween.kill();
      init();
    }, 250)
  );

})();
