<?php
use backend\assets\AppAsset;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <link rel="shortcut icon" href="<?= Yii::$app->request->baseUrl ?>/images/favicon.png" type="image/png">
    <link rel="icon" href="<?= Yii::$app->request->baseUrl ?>/images/favicon.png" type="image/png" sizes="192x192">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/jadal-login.css?v=<?= time() ?>">
</head>
<body class="login-page" dir="rtl">

<?php $this->beginBody() ?>

    <?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
