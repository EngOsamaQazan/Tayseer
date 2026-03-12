<?php
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use common\models\Prerequisite;
use kartik\select2\Select2;

?>
<div class="">
        
        <div class="">

        
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapperB3', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-itemsB3', // required: css class selector
                'widgetItem' => '.item3', // required: css class
                'limit' => 10, // the maximum times, an element can be cloned (default 999)
                'min' => 0, // 0 or 1 (default 1)
                'insertButton' => '.add-itemB3', // css class
                'deleteButton' => '.remove-itemB3', // css class
                'model' => $modelsPrerequisite[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'prerequisite_course_id',
                    'required',
                ],
            ]); ?>

            

            <div class="container-itemsB3"><!-- widgetContainer -->
                
                <div class="clearfix"></div>
                <?php foreach ($modelsPrerequisite as $ii => $modelPrerequisite): ?>
                    <div class="item3 "><!-- widgetBody -->
                       
                        <div class="panel-body padding-0">
                            <?php
                                // necessary for update action.
                                //$iinitial = false;
                                if (! $modelPrerequisite->isNewRecord) {
                                    echo Html::activeHiddenInput($modelPrerequisite, "[{$ii}]id");
                                }
                                $courseText = empty($modelPrerequisite->prerequisite_course_id) ? '' : $modelPrerequisite->prerequisiteCourse->name;
                            ?>
    						<div class="row">
                                <div class="col-sm-2"></div>
    							<div class="col-sm-4">
                                    <?php echo $form->field($modelPrerequisite, "[{$ii}]prerequisite_course_id")->widget(Select2::classname(), [
                                        'initValueText' => $courseText, // set the initial display text
                                        'options' => ['placeholder' => Yii::t('app','Select a course to be prerequistied')],
                                        'pluginOptions' => [
    									    
                                            'allowClear' => true,
                                            'minimumInputLength' => 3,
                                            'language' => [
                                                'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                                            ],
                                            'ajax' => [
                                                'url' => Url::to(['/dropdown/available-course']),
                                                'dataType' => 'json',
                                                'data' => new JsExpression('function(params) { return {q:params.term}; }')
                                            ],
                                            /*'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                                            'templateResult' => new JsExpression('function(city) { return city.text; }'),
                                            'templateSelection' => new JsExpression('function (city) { return city.text; }'),*/
                                        ],
                                    ])->label(false);?>
                                </div>
                                <div class="col-sm-2">
                                    <?= $form->field($modelPrerequisite, "[{$ii}]required")->checkbox(['label' => Yii::t('app','Required')]) ?>
                                </div> 
    							<div class="col-sm-1">
                                    <button type="button" class="margin_left remove-itemB3 btn btn-danger btn-xs" style="margin-top:4px;"><i title="<?=yii::t('app','Delete') ?>" class="glyphicon glyphicon-minus"></i></button>
                                </div>
    						</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                    
            </div>
			<div class="row">
               <div class="col-sm-2 col-xs-3">
                    
                </div>
                <div class="col-sm-4 col-xs-8">
                    <div class="form-group margin_top_15-">
                      
                        <a href="#" class="small-preq-link ">
                            <button type="button" class="add-itemB3 btn">Add Courses As Prerequisite</button>
                        </a>
                    </div> 
                </div>         
            </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>