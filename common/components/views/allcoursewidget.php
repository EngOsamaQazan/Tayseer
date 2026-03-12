<?php
use yii\helpers\Url;

?>
<?php
if ($type=="course"){
//print_r($posts);die();
	foreach($posts AS $model){
		?>

	<div class="col-sm-3 partener_logo ">
		<div class="slider_warp">
			<div class="slider_img">
				<a href=""><img src="<?=yii::$app->homeUrl?>photoes/n1.jpg"/> </a>
				<div class="share">
					
					<div class="share_icon">
						<span><a href=""><i class="fa fa-heart" aria-hidden="true"></i></a></span>
						<span><a href=""><i class="fa fa-share-alt" aria-hidden="true"></i></a></span>
					</div>
				</div>
				<div class="category blue"><!--color class blue green terquz-->
				<?=$model->contentType->name?>
				</div>
				<div class="hover_overlay">
					<a href="<?=Url::to(["course/{$model->slug}"])?>">View Details</a>
					<a href="<?=Url::to(["course/{$model->slug}/checkout"])?>">Enrol Now</a>
				</div>
			</div>
			<div class="slider_text">
				<a href="<?=Url::to(["course/{$model->slug}"])?>"> <h3><?=$model->name?></h3> </a>
				<p><i class="fa fa-calendar" aria-hidden="true"></i> <?=$model->_date?></p>
				<p><i class="fa fa-map-marker" aria-hidden="true"></i></p>
				<p><?=$model->overview?> </p>
				<div class="slider_bottom">
					<span><i class="fa fa-users" aria-hidden="true"></i> <?=$model->views?></span>
					<?php if ($model->_cme_hr!=''){ ?> <span><i class="fa fa-clock-o" aria-hidden="true"></i> <?=$model->_cme_hr?></span><?php } ?>
					<span><i class="sr" aria-hidden="true">SR</i> <?=$model->_price_text?></span>
					<span><i class="fa fa-star-half-o" aria-hidden="true"></i> 3.5</span>
				</div>
			</div>
		</div>
	</div>
	


<?php }

}else{ 
 for($i=1;$i<=4;$i++){?>

	<div class="col-sm-3 partener_logo ">
		<div class="slider_warp">
			<div class="slider_img">
				<a href=""><img src="<?=yii::$app->homeUrl?>photoes/n1.jpg"/> </a>
				<div class="share">
					
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
	


<?php } 
}
?>	


