<?php
use yii\widgets\ActiveForm;

use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use common\models\User; // Ensure this matches the namespace of your User model
use yii\helpers\Html;

/* @var $model app\models\CustomersJudiciaryActionsSearch */

$court = Yii::$app->cache->getOrSet("l1", function () {
    return Yii::$app->db->createCommand(Yii::$app->params['court_query'])->queryAll();
}, Yii::$app->params['time_duration']);

$form = ActiveForm::begin([
    'id' => '_search',
    'method' => 'get',
    'action' => ['customers-judiciary-actions'] // Update the action to the correct route
]);
?>
<div class="customers-judiciary-actions-search box box-primary">
    <div class="row">
        <!-- Add your search fields here, matching the attributes of your search model -->
        <!-- Example: -->
        <div class="col-lg-6">
            <?= $form->field($model, 'customer_id')->textInput() ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'customer_name')->textInput() ?>
        </div>
        <div class="col-lg-6">
        <?=
                $form->field($model, 'court_name')->widget(kartik\select2\Select2::classname(), [
                    'data' =>yii\helpers\ArrayHelper::map($court,'name','name'),
                    'language' => 'ar',
                    'options' => [
                        'placeholder' => 'Select a court.',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]);
                ?>
        </div>
        <!-- Repeat for other attributes -->
    </div>
    <div class="row">
        <div class="col-lg-6">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
