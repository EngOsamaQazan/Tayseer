<?php
/**
 * تبويب القضايا — يُعرض داخل الشاشة الموحدة
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use common\helper\Permissions;
use backend\widgets\ExportButtons;
?>

<?= $this->render('_search', ['model' => $searchModel]) ?>

<?php Pjax::begin(['id' => 'judiciary-pjax', 'timeout' => 10000]) ?>
<div id="ajaxCrudDatatable">
    <?= GridView::widget([
        'id' => 'crud-datatable',
        'dataProvider' => $dataProvider,
        'toggleData' => false,
        'columns' => (require __DIR__ . '/_columns.php'),
        'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} قضية</span>',
        'pjax' => true,
        'pjaxSettings' => [
            'options' => ['id' => 'judiciary-grid-pjax'],
            'neverTimeout' => true,
        ],
        'toolbar' => [
            [
                'content' =>
                    (Permissions::can(Permissions::JUD_CREATE)
                        ? Html::a('<i class="fa fa-bolt"></i> إدخال مجمّع', ['batch-actions'], ['class' => 'btn btn-warning', 'style' => 'font-weight:600']) .
                          Html::a('<i class="fa fa-plus"></i> إضافة إجراء', ['/judiciaryCustomersActions/judiciary-customers-actions/create'], ['class' => 'btn btn-success', 'role' => 'modal-remote'])
                        : '') .
                    Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                    ExportButtons::widget([
                        'excelRoute' => '/judiciary/judiciary/export-cases-excel',
                        'pdfRoute'   => '/judiciary/judiciary/export-cases-pdf',
                    ])
            ],
        ],
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'panel' => [
            'heading' => '<i class="fa fa-gavel"></i> القضايا <span class="badge">' . $counter . '</span>',
        ],
    ]) ?>
</div>
<?php Pjax::end() ?>
