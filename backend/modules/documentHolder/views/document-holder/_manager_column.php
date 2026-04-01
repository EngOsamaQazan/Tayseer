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
        'attribute' => 'approved',
        'value' => function ($model) {
            $string = '';
            if (Yii::$app->user->can('مدير') && $model->manager_approved == 0) {
                $string .= '<button type="button" class="btn btn-info" id="managerApproved" data-id="' . $model->id . '"><i class="fa fa-check"></i> موافقة المدير</button> ';
            }
            if (Yii::$app->user->id == $model->created_by  && $model->approved_by_employee == 0) {
                $string .= '<button type="button" class="btn btn-success" id="employee-approved" data-id="' . $model->id . '"><i class="fa fa-check"></i> موافقة الموظف</button> ';
            }
            return $string;

        },
        'format' => 'raw',
    ],

];