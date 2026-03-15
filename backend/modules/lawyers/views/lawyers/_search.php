<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\modules\lawyers\models\Lawyers;

?>
<div class="box box-primary" style="border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:20px">
    <?php $form = ActiveForm::begin(['id' => 'lw-search', 'method' => 'get', 'action' => ['index']]); ?>
    <div style="padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
        <div style="flex:1;min-width:180px">
            <?= $form->field($model, 'name')->textInput(['placeholder' => 'بحث بالاسم...'])->label('الاسم') ?>
        </div>
        <div style="flex:1;min-width:160px">
            <?= $form->field($model, 'representative_type')->dropDownList([
                Lawyers::REP_TYPE_DELEGATE => 'مفوض عادي',
                Lawyers::REP_TYPE_LAWYER => 'وكيل محامي',
            ], ['prompt' => 'الكل'])->label('النوع') ?>
        </div>
        <div style="flex:1;min-width:160px">
            <?= $form->field($model, 'status')->dropDownList([
                0 => 'نشط',
                1 => 'غير نشط',
            ], ['prompt' => 'الكل'])->label('الحالة') ?>
        </div>
        <div style="display:flex;gap:6px;padding-bottom:15px">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary btn-sm']) ?>
            <?= Html::a('إعادة تعيين', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
