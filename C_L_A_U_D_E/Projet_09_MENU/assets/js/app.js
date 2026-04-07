/* ============================================================
   MealCoach — app.js
   Vanilla JS — nav, accordion, checklist, api, sliders
   ============================================================ */

/* ── 1. Plus Menu ─────────────────────────────────────────── */

/**
 * Toggle the plus-action overlay menu.
 * @param {Event} e
 */
function togglePlusMenu(e) {
  e.preventDefault();
  const menu = document.getElementById('plusMenu');
  if (!menu) return;
  menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Close plus-menu when clicking outside of it
document.addEventListener('click', function (e) {
  const menu = document.getElementById('plusMenu');
  if (!menu || menu.style.display !== 'block') return;

  const trigger = document.querySelector('[onclick*="togglePlusMenu"]');
  if (
    !menu.contains(e.target) &&
    (!trigger || !trigger.contains(e.target))
  ) {
    menu.style.display = 'none';
  }
});

/* ── 2. Accordion ─────────────────────────────────────────── */

document.addEventListener('click', function (e) {
  const header = e.target.closest('.accordion-header');
  if (!header) return;

  const item = header.closest('.accordion-item');
  if (!item) return;

  item.classList.toggle('open');
});

/* ── 3. API helper ────────────────────────────────────────── */

/**
 * Centralised fetch wrapper.
 * Base URL comes from <meta name="api-base-url" content="/menus">.
 *
 * @param {string} endpoint
 * @param {string} [method='GET']
 * @param {Object} [data=null]
 * @returns {Promise<any>}
 */
async function api(endpoint, method = 'GET', data = null) {
  const metaTag = document.querySelector('meta[name="api-base-url"]');
  const baseUrl = metaTag ? metaTag.getAttribute('content') : '/menus';

  let url = baseUrl.replace(/\/$/, '') + '/' + endpoint.replace(/^\//, '');
  const options = { method };

  if (method === 'GET' && data) {
    const params = new URLSearchParams(data);
    url += (url.includes('?') ? '&' : '?') + params.toString();
  }

  if (method !== 'GET' && data) {
    options.headers = { 'Content-Type': 'application/json' };
    options.body = JSON.stringify(data);
  }

  const response = await fetch(url, options);
  return response.json();
}

/* ── 4. Checklist toggle ──────────────────────────────────── */

document.addEventListener('click', function (e) {
  const checkbox = e.target.closest('.checklist-checkbox');
  if (!checkbox) return;

  const item = checkbox.closest('.checklist-item');
  if (!item) return;

  item.classList.toggle('checked');

  const id       = checkbox.dataset.id;
  const endpoint = checkbox.dataset.endpoint;

  if (id && endpoint) {
    const checked = item.classList.contains('checked');
    api(endpoint, 'POST', { id, checked }).catch(function (err) {
      console.warn('[MealCoach] checklist API error:', err);
    });
  }

  updateProgress();
});

/* ── 5. Progress bar ──────────────────────────────────────── */

/**
 * Recalculate checklist progress and update DOM elements.
 */
function updateProgress() {
  const allItems     = document.querySelectorAll('.checklist-item');
  const checkedItems = document.querySelectorAll('.checklist-item.checked');

  const total   = allItems.length;
  const done    = checkedItems.length;
  const percent = total > 0 ? Math.round((done / total) * 100) : 0;

  const fill  = document.querySelector('.progress-fill');
  const label = document.querySelector('.progress-label');

  if (fill)  fill.style.width = percent + '%';
  if (label) label.textContent = done + ' / ' + total;
}

/* ── 6. Slider display ────────────────────────────────────── */

document.querySelectorAll('input[type="range"]').forEach(function (slider) {
  const valEl = document.getElementById(slider.id + '-val');
  if (!valEl) return;

  // Initial display
  valEl.textContent = slider.value;

  slider.addEventListener('input', function () {
    valEl.textContent = slider.value;
  });
});

/* ── 7. DOMContentLoaded bootstrap ───────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
  updateProgress();
});
