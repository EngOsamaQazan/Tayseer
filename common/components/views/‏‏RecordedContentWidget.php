<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\OldLectureTime;
use common\models\ExamQuestion;
use common\models\Course;
use common\components\GeneralHelpers;
use common\models\Subscription;



 $slug = Yii::$app->request->get('slug');
 $course_id = Yii::$app->request->get('id');
 $course = Course::find()->where(['vma_course_id' =>$course_id])->orWhere(['slug' =>$slug])->one();
 
$course_date = isset($course->date)?$course->date:'';
$course_start_date = isset($course->start_date)?$course->start_date:''; 
$course_end_date = isset($course->end_date)?$course->end_date:''; 
$course_register = isset($course->registration_policy)?$course->registration_policy:'';

    $homeActivate = $contentActivate = $userActivate = false;    

 
    $content = ['session','lecture','workshop','ticket','exam','assignment','question' , 'exam_assessment' ,'quiz_assessments' , 'assignments_assessments'];
    $slug = Yii::$app->request->get('slug');
	
	if (!Yii::$app->user->isGuest){
   $subscription = Subscription::find()
                ->andWhere(['user_id' => Yii::$app->user->identity->id,
                    'course_id' => $course->vma_course_id ])
                ->one();
				
     }	
	 ?>
    <?php 
	
    $arrayClass = ['1'=>'col-sm-7','2'=>'col-sm-11'];
	
    switch ($type) {
            case 'lecture':?>
			<?php 
   
			/*--=====================Recorded lecture =====================-*/
    	
			if($model->type == Lecture::RECORDED_LECTURE_TYPE){
			$lecture_dates = OldLectureTime::find()->where(['lecture_id' => $model->id])->asArray()->orderBy('start_date ASC')->one();?>
                 
			    
					<div class="lef col-xs-6 col-sm-7">
						
						<div class="colapse-lec minus-ico width_name">
						<?php  if ($model->type == 4) { ?>
								<span><img src="<?=yii::$app->homeUrl?>images/tab3.png"></span>
							<?php } else { ?>
								<span><img src="<?=yii::$app->homeUrl?>images/tab2.png"><span></span>
							<?php }
						?>
							
							<div class="lecture-name">
				
               
								<h4><?php  if ($contentId!=null){?>    
								<a class="content_a" href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><?=$model->name?> </a>
								<?php }else{?>    
									<?=$model->name?>
								<?php }?>   
                                 <?php 
								 if ($model->attendence==1){ ?>
									
									<div class="bordered-badge_required">
									
										Required
									
									</div>
									
									<?php } ?>
								</h4>
								<?php if ($model->_speakers!=''){ ?>
								<span><p><?= $model->_speakers ?></p></span>
								<?php }?>   
								
							</div>
						</div>
						<div class="lecture-time time-recorded">
							<p>
								<span class="">

                                
                                    <?php
                                    if ($lecture_dates && $model->availability_settings == 2) {
                                        $datetime1 = $lecture_dates['start_date'];
                                        $datetime2 = $lecture_dates['end_date'];
                                        if ($datetime1 == null && $datetime2 != null) {
                                              $date2 = Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                            $time2 = date('h:i A', strtotime($datetime2));
                                            echo '<i class="fa fa-calendar" aria-hidden="true"></i> Due Date : ' . $date2 . ' | ' . $time2;
                                        } elseif ($datetime2 == null && $datetime1 != null) {
                                            $date1 = Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                            $time1 = date('h:i A', strtotime($datetime1));
                                            echo '<i class="fa fa-calendar" aria-hidden="true"></i> From: ' . $date1 . ' | ' . $time1;

                                        } elseif ($datetime2 != null && $datetime1 != null) {
                                            $date1 = Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                            $time1 = date('h:i A', strtotime($datetime1));
                                            $date2 =Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                            $time2 = date('h:i A', strtotime($datetime2));

                                            echo '<i class="fa fa-calendar" aria-hidden="true"></i><B> From : </B>' . $date1 . ' - ' . $time1 . ' &ensp; <B>To : </B>' . $date2 . ' - ' . $time2;

                                        }

                                    } else {
                                        if ($model->availability_settings == 1 && $course_date == 3 && $course_start_date != null && $course_end_date != null) {

                                            $datetime1 = $course_start_date;
                                            $datetime2 = $course_end_date;
                                            $date1 = Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                            $time1 = date('h:i A', strtotime($datetime1));
                                            $date2 = Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                            $time2 = date('h:i A', strtotime($datetime2));
                                            echo '';
                                          // echo '<B>From: </B>' . $date1 . ' ' . $time1 . ' &ensp; <B>To: </B>' . $date2 . ' ' . $time2;
                                        }
                                        if ($model->availability_settings == 3) {
                                            $start_on = $model->start_day_on;
                                            $end_on = $model->end_day_on;

                                            if ($start_on == null && $end_on != null) {
                                                echo '<i class="fa fa-calendar" aria-hidden="true"></i><B> Due On : </B>' . $end_on . ' days from enrollment  ';
                                            } elseif ($start_on != null && $end_on == null) {
                                                echo '<i class="fa fa-calendar" aria-hidden="true"></i><B> Start On : </B>' . $start_on . ' days from enrollment  ';
                                            } elseif ($start_on != null && $end_on != null) {
                                                echo '<i class="fa fa-calendar" aria-hidden="true"></i><B> Start On : </B>' . $start_on . ' days from enrollment  - ' . '<B>End On: </B>' . $end_on . ' days from enrollment  ';
                                            }
                                        }
                                    }
                                    ?>
                            </span>
							</p> 
							
						</div>
					</div>
					
				<!----preview--->
				<?php
				if($course_register == 1){?>
				<div class="col-xs-6 col-sm-3 ">
				</div>
				<?php }?>
				<?php  if ($model->trial==1){ ?>
				<div class="col-xs-6 col-sm-1 " <?php /*if($course_register == 1){?> class="col-xs-6 col-sm-1 " <?php }else{?> class="col-xs-6 col-sm-1 "<?php } */?>>
                     
                       
                        <div class="course_preview">
                            <a href="" data-toggle="modal" data-target="#lectureCourse<?=$model->id?>"> <?= Yii::t('app','Preview')?> </a>
                        </div>
                        
                </div>
				<?php }else{?>
				<div class="col-xs-5 col-sm-1 text-center">
				</div>
				<?php } ?>
				<?php  if ($model->type!=4){ ?>
				
                <div class="col-xs-5 col-sm-1 text-center">
                    3:15
                </div>
				<?php } ?>
				<!----price--->
				<?php if(empty($subscription) || $subscription->subscription_status != Subscription::PAID){
			
				if($course_register == 2 || $course_register == 3){
					?>
				<div class="note-badge   ">
				<?php
				
					if($model->price > 0){
				?>
					<?= floatval($model->price) ?> SR
				<?php }else { ?>
				Free
				<?php } ?>
				</div>
				<?php } }?>
					
					
                <div class="clearfix"></div>
			<?php }?>
			
			 <!--=====================Live lecture =====================-->
    	
			
			<?php if($model->type == \common\models\Lecture::LIVE_LECTURE_TYPE){
		    /* if(empty($subscription) || $subscription->subscription_status != Subscription::PAID){
			  $lecture_dates = LectureTime::find()->where(['lecture_id' => $model->id])->asArray()->orderBy('start_date ASC')->all();
				
			} */
			
			 $lecture_dates = OldLectureTime::find()->where(['lecture_id' => $model->id])->asArray()->orderBy('start_date ASC')->all();
			 $lecture_dates_one = OldLectureTime::find()->where(['lecture_id' => $model->id])->asArray()->orderBy('start_date ASC')->one();

			?>
			<div class="lef col-xs-6 col-sm-7 ">
			

						
						<div class="colapse-lec minus-ico width_name">
						 <?php 
							if($model->locations != null){
						    foreach ($model->locations as $location) {?>
						    <?php if( $location->location_type == "Onsite"  ){ ?>
							<i class="fa fa-minus hideLecDet hidden" aria-hidden="true"></i>
							<i class="fa fa-plus showLecDet" aria-hidden="true"></i>

							<?php break;  }
							elseif( $location->location_type == "Online" &&  count($lecture_dates) > 1){ ?>
							 <i class="fa fa-minus hideLecDet hidden" aria-hidden="true"></i>
							 <i class="fa fa-plus showLecDet" aria-hidden="true"></i>
							
							
							<?php }}}else{
								if(count($lecture_dates) >= 1){?>
								<i class="fa fa-minus hideLecDet hidden" aria-hidden="true"></i>
							    <i class="fa fa-plus showLecDet" aria-hidden="true"></i>
							
							<?php }}?>
							<div class="lecture-name recorded_name">
							
							<?php if($model->locations != null){
								foreach ($model->locations as $location) {
								 if( $location->location_type == "Onsite"  ){ ?>
							<h4><img src="<?=yii::$app->homeUrl?>images/titlecourse5.png">
							
							<?php  if ($contentId!=null){?>    
								<a class="content_a" href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><?=$model->name?> </a>
								<?php }else{?>    
									<?=$model->name?>
								<?php }?>   
							 <?php 
								 if ($model->attendence==1){ ?>
									
									<div class="bordered-badge_required">
									
										Required
									
									</div>
									
									<?php } ?>

							</h4>

							<?php break;}
							elseif( $location->location_type == "Online" /*&&  count($lecture_dates) > 1*/){ ?>
							 <h4><img src="<?=yii::$app->homeUrl?>images/titlecourse7.png">
							
							<?php  if ($contentId!=null){?>    
								<a class="content_a" href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><?=$model->name?> </a>
								<?php }else{?>    
									<?=$model->name?>
								<?php }?>
							
							 <?php 
								 if ($model->attendence==1){ ?>
									
									<div class="bordered-badge_required">
									
										Required
									
									</div>
									
									<?php } ?>

							</h4>
							
							
							<?php break; }
								
							    }
							    }else{?>
								 <h4><img src="<?=yii::$app->homeUrl?>images/titlecourse7.png">
								<?php  if ($contentId!=null){?>    
								<a class="content_a" href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><?=$model->name?> </a>
								<?php }else{?>    
									<?=$model->name?>
								<?php }?>  
								
								 <?php 
								 if ($model->attendence==1){ ?>
									
									<div class="bordered-badge_required">
									
										Required
									
									</div>
									
									<?php } ?>
								
								</h4>
								<?php }?>
								
								
								<?php if ($model->_speakers!=''){ ?>
								<span><p><?= $model->_speakers ?></p></span>
								<?php }?> 

															
								<ul class="lecture-det hidden" >
								
								<!--********** LOCATION *********-->
								
								<?php if($model->locations != null){ ?>
									<li class="margin_li">
								 <?php foreach ($model->locations as $location) {?>
						
						          <?php if($location->location_type == 'Onsite' && $location->location_inner_type == 'Default'){
									  
								   $info = $location->info; ?>

								   <img src="<?=yii::$app->homeUrl?>images/address.png"><span> Onsite : <?= $info['hall_name']?> </span>			
				
						            <?php } ?>
									
								
									
									<?php if($location->location_type == 'Onsite' && $location->location_inner_type == 'New_location'){
                                     $info = $location->info;?>
								    
								     <img src="<?=yii::$app->homeUrl?>images/address.png"><span> Onsite :  <?= GeneralHelpers::getCountry($info['country_id'])?> , <?= GeneralHelpers::getCity($info['city_id'])?> , <?= $info['description'] ?> </span>		
					                
								
									<?php }

										}?>
								    <?php }?>
										
									</li>
									
								
								
								
								<!--**********END LOCATION *********-->
								
									<?php
                                    if($lecture_dates != null){
									   if (count($lecture_dates) == 1){?>
									
                                       <li  class="margin_li" >
										<img src="<?=yii::$app->homeUrl?>images/calender.png">
										   
													
                                                     <span><?php
                                                        $datetime1 = $lecture_dates_one['start_date'];
                                                        $datetime2 = $lecture_dates_one['end_date'];
                                                        
                                                            if ($datetime1 == null && $datetime2 != null) {
                                                                $date1 = Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                                                $time1 = Yii::$app->formatter->asDate($datetime2, 'php:H:i a') ;
                                                                echo 'Due Date: ' . $date1 . ' <B>:</B> ' . $time1;
                                                            } elseif ($datetime2 == null && $datetime1 != null) {
                                                                $date2 = Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                                                $time2 = Yii::$app->formatter->asDate($datetime1, 'php:H:i a') ;
                                                                echo 'From: ' . $date2 . ' <B>:</B> ' . $time2;
                                                            } elseif ($datetime2 != null && $datetime1 != null) {
                                                                $date1 =  Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                                                $time1 = Yii::$app->formatter->asDate($datetime1, 'php:H:i a') ;
                                                                $date2 = Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                                                $time2 = Yii::$app->formatter->asDate($datetime2, 'php:H:i a');
                                                                if ($date1 != $date2) {
                                                                     echo '<B></B>' . $date1 . ' to ' . $date2 . ' | ' . $time1 . ' to ' . $time2;
                                                                  
                                                                 
                                                                } else {
																    echo '<B></B>' . $date1 . ' | ' . $time1 . ' to ' . $time2;
																	
                                                                }

                                                            }
                                                         
                                                        ?>
                                                </span>
						                         
                                                   </li>									
									<?php }elseif (count($lecture_dates) > 1){
                                    
									$i = 1;
									foreach ($lecture_dates as $dates) { ?>
									<li  class="margin_li" >
										<img src="<?=yii::$app->homeUrl?>images/calender.png">
										   
													
                                                <span><?php
                                                        $datetime1 = $dates['start_date'];
                                                        $datetime2 = $dates['end_date'];
                                                        
                                                            if ($datetime1 == null && $datetime2 != null) {
                                                                $date1 = Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                                                $time1 = Yii::$app->formatter->asDate($datetime2, 'php:H:i a') ;
                                                                echo '<B>Date ' . $i . '</B> Due Date: ' . $date1 . ' <B>:</B> ' . $time1;
                                                            } elseif ($datetime2 == null && $datetime1 != null) {
                                                                $date2 = Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                                                echo '<B>Date ' . $i . '</B> From: ' . $date2 . ' <B>:</B> ' . $time2;
                                                            } elseif ($datetime2 != null && $datetime1 != null) {
                                                                $date1 =  Yii::$app->formatter->asDate($datetime1, 'php:d M Y');
                                                                $time1 = Yii::$app->formatter->asDate($datetime1, 'php:H:i a') ;
                                                                $date2 = Yii::$app->formatter->asDate($datetime2, 'php:d M Y');
                                                                $time2 = Yii::$app->formatter->asDate($datetime2, 'php:H:i a');
                                                                if ($date1 != $date2) {
                                                                    echo '<B> Option ' . $i . ': </B>' . $date1 . ' to ' . $date2 . ' | ' . $time1 . ' to ' . $time2;
                                                                   // echo  ' '.$date1 . " to " . $date2;
                                                                 
                                                                } else {
                                                                    echo '<B> Option ' . $i . ': </B>' . $date1 . ' | ' . $time1 . ' to ' . $time2;
																	//echo ' '.$date1;
                                                                }
                                                            }


                                                        
                                                        ?>
                                                </span>
						                         
                                    </li>
					                            <?php $i++;
									    } 
									}
									}?>
									
								</ul>   
							</div>
							
						</div>
						
					</div>
					<?php if($course_register == 1){?>
					<div class="col-xs-6 col-sm-5 ">
					</div>
					<?php }?>
					<!----location--->
					<div class="col-xs-3 col-sm-3 margin_">
					<?php  foreach ($model->locations as $location) {
							if ($location->location_type == 'Onsite'){
							?>
					<div class="right-badges-divs-rec ">
						<div class="bordered-badge">
						
						
							Onsite
						
						
						</div>
					</div>
					<?php }
					if ($location->location_type == 'Online'){
							?>
					<div class="right-badges-divs-rec ">
						<div class="bordered-badge-online">
						
							Webinar
						
						</div>
					</div>
					<?php }?>
					<?php }?>
					</div>
					
					<!----price--->
					<?php if(empty($subscription) || $subscription->subscription_status != Subscription::PAID){
			
					if($course_register == 2 || $course_register == 3){
				?>
					<div class="note-badge ">
					<?php
					
						if($model->price > 0){
					?>
						<?= floatval($model->price) ?> SR
					<?php }else { ?>
					Free
					<?php } ?>
					</div>
					<?php } }?>
					<div class="clearfix"></div>
					
	                <?php } ?>
			
    <?php   break;
	
	/*===================== Exam Assessment=====================*/
      
		case 'exam_assessment': ?>
       <div class="lef col-sm-12">
	     <?php $question_num = \common\models\ExamQuestion::find()->where(['exam_id'=>$model['id']])->count();
         	 ?>
		   
			<div class="minus-ico width_name col-sm-4">
                
           
            <?php if ($contentId != null) { ?>
                <h4> <img src="<?=yii::$app->homeUrl?>images/titlecourse4.png"/><a class="content_a" href="<?=Url::to(["course/{$slug}/learn-ass/{$contentId}"])?>"><?= $model['name'] ?></a></h4>
            <?php } else { ?>
                <h4> <img src="<?=yii::$app->homeUrl?>images/titlecourse4.png"/><?= $model['name'] ?></h4>
            <?php } ?>
			<span><p></p></span> 
            
            
			</div>
			<div class="col-xs-3 col-sm-3">
            </div>
			<div class="  col-xs-3 col-sm-3   <?php if($course_register == 1){?> question_num text-center<?php } ?>">
			<?php if($question_num > 0){
				echo '<span><p>'.$question_num .' ' .' Questions </p></span>' ;
			} ?>
            </div>
			<div class="lecture-time time-recorded col-sm-12">
			<p><?php if ($model['avilability_setting'] != '1') {
			?><i class="fa fa-calendar" aria-hidden="true"></i><?php } ?>
                <span class="">
<!--                float-right-->
            <?php if ($model['end_date'] != null && $model['start_date'] == null) { ?>
                <?php
                $date2 = Yii::$app->formatter->asDate($model['end_date'], 'php:d M Y');
                $time2 = date('h:i A', strtotime($model['end_date']));
                echo '<B>Due Date: </B>' . $date2 . ' <B>:</B> ' . $time2;
            } elseif ($model['end_date'] == null && $model['start_date'] != null) {
               $date1 = Yii::$app->formatter->asDate($model['start_date'], 'php:d M Y');
               
                $time1 = date('h:i A', strtotime($model['start_date']));
                echo '<B>From: </B>' . $date1 . ' <B>:</B> ' . $time1;
            } elseif ($model['end_date'] != null && $model['start_date'] != null) {
              $date1 = Yii::$app->formatter->asDate($model['start_date'], 'php:d M Y');
               
                $time1 = date('h:i A', strtotime($model['start_date']));
                $date2 = Yii::$app->formatter->asDate($model['end_date'], 'php:d M Y');
               
                $time2 = date('h:i A', strtotime($model['end_date'])); ?>
                <?php
                if ($date1 == $date2) {
                    echo $date1 . ' |  ' . $time1 . ' <B>-</B> ' . $time2;
                } else {
                    echo '<B>From : </B>' . $date1 . ' | ' . $time1 . ' &ensp; <B>To : </B>' . $date2 . ' | ' . $time2;
                } ?>
            <?php } elseif ($model['avilability_setting'] == '3') {


                if (!empty($model['start_on']) && !empty($model['end_on'])) {
                    echo $model['start_on'] . ' day from enrollment' . ' to ' . $model['end_on'] . ' day from enrollment';
                } elseif (!empty($model['start_on'])) {
                    echo 'Start date: ' . $model['start_on'] . ' day from enrollment';
                } elseif (!empty($model['end_on'])) {
                    echo 'Due date: ' . $model['end_on'] . ' day from enrollment';

                }

            }

            ?>
			</p>
            </span>
            </div>
             
        </div>
        <div class="clearfix"></div>
		
		<!--===================== Assignments Assessment=====================-->
    
					
        <?php break;

		case 'assignments_assessments': ?>
       <div class="lef col-sm-12">
	   
		   
			<div class="minus-ico width_name col-sm-4">
                
           
            <?php if ($contentId != null) { ?>
                <h4> <img class="assignment_icon" src="<?=yii::$app->homeUrl?>images/tab4.png"/><a class="content_a" href="<?=Url::to(["course/{$slug}/learn-ass/{$contentId}"])?>"><?= $model['name'] ?></a></h4>
            <?php } else { ?>
                <h4> <img class="assignment_icon" src="<?=yii::$app->homeUrl?>images/tab4.png"/><?= $model['name'] ?></h4>
            <?php } ?>
			
            
			</div>
			<div class="lecture-time time-recorded-assessment col-sm-12">
			<p><?php if ($model['avilability_setting'] != '1') {
			?><i class="fa fa-calendar" aria-hidden="true"></i><?php } ?>
                <span class="">
            <!-- float-right-->
            <?php if ($model['end_date'] != null && $model['start_date'] == null) { ?>
                <?php
                $date2 = Yii::$app->formatter->asDate($model['end_date'], 'php:d M Y');
                $time2 = date('h:i A', strtotime($model['end_date']));
                echo '<B>Due Date: </B>' . $date2 . ' <B>:</B> ' . $time2;
            } elseif ($model['end_date'] == null && $model['start_date'] != null) {
               $date1 = Yii::$app->formatter->asDate($model['start_date'], 'php:d M Y');
               
                $time1 = date('h:i A', strtotime($model['start_date']));
                echo '<B>From: </B>' . $date1 . ' <B>:</B> ' . $time1;
            } elseif ($model['end_date'] != null && $model['start_date'] != null) {
              $date1 = Yii::$app->formatter->asDate($model['start_date'], 'php:d M Y');
               
                $time1 = date('h:i A', strtotime($model['start_date']));
                $date2 = Yii::$app->formatter->asDate($model['end_date'], 'php:d M Y');
               
                $time2 = date('h:i A', strtotime($model['end_date'])); ?>
                <?php
                if ($date1 == $date2) {
                    echo $date1 . ' |  ' . $time1 . ' <B>-</B> ' . $time2;
                } else {
                    echo '<B>From : </B>' . $date1 . ' | ' . $time1 . ' &ensp; <B>To : </B>' . $date2 . ' | ' . $time2;
                } ?>
            <?php } elseif ($model['avilability_setting'] == '3') {


                if (!empty($model['start_on']) && !empty($model['end_on'])) {
                    echo $model['start_on'] . ' day from enrollment' . ' to ' . $model['end_on'] . ' day from enrollment';
                } elseif (!empty($model['start_on'])) {
                    echo 'Start date: ' . $model['start_on'] . ' day from enrollment';
                } elseif (!empty($model['end_on'])) {
                    echo 'Due date: ' . $model['end_on'] . ' day from enrollment';

                }

            }

            ?>
			</p>
            </span>
            </div>
             
        </div>
        <div class="clearfix"></div>
		
        <!--===================== Quiz Assessment=====================-->
    		
        <?php break;
        case 'quiz_assessments': ?>
        <div class="lef col-sm-12">
		<?php $question_num = \common\models\ExamQuestion::find()->where(['exam_id'=>$model['id']])->count();
         	 ?>
		  
	   
		   
			<div class="minus-ico width_name col-sm-4">
                
           
            <?php if ($contentId != null) { ?>
                <h4><img src="<?=yii::$app->homeUrl?>images/titlecourse4.png"/><a class="content_a" href="<?=Url::to(["course/{$slug}/learn-ass/{$contentId}"])?>"><?= $model['name'] ?></a></h4>
            <?php } else { ?>
                <h4><img src="<?=yii::$app->homeUrl?>images/titlecourse4.png"/><?= $model['name'] ?></h4>
            <?php } ?>
			 
            
			</div>
			<div class="  col-xs-3 col-sm-3   <?php if($course_register == 1){?> question_num text-center<?php } ?>">
			<?php if($question_num > 0){
				echo '<span><p>'.$question_num .' ' .' Questions </p></span>' ;
			} ?>
            </div>
			<div class="lecture-time time-recorded-quiz col-sm-12">
			<p><?php if ($model['avilability_setting'] != '1') {
			?><i class="fa fa-calendar" aria-hidden="true"></i><?php } ?>
                <span class="">
            <!-- float-right-->
            <?php if ($model['end_date'] != null && $model['start_date'] == null) { ?>
                <?php
                $date2 = Yii::$app->formatter->asDate($model['end_date'], 'php:d M Y');
                $time2 = date('h:i A', strtotime($model['end_date']));
                echo '<B>Due Date: </B>' . $date2 . ' <B>:</B> ' . $time2;
            } elseif ($model['end_date'] == null && $model['start_date'] != null) {
               $date1 = Yii::$app->formatter->asDate($model['start_date'], 'php:d M Y');
               
                $time1 = date('h:i A', strtotime($model['start_date']));
                echo '<B>From: </B>' . $date1 . ' <B>:</B> ' . $time1;
            } elseif ($model['end_date'] != null && $model['start_date'] != null) {
              $date1 = Yii::$app->formatter->asDate($model['start_date'], 'php:d M Y');
               
                $time1 = date('h:i A', strtotime($model['start_date']));
                $date2 = Yii::$app->formatter->asDate($model['end_date'], 'php:d M Y');
               
                $time2 = date('h:i A', strtotime($model['end_date'])); ?>
                <?php
                if ($date1 == $date2) {
                    echo $date1 . ' |  ' . $time1 . ' <B>-</B> ' . $time2;
                } else {
                    echo '<B>From : </B>' . $date1 . ' | ' . $time1 . ' &ensp; <B>To : </B>' . $date2 . ' | ' . $time2;
                } ?>
            <?php } elseif ($model['avilability_setting'] == '3') {


                if (!empty($model['start_on']) && !empty($model['end_on'])) {
                    echo $model['start_on'] . ' day from enrollment' . ' to ' . $model['end_on'] . ' day from enrollment';
                } elseif (!empty($model['start_on'])) {
                    echo 'Start date: ' . $model['start_on'] . ' day from enrollment';
                } elseif (!empty($model['end_on'])) {
                    echo 'Due date: ' . $model['end_on'] . ' day from enrollment';

                }

            }

            ?>
			 </span>
			</p>
           
            </div>
           
        </div>

        <div class="clearfix"></div>
					
        <?php break;
		
		}
    ?>    
    
