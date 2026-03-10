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
