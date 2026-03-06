<?php
/**
 * قائمة إجراءات العملاء القضائية - بناء من الصفر
 * تعرض جدول جميع الإجراءات مع بحث متقدم
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use backend\widgets\ExportButtons;

CrudAsset::register($this);
$this->title = 'إجراءات العملاء القضائية';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
/* ═══ Custom action dropdown — no clipping ═══ */
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

/* Ensure grid doesn't clip */
.judiciary-customers-actions-index, #ajaxCrudDatatable,
#crud-datatable .panel-body, #crud-datatable .kv-grid-container,
#crud-datatable-container, #crud-datatable .table-responsive,
.kv-grid-table { overflow:visible !important; }
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
                        Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
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

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<?php
$js = <<<'JS'
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
// Close on outside click
$(document).on('click', function() {
    $('.jca-act-wrap.open').removeClass('open');
});
// Close on menu item click
$(document).on('click', '.jca-act-menu a', function() {
    $('.jca-act-wrap.open').removeClass('open');
});
JS;
$this->registerJs($js);
?>
