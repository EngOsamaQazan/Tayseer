<?
use yii\widgets\ActiveForm;
/* @var $model */
?>
<div class="questions-bank box box-primary">

    <?php
    $form = yii\widgets\ActiveForm::begin([
                'id' => '_search',
                'method' => 'get',
                'action' => ['index']
    ]);
    ?>
<div class ="row">
    <div class="col-lg-6">
        <?=
        $form->field($model, 'created_by')->dropDownList(
            yii\helpers\ArrayHelper::map(\common\models\User::find()->all(), 'id', 'username'),
            ['prompt' => '-- اختر المنشئ --', 'class' => 'form-control']
        );
        ?>
    </div>
    <div class="col-lg-6">
        <?=
        $form->field($model, 'last_updated_by')->dropDownList(
            yii\helpers\ArrayHelper::map(\common\models\User::find()->all(), 'id', 'username'),
            ['prompt' => '-- آخر تعديل بواسطة --', 'class' => 'form-control']
        );
        ?>
    </div>

</div>

<div class="form-group">
<?= yii\helpers\Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
</div>
</div>
<?php yii\widgets\ActiveForm::end() ?>