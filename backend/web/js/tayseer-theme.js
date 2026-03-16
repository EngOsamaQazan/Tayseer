/**
 * Tayseer ERP — Theme System
 *
 * Manages dark/light mode and color palettes.
 * Persists to localStorage (instant) + server DB (cross-device).
 */
var TayseerTheme = (function () {
  'use strict';

  var LS_MODE  = 'tayseer_theme_mode';
  var LS_COLOR = 'tayseer_theme_color';
  var DEFAULT_MODE  = 'light';
  var DEFAULT_COLOR = 'burgundy';
  var VALID_MODES   = ['light', 'dark'];
  var VALID_COLORS  = ['burgundy', 'ocean', 'forest', 'royal', 'sunset', 'slate'];

  var _csrfToken = null;

  function getCsrf() {
    if (_csrfToken) return _csrfToken;
    var meta = document.querySelector('meta[name="csrf-token"]');
    _csrfToken = meta ? meta.getAttribute('content') : '';
    return _csrfToken;
  }

  function getMode() {
    return localStorage.getItem(LS_MODE) || DEFAULT_MODE;
  }

  function getColor() {
    return localStorage.getItem(LS_COLOR) || DEFAULT_COLOR;
  }

  function applyMode(mode) {
    if (VALID_MODES.indexOf(mode) === -1) mode = DEFAULT_MODE;
    document.documentElement.setAttribute('data-bs-theme', mode);
    localStorage.setItem(LS_MODE, mode);
    updateModeUI(mode);
  }

  function applyColor(color) {
    if (VALID_COLORS.indexOf(color) === -1) color = DEFAULT_COLOR;
    document.documentElement.setAttribute('data-theme-color', color);
    localStorage.setItem(LS_COLOR, color);
    updateColorUI(color);
  }

  function apply(mode, color) {
    applyMode(mode);
    applyColor(color);
  }

  function save(mode, color) {
    var body = {};
    if (mode)  body.mode  = mode;
    if (color) body.color = color;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/theme/save', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-Token', getCsrf());
    xhr.send(JSON.stringify(body));
  }

  function toggleMode() {
    var current = getMode();
    var next = current === 'dark' ? 'light' : 'dark';
    applyMode(next);
    save(next, null);
  }

  function setColor(color) {
    applyColor(color);
    save(null, color);
  }

  function updateModeUI(mode) {
    var lightBtn = document.getElementById('themeMode-light');
    var darkBtn  = document.getElementById('themeMode-dark');
    var icon     = document.getElementById('themeToggleIcon');

    if (lightBtn) lightBtn.classList.toggle('active', mode === 'light');
    if (darkBtn)  darkBtn.classList.toggle('active', mode === 'dark');

    if (icon) {
      if (mode === 'dark') {
        icon.className = 'fa-solid fa-moon fa-lg';
      } else {
        icon.className = 'fa-solid fa-sun fa-lg';
      }
    }
  }

  function updateColorUI(color) {
    var swatches = document.querySelectorAll('.theme-palette-swatch');
    for (var i = 0; i < swatches.length; i++) {
      var s = swatches[i];
      var isActive = s.getAttribute('data-color') === color;
      s.classList.toggle('active', isActive);
    }
  }

  function init() {
    var mode  = getMode();
    var color = getColor();
    apply(mode, color);

    document.addEventListener('DOMContentLoaded', function () {
      updateModeUI(mode);
      updateColorUI(color);
      bindEvents();
    });
  }

  function bindEvents() {
    var lightBtn = document.getElementById('themeMode-light');
    var darkBtn  = document.getElementById('themeMode-dark');
    var toggleBtn = document.getElementById('themeToggleBtn');

    if (lightBtn) {
      lightBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        applyMode('light');
        save('light', null);
      });
    }
    if (darkBtn) {
      darkBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        applyMode('dark');
        save('dark', null);
      });
    }
    if (toggleBtn) {
      toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        toggleMode();
      });
    }

    var swatches = document.querySelectorAll('.theme-palette-swatch[data-color]');
    for (var i = 0; i < swatches.length; i++) {
      swatches[i].addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var c = this.getAttribute('data-color');
        if (c) setColor(c);
      });
    }
  }

  return {
    init: init,
    apply: apply,
    applyMode: applyMode,
    applyColor: applyColor,
    toggleMode: toggleMode,
    setColor: setColor,
    getMode: getMode,
    getColor: getColor,
    save: save
  };
})();

TayseerTheme.init();
