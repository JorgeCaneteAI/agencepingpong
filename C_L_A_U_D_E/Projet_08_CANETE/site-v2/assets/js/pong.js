/**
 * pong.js — Agence Ping Pong
 * Classic Pong game (1972 Atari style).
 * 1 player vs AI. First to 5 wins.
 * IIFE, 'use strict'.
 */
(function () {
  'use strict';

  var canvas = document.getElementById('pong-canvas');
  var overlay = document.getElementById('pong-overlay');
  var startBtn = document.getElementById('pong-start');

  if (!canvas || !overlay || !startBtn) return;

  var ctx = canvas.getContext('2d');
  var W = 800;
  var H = 500;
  var MAX_SCORE = 5;

  /* ---- Game state ---- */
  var running = false;
  var paused = false;
  var rafId = null;

  var paddle = { w: 10, h: 80 };
  var ball = { size: 10 };

  var player = { x: 20, y: H / 2 - paddle.h / 2, score: 0 };
  var ai = { x: W - 20 - paddle.w, y: H / 2 - paddle.h / 2, score: 0, speed: 3.5 };

  var bx, by, bvx, bvy;
  var baseSpeed = 5;

  /* ---- Input ---- */
  var keys = {};

  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
      e.preventDefault();
      keys[e.key] = true;
    }
  });

  document.addEventListener('keyup', function (e) {
    keys[e.key] = false;
  });

  /* ---- Touch support ---- */
  var touchY = null;

  canvas.addEventListener('touchstart', function (e) {
    e.preventDefault();
    touchY = e.touches[0].clientY;
  }, { passive: false });

  canvas.addEventListener('touchmove', function (e) {
    e.preventDefault();
    if (touchY === null) return;
    var rect = canvas.getBoundingClientRect();
    var scaleY = H / rect.height;
    var newY = e.touches[0].clientY;
    var delta = (newY - touchY) * scaleY;
    player.y = Math.max(0, Math.min(H - paddle.h, player.y + delta));
    touchY = newY;
  }, { passive: false });

  canvas.addEventListener('touchend', function () {
    touchY = null;
  });

  /* ---- Audio (simple beeps) ---- */
  var audioCtx = null;

  function beep(freq, duration) {
    try {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = audioCtx.createOscillator();
      var gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.frequency.value = freq;
      osc.type = 'square';
      gain.gain.value = 0.08;
      osc.start();
      osc.stop(audioCtx.currentTime + duration);
    } catch (e) {
      // Audio not supported, silent
    }
  }

  /* ---- Reset ball ---- */
  function resetBall(direction) {
    bx = W / 2;
    by = H / 2;
    var angle = (Math.random() - 0.5) * Math.PI / 3;
    bvx = baseSpeed * (direction || 1) * Math.cos(angle);
    bvy = baseSpeed * Math.sin(angle);
  }

  /* ---- Reset game ---- */
  function resetGame() {
    player.y = H / 2 - paddle.h / 2;
    player.score = 0;
    ai.y = H / 2 - paddle.h / 2;
    ai.score = 0;
    resetBall(1);
  }

  /* ---- Update ---- */
  function update() {
    // Player movement
    if (keys['ArrowUp']) {
      player.y = Math.max(0, player.y - 6);
    }
    if (keys['ArrowDown']) {
      player.y = Math.min(H - paddle.h, player.y + 6);
    }

    // AI movement
    var aiCenter = ai.y + paddle.h / 2;
    var diff = by - aiCenter;
    if (Math.abs(diff) > 5) {
      ai.y += (diff > 0 ? 1 : -1) * Math.min(ai.speed, Math.abs(diff));
    }
    ai.y = Math.max(0, Math.min(H - paddle.h, ai.y));

    // Ball movement
    bx += bvx;
    by += bvy;

    // Top/bottom walls
    if (by <= 0) {
      by = 0;
      bvy = -bvy;
      beep(300, 0.05);
    }
    if (by + ball.size >= H) {
      by = H - ball.size;
      bvy = -bvy;
      beep(300, 0.05);
    }

    // Player paddle collision
    if (bvx < 0 &&
        bx <= player.x + paddle.w &&
        bx + ball.size >= player.x &&
        by + ball.size >= player.y &&
        by <= player.y + paddle.h) {
      bx = player.x + paddle.w;
      bvx = -bvx * 1.05;
      var hitPos = (by + ball.size / 2 - player.y) / paddle.h;
      bvy = (hitPos - 0.5) * baseSpeed * 2;
      beep(600, 0.08);
    }

    // AI paddle collision
    if (bvx > 0 &&
        bx + ball.size >= ai.x &&
        bx <= ai.x + paddle.w &&
        by + ball.size >= ai.y &&
        by <= ai.y + paddle.h) {
      bx = ai.x - ball.size;
      bvx = -bvx * 1.05;
      var hitPos2 = (by + ball.size / 2 - ai.y) / paddle.h;
      bvy = (hitPos2 - 0.5) * baseSpeed * 2;
      beep(600, 0.08);
    }

    // Scoring
    if (bx < -20) {
      ai.score++;
      beep(150, 0.2);
      if (ai.score >= MAX_SCORE) {
        endGame(false);
        return;
      }
      resetBall(1);
    }
    if (bx > W + 20) {
      player.score++;
      beep(150, 0.2);
      if (player.score >= MAX_SCORE) {
        endGame(true);
        return;
      }
      resetBall(-1);
    }

    // Cap ball speed
    var speed = Math.sqrt(bvx * bvx + bvy * bvy);
    if (speed > 12) {
      bvx = (bvx / speed) * 12;
      bvy = (bvy / speed) * 12;
    }
  }

  /* ---- Draw ---- */
  function draw() {
    ctx.fillStyle = '#000000';
    ctx.fillRect(0, 0, W, H);

    // Center line
    ctx.strokeStyle = '#FFFFFF';
    ctx.lineWidth = 2;
    ctx.setLineDash([8, 8]);
    ctx.beginPath();
    ctx.moveTo(W / 2, 0);
    ctx.lineTo(W / 2, H);
    ctx.stroke();
    ctx.setLineDash([]);

    // Score
    ctx.fillStyle = '#FFFFFF';
    ctx.font = '48px monospace';
    ctx.textAlign = 'center';
    ctx.fillText(player.score, W / 4, 60);
    ctx.fillText(ai.score, (W / 4) * 3, 60);

    // Paddles
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(player.x, player.y, paddle.w, paddle.h);
    ctx.fillRect(ai.x, ai.y, paddle.w, paddle.h);

    // Ball
    ctx.fillRect(bx, by, ball.size, ball.size);
  }

  /* ---- Game loop ---- */
  function loop() {
    if (!running || paused) return;
    update();
    draw();
    rafId = requestAnimationFrame(loop);
  }

  /* ---- End game ---- */
  function endGame(playerWon) {
    running = false;
    if (rafId) cancelAnimationFrame(rafId);

    draw();

    // Build overlay with result using safe DOM methods
    while (overlay.firstChild) overlay.removeChild(overlay.firstChild);

    var resultP = document.createElement('p');
    resultP.className = 'pong-overlay__result';
    resultP.textContent = playerWon ? 'Tu as gagn\u00e9 !' : 'L\'IA gagne...';
    overlay.appendChild(resultP);

    var replayBtn = document.createElement('button');
    replayBtn.className = 'pong-start-btn';
    replayBtn.textContent = '\u21BB REJOUER';
    replayBtn.addEventListener('click', function () {
      startGame();
    });
    overlay.appendChild(replayBtn);

    overlay.style.display = 'flex';
  }

  /* ---- Start game ---- */
  function startGame() {
    resetGame();
    overlay.style.display = 'none';
    running = true;
    paused = false;
    loop();
  }

  /* ---- Start button ---- */
  startBtn.addEventListener('click', function () {
    startGame();
  });

  /* ---- Pause when not visible ---- */
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!running) return;
      if (entry.isIntersecting) {
        paused = false;
        loop();
      } else {
        paused = true;
        if (rafId) cancelAnimationFrame(rafId);
      }
    });
  }, { threshold: 0.1 });

  observer.observe(canvas);

  /* ---- Initial draw (idle screen) ---- */
  resetBall(1);
  draw();

})();
