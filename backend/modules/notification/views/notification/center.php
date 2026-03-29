<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use backend\modules\notification\models\Notification;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel backend\modules\notification\models\NotificationSearch */

$this->title = Yii::t('app', 'مركز الإشعارات');
$this->params['breadcrumbs'][] = $this->title;

$filter = Yii::$app->request->get('NotificationSearch', []);
$currentFilter = $filter['is_unread'] ?? '';
$models = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();

$grouped = [];
$now = time();
$todayStart = strtotime('today');
$yesterdayStart = strtotime('yesterday');
$weekStart = strtotime('-7 days');

foreach ($models as $n) {
    $t = (int) $n->created_time;
    if ($t >= $todayStart) {
        $grouped['اليوم'][] = $n;
    } elseif ($t >= $yesterdayStart) {
        $grouped['أمس'][] = $n;
    } elseif ($t >= $weekStart) {
        $grouped['هذا الأسبوع'][] = $n;
    } else {
        $grouped['أقدم'][] = $n;
    }
}
?>

<style>
.notif-center-card {
    border-radius: 10px;
    overflow: hidden;
}
.notif-filter-tabs .btn {
    border-radius: 20px;
    padding: 6px 18px;
    font-size: 13px;
    font-weight: 500;
}
.notif-filter-tabs .btn.active {
    box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), .25);
}
.notif-list-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--bs-border-color);
    transition: background .2s;
    text-decoration: none;
    color: inherit;
    position: relative;
}
.notif-list-item:hover {
    background: rgba(var(--bs-primary-rgb), .04);
}
.notif-list-item.unread {
    background: rgba(var(--bs-primary-rgb), .06);
}
.notif-list-item:last-child {
    border-bottom: none;
}
.notif-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}
.notif-content {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
.notif-title {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.notif-meta {
    font-size: 12px;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    gap: 12px;
}
.notif-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0;
    transition: opacity .2s;
}
.notif-list-item:hover .notif-actions {
    opacity: 1;
}
.notif-action-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: none;
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    transition: all .15s;
}
.notif-action-btn:hover {
    background: var(--bs-primary);
    color: #fff;
}
.notif-action-btn.delete-btn:hover {
    background: var(--bs-danger);
}
.notif-group-header {
    font-size: 12px;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 20px 8px;
    background: var(--bs-tertiary-bg);
    border-bottom: 1px solid var(--bs-border-color);
}
.notif-empty {
    text-align: center;
    padding: 60px 20px;
}
.notif-empty i {
    font-size: 48px;
    color: var(--bs-secondary-color);
    margin-bottom: 16px;
    display: block;
    opacity: .4;
}
.notif-unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--bs-primary);
    flex-shrink: 0;
    margin-top: 6px;
}
</style>

<div class="notification-center">
    <div class="card notif-center-card shadow-sm">
        <!-- Header -->
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-4" style="border-bottom: 2px solid var(--bs-border-color)">
            <div>
                <h5 class="mb-1 fw-bold"><i class="fa-solid fa-bell me-2"></i><?= $this->title ?></h5>
                <small class="text-muted"><?= Yii::t('app', 'إجمالي') ?>: <?= $dataProvider->getTotalCount() ?> <?= Yii::t('app', 'إشعار') ?></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnMarkAllReadCenter" title="<?= Yii::t('app', 'تمييز الكل كمقروء') ?>">
                    <i class="fa fa-check-double"></i> <span class="d-none d-md-inline"><?= Yii::t('app', 'تمييز الكل كمقروء') ?></span>
                </button>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="px-4 py-3 border-bottom notif-filter-tabs d-flex gap-2 flex-wrap">
            <?php
            $filters = [
                '' => ['label' => 'الكل', 'icon' => 'fa-list'],
                '1' => ['label' => 'غير مقروءة', 'icon' => 'fa-envelope'],
                '0' => ['label' => 'مقروءة', 'icon' => 'fa-envelope-open'],
            ];
            foreach ($filters as $val => $f):
                $isActive = (string)$currentFilter === (string)$val;
                $url = Url::to(['center', 'NotificationSearch[is_unread]' => $val !== '' ? $val : null]);
            ?>
                <a href="<?= $url ?>" class="btn btn-sm <?= $isActive ? 'btn-primary active' : 'btn-outline-secondary' ?>">
                    <i class="fa-solid <?= $f['icon'] ?> me-1"></i> <?= $f['label'] ?>
                </a>
            <?php endforeach ?>
        </div>

        <!-- Notification list -->
        <div class="notif-list">
            <?php if (empty($models)): ?>
                <div class="notif-empty">
                    <i class="fa-regular fa-bell-slash"></i>
                    <p class="text-muted mb-0"><?= Yii::t('app', 'لا توجد إشعارات') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped as $groupLabel => $items): ?>
                    <div class="notif-group-header"><?= $groupLabel ?></div>
                    <?php foreach ($items as $n): ?>
                        <?php
                        $isUnread = (int)$n->is_unread === 1;
                        $icon = Notification::getTypeIcon($n->type_of_notification);
                        $color = Notification::getTypeColor($n->type_of_notification);
                        $typeLabel = Notification::getTypeLabel($n->type_of_notification);
                        $href = !empty($n->href) ? Url::to([$n->href, 'notificationID' => $n->id]) : '#';
                        $timeAgo = Yii::$app->formatter->asRelativeTime($n->created_time);
                        $fullDate = $n->created_time ? date('Y-m-d H:i', $n->created_time) : '';
                        $senderName = $n->sender ? $n->sender->username : 'النظام';
                        ?>
                        <a href="<?= Html::encode($href) ?>"
                           class="notif-list-item <?= $isUnread ? 'unread' : '' ?>"
                           data-id="<?= $n->id ?>">

                            <?php if ($isUnread): ?>
                                <div class="notif-unread-dot"></div>
                            <?php else: ?>
                                <div style="width:8px;flex-shrink:0"></div>
                            <?php endif ?>

                            <div class="notif-icon-wrap" style="background: <?= $color ?>18; color: <?= $color ?>">
                                <i class="fa-solid <?= $icon ?>"></i>
                            </div>

                            <div class="notif-content">
                                <div class="notif-title <?= $isUnread ? 'fw-semibold' : 'text-body-secondary' ?>">
                                    <?= Html::encode($n->title_html ?: $n->body_html) ?>
                                </div>
                                <div class="notif-meta">
                                    <span class="badge rounded-pill" style="background: <?= $color ?>20; color: <?= $color ?>; font-size:11px"><?= $typeLabel ?></span>
                                    <span><i class="fa-regular fa-user me-1"></i><?= Html::encode($senderName) ?></span>
                                    <span title="<?= $fullDate ?>"><i class="fa-regular fa-clock me-1"></i><?= $timeAgo ?></span>
                                </div>
                            </div>

                            <div class="notif-actions" onclick="event.preventDefault();event.stopPropagation();">
                                <?php if ($isUnread): ?>
                                    <button class="notif-action-btn" title="<?= Yii::t('app', 'تمييز كمقروء') ?>" onclick="markOneRead(<?= $n->id ?>, this)">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                <?php endif ?>
                                <button class="notif-action-btn delete-btn" title="<?= Yii::t('app', 'حذف') ?>" onclick="deleteNotif(<?= $n->id ?>, this)">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </a>
                    <?php endforeach ?>
                <?php endforeach ?>
            <?php endif ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination && $pagination->totalCount > $pagination->pageSize): ?>
            <div class="card-footer d-flex justify-content-center py-3">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm mb-0'],
                    'linkOptions' => ['class' => 'page-link'],
                    'disabledListItemSubTagOptions' => ['class' => 'page-link'],
                ]) ?>
            </div>
        <?php endif ?>
    </div>
</div>

<script>
function markOneRead(id, btn) {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?= Url::to(['/notification/notification/mark-read']) ?>&id=' + id, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        var item = btn.closest('.notif-list-item');
        if (item) {
            item.classList.remove('unread');
            var dot = item.querySelector('.notif-unread-dot');
            if (dot) dot.style.background = 'transparent';
            var title = item.querySelector('.notif-title');
            if (title) { title.classList.remove('fw-semibold'); title.classList.add('text-body-secondary'); }
        }
        btn.remove();
    };
    xhr.send('_csrf-backend=' + encodeURIComponent(csrfToken));
}

function deleteNotif(id, btn) {
    if (!confirm('هل أنت متأكد من الحذف؟')) return;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?= Url::to(['/notification/notification/delete']) ?>&id=' + id, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        var item = btn.closest('.notif-list-item');
        if (item) {
            item.style.transition = 'opacity .3s, max-height .3s';
            item.style.opacity = '0';
            item.style.maxHeight = '0';
            item.style.overflow = 'hidden';
            item.style.padding = '0 20px';
            setTimeout(function() { item.remove(); }, 300);
        }
    };
    xhr.send('_csrf-backend=' + encodeURIComponent(csrfToken));
}

document.getElementById('btnMarkAllReadCenter')?.addEventListener('click', function() {
    var btn = this;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري...';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?= Url::to(['/notification/notification/is-read']) ?>', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        document.querySelectorAll('.notif-list-item.unread').forEach(function(el) {
            el.classList.remove('unread');
        });
        document.querySelectorAll('.notif-unread-dot').forEach(function(el) {
            el.style.background = 'transparent';
        });
        document.querySelectorAll('.notif-title.fw-semibold').forEach(function(el) {
            el.classList.remove('fw-semibold');
            el.classList.add('text-body-secondary');
        });
        btn.innerHTML = '<i class="fa fa-check"></i> تم التمييز';
        setTimeout(function() {
            btn.innerHTML = '<i class="fa fa-check-double"></i> <span class="d-none d-md-inline">تمييز الكل كمقروء</span>';
        }, 2000);
    };
    xhr.send('_csrf-backend=' + encodeURIComponent(csrfToken));
});
</script>
