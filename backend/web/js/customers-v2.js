/**
 * CUSTOMERS V2 — JS Handler
 * ==========================
 * JSON-aware modal handler + dropdown menus + grid refresh
 * NO Bootstrap 3, NO CrudAsset, NO ajaxcrud
 * Designed for Bootstrap 5 modal + Kartik GridView
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

  // ── 2. RENDER MODAL RESPONSE (JSON) ──
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
  }

  // ── 3. LOAD REMOTE MODAL ──
  function loadRemoteModal(href) {
    setModalLoading();
    showModal();
    $.ajax({
      url: href,
      type: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).done(function (data) {
      renderModalResponse(data);
    }).fail(function (xhr) {
      if (xhr.responseText) {
        try {
          var json = JSON.parse(xhr.responseText);
          renderModalResponse(json);
          return;
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
    var formData;
    var ajaxOpts = {
      url: action,
      type: 'POST',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    };

    if (hasFiles) {
      formData = new FormData($form[0]);
      ajaxOpts.data = formData;
      ajaxOpts.processData = false;
      ajaxOpts.contentType = false;
    } else {
      ajaxOpts.data = $form.serialize();
    }

    var $btn = $modal.find('[type="submit"]');
    var origText = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

    $.ajax(ajaxOpts).done(function (data) {
      renderModalResponse(data);
    }).fail(function () {
      $btn.prop('disabled', false).html(origText);
      alert('حدث خطأ أثناء الحفظ');
    });
  }

  // ── 5. REFRESH GRID ──
  function refreshGrid() {
    if ($.pjax) {
      var $pjax = $('#customers-grid-pjax');
      if ($pjax.length) {
        $.pjax.reload({ container: '#customers-grid-pjax', timeout: 5000 });
        return;
      }
    }
    location.reload();
  }

  // ── 6. EVENT HANDLERS ──

  // role="modal-remote" click
  $(document).on('click', '[role="modal-remote"]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var href = $(this).attr('href') || $(this).data('url');
    if (!href || href === '#') return;
    loadRemoteModal(href);
  });

  // Form submit inside modal
  $modal.on('submit', 'form', function (e) {
    e.preventDefault();
    submitModalForm($(this));
  });
  $modal.on('click', '[type="submit"]', function (e) {
    var $form = $modal.find('form');
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

  // Delete confirmation (data-method="post" with data-confirm)
  $(document).on('click', '.cust-act-menu a[data-method="post"]', function (e) {
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
          '<button class="btn btn-danger cust-confirm-yes" style="border-radius:8px;padding:6px 20px">' +
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

    $modal.off('click', '.cust-confirm-yes').on('click', '.cust-confirm-yes', function () {
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

  // ── 7. DROPDOWN MENUS ──
  function closeAllDropdowns() {
    $('.cust-act-menu.show').removeClass('show');
  }

  $(document).on('click', '.cust-act-trigger', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $menu = $(this).next('.cust-act-menu');
    var wasOpen = $menu.hasClass('show');
    closeAllDropdowns();
    if (!wasOpen) $menu.addClass('show');
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('.cust-act-wrap').length) {
      closeAllDropdowns();
    }
  });

  // Close dropdown on menu item click
  $(document).on('click', '.cust-act-menu a:not([data-method])', function () {
    closeAllDropdowns();
  });

  // ── 8. CLEANUP ON MODAL HIDE ──
  $modal.on('hidden.bs.modal', function () {
    $modal.find('.modal-title').text('');
    $modal.find('.modal-body').html('');
    $modal.find('.modal-footer').html('');
    $modal.find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl');
    $('body').css('overflow', '');
  });

})(jQuery);
