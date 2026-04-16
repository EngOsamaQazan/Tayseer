<?php
/**
 * AdminLTE 3 Layout — جاهز للتبديل (غير مفعّل)
 * ==============================================
 * هذا الملف جاهز لاستبدال main.php عند الانتقال إلى AdminLTE 3 + Bootstrap 4.
 *
 * للتفعيل:
 * 1. composer require potime/yii2-adminlte3
 * 2. composer require yiisoft/yii2-bootstrap4
 * 3. في backend/config/main.php: 'layout' => 'main-v3'
 * 4. تحديث Kartik: 'gridview' => ['class' => '\kartik\grid\Module', 'bsVersion' => '4']
 */

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

if (Yii::$app->controller->action->id === 'login') {
    echo $this->render('main-login', ['content' => $content]);
} else {

    if (class_exists('backend\assets\AppAsset')) {
        backend\assets\AppAsset::register($this);
    }

    // TODO: Replace with AdminLTE 3 asset when activated
    // \potime\adminlte3\AdminLteAsset::register($this);
    dmstr\web\AdminLteAsset::register($this);

    $directoryAsset = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/v4-shims.min.css" crossorigin="anonymous" />

        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- AOS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />

        <!-- Tippy.js -->
        <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
        <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/shift-away.css" />

        <?php $assetVersion = Yii::$app->params['assetVersion'] ?? '2026.04.16'; ?>
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/jadal-theme.css?v=<?= $assetVersion ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/tayseer-modern-libs.css?v=<?= $assetVersion ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/adminlte3-shim.css?v=<?= $assetVersion ?>">
        <link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/fin-transactions.css?v=<?= $assetVersion ?>">
    </head>
    <!--
        AdminLTE 3 body classes:
        - sidebar-mini: collapsible sidebar
        - layout-fixed: fixed sidebar
        - layout-navbar-fixed: fixed top navbar
        Note: "skin-blue" becomes "navbar-dark navbar-primary" in ALT3
    -->
    <body class="hold-transition sidebar-mini layout-fixed" dir="rtl">
    <?php $this->beginBody() ?>

    <div class="wrapper">

        <!-- === Navbar (AdminLTE 3 style) === -->
        <nav class="main-header navbar navbar-expand navbar-dark" style="background:var(--clr-primary, #800020)">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav mr-auto">
                <!-- notifications and user menu go here (same as header.php adapted) -->
            </ul>
        </nav>

        <!-- === Sidebar (AdminLTE 3 style) === -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background:var(--clr-primary-900, #4a0012)">
            <a href="<?= Yii::$app->homeUrl ?>" class="brand-link" style="border-bottom-color:rgba(255,255,255,.1)">
                <span class="brand-text font-weight-bold" style="font-family:var(--font-family)">تيسير</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <!-- Menu items adapted for AdminLTE 3 nav-item/nav-link structure -->
                    <!-- TODO: Migrate _menu_items.php for ALT3 classes -->
                </nav>
            </div>
        </aside>

        <!-- === Content Wrapper (AdminLTE 3) === -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0" style="font-family:var(--font-family);font-weight:700">
                                <?php
                                if (isset($this->blocks['content-header'])) {
                                    echo $this->blocks['content-header'];
                                } elseif ($this->title !== null) {
                                    echo Html::encode($this->title);
                                }
                                ?>
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                                'options' => ['class' => 'breadcrumb float-sm-left'],
                                'itemTemplate' => "<li class='breadcrumb-item'>{link}</li>\n",
                                'activeItemTemplate' => "<li class='breadcrumb-item active'>{link}</li>\n",
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?= $content ?>
                </div>
            </section>
        </div>

        <!-- === Footer (AdminLTE 3) === -->
        <footer class="main-footer text-center">
            <span style="color:#999;font-size:11px">نظام تيسير لإدارة شركات التقسيط</span>
        </footer>

    </div>

    <!-- Modern Libraries -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/js/tayseer-modern.js?v=<?= $assetVersion ?>"></script>

    <?php $this->endBody() ?>
    </body>
    </html>
    <?php $this->endPage() ?>
<?php } ?>
