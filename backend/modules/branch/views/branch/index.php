<?php

use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\branch\models\BranchSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'الفروع';
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);
?>
<div class="branch-index">
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'pjax' => true,
            'columns' => require(__DIR__ . '/_columns.php'),
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-plus"></i> إضافة فرع', ['create'], [
                        'role' => 'modal-remote',
                        'title' => 'إضافة فرع جديد',
                        'class' => 'btn btn-primary',
                    ]) .
                    Html::a('<i class="fa fa-redo"></i>', [''], [
                        'data-pjax' => 1,
                        'class' => 'btn btn-outline-secondary',
                        'title' => 'تحديث',
                    ]) .
                    '{toggleData}' .
                    ExportButtons::widget(['excelRoute' => 'export-excel', 'pdfRoute' => 'export-pdf'])
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'primary',
                'heading' => '<i class="fa fa-code-branch"></i> إدارة الفروع',
                'before' => '<em>* الفروع الموحدة — يشمل فروع الموظفين ومناطق العمل الجغرافية.</em>',
                'after' => BulkButtonWidget::widget([
                    'buttons' => Html::a('<i class="fa fa-trash"></i>&nbsp; حذف المحدد', ['bulk-delete'], [
                        'class' => 'btn btn-danger btn-xs',
                        'role' => 'modal-remote-bulk',
                        'data-confirm' => false,
                        'data-method' => false,
                        'data-request-method' => 'post',
                        'data-confirm-title' => 'تأكيد الحذف',
                        'data-confirm-message' => 'هل تريد حذف العناصر المحددة؟',
                    ]),
                ]) . '<div class="clearfix"></div>',
            ],
        ]) ?>
    </div>
</div>
<?php
\yii\bootstrap5\Modal::begin([
    'id' => 'ajaxCrudModal',
    'footer' => '',
]);
\yii\bootstrap5\Modal::end();
?>
