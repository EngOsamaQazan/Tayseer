<?php
/**
 * نموذج البحث في الأقساط
 * ========================
 * يوفر حقول بحث: بواسطة، التاريخ، المبلغ
 * 
 * @var yii\web\View $this
 * @var backend\modules\contractInstallment\models\ContractInstallmentSearch $model
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
?>

<div class="jadal-search-box box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-search"></i> <?= Yii::t('app', 'بحث في الأقساط') ?>
        </h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'action' => ['index', 'contract_id' => Yii::$app->getRequest()->getQueryParam('contract_id')],
            'method' => 'get',
        ]) ?>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, '_by')
                    ->textInput(['placeholder' => Yii::t('app', 'اسم الدافع')])
                    ->label(Yii::t('app', 'بواسطة')) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => Yii::t('app', 'اختر التاريخ')],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label(Yii::t('app', 'التاريخ')) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'amount')
                    ->textInput(['type' => 'number', 'placeholder' => Yii::t('app', 'المبلغ')])
                    ->label(Yii::t('app', 'المبلغ')) ?>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton(
                '<i class="fa fa-search"></i> ' . Yii::t('app', 'بحث'),
                ['class' => 'btn btn-primary']
            ) ?>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
