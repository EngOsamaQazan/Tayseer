<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use yii\bootstrap5\Nav;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $user
 */

$this->title = Yii::t('user', 'Create a user account');
$this->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('/_alert', ['module' => Yii::$app->getModule('user'),]) ?>

<?= $this->render('_menu') ?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-body">
                <?= Nav::widget([
                    'options' => [
                        'class' => 'nav-pills flex-column',
                    ],
                    'items' => [
                        ['label' => Yii::t('user', 'Account details'), 'url' => ['/user/admin/create']],
                        [
                            'label' => Yii::t('user', 'Profile details'),
                            'disabled' => true,
                        ],
                        [
                            'label' => Yii::t('user', 'Information'),
                            'disabled' => true,
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card mb-3">
            <div class="card-body">
                <div class="alert alert-info">
                    <?= Yii::t('user', 'Credentials will be sent to the user by email') ?>.
                    <?= Yii::t('user', 'A password will be generated automatically if not provided') ?>.
                </div>
                <?php $form = ActiveForm::begin([
                    'options' => ['class' => ''],
                    'enableAjaxValidation' => true,
                    'enableClientValidation' => false,
                    'fieldConfig' => [
                        'options' => ['class' => 'row mb-3'],
                        'template' => "{label}\n<div class=\"col-lg-9\">{input}</div>\n<div class=\"offset-lg-3 col-lg-9\">{error}\n{hint}</div>",
                        'labelOptions' => ['class' => 'col-lg-3 col-form-label'],
                    ],
                ]); ?>

                <?= $this->render('_user', ['form' => $form, 'user' => $user]) ?>

                <div class="row mb-3">
                    <div class="offset-lg-3 col-lg-9">
                        <?= Html::submitButton(Yii::t('user', 'Save'), ['class' => 'btn btn-block btn-success']) ?>
                    </div>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
