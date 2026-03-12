<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel common\models\LeaveRequestSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Leave Requests');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

?>
<div class="leave-request-index">
    <div id="ajaxCrudDatatable">
        <?=GridView::widget([
            'id'=>'crud-datatable',
            'dataProvider' => $dataProvider,
            'pjax'=>true,
            'columns' => require(__DIR__.'/_columns.php'),
            'summary'=>'',
            'toolbar'=> [
                ['content'=>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'],
                    ['role'=>'modal-remote','title'=> 'Create new Leave Requests','class'=>'btn btn-default']).
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''],
                    [ 'class'=>'btn btn-default', 'title'=>'Reset Grid']).
                    '{toggleData}'.
                    ExportButtons::widget([
                        'excelRoute' => 'export-excel',
                        'pdfRoute'   => 'export-pdf',
                    ])
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '',
            ]
        ])?>
    </div>
</div>
<?php Modal::begin([
    "id"=>"ajaxCrudModal",
    "footer"=>"",// always need it for jquery plugin
])?>
<?php Modal::end(); ?>
