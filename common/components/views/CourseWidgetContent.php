<?php

use common\models\OldLectureTime;
use yii\helpers\Html;
use common\models\Lecture;

$session = Yii::$app->session;
$course_date = $session['courseInfo']['date'];
$course_start_date = $session['courseInfo']['start_date'];
$course_end_date = $session['courseInfo']['end_date'];
$course_register = $session['courseInfo']['registration_policy'];

//print_r($course_register);die();

$homeActivate = $contentActivate = $userActivate = false;

$content = ['session', 'lecture', 'workshop', 'ticket', 'exam', 'assignment', 'question', 'exam_assessment', 'quiz_assessments', 'assignments_assessments'];
$slug = Yii::$app->request->get('slug');
?>
<?php

$arrayClass = ['1' => 'col-sm-7', '2' => 'col-sm-11'];
switch ($type) {

    case 'lecture':

        ?>

        <tr class="<?= 'tr' . $increment; ?>">
            <td style="width:55%;">
                <?php
                $lecture_dates = \common\models\LectureDate::find()->where(['vma_lecture_id' => $model->vma_lecture_id])->asArray()->orderBy('start_date ASC')->all();
                if ($model->type == 2) {
                    if (count($lecture_dates) > 1) {
                        ?>

                        <span class="uncollapse-icon"><i class="fa fa-caret-up" aria-hidden="true"></i>
                        </i></span>
                    <span class="collapse-icon hidden"><i class="fa fa-caret-down" aria-hidden="true"></i></span>
                <?php } ?>
                <span><i class="fa fa-desktop" aria-hidden="true"></i></span>
                <?php
            } elseif ($model->type == 1) {
                if ($model->type == 4) {
                    ?>
                    <span><i class="fa fa-file-text-o" aria-hidden="true"></i></span>
                <?php } else { ?>
                    <span><i class="fa fa-play-circle-o" aria-hidden="true"></i></span>
                    <?php
                }
            }
            ?>


            <span class="width-100 margin-r-50">
                <?php if ($contentId != null) { ?>
                    <span><b><?= $model->name_en ?></b></span>
                <?php } else { ?>
                    <h4><?= $model->name_en ?></h4>
                <?php } ?>
                <?php
                if ($model->lectureSpeakers != '') {
                    $speakers=[];
                    foreach ($model->lectureSpeakers as $speaker){
                        /** @var $speaker \common\models\LectureSpeaker */
                        $speakers[]=$speaker->user->name;
                    }
                    ?>
                    <span><?php echo implode(',',$speakers) ?></span>
                <?php } ?>
            </span>
            <?php if ($model->publish == 0) { ?>
                <span class="red-border">Draft
                </span>
            <?php } ?>
            <?php
            if ($model->type == 1) {
                $lecture_dates = \common\models\LectureDate::find()->where(['vma_lecture_id' => $model->vma_lecture_id])->asArray()->orderBy('start_date ASC')->one();
                ?>
                <span class="">
                    <!--                                float-right-->
                    <p style="padding:20px 20px 0 20px ">
                        <?php
                        if ($lecture_dates && $model->availability_settings == 2) {
                            $datetime1 = $lecture_dates['start_date'];
                            $datetime2 = $lecture_dates['end_date'];
                            if ($datetime1 == null && $datetime2 != null) {
                                $date2 = date('Y-m-d', strtotime($datetime2));
                                $time2 = date('h:i A', strtotime($datetime2));
                                echo 'Due Date: ' . $date2 . ' <B>:</B> ' . $time2;
                            } elseif ($datetime2 == null && $datetime1 != null) {
                                $date1 = date('Y-m-d', strtotime($datetime1));
                                $time1 = date('h:i A', strtotime($datetime1));
                                echo 'From: ' . $date1 . ' <B>:</B> ' . $time1;
                            } elseif ($datetime2 != null && $datetime1 != null) {
                                $date1 = date('Y-m-d', strtotime($datetime1));
                                $time1 = date('h:i A', strtotime($datetime1));
                                $date2 = date('Y-m-d', strtotime($datetime2));
                                $time2 = date('h:i A', strtotime($datetime2));

                                echo '<B>From: </B>' . $date1 . ' ' . $time1 . ' &ensp; <B>To: </B>' . $date2 . ' ' . $time2;
                            }
                        } else {
                            if ($model->availability_settings == 1 && $course_date == 3 && $course_start_date != null && $course_end_date != null) {

                                $datetime1 = $course_start_date;
                                $datetime2 = $course_end_date;
                                $date1 = date('Y-m-d', strtotime($datetime1));
                                $time1 = date('h:i A', strtotime($datetime1));
                                $date2 = date('Y-m-d', strtotime($datetime2));
                                $time2 = date('h:i A', strtotime($datetime2));
                                echo '';
//                                            echo '<B>From: </B>' . $date1 . ' ' . $time1 . ' &ensp; <B>To: </B>' . $date2 . ' ' . $time2;
                            }
                            if ($model->availability_settings == 3) {
                                $start_on = $model->start_day_on;
                                $end_on = $model->end_day_on;

                                if ($start_on == null && $end_on != null) {
                                    echo '<B>Due On: </B>' . $end_on . ' days from enrollment  ';
                                } elseif ($start_on != null && $end_on == null) {
                                    echo '<B>Start On: </B>' . $start_on . ' days from enrollment  ';
                                } elseif ($start_on != null && $end_on != null) {
                                    echo '<B>Start On: </B>' . $start_on . ' days from enrollment  - ' . '<B>End On: </B>' . $end_on . ' days from enrollment  ';
                                }
                            }
                        }
                        ?></p>
                </span>

            <?php } ?>


        </td>

        <?php

        if ($model->type == Lecture::LIVE_LECTURE_TYPE) { ?>
            <td style="width:15%;">Live lecture</td>
        <?php } else { ?>
            <td style="width:15%;">Recorded lecture</td>
        <?php } ?>
        <td style="width:10%;">
            <?php
            $subscription_count = common\models\SubscriptionDetails::find()->joinwith('lecture')->where([Lecture::getTableSchema()->fullName . '.vma_lecture_id' => $model->vma_lecture_id])->groupBy('subscription_id')->count();
            echo $subscription_count;
            ?> 
        </td>
        <td style="width:10%;">50%</td>
        <td style="width:10%;" class="text-right">
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <button class="dropdown-toggle btn btn-default btn_padding" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">Action <span class="caret"></span></button>
                    <ul
                        class="dropdown-menu course-content-dropmenu">
                        <li> <?= Html::a(Yii::t('app', 'View Details'), ['lecture/view', 'id' => $model->vma_lecture_id,'cid'=>$course_id]) ?></li>
                        <li> <?= Html::a(Yii::t('app', 'Edit'), ['lecture/update', 'id' => $model->vma_lecture_id,'cid'=>$course_id]) ?></li>
                        <?php if ($model->publish == 1) { ?>
                            <li> <?= Html::a(Yii::t('app', 'Deactivate'), ['deactivatelecture', 'id' => $model->vma_lecture_id,'cid'=>$course_id]) ?></li>
                        <?php } else { ?>
                            <li> <?= Html::a(Yii::t('app', 'Activate'), ['deactivatelecture', 'id' => $model->vma_lecture_id,'cid'=>$course_id]) ?></li>
                        <?php } ?>

                        <?php
                        $subscription_content = common\models\SubscriptionDetails::find()->joinwith('lecture')->where([Lecture::getTableSchema()->fullName . '.vma_lecture_id' => $model->vma_lecture_id])->groupBy('subscription_id')->all();
                        if ($subscription_content) {
                            ?>
                            <li>
                                <?= Html::a(Yii::t('app', 'Participant Report'), ['lecture/participant-report', 'vma_lecture_id' => $model->vma_lecture_id,'cid'=>$course_id]) ?></li>
                        <?php } ?>   

                        <li><a href="">Content Report</a></li>
                        <li><?php
                            echo Html::a(Yii::t('app', 'Delete'), ['deletelecture', 'vma_lecture_id' => $model->vma_lecture_id,'cid'=>$course_id], [
                                'class' => '',
                                'data' => [
                                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                    'method' => 'post',
                                ],
                            ])
                            ?></li>


                    </ul>
                </li>
            </ul>
        </td>
        <!-- 23/1/2018 -->
        <?php if ($model->type == 2) { ?>
            <?php $lecture_dates = \common\models\LectureDate::find()->where(['vma_lecture_id' => $model->vma_lecture_id])->asArray()->orderBy('start_date ASC')->all(); ?>

            <tr class="nested-tr <?= 'tr' . $increment; ?>">
                <td colspan="5" class="colapse-td ">
                    <table class="inner-table table">
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($lecture_dates as $dates) {
                                ?>
                                <tr class="border-t">

                                    <td style="width:45%;">
                                        <span><?php
                                            $datetime1 = $dates['start_date'];
                                            $datetime2 = $dates['end_date'];
                                            if (count($lecture_dates) == 1) {
                                                if ($datetime1 == null && $datetime2 != null) {
                                                    $date1 = date('Y-m-d', strtotime($datetime2));
                                                    $time1 = date('h:i A', strtotime($datetime2));
                                                    echo 'Due Date: ' . $date1 . ' <B>:</B> ' . $time1;
                                                } elseif ($datetime2 == null && $datetime1 != null) {
                                                    $date2 = date('Y-m-d', strtotime($datetime1));
                                                    $time2 = date('h:i A', strtotime($datetime1));
                                                    echo 'From: ' . $date2 . ' <B>:</B> ' . $time2;
                                                } elseif ($datetime2 != null && $datetime1 != null) {
                                                    $date1 = date('Y-m-d', strtotime($datetime1));
                                                    $time1 = date('h:i A', strtotime($datetime1));
                                                    $date2 = date('Y-m-d', strtotime($datetime2));
                                                    $time2 = date('h:i A', strtotime($datetime2));
                                                    if ($date1 != $date2) {
                                                        echo '<B>Date </B>       From ' . $date1 . ' ' . $time1 . ' to ' . $date2 . ' ' . $time2;
                                                    } else {
                                                        echo '<B>Date </B>       From ' . $date1 . ' - ' . $time1 . ' to  ' . $time2;
                                                    }
                                                }
                                            } else {
                                                if ($datetime1 == null && $datetime2 != null) {
                                                    $date1 = date('Y-m-d', strtotime($datetime2));
                                                    $time1 = date('h:i A', strtotime($datetime2));
                                                    echo '<B>Date ' . $i . '</B> Due Date: ' . $date1 . ' <B>:</B> ' . $time1;
                                                } elseif ($datetime2 == null && $datetime1 != null) {
                                                    $date2 = date('Y-m-d', strtotime($datetime1));
                                                    $time2 = date('h:i A', strtotime($datetime1));
                                                    echo '<B>Date ' . $i . '</B> From: ' . $date2 . ' <B>:</B> ' . $time2;
                                                } elseif ($datetime2 != null && $datetime1 != null) {
                                                    $date1 = date('Y-m-d', strtotime($datetime1));
                                                    $time1 = date('h:i A', strtotime($datetime1));
                                                    $date2 = date('Y-m-d', strtotime($datetime2));
                                                    $time2 = date('h:i A', strtotime($datetime2));
                                                    if ($date1 != $date2) {
                                                        echo '<B>Date ' . $i . '</B>       From ' . $date1 . ' ' . $time1 . ' to ' . $date2 . ' ' . $time2;
                                                    } else {
                                                        echo '<B>Date ' . $i . '</B>       From ' . $date1 . ' - ' . $time1 . ' to  ' . $time2;
                                                    }
                                                }
                                            }
                                            ?>
                                        </span>                                         </span>
                                    </td>

                                    <?php
                                    if (count($lecture_dates) > 1) {
                                        $subscription_count_date = common\models\SubscriptionDetails::find()->where(['lecture_id' => $model->vma_lecture_id, 'is_deleted' => 0])->Andwhere(['specific_choosen' => $dates['id']])->groupBy('subscription_id')->count();
                                        ?>
                                        <td style="width:15%;"></td>
                                        <td style="width:10%;padding-left: 18px;"><?= $subscription_count_date ?></td>
                                        <td style="width:10%;">50%</td>
                                    <?php } ?>

                                </tr>

                                <?php
                                $i++;
                            }
                            ?>


                        </tbody>
                    </table>
                <?php } ?>
            </td>
        </tr>
        <!-- 23/1/2018 -->
        </tr>


        <?php
        break;
    case 'exam_assessment':
        ?>
        <tr>
            <td style="width:55%;">
                <span><i class="fa fa-user" aria-hidden="true"></i></span>
                <span class="width-100 margin-r-150">
                    <?php if ($contentId != null) { ?>
                        <p><?= $model['name'] ?></p>
                    <?php } else { ?>
                        <h4><?= $model['name'] ?></h4>
                    <?php } ?>
                </span>
                <span class="">
                    <!--                float-right-->
                    <?php
                    if ($model['avilability_setting'] == 2) {
                        if ($model['end_date'] != null && $model['start_date'] == null) {
                            ?>
                            <?php
                            $date2 = date('Y-m-d', strtotime($model['end_date']));
                            $time2 = date('h:i A', strtotime($model['end_date']));
                            echo '<B>Due Date: </B>' . $date2 . ' <B>:</B> ' . $time2;
                        } elseif ($model['end_date'] == null && $model['start_date'] != null) {
                            $date1 = date('Y-m-d', strtotime($model['start_date']));
                            $time1 = date('h:i A', strtotime($model['start_date']));
                            echo '<B>From: </B>' . $date1 . ' <B>:</B> ' . $time1;
                        } elseif ($model['end_date'] != null && $model['start_date'] != null) {
                            $date1 = date('Y-m-d', strtotime($model['start_date']));
                            $time1 = date('h:i A', strtotime($model['start_date']));
                            $date2 = date('Y-m-d', strtotime($model['end_date']));
                            $time2 = date('h:i A', strtotime($model['end_date']));
                            ?>
                            <?php
                            if ($date1 == $date2) {
                                echo $date1 . ' <B>:</B> ' . $time1 . ' <B>-</B> ' . $time2;
                            } else {
                                echo '<B>From: </B>' . $date1 . ' ' . $time1 . ' &ensp; <B>Due Date: </B>' . $date2 . ' ' . $time2;
                            }
                            ?>
                            <?php
                        }
                    } elseif ($model['avilability_setting'] == '3') {


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
                <span class="float-right">
                    <?php if (!$model['publish']) { ?>
                        <span class="red-border">Draft
                        </span>
                    <?php } ?>
                </span>
            </td>
            <td style="width:15%;">Exam</td>
            <td style="width:10%;">25</td>
            <td style="width:10%;">50%</td>


            <td style="width:10%;" class="text-right">
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <button class="dropdown-toggle btn btn-default btn_padding" data-toggle="dropdown" role="button" aria-haspopup="true"
                                aria-expanded="false">Action  <span class="caret"></span></button>
                        <ul class="dropdown-menu course-content-dropmenu">
                            <li> <?= Html::a(Yii::t('app', 'View Details'), ['assessment/view', 'id' => $contentId]) ?></li>
                            <li> <?= Html::a(Yii::t('app', 'Edit'), ['assessment/update', 'id' => $contentId]) ?></li>
                            <?php if ($model['publish'] == 0) { ?>
                                <li> <?= Html::a(Yii::t('app', 'Activate'), ['deactivateassessment', 'id' => $contentId]) ?></li>
                            <?php } elseif ($model['publish'] == 1) { ?>
                                <li> <?= Html::a(Yii::t('app', 'Deactivate'), ['deactivateassessment', 'id' => $contentId]) ?></li>
                            <?php } ?>


                            <li> <?= Html::a(Yii::t('app', 'Participant Report'), ['participant-assessment/assessment', 'id' => $contentId]) ?></li>

                            <li><a href="">Content Report</a></li>
                            <li> <?php
                                echo Html::a(Yii::t('app', 'Delete'), ['deleteassessment', 'id' => $contentId], [
                                    'class' => '',
                                    'data' => [
                                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                        'method' => 'post',
                                    ],
                                ])
                                ?></li>

                        </ul>
                    </li>
                </ul>
            </td>
        </tr>

        <?php
        break;

    case 'assignments_assessments':
        ?>
        <tr>
            <td style="width:55%;">
                <span><i class="fa fa-user" aria-hidden="true"></i></span>
                <span class="width-100 margin-r-150">
                    <?php if ($contentId != null) { ?>
                        <p><?= $model['name'] ?></p>
                    <?php } else { ?>
                        <h4><?= $model['name'] ?></h4>
                    <?php } ?>
                </span>
                <span class="">
                    <!--                float-right-->

                    <?php
                    if ($model['avilability_setting'] == 2) {
                        if ($model['end_date'] != null && $model['start_date'] == null) {
                            ?>
                            <?php
                            $date2 = date('Y-m-d', strtotime($model['end_date']));
                            $time2 = date('h:i A', strtotime($model['end_date']));
                            echo '<B>Due Date: </B>' . $date2 . ' <B>:</B> ' . $time2;
                        } elseif ($model['end_date'] == null && $model['start_date'] != null) {
                            $date1 = date('Y-m-d', strtotime($model['start_date']));
                            $time1 = date('h:i A', strtotime($model['start_date']));
                            echo '<B>From: </B>' . $date1 . ' <B>:</B> ' . $time1;
                        } elseif ($model['end_date'] != null && $model['start_date'] != null) {
                            $date1 = date('Y-m-d', strtotime($model['start_date']));
                            $time1 = date('h:i A', strtotime($model['start_date']));
                            $date2 = date('Y-m-d', strtotime($model['end_date']));
                            $time2 = date('h:i A', strtotime($model['end_date']));
                            ?>
                            <?php
                            if ($date1 == $date2) {
                                echo $date1 . ' <B>:</B> ' . $time1 . ' <B>-</B> ' . $time2;
                            } else {
                                echo '<B>From: </B>' . $date1 . ' ' . $time1 . '&ensp; <B>Due Date: </B>' . $date2 . ' ' . $time2;
                            }
                            ?>
                            <?php
                        }
                    } elseif ($model['avilability_setting'] == '3') {


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
                <span class="float-right">
                    <?php if (!$model['publish']) { ?>
                        <span class="red-border">Draft
                        </span>
                    <?php } ?>
                </span>
            </td>
            <td style="width:15%;">Assigment</td>
            <td style="width:10%;">25</td>
            <td style="width:10%;">50%</td>


            <td style="width:10%;" class="text-right">
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <button class="dropdown-toggle btn btn-default btn_padding" data-toggle="dropdown" role="button" aria-haspopup="true"
                                aria-expanded="false">Action <span class="caret"></span></button>
                        <ul class="dropdown-menu course-content-dropmenu">
                            <li> <?= Html::a(Yii::t('app', 'View Details'), ['assessment/view', 'id' => $contentId]) ?></li>
                            <li> <?= Html::a(Yii::t('app', 'Edit'), ['assessment/update', 'id' => $contentId]) ?></li>
                            <?php if ($model['publish'] == 0) { ?>
                                <li> <?= Html::a(Yii::t('app', 'Activate'), ['deactivateassessment', 'id' => $contentId]) ?></li>
                            <?php } elseif ($model['publish'] == 1) { ?>
                                <li> <?= Html::a(Yii::t('app', 'Deactivate'), ['deactivateassessment', 'id' => $contentId]) ?></li>
                            <?php } ?>
                            <li> <?= Html::a(Yii::t('app', 'Participant Report'), ['participant-assessment/assessment', 'id' => $contentId]) ?></li>

                            <li><a href="">Content Report2</a></li>
                            <li> <?php
                                echo Html::a(Yii::t('app', 'Delete'), ['deletelecture', 'id' => $contentId], [
                                    'class' => '',
                                    'data' => [
                                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                        'method' => 'post',
                                    ],
                                ])
                                ?>
                            </li>
                        </ul>
                    </li>
                </ul>
            </td>
        </tr>


        <?php
        break;
    case 'quiz_assessments':
        ?>
        <tr>
            <td style="width:55%;">
                <span><i class="fa fa-user" aria-hidden="true"></i></span>
                <span class="width-100 margin-r-150">
                    <?php if ($contentId != null) { ?>
                        <p><?= $model['name'] ?></p>
                    <?php } else { ?>
                        <h4><?= $model['name'] ?></h4>
                    <?php } ?>
                </span>
                <span class="">
                    <!--                float-right-->
                    <?php
                    if ($model['avilability_setting'] == 2) {
                        if ($model['end_date'] != null && $model['start_date'] == null) {
                            ?>
                            <?php
                            $date2 = date('Y-m-d', strtotime($model['end_date']));
                            $time2 = date('h:i A', strtotime($model['end_date']));
                            echo '<B>Due Date: </B>' . $date2 . ' <B>:</B> ' . $time2;
                        } elseif ($model['end_date'] == null && $model['start_date'] != null) {
                            $date1 = date('Y-m-d', strtotime($model['start_date']));
                            $time1 = date('h:i A', strtotime($model['start_date']));
                            echo '<B>From: </B>' . $date1 . ' <B>:</B> ' . $time1;
                        } elseif ($model['end_date'] != null && $model['start_date'] != null) {
                            $date1 = date('Y-m-d', strtotime($model['start_date']));
                            $time1 = date('h:i A', strtotime($model['start_date']));
                            $date2 = date('Y-m-d', strtotime($model['end_date']));
                            $time2 = date('h:i A', strtotime($model['end_date']));
                            ?>
                            <?php
                            if ($date1 == $date2) {
                                echo $date1 . ' <B>:</B> ' . $time1 . ' <B>-</B> ' . $time2;
                            } else {
                                echo '<B>From: </B>' . $date1 . ' ' . $time1 . '  &ensp; <B>Due Date: </B> ' . $date2 . ' ' . $time2;
                            }
                            ?>
                            <?php
                        }
                    } elseif ($model['avilability_setting'] == '3') {


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
                <span class="float-right">
                    <?php if (!$model['publish']) { ?>
                        <span class="red-border">Draft
                        </span>
                    <?php } ?>
                </span>
            </td>

            <td style="width:15%;">Quiz</td>
            <td style="width:10%;">25</td>
            <td style="width:10%;">50%</td>
            <td style="width:10%;" class="text-right">
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <button class="dropdown-toggle btn btn-default btn_padding" data-toggle="dropdown" role="button" aria-haspopup="true"
                                aria-expanded="false">Action <span class="caret"></span></button>
                        <ul class="dropdown-menu course-content-dropmenu">
                            <li> <?= Html::a(Yii::t('app', 'View Details'), ['assessment/view', 'id' => $contentId]) ?></li>
                            <li> <?= Html::a(Yii::t('app', 'Edit'), ['assessment/update', 'id' => $contentId]) ?></li>
                            <?php if ($model['publish'] == 0) { ?>
                                <li> <?= Html::a(Yii::t('app', 'Activate'), ['deactivateassessment', 'id' => $contentId]) ?></li>
                            <?php } elseif ($model['publish'] == 1) { ?>
                                <li> <?= Html::a(Yii::t('app', 'Deactivate'), ['deactivateassessment', 'id' => $contentId]) ?></li>
                            <?php } ?>
                            <li> <?= Html::a(Yii::t('app', 'Participant Report'), ['participant-assessment/assessment', 'id' => $contentId]) ?></li>

                            <li><a href="">Content Report</a></li>
                            <li> <?php
                                echo Html::a(Yii::t('app', 'Delete'), ['deletelecture', 'id' => $contentId], [
                                    'class' => '',
                                    'data' => [
                                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                        'method' => 'post',
                                    ],
                                ])
                                ?></li>

                        </ul>
                    </li>
                </ul>
            </td>
        </tr>


        <?php
        break;
}
?>
    
