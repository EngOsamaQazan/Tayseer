<?php

use yii\helpers\Url;

return [
    /*  [
          'class' => '\kartik\grid\DataColumn',
          'attribute' => 'created_by',
      ],
      [
           'class'=>'\kartik\grid\DataColumn',
           'attribute'=>'updated_by',
       ],*/
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'created_at',
    // ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'updated_at',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'approved_by_manager',
        'value' => 'approvedByManager.username'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'approved_by_employee',
        'value' => function ($model) {
            if (empty($model->approved_by_employee)) {
                return 'لا';
            } else {
                return 'نعم';
            }
        }
    ],
    /* [
         'class'=>'\kartik\grid\DataColumn',
         'attribute'=>'approved_at',
     ],*/
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'reason',

    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type',
    ],    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'موافقة',
    'value' => function ($model) {
        $string = '';

        if (Yii::$app->user->can('مدير') && $model->manager_approved == 0) {
            $string .= '<button type="button" class="btn btn-info glyphicon glyphicon-ok "  id = "managerApproved" data-id = "' . $model->id . ' " >
موافقة المدير
</button> 

 ';
        }
        if (Yii::$app->user->id == $model->created_by  && $model->approved_by_employee == 0) {
            $string .= '<button type="button" class="  btn btn-success glyphicon glyphicon-ok "  id = "employee-approved" data-id = "' . $model->id . ' " >
موافقة الموظف
</button> 

 ';
        }
        return $string;

    },
    'format' => 'raw',
],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'template' => "{delete}",
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['title' => 'View', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'Update', 'data-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => 'Delete',
            'data-confirm' => false, 'data-method' => false,// for overide yii data api
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'Are you sure?',
            'data-confirm-message' => 'Are you sure want to delete this item'],
    ],

];   