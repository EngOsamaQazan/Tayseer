<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use wbraganca\dynamicform\DynamicFormWidget;
use backend\helpers\FlatpickrWidget;
?>

<div class="customer-form">

    <?php $form = ActiveForm::begin(['id' => 'dynamic-form']); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><h4><i class="glyphicon glyphicon-envelope"></i> Addresses</h4></div>
        <div class="panel-body">
            <?php
            DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapper', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-items', // required: css class selector
                'widgetItem' => '.item', // required: css class
                'limit' => 4, // the maximum times, an element can be cloned (default 999)
                'min' => 1, // 0 or 1 (default 1)
                'insertButton' => '.add-item', // css class
                'deleteButton' => '.remove-item', // css class
                'model' => $modelsInstallment[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'customer_id',
                    'date',
                    'cheque_number',
                    'notes',
                    'is_made_payment',
                    'total',
                ],
            ]);
            ?>

            <div class="container-items"><!-- widgetContainer -->
                <div class="item panel panel-default"><!-- widgetBody -->
                    <div class="panel-heading">
                        <h3 class="panel-title pull-left">Address</h3>
                        <div class="pull-right">
                            <button type="button" class="add-item btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>
                            <button type="button" class="remove-item btn btn-danger btn-xs"><i class="glyphicon glyphicon-minus"></i></button>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php
                        // necessary for update action.
                        if (!$modelAddress->isNewRecord) {
                            echo Html::activeHiddenInput($modelAddress, "[{$i}]id");
                        }
                        ?>
                        <div class="col-sm-6">
                            <?= $form->field($modelAddress, "[{$i}]customer_id")->textInput(['maxlength' => true]) ?>
                        </div> 
                        <div class="col-sm-6">
                            <?= $form->field($modelAddress, "[{$i}]item_id")->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?=
                                $form->field($modelAddress, "[{$i}]date")->widget(FlatpickrWidget::class, [
                                    'options' => ['placeholder' => 'Enter birth date ...'],
                                    'pluginOptions' => [
                                        'dateFormat' => 'Y-m-d'
                                    ]
                                ]);
                                ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($modelAddress, "[{$i}]cheque_number")->textInput(['maxlength' => true]) ?>
                            </div>
                        </div><!-- .row -->
                        <div class="row">
                            <div class="col-sm-4">
                                <?= $form->field($modelAddress, "[{$i}]notes")->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($modelAddress, "[{$i}]is_made_payment")->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($modelAddress, "[{$i}]total")->textInput(['maxlength' => true]) ?>
                            </div>
                        </div><!-- .row -->
                    </div>
                </div>
            </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($modelAddress->isNewRecord ? 'Create' : 'Update', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
