<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;
    
    $homeActivate = $contentActivate = $userActivate = false;    

    $content = ['session','lecture','workshop','ticket','exam','assignment','question'];
    $slug = Yii::$app->request->get('slug');
    ?>
    <?php 
    $arrayClass = ['1'=>'col-sm-7','2'=>'col-sm-11'];
    switch ($type) {
            case 'lecture':?>
                <a href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><p> <img src="<?=yii::$app->homeUrl?>images/tab3.png" class="side_cours_img"><?=$model->name?><span class="pull-right"> </span></p></a>

    <?php   break;
            case 'assignment': ?>
                 <a href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><p> <img src="<?=yii::$app->homeUrl?>images/tab4.png" class="side_cours_img"><?=$model->name?><span class="pull-right"> </span></p></a>
    <?php   break;
            case 'exam': ?>
                <a href="<?=Url::to(["course/{$slug}/learn/{$contentId}"])?>"><p> <img src="<?=yii::$app->homeUrl?>images/titlecourse4.png" class="side_cours_img"><?=$model->name?><span class="pull-right"> <?=$model->duration?>:00</span></p></a>
    <?php break;
        }
    ?>    
    
    