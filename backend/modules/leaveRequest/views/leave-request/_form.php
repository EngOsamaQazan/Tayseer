<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use common\models\UserLeavePolicy;
use backend\helpers\FlatpickrWidget;
use yii\web\View;
use backend\modules\leavePolicy\models\LeavePolicy;
use backend\widgets\ImageManagerInputWidget;

$this->registerJs(" 
         leave_messages = ['" . Yii::t('app', 'You have leave cridt:') . "', '" . Yii::t('app', 'You dont have any leave cridt days') . "']
         ", View::POS_HEAD);


/* @var $this yii\web\View */
/* @var $model common\models\LeaveRequest */
/* @var $form yii\widgets\ActiveForm */

?>

    <div class="leave-request-form">

        <?php $form = ActiveForm::begin(); ?>
        <div class="col-md-12">
            <div class="row">
                <div class="clabel col-md-2">
                    <?= Yii::t('app', 'leave type') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'leave_policy')->dropDownList(yii\helpers\ArrayHelper::map(LeavePolicy::find()->where(['department'=>Yii::$app->user->identity['department']])->all(), 'id', 'title'), ['prompt' => Yii::t('app', 'select Policy'), 'class' => 'leave_policy'])->label(false) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">

                </div>
                <div id="days_lift" class="col-md-1 available"
                     style="text-align: center; font-size: 25px;color: #1ac11a;font-style: italic;">0
                </div>
                <div class="col-md-2 available_days"
                     style="font-size: 25px;color: #1ac11a;font-style: italic;"><?= Yii::t('app', 'days lift') ?></div>
            </div>
            <div class="row">
                <div class="col-md-2 clabel">
                    <?= Yii::t('app', 'from') ?>
                </div>
                <div class="col-md-4">
                    <?=
                    $form->field($model, 'start_at')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                        'dateFormat' => 'Y-m-d',
                    ]])->label(false);
                    ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2 clabel">
                    <?= Yii::t('app', 'to') ?>
                </div>
                <div class="col-md-4">
                    <?=
                    $form->field($model, 'end_at')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                        'dateFormat' => 'Y-m-d',
                    ]])->label(false);
                    ?>
                </div>
            </div>
            <div class="row">

                <div class="col-md-8"
                     id="leave_days_message"
                     style="
                 border-color: black;
                 border-style: solid;
                 border-width: 1px;
                 margin-left: 15px;
                 margin-bottom: 15px;
                 "><?= Yii::t('app', 'You have leave cridt:') ?><span> </span>
                    <div id="leave_days_cridet"> <?php

                        $date1 = date_create($model->start_at);
                        $date2 = date_create($model->end_at);
                        $diff = date_diff($date1, $date2);
                        echo $diff->format("%a");
                        ?></div>
                    <span> </span><?= Yii::t('app', 'days') ?></div>
            </div>
            <div class="row">
                <div class="col-md-2 clabel">
                    <?= Yii::t('app', 'reason') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'reason')->textarea(['maxlength' => true])->label(false) ?>
                </div>
            </div>
            <div class="row">
                <div class="clabel col-md-2">
                    <?= Yii::t('app', 'attachment') ?>
                </div>
                <div class="col-md-4">
                    <?=
                    $form->field($model, 'attachment')->widget(ImageManagerInputWidget::class, [
                        'aspectRatio' => (16 / 9), //set the aspect ratio
                        'cropViewMode' => 1, //crop mode, option info: https://github.com/fengyuanchen/cropper/#viewmode
                        'showPreview' => true, //false to hide the preview
                        'showDeletePickedImageConfirm' => false, //on true show warning before detach image
                    ])->label(false);;
                    ?>

                </div>

            </div>
            <div class="row">
                <div class="col-md-6">

                    <?php if (!Yii::$app->request->isAjax) { ?>
                    <div class="form-group">
                        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Send leave request') : Yii::t('app', 'Update leave request'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                        <div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
        <?php
        $this->registerJs(
            "$('#leaverequest-leave_policy').on('change', function() {
            $.ajax({
                 url:'" . yii\helpers\Url::to(["/employee/leave-policy-remaining?policy_id="]) . "'+$(this).val() ,
                 type: 'get',
                 dataType: 'json',
                 success: function(data){
                   $('#days_lift').html(data);
                   if(data <= 0 ){
                  form_elemnt_enable(false);
                   
                 }else{
                  form_elemnt_enable(true);
                 }
                 }
            });
            });
            ", View::POS_READY
        );
        $this->registerJsFile('/js/leave-request.js', ['depends' => [\yii\web\JqueryAsset::class]]);
        ?>
    </div>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('change','.leave_policy',function(){
 let leavePolicy = $('.leave_policy').val();
  $.post('number-date',{leavePolicy:leavePolicy},function(response){
  $('.available').html(response);
   });
});
SCRIPT
)
?>