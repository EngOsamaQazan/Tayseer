<?php

use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\judiciary\models\JudiciarySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Judiciaries');
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
?>
<div class="judiciary-index">
    <div id="ajaxCrudDatatable">
        <?=
        GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'columns' => require(__DIR__ . '/_report_columns.php'),
            'summary' => '',
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-refresh"></i>', [''],
                            ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'Reset Grid']) .
                    '{toggleData}' .
                    ExportButtons::widget([
                        'excelRoute' => '/judiciary/judiciary/export-report-excel',
                        'pdfRoute'   => '/judiciary/judiciary/export-report-pdf',
                    ])
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading'=>'<h4>'.Yii::t('app','Judiciary Total').":{$counter}</h4>",
            ]
        ])
        ?>
    </div>
</div>
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
