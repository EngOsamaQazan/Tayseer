<?php
use wbraganca\dynamicform\DynamicFormWidget;
use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Profession;
use common\models\Audience;
use kartik\depdrop\DepDrop;
use kartik\select2\Select2;

?>
<div class="">
        
        <div class="">

        
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapperB1', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-itemsB1', // required: css class selector
                'widgetItem' => '.item', // required: css class
                'limit' => 10, // the maximum times, an element can be cloned (default 999)
                'min' => 1, // 0 or 1 (default 1)
                'insertButton' => '.add-itemB1', // css class
                'deleteButton' => '.remove-itemB1', // css class
                'model' => $modelsAudience[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'profession_id',
                    'specialist_id',
                ],
            ]); ?>

            <div class="container-itemsB1"><!-- widgetContainer -->
                <div class="panel-heading">
                    <div class="pull-left">
                        <button type="button" class="add-itemB1 btn btn-success btn-xs"><i title="<?=yii::t('app','Add Audience') ?>" class="glyphicon glyphicon-plus"></i></button>
                    </div>
					<h4 class="pull-left"><i class="glyphicon glyphicon-tree"></i> <?=yii::t('app','Add Audience') ?> </h4>
                </div>
        <div class="clearfix"></div>
            <?php foreach ($modelsAudience as $i => $modelAudience): ?>
                <div class="item panel panel-default"><!-- widgetBody -->
                    <div class="panel-heading">
                        <div class="pull-right">
                            <button type="button" class="add-itemB1 btn btn-success btn-xs"><i title="<?=yii::t('app','Add') ?>" class="glyphicon glyphicon-plus"></i></button>
                            <button type="button" class="remove-itemB1 btn btn-danger btn-xs"><i title="<?=yii::t('app','Delete') ?>" class="glyphicon glyphicon-minus"></i></button>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php
                            // necessary for update action.
                            $initial = false;
                            if (! $modelAudience->isNewRecord) {
                                echo Html::activeHiddenInput($modelAudience, "[{$i}]id");
                                echo Html::hiddenInput("profession-{$i}-id", $modelAudience->profession_id, ['id'=>"profession-{$i}-id"]);
                                echo Html::hiddenInput("audienceable-{$i}-type", $modelAudience->audienceable_type, ['id'=>"audienceable-{$i}-type"]);
                                echo Html::hiddenInput("audienceable-{$i}-id", $modelAudience->audienceable_id, ['id'=>"audienceable-{$i}-id"]);
                                $initial = true;
                            }
                        ?>
						<div class="row row2">
							<div class="col-sm-6">
							    <?= $form->field($modelAudience, "[{$i}]profession_id")->dropDownList(Profession::getProfessionList(),['prompt'=> \Yii::t('app',"Select Profession"),'class'=>'courserequired']) ?>

                            </div>
                            <div class="col-sm-6">
                                <?php
                            if ($modelAudience->isNewRecord) {
                               echo $form->field($modelAudience, "[{$i}]specialist_id")->widget(DepDrop::classname(), [
                                        'options'=>[
                                            'id'=>"audience-{$i}-specialist_id",
                                            'multiple' => true,
                                              //'theme' => Select2::THEME_BOOTSTRAP,
                                        ],
                                        //'value'=>array_keys($specialistArray),
                                        //'data'=>$specialistArray,
                                        'type'=> DepDrop::TYPE_SELECT2,
                                        'select2Options'=>
                                                [
                                                    'pluginOptions'=>[
                                                        'allowClear'=>true,
                                                        'tags' => true,
                                                        /*'tokenSeparators' => [',', ' '],
                                                        'maximumInputLength' => 10*/
                                                    ],
                                                ],
                                        'pluginOptions'=>[
                                            'depends'=>["audience-{$i}-profession_id"],
                                            'placeholder'=>'Select...',
                                            'url'=>Url::to(['/dropdown/sub-specialist']),
                                            //'params'=>["profession-{$i}-id","audienceable-{$i}-type","audienceable-{$i}-id"]
                                        ]
                                    ]); 
                                }else{
                                echo $form->field($modelAudience, "[{$i}]specialist_id")->widget(DepDrop::classname(), [
                                        'options'=>[
                                            'id'=>"audience-{$i}-specialist_id",
                                            'multiple' => true,
                                              //'theme' => Select2::THEME_BOOTSTRAP,
                                        ],
                                        //'value'=>array_keys($specialistArray),
                                        //'data'=>$specialistArray,
                                        'type'=> DepDrop::TYPE_SELECT2,
                                        'select2Options'=>
                                                [
                                                    'pluginOptions'=>[
                                                        'allowClear'=>true,
                                                        'tags' => true,
                                                        /*'tokenSeparators' => [',', ' '],
                                                        'maximumInputLength' => 10*/
                                                    ],
                                                ],
                                        'pluginOptions'=>[
                                            'initialize' => $initial,
                                            'depends'=>["audience-{$i}-profession_id"],
                                            'placeholder'=>'Select...',
                                            'url'=>Url::to(['/dropdown/sub-specialist']),
                                            'params'=>["profession-{$i}-id","audienceable-{$i}-type","audienceable-{$i}-id"]
                                        ]
                                    ]);
                                }
                                    ?>

                            </div>
						</div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>