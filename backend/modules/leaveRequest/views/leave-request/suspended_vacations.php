<?php

use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\LeaveRequestSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Leave Requests');
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

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
                                return '<button type="button" class="btn btn-sm btn-success js-suspended-approve" data-id="' . $model->id . '"><i class="fa fa-check"></i></button> '
                                    . '<button type="button" class="btn btn-sm btn-danger js-suspended-reject" data-id="' . $model->id . '"><i class="fa fa-times"></i></button>';
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
<?php
$this->registerJs(<<<SCRIPT
$(document).on('click','.js-suspended-approve',function(){
 let id =  $(this).attr('data-id');

   $.post('aproved',{id:id},function(response){
  
         if(response == 1){
          location.reload();
         }
        });
});

$(document).on('click','.js-suspended-reject',function(){
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
