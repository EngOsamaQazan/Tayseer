/**
 * TAYSEER ERP — Shared GridView Modal Handler  v2.0
 * =================================================
 * Replaces CrudAsset's Bootstrap 3 modal handling with Bootstrap 5.
 * Works with ALL pages that use role="modal-remote" links.
 *
 * Features:
 * - JSON response support (title, content, footer, size, forceClose)
 * - HTML fallback (legacy pages that return raw HTML)
 * - File upload support in modal forms
 * - Dynamic PJAX container detection
 * - Custom action dropdown menus (.ty-act-trigger / .ty-act-menu)
 * - Delete confirmation dialog
 *
 * USAGE: Register this JS after jQuery in any GridView page.
 */
(function ($) {
  'use strict';

  var $modal = $('#ajaxCrudModal');
  var modalEl = $modal[0];
  if (!modalEl) return;

  // ── 1. BOOTSTRAP 5 MODAL HELPERS ──
  function getBsModal() {
    if (typeof bootstrap === 'undefined') return null;
    return bootstrap.Modal.getOrCreateInstance
      ? bootstrap.Modal.getOrCreateInstance(modalEl)
      : new bootstrap.Modal(modalEl);
  }

  function showModal() {
    var m = getBsModal();
    if (m) m.show();
  }

  function hideModal() {
    var m = getBsModal();
    if (m) try { m.hide(); } catch (e) {}
    $modal.removeClass('show').css('display', '');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '');
  }

  function setModalLoading() {
    $modal.find('.modal-title').text('جاري التحميل...');
    $modal.find('.modal-body').html(
      '<div style="text-align:center;padding:40px">' +
      '<i class="fa fa-spinner fa-spin" style="font-size:24px;color:#800020"></i>' +
      '</div>'
    );
    $modal.find('.modal-footer').html('');
  }

  function setModalSize(size) {
    var $dlg = $modal.find('.modal-dialog');
    $dlg.removeClass('modal-sm modal-lg modal-xl');
    if (size === 'large' || size === 'lg') $dlg.addClass('modal-lg');
    else if (size === 'small' || size === 'sm') $dlg.addClass('modal-sm');
    else if (size === 'xl') $dlg.addClass('modal-xl');
  }

  // ── 2. RENDER RESPONSE ──
  function renderModalResponse(data) {
    if (data.forceClose) {
      hideModal();
      refreshGrid();
      return;
    }
    if (data.size) setModalSize(data.size);
    if (data.title !== undefined) $modal.find('.modal-title').html(data.title);
    if (data.content !== undefined) $modal.find('.modal-body').html(data.content);
    if (data.footer !== undefined) {
      var $footer = $modal.find('.modal-footer');
      if (!$footer.length) {
        $modal.find('.modal-content').append('<div class="modal-footer"></div>');
        $footer = $modal.find('.modal-footer');
      }
      $footer.html(data.footer);
    }
    if (data.forceReload) {
      refreshGrid();
    }
  }

  function renderHtmlFallback(html) {
    $modal.find('.modal-body').html(html);
    var title = $modal.find('.modal-body').find('h1,h2,h3,h4,.modal-title-text').first().text();
    if (title) $modal.find('.modal-title').text(title);
  }

  function isJsonResponse(data) {
    return data && typeof data === 'object' && (data.content !== undefined || data.forceClose || data.title !== undefined);
  }

  // ── 3. LOAD REMOTE MODAL ──
  function loadRemoteModal(href) {
    setModalLoading();
    showModal();
    $.ajax({
      url: href,
      type: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).done(function (data) {
      if (isJsonResponse(data)) {
        renderModalResponse(data);
      } else if (typeof data === 'string') {
        try {
          var json = JSON.parse(data);
          if (isJsonResponse(json)) { renderModalResponse(json); return; }
        } catch (e) {}
        renderHtmlFallback(data);
      }
    }).fail(function (xhr) {
      if (xhr.responseText) {
        try {
          var json = JSON.parse(xhr.responseText);
          if (isJsonResponse(json)) { renderModalResponse(json); return; }
        } catch (e) {}
      }
      $modal.find('.modal-title').text('خطأ');
      $modal.find('.modal-body').html(
        '<div class="text-danger text-center" style="padding:30px">' +
        '<i class="fa fa-exclamation-triangle" style="font-size:28px;margin-bottom:8px;display:block"></i>' +
        'حدث خطأ في تحميل المحتوى' +
        '</div>'
      );
      $modal.find('.modal-footer').html(
        '<button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>'
      );
    });
  }

  // ── 4. SUBMIT MODAL FORM ──
  function submitModalForm($form) {
    var action = $form.attr('action') || window.location.href;
    var hasFiles = $form.find('input[type="file"]').length > 0;
    var ajaxOpts = {
      url: action,
      type: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    };

    if (hasFiles) {
      ajaxOpts.data = new FormData($form[0]);
      ajaxOpts.processData = false;
      ajaxOpts.contentType = false;
    } else {
      ajaxOpts.data = $form.serialize();
    }

    var $btn = $modal.find('[type="submit"]');
    var origText = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

    $.ajax(ajaxOpts).done(function (data) {
      if (isJsonResponse(data)) {
        renderModalResponse(data);
      } else if (typeof data === 'string') {
        try {
          var json = JSON.parse(data);
          if (isJsonResponse(json)) { renderModalResponse(json); return; }
        } catch (e) {}
        hideModal();
        refreshGrid();
      } else {
        hideModal();
        refreshGrid();
      }
    }).fail(function (xhr) {
      $btn.prop('disabled', false).html(origText);
      if (xhr.responseText) {
        $modal.find('.modal-body').html(xhr.responseText);
      } else {
        alert('حدث خطأ أثناء الحفظ');
      }
    });
  }

  // ── 5. REFRESH GRID (auto-detect PJAX container) ──
  function refreshGrid() {
    if (typeof window.ocpRefreshTabs === 'function') {
      window.ocpRefreshTabs();
      return;
    }
    if (!$.pjax) { location.reload(); return; }
    var $pjax = $('[data-pjax-container]').filter('[id$="-pjax"]');
    if (!$pjax.length) $pjax = $('[id$="-grid-pjax"]');
    if (!$pjax.length) $pjax = $('[id$="-pjax"]');
    if ($pjax.length) {
      $.pjax.reload({ container: '#' + $pjax.first().attr('id'), timeout: 5000 });
    } else {
      location.reload();
    }
  }

  // ── 6. EVENT: role="modal-remote" click ──
  $(document).on('click', '[role="modal-remote"]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var href = $(this).attr('href') || $(this).data('url');
    if (!href || href === '#') return;
    closeAllDropdowns();
    loadRemoteModal(href);
  });

  // ── 7. EVENT: role="modal-remote-bulk" click ──
  $(document).on('click', '[role="modal-remote-bulk"]', function (e) {
    e.preventDefault();
    var href = $(this).data('url');
    var ids = [];
    $('.select-on-check-all:checked, .kv-row-checkbox:checked').each(function () {
      ids.push($(this).val());
    });
    if (ids.length === 0) return;
    setModalLoading();
    showModal();
    $.post(href, { pks: ids }).done(function (data) {
      if (isJsonResponse(data)) {
        renderModalResponse(data);
      } else {
        renderHtmlFallback(typeof data === 'string' ? data : '');
      }
    });
  });

  // ── 8. EVENT: form submit inside modal ──
  $modal.on('submit', 'form', function (e) {
    if ($(this).data('native-submit')) return true;
    e.preventDefault();
    submitModalForm($(this));
  });
  $modal.on('click', '[type="submit"]', function (e) {
    if ($(this).closest('form').data('native-submit')) return true;
    var $form = $modal.find('form').not('[data-native-submit]');
    if ($form.length) {
      e.preventDefault();
      submitModalForm($form);
    }
  });

  // Nested modal-remote inside modal body
  $modal.on('click', '[role="modal-remote"]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var href = $(this).attr('href') || $(this).data('url');
    if (href) loadRemoteModal(href);
  });

  // ── 9. EVENT: delete confirmation ──
  $(document).on('click', '.ty-act-menu a[data-method="post"], .cust-act-menu a[data-method="post"], .jud-act-menu a[data-method="post"], .jca-act-menu a[data-method="post"]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    var href = $el.attr('href');
    var msg = $el.data('confirm') || 'هل أنت متأكد؟';

    closeAllDropdowns();

    $modal.find('.modal-title').text('تأكيد الحذف');
    $modal.find('.modal-body').html(
      '<div style="text-align:center;padding:20px">' +
        '<i class="fa fa-exclamation-triangle" style="font-size:36px;color:#dc2626;margin-bottom:12px;display:block"></i>' +
        '<p style="margin-bottom:20px;font-size:14px;color:#334155">' + msg + '</p>' +
        '<div style="display:flex;gap:8px;justify-content:center">' +
          '<button class="btn btn-danger ty-confirm-yes" style="border-radius:8px;padding:6px 20px">' +
            '<i class="fa fa-trash"></i> نعم، حذف' +
          '</button>' +
          '<button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;padding:6px 20px">' +
            '<i class="fa fa-times"></i> إلغاء' +
          '</button>' +
        '</div>' +
      '</div>'
    );
    $modal.find('.modal-footer').html('');
    showModal();

    $modal.off('click', '.ty-confirm-yes').on('click', '.ty-confirm-yes', function () {
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
      $.post(href).done(function () {
        hideModal();
        refreshGrid();
      }).fail(function () {
        $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> نعم، حذف');
        alert('حدث خطأ');
      });
    });
  });

  // Legacy: data-request-method="post" with data-confirm-message
  $(document).on('click', '[data-request-method="post"][data-confirm-message]', function (e) {
    e.preventDefault();
    var $el = $(this);
    var msg = $el.data('confirm-message') || 'هل أنت متأكد؟';

    $modal.find('.modal-title').text('تأكيد');
    $modal.find('.modal-body').html(
      '<div style="text-align:center;padding:20px">' +
        '<p style="margin-bottom:20px">' + msg + '</p>' +
        '<div style="display:flex;gap:8px;justify-content:center">' +
          '<button class="btn btn-danger ty-confirm-yes" style="border-radius:8px;padding:6px 20px">' +
            '<i class="fa fa-check"></i> نعم' +
          '</button>' +
          '<button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;padding:6px 20px">' +
            '<i class="fa fa-times"></i> لا' +
          '</button>' +
        '</div>' +
      '</div>'
    );
    showModal();

    $modal.off('click', '.ty-confirm-yes').on('click', '.ty-confirm-yes', function () {
      $.post($el.attr('href')).done(function () {
        hideModal();
        refreshGrid();
      });
    });
  });

  // ── 10. DROPDOWN MENUS (shared for all modules) ──
  function closeAllDropdowns() {
    $('.ty-act-menu.show, .cust-act-menu.show, .jud-act-menu.show, .jca-act-menu.show').removeClass('show');
  }

  $(document).on('click', '.ty-act-trigger, .cust-act-trigger, .jud-act-trigger, .jca-act-trigger', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $menu = $(this).next('.ty-act-menu, .cust-act-menu, .jud-act-menu, .jca-act-menu');
    var wasOpen = $menu.hasClass('show');
    closeAllDropdowns();
    if (!wasOpen) $menu.addClass('show');
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('.ty-act-wrap, .cust-act-wrap, .jud-act-wrap, .jca-act-wrap').length) {
      closeAllDropdowns();
    }
  });

  $(document).on('click', '.ty-act-menu a:not([data-method]), .cust-act-menu a:not([data-method]), .jud-act-menu a:not([data-method]), .jca-act-menu a:not([data-method])', function () {
    closeAllDropdowns();
  });

  // ── 11. CLEANUP ON MODAL HIDE ──
  $modal.on('hidden.bs.modal', function () {
    $modal.find('.modal-title').text('');
    $modal.find('.modal-body').html('');
    $modal.find('.modal-footer').html('');
    $modal.find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl');
    $('body').css('overflow', '');
  });

  // ── 12. BS3→BS5 COMPATIBILITY SHIM ──
  // Makes old data-dismiss="modal" work with Bootstrap 5
  $(document).on('click', '[data-dismiss="modal"]', function (e) {
    var $m = $(this).closest('.modal');
    if ($m.length && typeof bootstrap !== 'undefined') {
      e.preventDefault();
      var inst = bootstrap.Modal.getInstance($m[0]);
      if (inst) inst.hide();
      else $m.modal('hide');
    }
  });

})(jQuery);
