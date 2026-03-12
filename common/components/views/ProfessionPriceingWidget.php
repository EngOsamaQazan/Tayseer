<?php

use common\models\Profession;
use kartik\select2\Select2;
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var $model \common\models\Course */

$ids = ArrayHelper::getColumn($modelsAudience, 'profession_id');
$content = Profession::find()->Where(['in', 'id', $ids])->asArray()->all();
$professions = yii\helpers\ArrayHelper::map($content, 'id', 'name');

//print_r($professions);die();

$hiddenClass1 = ($model->date_allow == 1) ? 'hidden' : '';
$hiddenClass2 = ($model->date_allow == 0) ? 'hidden' : '';
$profClasses = $model->date_allow == 0 ? 'col-sm-5 col-xs-5' : 'col-sm-7 col-xs-7'
?>
<div class="diff-price-widget">
    <div class="hide">
        <?= Html::dropDownList('all_free_prof', null, Profession::getSubProfessionList(null), ['id' => 'all-free-profession']) ?>
    </div>
    <div class="row profession_price">
        <div class="col-sm-12 col-xs-12 ">
            <div class="col-sm-3 col-xs-3">
                <p><?= yii::t('app', 'Description') ?></p>
            </div>
            <div class="<?= $profClasses ?> prof-list-table">
                <p><?= yii::t('app', 'Professions') ?></p>
            </div>
            <div class="col-sm-2 col-xs-2 early_late_fee <?= $hiddenClass1 ?>">
                <p><?= yii::t('app', 'Early Fee') ?></p>
            </div>
            <div class="col-sm-2 col-xs-2 early_late_fee <?= $hiddenClass1 ?>">
                <p><?= yii::t('app', 'Late Fee') ?></p>
            </div>
            <div class="col-sm-2 col-xs-2 fee_diff <?= $hiddenClass2 ?>">
                <p><?= yii::t('app', 'Fee') ?></p>
            </div>
        </div>
    </div>
    <div class="margin-top-20">


        <?php DynamicFormWidget::begin([
            'widgetContainer' => 'dynamicform_wrapperBR44', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
            'widgetBody' => '.container-itemsBR44', // required: css class selector
            'widgetItem' => '.itemR47', // required: css class
            'limit' => 10, // the maximum times, an element can be cloned (default 999)
            'min' => 0, // 0 or 1 (default 1)
            'insertButton' => '.add-itemBR44', // css class
            'deleteButton' => '.remove-itemBR44', // css class
            'model' => $modelsProfessionPrice[0],
            'formId' => 'dynamic-form',
            'formFields' => [
                'description',
                'professions_id',
                'price_before',
                'price_after',
                'price_fee',
            ],
        ]); ?>

        <div class="container-itemsBR44"><!-- widgetContainer -->

            <div class="clearfix"></div>

            <?php foreach ($modelsProfessionPrice as $i => $modelProfessionPrice): ?>

                <div class="itemR47"><!-- widgetBody -->

                    <?php
                    // necessary for update action.
                    //$initial = false;

                    //	if ($modelProfessionPrice->profession_id!=null){
                    if (!$modelProfessionPrice->isNewRecord) {
                        echo Html::activeHiddenInput($modelProfessionPrice, "[{$i}]id");
                    }
                    ?>


                    <div class="row row2 ">

                        <div class="col-sm-3 ">
                            <?= $form->field($modelProfessionPrice, "[{$i}]description")->textInput(['maxlength' => true, 'class' => 'form-control '])->label(false); ?>

                        </div>
                        <div class=" <?= $profClasses ?> prof-list-table">

                            <?php

                            echo $form->field($modelProfessionPrice, "[{$i}]professions_id")->widget(Select2::classname(), [
                                    'data' => ($model->audiance_type == 1) ? Profession::getSubProfessionList(null) : Profession::getProfessionList($model->audience),
                                    //'data'=>$professions,
                                    'options' => ['placeholder' => Yii::t('app', 'Select a course to be prerequistied'),
                                        'multiple' => true,
                                        'class' => 'select2-prof-list-price'
                                    ],
                                    'pluginOptions' => [
                                        'tags' => true,
                                        'closeOnSelect' => false,
                                        'tokenSeparators' => [',', ' '],
                                        'maximumInputLength' => 10,

                                    ],
                                ]
                            )->label(false); ?>


                        </div>

                        <div class="col-sm-2 refund-precentage early_late_fee <?= $hiddenClass1 ?>">
                            <?= $form->field($modelProfessionPrice, "[{$i}]price_before")->textInput(['maxlength' => true, 'class' => 'form-control '])->label(false); ?>
                            <span class="input-group-addon sar-coin">
                                    <span>SR</span>
                                </span>
                        </div>
                        <div class="col-sm-2 refund-precentage early_late_fee <?= $hiddenClass1 ?>">
                            <?= $form->field($modelProfessionPrice, "[{$i}]price_after")->textInput(['maxlength' => true, 'class' => 'form-control '])->label(false); ?>
                            <span class="input-group-addon sar-coin">
                                    <span>SR</span>
                                </span>
                        </div>
                        <div class="col-sm-2 refund-precentage fee_diff <?= $hiddenClass2 ?>">
                            <?= $form->field($modelProfessionPrice, "[{$i}]price_fee")->textInput(['maxlength' => true, 'class' => 'form-control '])->label(false); ?>
                            <span class="input-group-addon sar-coin">
                                    <span>SR</span>
                                </span>
                        </div>


                        <div>
                            <div class="pull-right" style="    margin-top: -40px; margin-right: -5px;">
                                <a href="javascript:void(0);" class="remove-itemBR44 remove-broadcasting"><span
                                            class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                </div>
                <div class="clearfix"></div>
            <?php //}
            endforeach; ?>

        </div>
        <div class="clearfix"></div>
        <div class="row ">
            <div class="col-sm-12">
                <button type="button" class="add-itemBR44 btn btn-success btn-xs"><i
                            title="<?= yii::t('app', 'Add Another') ?>"
                            class="glyphicon glyphicon-plus"></i> <?= yii::t('app', 'Add Another') ?> </button>
            </div>
        </div>
        <?php DynamicFormWidget::end(); ?>
    </div>
</div>