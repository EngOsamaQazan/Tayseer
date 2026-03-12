<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="fin-filter" style="margin-bottom:16px">
    <?php $form = ActiveForm::begin([
        'id' => '_search',
        'method' => 'get',
        'action' => ['index'],
        'options' => ['class' => 'fin-filter-main'],
    ]); ?>

    <div class="fin-f-field fin-f--grow">
        <label><i class="fa fa-cube"></i> اسم الصنف</label>
        <?= Html::activeTextInput($model, 'item_name', ['class' => 'form-control fin-f-input', 'placeholder' => 'بحث بالاسم...']) ?>
    </div>
    <div class="fin-f-field fin-f--grow">
        <label><i class="fa fa-barcode"></i> الباركود</label>
        <?= Html::activeTextInput($model, 'item_barcode', ['class' => 'form-control fin-f-input', 'placeholder' => 'بحث بالباركود...']) ?>
    </div>
    <div class="fin-f-btns">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'fin-btn fin-btn--search']) ?>
        <?= Html::a('<i class="fa fa-times"></i> مسح', ['index'], ['class' => 'fin-btn fin-btn--reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
