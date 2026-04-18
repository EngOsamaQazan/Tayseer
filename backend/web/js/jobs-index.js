/**
 * Tayseer ERP — Jobs Index page interactions
 *  - Debounced search input (auto-submits via PJAX after 350ms)
 *  - Status chip filter (radio-like, syncs hidden status field)
 *  - View toggle (list ↔ cards) persisted in localStorage
 *  - Pjax loading state on the results card
 *  - Live "Esc" to clear search
 *
 * Scoped to `.jobs-page` only — safe to load on any layout.
 */
(function () {
  'use strict';

  var ROOT_SELECTOR  = '.jobs-page';
  var FORM_ID        = 'jp-filter-form';
  var SEARCH_INPUT   = 'jp-search-input';
  var STATUS_INPUT   = 'jp-status-input';
  var TYPE_SELECT    = 'jp-type-select';
  var CITY_INPUT     = 'jp-city-input';
  var RESULTS_ID     = 'jp-results';
  var VIEW_STORAGE   = 'jp:view';

  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else { fn(); }
  }

  function init() {
    var root = document.querySelector(ROOT_SELECTOR);
    if (!root) return;

    var form        = root.querySelector('#' + FORM_ID);
    var searchInput = root.querySelector('#' + SEARCH_INPUT);
    var statusInput = root.querySelector('#' + STATUS_INPUT);
    var results     = root.querySelector('#' + RESULTS_ID);

    /* ── Search debounce + auto-submit via PJAX ────────────── */
    if (searchInput && form) {
      var wrap = searchInput.closest('.jp-search');
      var clearBtn = wrap ? wrap.querySelector('.jp-search-clear') : null;
      var debounceTimer = null;

      function syncClearVisibility() {
        if (!wrap) return;
        wrap.classList.toggle('has-value', searchInput.value.trim() !== '');
      }
      syncClearVisibility();

      searchInput.addEventListener('input', function () {
        syncClearVisibility();
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          submitFormPjax(form, results);
        }, 350);
      });

      // Esc to clear quickly
      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && searchInput.value !== '') {
          e.preventDefault();
          searchInput.value = '';
          syncClearVisibility();
          submitFormPjax(form, results);
        }
      });

      if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
          e.preventDefault();
          searchInput.value = '';
          searchInput.focus();
          syncClearVisibility();
          submitFormPjax(form, results);
        });
      }
    }

    /* ── Status chip group ────────────────────────────────── */
    var chips = $$('.jp-chip', root);
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var val = chip.getAttribute('data-value') || '';
        chips.forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
        if (statusInput) statusInput.value = val;
        if (form) submitFormPjax(form, results);
      });
    });

    /* ── Type / city auto-submit on change ────────────────── */
    var typeSelect = root.querySelector('#' + TYPE_SELECT);
    if (typeSelect && form) {
      typeSelect.addEventListener('change', function () { submitFormPjax(form, results); });
    }
    var cityInput = root.querySelector('#' + CITY_INPUT);
    if (cityInput && form) {
      var cityTimer = null;
      cityInput.addEventListener('input', function () {
        if (cityTimer) clearTimeout(cityTimer);
        cityTimer = setTimeout(function () { submitFormPjax(form, results); }, 350);
      });
    }

    /* ── View toggle (list ↔ cards) ───────────────────────── */
    var viewBtns = $$('.jp-view-toggle [data-view]', root);
    if (results && viewBtns.length) {
      var saved = null;
      try { saved = localStorage.getItem(VIEW_STORAGE); } catch (e) {}
      if (saved === 'cards' || saved === 'list') {
        applyView(results, viewBtns, saved);
      }
      viewBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var v = btn.getAttribute('data-view');
          applyView(results, viewBtns, v);
          try { localStorage.setItem(VIEW_STORAGE, v); } catch (e) {}
        });
      });
    }

    /* ── Pjax loading shimmer ─────────────────────────────── */
    if (typeof jQuery !== 'undefined' && results) {
      jQuery(document)
        .on('pjax:send', function (e) {
          var c = e.target;
          if (c && (c.id === 'crud-datatable-pjax' || results.contains(c))) {
            results.classList.add('is-loading');
          }
        })
        .on('pjax:complete pjax:error', function (e) {
          results.classList.remove('is-loading');
          // Re-apply persisted view after PJAX rebuilds the table
          var v = null;
          try { v = localStorage.getItem(VIEW_STORAGE); } catch (err) {}
          if (v === 'cards') results.setAttribute('data-view', 'cards');
        });
    }
  }

  function applyView(results, btns, view) {
    if (view === 'cards') {
      results.setAttribute('data-view', 'cards');
    } else {
      results.removeAttribute('data-view');
    }
    btns.forEach(function (b) {
      b.setAttribute('aria-pressed', b.getAttribute('data-view') === view ? 'true' : 'false');
    });
  }

  function submitFormPjax(form, results) {
    if (typeof jQuery === 'undefined' || !jQuery.pjax) {
      // Fallback: regular submit
      form.submit();
      return;
    }
    if (results) results.classList.add('is-loading');
    jQuery.pjax.submit(jQuery.Event('submit', { target: form }), '#crud-datatable-pjax', {
      timeout: 8000,
      scrollTo: false,
    });
  }

  ready(init);
  if (typeof jQuery !== 'undefined') {
    // Re-initialize after PJAX swaps the toolbar (rare but safe)
    jQuery(document).on('pjax:complete', function (e) {
      if (e.target && e.target.id === 'crud-datatable-pjax') {
        // The toolbar lives outside the pjax container, so no re-init needed.
      }
    });
  }
})();
