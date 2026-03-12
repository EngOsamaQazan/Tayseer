<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model \common\models\Expenses */
/* @var $notImportedRecords */
?>

<div class="questions-bank box box-primary">
    <fieldset>
        <legend><?= Yii::t('app', 'Select file to import') ?></legend>
        <div class="row">
            <?php $form = ActiveForm::begin(); ?>
            <div class="col-md-4">
                <?= $form->field($model, 'excel_file')->fileInput()->label(false); ?>
            </div>
            <div class="col-md-8">
                <?= Html::submitButton(Yii::t('app', 'Import'), ['class' => 'btn btn-primary btn-md']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>

        <?php foreach (Yii::$app->session->getAllFlashes() as $key => $message): ?>
            <div class="alert alert-<?= $key ?>">
                <?= $message[0] ?>
            </div>
        <?php endforeach; ?>

        <?php if (count($notImportedRecords) > 0): ?>
            <legend><?= Yii::t('app', 'Not imported records') ?></legend>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th><?= Yii::t('app', 'Description') ?></th>
                    <th><?= Yii::t('app', 'Amount') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($notImportedRecords as $key => $record): ?>
                    <tr>
                        <td><?= $record['description'] ?></td>
                        <td><?= $record['amount'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </fieldset>
</div>