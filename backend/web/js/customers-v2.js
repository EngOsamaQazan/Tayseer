/**
 * Customers V2 — Modern interactions
 * Filter drawer, chips, quick search, actions menu (portal), delete, copy ID
 */
(function ($) {
  'use strict';

  /* ========== FILTER PANEL / DRAWER ========== */
  var $filterWrap  = $('#ctFilterWrap'),
      $filterPanel = $('#ctFilterPanel'),
      $backdrop    = $('#ctFilterBackdrop'),
      $toggleBtn   = $('#ctFilterToggle'),
      $drawerClose = $('#ctDrawerClose');

  $(document).on('click', '.ct-filter-hdr', function () {
    if (window.innerWidth > 767) {
      $filterPanel.toggleClass('collapsed');
      localStorage.setItem('cust_filter_collapsed', $filterPanel.hasClass('collapsed') ? '1' : '0');
    }
  });

  if (window.innerWidth > 767 && localStorage.getItem('cust_filter_collapsed') === '1') {
    $filterPanel.addClass('collapsed');
  }

  $toggleBtn.on('click', function () {
    $filterWrap.addClass('open');
    $('body').css('overflow', 'hidden');
  });

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
    'CustomersSearch[q]':             'بحث',
    'CustomersSearch[customer_name]': 'اسم العميل',
    'CustomersSearch[id]':            'رقم العميل',
    'CustomersSearch[id_number]':     'الرقم الوطني',
    'CustomersSearch[phone_number]':  'الهاتف',
    'CustomersSearch[city]':          'المدينة',
    'CustomersSearch[job_Type]':      'نوع الوظيفة',
    'CustomersSearch[contract_type]': 'حالة العقد',
    'CustomersSearch[number_row]':    'نتائج/صفحة'
  };
  var statusMap = {
    'active': 'نشط', 'judiciary_active': 'قضائي فعّال', 'judiciary_paid': 'قضائي مسدد',
    'judiciary': 'قضائي', 'legal_department': 'قانوني', 'settlement': 'تسوية',
    'finished': 'منتهي', 'canceled': 'ملغي'
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
      if (key === 'CustomersSearch[contract_type]') displayVal = statusMap[value] || value;

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

  /* ========== ACTIONS MENU (portal approach) ========== */
  var $activePortal = null;
  var $activeWrap   = null;
  var _menuScrollY  = null;
  var _menuOpenTime = 0;

  function closeActMenu() {
    if ($activePortal) {
      $activePortal.remove();
      $activePortal = null;
    }
    if ($activeWrap) {
      $activeWrap.removeClass('open');
      $activeWrap.find('.ct-act-menu').css('display', '');
      $activeWrap = null;
    }
    _menuScrollY = null;
    _menuOpenTime = 0;
  }

  function openActMenu($wrap) {
    var $trigger = $wrap.find('.ct-act-trigger');
    var $menu    = $wrap.find('.ct-act-menu');

    var $portal = $menu.clone(true, true);
    $portal.removeClass('ct-act-menu').addClass('ct-act-menu-portal');
    $portal.css('display', '');
    $('body').append($portal);

    $menu.css('display', 'none');

    var triggerRect = $trigger[0].getBoundingClientRect();
    var menuHeight  = $portal.outerHeight();
    var menuWidth   = $portal.outerWidth();
    var viewH = window.innerHeight;
    var viewW = window.innerWidth;
    var gap = 4;

    var spaceBelow = viewH - triggerRect.bottom - gap;
    var spaceAbove = triggerRect.top - gap;
    var top;
    if (spaceBelow >= menuHeight) {
      top = triggerRect.bottom + gap;
    } else if (spaceAbove >= menuHeight) {
      top = triggerRect.top - menuHeight - gap;
    } else {
      top = spaceBelow >= spaceAbove
        ? triggerRect.bottom + gap
        : Math.max(gap, triggerRect.top - menuHeight - gap);
    }

    var left = triggerRect.right - menuWidth;
    if (left < gap) left = gap;
    if (left + menuWidth > viewW - gap) left = viewW - menuWidth - gap;

    $portal.css({ position: 'fixed', top: top + 'px', left: left + 'px', zIndex: 1075 });

    $wrap.addClass('open');
    $activePortal = $portal;
    $activeWrap   = $wrap;
    _menuScrollY  = window.scrollY;
    _menuOpenTime = Date.now();
  }

  $(document).on('click', '.ct-act-trigger', function (e) {
    e.stopImmediatePropagation();
    var $wrap = $(this).closest('.ct-act-wrap');
    var wasOpen = $wrap.hasClass('open');
    closeActMenu();
    if (!wasOpen) {
      openActMenu($wrap);
    }
  });

  $(document).on('click', function (e) {
    if (!$activePortal) return;
    if (Date.now() - _menuOpenTime < 300) return;
    if ($(e.target).closest('.ct-act-menu-portal, .ct-act-wrap').length) return;
    closeActMenu();
  });

  $(document).on('click', '.ct-act-menu-portal', function (e) {
    e.stopPropagation();
  });

  $(document).on('click', '.ct-act-menu-portal a:not(.cust-delete-link):not([role="modal-remote"])', function () {
    closeActMenu();
  });

  $(window).on('scroll', function () {
    if (!$activePortal) return;
    if (_menuScrollY === null) { _menuScrollY = window.scrollY; return; }
    if (Math.abs(window.scrollY - _menuScrollY) > 60) {
      closeActMenu();
    }
  });
  $(window).on('resize', function () {
    if ($activePortal) closeActMenu();
  });

  /* ========== MODAL-REMOTE IN PORTAL ========== */
  $(document).on('click', '.ct-act-menu-portal [role="modal-remote"]', function () {
    setTimeout(closeActMenu, 100);
  });

  /* ========== DELETE CONFIRMATION ========== */
  $(document).on('click', '.ct-act-menu-portal .cust-delete-link, .ct-act-menu .cust-delete-link', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    var href = $(this).attr('href');
    var msg  = $(this).data('confirm-msg') || 'هل أنت متأكد؟';
    closeActMenu();
    showDeleteConfirm(href, msg);
  });

  function showDeleteConfirm(href, msg) {
    var $modal = $('#ajaxCrudModal');
    if (!$modal.length) return;

    $modal.find('.modal-title').html('<i class="fa fa-exclamation-triangle text-danger"></i> تأكيد الحذف');
    $modal.find('.modal-body').html(
      '<div class="ct-modal-body">' +
        '<i class="fa fa-exclamation-triangle" style="font-size:36px;color:#dc2626;margin-bottom:12px;display:block"></i>' +
        '<p class="lead">' + msg + '</p>' +
        '<div class="ct-modal-actions">' +
          '<a id="custConfirmDelete" href="' + href + '" class="ct-btn ct-btn-primary" style="background:#dc3545;border-color:#dc3545">' +
            '<i class="fa fa-trash"></i> نعم، حذف' +
          '</a>' +
          '<button type="button" class="ct-btn ct-btn-outline" data-bs-dismiss="modal">' +
            '<i class="fa fa-times"></i> إلغاء' +
          '</button>' +
        '</div>' +
      '</div>'
    );
    $modal.find('.modal-footer').html('');

    if (typeof bootstrap !== 'undefined') {
      bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }
  }

  $(document).on('click', '#custConfirmDelete', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var href = $btn.attr('href');
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحذف...');

    $.post(href).done(function () {
      var $modal = $('#ajaxCrudModal');
      if (typeof bootstrap !== 'undefined') {
        var inst = bootstrap.Modal.getInstance($modal[0]);
        if (inst) try { inst.hide(); } catch (ex) {}
      }
      location.reload();
    }).fail(function () {
      $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> نعم، حذف');
      alert('حدث خطأ أثناء الحذف');
    });
  });

  /* ========== COPY CUSTOMER ID ========== */
  $(document).on('click', '.ct-td-id', function () {
    var id = $(this).text().trim();
    if (!id || id === '#') return;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(id).then(function () {
        showCopyTip('تم نسخ رقم العميل: ' + id);
      });
    }
  });

  function showCopyTip(text) {
    var $tip = $('<div class="ct-copied-tip">' + text + '</div>');
    $('body').append($tip);
    setTimeout(function () { $tip.fadeOut(300, function () { $tip.remove(); }); }, 1500);
  }

  /* ========== KEYBOARD NAVIGATION ========== */
  $(document).on('keydown', '.ct-act-trigger', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).trigger('click');
    }
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
