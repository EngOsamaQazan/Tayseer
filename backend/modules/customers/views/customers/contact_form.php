<?php
/**
 * نموذج تعديل معلومات الاتصال السريع
 * ======================================
 * يسمح بتعديل رقم الهاتف والبريد الإلكتروني وحساب فيسبوك
 * بدون الحاجة لتعديل جميع بيانات العميل
 * 
 * @var yii\web\View $this
 * @var backend\modules\customers\models\Customers $model نموذج العميل
 * @var int $id معرّف العميل
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\helpers\PhoneInputWidget;
?>

<div class="customers-contact-form">
    <?php $form = ActiveForm::begin([
        'action' => Url::to(['customers/update-contact', 'id' => $id]),
    ]) ?>

    <!-- === عرض ملخص الأخطاء === -->
    <?= $form->errorSummary($model) ?>

    <!-- === حقول معلومات الاتصال === -->
    <div class="row">
        <!-- رقم الهاتف الرئيسي -->
        <div class="col-md-12">
            <?= $form->field($model, 'primary_phone_number')->widget(PhoneInputWidget::class, [
                'options' => ['class' => 'form-control'],
            ])->label(Yii::t('app', 'رقم الهاتف الرئيسي')) ?>
        </div>

        <!-- حساب فيسبوك -->
        <div class="col-md-12">
            <?= $form->field($model, 'facebook_account')
                ->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'رابط أو اسم الحساب')])
                ->label(Yii::t('app', 'حساب فيسبوك')) ?>
        </div>

        <!-- البريد الإلكتروني -->
        <div class="col-md-12">
            <?= $form->field($model, 'email')
                ->textInput(['type' => 'email', 'placeholder' => 'example@email.com'])
                ->label(Yii::t('app', 'البريد الإلكتروني')) ?>
        </div>
    </div>

    <!-- === زر الحفظ === -->
    <div class="row" style="margin-top: 15px;">
        <div class="col-md-12">
            <?php if (!Yii::$app->request->isAjax) : ?>
                <div class="form-group">
                    <?= Html::submitButton(
                        '<i class="fa fa-save"></i> ' . Yii::t('app', 'حفظ التعديلات'),
                        ['class' => 'btn btn-primary']
                    ) ?>
                </div>
            <?php endif ?>
        </div>
    </div>

    <?php ActiveForm::end() ?>
</div>
