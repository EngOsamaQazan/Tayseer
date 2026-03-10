<?php
/**
 * نموذج إجراء عميل قضائي - بناء من الصفر
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\helpers\FlatpickrWidget;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\customers\models\Customers;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* بيانات مرجعية */
$judiciaries = ArrayHelper::map(Judiciary::find()->asArray()->all(), 'id', 'judiciary_number');
$actions = ArrayHelper::map(JudiciaryActions::find()->asArray()->all(), 'id', 'name');
/* العميل الحالي (لوضع التعديل) */
$custInitText = '';
if (!$model->isNewRecord && $model->customers_id) {
    $c = Customers::findOne($model->customers_id);
    $custInitText = $c ? $c->name : '';
}
$isNew = $model->isNewRecord;
?>

<div class="judiciary-customers-actions-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

    <fieldset>
        <legend><i class="fa fa-gavel"></i> بيانات الإجراء</legend>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'judiciary_id')->widget(Select2::class, [
                    'data' => $judiciaries,
                    'options' => ['placeholder' => 'اختر القضية'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('القضية') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'customers_id')->widget(Select2::class, [
                    'initValueText' => $custInitText,
                    'options' => ['placeholder' => 'ابحث بالاسم أو الرقم الوطني...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
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
                ])->label('العميل') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'judiciary_actions_id')->widget(Select2::class, [
                    'data' => $actions,
                    'options' => ['placeholder' => 'اختر الإجراء'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الإجراء') ?>
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
