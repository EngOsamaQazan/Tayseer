<?php

use yii\helpers\StringHelper;
use yii\helpers\Url;

$i = 0;
foreach ($posts AS $model) {
    $i++;
    ?>
    <div class="slider slick-slide slick-current <?php if ($i == 1) { ?>slick-active<?php } ?>" data-slick-index="0"
         aria-hidden="false" tabindex="-1" role="option" aria-describedby="slick-slide10">
        <div class="col-sm-12 partener_logo ">
            <div class="slider_warp">
                <div class="slider_img">
                    <a href=""><img src="<?= yii::$app->homeUrl ?>photoes/n1.jpg"/> </a>
                    <div class="share">
                        <div class="best_sale">Best Sale</div>
                        <div class="share_icon">
                            <span><a href=""><i class="fa fa-heart" aria-hidden="true"></i></a></span>
                            <span><a href=""><i class="fa fa-share-alt" aria-hidden="true"></i></a></span>
                        </div>
                    </div>
                    <div class="category blue"><!--color class blue green terquz-->
                        <?= $model->name ?>
                    </div>
                    <div class="hover_overlay">
                        <a href="<?= Url::to(["class/{$model->slug}"]) ?>">View Details</a>
                        <a href="<?= Url::to(["class/{$model->slug}/checkout"]) ?>">Enrol Now</a>
                    </div>
                </div>
                <div class="slider_text">
                    <a href="<?= Url::to(["class/{$model->slug}"]) ?>"><h3><?= $model->name ?></h3></a>
                    <p><i class="fa fa-calendar" aria-hidden="true"></i>15-1-2019</p>
                    <p><i class="fa fa-map-marker" aria-hidden="true"></i></p>
                    <p><?= StringHelper::truncateWords(strip_tags($model->overview), 15) ?> </p>
                    <div class="slider_bottom">
                        <span><i class="fa fa-users" aria-hidden="true"></i> 3000</span>
                        <span><i class="fa fa-clock-o" aria-hidden="true"></i>
                            <span><i class="sr" aria-hidden="true">SR</i> <?= $model->price ?></span>
                        <span><i class="fa fa-star-half-o" aria-hidden="true"></i> 3.5</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php }

?>