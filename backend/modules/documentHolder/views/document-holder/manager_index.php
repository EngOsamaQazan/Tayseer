<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel common\models\DocumentHolderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Document Holders');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

?>
    <div class="document-holder-index">
        <div id="ajaxCrudDatatable">
            <?= GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'summary' => '',
                'columns' => require(__DIR__ . '/_columns.php'),
                'toolbar' => [
                    ['content' =>
                        Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'],
                            ['title' => 'Create new Document Holders', 'class' => 'btn btn-default']) .
                        Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''],
                            ['class' => 'btn btn-default', 'title' => 'Reset Grid']) .
                        '{toggleData}' .
                        ExportButtons::widget([
                            'excelRoute' => ['export-manager-excel'],
                            'pdfRoute' => ['export-manager-pdf'],
                        ])
                    ],
                ],
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
                'panel' => [
                    'type' => 'default',
                ]
            ]) ?>
        </div>
    </div>
<?php Modal::begin([
    "id" => "ajaxCrudModal",
    "footer" => "",// always need it for jquery plugin
]) ?>
<?php Modal::end(); ?>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('click','#managerApproved',function(){
let id = $(this).attr('data-id');
$.post('manager-approved',{id:id},function(d){
 location.reload();
})
});
$(document).on('click','#employee-approved',function(){
let id = $(this).attr('data-id');
$.post('employee-approved',{id:id},function(){
 location.reload();
})
});
SCRIPT
)
?>