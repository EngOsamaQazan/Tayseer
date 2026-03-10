<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

if (Yii::$app->controller->action->id === 'login') {
    echo $this->render('main-login', ['content' => $content]);
} else {

    if (class_exists('backend\assets\AppAsset')) {
        backend\assets\AppAsset::register($this);
    } else {
        app\assets\AppAsset::register($this);
    }

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
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">

        <!-- Font Awesome 6 + v4 Shims -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/v4-shims.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/bs4-to-bs5-shim.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/jadal-theme.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/adminlte3-shim.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/tayseer-modern-libs.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/fin-transactions.css?v=<?= time() ?>">

        <!-- Vite Bundle (Tabler + Tailwind + Alpine, SweetAlert2, ApexCharts, Tippy, HTMX, AOS, SortableJS) -->
        <?= \backend\helpers\ViteHelper::tags() ?>
    </head>
    <body dir="rtl">
    <?php $this->beginBody() ?>

    <?php
    yii\bootstrap5\Modal::begin([
        'id' => 'gModal',
        'title' => '<h3 id="modalTitle"></h3>',
    ]);
    yii\bootstrap5\Modal::end();
    ?>

    <div class="page" id="tayseerPage">
        <script>
        localStorage.removeItem('tayseer-sidebar-mini');
        if(localStorage.getItem('tayseer-sidebar-hidden')==='1')document.getElementById('tayseerPage').classList.add('sidebar-hidden');
        </script>

        <!-- Floating button to re-show sidebar (only visible when sidebar is hidden) -->
        <button type="button" id="sidebarShowBtn" title="إظهار القائمة">
            <i class="fa-solid fa-bars"></i>
        </button>

        <?= $this->render('left.php') ?>

        <div class="page-wrapper">
            <?= $this->render('header.php') ?>
            <?= $this->render('content.php', ['content' => $content]) ?>
        </div>
    </div>

    <?php $this->endBody() ?>
    </body>
    </html>
    <?php $this->endPage() ?>
<?php } ?>
