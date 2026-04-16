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
    <?php $_og = \common\helper\CompanyOg::get(); $_ogTitle = $_og['title']; $_ogDesc = $_og['desc']; $_ogImg = $_og['image']; $_ogHost = Yii::$app->request->hostInfo; ?>
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= Html::encode($_ogHost) ?>">
    <meta property="og:title" content="<?= Html::encode($_ogTitle) ?>">
    <meta property="og:description" content="<?= Html::encode($_ogDesc) ?>">
    <meta property="og:image" content="<?= Html::encode($_ogImg) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Html::encode($_ogTitle) ?>">
    <meta name="twitter:description" content="<?= Html::encode($_ogDesc) ?>">
    <meta name="twitter:image" content="<?= Html::encode($_ogImg) ?>">
    <?php $this->head() ?>
    <?php $assetVersion = Yii::$app->params['assetVersion'] ?? '2026.04.16'; ?>
    <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/jadal-login.css?v=<?= $assetVersion ?>">
</head>
<body class="login-page" dir="rtl">

<?php $this->beginBody() ?>

    <?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
