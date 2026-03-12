<?php
use yii\helpers\Url;
use yii\helpers\StringHelper;
?>
<?php
/** @var $model \common\models\Course */
if ($type=="course"){
//print_r($posts);die();
	$i = 0;
	foreach($posts AS $model){
$i++;
		?>
<div class="slider slick-slide slick-current <?php if($i==1){?>slick-active<?php } ?>" data-slick-index="0" aria-hidden="false" tabindex="-1" role="option" aria-describedby="slick-slide10">
	<div class="col-sm-12 partener_logo ">
		<div class="slider_warp">
			<div class="slider_img">
				<a href=""><img src="<?=yii::$app->homeUrl?>photoes/n1.jpg"/> </a>
				<div class="share">
					<div class="best_sale">Best Sale</div>
					<div class="share_icon">
						<span><a href=""><i class="fa fa-heart" aria-hidden="true"></i></a></span>
						<span><a href=""><i class="fa fa-share-alt" aria-hidden="true"></i></a></span>
					</div>
				</div>
				<div class="category blue"><!--color class blue green terquz-->
				<?= (!empty($model->courseActivityType))? $model->courseActivityType->vmaActivityType->name_en : ''?>
				</div>
				<div class="hover_overlay">
<!--                    <a href="--><?php //=Url::to(["/vma-course/{$model->slug}"])?><!--">View Details</a>-->

                    <a href="<?= Url::to(['/vma-course/'.$model->slug])?>">View Details</a>
					<a href="<?=Url::to(["course/{$model->slug}/checkout"])?>">Enrol Now</a>
				</div>
			</div>
			<div class="slider_text">
				<a href="<?=Url::to(["vma-course/view/{$model->slug}"])?>"> <h3><?=$model->course_name?></h3> </a>
				<p><i class="fa fa-calendar" aria-hidden="true"></i> <?=$model->course_start?></p>
				<p><i class="fa fa-map-marker" aria-hidden="true"></i></p>
				<p><?= StringHelper::truncateWords(strip_tags($model->over_view),15)   ?> </p>
				<div class="slider_bottom">
					<span><i class="fa fa-users" aria-hidden="true"></i> 3000</span>
					<?php // if ($model->_cme_hr!=''){ ?> <span><i class="fa fa-clock-o" aria-hidden="true"></i> <?php //=$model->_cme_hr?></span><?php // } ?>
					<span><i class="sr" aria-hidden="true">SR</i> <?=$model->_price_text?></span>
					<span><i class="fa fa-star-half-o" aria-hidden="true"></i> 3.5</span>
				</div>
			</div>
		</div>
	</div>
	
</div>

<?php  }
}else{
 ?>

<?php $i=1; for($i=1;$i<=10;$i++){?>
<div class="slider slick-slide slick-current <?php if($i==1){?>slick-active<?php } ?>" data-slick-index="0" aria-hidden="false" tabindex="-1" role="option" aria-describedby="slick-slide10">
	<div class="col-sm-12 partener_logo ">
		<div class="slider_warp">
			<div class="slider_img">
				<a href=""><img src="<?=yii::$app->homeUrl?>photoes/n1.jpg"/> </a>
				<div class="share">
					<div class="best_sale">Best Sale</div>
					<div class="share_icon">
						<span><a href=""><i class="fa fa-heart" aria-hidden="true"></i></a></span>
						<span><a href=""><i class="fa fa-share-alt" aria-hidden="true"></i></a></span>
					</div>
				</div>
				<div class="category blue"><!--color class blue green terquz-->
				Courses
				</div>
				<div class="hover_overlay">
					<a href="">View Details</a>
					<a href="">Enrol Now</a>
				</div>
			</div>
			<div class="slider_text">
				<a href=""> <h3>Title Name</h3> </a>
				<p><i class="fa fa-calendar" aria-hidden="true"></i> 30-5-2017</p>
				<p><i class="fa fa-map-marker" aria-hidden="true"></i></p>
				<p>Text Text Text Text Text Text Text Text Text Text Text Text Text Text <?=$i?> </p>
				<div class="slider_bottom">
					<span><i class="fa fa-users" aria-hidden="true"></i> 3000</span>
					<span><i class="fa fa-clock-o" aria-hidden="true"></i> 30 Hr</span>
					<span><i class="sr" aria-hidden="true">SR</i> 150 SR</span>
					<span><i class="fa fa-star-half-o" aria-hidden="true"></i> 3.5</span>
				</div>
			</div>
		</div>
	</div>
	
</div>

<?php  $i++;} 
}
?>	


