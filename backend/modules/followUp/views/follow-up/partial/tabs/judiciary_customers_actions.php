<?php

use yii\helpers\Html;
use yii\data\ActiveDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

?>


<div class="card card-body">
    <?php
    $custamerAction = new ActiveDataProvider([
        'query' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()->select([
            'os_judiciary_customers_actions.action_date',
            'os_judiciary.contract_id',
            'os_judiciary_customers_actions.judiciary_id',
            'os_judiciary_customers_actions.customers_id',
            'os_judiciary_customers_actions.note',
            'os_judiciary_customers_actions.created_by',
            'os_judiciary_customers_actions.judiciary_actions_id',
            'os_judiciary_customers_actions.id'

        ])
            ->innerJoin('os_judiciary', 'os_judiciary.id=os_judiciary_customers_actions.judiciary_id')
            ->where(['os_judiciary.contract_id' => $contract_id])->orderBy(['os_judiciary_customers_actions.action_date' => SORT_ASC])
    ]);
    ?>
    <?= GridView::widget([
        'id' => 'os_judiciary_customers_actions',
        'dataProvider' => $custamerAction,
        'summary' => '',
        'toolbar' => [
            [
                'content' =>
                Html::a('<i class="glyphicon glyphicon-plus"></i>', Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $contract_id]), ['role' => 'modal-remote', 'title' => 'إنشاء إجراءات قضائية', 'class' => 'btn btn-default'])


            ],
        ],
        'columns' => [
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'judiciary_id',
                'label' => Yii::t('app', 'judiciary'),
                'value' => function ($model) {
                    return \common\helper\FindJudicary::findJudiciaryNumberJudicary($model->judiciary_id) . '/' . \common\helper\FindJudicary::findYearJudicary($model->judiciary_id);
                }
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'label' => Yii::t('app', 'Court'),
                'value' => function ($model) {
                    return \common\helper\FindJudicary::findCourtJudicary($model->judiciary_id);
                }

            ],

            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'customers_id ',
                'value' => 'customers.name',
                'label' => Yii::t('app', 'Customers Id')
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'judiciary_actions_id',
                'value' => 'judiciaryActions.name',
                'label' => Yii::t('app', 'Judiciary Actions ID')

            ],

            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'note',
                'value' => function ($model) {
                    if (!empty($model->note)) {
                        return "<textarea rows=3' style=' resize: none;' disabled>{$model->note}</textarea>";
                    }
                },
                'format' => 'raw'
            ],
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
                'attribute' => 'created_by',
                'value' => 'createdBy.username'
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'اسم الوكيل',
                'value' => function ($model) {
                    return \common\helper\FindJudicary::findLawyerJudicary($model->judiciary_id);
                }
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'action_date',
                'label' => 'تاريخ الحركة'
            ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'dropdown' => false,
                'vAlign' => 'middle',
                'template' => '{delete} {update}',
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action == 'delete') {
                        return Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/' . $action, 'id' => $model->id]);
                    } else {
                        $contract_id = \common\helper\FindJudicary::findJudiciaryContract($model->judiciary_id);
                        return Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $model->id, 'contractID' => $contract_id]);
                    }
                },
                'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                'deleteOptions' => [
                    'role' => 'modal-remote', 'title' => 'Delete',
                    'data-confirm' => false, 'data-method' => false, // for overide yii data api
                    'data-request-method' => 'post',
                    'data-toggle' => 'tooltip',
                    'data-confirm-title' => 'Are you sure?',
                    'data-confirm-message' => 'Are you sure want to delete this item'
                ],
            ],
        ],

        'striped' => false,
        'condensed' => false,
        'responsive' => false,
        'export' => false,
        'panel' => [
            'type' => false,
            'heading' => false,
            'after' => false,
            'footer' => false
        ]

    ]) ?>
</div>