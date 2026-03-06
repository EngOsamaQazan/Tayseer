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

<?= $this->render('@backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search', ['model' => $searchModel]) ?>

<div id="ajaxCrudDatatable-actions">
    <?= GridView::widget([
        'id' => 'crud-datatable-actions',
        'dataProvider' => $dataProvider,
        'toggleData' => false,
        'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} إجراء</span>',
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
            'heading' => '<i class="fa fa-gavel"></i> إجراءات العملاء القضائية <span class="badge">' . $searchCounter . '</span>',
        ],
    ]) ?>
</div>

<script>
$('#lh-badge-actions').text('<?= $searchCounter ?>');
</script>
