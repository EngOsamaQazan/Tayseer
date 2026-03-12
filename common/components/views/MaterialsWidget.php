<?php

use common\models\Lecture;
use wbraganca\dynamicform\DynamicFormWidget;
use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Material;

?>

<div class="panel panel-default">
        
        <div class="panel-body add-material-panel">

        
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapperB2', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-itemsB2', // required: css class selector
                'widgetItem' => '.item', // required: css class
                'limit' => 10, // the maximum times, an element can be cloned (default 999)
                'min' => 0, // 0 or 1 (default 1)
                'insertButton' => '.add-itemB2', // css class
                'deleteButton' => '.remove-itemB2', // css class
                'model' => $modelsMaterial[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'name',
                    'link',
                    'filepath',
                ],
            ]); ?>

            <div class="container-itemsB2"><!-- widgetContainer -->
                <div class="panel-heading">
					<h4 class="pull-left"><i class="glyphicon glyphicon-tree"></i> <?=yii::t('app','Add material') ?> </h4>
					<div class="pull-right">
						<button type="button" onclick="" class="add-itemB2 btn btn-success btn-xs"><i title="<?=yii::t('app','Add Material') ?>" class="glyphicon glyphicon-plus"></i></button>
					</div>
                    <div class="clearfix"></div>
                </div>
                <div class="previous-added-material">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th style="width:20%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $count=0; foreach ($modelsMaterial as $i => $modelMaterial): ?>
                            <tr>
                                <td><?php echo  $modelMaterial['name']; ?></td>
                                <td><?php if($modelMaterial['type']==1) echo 'Material';if($modelMaterial['type']==2) echo 'Recorded' ?></td>
                                <td style="width:20%;">
                                    <button type="button" onclick="view_material(<?php echo $count; ?>)" class="btn btn-default link-btn">Edit</button>
                                    <button type="button" class="remove-itemB2 btn btn-default link-btn">Delete</button>
                                </td>
                            </tr>
                            <?php $count++; endforeach; ?>
                        </tbody>
                    </table>

                    <div class="pull-right">
                    <button type="button" onclick="" class="add-itemB2 btn btn-success ">Add Material</button>
                    </div>
                    <div class="clearfix"></div>
                </div>

<!------------------------------------------------------------------------->
            <?php    
             foreach ($modelsMaterial as $i => $modelMaterial): ?>
               <?php  ?>
                <div class="item panel panel-default <?php echo 'material_'.$i; ?>  "><!-- widgetBody -->
                    <div class="panel-heading">
                        <div class="pull-right">
                            <button type="button"  onclick="" class="add-itemB2 btn btn-success btn-xs"><i title="<?=yii::t('app','Add') ?>" class="glyphicon glyphicon-plus"></i></button>
                            <button type="button" class="remove-itemB2 btn btn-danger btn-xs"><i title="<?=yii::t('app','Delete') ?>" class="glyphicon glyphicon-minus"></i></button>
                            <button type="button" class="edit-itemB2 btn btn-warning btn-xs"><i title="<?=yii::t('app','Edit') ?>" class="glyphicon glyphicon-edit"></i></button>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php

                        
                            // necessary for update action.
                            if (! $modelMaterial->isNewRecord) {
                                echo Html::activeHiddenInput($modelMaterial, "[{$i}]id");
                            }
                        ?>
                          <?php if(isset($modelsLecture->lecture_type) && $modelsLecture->type == Lecture::RECORDED_LECTURE_TYPE ){ ?>
						
                        <?php echo $form->field($modelMaterial, "[{$i}]type")->hiddenInput(['value'=>1])->label(false); ?>    
                        <?php }else{ ?>
	              <div class="row row2">
                  <div class="col-sm-2">
                  <label><?=yii::t('app','Type') ?></label>
                 </div>
                  <div class="col-sm-6">
                     <?php $modelMaterial->isNewRecord ? $modelMaterial->type=1:$modelMaterial->type;?>
                     <?= $form->field($modelMaterial, "[{$i}]type")->radioList(Yii::$app->params['MaterialType'])->label(false); ?>
                      </div>

                        </div>	
                        	<?php	} ?>
                        <div class="row row2">
						    <div class="col-sm-2">
						      <label><?=yii::t('app','Name') ?></label>
							</div>
                            <div class="col-sm-6">
                                <?= $form->field($modelMaterial, "[{$i}]name")->textInput(['maxlength' => true])->label(false); ?>
                            </div>
						</div>
						
						<div class="row row2">
						    <div class="col-sm-2">
                                
						      <label><?=yii::t('app','Link') ?></label>
							</div>
                            <div class="col-sm-6">
                                <?= $form->field($modelMaterial, "[{$i}]link")->textInput(['maxlength' => true])->label(false); ?>
                            </div>
                        </div><!-- .row -->
                        <div class="row row2">
						    <div class="col-sm-12">
						      <label><?=yii::t('app','File') ?></label>
                                    <?php 
                                    $file = '';
                                    $infoFile = [];
                                    $fileTypes = ['video/mp4' => 'video', 'application/pdf' => 'pdf'];
                                  
                                    if (!empty($modelMaterial->filepath)){
                                        $file = [];
										$file[]=$modelMaterial->filepath;
                                        $type1 = (isset($fileTypes[$modelMaterial->filemime])) ? $fileTypes[$modelMaterial->filemime] : 'image';
                                        $infoFile[] = array('caption' => $modelMaterial->name, 'size' => $modelMaterial->filesize, 'type' => $type1, 'filetype' => $modelMaterial->filemime);
                                    }
                                
                                    print $form->field($modelMaterial, "[{$i}]filepath")->widget(FileInput::classname(), [
                                    'pluginOptions' => [
                                            /*'previewFileType' => 'any',
                                            'initialPreview'=> !empty($modelMaterial->filepath) ? $modelMaterial->filepath : '',
                                            'initialPreviewAsData'=>true,*/
                                            'allowedPreviewTypes' => ['image', 'html', 'text', 'video', 'audio', 'flash', 'object'],
                                            'previewFileType' => 'any',
                                            'showUpload' => false,
                                            'showRemove' => true,
                                            'removeLabel' => '',
                                            'removeClass' => '',
                                            'initialPreviewShowDelete' => false,
                                            'initialPreview'=> $file,
                                            'initialPreviewAsData'=>true,
                                            'initialPreviewConfig' => $infoFile,
                                            'deleteUrl' => Url::to(['workshop/delete-file', 'id' => $modelMaterial->id]),
                                            'overwriteInitial'=>true,
                                    ],
                                    ])->label(false);  ?>
                            </div>
						</div><!-- .row -->
                        <div class="panel-foter">
                            <div class="pull-right">
                         


                                <button type="button" onclick="" class="add-itemB2 btn btn-success ">Add another</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>



            
            </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>


<?php 
$script = <<< JS


    $(document).ready(function(){
 $(".add-itemB2").mouseup(function(){
    setTimeout(function(){
        $('.container-itemsB2 div.panel-default:last').removeClass('hidden').delay( 8000 )  
}, 1000);
 
    });

 
  });

JS;
$this->registerJs($script);
?>

