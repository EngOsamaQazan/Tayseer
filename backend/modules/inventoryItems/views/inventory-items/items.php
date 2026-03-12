<?php
/**
 * شاشة الأصناف — عرض وإدارة كل المنتجات
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\widgets\ExportButtons;

$this->title = 'إدارة المخزون';
CrudAsset::register($this);
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'items']) ?>

<style>
/* ── تعريف المتغيرات المطلوبة من fin-transactions.css ── */
.inv-page {
    color: #1e293b;
    --fin-font: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    --fin-credit: #166534;
    --fin-credit-bg: #dcfce7;
    --fin-debit: #991b1b;
    --fin-debit-bg: #fee2e2;
    --fin-gold: #92400e;
    --fin-gold-bg: #fef3c7;
    --fin-neutral: #475569;
    --fin-neutral-bg: #f1f5f9;
    --fin-border: #cbd5e1;
    --fin-bg: #f8fafc;
    --fin-surface: #ffffff;
    --fin-text: #1e293b;
    --fin-text2: #475569;
    --fin-r: 10px;
    --fin-r-sm: 6px;
    --fin-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
    --fin-shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    --fin-primary: #075985;
    --clr-primary-400: #075985;
    --clr-primary-600: #0c4a6e;
}
.inv-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.inv-badge--draft { background: #e2e8f0; color: #334155; }
.inv-badge--pending { background: #fef3c7; color: #92400e; }
.inv-badge--approved { background: #dcfce7; color: #166534; }
.inv-badge--rejected { background: #fee2e2; color: #991b1b; }
.inv-approve-btn { background: #166534; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; margin-left: 4px; }
.inv-approve-btn:hover { background: #14532d; }
.inv-reject-btn { background: #991b1b; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
.inv-reject-btn:hover { background: #7f1d1d; }
.inv-stock-tag { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }
.inv-stock-ok { background: #dcfce7; color: #166534; }
.inv-stock-low { background: #fee2e2; color: #991b1b; }
.inv-stock-zero { background: #e2e8f0; color: #475569; }
.inv-page .panel { border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.inv-page .panel-heading { background: #f1f5f9 !important; border-bottom: 1px solid #cbd5e1; font-weight: 700; color: #0f172a; border-radius: 10px 10px 0 0 !important; }
.inv-page .kv-grid-table th { background: #f1f5f9; font-weight: 700; font-size: 13px; color: #0f172a; border-bottom: 2px solid #cbd5e1; }
.inv-page .kv-grid-table th a { color: #0f172a !important; text-decoration: none; }
.inv-page .kv-grid-table th a:hover { color: #075985 !important; }
.inv-page .kv-grid-table td { font-size: 13.5px; vertical-align: middle; color: #1e293b; }
.inv-page .kv-grid-table td a { color: #075985; }
.inv-page .panel-heading .badge { color: #fff; }
.inv-page .panel-footer { background: #f8fafc; border-top: 1px solid #cbd5e1; }
</style>

<div class="inv-page">

    <!-- Alpine.js: إشعار نجاح العملية -->
    <div x-data="{ show: false }" x-show="show" x-transition x-cloak
         @show-notif.window="show = true; setTimeout(() => show = false, 2500)"
         style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;background:#166534;color:#fff;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        <i class="fa fa-check-circle"></i> تمت العملية بنجاح
    </div>

    <section class="fin-actions" aria-label="إجراءات" style="margin-bottom: 14px">
        <?php if (Permissions::can(Permissions::INVITEM_CREATE)): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>صنف جديد</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة صنف جديد', 'role' => 'modal-remote',
            ]) ?>
            <?= Html::a('<i class="fa fa-cubes"></i> <span>إضافة دفعة</span>', ['batch-create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة مجموعة أصناف دفعة واحدة', 'role' => 'modal-remote',
                'style' => 'background:#0ea5e9;margin-right:6px',
            ]) ?>
        </div>
        <?php endif ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-refresh"></i> <span>تحديث</span>', ['items'], [
                'class' => 'fin-btn fin-btn--reset',
            ]) ?>
        </div>
    </section>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => require(__DIR__ . '/_columns.php'),
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-table"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> صنف</span>',
            'toolbar' => [['content' =>
                ExportButtons::widget([
                    'excelRoute' => ['export-items-excel'],
                    'pdfRoute'   => ['export-items-pdf'],
                ]) . ' {toggleData}'
            ]],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-cubes"></i> أصناف المخزون <span class="badge" style="background:#0369a1;margin-right:6px">' . $dataProvider->totalCount . '</span>',
            ],
            'pjax' => true,
            'pjaxSettings' => ['options' => ['id' => 'crud-datatable-pjax']],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'options' => ['class' => 'modal fade', 'tabindex' => false], 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end(); ?>

<?php
$approveBaseUrl = Url::to(['approve', 'id' => '__ID__']);
$rejectBaseUrl  = Url::to(['reject', 'id' => '__ID__']);
$csrfToken  = Yii::$app->request->csrfToken;

$js = <<<JS
// إظهار إشعار نجاح بعد إغلاق المودال
$('#ajaxCrudModal').on('hidden.bs.modal', function () {
    // بعد إغلاق المودال تلقائياً (forceClose) نعرض إشعار
    if (window._itemSaveSuccess) {
        window._itemSaveSuccess = false;
    }
});
// تتبع نجاح الحفظ
$(document).ajaxComplete(function(e, xhr, settings) {
    try {
        var resp = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
        if (resp && resp.forceClose && resp.forceReload) {
            /* OLD jQuery - replaced by Alpine.js
            var notif = $('<div style="..."><i class="fa fa-check-circle"></i> تمت العملية بنجاح</div>');
            $('body').append(notif);
            setTimeout(function(){ notif.fadeOut(400, function(){ notif.remove(); }); }, 2500);
            */
            window.dispatchEvent(new Event('show-notif'));
        }
    } catch(ex) {}
});
$(document).on('click', '.inv-approve-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    if (!confirm('هل تريد اعتماد هذا الصنف؟')) return;
    $.post('{$approveBaseUrl}'.replace('__ID__', id), { _csrf: '{$csrfToken}' }, function(resp){
        if (resp.success) $.pjax.reload({container: '#crud-datatable-pjax'});
        else alert(resp.message || 'خطأ');
    }, 'json');
});
$(document).on('click', '.inv-reject-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    var reason = prompt('سبب الرفض (اختياري):');
    if (reason === null) return;
    $.post('{$rejectBaseUrl}'.replace('__ID__', id), { reason: reason, _csrf: '{$csrfToken}' }, function(resp){
        if (resp.success) $.pjax.reload({container: '#crud-datatable-pjax'});
        else alert(resp.message || 'خطأ');
    }, 'json');
});
JS;
$this->registerJs($js);
?>
