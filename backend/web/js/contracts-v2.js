/**
 * Contracts V2 — Modern interactions
 * Filter drawer, chips, quick search, actions menu, copy ID
 */
(function ($) {
  'use strict';

  /* ========== FILTER PANEL / DRAWER ========== */
  var $filterWrap    = $('#ctFilterWrap'),
      $filterPanel   = $('#ctFilterPanel'),
      $backdrop      = $('#ctFilterBackdrop'),
      $toggleBtn     = $('#ctFilterToggle'),
      $drawerClose   = $('#ctDrawerClose');

  // Desktop: toggle collapse
  $(document).on('click', '.ct-filter-hdr', function () {
    if (window.innerWidth > 767) {
      $filterPanel.toggleClass('collapsed');
      localStorage.setItem('ct_filter_collapsed', $filterPanel.hasClass('collapsed') ? '1' : '0');
    }
  });

  // Restore collapse state on desktop
  if (window.innerWidth > 767 && localStorage.getItem('ct_filter_collapsed') === '1') {
    $filterPanel.addClass('collapsed');
  }

  // Mobile: open drawer
  $toggleBtn.on('click', function () {
    $filterWrap.addClass('open');
    $('body').css('overflow', 'hidden');
  });

  // Mobile: close drawer
  function closeDrawer() {
    $filterWrap.removeClass('open');
    $('body').css('overflow', '');
  }
  $backdrop.on('click', closeDrawer);
  $drawerClose.on('click', closeDrawer);
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      closeDrawer();
      closeActMenu();
    }
  });

  /* ========== FILTER CHIPS ========== */
  var chipLabels = {
    'ContractsSearch[id]':            'رقم العقد',
    'ContractsSearch[customer_name]': 'العميل',
    'ContractsSearch[status]':        'الحالة',
    'ContractsSearch[from_date]':     'من تاريخ',
    'ContractsSearch[to_date]':       'إلى تاريخ',
    'ContractsSearch[seller_id]':     'البائع',
    'ContractsSearch[followed_by]':   'المتابع',
    'ContractsSearch[phone_number]':  'الهاتف',
    'ContractsSearch[job_Type]':      'نوع الوظيفة'
  };
  var statusMap = {
    'active': 'نشط', 'pending': 'معلّق', 'legal_department': 'قانوني',
    'judiciary': 'قضاء', 'settlement': 'تسوية', 'finished': 'منتهي',
    'canceled': 'ملغي', 'refused': 'مرفوض'
  };

  function buildChips() {
    var $container = $('#ctChips');
    $container.empty();
    var params = new URLSearchParams(window.location.search);
    var hasChips = false;

    params.forEach(function (value, key) {
      if (!value || key === 'r' || key === 'page' || key === 'sort' || key === 'per-page') return;
      var label = chipLabels[key];
      if (!label) return;

      var displayVal = value;
      if (key === 'ContractsSearch[status]') displayVal = statusMap[value] || value;

      // Try to get select2 display text
      var $field = $('[name="' + key + '"]');
      if ($field.length && $field.is('select') && $field.find('option:selected').text()) {
        var selText = $field.find('option:selected').text().trim();
        if (selText && selText !== '' && !selText.startsWith('--')) displayVal = selText;
      }

      hasChips = true;
      var $chip = $('<span class="ct-chip">' +
        '<span class="ct-chip-label">' + label + ':</span> ' + displayVal +
        ' <button class="ct-chip-remove" data-param="' + key + '" title="إزالة" aria-label="إزالة فلتر ' + label + '">&times;</button>' +
        '</span>');
      $container.append($chip);
    });

    if (hasChips) {
      $container.append(
        '<span class="ct-chip ct-chip-clear"><button class="ct-chip-remove" data-param="__all" title="مسح الكل">مسح الكل &times;</button></span>'
      );
    }
  }

  $(document).on('click', '.ct-chip-remove', function () {
    var param = $(this).data('param');
    if (param === '__all') {
      // Go to index without params
      window.location.href = window.location.pathname;
      return;
    }
    var params = new URLSearchParams(window.location.search);
    params.delete(param);
    window.location.href = window.location.pathname + '?' + params.toString();
  });

  buildChips();

  /* ========== QUICK SEARCH ========== */
  var quickTimer = null;
  $('#ctQuickSearch').on('input', function () {
    var query = $(this).val().toLowerCase().trim();
    clearTimeout(quickTimer);
    quickTimer = setTimeout(function () {
      var $rows = $('.ct-table tbody tr');
      if (!query) {
        $rows.show();
        return;
      }
      $rows.each(function () {
        var text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(query) > -1);
      });
    }, 200);
  });

  /* ========== ACTIONS MENU (inline dropdown with flip-up) ========== */
  function closeActMenu() {
    $('.ct-act-wrap.open').each(function () {
      $(this).removeClass('open');
      $(this).find('.ct-act-menu').removeClass('flip-up');
    });
  }

  $(document).on('click', '.ct-act-trigger', function (e) {
    e.stopImmediatePropagation();
    var $wrap = $(this).closest('.ct-act-wrap');
    var wasOpen = $wrap.hasClass('open');

    closeActMenu();
    if (wasOpen) return;

    var $menu = $wrap.find('.ct-act-menu');
    $wrap.addClass('open');

    var triggerRect = this.getBoundingClientRect();
    var menuH = $menu.outerHeight();
    var spaceBelow = window.innerHeight - triggerRect.bottom - 8;
    if (spaceBelow < menuH && triggerRect.top > menuH) {
      $menu.addClass('flip-up');
    }
  });

  $(document).on('click', function (e) {
    if ($(e.target).closest('.ct-act-wrap').length) return;
    closeActMenu();
  });

  $(document).on('click', '.ct-act-menu a', function () {
    if ($(this).hasClass('yeas-cancel') || $(this).hasClass('yeas-finish')) return;
    closeActMenu();
  });

  /* ========== CONTRACT ID — now a direct link, no copy ========== */

  function showCopyTip(text) {
    var $tip = $('<div class="ct-copied-tip">' + text + '</div>');
    $('body').append($tip);
    setTimeout(function () { $tip.fadeOut(300, function () { $tip.remove(); }); }, 1500);
  }

  /* ========== FOLLOW-UP USER CHANGE ========== */
  $(document).on('change', '.ct-follow-select', function () {
    var cid = $(this).data('contract-id'),
        uid = $(this).val();
    if (cid && uid) {
      $.post('/contracts/contracts/chang-follow-up', {
        contract_id: cid,
        user_id: uid,
        _csrf: yii.getCsrfToken()
      }).done(function () {
        showCopyTip('تم تغيير المتابع');
      });
    }
  });

  /* ========== FINISH / CANCEL MODALS ========== */
  $(document).on('click', '.yeas-finish', function (e) {
    e.preventDefault();
    closeActMenu();
    var $btn = document.getElementById('finishContractBtn');
    var el   = document.getElementById('finishContractModal');
    if ($btn) $btn.setAttribute('href', $(this).data('url'));
    if (el) { el.style.display = ''; }
    if (el && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(el).show();
  });
  $(document).on('click', '.yeas-cancel', function (e) {
    e.preventDefault();
    closeActMenu();
    var $btn = document.getElementById('cancelContractBtn');
    var el   = document.getElementById('cancelContractModal');
    if ($btn) $btn.setAttribute('href', $(this).data('url'));
    if (el) { el.style.display = ''; }
    if (el && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(el).show();
  });

  /* ========== KEYBOARD NAVIGATION ========== */
  $(document).on('keydown', '.ct-act-trigger', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).trigger('click');
    }
  });

  /* ========== CSV EXPORT ========== */
  $(document).on('click', '#ctExportBtn', function () {
    var params = window.location.search;
    var exportUrl = window.location.pathname + (params ? params + '&' : '?') + 'export=csv';
    window.location.href = exportUrl;
  });

  /* ========== RESPONSIVE HANDLER ========== */
  var lastWidth = window.innerWidth;
  $(window).on('resize', function () {
    var w = window.innerWidth;
    if ((lastWidth > 767 && w <= 767) || (lastWidth <= 767 && w > 767)) {
      closeDrawer();
    }
    lastWidth = w;
  });

})(jQuery);
