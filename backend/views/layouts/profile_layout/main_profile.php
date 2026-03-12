<?php
use yii\helpers\Html;
use yii\bootstrap5\Modal;
   backend\assets\AppAsset::register($this);


if (class_exists('backend\assets\AppAsset')) {
    backend\assets\AppAsset::register($this);
} else {
    app\assets\AppAsset::register($this);
}
$directoryAsset = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="en" dir="rtl" class="light-theme">
<head>
    <!-- Required meta tags -->
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?= Html::csrfMetaTags() ?>


    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body>

<?php $this->beginBody() ?>
<!--start wrapper-->
<div class="wrapper">
    <!--start sidebar wrapper-->
    <?= $this->render(
        '../navigation.php',
    )
    ?>
   <!--end sidebar wrapper-->

    <!--start top header-->
    <?= $this->render(
        '../header.php',
    ) ?>

    <!--end top header-->


    <?= $this->render(
        'content_profile.php',
        ['content' => $content],
    ) ?>

    <?= $this->render(
        '../footer.php',
    ) ?>

</div>
<!--end wrapper-->




<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>