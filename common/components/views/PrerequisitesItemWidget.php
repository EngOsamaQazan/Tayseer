<?php
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use common\models\PrerequisiteItem;
use kartik\select2\Select2;

if ($has_prerequisites==1){
	//$PrerequisitesClass = '';
	$PrerequisitesRequired = 'courserequired';
	
}else{
	//$PrerequisitesClass = 'hidden';
	$PrerequisitesRequired = '';
}
//print_r($has_prerequisites);die();
?>
<div class="">
        
        <div class="">

        
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapperBx3', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-itemsBx3', // required: css class selector
                'widgetItem' => '.itemx3', // required: css class
                'limit' => 10, // the maximum times, an element can be cloned (default 999)
                'min' => 0, // 0 or 1 (default 1)
                'insertButton' => '.add-itemBx3', // css class
                'deleteButton' => '.remove-itemBx3', // css class
                'model' => $modelsPrerequisiteItem[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    
                    'item',
                ],
            ]); ?>

            
            <div class="container-itemsBx3"><!-- widgetContainer -->
                <!-- <div class="panel-heading">
                    <div class="pull-left">
                        <button type="button" class="add-itemBx3 btn btn-success btn-xs"><i title="<?=yii::t('app','Add Prerequisite') ?>" class="glyphicon glyphicon-plus"></i></button>
                    </div>
					<h5 class="pull-left"><i class="glyphicon glyphicon-tree"></i> <?=yii::t('app','Add Prerequisite') ?> </h5>
                </div> -->
                <div class="clearfix"></div>
                <?php foreach ($modelsPrerequisiteItem as $i => $modelPrerequisiteItem): ?>
                    <div class="itemx3 "><!-- widgetBody -->
                        
                        <div class="panel-body padding-0">
                            <?php
                                // necessary for update action.
                                //$iinitial = false;
                                if (! $modelPrerequisiteItem->isNewRecord) {
                                    echo Html::activeHiddenInput($modelPrerequisiteItem, "[{$i}]id");
                                }
                                $itemText = empty($modelPrerequisiteItem->item) ? '' : $modelPrerequisiteItem->item;
                            ?>
    						<div class="row">
    							<div class="col-sm-2"></div>
                                <div class="col-sm-4 ">
                                    <?= $form->field($modelPrerequisiteItem, "[{$i}]item")->textInput([])->label(false) ?>
                                </div>
    							<div class="col-sm-1 text-right">
                                <button type="button" class="remove-itemBx3 btn btn-danger btn-xs" style="margin-top:4px;"><i title="<?=yii::t('app','Delete') ?>" class="glyphicon glyphicon-minus"></i></button>                            </div>
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
                        
                        <a href="#" class="small-preq-link">
                            <button type="button" class="add-itemBx3 btn">Add Text</button>
                        </a>
                        
                    </div> 
                </div>         
            </div>
            <?php DynamicFormWidget::end(); ?>
			

        </div>
    </div>