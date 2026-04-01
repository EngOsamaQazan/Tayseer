<?php

/*
 * This file is part of the Dektrium project
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
 * @var dektrium\user\models\User $user
 */
?>

<?php $this->beginContent('@dektrium/user/views/admin/update.php', ['user' => $user]) ?>

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
        <?= Html::submitButton(Yii::t('user', 'Update'), ['class' => 'btn btn-block btn-success']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>

<?php $this->endContent() ?>
