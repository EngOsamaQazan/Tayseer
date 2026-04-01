<?php
/**
 * نموذج إجراء عميل قضائي - بناء من الصفر
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

use backend\helpers\FlatpickrWidget;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* بيانات مرجعية */
$judiciaries = ArrayHelper::map(Judiciary::find()->asArray()->all(), 'id', 'judiciary_number');
$actions = ArrayHelper::map(JudiciaryActions::find()->asArray()->all(), 'id', 'name');
$isNew = $model->isNewRecord;
?>

<div class="judiciary-customers-actions-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

    <fieldset>
        <legend><i class="fa fa-gavel"></i> بيانات الإجراء</legend>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'judiciary_id')->dropDownList($judiciaries, ['prompt' => '-- اختر القضية --', 'class' => 'form-control'])->label('القضية') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'customers_id')->textInput(['placeholder' => 'ابحث بالاسم أو الرقم الوطني...', 'class' => 'form-control'])->label('العميل') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'judiciary_actions_id')->dropDownList($actions, ['prompt' => '-- اختر الإجراء --', 'class' => 'form-control'])->label('الإجراء') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'action_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'تاريخ الإجراء'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('تاريخ الإجراء') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'image')->fileInput(['accept' => 'image/*,.pdf'])->label('مرفق') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'note')->textarea(['rows' => 3, 'placeholder' => 'ملاحظات الإجراء'])->label('ملاحظات') ?>
            </div>
        </div>
    </fieldset>

    <!-- زر الحفظ -->
    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="jadal-form-actions">
            <?= Html::submitButton(
                $isNew ? '<i class="fa fa-plus"></i> إضافة إجراء' : '<i class="fa fa-save"></i> حفظ التعديلات',
                ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
        </div>
    <?php endif ?>

    <?php ActiveForm::end() ?>
</div>
