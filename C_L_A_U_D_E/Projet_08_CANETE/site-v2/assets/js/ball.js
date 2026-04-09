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

    // Start from the logo center
    var scrollY = window.scrollY || window.pageYOffset;
    var startX = vw * 0.12;  // fallback
    var startY = 78;          // fallback
    var logoEl = document.querySelector('.site-header__logo-svg');
    if (logoEl) {
      var logoRect = logoEl.getBoundingClientRect();
      startX = logoRect.left + logoRect.width / 2;
      startY = logoRect.top + scrollY + logoRect.height / 2;
    }

    // Drop from logo, bounce right, then ping-pong between walls
    var vh = window.innerHeight;
    var margin = 60; // distance from screen edge
    var leftWall = margin;
    var rightWall = vw - margin;
    var bottomY = startY + vh - 40;

    // Viewport-relative bounce positions
    var top = vh * 0.15;       // header bottom (15vh)
    var bottom = vh - 40;      // floor
    var centerX = (leftWall + rightWall) / 2;

    // 1. Drop vertically from logo to floor
    waypoints.push({ x: startX, y: startY });
    waypoints.push({ x: startX, y: startY + bottom });

    // 2. Bouncing pattern: arc up → ceiling → diagonal down → floor → repeat
    var docH = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    var scrollRange = docH - vh;
    var numCycles = 7;
    var pointsPerCycle = 4; // arc-peak, ceiling, diagonal-floor
    var totalPoints = numCycles * pointsPerCycle;
    var dropProgress = 0.06; // 6% of scroll for the initial drop

    for (var c = 0; c < numCycles; c++) {
      var goingRight = (c % 2 === 0);
      var wallHit = goingRight ? rightWall : leftWall;
      var oppositeWall = goingRight ? leftWall : rightWall;

      // Progress range for this cycle
      var cycleStart = dropProgress + (c / numCycles) * (1 - dropProgress);
      var cycleLen = (1 - dropProgress) / numCycles;

      // Point 1: Arc peak (ball going up from floor, parabolic)
      var p1 = cycleStart + cycleLen * 0.25;
      var s1 = p1 * scrollRange;
      waypoints.push({ x: centerX, y: s1 + top + 20 });

      // Point 2: Hit ceiling at wall (top of arc, touching wall)
      var p2 = cycleStart + cycleLen * 0.4;
      var s2 = p2 * scrollRange;
      waypoints.push({ x: wallHit, y: s2 + top });

      // Point 3: Diagonal fall — midpoint (straight oblique descent)
      var p3 = cycleStart + cycleLen * 0.7;
      var s3 = p3 * scrollRange;
      waypoints.push({ x: (wallHit + oppositeWall) / 2, y: s3 + bottom * 0.6 });

      // Point 4: Hit floor at opposite side
      var p4 = cycleStart + cycleLen;
      var s4 = p4 * scrollRange;
      waypoints.push({ x: oppositeWall, y: s4 + bottom });
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
      var goingUp = dy < 0;

      if (Math.abs(dx) < 5) {
        // Vertical drop — straight
        d += ' C ' + prev.x + ' ' + (prev.y + dy * 0.4) + ', '
           + curr.x + ' ' + (prev.y + dy * 0.6) + ', '
           + curr.x + ' ' + curr.y;
      } else if (goingUp) {
        // Arc upward — parabolic: control points high
        d += ' C ' + prev.x + ' ' + (prev.y + dy * 0.8) + ', '
           + curr.x + ' ' + (curr.y - dy * 0.1) + ', '
           + curr.x + ' ' + curr.y;
      } else {
        // Diagonal down — more linear
        d += ' C ' + (prev.x + dx * 0.33) + ' ' + (prev.y + dy * 0.33) + ', '
           + (prev.x + dx * 0.66) + ' ' + (prev.y + dy * 0.66) + ', '
           + curr.x + ' ' + curr.y;
      }
    }

    return d;
  }

  /* ------------------------------------------------------------------ */
  /* COMIC IMPACT — "PING!" / "PONG!" on wall hits                      */
  /* ------------------------------------------------------------------ */
  var lastImpactTime = 0;

  function createExplosionParticles(x, y) {
    var count = 8;
    for (var i = 0; i < count; i++) {
      var particle = document.createElement('div');
      var angle = (Math.PI * 2 / count) * i;
      var size = 4 + Math.random() * 6;
      particle.style.cssText = 'position:fixed;z-index:10001;pointer-events:none;'
        + 'width:' + size + 'px;height:' + size + 'px;'
        + 'background:#FFFFFF;border-radius:50%;'
        + 'left:' + x + 'px;top:' + y + 'px;'
        + 'transform:translate(-50%,-50%);opacity:1;';
      document.body.appendChild(particle);

      var dist = 40 + Math.random() * 60;
      gsap.to(particle, {
        x: Math.cos(angle) * dist,
        y: Math.sin(angle) * dist,
        opacity: 0,
        scale: 0,
        duration: 0.5 + Math.random() * 0.3,
        ease: 'power2.out',
        onComplete: (function (p) {
          return function () { if (p.parentNode) p.parentNode.removeChild(p); };
        })(particle)
      });
    }
  }

  function createShockwave(x, y) {
    var ring = document.createElement('div');
    ring.style.cssText = 'position:fixed;z-index:10000;pointer-events:none;'
      + 'width:0;height:0;border-radius:50%;'
      + 'border:3px solid rgba(255,255,255,0.8);'
      + 'left:' + x + 'px;top:' + y + 'px;'
      + 'transform:translate(-50%,-50%);opacity:1;';
    document.body.appendChild(ring);

    gsap.to(ring, {
      width: 120,
      height: 120,
      opacity: 0,
      borderWidth: 0,
      duration: 0.5,
      ease: 'power2.out',
      onComplete: function () {
        if (ring.parentNode) ring.parentNode.removeChild(ring);
      }
    });
  }

  function createComicImpact(x, y, text) {
    var now = Date.now();
    if (now - lastImpactTime < 400) return; // debounce
    lastImpactTime = now;

    // Shockwave ring
    createShockwave(x, y);

    // Explosion particles
    createExplosionParticles(x, y);

    // Text element
    var el = document.createElement('div');
    el.textContent = text;
    el.style.cssText = 'position:fixed;z-index:10002;pointer-events:none;'
      + 'font-family:var(--font-display,"Arial Black",sans-serif);'
      + 'font-weight:800;font-size:clamp(2.5rem,6vw,5rem);'
      + 'color:#FFFFFF;'
      + 'text-transform:uppercase;letter-spacing:-0.02em;'
      + 'text-shadow:0 0 20px rgba(255,255,255,0.8),0 0 40px rgba(255,255,255,0.4),0 2px 6px rgba(0,0,0,0.6),0 4px 12px rgba(27,42,74,0.5),0 0 60px rgba(255,255,255,0.3);'
      + '-webkit-text-stroke:1px rgba(255,255,255,0.5);'
      + 'paint-order:stroke fill;'
      + 'left:' + x + 'px;top:' + y + 'px;'
      + 'transform:translate(-50%,-50%) scale(0) rotate(' + ((Math.random() - 0.5) * 20) + 'deg);'
      + 'opacity:0;white-space:nowrap;';
    document.body.appendChild(el);

    gsap.to(el, {
      scale: 1.2,
      opacity: 1,
      duration: 0.15,
      ease: 'back.out(4)',
      onComplete: function () {
        // Quick punch scale
        gsap.to(el, {
          scale: 0.9,
          duration: 0.08,
          ease: 'power2.in',
          onComplete: function () {
            gsap.to(el, {
              scale: 1.1,
              duration: 0.06,
              ease: 'power2.out',
              onComplete: function () {
                // Fade out with upward drift
                gsap.to(el, {
                  scale: 1.5,
                  opacity: 0,
                  y: -30,
                  duration: 0.35,
                  ease: 'power2.in',
                  delay: 0.1,
                  onComplete: function () {
                    if (el.parentNode) el.parentNode.removeChild(el);
                  }
                });
              }
            });
          }
        });
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* 4+5. setupTrajectoryAndBall(pathD) — draw + ball at tip             */
  /* ------------------------------------------------------------------ */
  var idleTween = null;

  function setupTrajectoryAndBall(pathD) {
    trajectoryPath.setAttribute('d', pathD);
    trajectoryPath.setAttribute('stroke', '#FFFFFF');
    trajectoryPath.setAttribute('stroke-width', '15');
    trajectoryPath.setAttribute('fill', 'none');
    trajectoryPath.setAttribute('opacity', '0.2');

    var totalLength = trajectoryPath.getTotalLength();
    trajectoryPath.style.strokeDasharray = totalLength;
    trajectoryPath.style.strokeDashoffset = totalLength;

    // Half the ball size for centering
    var halfBall = 40;

    // Place ball at start point
    var startPt = trajectoryPath.getPointAtLength(0);
    ballContainer.style.transform = 'translate(' + (startPt.x - halfBall) + 'px, ' + (startPt.y - halfBall) + 'px)';

    // Ball follows trajectory over full page scroll
    ScrollTrigger.create({
      trigger: 'body',
      start: 'top top',
      end: 'bottom bottom',
      onUpdate: function (self) {
        var progress = self.progress;
        var drawnLength = totalLength * progress;

        // Draw line up to this point
        trajectoryPath.style.strokeDashoffset = totalLength - drawnLength;

        // Position ball at the tip of the drawn line
        var pt = trajectoryPath.getPointAtLength(drawnLength);
        ballContainer.style.transform = 'translate(' + (pt.x - halfBall) + 'px, ' + (pt.y - halfBall) + 'px)';

        // Detect wall hits for comic impact
        var ballScreenX = pt.x;
        var ballScreenY = pt.y - (window.scrollY || window.pageYOffset);
        var vw = window.innerWidth;
        var wallMargin = 100;

        if (ballScreenX < wallMargin || ballScreenX > vw - wallMargin) {
          var isPing = ballScreenX < wallMargin;
          var impactX = isPing ? ballScreenX + 60 : ballScreenX - 60;
          createComicImpact(impactX, ballScreenY, isPing ? 'PING !' : 'PONG !');
        }
      }
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
          gsap.to(trajectoryPath, { stroke: '#FFFFFF', duration: 0.3 });
        },
        onEnterBack: function () {
          gsap.to(trajectoryPath, { stroke: '#ffffff', duration: 0.3 });
        },
        onLeaveBack: function () {
          gsap.to(trajectoryPath, { stroke: '#FFFFFF', duration: 0.3 });
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

    setupTrajectoryAndBall(pathD);
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
      init();
    }, 250)
  );

})();
