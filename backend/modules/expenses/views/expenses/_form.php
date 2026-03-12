<?php
/**
 * نموذج إدخال/تعديل مصروف
 * ==========================
 * يحتوي على: التصنيف، المبلغ، رقم المستلم، التاريخ، رقم المستند، العقد، الوصف، ملاحظات
 * 
 * @var yii\web\View $this
 * @var backend\modules\expenses\models\Expenses $model نموذج المصروف
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use kartik\select2\Select2;
use backend\modules\expenseCategories\models\ExpenseCategories;

/* === جلب البيانات من الكاش === */
$users = Yii::$app->cache->getOrSet(Yii::$app->params['key_users'], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['users_query'])->queryAll();
}, Yii::$app->params['time_duration']);

$contract_id = Yii::$app->cache->getOrSet(Yii::$app->params['key_contract_id'], function () {
    return Yii::$app->db->createCommand(Yii::$app->params['contract_id_query'])->queryAll();
}, Yii::$app->params['time_duration']);
?>

<div class="expenses-form box box-primary">
    <div class="box-body">
        <?php $form = ActiveForm::begin() ?>

        <!-- التصنيف والمبلغ -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'category_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map(ExpenseCategories::find()->all(), 'id', 'name'),
                    'options' => ['placeholder' => Yii::t('app', 'اختر التصنيف ...')],
                    'pluginOptions' => ['allowClear' => true],
                ])->label(Yii::t('app', 'تصنيف المصروف')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'amount')
                    ->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])
                    ->label(Yii::t('app', 'المبلغ')) ?>
            </div>
        </div>

        <!-- رقم المستلم وتاريخ المصروف -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'receiver_number')
                    ->textInput(['placeholder' => Yii::t('app', 'رقم المستلم')])
                    ->label(Yii::t('app', 'رقم المستلم')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'expenses_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => Yii::t('app', 'تاريخ المصروف')],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label(Yii::t('app', 'تاريخ المصروف')) ?>
            </div>
        </div>

        <!-- رقم المستند ورقم العقد -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'document_number')
                    ->textInput(['placeholder' => Yii::t('app', 'رقم المستند')])
                    ->label(Yii::t('app', 'رقم المستند')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'contract_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map($contract_id, 'id', 'id'),
                    'options' => ['placeholder' => Yii::t('app', 'اختر العقد ...')],
                    'pluginOptions' => ['allowClear' => true],
                ])->label(Yii::t('app', 'رقم العقد')) ?>
            </div>
        </div>

        <!-- الوصف والملاحظات -->
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'description')
                    ->textarea(['rows' => 4, 'placeholder' => Yii::t('app', 'وصف المصروف')])
                    ->label(Yii::t('app', 'الوصف')) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'notes')
                    ->textarea(['rows' => 4, 'placeholder' => Yii::t('app', 'ملاحظات إضافية')])
                    ->label(Yii::t('app', 'ملاحظات')) ?>
            </div>
        </div>

        <!-- زر الحفظ -->
        <?php if (!Yii::$app->request->isAjax) : ?>
            <div class="form-group jadal-form-actions">
                <?= Html::submitButton(
                    $model->isNewRecord
                        ? '<i class="fa fa-plus"></i> ' . Yii::t('app', 'إضافة')
                        : '<i class="fa fa-save"></i> ' . Yii::t('app', 'حفظ التعديلات'),
                    ['class' => $model->isNewRecord ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
                ) ?>
            </div>
        <?php endif ?>

        <?php ActiveForm::end() ?>
    </div>
</div>
