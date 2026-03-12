<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;

/* @var $this yii\web\View */
/* @var $searchModel common\models\LeaveRequestSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Leave Requests');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

?>
    <div class="leave-request-index">
        <div id="ajaxCrudDatatable">
            <?= GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'Reason',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'start_at',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'end_at',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'leave_policy',
                        'value' => 'leavePolicy.title'
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'status',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'created_by',
                        'value' => 'createdBy.username'
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'notes',
                        'label' => 'الملاحضات',
                        'options' => [
                            'style' => 'width:15%',
                        ],
                        'value' => function ($model) {
                            if ($model->status != 'approved') {
                                return '<button type="button" class="glyphicon glyphicon-ok "  id = "aproved" data-id = "' . $model->id . ' " >

</button> 
<button type="button" class="glyphicon glyphicon-remove "  id = "reject" data-id = "' . $model->id . ' " >

</button>

 ';
                            } else {
                                return '';
                            }

                        },
                        'format' => 'raw',

                    ],
                ],
                'summary' => '',
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
                'panel' => [
                    'heading' => '',
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
$(document).on('click','#aproved',function(){
 let id =  $(this).attr('data-id');

   $.post('aproved',{id:id},function(response){
  
         if(response == 1){
          location.reload();
         }
        });
});

$(document).on('click','#reject',function(){
 let id =  $(this).attr('data-id');

   $.post('reject',{id:id},function(response){
  
         if(response == 1){
          location.reload();
         }
        });
})
SCRIPT
)
?>