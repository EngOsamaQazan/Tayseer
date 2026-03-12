<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\judiciary\models\JudiciarySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Judiciaries');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);
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
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''],
                            ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'Reset Grid']) .
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
<?php
Modal::begin([
    "id" => "ajaxCrudModal",
    "footer" => "", // always need it for jquery plugin
])
?>
<?php Modal::end(); ?>
