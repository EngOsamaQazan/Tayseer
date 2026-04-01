<?php
/**
 * قائمة إجراءات العملاء القضائية
 * BS5 modal + JSON-aware AJAX handler
 */
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use backend\widgets\ExportButtons;

$this->title = 'إجراءات العملاء القضائية';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.jca-act-wrap { position:relative;display:inline-block; }
.jca-act-trigger {
    background:none;border:1px solid #E2E8F0;border-radius:6px;
    width:30px;height:28px;display:inline-flex;align-items:center;justify-content:center;
    cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0;
}
.jca-act-trigger:hover { background:#F1F5F9;color:#1E293B;border-color:#CBD5E1; }
.jca-act-menu {
    display:none;position:fixed;left:auto;top:auto;margin:0;min-width:160px;
    background:#fff;border:1px solid #E2E8F0;border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;
    direction:rtl;font-size:12px;
}
.jca-act-wrap.open .jca-act-menu { display:block; }
.jca-act-menu a {
    display:flex;align-items:center;gap:8px;padding:7px 14px;
    color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s;
}
.jca-act-menu a:hover { background:#F1F5F9;color:#1D4ED8; }
.jca-act-menu a i { width:16px;text-align:center; }
.jca-act-divider { height:1px;background:#E2E8F0;margin:4px 0; }

.judiciary-customers-actions-index, #ajaxCrudDatatable,
#crud-datatable .panel-body, #crud-datatable .kv-grid-container,
#crud-datatable-container, #crud-datatable .table-responsive,
.kv-grid-table { overflow:visible !important; }

#ajaxCrudModal .modal-body { min-height:120px; }
#ajaxCrudModal .modal-dialog { transition:max-width .25s ease; }
</style>

<div class="judiciary-customers-actions-index">

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'toggleData' => false,
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} إجراء</span>',
            'columns' => require __DIR__ . '/_columns.php',
            'toolbar' => [
                [
                    'content' =>
                        Html::a('<i class="fa fa-plus"></i> إضافة إجراء', ['create'], ['class' => 'btn btn-success', 'role' => 'modal-remote']) .
                        Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'تحديث']) .
                        ExportButtons::widget([
                            'excelRoute' => ['export-excel'],
                            'pdfRoute' => ['export-pdf'],
                        ])
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-gavel"></i> إجراءات العملاء القضائية <span class="badge">' . $searchCounter . '</span>',
            ],
        ]) ?>
    </div>
</div>

<!-- BS5 Modal -->
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer"></div>
    </div>
  </div>
</div>

<?php
$js = <<<'JS'
(function($) {
    'use strict';

    var $modal     = $('#ajaxCrudModal');
    var $dialog    = $modal.find('.modal-dialog');
    var $title     = $modal.find('.modal-title');
    var $body      = $modal.find('.modal-body');
    var $footer    = $modal.find('.modal-footer');
    var modalEl    = $modal[0];
    var bsModal    = null;

    var SPINNER = '<div style="text-align:center;padding:40px">'
                + '<i class="fa fa-spinner fa-spin" style="font-size:28px;color:#800020"></i>'
                + '<div style="margin-top:10px;color:#64748B;font-size:13px">جاري التحميل...</div>'
                + '</div>';

    function getModal() {
        if (!bsModal && typeof bootstrap !== 'undefined') {
            bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
        }
        return bsModal;
    }

    function showModal() {
        var m = getModal();
        if (m) m.show();
    }

    function hideModal() {
        var m = getModal();
        if (m) m.hide();
    }

    function setSize(size) {
        $dialog.removeClass('modal-sm modal-lg modal-xl');
        if (size === 'large' || size === 'lg') $dialog.addClass('modal-lg');
        else if (size === 'xl') $dialog.addClass('modal-xl');
        else if (size === 'small' || size === 'sm') $dialog.addClass('modal-sm');
    }

    function renderResponse(data) {
        if (typeof data === 'string') {
            $title.text('');
            $body.html(data);
            $footer.html('');
            return;
        }

        if (data.forceClose) {
            hideModal();
            refreshGrid();
            return;
        }

        if (data.title)   $title.html(data.title);
        if (data.content) $body.html(data.content);
        if (data.footer)  $footer.html(data.footer);
        if (data.size)    setSize(data.size);

        initScriptsInBody();
    }

    function initScriptsInBody() {
        $body.find('script').each(function() {
            try { $.globalEval(this.text || this.textContent || this.innerHTML || ''); } catch(e) {}
        });
    }

    function refreshGrid() {
        if ($.pjax) {
            var $pjax = $('#judiciary-grid-pjax');
            if ($pjax.length) {
                $.pjax.reload({ container: '#judiciary-grid-pjax', timeout: 5000 });
                return;
            }
            $pjax = $('[id$="-pjax"]').filter(function() {
                return $(this).find('#crud-datatable').length > 0;
            }).first();
            if ($pjax.length) {
                $.pjax.reload({ container: '#' + $pjax.attr('id'), timeout: 5000 });
                return;
            }
        }
        location.reload();
    }

    function loadRemote(href) {
        $title.html('');
        $body.html(SPINNER);
        $footer.html('');
        setSize('');
        showModal();

        $.ajax({
            url: href,
            type: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(data) {
            renderResponse(data);
        }).fail(function(xhr) {
            var fallback = xhr.responseText || '';
            if (fallback && fallback.charAt(0) === '{') {
                try { renderResponse(JSON.parse(fallback)); return; } catch(e) {}
            }
            if (fallback) {
                $body.html(fallback);
                $footer.html('');
            } else {
                $body.html(
                    '<div class="text-center" style="padding:30px;color:#DC2626">'
                    + '<i class="fa fa-exclamation-triangle" style="font-size:28px;display:block;margin-bottom:8px"></i>'
                    + 'حدث خطأ في تحميل المحتوى'
                    + '</div>'
                );
            }
        });
    }

    function submitForm($form) {
        var action = $form.attr('action');
        var hasFile = $form.find('input[type="file"]').length > 0;
        var formData;

        if (hasFile) {
            formData = new FormData($form[0]);
        } else {
            formData = $form.serialize();
        }

        var ajaxOpts = {
            url: action,
            type: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        };

        if (hasFile) {
            ajaxOpts.data = formData;
            ajaxOpts.processData = false;
            ajaxOpts.contentType = false;
        } else {
            ajaxOpts.data = formData;
        }

        $footer.find('[type="submit"]').prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

        $.ajax(ajaxOpts).done(function(data) {
            renderResponse(data);
        }).fail(function(xhr) {
            var fallback = xhr.responseText || '';
            try { renderResponse(JSON.parse(fallback)); return; } catch(e) {}
            $body.html(fallback || '<div class="text-danger text-center" style="padding:20px">حدث خطأ أثناء الحفظ</div>');
            $footer.find('[type="submit"]').prop('disabled', false).html('<i class="fa fa-save"></i> حفظ');
        });
    }

    // --- Event Handlers ---

    $(document).on('click', '[role="modal-remote"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var href = $(this).attr('href') || $(this).data('url');
        if (!href) return;
        loadRemote(href);
    });

    $modal.on('click', '[role="modal-remote"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var href = $(this).attr('href') || $(this).data('url');
        if (!href) return;
        loadRemote(href);
    });

    $modal.on('click', '[type="submit"]', function(e) {
        e.preventDefault();
        var $form = $body.find('form');
        if (!$form.length) return;

        var evt = $.Event('beforeSubmit');
        $form.trigger(evt);
        if (evt.isDefaultPrevented()) return;

        submitForm($form);
    });

    $modal.on('submit', 'form', function(e) {
        e.preventDefault();
        var $form = $(this);

        var evt = $.Event('beforeSubmit');
        $form.trigger(evt);
        if (evt.isDefaultPrevented()) return;

        submitForm($form);
    });

    // Action dropdown handlers
    $(document).on('click', '.jca-act-trigger', function(e) {
        e.stopPropagation();
        var $wrap = $(this).closest('.jca-act-wrap');
        var $menu = $wrap.find('.jca-act-menu');
        var wasOpen = $wrap.hasClass('open');
        $('.jca-act-wrap.open').removeClass('open');
        if (!wasOpen) {
            $wrap.addClass('open');
            var r = this.getBoundingClientRect();
            $menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
        }
    });
    $(document).on('click', function() { $('.jca-act-wrap.open').removeClass('open'); });
    $(document).on('click', '.jca-act-menu a', function() { $('.jca-act-wrap.open').removeClass('open'); });

    // Delete confirmation via modal-remote with post method
    $(document).on('click', '[role="modal-remote"][data-request-method="post"]', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var href = $(this).attr('href');
        var msg = $(this).data('confirm-message') || $(this).data('confirm') || 'هل أنت متأكد من الحذف؟';

        $title.html('<i class="fa fa-exclamation-triangle" style="color:#DC2626"></i> تأكيد');
        $body.html(
            '<div style="text-align:center;padding:20px;font-size:14px">' + msg + '</div>'
        );
        $footer.html(
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>' +
            '<button type="button" class="btn btn-danger" id="jca-confirm-delete"><i class="fa fa-trash"></i> حذف</button>'
        );
        showModal();

        $footer.off('click', '#jca-confirm-delete').on('click', '#jca-confirm-delete', function() {
            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            $.post(href).done(function(data) {
                hideModal();
                refreshGrid();
            }).fail(function() {
                hideModal();
                refreshGrid();
            });
        });
    });

})(jQuery);
JS;
$this->registerJs($js);
?>
