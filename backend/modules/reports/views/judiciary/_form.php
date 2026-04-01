<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use yii\helpers\Url;
use kartik\grid\GridView;
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
        <?= $form->field($model, 'court_id')->dropDownList(yii\helpers\ArrayHelper::map(Court::find()->all(), 'id', 'name'), ['prompt' => '-- اختر المحكمة --', 'class' => 'form-control']) ?>
    </div>
    <div class="col-lg-6">
        <?= $form->field($model, 'type_id')->dropDownList(yii\helpers\ArrayHelper::map(JudiciaryType::find()->all(), 'id', 'name'), ['prompt' => '-- اختر النوع --', 'class' => 'form-control']) ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <?= $form->field($model, 'lawyer_id')->dropDownList(yii\helpers\ArrayHelper::map(Lawyers::find()->all(), 'id', 'name'), ['prompt' => '-- اختر المحامي --', 'class' => 'form-control']) ?>
    </div>
    <div class="col-lg-6">
        <?= $form->field($model, 'lawyer_cost')->textInput() ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <?= $form->field($model, 'year')->dropDownList($model->year(), ['prompt' => '-- السنة --', 'class' => 'form-control'])->label('السنة') ?>
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
        <a class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false"
            aria-controls="collapseExample">
            انشاء حركات عملاء
        </a>
        <?= Html::a(Yii::t('app','طباعة سندات التنفيذ'), ['/judiciary/judiciary/print-case', 'id' => $model->id], ['class'=>'btn btn-primary']); ?>
    </div>
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
    $this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
    $this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
        'depends' => [\yii\web\JqueryAsset::class],
    ]);
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
                    <?= $form->field($modelCustomerAction, 'customers_id')->textInput(['placeholder' => 'ابحث بالاسم أو الرقم الوطني...', 'class' => 'form-control'])->label('اسم العميل') ?>
                </div>
                <div class="col-lg-6">
                    <?= $form->field($modelCustomerAction, 'judiciary_actions_id')->dropDownList(yii\helpers\ArrayHelper::map(JudiciaryActions::find()->all(), 'id', 'name'), ['prompt' => '-- اختر الإجراء --', 'class' => 'form-control']) ?>
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
                        'viewOptions' => ['role' => 'modal-remote', 'data-pjax' => 0, 'title' => 'View', 'data-bs-toggle' => 'tooltip'],
                        'updateOptions' => ['role' => 'modal-remote', 'data-pjax' => 0, 'title' => 'Update', 'data-bs-toggle' => 'tooltip'],
                        'deleteOptions' => [
                            'role' => 'modal-remote',
                            'data-pjax' => 0,
                            'title' => 'Delete',
                            'data-confirm' => false,
                            'data-method' => false,
                            // for overide yii data api
                            'data-request-method' => 'post',
                            'data-bs-toggle' => 'tooltip',
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
</div>
<?php } ?>