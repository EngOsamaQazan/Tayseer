<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\DocumentHolder */
/* @var $form yii\widgets\ActiveForm */
?>

    <div class="document-holder-form" x-data="{ warMsg: '', war2Msg: '' }"
         @war-update.window="warMsg = $event.detail"
         @war2-update.window="war2Msg = $event.detail">

<?php $form = ActiveForm::begin(); ?>
    <div class="questions-bank box box-primary">
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'contract_id')->dropDownList(\yii\helpers\ArrayHelper::map(\backend\modules\contracts\models\Contracts::find()->all(), 'id', 'id'), ['prompt' => '-- اختر العقد --', 'class' => 'form-control contract']) ?>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'type')->dropDownList(['contract file','judiciary file'],['class'=>'type'])?>
        </div>
    </div>
    <div class="alert alert-warning war" role="alert"
         x-show="warMsg !== ''" x-text="warMsg" x-transition x-cloak>
    </div>
    <div class="alert alert-warning war2" role="alert"
         x-show="war2Msg !== ''" x-text="war2Msg" x-transition x-cloak>
    </div>
    <div class="row">

        <?php if (Yii::$app->user->can('مدير') && !$model->isNewRecord) { ?>
            <div class="col-lg-6">
                <?= $form->field($model, 'manager_approved')->checkbox() ?>

            </div>
        <?php } ?>
        <?php if (Yii::$app->user->id == $model->created_by && !$model->isNewRecord) { ?>
        <div class="col-lg-6">

            <?= $form->field($model, 'approved_by_employee')->checkbox() ?>
            <?php } ?>
        </div>
        <?= $form->field($model, 'reason')->textarea(['rows' => 6]) ?>
        <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group" style="margin-top: 10px">
                <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

        <?php ActiveForm::end(); ?>

    </div>
<?php
/* OLD jQuery - replaced by Alpine.js
$this->registerJs(<<<SCRIPT
$(document).on('change','.contract',function(){
let contract = $('.contract').val();
$.post('find-list-user',{contract:contract},function(response){
$('.war').css('display','block');
$('.war').text(response);
});
$.post('find-type',{contract:contract},function(response){
response = JSON.parse(response);
if(response.length === 0){
$('.war2').css('display','block');
$('.war2').text('هذا الملف لا يحتوي على ملف للقضيه');
}else {
$('.war2').css('display','none');
$('.war2').text('');
}
})
});
SCRIPT
)
*/
$this->registerJs(<<<SCRIPT
$(document).on('change','.contract',function(){
let contract = $('.contract').val();
$.post('find-list-user',{contract:contract},function(response){
    window.dispatchEvent(new CustomEvent('war-update', { detail: response }));
});
$.post('find-type',{contract:contract},function(response){
    response = JSON.parse(response);
    if(response.length === 0){
        window.dispatchEvent(new CustomEvent('war2-update', { detail: 'هذا الملف لا يحتوي على ملف للقضيه' }));
    } else {
        window.dispatchEvent(new CustomEvent('war2-update', { detail: '' }));
    }
});
});
SCRIPT
)
?>