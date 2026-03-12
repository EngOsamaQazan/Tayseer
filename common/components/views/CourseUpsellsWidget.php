<?php
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use common\models\CourseUpsell;
use kartik\select2\Select2;

?>
<div class="">
        
        <div class="unspell-panel">

        
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapperB4', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-itemsB4', // required: css class selector
                'widgetItem' => '.item4', // required: css class
                'limit' => 10, // the maximum times, an element can be cloned (default 999)
                'min' => 0, // 0 or 1 (default 1)
                'insertButton' => '.add-itemB4', // css class
                'deleteButton' => '.remove-itemB4', // css class
                'model' => $modelsCourseUpsell[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'upsell_course_id',
                    'showing_landing',
                    'showing_thank'
                ],
            ]); ?>

            <div class="container-itemsB4"><!-- widgetContainer -->
                <div class="panel-heading padding-l-0">
                    <label class="pull-left "><?=yii::t('app','Add Activity Upsell') ?> </label>
                    <div class="pull-left">
                        <button type="button" class="add-itemB4 btn btn-success btn-xs"><i title="<?=yii::t('app','Add CourseUpsell') ?>" class="glyphicon glyphicon-plus"></i></button>
                    </div>
                </div>
        <div class="clearfix"></div>
            <div class="row">
                    <div class="col-sm-4"><label><?= Yii::t('app','Course')?></label></div>
                    <div class="col-sm-3 padding-r-0"><label class="padding-r-0"><?= Yii::t('app','Show on Landing Page')?></label></div>
                    <div class="col-sm-4 padding-r-0"><label class="padding-r-0"><?= Yii::t('app','Show on Thank You Page')?></label></div>
                    <div class="col-sm-1"></div>
                </div>
            <div class="clearfix"></div>
            <?php foreach ($modelsCourseUpsell as $i => $modelCourseUpsell): ?>
                
                <div class="item4"><!-- widgetBody -->
                    
                        <?php
                            // necessary for update action.
                            //$initial = false;
                            if (! $modelCourseUpsell->isNewRecord) {
                                echo Html::activeHiddenInput($modelCourseUpsell, "[{$i}]id");
                            }
                            $courseText = empty($modelCourseUpsell->upsell_course_id) ? '' : $modelCourseUpsell->upsellCourse->name;
                        ?>
						<div class="row row2">
                            <div class="col-sm-4">
                                <?php echo $form->field($modelCourseUpsell, "[{$i}]upsell_course_id")->widget(Select2::classname(), [
                                    'initValueText' => $courseText, // set the initial display text
                                    'options' => ['placeholder' => Yii::t('app','Select a course or bundle to promot')],
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
                                    ],
                                ])->label(false);?>
                            </div>
                            <div class="col-sm-3">
                                <?= $form->field($modelCourseUpsell, "[{$i}]showing_landing")->checkbox(['label' => '']) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($modelCourseUpsell, "[{$i}]showing_thank")->checkbox(['label' => '']) ?>
                            </div>
                            <div class="col-sm-1"><button type="button" class="remove-itemB4 btn btn-danger btn-xs"><i title="<?=yii::t('app','Delete') ?>" class="glyphicon glyphicon-minus"></i></button></div>
                        </div>
                </div>
                <div class="clearfix"></div>
            <?php endforeach; ?>
            </div>
            <button type="button" class="add-itemB4 btn btn-success btn-xs"><i title="<?=yii::t('app','Add Activity Upsell') ?>" class="glyphicon glyphicon-plus"></i> Add another</button>

            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>