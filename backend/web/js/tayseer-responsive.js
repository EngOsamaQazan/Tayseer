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
  /* ── Persistent Quick Search for GridView / Tables ── */
  function injectQuickSearch(gridView) {
    if (!gridView || gridView.dataset.tySearchInjected) return;
    gridView.dataset.tySearchInjected = '1';
    var table = gridView.querySelector('.kv-grid-table, .table');
    if (!table) return;
    var panelBefore = gridView.querySelector('.kv-panel-before');
    var wrapper = document.createElement('div');
    wrapper.className = 'ty-grid-search';
    wrapper.innerHTML =
      '<div class="ty-grid-search-inner">' +
        '<i class="fa fa-search ty-grid-search-icon"></i>' +
        '<input type="text" class="ty-grid-search-input" placeholder="بحث سريع في الجدول..." aria-label="بحث سريع في النتائج المعروضة">' +
        '<span class="ty-grid-search-count" style="display:none"></span>' +
      '</div>';
    if (panelBefore) {
      panelBefore.appendChild(wrapper);
    } else {
      gridView.insertBefore(wrapper, gridView.firstChild);
    }

    var input = wrapper.querySelector('.ty-grid-search-input');
    var countEl = wrapper.querySelector('.ty-grid-search-count');
    var debounceTimer;

    input.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function() {
        var term = input.value.trim().toLowerCase();
        var rows = table.querySelectorAll('tbody tr:not(.filters)');
        var visible = 0;
        rows.forEach(function(tr) {
          if (!term) {
            tr.style.display = '';
            visible++;
          } else {
            var text = tr.textContent.toLowerCase();
            var match = text.indexOf(term) > -1;
            tr.style.display = match ? '' : 'none';
            if (match) visible++;
          }
        });
        if (term) {
          countEl.textContent = visible + ' نتيجة';
          countEl.style.display = '';
        } else {
          countEl.style.display = 'none';
        }
      }, 200);
    });
  }

  function injectAllQuickSearches() {
    document.querySelectorAll('.grid-view, [id*="crud-datatable"]').forEach(injectQuickSearch);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectAllQuickSearches);
  } else {
    injectAllQuickSearches();
  }

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', function() {
      setTimeout(injectAllQuickSearches, 300);
    });
  }

  /* ── Auto data-label for responsive table cards ── */
  function applyDataLabels(table) {
    if (!table || table.dataset.labelsApplied) return;
    var headers = table.querySelectorAll('thead th');
    if (!headers.length) return;
    var labels = [];
    headers.forEach(function(th) {
      labels.push(th.textContent.replace(/[\n\r]/g, ' ').trim());
    });
    table.querySelectorAll('tbody tr').forEach(function(tr) {
      var cells = tr.querySelectorAll('td');
      cells.forEach(function(td, i) {
        if (i < labels.length && labels[i] && !td.hasAttribute('data-label')) {
          td.setAttribute('data-label', labels[i]);
        }
      });
    });
    table.dataset.labelsApplied = '1';
  }

  function applyAllDataLabels() {
    document.querySelectorAll('.kv-grid-table, .table').forEach(applyDataLabels);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyAllDataLabels);
  } else {
    applyAllDataLabels();
  }

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', function() {
      setTimeout(applyAllDataLabels, 200);
    });
  }

  /* ── Column Visibility Toggle for GridView / Tables ── */
  function injectColumnToggle(gridView) {
    if (!gridView || gridView.dataset.tyColToggle) return;
    gridView.dataset.tyColToggle = '1';
    var table = gridView.querySelector('.kv-grid-table, .table');
    if (!table) return;
    var headers = table.querySelectorAll('thead th');
    if (headers.length < 3) return;

    var storageKey = 'ty-cols-' + (gridView.id || window.location.pathname);
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch(e) {}

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-outline-secondary ty-col-toggle-btn';
    btn.innerHTML = '<i class="fa fa-columns"></i>';
    btn.title = 'إظهار/إخفاء الأعمدة';
    btn.setAttribute('aria-label', 'إظهار وإخفاء أعمدة الجدول');

    var dropdown = document.createElement('div');
    dropdown.className = 'ty-col-toggle-dropdown';
    dropdown.style.display = 'none';

    headers.forEach(function(th, i) {
      var label = th.textContent.replace(/[\n\r]/g, ' ').trim();
      if (!label || label === '#') return;
      var id = 'tyCol_' + i;
      var isHidden = saved[i] === false;

      var item = document.createElement('label');
      item.className = 'ty-col-toggle-item';
      item.innerHTML = '<input type="checkbox" ' + (isHidden ? '' : 'checked') + ' data-col-idx="' + i + '"> ' + label;
      dropdown.appendChild(item);

      if (isHidden) toggleColumn(table, i, false);
    });

    dropdown.addEventListener('change', function(e) {
      var cb = e.target;
      if (!cb.dataset.colIdx) return;
      var idx = parseInt(cb.dataset.colIdx);
      toggleColumn(table, idx, cb.checked);
      saved[idx] = cb.checked;
      try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch(e) {}
    });

    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      var isOpen = dropdown.style.display !== 'none';
      dropdown.style.display = isOpen ? 'none' : '';
    });

    document.addEventListener('click', function(e) {
      if (!dropdown.contains(e.target) && e.target !== btn) {
        dropdown.style.display = 'none';
      }
    });

    var wrapper = document.createElement('div');
    wrapper.className = 'ty-col-toggle-wrap';
    wrapper.appendChild(btn);
    wrapper.appendChild(dropdown);

    var toolbar = gridView.querySelector('.kv-panel-before, .panel-heading .pull-right');
    if (toolbar) {
      toolbar.appendChild(wrapper);
    } else {
      gridView.insertBefore(wrapper, gridView.firstChild);
    }
  }

  function toggleColumn(table, colIdx, show) {
    var cells = table.querySelectorAll('tr > th:nth-child(' + (colIdx + 1) + '), tr > td:nth-child(' + (colIdx + 1) + ')');
    cells.forEach(function(cell) {
      cell.style.display = show ? '' : 'none';
    });
  }

  function injectAllColumnToggles() {
    document.querySelectorAll('.grid-view, [id*="crud-datatable"]').forEach(injectColumnToggle);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectAllColumnToggles);
  } else {
    injectAllColumnToggles();
  }

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', function() {
      setTimeout(injectAllColumnToggles, 300);
    });
  }

  /* ── Form Error Summary (Arabic) — ISO 9241-143 / WCAG 3.3.1 ── */
  function buildErrorSummary(form) {
    var existing = form.querySelector('.ty-error-summary');
    if (existing) existing.remove();
    var errors = form.querySelectorAll('.has-error .help-block, .is-invalid ~ .invalid-feedback, .field-error');
    if (!errors.length) return;
    var summary = document.createElement('div');
    summary.className = 'ty-error-summary alert alert-danger';
    summary.setAttribute('role', 'alert');
    summary.setAttribute('aria-live', 'assertive');
    var title = document.createElement('strong');
    title.textContent = 'يرجى تصحيح الأخطاء التالية:';
    summary.appendChild(title);
    var ul = document.createElement('ul');
    ul.className = 'mb-0 mt-1';
    errors.forEach(function(err) {
      var text = err.textContent.trim();
      if (!text) return;
      var li = document.createElement('li');
      var fieldGroup = err.closest('.form-group, .mb-3');
      var label = fieldGroup ? fieldGroup.querySelector('label') : null;
      if (label) {
        var a = document.createElement('a');
        a.href = '#' + (label.getAttribute('for') || '');
        a.textContent = text;
        a.style.color = 'inherit';
        a.style.textDecoration = 'underline';
        a.addEventListener('click', function(e) {
          e.preventDefault();
          var targetId = label.getAttribute('for');
          var target = targetId ? document.getElementById(targetId) : null;
          if (target) { target.focus(); target.scrollIntoView({behavior:'smooth', block:'center'}); }
        });
        li.appendChild(a);
      } else {
        li.textContent = text;
      }
      ul.appendChild(li);
    });
    summary.appendChild(ul);
    form.insertBefore(summary, form.firstChild);
    summary.scrollIntoView({behavior:'smooth', block:'center'});
    summary.focus();
  }

  function associateLabels(scope) {
    var labels = (scope || document).querySelectorAll('label:not([for])');
    labels.forEach(function(label) {
      var group = label.closest('.form-group, .mb-3');
      if (!group) return;
      var input = group.querySelector('input:not([type=hidden]),select,textarea');
      if (input && input.id) label.setAttribute('for', input.id);
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    associateLabels();
    document.addEventListener('submit', function(e) {
      var form = e.target;
      if (form.tagName !== 'FORM') return;
      setTimeout(function() { buildErrorSummary(form); }, 100);
    });
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('afterValidate', 'form', function() {
        buildErrorSummary(this);
      });
    }
  });

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', function() { associateLabels(); });
  }

  /* ── Auto-translate common English UI strings to Arabic ── */
  var i18nMap = {
    'Create': 'إنشاء', 'Create new': 'إنشاء جديد', 'Update': 'تعديل',
    'Delete': 'حذف', 'View': 'عرض', 'Reset Grid': 'إعادة تعيين',
    'Reset': 'إعادة تعيين', 'Search': 'بحث', 'Export': 'تصدير',
    'Save': 'حفظ', 'Cancel': 'إلغاء', 'Close': 'إغلاق',
    'Submit': 'إرسال', 'Back': 'رجوع', 'Print': 'طباعة',
    'Loading': 'جاري التحميل', 'No results found': 'لا توجد نتائج',
    'Are you sure': 'هل أنت متأكد', 'Actions': 'الإجراءات',
    'Showing': 'عرض', 'of': 'من'
  };

  function translateTitles(scope) {
    var elements = (scope || document).querySelectorAll('[title]');
    elements.forEach(function(el) {
      var title = el.getAttribute('title');
      Object.keys(i18nMap).forEach(function(en) {
        if (title.toLowerCase().indexOf(en.toLowerCase()) === 0) {
          el.setAttribute('title', title.replace(new RegExp(en, 'i'), i18nMap[en]));
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', translateTitles.bind(null, null));
  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:complete', function() { setTimeout(function(){translateTitles();}, 200); });
  }

  /* ── Toast Notification API — window.TyToast ── */
  var toastIcons = {
    success: '<i class="fa fa-check"></i>',
    error: '<i class="fa fa-times"></i>',
    warning: '<i class="fa fa-exclamation"></i>',
    info: '<i class="fa fa-info"></i>'
  };

  function getOrCreateContainer() {
    var c = document.getElementById('ty-toast-container');
    if (c) return c;
    c = document.createElement('div');
    c.id = 'ty-toast-container';
    c.className = 'ty-toast-container';
    c.setAttribute('aria-live', 'polite');
    c.setAttribute('aria-atomic', 'false');
    document.body.appendChild(c);
    return c;
  }

  window.TyToast = function(opts) {
    var type = opts.type || 'info';
    var title = opts.title || '';
    var msg = opts.message || opts.msg || '';
    var duration = opts.duration !== undefined ? opts.duration : 5000;
    var container = getOrCreateContainer();

    var toast = document.createElement('div');
    toast.className = 'ty-toast ty-toast--' + type;
    toast.setAttribute('role', 'status');
    toast.style.position = 'relative';
    toast.innerHTML =
      '<div class="ty-toast-icon">' + (toastIcons[type] || toastIcons.info) + '</div>' +
      '<div class="ty-toast-body">' +
        (title ? '<div class="ty-toast-title">' + title + '</div>' : '') +
        '<div class="ty-toast-msg">' + msg + '</div>' +
      '</div>' +
      '<button class="ty-toast-close" aria-label="إغلاق">&times;</button>' +
      (duration > 0 ? '<div class="ty-toast-progress" style="width:100%"></div>' : '');

    container.appendChild(toast);

    var progress = toast.querySelector('.ty-toast-progress');
    if (progress && duration > 0) {
      requestAnimationFrame(function() {
        progress.style.transitionDuration = duration + 'ms';
        progress.style.width = '0%';
      });
    }

    toast.querySelector('.ty-toast-close').addEventListener('click', function() { dismiss(); });

    var timer = duration > 0 ? setTimeout(dismiss, duration) : null;

    function dismiss() {
      if (timer) clearTimeout(timer);
      toast.classList.add('ty-toast-out');
      setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
    }

    return { dismiss: dismiss };
  };

  /* Bridge Yii flash messages to TyToast */
  document.addEventListener('DOMContentLoaded', function() {
    var flashAlerts = document.querySelectorAll('.alert[class*="alert-"]');
    flashAlerts.forEach(function(alert) {
      var type = 'info';
      if (alert.classList.contains('alert-success')) type = 'success';
      else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-error')) type = 'error';
      else if (alert.classList.contains('alert-warning')) type = 'warning';
      var text = alert.textContent.trim();
      if (text && !alert.closest('.ty-error-summary')) {
        TyToast({ type: type, message: text, duration: 6000 });
        alert.style.display = 'none';
      }
    });
  });

  /* ── Pjax Skeleton Loading ── */
  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('pjax:send', function(e) {
      var container = e.target;
      if (container) container.classList.add('ty-pjax-loading');
    });
    jQuery(document).on('pjax:complete pjax:error', function(e) {
      var container = e.target;
      if (container) container.classList.remove('ty-pjax-loading');
    });
  }

  /* ── Keyboard Shortcuts — ISO 9241-171 (Accessibility) ── */
  var shortcuts = [
    { keys: 'alt+h', desc: 'الرئيسية', action: function() { window.location.href = '/'; } },
    { keys: 'alt+c', desc: 'العملاء', action: function() { window.location.href = '/customers'; } },
    { keys: 'alt+q', desc: 'العقود', action: function() { window.location.href = '/contracts'; } },
    { keys: 'alt+f', desc: 'المتابعة', action: function() { window.location.href = '/followUp'; } },
    { keys: 'alt+/', desc: 'اختصارات لوحة المفاتيح', action: showShortcutsHelp },
    { keys: 'alt+s', desc: 'البحث السريع', action: function() {
      var search = document.querySelector('.searchable-select, input[name*="search"], .kv-search-filter input');
      if (search) { search.focus(); search.select && search.select(); }
    }},
    { keys: 'escape', desc: 'إغلاق النافذة المنبثقة', action: function() {
      var modal = document.querySelector('.modal.show');
      if (modal) {
        var bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
      }
    }}
  ];

  function parseKey(combo) {
    var parts = combo.toLowerCase().split('+');
    return { alt: parts.indexOf('alt') > -1, ctrl: parts.indexOf('ctrl') > -1, shift: parts.indexOf('shift') > -1, key: parts[parts.length - 1] };
  }

  document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT' || e.target.isContentEditable) {
      if (e.key === 'Escape') {
        var modal = document.querySelector('.modal.show');
        if (modal) { var bsModal = bootstrap.Modal.getInstance(modal); if (bsModal) bsModal.hide(); }
      }
      return;
    }
    for (var i = 0; i < shortcuts.length; i++) {
      var s = parseKey(shortcuts[i].keys);
      if (s.alt === e.altKey && s.ctrl === e.ctrlKey && s.shift === e.shiftKey && e.key.toLowerCase() === s.key) {
        e.preventDefault();
        shortcuts[i].action();
        return;
      }
    }
  });

  function showShortcutsHelp() {
    var existing = document.getElementById('ty-shortcuts-help');
    if (existing) { existing.remove(); return; }
    var overlay = document.createElement('div');
    overlay.id = 'ty-shortcuts-help';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:100020;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;animation:ty-toast-in .2s';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-label', 'اختصارات لوحة المفاتيح');
    var card = '<div style="background:var(--bs-card-bg,#fff);border-radius:12px;padding:24px 32px;max-width:400px;width:90%;color:var(--bs-body-color,#333);box-shadow:0 16px 48px rgba(0,0,0,.2)">';
    card += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px"><h3 style="margin:0;font-size:16px;font-weight:700">اختصارات لوحة المفاتيح</h3><button onclick="this.closest(\'#ty-shortcuts-help\').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:inherit">&times;</button></div>';
    card += '<table style="width:100%;font-size:13px;border-collapse:collapse">';
    shortcuts.forEach(function(s) {
      var keyCap = s.keys.replace('alt+', 'Alt + ').replace('ctrl+', 'Ctrl + ').replace('shift+', 'Shift + ').replace('escape', 'Esc').toUpperCase();
      card += '<tr style="border-bottom:1px solid var(--bs-border-color,#e0e0e0)"><td style="padding:8px 4px;font-weight:600">' + s.desc + '</td><td style="padding:8px 4px;text-align:left;direction:ltr"><kbd style="background:var(--bs-tertiary-bg,#f0f0f0);padding:2px 8px;border-radius:4px;font-size:11px;font-family:monospace">' + keyCap + '</kbd></td></tr>';
    });
    card += '</table></div>';
    overlay.innerHTML = card;
    overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
    overlay.querySelector('button').focus();
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
