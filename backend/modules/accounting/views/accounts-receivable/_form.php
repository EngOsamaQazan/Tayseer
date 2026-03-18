<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\Receivable;

$accounts = Account::getLeafAccounts();
$customers = ArrayHelper::map(
    \backend\modules\customers\models\Customers::find()->orderBy(['first_name' => SORT_ASC])->all(),
    'id',
    function ($m) { return $m->first_name . ' ' . ($m->last_name ?? ''); }
);

$form = ActiveForm::begin([
    'id' => 'receivable-form',
    'options' => ['class' => 'jadal-form'],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{hint}\n{error}",
        'labelOptions' => ['class' => 'control-label'],
    ],
]);
?>

<div class="box box-primary">
    <div class="box-body">
        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-arrow-circle-down"></i> بيانات الذمة المدينة</legend>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'customer_id')->widget(Select2::class, [
                        'data' => $customers,
                        'options' => ['placeholder' => 'اختر العميل...'],
                        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'account_id')->widget(Select2::class, [
                        'data' => $accounts,
                        'options' => ['placeholder' => 'اختر الحساب...'],
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'amount')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'placeholder' => '0.00']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'due_date')->textInput(['type' => 'date']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'status')->widget(Select2::class, [
                        'data' => Receivable::getStatuses(),
                        'pluginOptions' => ['allowClear' => false, 'dir' => 'rtl'],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'contract_id')->textInput(['type' => 'number', 'placeholder' => 'رقم العقد (اختياري)']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'description')->textarea(['rows' => 2, 'placeholder' => 'وصف الذمة...']) ?>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="box-footer jadal-form-actions">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-save"></i> حفظ' : '<i class="fa fa-check"></i> تحديث',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
