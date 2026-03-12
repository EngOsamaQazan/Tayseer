<?php  for($i=0;$i<=10;$i++){?>
<div class="slider slick-slide slick-current <?php if($i==0){?>slick-active<?php } ?>" data-slick-index="0" aria-hidden="false" tabindex="-1" role="option" aria-describedby="slick-slide10">
	<div class="col-sm-12 partener_logo ">
		<div class="slider_warp">
			<div class="slider_img">
				<a href=""><img src="<?=yii::$app->homeUrl?>photoes/n1.jpg"/> </a>
				<div class="share">
					
					<div class="share_icon">
						<span><a href=""><i class="fa fa-heart" aria-hidden="true"></i></a></span>
						<span><a href=""><i class="fa fa-share-alt" aria-hidden="true"></i></a></span>
					</div>
				</div>
				<div class=" writen"><!--color class blue green terquz-->
					<div class="terquz width_80">
					By written Name
					</div>
					<div class="black width_20">
					25 May
					</div>
				</div>
				
			</div>
			<div class="slider_text">
				<a href=""> <h3>Title Name</h3> </a>
				
				<p>Text Text Text Text Text Text Text Text Text Text Text Text Text Text  </p>
				<div class="slider_bottom">
					<span class="btn_writen blue">Writen</span> <!--color class blue green terquz-->
					<span class="btn_writen terquz"> Broadcast</span> <!--color class blue green terquz-->
					
					<span class="pull-right"><i class="fa fa-star-half-o" aria-hidden="true"></i> 3.5</span>
				</div>
			</div>
		</div>
	</div>
	
</div>
<?php  $i++;} ?>	


