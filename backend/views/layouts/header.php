<?php
/**
 * Tabler — Top Navbar (Header)
 */

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\CompanyChecked;
use backend\modules\notification\models\Notification;

$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();

if ($primary_company == '') {
    $logo = Yii::$app->params['companies_logo'];
    $companyName = '';
} else {
    $logo = $primary_company->logo;
    $companyName = $primary_company->name;
}

$_avatarRecord = \backend\modules\employee\models\EmployeeFiles::find()
    ->where(['user_id' => Yii::$app->user->id])
    ->andWhere(['type' => 0])
    ->orderBy(['id' => SORT_DESC])
    ->one();
$avatar = $_avatarRecord;
if ($avatar && $avatar->path && !file_exists(Yii::getAlias('@webroot') . '/' . ltrim($avatar->path, '/'))) {
    $avatar = null;
}

$userId = Yii::$app->user->id;
$unreadCount = (int) Notification::find()
    ->where(['recipient_id' => $userId, 'is_unread' => 1])
    ->count();
$latestNotifs = Notification::find()
    ->where(['recipient_id' => $userId])
    ->orderBy(['id' => SORT_DESC])
    ->limit(10)
    ->all();

Yii::$app->view->registerJsVar('base_url', Yii::$app->request->hostInfo . Yii::$app->getUrlManager()->getBaseUrl());

$defaultAvatar = Yii::$app->request->baseUrl . '/img/default-avatar.png';
$avatarSrc = !empty($avatar->path) ? Url::to([$avatar->path]) : $defaultAvatar;
$onError = "this.onerror=null;this.src='" . $defaultAvatar . "';";
$markReadUrl = Url::to(['/notification/notification/is-read']);
?>

<header class="navbar navbar-expand-md d-print-none tayseer-header">
    <div class="container-xl">
        <!-- Right-side items: notifications + user -->
        <div class="navbar-nav flex-row order-md-last">

            <!-- Notifications -->
            <div class="nav-item dropdown d-flex me-3" id="notifDropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset px-2" data-bs-toggle="dropdown"
                   aria-label="الإشعارات" style="position:relative">
                    <i class="fa-regular fa-bell" style="font-size:18px"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger badge-notification" id="notifBadge">
                            <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                        </span>
                    <?php endif ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="width:350px;max-width:92vw;padding:0;border-radius:10px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.18)">
                    <div style="background:var(--clr-primary,#800020);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:10px 14px">
                        <span style="font-size:14px;font-weight:700"><i class="fa fa-bell"></i> الإشعارات</span>
                        <button type="button" id="btnMarkAllRead" class="btn btn-sm" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;padding:3px 10px;border-radius:12px;font-weight:600">
                            <i class="fa fa-check-double"></i> تمييز الجميع كمقروء
                        </button>
                    </div>
                    <div id="notifList" style="max-height:380px;overflow-y:auto">
                        <?php if (empty($latestNotifs)): ?>
                        <div style="padding:30px 15px;text-align:center;color:#aaa">
                            <i class="fa-regular fa-bell-slash" style="font-size:28px;display:block;margin-bottom:10px;color:#ddd"></i>
                            لا توجد إشعارات
                        </div>
                        <?php else: ?>
                        <?php foreach ($latestNotifs as $n): ?>
                        <?php
                            $isUnread = ((int)$n->is_unread === 1);
                            $timeAgo  = Yii::$app->formatter->asRelativeTime($n->created_time);
                            $href     = !empty($n->href) ? Url::to([$n->href]) : '#';
                        ?>
                        <a href="<?= Html::encode($href) ?>" class="dropdown-item notif-item <?= $isUnread ? 'notif-unread' : '' ?>" style="white-space:normal;padding:11px 14px;border-bottom:1px solid #f0f0f0">
                            <div style="display:flex;align-items:flex-start;gap:10px">
                                <span class="notif-dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $isUnread ? '#e74c3c' : '#ddd' ?>;flex-shrink:0;margin-top:5px"></span>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:<?= $isUnread ? '600' : '400' ?>;color:<?= $isUnread ? '#222' : '#666' ?>;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= Html::encode($n->title_html ?: $n->body_html) ?></div>
                                    <div style="font-size:11px;color:#aaa"><i class="fa-regular fa-clock"></i> <?= $timeAgo ?></div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach ?>
                        <?php endif ?>
                    </div>
                    <div style="border-top:1.5px solid #eee;background:#fafafa">
                        <?= Html::a(
                            '<i class="fa fa-list-ul"></i> مشاهدة جميع الإشعارات',
                            ['/notification/notification/index'],
                            ['class' => 'dropdown-item text-center', 'style' => 'padding:11px 15px;font-size:13px;font-weight:700;color:var(--clr-primary,#800020)']
                        ) ?>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                   aria-label="<?= Yii::t('app', 'قائمة المستخدم') ?>">
                    <span class="avatar avatar-sm rounded-circle" style="background-image:url('<?= Html::encode($avatarSrc) ?>')"></span>
                    <div class="d-none d-xl-block pe-2">
                        <div style="font-size:13px;font-weight:600"><?= Html::encode(Yii::$app->user->identity ? Yii::$app->user->identity['username'] : 'مستخدم') ?></div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <div class="dropdown-header text-center" style="padding:15px">
                        <span class="avatar avatar-lg rounded-circle mb-2" style="background-image:url('<?= Html::encode($avatarSrc) ?>')"></span>
                        <p class="mb-0" style="font-weight:600"><?= Html::encode(Yii::$app->user->identity ? Yii::$app->user->identity['username'] : 'مستخدم') ?></p>
                    </div>
                    <div class="dropdown-divider"></div>
                    <?= Html::a(
                        '<i class="fa fa-user me-2"></i> ' . Yii::t('app', 'الملف الشخصي'),
                        Url::to(['/employee/update', 'id' => Yii::$app->user->id]),
                        ['class' => 'dropdown-item']
                    ) ?>
                    <div class="dropdown-divider"></div>
                    <?= Html::a(
                        '<i class="fa fa-sign-out-alt me-2"></i> ' . Yii::t('app', 'تسجيل الخروج'),
                        ['/site/logout'],
                        ['data-method' => 'post', 'class' => 'dropdown-item']
                    ) ?>
                </div>
            </div>
        </div>

        <?php if (!empty($companyName)): ?>
            <span class="navbar-text text-muted d-none d-md-inline" style="font-size:13px;font-weight:600"><?= Html::encode($companyName) ?></span>
        <?php endif ?>
    </div>
</header>

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
            $(".notif-dot").css("background","#ddd");
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
        $(".notif-dot").css("background","#ddd");
        btn.html('<i class="fa fa-check"></i> تم التمييز');
        setTimeout(function(){btn.html('<i class="fa fa-check-double"></i> تمييز الجميع كمقروء');},2000);
    });
});
$(".notif-unread").css("background","#fef9f0");
JSBLOCK;
$this->registerJs($notifJs, \yii\web\View::POS_READY);
?>
