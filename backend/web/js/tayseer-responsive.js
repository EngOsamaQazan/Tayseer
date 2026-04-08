/**
 * Tayseer ERP — Responsive Behavior Helpers
 * Works with tayseer-responsive.css
 */
(function () {
  'use strict';

  /* ── Filter Panel Toggle ── */
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-ty-filter-toggle]');
    if (toggle) {
      e.preventDefault();
      var targetId = toggle.getAttribute('data-ty-filter-toggle');
      var panel = document.getElementById(targetId);
      var backdrop = document.querySelector('.ty-filter-backdrop[data-ty-filter="' + targetId + '"]');
      if (panel) {
        panel.classList.toggle('ty-filter--open');
        if (backdrop) backdrop.classList.toggle('ty-filter--open');
        document.body.classList.toggle('ty-filter-body-lock');
      }
    }

    if (e.target.closest('.ty-filter-backdrop')) {
      var bd = e.target.closest('.ty-filter-backdrop');
      var filterId = bd.getAttribute('data-ty-filter');
      var filterPanel = document.getElementById(filterId);
      if (filterPanel) filterPanel.classList.remove('ty-filter--open');
      bd.classList.remove('ty-filter--open');
      document.body.classList.remove('ty-filter-body-lock');
    }
  });

  /* ── Body scroll lock when filter is open ── */
  var style = document.createElement('style');
  style.textContent = '.ty-filter-body-lock { overflow: hidden !important; }';
  document.head.appendChild(style);

  /* ── Reinitialize Select2 after PJAX (not general AJAX) ── */
  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', function () {
      setTimeout(function () {
        if (jQuery('.select2-container--open').length) return;
        jQuery('.select2-hidden-accessible').each(function () {
          var $el = jQuery(this);
          if ($el.data('select2')) {
            try { $el.select2('destroy'); } catch (ex) { /* ignore */ }
          }
        });
        if (typeof window.initSearchableSelects === 'function') {
          window.initSearchableSelects();
        }
      }, 150);
    });
  }
  /* ── Layout Debug Mode ── */
  /* Add ?debug=layout to URL to see colored borders + width panel */
  if (window.location.search.includes('debug=layout')) {
    document.documentElement.classList.add('tayseer-debug');
    window.addEventListener('load', function () {
      var els = [
        { sel: 'html', label: 'html' },
        { sel: 'body', label: 'body' },
        { sel: '.layout-wrapper', label: '.layout-wrapper' },
        { sel: '.layout-container', label: '.layout-container' },
        { sel: '.layout-page', label: '.layout-page' },
        { sel: '.layout-navbar', label: '.layout-navbar' },
        { sel: '.content-wrapper', label: '.content-wrapper' },
        { sel: '.content-wrapper > .container-xxl', label: '.container-xxl (content)' }
      ];
      var html = '<div style="position:fixed;bottom:0;left:0;right:0;z-index:99999;background:#222;color:#0f0;font:12px monospace;padding:8px;max-height:40vh;overflow:auto;direction:ltr">';
      html += '<div>Window: ' + window.innerWidth + 'x' + window.innerHeight + ' | DPR: ' + devicePixelRatio + '</div>';
      els.forEach(function (item) {
        var el = document.querySelector(item.sel);
        if (!el) { html += '<div style="color:red">' + item.label + ': NOT FOUND</div>'; return; }
        var cs = getComputedStyle(el);
        var r = el.getBoundingClientRect();
        html += '<div>' + item.label + ': '
          + 'rect.w=' + Math.round(r.width) + 'px'
          + ' | w=' + cs.width
          + ' | max-w=' + cs.maxWidth
          + ' | inline-size=' + cs.inlineSize
          + ' | max-inline-size=' + cs.maxInlineSize
          + ' | pad-L=' + cs.paddingLeft
          + ' | pad-R=' + cs.paddingRight
          + '</div>';
      });

      var sheets = document.styleSheets;
      html += '<div style="color:yellow;margin-top:6px">--- CSS files order ---</div>';
      for (var i = 0; i < sheets.length; i++) {
        var href = sheets[i].href ? sheets[i].href.split('/').pop().split('?')[0] : '(inline)';
        var isResp = href.includes('tayseer-responsive');
        html += '<div style="color:' + (isResp ? '#0ff' : '#aaa') + '">[' + i + '] ' + href + '</div>';
      }
      html += '</div>';
      var div = document.createElement('div');
      div.innerHTML = html;
      document.body.appendChild(div);
    });
  }

})();
