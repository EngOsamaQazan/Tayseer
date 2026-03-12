<?php

use dektrium\user\widgets\Connect;
use dektrium\user\models\LoginForm;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\LoginForm $model
 * @var dektrium\user\Module $module
 */

$this->title = 'تسجيل الدخول';
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('/_alert', ['module' => Yii::$app->getModule('user')]) ?>

<div class="jadal-login-wrapper">
    <!-- Logo -->
    <div class="jadal-login-logo">
        <div class="jadal-login-logo-inner">
            <img src="<?= Yii::$app->request->baseUrl ?>/images/favicon.png" alt="تيسير" class="jadal-login-logo-img" />
            <span class="jadal-login-brand">تيسير</span>
            <span class="jadal-login-subtitle">المنصة السحابية المتكاملة لإدارة شركات التقسيط</span>
        </div>
    </div>

    <!-- Login Card -->
    <div class="jadal-login-card">
        <div class="jadal-login-card-header">
            <i class="fa fa-lock"></i>
            <span><?= Html::encode($this->title) ?></span>
        </div>
        <div class="jadal-login-card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'enableAjaxValidation' => true,
                'enableClientValidation' => false,
                'validateOnBlur' => false,
                'validateOnType' => false,
                'validateOnChange' => false,
                'options' => ['class' => 'jadal-login-form'],
            ]) ?>

            <div class="jadal-field-group">
                <label for="login-form-login">
                    <i class="fa fa-user"></i>
                    اسم المستخدم
                </label>
                <?= $form->field($model, 'login', [
                    'template' => '{input}{error}',
                    'inputOptions' => [
                        'autofocus' => 'autofocus',
                        'class' => 'form-control jadal-input',
                        'tabindex' => '1',
                        'placeholder' => 'أدخل اسم المستخدم أو البريد الإلكتروني',
                    ]
                ]) ?>
            </div>

            <div class="jadal-field-group">
                <label for="login-form-password">
                    <i class="fa fa-key"></i>
                    كلمة المرور
                    <?php if ($module->enablePasswordRecovery): ?>
                        <a href="<?= \yii\helpers\Url::to(['/user/recovery/request']) ?>" class="jadal-forgot-link">نسيت كلمة المرور؟</a>
                    <?php endif ?>
                </label>
                <?php if ($module->debug): ?>
                    <div class="alert alert-warning jadal-alert">
                        <i class="fa fa-exclamation-triangle"></i>
                        كلمة المرور غير مطلوبة (وضع التطوير)
                    </div>
                <?php else: ?>
                    <?= $form->field($model, 'password', [
                        'template' => '{input}{error}',
                        'inputOptions' => [
                            'class' => 'form-control jadal-input',
                            'tabindex' => '2',
                            'placeholder' => 'أدخل كلمة المرور',
                        ]
                    ])->passwordInput() ?>
                <?php endif ?>
            </div>

            <div class="jadal-remember-row">
                <?= $form->field($model, 'rememberMe', [
                    'template' => '{input}',
                ])->checkbox(['tabindex' => '3', 'label' => 'تذكرني في المرة القادمة']) ?>
            </div>

            <div class="jadal-submit-row">
                <?= Html::submitButton(
                    'تسجيل الدخول <i class="fa fa-arrow-left"></i>',
                    ['class' => 'btn jadal-login-btn', 'tabindex' => '4']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Footer Links -->
    <div class="jadal-login-footer">
        <?php if ($module->enableConfirmation): ?>
            <a href="<?= \yii\helpers\Url::to(['/user/registration/resend']) ?>" class="jadal-footer-link">
                لم تستلم رسالة التأكيد؟
            </a>
        <?php endif ?>
        <?php if ($module->enableRegistration): ?>
            <a href="<?= \yii\helpers\Url::to(['/user/registration/register']) ?>" class="jadal-footer-link jadal-footer-link-gold">
                ليس لديك حساب؟ سجّل الآن!
            </a>
        <?php endif ?>
    </div>

    <?= Connect::widget([
        'baseAuthUrl' => ['/user/security/auth'],
    ]) ?>
</div>
