<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;
/* @var $model backend\modules\judiciaryActions\models\JudiciaryActionsSearch */
?>

<style>
.ja-search-box {
    background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px 18px;margin-bottom:16px;
    box-shadow:0 1px 3px rgba(0,0,0,.03);
}
.ja-search-box .form-control { border-radius:8px;font-size:13px;border-color:#D1D5DB; }
.ja-search-box .form-control:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.08); }
.ja-search-box label { font-size:11px;font-weight:600;color:#64748B; }
</style>

<div class="ja-search-box">
    <?php $form = ActiveForm::begin([
        'id' => '_search',
        'method' => 'get',
        'action' => ['index'],
        'options' => ['style' => 'margin:0'],
    ]); ?>
    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'ابحث بالاسم...'])->label('اسم الإجراء') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'action_nature')->dropDownList(
                JudiciaryActions::getNatureList(),
                ['prompt' => '— جميع الطبائع —']
            )->label('الطبيعة') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'action_type')->dropDownList(
                JudiciaryActions::getActionTypeList(),
                ['prompt' => '— جميع المراحل —']
            )->label('المرحلة') ?>
        </div>
        <div class="col-md-2" style="margin-top:24px">
            <div class="form-group" style="display:flex;gap:6px">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary', 'style' => 'border-radius:8px;font-size:12px;padding:7px 16px']) ?>
                <?= Html::a('<i class="fa fa-times"></i>', ['index'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px;font-size:12px;padding:7px 12px']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
