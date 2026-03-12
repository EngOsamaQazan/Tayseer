<?php
use yii\helpers\Url;
use common\helper\Permissions;

return [
        // [
        // 'class'=>'\kartik\grid\DataColumn',
        // 'attribute'=>'id',
    // ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'name',
        'label'=>Yii::t('app','Name'),
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'phone_number',
        'label'=>Yii::t('app','Phone Number'),
    ],

    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'logo',
        'label'=>Yii::t('app','Logo'),
        'value'=>function($model){
    if(empty($model->logo)){
        return \yii\helpers\Html::img(Url::to([Yii::$app->params['companies_logo']]),['style' => "width: 50px;height:50px;", 'alt' => 'User Image','class'=>' img-circle']);
    }
    return \yii\helpers\Html::img(Url::to(['/'.$model->logo]),['style' => "width: 50px;height:50px;", 'alt' => 'User Image','class'=>' img-circle']);

        },
        'format'=>'html',
        'width'=>'10%',


    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'created_by',
        'value'=>'createdBy.username',
        'label'=>Yii::t('app','Created By'),
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
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'template' => (Permissions::can(Permissions::COMP_DELETE) ? '{delete}' : '') . (Permissions::can(Permissions::COMP_UPDATE) ? '{update}' : ''),
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['title'=>'View','data-toggle'=>'tooltip'],
        'updateOptions'=>['title'=>'Update', 'data-toggle'=>'tooltip'],
        'deleteOptions'=>['title'=>'Delete',
                          'data-confirm'=>false, 'data-method'=>false,// for overide yii data api
                          'data-request-method'=>'post',
                          'data-toggle'=>'tooltip',
                          'data-confirm-title'=>'Are you sure?',
                          'data-confirm-message'=>'Are you sure want to delete this item'], 
    ],

];   