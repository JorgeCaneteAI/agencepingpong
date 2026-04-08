/* ============================================================
   MealCoach V2 — app.js
   Swipe par jour, SOS overlay, suivi, API helper
   ============================================================ */

/* ── 1. API helper ────────────────────────────────────────── */

async function api(endpoint, method = 'GET', data = null) {
  const metaTag = document.querySelector('meta[name="api-base-url"]');
  const baseUrl = metaTag ? metaTag.getAttribute('content') : '/menus';
  let url = baseUrl.replace(/\/$/, '') + '/' + endpoint.replace(/^\//, '');
  const options = { method };

  if (method === 'GET' && data) {
    url += (url.includes('?') ? '&' : '?') + new URLSearchParams(data).toString();
  }
  if (method !== 'GET' && data) {
    options.headers = { 'Content-Type': 'application/json' };
    options.body = JSON.stringify(data);
  }
  const response = await fetch(url, options);
  return response.json();
}

/* ── 2. Day Swiper ────────────────────────────────────────── */

function initDaySwiper() {
  const swiper = document.getElementById('daySwiper');
  const tabs = document.querySelectorAll('.day-tab');
  if (!swiper || !tabs.length) return;

  const todayIdx = parseInt(swiper.dataset.today || '0', 10);

  // Scroll to today on load (instant)
  requestAnimationFrame(function() {
    swiper.scrollLeft = todayIdx * swiper.offsetWidth;
    syncTabs(todayIdx);
  });

  // Sync tabs on swipe
  let scrollTimer;
  swiper.addEventListener('scroll', function() {
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function() {
      var idx = Math.round(swiper.scrollLeft / swiper.offsetWidth);
      syncTabs(idx);
    }, 50);
  });

  // Click tab → scroll to day
  tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      var day = parseInt(tab.dataset.day, 10);
      swiper.scrollTo({ left: day * swiper.offsetWidth, behavior: 'smooth' });
      syncTabs(day);
    });
  });

  function syncTabs(idx) {
    tabs.forEach(function(t, i) {
      t.classList.toggle('active', i === idx);
    });
    // Scroll active tab into view
    if (tabs[idx]) {
      tabs[idx].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
  }
}

/* ── 2b. J-2 Alert Response ───────────────────────────────── */

function j2Reponse(reponse) {
  var alert = document.getElementById('alertJ2');
  if (!alert) return;
  if (reponse === 'oui') {
    var actions = alert.querySelector('.j2-actions');
    var question = alert.querySelector('.j2-question');
    if (actions) actions.remove();
    if (question) question.remove();
    var msg = document.createElement('div');
    msg.className = 'j2-confirmed';
    msg.textContent = '✓ Super, tu es pret !';
    alert.querySelector('.alert-text').appendChild(msg);
    alert.classList.add('alert-card--dismissed');
  }
}

/* ── 2c. Meal Card Expand/Collapse ────────────────────────── */

function toggleMealDetail(card, event) {
  // Don't toggle if clicking on a button or link inside the card
  if (event && event.target.closest('.action-btn, .done-check, button, a')) return;
  card.classList.toggle('meal-card--expanded');
}

/* ── 3. Meal Actions (C'est fait / Saute) ─────────────────── */

async function marquerRepas(repasId, typeRepas, statut, btn) {
  var card = btn.closest('.meal-card');
  try {
    var res = await api('suivi', 'POST', {
      action: 'maj_repas',
      type_repas: typeRepas,
      statut: statut,
      date: new Date().toISOString().slice(0, 10)
    });
    if (res.ok && card) {
      if (statut === 'mange') {
        card.classList.add('meal-card--done');
        // Replace action with done check using safe DOM methods
        var actionEl = card.querySelector('.meal-card-right');
        if (actionEl) {
          actionEl.textContent = '';
          var checkDiv = document.createElement('div');
          checkDiv.className = 'done-check';
          checkDiv.textContent = '\u2713';
          actionEl.appendChild(checkDiv);
        }
      } else {
        card.style.opacity = '0.3';
        var actionEl = card.querySelector('.meal-card-right');
        if (actionEl) {
          actionEl.textContent = '';
          var badge = document.createElement('span');
          badge.className = 'badge badge-neutral';
          badge.textContent = 'Saute';
          actionEl.appendChild(badge);
        }
      }
      updateProgressRing();
    }
  } catch (err) {
    console.warn('[MealCoach] marquerRepas error:', err);
  }
}

/* ── 4. Progress Ring ─────────────────────────────────────── */

function updateProgressRing() {
  var doneCount = 0;
  var totalCount = 0;

  // Count meals: in day-panel (semaine) or page-wide (dashboard)
  var activePanel = document.querySelector('.day-panel[data-day="' + getCurrentDayIdx() + '"]');
  var scope = activePanel || document;
  doneCount = scope.querySelectorAll('.meal-card--done').length;
  totalCount = scope.querySelectorAll('.meal-card').length;

  var bigEl = document.querySelector('.progress-center .big');
  var smallEl = document.querySelector('.progress-center .small');
  var ringFill = document.querySelector('.ring-fill');

  if (bigEl) bigEl.textContent = doneCount;
  if (smallEl) smallEl.textContent = 'sur ' + totalCount;

  if (ringFill) {
    var circumference = parseFloat(ringFill.getAttribute('stroke-dasharray')) || 163;
    var progress = totalCount > 0 ? doneCount / totalCount : 0;
    ringFill.setAttribute('stroke-dashoffset', circumference * (1 - progress));
  }
}

function getCurrentDayIdx() {
  var swiper = document.getElementById('daySwiper');
  if (!swiper) return 0;
  return Math.round(swiper.scrollLeft / swiper.offsetWidth);
}

/* ── 5. SOS Overlay ───────────────────────────────────────── */

function openSOS() {
  var overlay = document.getElementById('sosOverlay');
  if (overlay) {
    overlay.classList.add('visible');
    showSOSStep('start');
  }
}

function closeSOS() {
  var overlay = document.getElementById('sosOverlay');
  if (overlay) overlay.classList.remove('visible');
}

function showSOSStep(stepName) {
  var steps = document.querySelectorAll('.sos-step');
  steps.forEach(function(s) { s.classList.remove('active'); });
  var target = document.querySelector('.sos-step[data-step="' + stepName + '"]');
  if (target) target.classList.add('active');
}

function startGrounding() {
  showSOSStep('g5');
}

function nextGroundingStep(currentStep) {
  var steps = ['g5', 'g4', 'g3', 'g2', 'g1', 'bilan'];
  var idx = steps.indexOf(currentStep);
  if (idx >= 0 && idx < steps.length - 1) {
    showSOSStep(steps[idx + 1]);
  }
}

async function sosResult(result) {
  try {
    await api('suivi', 'POST', {
      action: 'sos_event',
      result: result,
      date: new Date().toISOString().slice(0, 10),
      time: new Date().toTimeString().slice(0, 5)
    });
  } catch (e) { /* silently fail */ }
  showSOSStep(result === 'resiste' ? 'bravo' : 'craque-ok');
}

/* ── 6. Plus Menu ─────────────────────────────────────────── */

function togglePlusMenu(e) {
  if (e) e.preventDefault();
  var menu = document.getElementById('plusMenu');
  var overlay = document.getElementById('plusMenuOverlay');
  if (!menu) return;

  var isOpen = menu.style.display === 'block';
  if (isOpen) {
    menu.style.display = 'none';
    if (overlay) overlay.classList.remove('visible');
  } else {
    menu.style.display = 'block';
    if (overlay) overlay.classList.add('visible');
  }
}

/* ── 7. Slider Display ────────────────────────────────────── */

function initSliders() {
  document.querySelectorAll('input[type="range"]').forEach(function(slider) {
    var valEl = document.getElementById(slider.id + '-val');
    if (!valEl) return;
    valEl.textContent = slider.value;
    slider.addEventListener('input', function() {
      valEl.textContent = slider.value;
    });
  });
}

/* ── 8. Checklist (courses — j'ai / j'ai pas) ────────────── */

function initChecklists() {
  document.addEventListener('change', function(e) {
    var input = e.target;
    if (!input.matches('.check-jai input, .check-jaipas input')) return;

    var row = input.closest('.checklist-row');
    if (!row) return;

    // Mutual exclusion: "j'ai" unchecks "j'ai pas" and vice versa
    if (input.closest('.check-jai') && input.checked) {
      var other = row.querySelector('.check-jaipas input');
      if (other) other.checked = false;
    }
    if (input.closest('.check-jaipas') && input.checked) {
      var other = row.querySelector('.check-jai input');
      if (other) other.checked = false;
    }
  });
}

/* ── 9. Bootstrap ─────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function() {
  initDaySwiper();
  initSliders();
  initChecklists();
  updateProgressRing();

  // Close plus menu on outside click
  document.addEventListener('click', function(e) {
    var menu = document.getElementById('plusMenu');
    var overlay = document.getElementById('plusMenuOverlay');
    if (!menu || menu.style.display !== 'block') return;
    var trigger = document.querySelector('[onclick*="togglePlusMenu"]');
    if (!menu.contains(e.target) && (!trigger || !trigger.contains(e.target)) && e.target !== overlay) {
      menu.style.display = 'none';
      if (overlay) overlay.classList.remove('visible');
    }
  });

  // Close SOS on overlay click
  var sosOverlay = document.getElementById('sosOverlay');
  if (sosOverlay) {
    sosOverlay.addEventListener('click', function(e) {
      if (e.target === sosOverlay) closeSOS();
    });
  }
});
