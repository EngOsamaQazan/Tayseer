<?php

use yii\helpers\Url;
use common\helper\Permissions;

return [

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'date',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'amount',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'notes',
    ],

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'value' => 'createdBy.username'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'المتاح للقبض',
        'value' => function ($model) {
            $d1 = new DateTime($model->date);
            $d2 = new DateTime(date('Y-m-d'));
            $interval = $d1->diff($d2);
            $diffInMonths = $interval->m; //4

            $revares_courts = backend\modules\financialTransaction\models\FinancialTransaction::find()->where(['contract_id' => $model->contract_id])->andWhere(['income_type' => 11])->all();
            $revares = 0;
            foreach ($revares_courts as $revares_court) {
                $revares = $revares + $revares_court->amount;
            }
            $diffInMonths = $diffInMonths + 1;
            $value = ($diffInMonths * $model->amount) - $revares;
            return $value;
        }
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'template' => (Permissions::can(Permissions::COLL_VIEW) ? '{view}' : '')
            . (Permissions::can(Permissions::COLL_UPDATE) ? '{update}' : '')
            . (Permissions::can(Permissions::COLL_DELETE) ? '{delete}' : ''),
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['title' => 'View', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => 'Update', 'data-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => 'Delete',
            'data-confirm' => false, 'data-method' => false,
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'Are you sure?',
            'data-confirm-message' => 'Are you sure want to delete this item'],
    ],

];   