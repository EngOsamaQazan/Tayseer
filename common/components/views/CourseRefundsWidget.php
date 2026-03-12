<?php
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\CourseRefund;
$hiddenClass2 = ($refunded_policy!=3) ? 'hidden' : '';
?>
<div class="customize_refunded <?=$hiddenClass2?>">
        
        <div class="">

        
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapperBR4', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-itemsBR4', // required: css class selector
                'widgetItem' => '.itemR4', // required: css class
                'limit' => 10, // the maximum times, an element can be cloned (default 999)
                'min' => 0, // 0 or 1 (default 1)
                'insertButton' => '.add-itemBR4', // css class
                'deleteButton' => '.remove-itemBR4', // css class
                'model' => $modelsCourseRefund[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'refund_amount',
                    'status',
                ],
            ]); ?>

            <div class="container-itemsBR4"><!-- widgetContainer -->
                
        <div class="clearfix"></div>
            
            <?php foreach ($modelsCourseRefund as $i => $modelCourseRefund): ?>
                
                <div class="itemR4"><!-- widgetBody -->
                    
                        <?php
                            // necessary for update action.
                            //$initial = false;
                            if (! $modelCourseRefund->isNewRecord) {
                                echo Html::activeHiddenInput($modelCourseRefund, "[{$i}]id");
                            }
                        ?>
						<div class="row row2">
                            <div class="col-sm-1">
                                <label>
                                    <?=\Yii::t('app',"Refund")?>
                                </label>
                            </div>    
                            <div class="col-sm-2 refund-precentage">
                                <?= $form->field($modelCourseRefund, "[{$i}]refund_amount")->textInput(['maxlength' => true,'class'=>'form-control courserequired'])->label(false); ?>
                                <span class="input-group-addon sar-coin">
                                    <span>%</span>
                                </span>
                            </div>
                            <div class="col-sm-2 refund-day">
                                <?= $form->field($modelCourseRefund, "[{$i}]days")->textInput(['maxlength' => true,'class'=>'form-control courserequired'])->label(false); ?>
                                <span class="input-group-addon sar-coin">
                                    <span>day</span>
                                </span>
                            </div>
                            <!-- <div class="col-sm-1">
                                <label>
                                    <?=\Yii::t('app',"Day")?>
                                </label>
                            </div> -->  
                            <div class="col-sm-2">
                                <?= $form->field($modelCourseRefund, "[{$i}]status")
                                    ->dropDownList(Yii::$app->params['refundStatus'], 
                                    ['prompt'=> \Yii::t('app',"Select"),'class'=>'form-control courserequired'])->label(false) ?>
                            </div>
                            <div class="col-sm-3">
                                <label>
                                    <?=\Yii::t('app',"Event Start Date")?>
                                </label>
                            </div>
                            <div class="col-sm-1"><button type="button" class="remove-itemBR4 btn btn-danger btn-xs"><i title="<?=yii::t('app','Delete') ?>" class="glyphicon glyphicon-minus"></i></button></div>
                        </div>
                </div>
                <div class="clearfix"></div>
            <?php endforeach; ?>
                
            </div>
            <div class="clearfix"></div>
                <div class="row ">
                    <div class="col-sm-12">
                        <button type="button" class="add-itemBR4 btn btn-success btn-xs"> <i title="<?=yii::t('app','Add Another') ?>" class="glyphicon glyphicon-plus"></i> <?=yii::t('app','Add Another') ?> </button>
                    </div>
                </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>