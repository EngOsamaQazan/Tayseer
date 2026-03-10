<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\modules\customers\models\Customers;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\helpers\FlatpickrWidget;
use backend\modules\customers\models\ContractsCustomers;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\Judiciary */
/* @var $form yii\widgets\ActiveForm */


if (!$model->isNewRecord) {

    $form = ActiveForm::begin([
        'method' => 'post',
        'action' => 'update?id=' . $model->id
    ]);
} else {
    $form = ActiveForm::begin();
}
?>
<div class="row">
    <div class="col-lg-6">
        <?=
            $form->field($model, 'court_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(Court::find()->all(), 'id', 'name'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a court.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
        ?>
    </div>
    <div class="col-lg-6">
        <?=
            $form->field($model, 'type_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(JudiciaryType::find()->all(), 'id', 'name'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a type.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
        ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <?=
            $form->field($model, 'lawyer_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(Lawyers::find()->all(), 'id', 'name'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a lawyer.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
        ?>
    </div>
    <div class="col-lg-6">
        <?= $form->field($model, 'lawyer_cost')->textInput() ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <?=
            $form->field($model, 'year')->widget(kartik\select2\Select2::classname(), [
                'data' => $model->year(),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a year.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ])->label('السنة');
        ?>
    </div>
    <div class="col-lg-6">
        <?= $form->field($model, 'judiciary_number')->textInput()->label('رقم القضية') ?>

    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <?=
            $form->field($model, 'income_date')->widget(FlatpickrWidget::class, [
                'pluginOptions' => [
                    'dateFormat' => 'Y-m-d'
                ]
            ])->label('تاريخ الورود');
        ?>


    </div>
</div>

<?php if (!Yii::$app->request->isAjax) { ?>
    <div class="form-group" style="display: inline">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
<?php } ?>
<?php
if (!$model->isNewRecord) {
    ?>
    <div style="display: inline">
        <a class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false"
            aria-controls="collapseExample">
            انشاء حركات عملاء
        </a>
        <?= Html::a(Yii::t('app','طباعة سندات التنفيذ'), ['/judiciary/judiciary/print-case', 'id' => $model->id], ['class'=>'btn btn-primary']); ?>
    </div>`2
<?php } ?>

<?php ActiveForm::end(); ?>
<?php
$data = ContractsCustomers::find()
    ->select(['c.id', 'c.name'])
    ->alias('cc')
    ->innerJoin('{{%customers}} c', 'c.id=cc.customer_id')
    ->where(['cc.contract_id' => $model->contract_id])
    ->createCommand()->queryAll();

if (!$model->isNewRecord) {
    ?>
    <div class="collapse" id="collapseExample">
        <div class="card card-body">
            <?php
            $form = ActiveForm::begin([
                'method' => 'post',
                'action' => 'customer-action?judiciary=' . $model->id . '&contract_id=' . $model->contract_id
            ]);
            ?>
            <div class="row">
                <div class="col-lg-6">
                    <?=
                        $form->field($modelCustomerAction, 'customers_id')->widget(kartik\select2\Select2::classname(), [
                            'options' => [
                                'placeholder' => 'ابحث بالاسم أو الرقم الوطني...',
                            ],
                            'pluginOptions' => [
                                'allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
                                'ajax' => [
                                    'url' => \yii\helpers\Url::to(['/customers/search-customers']),
                                    'dataType' => 'json', 'delay' => 250,
                                    'data' => new \yii\web\JsExpression('function(p){return{q:p.term}}'),
                                    'processResults' => new \yii\web\JsExpression('function(d){return d}'),
                                    'cache' => true,
                                ],
                                'templateResult' => new \yii\web\JsExpression("function(i){if(i.loading)return i.text;var h='<div><b>'+i.text+'</b>';if(i.id_number)h+=' <small style=\"color:#64748b\">· '+i.id_number+'</small>';if(i.phone)h+=' <small style=\"color:#0891b2\">☎ '+i.phone+'</small>';return $(h+'</div>')}"),
                                'templateSelection' => new \yii\web\JsExpression("function(i){return i.text||i.id}"),
                            ],
                        ])->label('اسم العميل');
                    ?>
                </div>
                <div class="col-lg-6">
                    <?=
                        $form->field($modelCustomerAction, 'judiciary_actions_id')->widget(kartik\select2\Select2::classname(), [
                            'data' => yii\helpers\ArrayHelper::map(JudiciaryActions::find()->all(), 'id', 'name'),
                            'language' => 'de',
                            'options' => [
                                'placeholder' => 'Select a judiciary action.',
                            ],
                            'pluginOptions' => [
                                'allowClear' => true
                            ],
                        ]);
                    ?>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div>
                        <?=
                            $form->field($modelCustomerAction, 'action_date')->widget(FlatpickrWidget::class, [
                                'pluginOptions' => [
                                    'dateFormat' => 'Y-m-d'
                                ]
                            ])->label('تاريخ الحركة');
                        ?>
                    </div>
                </div>
            </div>
            <?= $form->field($modelCustomerAction, 'note')->textarea(['rows' => 6]) ?>
            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($modelCustomerAction->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
    <div>
        <?php
        $dataProvider = new yii\data\ArrayDataProvider([
            'key' => 'id',
            'allModels' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()->Where(['judiciary_id' => $model->id])->all(),
        ])
            ?>

        <?=
            GridView::widget([
                'id' => 'os_judiciary_customers_actions',
                'dataProvider' => $dataProvider,
                'summary' => '',

                'columns' => [
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'contract_id',
                        'value' => function ($model) {
                        return \common\helper\FindJudicary::findJudiciaryContract($model->judiciary_id);
                    },
                        'label' => 'رقم العقد'
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'customers_id ',
                        'value' => 'customers.name',
                        'label' => 'اسم العميل'
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'judiciary_actions_id',
                        'value' => 'judiciaryActions.name',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'note',
                        'format' => 'html',
                        'contentOptions' => [
                            'style' => 'max-width:150px; overflow: auto; white-space: normal; word-wrap: break-word;direction: rtl;'
                        ]
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
                        'attribute' => 'action_date',
                    ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'last_update_by',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'is_deleted',
                    // ],
    
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'dropdown' => false,
                        'vAlign' => 'middle',
                        'urlCreator' => function ($action, $model, $key, $index) {
                        if ($action == "delete") {
                            return Url::to(['judiciary/delete-customer-action', 'id' => $model->id, 'judiciary' => $model->judiciary_id]);
                        } else {
                            return Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action?contractID=' . $model->contract_id . '&id=' . $model->id]);
                        }
                    },
                        'viewOptions' => ['role' => 'modal-remote', 'title' => 'View', 'data-toggle' => 'tooltip'],
                        'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                        'deleteOptions' => [
                            'role' => 'modal-remote',
                            'title' => 'Delete',
                            'data-confirm' => false,
                            'data-method' => false,
                            // for overide yii data api
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
            ])
            ?>
    </div>
<?php } ?>
</div>
</div>