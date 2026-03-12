<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use wbraganca\dynamicform\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Invoice */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="invoice-form">


    <?php
    if (isset($id)) {
        $form = ActiveForm::begin(['action' => Url::to(['invoice/update', 'id' => $id]), 'id' => 'dynamic-form']);
    } else {
        $form = ActiveForm::begin(['id' => 'dynamic-form']);
    }
    ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'number')->textInput() ?>
    <?=
    $form->field($model, 'date')->widget(FlatpickrWidget::class, [
        'options' => ['placeholder' => 'Enter birth date ...'],
        'pluginOptions' => [
            'dateFormat' => 'Y-m-d'
        ]
    ]);
    ?>
    <?= $form->field($model, 'total')->textInput() ?>
    <div class="panel panel-default">
        <div class="panel-heading"><h4><i class="glyphicon glyphicon-envelope"></i> item's</h4></div>
        <div class="panel-body">
            <?php
            DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapper', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-items', // required: css class selector
                'widgetItem' => '.addrres-item', // required: css class
                'limit' => 50, // the maximum times, an element can be cloned (default 999)
                'min' => 1, // 0 or 1 (default 1)
                'insertButton' => '.addrres-add-item', // css class
                'deleteButton' => '.addrres-remove-item', // css class
                'model' => $modelsItems[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'address',
                ],
            ]);
            ?>

            <div class="container-items">
                <?php foreach ($modelsItems as $i => $modelItems): ?>
                    <div class="addrres-item panel panel-default"><!-- widgetBody -->

                        <div class="panel-body">
                            <?php
                            // necessary for update action.
                            if (!$modelItems->isNewRecord) {
                                echo Html::activeHiddenInput($modelItems, "[{$i}]id");
                            }
                            ?>
                            <div class="row">
                                <div class="col-sm-4">
                                    <?= $form->field($modelItems, "[{$i}]name")->textInput(['maxlength' => true]) ?>

                                </div>
                                <div class="col-sm-4">
                                    <?=
                                    $form->field($modelItems, "[{$i}]cost")->textInput([
                                        'type' => 'number',
                                        'step' => '1',
                                        'min' => '0',
                                        'class' => 'form-control',
                                        'placeholder' => '0',
                                    ]);
                                    ?>

                                </div>
                                <div class="col-sm-4">
                                    <?=
                                    $form->field($modelItems, "[{$i}]price")->textInput([
                                        'type' => 'number',
                                        'step' => '1',
                                        'min' => '0',
                                        'class' => 'form-control',
                                        'placeholder' => '0',
                                    ]);
                                    ?>

                                </div>
                                <div class="col-sm-6">
                                    <button type="button" class="addrres-remove-item btn btn-danger btn-xs" style="margin-top: 30px;">
                                        <i class="glyphicon glyphicon-minus"></i>
                                    </button>
                                </div>
                            </div><!-- .row -->
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
            <div class="pull-right">
                <button type="button" class="addrres-add-item btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>

            </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>



    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
