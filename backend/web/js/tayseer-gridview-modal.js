/**
 * TAYSEER ERP — Shared GridView Modal Handler
 * Replaces CrudAsset's Bootstrap 3 modal handling with Bootstrap 5.
 * Works with all pages that use role="modal-remote" links.
 *
 * USAGE: Register this JS after jQuery in any GridView page that
 *        previously used CrudAsset + yii\bootstrap\Modal.
 */
(function ($) {
  'use strict';

  var $modal = $('#ajaxCrudModal');
  var modalEl = $modal[0];
  if (!modalEl) return;

  function getBsModal() {
    if (typeof bootstrap === 'undefined') return null;
    return bootstrap.Modal.getOrCreateInstance
      ? bootstrap.Modal.getOrCreateInstance(modalEl)
      : new bootstrap.Modal(modalEl);
  }

  $(document).on('click', '[role="modal-remote"]', function (e) {
    e.preventDefault();
    var href = $(this).attr('href') || $(this).data('url');
    if (!href) return;

    $modal.find('.modal-title').text('');
    $modal.find('.modal-body').html(
      '<div style="text-align:center;padding:40px">' +
      '<i class="fa fa-spinner fa-spin" style="font-size:24px;color:#800020"></i>' +
      '</div>'
    );

    var bsModal = getBsModal();
    if (bsModal) bsModal.show();

    $.get(href).done(function (data) {
      $modal.find('.modal-body').html(data);
      var title = $modal.find('.modal-body').find('h1,h2,h3,h4,.modal-title-text').first().text();
      if (title) $modal.find('.modal-title').text(title);
    }).fail(function () {
      $modal.find('.modal-body').html(
        '<div class="text-danger text-center" style="padding:30px">' +
        '<i class="fa fa-exclamation-triangle" style="font-size:28px;margin-bottom:8px;display:block"></i>' +
        'حدث خطأ في تحميل المحتوى' +
        '</div>'
      );
    });
  });

  $(document).on('click', '[role="modal-remote-bulk"]', function (e) {
    e.preventDefault();
    var href = $(this).data('url');
    var ids = [];
    $('.select-on-check-all:checked, .kv-row-checkbox:checked').each(function () {
      ids.push($(this).val());
    });
    if (ids.length === 0) return;

    $modal.find('.modal-title').text('');
    $modal.find('.modal-body').html(
      '<div style="text-align:center;padding:40px">' +
      '<i class="fa fa-spinner fa-spin" style="font-size:24px;color:#800020"></i>' +
      '</div>'
    );

    var bsModal = getBsModal();
    if (bsModal) bsModal.show();

    $.post(href, { pks: ids }).done(function (data) {
      $modal.find('.modal-body').html(data);
    });
  });

  $modal.on('click', '[data-action="submit"]', function (e) {
    e.preventDefault();
    var $form = $modal.find('form');
    if (!$form.length) return;
    var action = $form.attr('action');
    var method = ($form.attr('method') || 'POST').toUpperCase();
    var data = $form.serialize();

    $.ajax({
      url: action,
      type: method,
      data: data,
    }).done(function (res) {
      var bsModal = getBsModal();
      if (bsModal) bsModal.hide();
      if ($.pjax) {
        $.pjax.reload({ container: '#crud-datatable-pjax', timeout: 5000 });
      } else {
        location.reload();
      }
    }).fail(function (xhr) {
      if (xhr.responseText) {
        $modal.find('.modal-body').html(xhr.responseText);
      }
    });
  });

  $(document).on('click', '[data-request-method="post"][data-confirm-message]', function (e) {
    e.preventDefault();
    var $el = $(this);
    var msg = $el.data('confirm-message') || 'هل أنت متأكد؟';
    var title = $el.data('confirm-title') || 'تأكيد';

    $modal.find('.modal-title').text(title);
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

    var bsModal = getBsModal();
    if (bsModal) bsModal.show();

    $modal.off('click', '.ty-confirm-yes').on('click', '.ty-confirm-yes', function () {
      $.post($el.attr('href')).done(function () {
        if (bsModal) bsModal.hide();
        if ($.pjax) {
          $.pjax.reload({ container: '#crud-datatable-pjax', timeout: 5000 });
        } else {
          location.reload();
        }
      });
    });
  });

})(jQuery);
