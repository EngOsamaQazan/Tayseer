<?php

use yii\helpers\Url;

use backend\modules\expenseCategories\models\ExpenseCategories;
use backend\modules\contracts\models\Contracts;
use backend\modules\financialTransaction\models\FinancialTransaction;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use mdm\admin\components\Helper;
use kartik\select2\Select2;
use backend\modules\incomeCategory\models\IncomeCategory;
return [
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'id',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'description',
        'value' => function ($model) {
            return !empty($model->description) ? $model->description : $model->bank_description;
        },
        'options' => [
            'style' => 'width:20%'
        ]
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'amount',
        'options' => [
            'style' => 'width:5%'
        ]
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type',
        'value' => function ($model) {
           // $type = ['', 'Income', 'Outcome'];
           // return Html::dropDownList('type', $model->type, $type, ['class' => 'form-control type', 'data-id' => $model->id]);
return ($model->type == 1) ? "Income" : "Outcome";
        },
      //  'format' => 'raw',
       // 'options' => [
        //    'style' => 'width:15%',
        //],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'category_id',
        'value' => function ($model) {
            $categoryItems = yii\helpers\ArrayHelper::map(ExpenseCategories::find()->all(), 'id', 'name');
            return '<div class="category-list" style="display:' . ($model->type == FinancialTransaction::TYPE_OUTCOME ? 'block' : 'none') . ' ">' . Html::dropDownList('category_id', $model->category_id, $categoryItems, ['class' => 'form-control category-change', 'data-id' => $model->id, 'prompt' => 'Select a category.']) . '</div>';
        },
        'format' => 'raw',
        'options' => [
            'style' => 'width:10%',
        ]
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'income_type',
        'value' => function ($model) {
            $type = yii\helpers\ArrayHelper::map(IncomeCategory::find()->all(), 'id', 'name');
            return '<div class="income-type-list" style="display:' . ($model->type == FinancialTransaction::TYPE_INCOME ? 'block' : 'none') . ' ">' . Html::dropDownList('income_type', $model->income_type, $type, ['class' => 'form-control income_type', 'data-id' => $model->id]) . '</div>';

        },
        'format' => 'raw',
        'options' => [
            'style' => 'width:10%'
        ]
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'value' => function ($model) {
            $contract = yii\helpers\ArrayHelper::map(Contracts::find()->all(), 'id', 'id');
            return '<div class="contract-id-list" style="display:' . ($model->income_type == 8 ? 'block' : 'none') . ' ">' . Html::dropDownList('contract_id', $model->contract_id, $contract, ['class' => 'form-control contract', 'data-id' => $model->id, 'prompt' => 'Select a contract.']) . '</div>';
        },
        'format' => 'raw',
        'options' => [
            'style' => 'width:10%'
        ],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'company_id',
        'value' => 'company.name',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'date',
        'options' => [
            'style' => 'width:10%'
        ]
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'notes',
        'options' => [
            'style' => 'width:10%',
        ],
        'value' => function ($model) {
            if (!empty($model->bank_description)) {
                return '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
notes
</button>';
            } else {
                return '';
            }

        },
        'format' => 'raw',

    ],

]; ?>
<div class="modal fade" id="exampleModal-<?=$model->id?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>