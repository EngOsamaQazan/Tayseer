<?php
/**
 * تبويب إجراءات الأطراف — يُعرض عبر AJAX داخل الشاشة الموحدة
 */
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use backend\widgets\ExportButtons;

/* @var $searchModel \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActionsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchCounter int */
?>

<style>
#crud-datatable-actions .kv-grid-table { table-layout: fixed !important; width: 100% !important; }
#crud-datatable-actions .kv-grid-table td,
#crud-datatable-actions .kv-grid-table th { word-wrap: break-word; overflow-wrap: break-word; }
#crud-datatable-actions .jca-notes-cell {
    max-width: 200px; overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; cursor: pointer; font-size: 11px; color: #475569; direction: rtl;
}
#crud-datatable-actions .jca-notes-cell:hover { white-space: normal; background: #FFFBEB; }
#crud-datatable-actions .jca-name-cell {
    overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; font-size: 12px;
}
#crud-datatable-actions .jca-name-cell:hover { white-space: normal; background: #F0F9FF; }

/* ═══ Responsive ═══ */
@media (max-width:992px) {
    #crud-datatable-actions .kv-grid-container { overflow-x:auto !important; -webkit-overflow-scrolling:touch; }
    #crud-datatable-actions .kv-grid-table { min-width:650px; }
}
@media (max-width:767px) {
    #crud-datatable-actions .kv-grid-container { overflow:visible !important; }
    #crud-datatable-actions .kv-grid-table { min-width:0; table-layout:auto !important; }
    #crud-datatable-actions .kv-grid-table thead { display:none; }
    #crud-datatable-actions .kv-grid-table tbody tr {
        display:block; background:#fff; border:1px solid #E2E8F0;
        border-radius:10px; margin-bottom:8px; padding:10px 12px;
        box-shadow:0 1px 3px rgba(0,0,0,.04);
    }
    #crud-datatable-actions .kv-grid-table tbody tr:hover { background:#FFFBEB; }
    #crud-datatable-actions .kv-grid-table tbody td {
        display:flex; justify-content:space-between; align-items:center;
        padding:3px 0 !important; border:none !important; font-size:12px;
        white-space:normal !important; max-width:none !important;
        overflow:visible !important;
    }
    #crud-datatable-actions .kv-grid-table tbody td::before {
        content:attr(data-label); font-weight:600; color:#64748B;
        font-size:11px; min-width:75px; flex-shrink:0;
    }
    #crud-datatable-actions .kv-grid-table tbody td:last-child {
        justify-content:flex-end; padding-top:6px !important;
        margin-top:4px; border-top:1px solid #F1F5F9 !important;
    }
    #crud-datatable-actions .kv-grid-table .filters { display:none; }
    #crud-datatable-actions .panel-heading { font-size:12px; padding:8px 10px !important; }
    #crud-datatable-actions .panel-heading .pull-right {
        float:none !important; margin-top:6px; display:flex; flex-wrap:wrap; gap:3px;
    }
    #crud-datatable-actions .pagination { flex-wrap:wrap; justify-content:center; gap:2px; }
    #crud-datatable-actions .pagination>li>a,
    #crud-datatable-actions .pagination>li>span {
        padding:3px 6px; font-size:10px; min-width:28px; min-height:28px;
        display:inline-flex; align-items:center; justify-content:center;
    }
    .jca-act-menu { min-width:140px; }
    .jca-act-menu a { padding:10px 14px; font-size:13px; min-height:44px; }
}
@media (max-width:480px) {
    #crud-datatable-actions .kv-grid-table tbody td { font-size:11px; }
    #crud-datatable-actions .kv-grid-table tbody td::before { font-size:10px; min-width:60px; }
}
</style>

<?= $this->render('@backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search', ['model' => $searchModel]) ?>

<div id="ajaxCrudDatatable-actions">
    <?= GridView::widget([
        'id' => 'crud-datatable-actions',
        'dataProvider' => $dataProvider,
        'toggleData' => false,
        'summary' => '<span style="font-size:12px;color:#64748B">عرض {begin}–{end} من {totalCount} إجراء</span>',
        'columns' => require Yii::getAlias('@backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_columns.php'),
        'toolbar' => [
            [
                'content' =>
                    Html::a('<i class="fa fa-plus"></i> إضافة إجراء', ['/judiciaryCustomersActions/judiciary-customers-actions/create'], ['class' => 'btn btn-success', 'role' => 'modal-remote']) .
                    Html::a('<i class="fa fa-refresh"></i>', ['/judiciaryCustomersActions/judiciary-customers-actions/index'], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                    ExportButtons::widget([
                        'excelRoute' => '/judiciary/judiciary/export-actions-excel',
                        'pdfRoute'   => '/judiciary/judiciary/export-actions-pdf',
                    ])
            ],
        ],
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'panel' => [
            'type' => 'default',
            'heading' => '<i class="fa fa-gavel"></i> إجراءات العملاء القضائية <span class="badge">' . $searchCounter . '</span>',
        ],
    ]) ?>
</div>

<script>
$('#lh-badge-actions').text('<?= $searchCounter ?>');
</script>
