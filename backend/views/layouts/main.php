<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\CompanyChecked;
use backend\modules\notification\models\Notification;
use common\models\UserPreference;

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

    $baseUrl = Yii::$app->request->baseUrl;

    // ── User data for navbar ──
    $_avatarRecord = \backend\modules\employee\models\EmployeeFiles::find()
        ->where(['user_id' => Yii::$app->user->id])
        ->andWhere(['type' => 0])
        ->orderBy(['id' => SORT_DESC])
        ->one();
    $avatar = $_avatarRecord;
    if ($avatar && $avatar->path && !file_exists(Yii::getAlias('@webroot') . '/' . ltrim($avatar->path, '/'))) {
        $avatar = null;
    }
    $defaultAvatar = $baseUrl . '/img/default-avatar.png';
    $avatarSrc = !empty($avatar->path) ? Url::to([$avatar->path]) : $defaultAvatar;
    $onError = "this.onerror=null;this.src='" . $defaultAvatar . "';";

    $userId = Yii::$app->user->id;
    $userName = Yii::$app->user->identity ? Yii::$app->user->identity['username'] : 'مستخدم';

    $unreadCount = (int) Notification::find()
        ->where(['recipient_id' => $userId, 'is_unread' => 1])
        ->count();
    $latestNotifs = Notification::find()
        ->where(['recipient_id' => $userId])
        ->orderBy(['id' => SORT_DESC])
        ->limit(10)
        ->all();
    $markReadUrl = Url::to(['/notification/notification/is-read']);

    $themePrefs = UserPreference::getTheme();
    $themeMode  = $themePrefs['mode'];
    $themeColor = $themePrefs['color'];

    ?>
    <?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="ar"
          class="layout-navbar-fixed layout-menu-fixed layout-compact"
          dir="rtl"
          data-bs-theme="<?= Html::encode($themeMode) ?>"
          data-theme-color="<?= Html::encode($themeColor) ?>"
          data-assets-path="<?= $baseUrl ?>/vuexy/"
          data-template="vertical-menu-template">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <script>
        (function(){
          var m=localStorage.getItem('tayseer_theme_mode');
          var c=localStorage.getItem('tayseer_theme_color');
          if(m) document.documentElement.setAttribute('data-bs-theme',m);
          if(c) document.documentElement.setAttribute('data-theme-color',c);
        })();
        </script>
        <link rel="shortcut icon" href="<?= $baseUrl ?>/images/favicon.png" type="image/png">
        <link rel="icon" href="<?= $baseUrl ?>/images/favicon.png" type="image/png" sizes="192x192">
        <title><?= Html::encode($this->title) ?></title>
        <?php
            $_ogHost = Yii::$app->request->hostInfo;
            $_ogServer = Yii::$app->request->serverName ?? '';
            if (strpos($_ogServer, 'jadal') !== false) {
                $_ogTitle = 'نظام تيسير — جدل';
                $_ogDesc  = 'نظام إدارة التقسيط والأعمال المتكامل — شركة جدل للتقسيط';
                $_ogImg   = $_ogHost . $baseUrl . '/img/og-jadal.png';
            } elseif (strpos($_ogServer, 'namaa') !== false) {
                $_ogTitle = 'نظام تيسير — نماء';
                $_ogDesc  = 'نظام إدارة التقسيط والأعمال المتكامل — شركة نماء للتقسيط';
                $_ogImg   = $_ogHost . $baseUrl . '/img/og-namaa.png';
            } else {
                $_ogTitle = 'نظام تيسير';
                $_ogDesc  = 'نظام إدارة التقسيط والأعمال المتكامل';
                $_ogImg   = $_ogHost . $baseUrl . '/img/og-jadal.png';
            }
        ?>
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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">

        <!-- Font Awesome 6 -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/v4-shims.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Vuexy Core -->
        <link rel="stylesheet" href="<?= $baseUrl ?>/vuexy/vendor/libs/node-waves/node-waves.css">
        <link rel="stylesheet" href="<?= $baseUrl ?>/vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.css">
        <link rel="stylesheet" href="<?= $baseUrl ?>/vuexy/vendor/css/core.css">
        <link rel="stylesheet" href="<?= $baseUrl ?>/vuexy/css/demo.css">

        <!-- Tayseer Feature CSS -->
        <link rel="stylesheet" href="<?= $baseUrl ?>/css/tayseer-modern-libs.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= $baseUrl ?>/css/fin-transactions.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= $baseUrl ?>/css/tayseer-vuexy.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= $baseUrl ?>/css/tayseer-responsive.css?v=<?= time() ?>">
        <link rel="stylesheet" href="<?= $baseUrl ?>/css/tayseer-themes.css?v=<?= time() ?>">

        <!-- Vuexy Helpers (must load in head before body renders) -->
        <script src="<?= $baseUrl ?>/vuexy/vendor/js/helpers.js"></script>
        <script src="<?= $baseUrl ?>/vuexy/js/config.js"></script>

        <!-- Vite Bundle (Alpine, SweetAlert2, ApexCharts, Tippy, HTMX, AOS, SortableJS) -->
        <?= \backend\helpers\ViteHelper::tags() ?>
    </head>
    <body>
    <?php $this->beginBody() ?>

    <?php
    yii\bootstrap5\Modal::begin([
        'id' => 'gModal',
        'title' => '<h3 id="modalTitle"></h3>',
    ]);
    yii\bootstrap5\Modal::end();
    ?>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <?= $this->render('left.php') ?>

            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="fa-solid fa-bars fa-lg"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                        <ul class="navbar-nav flex-row align-items-center ms-md-auto">

                            <!-- Fullscreen Toggle -->
                            <li class="nav-item me-2">
                                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon" href="javascript:void(0)" id="btnFullscreen" title="وضع ملء الشاشة" onclick="toggleFullScreen()">
                                    <i class="fa-solid fa-expand fa-lg" id="fullscreenIcon"></i>
                                </a>
                            </li>

                            <!-- Theme Toggle -->
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon" href="javascript:void(0)" id="themeToggleBtn" title="تبديل المظهر">
                                    <i class="fa-solid fa-sun fa-lg" id="themeToggleIcon"></i>
                                </a>
                            </li>

                            <!-- Theme Palette -->
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="ألوان النظام">
                                    <i class="fa-solid fa-palette fa-lg"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end theme-switcher-menu p-3" style="min-width:240px">
                                    <div class="theme-section-title mb-2">وضع العرض</div>
                                    <div class="d-flex gap-2 mb-3">
                                        <div class="theme-mode-toggle flex-fill text-center" id="themeMode-light">
                                            <i class="fa-solid fa-sun"></i>
                                            <span style="font-size:13px">فاتح</span>
                                        </div>
                                        <div class="theme-mode-toggle flex-fill text-center" id="themeMode-dark">
                                            <i class="fa-solid fa-moon"></i>
                                            <span style="font-size:13px">داكن</span>
                                        </div>
                                    </div>
                                    <div class="theme-section-title mb-2">لون النظام</div>
                                    <div class="theme-palette-grid">
                                        <div class="theme-palette-swatch swatch-burgundy" data-color="burgundy" title="عنابي"><i class="fa-solid fa-check swatch-check"></i></div>
                                        <div class="theme-palette-swatch swatch-ocean" data-color="ocean" title="أزرق"><i class="fa-solid fa-check swatch-check"></i></div>
                                        <div class="theme-palette-swatch swatch-forest" data-color="forest" title="أخضر"><i class="fa-solid fa-check swatch-check"></i></div>
                                        <div class="theme-palette-swatch swatch-royal" data-color="royal" title="بنفسجي"><i class="fa-solid fa-check swatch-check"></i></div>
                                        <div class="theme-palette-swatch swatch-sunset" data-color="sunset" title="برتقالي"><i class="fa-solid fa-check swatch-check"></i></div>
                                        <div class="theme-palette-swatch swatch-slate" data-color="slate" title="رمادي"><i class="fa-solid fa-check swatch-check"></i></div>
                                    </div>
                                </div>
                            </li>

                            <!-- Notifications -->
                            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2" id="notifDropdown">
                                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fa-regular fa-bell fa-lg"></i>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="position-absolute top-0 start-50 translate-middle-y badge rounded-pill bg-danger" id="notifBadge" style="font-size:10px"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                                    <?php endif ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-0" style="width:380px;max-width:92vw;border-radius:10px;overflow:hidden">
                                    <div class="d-flex align-items-center justify-content-between py-3 px-4" style="background:var(--bs-primary);color:#fff">
                                        <span class="fw-bold"><i class="fa fa-bell me-1"></i> الإشعارات</span>
                                        <button type="button" id="btnMarkAllRead" class="btn btn-sm" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;padding:3px 10px;border-radius:12px;font-weight:600">
                                            <i class="fa fa-check-double"></i> تمييز الجميع كمقروء
                                        </button>
                                    </div>
                                    <div id="notifList" style="max-height:380px;overflow-y:auto">
                                        <?php if (empty($latestNotifs)): ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="fa-regular fa-bell-slash d-block mb-2" style="font-size:28px"></i>
                                            لا توجد إشعارات
                                        </div>
                                        <?php else: ?>
                                        <?php foreach ($latestNotifs as $n): ?>
                                        <?php
                                            $isUnread = ((int)$n->is_unread === 1);
                                            $timeAgo  = Yii::$app->formatter->asRelativeTime($n->created_time);
                                            $href     = !empty($n->href) ? Url::to([$n->href]) : '#';
                                        ?>
                                        <a href="<?= Html::encode($href) ?>" class="dropdown-item notif-item <?= $isUnread ? 'notif-unread' : '' ?>" style="white-space:normal;padding:11px 14px;border-bottom:1px solid var(--bs-border-color)">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="notif-dot rounded-circle flex-shrink-0 mt-1" style="width:8px;height:8px;background:<?= $isUnread ? 'var(--bs-danger)' : 'var(--bs-border-color)' ?>"></span>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="<?= $isUnread ? 'fw-semibold text-heading' : 'text-muted' ?>" style="font-size:13px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= Html::encode($n->title_html ?: $n->body_html) ?></div>
                                                    <small class="text-muted"><i class="fa-regular fa-clock"></i> <?= $timeAgo ?></small>
                                                </div>
                                            </div>
                                        </a>
                                        <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                    <div class="border-top">
                                        <?= Html::a(
                                            '<i class="fa fa-list-ul"></i> مشاهدة جميع الإشعارات',
                                            ['/notification/notification/index'],
                                            ['class' => 'dropdown-item text-center py-3 fw-bold', 'style' => 'font-size:13px;color:var(--bs-primary)']
                                        ) ?>
                                    </div>
                                </div>
                            </li>

                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="<?= Html::encode($avatarSrc) ?>" onerror="<?= $onError ?>" class="w-px-40 h-auto rounded-circle" alt="<?= Html::encode($userName) ?>">
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <?= Html::a(
                                            '<i class="fa fa-user me-2"></i> الملف الشخصي',
                                            Url::to(['/employee/update', 'id' => $userId]),
                                            ['class' => 'dropdown-item']
                                        ) ?>
                                    </li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li>
                                        <?= Html::a(
                                            '<i class="fa fa-sign-out-alt me-2"></i> تسجيل الخروج',
                                            ['/site/logout'],
                                            ['data-method' => 'post', 'class' => 'dropdown-item']
                                        ) ?>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>

                <div class="content-wrapper">
                    <?= $this->render('content.php', ['content' => $content]) ?>
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    <!-- Vuexy JS -->
    <script src="<?= $baseUrl ?>/vuexy/vendor/libs/node-waves/node-waves.js"></script>
    <script src="<?= $baseUrl ?>/vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?= $baseUrl ?>/vuexy/vendor/libs/hammer/hammer.js"></script>
    <script src="<?= $baseUrl ?>/vuexy/vendor/js/menu.js"></script>
    <script src="<?= $baseUrl ?>/vuexy/js/main.js"></script>

    <!-- Tayseer Theme System -->
    <script src="<?= $baseUrl ?>/js/tayseer-theme.js?v=<?= time() ?>"></script>

    <!-- Tayseer Responsive -->
    <script src="<?= $baseUrl ?>/js/tayseer-responsive.js?v=<?= time() ?>"></script>

    <!-- Global Image Lightbox -->
    <div id="tLightbox" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.88);align-items:center;justify-content:center;cursor:zoom-out">
        <button onclick="tLightboxClose()" style="position:absolute;top:16px;right:16px;width:44px;height:44px;border-radius:50%;border:none;background:rgba(255,255,255,.15);color:#fff;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:1">&times;</button>
        <img id="tLbImg" src="" style="max-width:92vw;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 8px 40px rgba(0,0,0,.5)" alt="">
    </div>
    <script>
    function tLightboxOpen(src){if(!src)return;document.getElementById('tLbImg').src=src;document.getElementById('tLightbox').style.display='flex';}
    function tLightboxClose(){document.getElementById('tLightbox').style.display='none';document.getElementById('tLbImg').src='';}
    document.getElementById('tLightbox').addEventListener('click',function(e){if(e.target.id!=='tLbImg')tLightboxClose();});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&document.getElementById('tLightbox').style.display==='flex')tLightboxClose();});
    document.addEventListener('click',function(e){var t=e.target;if(t.classList&&t.classList.contains('t-zoomable')){e.preventDefault();e.stopPropagation();tLightboxOpen(t.getAttribute('data-full')||t.src);}},true);
    </script>

    <?php
    $notifJs = <<<JSBLOCK
    var notifMarked=false;
    $("#notifDropdown").on("show.bs.dropdown",function(){
        if(notifMarked) return;
        notifMarked=true;
        $.post("$markReadUrl",function(){
            $("#notifBadge").fadeOut(300);
            setTimeout(function(){
                $(".notif-unread").css("background","transparent").removeClass("notif-unread");
                $(".notif-dot").css("background","var(--bs-border-color)");
            },1000);
        });
    });
    $("#btnMarkAllRead").on("click",function(e){
        e.stopPropagation();
        var btn=$(this);
        btn.html('<i class="fa fa-spinner fa-spin"></i> جاري...');
        $.post("$markReadUrl",function(){
            $("#notifBadge").fadeOut(300);
            $(".notif-unread").css("background","transparent").removeClass("notif-unread");
            $(".notif-dot").css("background","var(--bs-border-color)");
            btn.html('<i class="fa fa-check"></i> تم التمييز');
            setTimeout(function(){btn.html('<i class="fa fa-check-double"></i> تمييز الجميع كمقروء');},2000);
        });
    });
    $(".notif-unread").css("background","rgba(var(--bs-primary-rgb),.06)");
    JSBLOCK;
    $this->registerJs($notifJs, \yii\web\View::POS_READY);
    ?>

    <script>
    (function(){
        var FS_KEY = 'tayseer_fullscreen';

        function isFullscreen() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
        }

        function enterFullscreen() {
            var el = document.documentElement;
            var p;
            if (el.requestFullscreen) p = el.requestFullscreen();
            else if (el.webkitRequestFullscreen) p = el.webkitRequestFullscreen();
            else if (el.msRequestFullscreen) p = el.msRequestFullscreen();
            return p;
        }

        function exitFullscreen() {
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            else if (document.msExitFullscreen) document.msExitFullscreen();
        }

        function updateIcon() {
            var icon = document.getElementById('fullscreenIcon');
            if (!icon) return;
            if (isFullscreen()) { icon.classList.remove('fa-expand'); icon.classList.add('fa-compress'); }
            else { icon.classList.remove('fa-compress'); icon.classList.add('fa-expand'); }
        }

        window.toggleFullScreen = function() {
            if (isFullscreen()) {
                sessionStorage.removeItem(FS_KEY);
                exitFullscreen();
            } else {
                sessionStorage.setItem(FS_KEY, '1');
                enterFullscreen();
            }
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isFullscreen()) {
                sessionStorage.removeItem(FS_KEY);
            }
        }, true);

        function onFSChange() { updateIcon(); }
        document.addEventListener('fullscreenchange', onFSChange);
        document.addEventListener('webkitfullscreenchange', onFSChange);

        if (sessionStorage.getItem(FS_KEY) && !isFullscreen()) {
            updateIcon();
            var restored = false;
            function restoreFS() {
                if (restored) return;
                restored = true;
                document.removeEventListener('click', restoreFS, true);
                document.removeEventListener('keydown', restoreFS, true);
                document.removeEventListener('mousedown', restoreFS, true);
                if (sessionStorage.getItem(FS_KEY) && !isFullscreen()) {
                    enterFullscreen();
                }
            }
            document.addEventListener('click', restoreFS, true);
            document.addEventListener('keydown', restoreFS, true);
            document.addEventListener('mousedown', restoreFS, true);
        }
    })();
    </script>

    <?php $this->endBody() ?>
    </body>
    </html>
    <?php $this->endPage() ?>
<?php } ?>
