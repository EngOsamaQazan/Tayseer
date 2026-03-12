/**
 * Judiciary V2 — Legal Hub JavaScript
 * Dependencies: jQuery (via YiiAsset), Bootstrap 5 (via Vuexy), PinSystem
 * Config injected via LH_CONFIG global from index.php
 */
(function () {
  'use strict';

  var urls = window.LH_CONFIG || {};
  var activeTab = urls.activeTab || 'cases';

  /* ═══════════════════════════════════════════════
     1. TAB LOADING (lazy load via AJAX)
     ═══════════════════════════════════════════════ */
  function loadTab(tab) {
    var $panel = $('#lh-panel-' + tab);
    if ($panel.data('loaded') === '1' || $panel.data('loaded') === 1) return;

    $panel
      .html('<div class="lh-loader"><i class="fa fa-spinner"></i><span>جاري التحميل...</span></div>')
      .addClass('loading');

    $.get(urls[tab], function (html) {
      $panel.html(html).removeClass('loading').data('loaded', '1');
      if (window._lhInitColResize) setTimeout(window._lhInitColResize, 300);
    }).fail(function () {
      $panel
        .html('<div style="padding:40px;text-align:center;color:#EF4444"><i class="fa fa-exclamation-triangle"></i> حدث خطأ في التحميل</div>')
        .removeClass('loading');
    });
  }

  $(document).on('click', '.lh-tab', function () {
    var tab = $(this).data('tab');
    $('.lh-tab').removeClass('active');
    $(this).addClass('active');
    $('.lh-panel').removeClass('active');
    $('#lh-panel-' + tab).addClass('active');
    loadTab(tab);
  });

  /* ═══════════════════════════════════════════════
     2. BADGE / STATS UPDATER
     ═══════════════════════════════════════════════ */
  if (urls.tabCounts) {
    $.getJSON(urls.tabCounts, function (d) {
      $('#lh-badge-cases').text(d.cases);
      $('#lh-badge-actions').text(d.actions);
      $('#lh-badge-persistence').text(d.persistence);
      $('#lh-badge-legal').text(d.legal);
      $('#lh-badge-collection').text(d.collection);
      $('#lh-stat-cases').text(d.cases.toLocaleString('en'));
      $('#lh-stat-red').text(d.stats.red.toLocaleString('en'));
      $('#lh-stat-collection').text(d.collection.toLocaleString('en'));
      var amt = parseFloat(d.stats.collectionAmount) || 0;
      $('#lh-stat-amount').text(amt.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    });
  }

  /* ═══════════════════════════════════════════════
     3. DROPDOWN MENU TOGGLE (event delegation)
     ═══════════════════════════════════════════════ */
  $(document).on('click', '.jud-act-trigger, .jca-act-trigger', function (e) {
    e.stopPropagation();
    var $wrap = $(this).closest('.jud-act-wrap, .jca-act-wrap');
    var $menu = $wrap.find('.jud-act-menu, .jca-act-menu');
    var wasOpen = $wrap.hasClass('open');
    $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
    if (!wasOpen) {
      $wrap.addClass('open');
      var r = this.getBoundingClientRect();
      $menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
    }
  });
  $(document).on('click', function () {
    $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
  });
  $(document).on('click', '.jud-act-menu a, .jca-act-menu a', function () {
    $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
  });

  /* ═══════════════════════════════════════════════
     4. TIMELINE SIDE PANEL
     ═══════════════════════════════════════════════ */
  var ctlData = null;
  var ctlFilter = 'all';

  function esc(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
  }

  function ctlOpen(url, label) {
    $('#ctlTitle').text('متابعة القضية ' + label);
    $('#ctlBody').html('<div class="ctl-loading"><i class="fa fa-spinner"></i><div>جاري التحميل...</div></div>');
    $('#ctlCaseInfo').html('');
    $('#ctlFilterChips').html('<span class="ctl-chip active" data-filter="all">الكل</span>');
    $('#ctlOverlay').addClass('open');
    setTimeout(function () { $('#ctlPanel').addClass('open'); }, 30);
    ctlFilter = 'all';

    $.getJSON(url, function (res) {
      if (!res.success) {
        $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-exclamation-circle"></i><div>' + (res.message || 'خطأ') + '</div></div>');
        return;
      }
      ctlData = res;
      $('#ctlAddAction').attr('href', res.addActionUrl);
      ctlRenderInfo(res);
      ctlRenderChips(res.parties);
      ctlRenderTimeline(res.timeline);
    }).fail(function () {
      $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-exclamation-triangle"></i><div>حدث خطأ في الاتصال</div></div>');
    });
  }

  function ctlClose() {
    $('#ctlPanel').removeClass('open');
    setTimeout(function () { $('#ctlOverlay').removeClass('open'); }, 300);
    ctlData = null;
  }

  function ctlRenderInfo(res) {
    var c = res['case'];
    var h = '';
    h += '<div class="ctl-info-item"><i class="fa fa-hashtag"></i> القضية: <b>' + (c.judiciary_number || '—') + '/' + (c.year || '') + '</b></div>';
    h += '<div class="ctl-info-item"><i class="fa fa-file-text-o"></i> العقد: <b>' + c.contract_id + '</b></div>';
    if (c.court) h += '<div class="ctl-info-item"><i class="fa fa-institution"></i> <b>' + esc(c.court) + '</b></div>';
    if (c.lawyer) h += '<div class="ctl-info-item"><i class="fa fa-user-secret"></i> <b>' + esc(c.lawyer) + '</b></div>';
    if (c.type) h += '<div class="ctl-info-item"><i class="fa fa-tag"></i> <b>' + esc(c.type) + '</b></div>';
    $('#ctlCaseInfo').html(h);
  }

  function ctlRenderChips(parties) {
    var h = '<span class="ctl-chip active" data-filter="all">الكل</span>';
    for (var i = 0; i < parties.length; i++) {
      var p = parties[i];
      var icon = p.type === 'guarantor' ? 'fa-shield' : 'fa-user';
      h += '<span class="ctl-chip" data-filter="' + p.id + '"><i class="fa ' + icon + '"></i> ' + esc(p.name.split(' ').slice(0, 2).join(' ')) + '</span>';
    }
    $('#ctlFilterChips').html(h);
  }

  function ctlRenderTimeline(items) {
    if (!items || !items.length) {
      $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-inbox"></i><div>لا توجد إجراءات مسجلة</div></div>');
      return;
    }
    var filtered = items;
    if (ctlFilter !== 'all') {
      var fid = parseInt(ctlFilter);
      filtered = items.filter(function (i) { return i.customer_id === fid; });
    }
    if (!filtered.length) {
      $('#ctlBody').html('<div class="ctl-empty"><i class="fa fa-filter"></i><div>لا توجد إجراءات لهذا الطرف</div></div>');
      return;
    }

    var h = '';
    var lastDate = '';
    for (var i = 0; i < filtered.length; i++) {
      var a = filtered[i];
      var d = a.action_date || '';
      if (d && d !== lastDate) {
        h += '<div class="ctl-date-sep"><span>' + esc(d) + '</span></div>';
        lastDate = d;
      }
      var statusHtml = '';
      if (a.request_status) {
        var sMap = { pending: 'قيد الانتظار', approved: 'مقبول', rejected: 'مرفوض' };
        statusHtml = '<span class="ctl-item-status ' + a.request_status + '">' + (sMap[a.request_status] || a.request_status) + '</span>';
      }
      h += '<div class="ctl-item" data-nature="' + (a.action_nature || 'process') + '" data-id="' + (a.id || '') + '">';
      h += '<div class="ctl-item-hdr">';
      h += '<span class="ctl-item-action">' + esc(a.action_name || '—') + '</span>';
      h += '<span class="ctl-item-date">' + esc(d) + '</span>';
      h += '</div>';
      h += '<div class="ctl-item-party"><i class="fa fa-user"></i> ' + esc(a.customer_name || '') + '</div>';
      if (statusHtml) h += '<div>' + statusHtml + '</div>';
      if (a.decision_text) h += '<div class="ctl-item-note" style="color:#1E293B;font-weight:600"><i class="fa fa-gavel" style="color:#F59E0B;margin-left:4px"></i> ' + esc(a.decision_text) + '</div>';
      if (a.note) h += '<div class="ctl-item-note">' + esc(a.note) + '</div>';
      if (a.image) h += '<div class="ctl-item-img"><a href="' + a.image + '" target="_blank"><i class="fa fa-paperclip"></i> مرفق</a></div>';
      h += '<div class="ctl-item-meta">';
      if (a.created_by) h += '<span><i class="fa fa-user-circle"></i> ' + esc(a.created_by) + '</span>';
      if (a.created_at) h += '<span>' + esc(a.created_at) + '</span>';
      h += '</div>';
      if (a.request_status === 'pending' && a.id) {
        h += '<div class="ctl-req-actions">';
        h += '<button type="button" class="ctl-req-btn approve" data-action-id="' + a.id + '" data-status="approved"><i class="fa fa-check"></i> موافقة</button>';
        h += '<button type="button" class="ctl-req-btn reject" data-action-id="' + a.id + '" data-status="rejected"><i class="fa fa-times"></i> رفض</button>';
        h += '</div>';
        h += '<div class="ctl-decision-form" id="ctl-df-' + a.id + '">';
        h += '<textarea class="ctl-decision-input" placeholder="نص القرار أو سبب الرفض (اختياري)..." rows="2"></textarea>';
        h += '<div class="ctl-decision-actions">';
        h += '<button type="button" class="ctl-decision-cancel">إلغاء</button>';
        h += '<button type="button" class="ctl-decision-submit" data-action-id="' + a.id + '">تأكيد</button>';
        h += '</div></div>';
      }
      h += '</div>';
    }
    $('#ctlBody').html(h);
  }

  $(document).on('click', '.jud-timeline-btn', function (e) {
    e.stopPropagation();
    e.preventDefault();
    ctlOpen($(this).data('url'), $(this).data('label'));
  });

  $('#ctlClose, #ctlOverlay').on('click', function () { ctlClose(); });

  $(document).on('click', '.ctl-chip', function () {
    $('.ctl-chip').removeClass('active');
    $(this).addClass('active');
    ctlFilter = $(this).data('filter');
    if (ctlData) ctlRenderTimeline(ctlData.timeline);
  });

  /* ═══════════════════════════════════════════════
     5. PRA APPROVE/REJECT (Timeline panel)
     ═══════════════════════════════════════════════ */
  var pendingDecision = {};

  $(document).on('click', '.ctl-req-btn', function () {
    var id = $(this).data('action-id');
    var st = $(this).data('status');
    pendingDecision = { id: id, status: st };
    var $form = $('#ctl-df-' + id);
    $form.addClass('open');
    $form.find('.ctl-decision-submit')
      .removeClass('confirm-approve confirm-reject')
      .addClass(st === 'approved' ? 'confirm-approve' : 'confirm-reject')
      .text(st === 'approved' ? 'تأكيد الموافقة' : 'تأكيد الرفض');
    $form.find('.ctl-decision-input').focus();
  });

  $(document).on('click', '.ctl-decision-cancel', function () {
    $(this).closest('.ctl-decision-form').removeClass('open');
    pendingDecision = {};
  });

  $(document).on('click', '.ctl-decision-submit', function () {
    if (!pendingDecision.id) return;
    var $btn = $(this);
    var $form = $btn.closest('.ctl-decision-form');
    var decisionText = $form.find('.ctl-decision-input').val().trim();
    $btn.prop('disabled', true).text('جاري الحفظ...');
    $.post(urls.updateReqStatus, {
      id: pendingDecision.id,
      status: pendingDecision.status,
      decision_text: decisionText,
      _csrf: yii.getCsrfToken()
    }).done(function (res) {
      if (res.success && ctlData && ctlData.timeline) {
        for (var j = 0; j < ctlData.timeline.length; j++) {
          if (ctlData.timeline[j].id == pendingDecision.id) {
            ctlData.timeline[j].request_status = res.new_status;
            if (decisionText) ctlData.timeline[j].decision_text = decisionText;
            break;
          }
        }
        ctlRenderTimeline(ctlData.timeline);
      }
    }).fail(function () {
      alert('حدث خطأ أثناء الحفظ');
      $btn.prop('disabled', false).text('تأكيد');
    }).always(function () { pendingDecision = {}; });
  });

  /* ═══════════════════════════════════════════════
     6. PRA APPROVE/REJECT (inline GridView)
     ═══════════════════════════════════════════════ */
  var praPending = {};

  $(document).on('click', '.pra-approve, .pra-reject', function (e) {
    e.stopPropagation();
    var id = $(this).data('id');
    var st = $(this).hasClass('pra-approve') ? 'approved' : 'rejected';
    praPending = { id: id, status: st };
    $('.pra-df').slideUp(100);
    var $df = $('#pra-df-' + id);
    $df.slideDown(150);
    $df.find('.pra-submit')
      .removeClass('pra-submit-approve pra-submit-reject')
      .addClass(st === 'approved' ? 'pra-submit-approve' : 'pra-submit-reject')
      .text(st === 'approved' ? 'تأكيد الموافقة' : 'تأكيد الرفض')
      .prop('disabled', false);
    $df.find('.pra-input').val('').focus();
  });

  $(document).on('click', '.pra-df-cancel', function (e) {
    e.stopPropagation();
    $(this).closest('.pra-df').slideUp(150);
    praPending = {};
  });

  $(document).on('click', '.pra-submit', function (e) {
    e.stopPropagation();
    if (!praPending.id) return;
    var savedPending = { id: praPending.id, status: praPending.status };
    var $btn = $(this);
    var $df = $btn.closest('.pra-df');
    var $item = $df.closest('.pra-item');
    var decisionText = $df.find('.pra-input').val().trim();
    $btn.prop('disabled', true).text('جاري الحفظ...');

    $.post(urls.updateReqStatus, {
      id: savedPending.id,
      status: savedPending.status,
      decision_text: decisionText,
      _csrf: yii.getCsrfToken()
    }).done(function (res) {
      if (res.success) {
        var sLabels = { approved: 'تمت الموافقة', rejected: 'تم الرفض' };
        var sColors = { approved: '#065F46', rejected: '#991B1B' };
        var sBgs = { approved: '#D1FAE5', rejected: '#FEE2E2' };
        $item.find('.pra-btns').html(
          '<span class="pra-status" style="background:' + sBgs[savedPending.status] + ';color:' + sColors[savedPending.status] + '">' + sLabels[savedPending.status] + '</span>'
        );
        $df.slideUp(150);
        $item.addClass('pra-done');
        var $badge = $('.lh-pending-count');
        if ($badge.length) {
          var c = parseInt($badge.text()) - 1;
          $badge.text(c > 0 ? c : 0);
          if (c <= 0) $badge.closest('.lh-pending-queue').fadeOut(300);
        }
      }
    }).fail(function () {
      alert('حدث خطأ أثناء الحفظ');
      $btn.prop('disabled', false).text('تأكيد');
    }).always(function () { praPending = {}; });
  });

  /* ═══════════════════════════════════════════════
     7. MODAL HANDLER (role="modal-remote")
        Uses Bootstrap 5 Modal API + JSON-aware AJAX
     ═══════════════════════════════════════════════ */
  var $modal = $('#ajaxCrudModal');
  var modalEl = $modal[0];
  var $mTitle = $modal.find('.modal-title');
  var $mBody = $modal.find('.modal-body');
  var $mFooter = $modal.find('.modal-footer');
  var $mDialog = $modal.find('.modal-dialog');
  var bsModalInstance = null;

  if (!$mFooter.length) {
    $modal.find('.modal-content').append('<div class="modal-footer"></div>');
    $mFooter = $modal.find('.modal-footer');
  }

  var SPINNER = '<div style="text-align:center;padding:40px">'
    + '<i class="fa fa-spinner fa-spin" style="font-size:28px;color:#800020"></i>'
    + '<div style="margin-top:10px;color:#64748B;font-size:13px">جاري التحميل...</div></div>';

  function getBsModal() {
    if (!modalEl || typeof bootstrap === 'undefined') return null;
    if (!bsModalInstance) {
      bsModalInstance = bootstrap.Modal.getOrCreateInstance
        ? bootstrap.Modal.getOrCreateInstance(modalEl)
        : new bootstrap.Modal(modalEl);
    }
    return bsModalInstance;
  }

  function showModal() { var m = getBsModal(); if (m) m.show(); }
  function hideModal() { var m = getBsModal(); if (m) m.hide(); }

  function setModalSize(size) {
    $mDialog.removeClass('modal-sm modal-lg modal-xl');
    if (size === 'large' || size === 'lg') $mDialog.addClass('modal-lg');
    else if (size === 'xl') $mDialog.addClass('modal-xl');
    else if (size === 'small' || size === 'sm') $mDialog.addClass('modal-sm');
  }

  function renderModalResponse(data) {
    if (typeof data === 'string') {
      $mTitle.text('');
      $mBody.html(data);
      $mFooter.html('');
      return;
    }
    if (data.forceClose) {
      hideModal();
      refreshActiveTab();
      return;
    }
    if (data.title)   $mTitle.html(data.title);
    if (data.content) $mBody.html(data.content);
    if (data.footer)  $mFooter.html(data.footer);
    if (data.size)    setModalSize(data.size);
    $mBody.find('script').each(function () {
      try { $.globalEval(this.text || this.textContent || this.innerHTML || ''); } catch (ex) {}
    });
  }

  function refreshActiveTab() {
    var tab = $('.lh-tab.active').data('tab');
    if (tab) {
      var $panel = $('#lh-panel-' + tab);
      $panel.data('loaded', '0');
      loadTab(tab);
    }
  }

  function loadRemoteModal(href) {
    $mTitle.html('');
    $mBody.html(SPINNER);
    $mFooter.html('');
    setModalSize('');
    showModal();
    $.ajax({
      url: href, type: 'GET', dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).done(function (data) {
      renderModalResponse(data);
    }).fail(function (xhr) {
      var fb = xhr.responseText || '';
      if (fb && fb.charAt(0) === '{') {
        try { renderModalResponse(JSON.parse(fb)); return; } catch (ex) {}
      }
      if (fb) { $mBody.html(fb); $mFooter.html(''); }
      else {
        $mBody.html('<div style="text-align:center;padding:30px;color:#DC2626">'
          + '<i class="fa fa-exclamation-triangle" style="font-size:28px;display:block;margin-bottom:8px"></i>'
          + 'حدث خطأ في تحميل المحتوى</div>');
      }
    });
  }

  function submitModalForm($form) {
    var action = $form.attr('action');
    var hasFile = $form.find('input[type="file"]').length > 0;
    var formData = hasFile ? new FormData($form[0]) : $form.serialize();
    var ajaxOpts = {
      url: action, type: 'POST', dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    };
    if (hasFile) {
      ajaxOpts.data = formData;
      ajaxOpts.processData = false;
      ajaxOpts.contentType = false;
    } else {
      ajaxOpts.data = formData;
    }
    $mFooter.find('[type="submit"]').prop('disabled', true)
      .html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

    $.ajax(ajaxOpts).done(function (data) {
      renderModalResponse(data);
    }).fail(function (xhr) {
      var fb = xhr.responseText || '';
      try { renderModalResponse(JSON.parse(fb)); return; } catch (ex) {}
      $mBody.html(fb || '<div class="text-danger text-center" style="padding:20px">حدث خطأ أثناء الحفظ</div>');
      $mFooter.find('[type="submit"]').prop('disabled', false).html('<i class="fa fa-save"></i> حفظ');
    });
  }

  $(document).on('click', '[role="modal-remote"]', function (e) {
    if ($(this).data('request-method') === 'post') return;
    e.preventDefault();
    e.stopPropagation();
    var href = $(this).attr('href') || $(this).data('url');
    if (!href || href === '#') return;
    loadRemoteModal(href);
  });

  $(document).on('click', '[role="modal-remote"][data-request-method="post"]', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    var href = $(this).attr('href');
    var msg = $(this).data('confirm-message') || $(this).data('confirm') || 'هل أنت متأكد من الحذف؟';
    $mTitle.html('<i class="fa fa-exclamation-triangle" style="color:#DC2626"></i> تأكيد');
    $mBody.html('<div style="text-align:center;padding:20px;font-size:14px">' + msg + '</div>');
    $mFooter.html(
      '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>'
      + '<button type="button" class="btn btn-danger" id="lh-confirm-delete"><i class="fa fa-trash"></i> حذف</button>'
    );
    showModal();
    $mFooter.off('click', '#lh-confirm-delete').on('click', '#lh-confirm-delete', function () {
      $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
      $.post(href).done(function () { hideModal(); refreshActiveTab(); })
        .fail(function () { hideModal(); refreshActiveTab(); });
    });
  });

  $modal.on('click', '[type="submit"]', function (e) {
    e.preventDefault();
    var $form = $mBody.find('form');
    if (!$form.length) return;
    var evt = $.Event('beforeSubmit');
    $form.trigger(evt);
    if (evt.isDefaultPrevented()) return;
    submitModalForm($form);
  });

  $modal.on('submit', 'form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var evt = $.Event('beforeSubmit');
    $form.trigger(evt);
    if (evt.isDefaultPrevented()) return;
    submitModalForm($form);
  });

  $modal.on('hidden.bs.modal', function () {
    $mBody.html('');
    $mTitle.text('');
    $mFooter.html('');
    setModalSize('');
  });

  /* ═══════════════════════════════════════════════
     8. COLUMN RESIZER (progressive enhancement)
     ═══════════════════════════════════════════════ */
  var SK = 'lh_col_widths_';

  function initResize() {
    var headers = document.querySelectorAll('.lh-panel .kv-grid-table thead th');
    if (!headers.length) return;

    headers.forEach(function (th) {
      if (th.querySelector('.col-resize-handle')) return;
      var handle = document.createElement('div');
      handle.className = 'col-resize-handle';
      th.appendChild(handle);
    });

    restoreWidths();
  }

  function restoreWidths() {
    document.querySelectorAll('.lh-panel .kv-grid-table').forEach(function (table) {
      var tid = table.id || 'default';
      try {
        var saved = localStorage.getItem(SK + tid);
        if (!saved) return;
        var widths = JSON.parse(saved);
        var ths = table.querySelectorAll('thead th');
        Object.keys(widths).forEach(function (i) {
          var th = ths[parseInt(i)];
          if (th) { th.style.width = widths[i]; th.style.minWidth = widths[i]; }
        });
      } catch (e) { /* ignore */ }
    });
  }

  var dragging = false, startX = 0, startW = 0, activeEl = null, activeTid = '';

  document.addEventListener('mousedown', function (e) {
    if (!e.target.classList.contains('col-resize-handle')) return;
    e.preventDefault();
    e.stopPropagation();
    dragging = true;
    activeEl = e.target.parentElement;
    var table = activeEl.closest('table');
    activeTid = table ? (table.id || 'default') : 'default';
    startX = e.pageX;
    startW = activeEl.offsetWidth;
    e.target.classList.add('active');
  }, true);

  document.addEventListener('mousemove', function (e) {
    if (!dragging || !activeEl) return;
    var newW = Math.max(40, startW + (startX - e.pageX));
    activeEl.style.width = newW + 'px';
    activeEl.style.minWidth = newW + 'px';
  });

  document.addEventListener('mouseup', function () {
    if (!dragging) return;
    dragging = false;
    document.querySelectorAll('.col-resize-handle.active').forEach(function (h) { h.classList.remove('active'); });
    if (activeEl) {
      try {
        var table = activeEl.closest('table');
        if (table) {
          var widths = {};
          table.querySelectorAll('thead th').forEach(function (th, i) { widths[i] = th.style.width || (th.offsetWidth + 'px'); });
          localStorage.setItem(SK + activeTid, JSON.stringify(widths));
        }
      } catch (e) { /* ignore */ }
    }
    activeEl = null;
  });

  window._lhInitColResize = initResize;

  /* ═══════════════════════════════════════════════
     9. PIN SYSTEM
     ═══════════════════════════════════════════════ */
  if (typeof PinSystem !== 'undefined') {
    PinSystem.init({
      type: 'judiciary_case',
      barSelector: '#pin-bar',
      buildUrl: function (itemId) {
        return '/judiciary/judiciary/view?id=' + itemId;
      }
    });

    $(document).on('click', '.pin-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = $(this).data('item-id');
      var label = $(this).data('label') || '';
      var extra = $(this).data('extra') || '';
      PinSystem.togglePin(id, label, extra);
    });
  }

  /* ═══════════════════════════════════════════════
     INIT
     ═══════════════════════════════════════════════ */
  if (activeTab !== 'cases') {
    loadTab(activeTab);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { setTimeout(initResize, 300); });
  } else {
    setTimeout(initResize, 300);
  }

})();
