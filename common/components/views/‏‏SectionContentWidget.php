<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\ExamQuestion;
 

    $homeActivate = $contentActivate = $userActivate = false;    

    $content = ['session','lecture','workshop','ticket','exam','assignment','question' , 'exam_assessment' ,'quiz_assessments' , 'assignments_assessments'];
    $slug = Yii::$app->request->get('slug');
    ?>
    <?php 
    $arrayClass = ['1'=>'col-sm-7','2'=>'col-sm-11'];
    switch ($type) {
            case 'lecture':?>
                <div class="col-xs-1 padding_0"><img src="<?=yii::$app->homeUrl?>images/tab2.png"></div>
                <div class="col-xs-11 <?=$arrayClass[$model->lecture_type]?> ">
                    
                <?php 
                 if ($contentId!=null){?>    
                <a href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"> <h4><?=$model->name?></h4> </a>
                <?php }else{?>    
                    <h4><?=$model->name?></h4>
                <?php }?>    
                <?php
                    if ($model->_speakers!=''){
                 ?>
                    <p><?= $model->_speakers ?></p>
                <?php }?>    
                </div>
                <?php if ($model->lecture_type==2){ ?>
                    <div class="schedual_branch">
                        <div class="col-sm-12">
                            <div class="col-xs-1 padding_0"><img src="<?=yii::$app->homeUrl?>images/calender.png"></div>
                            <div class="col-xs-11 col-sm-11 "> <?= $model->start_date ?></div>
                        </div>
                        <div class="col-sm-12">
                            <div class="col-xs-1 padding_0"><img src="<?=yii::$app->homeUrl?>images/address.png"></div>
                            <div class="col-xs-11 col-sm-11 ">country city region </div>
                        </div>
                    </div>
                <?php }else{ ?>
                <div class="col-xs-6 col-sm-2">
                    <?php  if ($model->trial==1){ 
                        ?>
                        <div class="course_preview">
                            <a href="" data-toggle="modal" data-target="#lectureCourse<?=$model->id?>"> <?= Yii::t('app','Preview')?> </a>
                        </div>
                        <?php } ?>
                </div>
                <div class="col-xs-5 col-sm-2 text-center">
                    3:15
                </div>
                <?php } ?>

    <?php   break;
        case 'exam_assessment': ?>
        <div class="col-xs-1 padding_0"><img src="<?=yii::$app->homeUrl?>images/titlecourse6.png"></div>
        <div class="col-xs-11 col-sm-11 ">
        
            <?php   if ($contentId!=null){?>    
                <a href="<?=Url::to(["course/{$slug}/learn-ass/{$contentId}"])?>"> <h4><?=$model?></h4> </a>
            <?php }else{?>    
                <h4><?=$model?></h4>
            <?php }?>    
           </div>
             <?php  break;

      case 'assignments_assessments': ?>
      
      <div class="col-xs-1 padding_0"><img src="<?=yii::$app->homeUrl?>images/titlecourse6.png"></div>
      <div class="col-xs-11 col-sm-11 ">
      
          <?php if ($contentId!=null){?>    
              <a href="<?=Url::to(["course/{$slug}/learn-ass/{$contentId}"])?>"> <h4><?=$model?></h4> </a>
          <?php }else{?>    
              <h4><?=$model?></h4>
          <?php }?>    
          </div>
          <?php  break;
           case 'quiz_assessments': ?>
      
           <div class="col-xs-1 padding_0"><img src="<?=yii::$app->homeUrl?>images/titlecourse6.png"></div>
           <div class="col-xs-11 col-sm-11 ">
           
               <?php if ($contentId!=null){?>    
                   <a href="<?=Url::to(["course/{$slug}/learn-ass/{$contentId}"])?>"> <h4><?=$model?></h4> </a>
               <?php }else{?>    
                   <h4><?=$model?></h4>
               <?php }?>    
               </div>
               <?php  break;
        }
    ?>    
    
    