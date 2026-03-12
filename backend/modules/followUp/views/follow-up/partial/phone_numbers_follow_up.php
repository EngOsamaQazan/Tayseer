<?php

use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
?>

<div class="panel-body">
    <?php
    DynamicFormWidget::begin([
        'widgetContainer' => 'dynamicform_wrapper2', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
        'widgetBody' => '.container-items2', // required: css class selector
        'widgetItem' => '.phone-numbers-item', // required: css class
        'limit' => 100000000000000000000, // the maximum times, an element can be cloned (default 999)
        'min' => 0, // 0 or 1 (default 1)
        'insertButton' => '.phone-numbers-add-item', // css class
        'deleteButton' => '.phone-numbers-remove-item', // css class
        'model' => $modelsPhoneNumbersFollwUps[0],
        'formId' => 'dynamic-form',
        'formFields' => [
            'address',
        ],
    ]);
    ?>

    <div class="container-items2">
        <?php foreach ($modelsPhoneNumbersFollwUps as $i => $modelPhoneNumbersFollwUps): ?>
            <div class="phone-numbers-item panel panel-default"><!-- widgetBody -->

                <div class="panel-body">
                    <?php
                    // necessary for update action.
                    if (!$modelPhoneNumbersFollwUps->isNewRecord) {
                        echo Html::activeHiddenInput($modelPhoneNumbersFollwUps, "[{$i}]id");
                    }
                    if ($modelPhoneNumbersFollwUps->isNewRecord) {
                        echo $form->field($modelPhoneNumbersFollwUps, 'os_follow_up_id')->hiddenInput(['value' => 1])->label(false);
                    }
                    ?>
                    <div class="row">
                        <?php
                        $phone_names_and_numbers = array();
                        foreach ($model->contractsCustomers as $key => $value) {
                            array_push($phone_names_and_numbers, yii\helpers\ArrayHelper::map($value->customer->phoneNumbers, 'owner_name', 'owner_name'));
                        }
                        $phone_names_and_numbers = array_shift($phone_names_and_numbers);
                        $phone_names_and_numbers[$model->customer->name] = $model->customer->name;

                        //yii\helpers\ArrayHelper::setValue($phone_names_and_numbers, 0,[$model->customer->name=>$model->customer->name]);
                        ?> 
                        <div class="col-sm-4">
                            <?= $form->field($modelPhoneNumbersFollwUps, "[{$i}]customer_name")->dropDownList($phone_names_and_numbers, ['prompt' => '']) ?>

                        </div>
                        <div class="col-sm-4">
                            <?= $form->field($modelPhoneNumbersFollwUps, "[{$i}]connection_type")->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\contactType\models\ContactType::find()->all(), 'id', 'name'), ['prompt' => '']); ?>

                        </div>
                        <div class="col-sm-4">
                            <?= $form->field($modelPhoneNumbersFollwUps, "[{$i}]connection_response")->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\connectionResponse\models\ConnectionResponse::find()->all(), 'id', 'name'), ['prompt' => '']); ?>
                        </div>



                    </div>
                    <div class="row">
                        <div class="col-sm-11">
                            <?= $form->field($modelPhoneNumbersFollwUps, "[{$i}]note")->textarea(['rows' => 6]) ?>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="phone-numbers-remove-item btn btn-danger btn-xs" style="margin-top: 30px;">
                                <i class="glyphicon glyphicon-minus"></i>
                            </button>
                        </div>


                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <div class="pull-right">
        <button type="button" class="phone-numbers-add-item btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>

    </div>
    <?php DynamicFormWidget::end(); ?>
</div>