<?php

use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
?>

<div class="panel-body">
    <?php
    DynamicFormWidget::begin([
        'widgetContainer' => 'dynamicform_wrapper9', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
        'widgetBody' => '.container-items9', // required: css class selector
        'widgetItem' => '.c-item', // required: css class
        'limit' => 1000000000000000, // the maximum times, an element can be cloned (default 999)
        'min' => 1, // 0 or 1 (default 1)
        'insertButton' => '.c-add-item', // css class
        'deleteButton' => '.c-remove-item', // css class
        'model' => $modelsCompanieBanks[0],
        'formId' => 'dynamic-form',
        'formFields' => [
            'address',
        ],

    ]);
    ?>

    <div class="container-items9">
        <?php foreach ($modelsCompanieBanks as $i => $modelsCompanieBank): ?>
            <div class="c-item panel panel-default"><!-- widgetBody -->

                <div class="panel-body">
                    <?php
                    // necessary for update action.
                    if (!$modelsCompanieBank->isNewRecord) {
                        echo Html::activeHiddenInput($modelsCompanieBank, "[{$i}]id");
                    }
                    ?>
                    <div class="row">
                        <div class="col-sm-4">
                            <?= $form->field($modelsCompanieBank, "[{$i}]bank_id")->dropDownList([yii\helpers\ArrayHelper::map(\backend\modules\bancks\models\Bancks::find()->all(),'id','name')]) ?>  </div>


                        <div class="col-sm-4">
                            <?= $form->field($modelsCompanieBank, "[{$i}]bank_number")->textInput(['maxlength' => true])?> 
                        </div>
                        
                        <div class="col-sm-4">
                            <?= $form->field($modelsCompanieBank, "[{$i}]iban_number")->textInput(['maxlength' => true])?> 
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="c-remove-item btn btn-danger btn-xs" style="margin-top: 30px;">
                                <i class="glyphicon glyphicon-minus"></i>
                            </button>
                        </div>
                    </div><!-- .row -->
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <div class="pull-right">
        <button type="button" class="c-add-item btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>

    </div>
    <?php DynamicFormWidget::end(); ?>
</div>