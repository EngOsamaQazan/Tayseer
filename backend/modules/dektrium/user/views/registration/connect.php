<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var dektrium\user\models\User $model
 * @var dektrium\user\models\Account $account
 */

$this->title = Yii::t('user', 'Sign in');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-md-4 offset-md-4 col-sm-6 offset-sm-3">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="h5 mb-0"><?= Html::encode($this->title) ?></h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>
                        <?= Yii::t(
                            'user',
                            'In order to finish your registration, we need you to enter following fields'
                        ) ?>:
                    </p>
                </div>
                <?php $form = ActiveForm::begin([
                    'id' => 'connect-account-form',
                ]); ?>

                <?= $form->field($model, 'email') ?>

                <?= $form->field($model, 'username') ?>

                <?= Html::submitButton(Yii::t('user', 'Continue'), ['class' => 'btn btn-success btn-block']) ?>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
        <p class="text-center">
            <?= Html::a(
                Yii::t(
                    'user',
                    'If you already registered, sign in and connect this account on settings page'
                ),
                ['/user/settings/networks']
            ) ?>.
        </p>
    </div>
</div>
