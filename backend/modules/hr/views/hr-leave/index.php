<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  إدارة الإجازات — الشاشة الموحدة
 *  ──────────────────────────────────────
 *  طلبات الإجازات + العطل الرسمية + أنواع وسياسات الإجازات
 *  + جدول أيام العمل والورديات
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use backend\helpers\NameHelper;

/** @var yii\web\View $this */
/** @var string $tab */
/** @var string $statusFilter */
/** @var yii\data\ActiveDataProvider $requestsProvider */
/** @var array $kpis */
/** @var yii\data\ActiveDataProvider $holidaysProvider */
/** @var array $typesData */
/** @var array $policiesData */
/** @var array $workdays */
/** @var array $shifts */
/** @var array $employees */
/** @var array $leaveTypes */
/** @var array $departments */
/** @var array $designations */
/** @var array $leavePolicies */

$this->title = 'إدارة الإجازات';

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── Safe defaults ─── */
$kpis = $kpis ?: ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

/* ─── Day name map ─── */
$dayMap = [
    'Sundays'    => 'الأحد',
    'Mondays'    => 'الاثنين',
    'Tuesdays'   => 'الثلاثاء',
    'Wednesdays' => 'الأربعاء',
    'Thursdays'  => 'الخميس',
    'Fridays'    => 'الجمعة',
    'Saturdays'  => 'السبت',
];

/* ─── Status map ─── */
$statusMap = [
    'under review' => ['label' => 'قيد المراجعة', 'icon' => 'clock-o',    'color' => '#f39c12', 'bg' => '#fef9e7'],
    'approved'     => ['label' => 'موافق عليها',  'icon' => 'check-circle','color' => '#27ae60', 'bg' => '#eafaf1'],
    'rejected'     => ['label' => 'مرفوضة',       'icon' => 'times-circle','color' => '#e74c3c', 'bg' => '#fdedec'],
];

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
?>

<style>
/* ═══════════════════════════════════════
   Leave Management — Page Styles
   ═══════════════════════════════════════ */

/* Tab navigation */
.leave-tabs {
    display: flex; gap: 0; border-bottom: 2px solid var(--hr-border, #e0e0e0);
    margin-bottom: 24px; overflow-x: auto;
}
.leave-tab {
    padding: 12px 22px; font-size: 13px; font-weight: 600;
    color: var(--hr-text-muted, #95a5a6); cursor: pointer;
    border-bottom: 3px solid transparent; transition: all 0.2s;
    white-space: nowrap; display: flex; align-items: center; gap: 8px;
    text-decoration: none !important;
}
.leave-tab:hover { color: var(--hr-primary, #800020); background: var(--hr-primary-50, #fdf0f3); }
.leave-tab.active {
    color: var(--hr-primary, #800020);
    border-bottom-color: var(--hr-primary, #800020);
}
.leave-tab .badge-count {
    background: var(--hr-danger, #e74c3c); color: #fff;
    font-size: 10px; padding: 2px 7px; border-radius: 10px;
    font-weight: 700; min-width: 20px; text-align: center;
}
.leave-tab-panel { display: none; }
.leave-tab-panel.active { display: block; }

/* Quick filter pills */
.leave-filters { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
.leave-filter-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
    border: 1px solid var(--hr-border, #e0e0e0); background: #fff;
    color: var(--hr-text-light, #7f8c8d); cursor: pointer;
    text-decoration: none !important; transition: all 0.2s;
}
.leave-filter-pill:hover, .leave-filter-pill.active {
    border-color: var(--hr-primary, #800020); color: var(--hr-primary, #800020);
    background: var(--hr-primary-50, #fdf0f3);
}

/* Status badge */
.leave-status {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
}

/* Card table wrapper */
.leave-card {
    background: var(--hr-card-bg, #fff);
    border-radius: var(--hr-radius-md, 10px);
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    overflow: hidden;
}
.leave-card .table { margin-bottom: 0; }
.leave-card .table th {
    background: #f8f9fa; font-size: 12px; font-weight: 700;
    color: var(--hr-text-muted, #6c757d); border-top: none; padding: 10px 14px;
}
.leave-card .table td { font-size: 13px; vertical-align: middle; padding: 10px 14px; }
.leave-card .table tr:hover td { background: #fafbfc; }

/* Action buttons */
.leave-action {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 8px; border: none;
    cursor: pointer; transition: all 0.15s; background: transparent;
    font-size: 13px;
}
.leave-action:hover { transform: scale(1.1); }
.leave-action--approve { color: #27ae60; }
.leave-action--approve:hover { background: #eafaf1; }
.leave-action--reject { color: #e74c3c; }
.leave-action--reject:hover { background: #fdedec; }
.leave-action--edit { color: #d97706; }
.leave-action--edit:hover { background: #fef9e7; }
.leave-action--delete { color: #dc2626; }
.leave-action--delete:hover { background: #fdedec; }
.leave-action--view { color: #3498db; }
.leave-action--view:hover { background: #ebf5fb; }

/* Workday row */
.wd-row {
    display: grid; grid-template-columns: 120px 100px 100px 150px;
    gap: 12px; align-items: center; padding: 10px 16px;
    border-bottom: 1px solid #f0f0f0;
}
.wd-row:last-child { border-bottom: none; }
.wd-row label { font-weight: 700; font-size: 13px; color: var(--hr-text, #2c3e50); margin: 0; }
.wd-row input[type="time"] {
    padding: 5px 8px; border: 1px solid #ddd; border-radius: 6px;
    font-size: 13px; direction: ltr; text-align: center;
}
.wd-row select {
    padding: 5px 8px; border: 1px solid #ddd; border-radius: 6px;
    font-size: 12px; font-weight: 600;
}
.wd-day-off { opacity: 0.5; background: #f5f5f5; }

/* Modal inline */
.leave-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4);
    z-index: 1050; align-items: center; justify-content: center;
}
.leave-modal-overlay.show { display: flex; }
.leave-modal-box {
    background: #fff; border-radius: 14px; width: 520px; max-width: 95vw;
    max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2);
    animation: slideUp .2s ease;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.leave-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #eee;
}
.leave-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--hr-text, #2c3e50); }
.leave-modal-close {
    width: 32px; height: 32px; border: none; background: #f5f5f5;
    border-radius: 50%; cursor: pointer; font-size: 16px; display: flex;
    align-items: center; justify-content: center; color: #666; transition: all 0.15s;
}
.leave-modal-close:hover { background: #e0e0e0; }
.leave-modal-body { padding: 20px; }
.leave-modal-footer {
    padding: 14px 20px; border-top: 1px solid #eee;
    display: flex; justify-content: flex-start; gap: 8px;
}

/* Form fields */
.leave-field { margin-bottom: 14px; }
.leave-field label { display: block; font-size: 12px; font-weight: 700; color: #555; margin-bottom: 5px; }
.leave-field input, .leave-field select, .leave-field textarea {
    width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-size: 13px; transition: border-color 0.15s;
}
.leave-field input:focus, .leave-field select:focus, .leave-field textarea:focus {
    border-color: var(--hr-primary, #800020); outline: none;
    box-shadow: 0 0 0 3px rgba(128,0,32,.1);
}
.leave-field textarea { resize: vertical; min-height: 60px; }
.leave-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

/* Empty state */
.leave-empty {
    text-align: center; padding: 40px 20px; color: var(--hr-text-muted, #95a5a6);
}
.leave-empty i { font-size: 48px; margin-bottom: 12px; display: block; opacity: .4; }
.leave-empty p { font-size: 14px; }

/* Shift card */
.shift-card {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; background: #f8f9fa; border-radius: 10px;
    margin-bottom: 8px;
}
.shift-card__info { display: flex; align-items: center; gap: 12px; }
.shift-card__title { font-weight: 700; font-size: 14px; color: var(--hr-text, #2c3e50); }
.shift-card__time {
    direction: ltr; font-size: 12px; color: var(--hr-text-light, #7f8c8d);
    background: #fff; padding: 3px 10px; border-radius: 6px; font-weight: 600;
}
</style>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان وأزرار الإجراءات             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-header">
        <h1><i class="fa fa-calendar-check-o"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  بطاقات ملخص                          ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-kpi-grid hr-kpi-grid--4">
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-primary, #800020)">
            <div class="hr-kpi-card__icon"><i class="fa fa-file-text"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">إجمالي الطلبات</span>
                <span class="hr-kpi-card__value"><?= number_format($kpis['total'] ?? 0) ?></span>
            </div>
        </div>
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-warning, #f39c12)">
            <div class="hr-kpi-card__icon"><i class="fa fa-clock-o"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">قيد المراجعة</span>
                <span class="hr-kpi-card__value"><?= number_format($kpis['pending'] ?? 0) ?></span>
            </div>
        </div>
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-success, #27ae60)">
            <div class="hr-kpi-card__icon"><i class="fa fa-check-circle"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">موافق عليها</span>
                <span class="hr-kpi-card__value"><?= number_format($kpis['approved'] ?? 0) ?></span>
            </div>
        </div>
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-danger, #e74c3c)">
            <div class="hr-kpi-card__icon"><i class="fa fa-times-circle"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">مرفوضة</span>
                <span class="hr-kpi-card__value"><?= number_format($kpis['rejected'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  التبويبات                              ║
         ╚═══════════════════════════════════════╝ -->
    <div class="leave-tabs" id="leaveTabs">
        <a class="leave-tab <?= $tab === 'requests' ? 'active' : '' ?>" data-tab="requests" href="javascript:void(0)">
            <i class="fa fa-envelope-open"></i> طلبات الإجازات
            <?php if ($kpis['pending'] > 0): ?>
                <span class="badge-count"><?= $kpis['pending'] ?></span>
            <?php endif; ?>
        </a>
        <a class="leave-tab <?= $tab === 'holidays' ? 'active' : '' ?>" data-tab="holidays" href="javascript:void(0)">
            <i class="fa fa-calendar"></i> العطل الرسمية
        </a>
        <a class="leave-tab <?= $tab === 'types' ? 'active' : '' ?>" data-tab="types" href="javascript:void(0)">
            <i class="fa fa-tags"></i> أنواع وسياسات الإجازات
        </a>
        <a class="leave-tab <?= $tab === 'schedule' ? 'active' : '' ?>" data-tab="schedule" href="javascript:void(0)">
            <i class="fa fa-calendar-check-o"></i> جدول أيام العمل
        </a>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 1: طلبات الإجازات
         ═══════════════════════════════════════ -->
    <div class="leave-tab-panel <?= $tab === 'requests' ? 'active' : '' ?>" id="panel-requests">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
            <div class="leave-filters">
                <a class="leave-filter-pill <?= $statusFilter === '' ? 'active' : '' ?>"
                   href="<?= Url::to(['index', 'tab' => 'requests']) ?>">
                    <i class="fa fa-list"></i> الكل (<?= $kpis['total'] ?>)
                </a>
                <a class="leave-filter-pill <?= $statusFilter === 'under review' ? 'active' : '' ?>"
                   href="<?= Url::to(['index', 'tab' => 'requests', 'status' => 'under review']) ?>">
                    <i class="fa fa-clock-o"></i> معلقة (<?= $kpis['pending'] ?>)
                </a>
                <a class="leave-filter-pill <?= $statusFilter === 'approved' ? 'active' : '' ?>"
                   href="<?= Url::to(['index', 'tab' => 'requests', 'status' => 'approved']) ?>">
                    <i class="fa fa-check"></i> موافق عليها (<?= $kpis['approved'] ?>)
                </a>
                <a class="leave-filter-pill <?= $statusFilter === 'rejected' ? 'active' : '' ?>"
                   href="<?= Url::to(['index', 'tab' => 'requests', 'status' => 'rejected']) ?>">
                    <i class="fa fa-times"></i> مرفوضة (<?= $kpis['rejected'] ?>)
                </a>
            </div>
            <button class="hr-btn hr-btn--primary" onclick="openModal('requestModal')">
                <i class="fa fa-plus"></i> طلب إجازة جديد
            </button>
        </div>

        <div class="leave-card">
            <?php $models = $requestsProvider->getModels(); ?>
            <?php if (empty($models)): ?>
                <div class="leave-empty">
                    <i class="fa fa-inbox"></i>
                    <p>لا توجد طلبات إجازات<?= $statusFilter ? ' بهذا الفلتر' : '' ?></p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>الموظف</th>
                            <th>نوع الإجازة</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th style="text-align:center">المدة</th>
                            <th style="text-align:center">الحالة</th>
                            <th>السبب</th>
                            <th style="text-align:center;width:130px">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($models as $row): ?>
                            <?php
                                $start = new \DateTime($row['start_at']);
                                $end   = new \DateTime($row['end_at']);
                                $days  = $start->diff($end)->days + 1;
                                $sInfo = $statusMap[$row['status']] ?? ['label' => $row['status'], 'icon' => 'question', 'color' => '#999', 'bg' => '#f5f5f5'];
                            ?>
                            <tr>
                                <td style="text-align:center;font-weight:600;color:#aaa"><?= $i++ ?></td>
                                <td><strong><?= Html::encode(NameHelper::short($row['employee_name'] ?? '—')) ?></strong></td>
                                <td>
                                    <span style="color:var(--hr-info,#3498db);font-weight:600">
                                        <?= Html::encode($row['type_title'] ?? ($row['policy_title'] ?? '—')) ?>
                                    </span>
                                </td>
                                <td style="direction:ltr;text-align:right"><?= $row['start_at'] ?></td>
                                <td style="direction:ltr;text-align:right"><?= $row['end_at'] ?></td>
                                <td style="text-align:center">
                                    <span style="background:#ebf5fb;color:#2980b9;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700">
                                        <?= $days ?> يوم
                                    </span>
                                </td>
                                <td style="text-align:center">
                                    <span class="leave-status" style="background:<?= $sInfo['bg'] ?>;color:<?= $sInfo['color'] ?>">
                                        <i class="fa fa-<?= $sInfo['icon'] ?>"></i>
                                        <?= $sInfo['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:12px;color:#666"><?= Html::encode(mb_substr($row['reason'] ?? '', 0, 40)) ?><?= mb_strlen($row['reason'] ?? '') > 40 ? '...' : '' ?></span>
                                </td>
                                <td style="text-align:center;white-space:nowrap">
                                    <?php if ($row['status'] === 'under review'): ?>
                                        <button class="leave-action leave-action--approve" title="موافقة"
                                                onclick="leaveAction('approve-request', <?= $row['id'] ?>)">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        <button class="leave-action leave-action--reject" title="رفض"
                                                onclick="leaveAction('reject-request', <?= $row['id'] ?>)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="leave-action leave-action--delete" title="حذف"
                                            onclick="if(confirm('هل أنت متأكد من حذف هذا الطلب؟')) leaveAction('delete-request', <?= $row['id'] ?>)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php
        $pagination = $requestsProvider->getPagination();
        $pageCount = $pagination->getPageCount();
        if ($pageCount > 1): ?>
            <div style="display:flex;justify-content:center;padding:16px 0">
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination hr-pagination'],
                    'maxButtonCount' => 7,
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 2: العطل الرسمية
         ═══════════════════════════════════════ -->
    <div class="leave-tab-panel <?= $tab === 'holidays' ? 'active' : '' ?>" id="panel-holidays">

        <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
            <button class="hr-btn hr-btn--primary" onclick="openModal('holidayModal')">
                <i class="fa fa-plus"></i> إضافة عطلة
            </button>
        </div>

        <div class="leave-card">
            <?php $holidays = $holidaysProvider->getModels(); ?>
            <?php if (empty($holidays)): ?>
                <div class="leave-empty">
                    <i class="fa fa-calendar-times-o"></i>
                    <p>لا توجد عطل رسمية مسجلة</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>اسم العطلة</th>
                            <th>تاريخ البداية</th>
                            <th>تاريخ النهاية</th>
                            <th style="text-align:center">المدة</th>
                            <th style="text-align:center;width:100px">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($holidays as $h): ?>
                            <?php
                                $hStart = new \DateTime($h['start_at']);
                                $hEnd   = new \DateTime($h['end_at']);
                                $hDays  = $hStart->diff($hEnd)->days + 1;
                                $isPast = $hEnd < new \DateTime();
                            ?>
                            <tr style="<?= $isPast ? 'opacity:.6' : '' ?>">
                                <td style="text-align:center;font-weight:600;color:#aaa"><?= $i++ ?></td>
                                <td>
                                    <i class="fa fa-star" style="color:var(--hr-accent,#d4a84b);margin-left:6px"></i>
                                    <strong><?= Html::encode($h['title']) ?></strong>
                                    <?php if ($isPast): ?>
                                        <small style="color:#aaa;margin-right:6px">(انتهت)</small>
                                    <?php endif; ?>
                                </td>
                                <td style="direction:ltr;text-align:right"><?= $h['start_at'] ?></td>
                                <td style="direction:ltr;text-align:right"><?= $h['end_at'] ?></td>
                                <td style="text-align:center">
                                    <span style="background:#fef9e7;color:#f39c12;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700">
                                        <?= $hDays ?> يوم
                                    </span>
                                </td>
                                <td style="text-align:center;white-space:nowrap">
                                    <button class="leave-action leave-action--edit" title="تعديل"
                                            onclick="editHoliday(<?= $h['id'] ?>, '<?= addslashes($h['title']) ?>', '<?= $h['start_at'] ?>', '<?= $h['end_at'] ?>')">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button class="leave-action leave-action--delete" title="حذف"
                                            onclick="if(confirm('هل أنت متأكد من حذف هذه العطلة؟')) leaveAction('delete-holiday', <?= $h['id'] ?>)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 3: أنواع وسياسات الإجازات
         ═══════════════════════════════════════ -->
    <div class="leave-tab-panel <?= $tab === 'types' ? 'active' : '' ?>" id="panel-types">

        <!-- أنواع الإجازات -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h4 style="margin:0;font-weight:700;color:var(--hr-text,#2c3e50)">
                <i class="fa fa-tags" style="color:var(--hr-primary,#800020)"></i> أنواع الإجازات
            </h4>
            <button class="hr-btn hr-btn--primary" onclick="openModal('typeModal')">
                <i class="fa fa-plus"></i> نوع جديد
            </button>
        </div>

        <div class="leave-card" style="margin-bottom:28px">
            <?php if (empty($typesData)): ?>
                <div class="leave-empty">
                    <i class="fa fa-tags"></i>
                    <p>لا توجد أنواع إجازات مسجلة</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>نوع الإجازة</th>
                            <th>الوصف</th>
                            <th style="text-align:center">عدد السياسات</th>
                            <th style="text-align:center">عدد الطلبات</th>
                            <th style="text-align:center">الحالة</th>
                            <th style="text-align:center;width:100px">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($typesData as $idx => $type): ?>
                            <tr>
                                <td style="text-align:center;font-weight:600;color:#aaa"><?= $idx + 1 ?></td>
                                <td><strong><?= Html::encode($type['title']) ?></strong></td>
                                <td style="font-size:12px;color:#666"><?= Html::encode($type['description'] ?? '—') ?></td>
                                <td style="text-align:center">
                                    <span style="background:#ebf5fb;color:#3498db;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700">
                                        <?= (int) $type['policies_count'] ?>
                                    </span>
                                </td>
                                <td style="text-align:center">
                                    <span style="background:#fdf0f3;color:#800020;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700">
                                        <?= (int) $type['requests_count'] ?>
                                    </span>
                                </td>
                                <td style="text-align:center">
                                    <?php if ($type['status'] === 'active'): ?>
                                        <span class="leave-status" style="background:#eafaf1;color:#27ae60"><i class="fa fa-check-circle"></i> فعّال</span>
                                    <?php else: ?>
                                        <span class="leave-status" style="background:#ecf0f1;color:#95a5a6"><i class="fa fa-ban"></i> معطّل</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;white-space:nowrap">
                                    <button class="leave-action leave-action--edit" title="تعديل"
                                            onclick="editType(<?= $type['id'] ?>, '<?= addslashes($type['title']) ?>', '<?= addslashes($type['description'] ?? '') ?>', '<?= $type['status'] ?>')">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button class="leave-action leave-action--delete" title="تعطيل"
                                            onclick="if(confirm('هل أنت متأكد من تعطيل هذا النوع؟')) leaveAction('delete-type', <?= $type['id'] ?>)">
                                        <i class="fa fa-power-off"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- سياسات الإجازات -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h4 style="margin:0;font-weight:700;color:var(--hr-text,#2c3e50)">
                <i class="fa fa-file-text-o" style="color:var(--hr-primary,#800020)"></i> سياسات الإجازات
            </h4>
            <button class="hr-btn hr-btn--primary" onclick="openModal('policyModal')">
                <i class="fa fa-plus"></i> سياسة جديدة
            </button>
        </div>

        <div class="leave-card">
            <?php if (empty($policiesData)): ?>
                <div class="leave-empty">
                    <i class="fa fa-file-text-o"></i>
                    <p>لا توجد سياسات إجازات مسجلة</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>السياسة</th>
                            <th>نوع الإجازة</th>
                            <th style="text-align:center">السنة</th>
                            <th style="text-align:center">أيام</th>
                            <th>القسم</th>
                            <th style="text-align:center">الجنس</th>
                            <th style="text-align:center">الحالة</th>
                            <th style="text-align:center;width:100px">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policiesData as $idx => $p): ?>
                            <?php
                                $genderMap = ['all' => 'الكل', 'Male' => 'ذكر', 'Female' => 'أنثى'];
                            ?>
                            <tr>
                                <td style="text-align:center;font-weight:600;color:#aaa"><?= $idx + 1 ?></td>
                                <td><strong><?= Html::encode($p['title']) ?></strong></td>
                                <td style="color:var(--hr-info,#3498db);font-weight:600"><?= Html::encode($p['type_title'] ?? '—') ?></td>
                                <td style="text-align:center;font-weight:700"><?= $p['year'] ?></td>
                                <td style="text-align:center">
                                    <span style="background:#eafaf1;color:#27ae60;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700">
                                        <?= $p['total_days'] ?> يوم
                                    </span>
                                </td>
                                <td style="font-size:12px"><?= Html::encode($p['dept_name'] ?? 'الكل') ?></td>
                                <td style="text-align:center;font-size:12px"><?= $genderMap[$p['gender']] ?? $p['gender'] ?></td>
                                <td style="text-align:center">
                                    <?php if ($p['status'] === 'active'): ?>
                                        <span class="leave-status" style="background:#eafaf1;color:#27ae60;font-size:11px"><i class="fa fa-check-circle"></i> فعّال</span>
                                    <?php else: ?>
                                        <span class="leave-status" style="background:#ecf0f1;color:#95a5a6;font-size:11px"><i class="fa fa-ban"></i> معطّل</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;white-space:nowrap">
                                    <button class="leave-action leave-action--edit" title="تعديل"
                                            onclick="editPolicy(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button class="leave-action leave-action--delete" title="تعطيل"
                                            onclick="if(confirm('هل أنت متأكد من تعطيل هذه السياسة؟')) leaveAction('delete-policy', <?= $p['id'] ?>)">
                                        <i class="fa fa-power-off"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 4: جدول أيام العمل والورديات
         ═══════════════════════════════════════ -->
    <div class="leave-tab-panel <?= $tab === 'schedule' ? 'active' : '' ?>" id="panel-schedule">

        <!-- أيام العمل -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h4 style="margin:0;font-weight:700;color:var(--hr-text,#2c3e50)">
                <i class="fa fa-calendar" style="color:var(--hr-primary,#800020)"></i> جدول أيام العمل
            </h4>
            <button class="hr-btn hr-btn--primary" id="saveWorkdaysBtn" onclick="saveWorkdays()">
                <i class="fa fa-save"></i> حفظ التغييرات
            </button>
        </div>

        <div class="leave-card" style="margin-bottom:28px">
            <div style="padding:6px 16px 4px;background:#f8f9fa;border-bottom:1px solid #eee">
                <div class="wd-row" style="font-weight:700;font-size:11px;color:#999;border-bottom:none;padding:6px 0">
                    <span>اليوم</span>
                    <span>بداية الدوام</span>
                    <span>نهاية الدوام</span>
                    <span>الحالة</span>
                </div>
            </div>
            <form id="workdaysForm">
                <?php foreach ($workdays as $wd): ?>
                    <?php $isDayOff = $wd['status'] === 'day_off'; ?>
                    <div class="wd-row <?= $isDayOff ? 'wd-day-off' : '' ?>" data-id="<?= $wd['id'] ?>">
                        <label>
                            <i class="fa fa-<?= $isDayOff ? 'moon-o' : 'sun-o' ?>"
                               style="color:<?= $isDayOff ? '#95a5a6' : 'var(--hr-accent,#d4a84b)' ?>;margin-left:6px"></i>
                            <?= $dayMap[$wd['day_name']] ?? $wd['day_name'] ?>
                        </label>
                        <input type="time" name="days[<?= $wd['id'] ?>][start_at]" value="<?= $wd['start_at'] ?>" <?= $isDayOff ? 'disabled' : '' ?>>
                        <input type="time" name="days[<?= $wd['id'] ?>][end_at]" value="<?= $wd['end_at'] ?>" <?= $isDayOff ? 'disabled' : '' ?>>
                        <select name="days[<?= $wd['id'] ?>][status]" onchange="toggleDayOff(this)">
                            <option value="working_day" <?= !$isDayOff ? 'selected' : '' ?>>يوم عمل</option>
                            <option value="day_off" <?= $isDayOff ? 'selected' : '' ?>>يوم عطلة</option>
                        </select>
                        <input type="hidden" name="days[<?= $wd['id'] ?>][id]" value="<?= $wd['id'] ?>">
                    </div>
                <?php endforeach; ?>
            </form>
        </div>

        <!-- الورديات -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h4 style="margin:0;font-weight:700;color:var(--hr-text,#2c3e50)">
                <i class="fa fa-clock-o" style="color:var(--hr-primary,#800020)"></i> ورديات العمل
            </h4>
            <button class="hr-btn hr-btn--primary" onclick="openModal('shiftModal')">
                <i class="fa fa-plus"></i> إضافة وردية
            </button>
        </div>

        <div class="leave-card">
            <?php if (empty($shifts)): ?>
                <div class="leave-empty">
                    <i class="fa fa-clock-o"></i>
                    <p>لا توجد ورديات مسجلة</p>
                </div>
            <?php else: ?>
                <div style="padding:12px">
                    <?php foreach ($shifts as $s): ?>
                        <div class="shift-card">
                            <div class="shift-card__info">
                                <i class="fa fa-clock-o" style="color:var(--hr-primary,#800020);font-size:18px"></i>
                                <div>
                                    <div class="shift-card__title"><?= Html::encode($s['title']) ?></div>
                                    <div class="shift-card__time"><?= $s['start_at'] ?> — <?= $s['end_at'] ?></div>
                                </div>
                            </div>
                            <div style="display:flex;gap:4px">
                                <button class="leave-action leave-action--edit" title="تعديل"
                                        onclick="editShift(<?= $s['id'] ?>, '<?= addslashes($s['title']) ?>', '<?= $s['start_at'] ?>', '<?= $s['end_at'] ?>')">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button class="leave-action leave-action--delete" title="حذف"
                                        onclick="if(confirm('هل أنت متأكد من حذف هذه الوردية؟')) leaveAction('delete-shift', <?= $s['id'] ?>)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.hr-page -->

<!-- ═══════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════ -->

<!-- Modal: طلب إجازة -->
<div class="leave-modal-overlay" id="requestModal">
    <div class="leave-modal-box">
        <div class="leave-modal-header">
            <h3><i class="fa fa-envelope-open" style="color:var(--hr-primary,#800020)"></i> طلب إجازة جديد</h3>
            <button class="leave-modal-close" onclick="closeModal('requestModal')">&times;</button>
        </div>
        <div class="leave-modal-body">
            <div class="leave-field">
                <label>سياسة الإجازة *</label>
                <select id="req_policy">
                    <option value="">— اختر سياسة الإجازة —</option>
                    <?php foreach ($leavePolicies as $pid => $plabel): ?>
                        <option value="<?= $pid ?>"><?= Html::encode($plabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>تاريخ البداية *</label>
                    <input type="date" id="req_start">
                </div>
                <div class="leave-field">
                    <label>تاريخ النهاية *</label>
                    <input type="date" id="req_end">
                </div>
            </div>
            <div class="leave-field">
                <label>السبب</label>
                <textarea id="req_reason" placeholder="سبب الإجازة (اختياري)"></textarea>
            </div>
        </div>
        <div class="leave-modal-footer">
            <button class="hr-btn hr-btn--primary" onclick="submitRequest()">
                <i class="fa fa-paper-plane"></i> تقديم الطلب
            </button>
            <button class="hr-btn" style="background:#eee;color:#333" onclick="closeModal('requestModal')">إلغاء</button>
        </div>
    </div>
</div>

<!-- Modal: عطلة رسمية -->
<div class="leave-modal-overlay" id="holidayModal">
    <div class="leave-modal-box">
        <div class="leave-modal-header">
            <h3 id="holidayModalTitle"><i class="fa fa-calendar" style="color:var(--hr-accent,#d4a84b)"></i> إضافة عطلة رسمية</h3>
            <button class="leave-modal-close" onclick="closeModal('holidayModal')">&times;</button>
        </div>
        <div class="leave-modal-body">
            <input type="hidden" id="hol_id" value="">
            <div class="leave-field">
                <label>اسم العطلة *</label>
                <input type="text" id="hol_title" placeholder="مثال: عيد الأضحى">
            </div>
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>تاريخ البداية *</label>
                    <input type="date" id="hol_start">
                </div>
                <div class="leave-field">
                    <label>تاريخ النهاية *</label>
                    <input type="date" id="hol_end">
                </div>
            </div>
        </div>
        <div class="leave-modal-footer">
            <button class="hr-btn hr-btn--primary" onclick="submitHoliday()">
                <i class="fa fa-save"></i> حفظ
            </button>
            <button class="hr-btn" style="background:#eee;color:#333" onclick="closeModal('holidayModal')">إلغاء</button>
        </div>
    </div>
</div>

<!-- Modal: نوع إجازة -->
<div class="leave-modal-overlay" id="typeModal">
    <div class="leave-modal-box">
        <div class="leave-modal-header">
            <h3 id="typeModalTitle"><i class="fa fa-tags" style="color:var(--hr-primary,#800020)"></i> نوع إجازة جديد</h3>
            <button class="leave-modal-close" onclick="closeModal('typeModal')">&times;</button>
        </div>
        <div class="leave-modal-body">
            <input type="hidden" id="type_id" value="">
            <div class="leave-field">
                <label>اسم النوع *</label>
                <input type="text" id="type_title" placeholder="مثال: إجازة سنوية">
            </div>
            <div class="leave-field">
                <label>الوصف</label>
                <textarea id="type_desc" placeholder="وصف اختياري"></textarea>
            </div>
            <div class="leave-field" id="type_status_field" style="display:none">
                <label>الحالة</label>
                <select id="type_status">
                    <option value="active">فعّال</option>
                    <option value="unActive">معطّل</option>
                </select>
            </div>
        </div>
        <div class="leave-modal-footer">
            <button class="hr-btn hr-btn--primary" onclick="submitType()">
                <i class="fa fa-save"></i> حفظ
            </button>
            <button class="hr-btn" style="background:#eee;color:#333" onclick="closeModal('typeModal')">إلغاء</button>
        </div>
    </div>
</div>

<!-- Modal: سياسة إجازة -->
<div class="leave-modal-overlay" id="policyModal">
    <div class="leave-modal-box">
        <div class="leave-modal-header">
            <h3 id="policyModalTitle"><i class="fa fa-file-text-o" style="color:var(--hr-primary,#800020)"></i> سياسة إجازة جديدة</h3>
            <button class="leave-modal-close" onclick="closeModal('policyModal')">&times;</button>
        </div>
        <div class="leave-modal-body">
            <input type="hidden" id="pol_id" value="">
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>عنوان السياسة *</label>
                    <input type="text" id="pol_title" placeholder="مثال: إجازة سنوية 2026">
                </div>
                <div class="leave-field">
                    <label>نوع الإجازة *</label>
                    <select id="pol_type">
                        <option value="">— اختر —</option>
                        <?php foreach ($leaveTypes as $tid => $tname): ?>
                            <option value="<?= $tid ?>"><?= Html::encode($tname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>السنة *</label>
                    <input type="number" id="pol_year" value="<?= date('Y') ?>" min="2020" max="2040">
                </div>
                <div class="leave-field">
                    <label>إجمالي الأيام *</label>
                    <input type="number" id="pol_days" placeholder="14" min="1">
                </div>
            </div>
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>القسم</label>
                    <select id="pol_dept">
                        <option value="0">جميع الأقسام</option>
                        <?php foreach ($departments as $did => $dname): ?>
                            <option value="<?= $did ?>"><?= Html::encode($dname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="leave-field">
                    <label>المسمى الوظيفي</label>
                    <select id="pol_desig">
                        <option value="0">جميع المسميات</option>
                        <?php foreach ($designations as $did => $dname): ?>
                            <option value="<?= $did ?>"><?= Html::encode($dname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>الجنس</label>
                    <select id="pol_gender">
                        <option value="all">الكل</option>
                        <option value="Male">ذكر</option>
                        <option value="Female">أنثى</option>
                    </select>
                </div>
                <div class="leave-field">
                    <label>الحالة الاجتماعية</label>
                    <select id="pol_marital">
                        <option value="all">الكل</option>
                        <option value="single">أعزب</option>
                        <option value="married">متزوج</option>
                    </select>
                </div>
            </div>
            <div class="leave-field" id="pol_status_field" style="display:none">
                <label>الحالة</label>
                <select id="pol_status">
                    <option value="active">فعّال</option>
                    <option value="unActive">معطّل</option>
                </select>
            </div>
        </div>
        <div class="leave-modal-footer">
            <button class="hr-btn hr-btn--primary" onclick="submitPolicy()">
                <i class="fa fa-save"></i> حفظ
            </button>
            <button class="hr-btn" style="background:#eee;color:#333" onclick="closeModal('policyModal')">إلغاء</button>
        </div>
    </div>
</div>

<!-- Modal: وردية عمل -->
<div class="leave-modal-overlay" id="shiftModal">
    <div class="leave-modal-box">
        <div class="leave-modal-header">
            <h3 id="shiftModalTitle"><i class="fa fa-clock-o" style="color:var(--hr-primary,#800020)"></i> إضافة وردية</h3>
            <button class="leave-modal-close" onclick="closeModal('shiftModal')">&times;</button>
        </div>
        <div class="leave-modal-body">
            <input type="hidden" id="shift_id" value="">
            <div class="leave-field">
                <label>اسم الوردية *</label>
                <input type="text" id="shift_title" placeholder="مثال: الوردية الصباحية">
            </div>
            <div class="leave-field-row">
                <div class="leave-field">
                    <label>وقت البداية *</label>
                    <input type="time" id="shift_start">
                </div>
                <div class="leave-field">
                    <label>وقت النهاية *</label>
                    <input type="time" id="shift_end">
                </div>
            </div>
        </div>
        <div class="leave-modal-footer">
            <button class="hr-btn hr-btn--primary" onclick="submitShift()">
                <i class="fa fa-save"></i> حفظ
            </button>
            <button class="hr-btn" style="background:#eee;color:#333" onclick="closeModal('shiftModal')">إلغاء</button>
        </div>
    </div>
</div>


<?php
$baseUrl = Url::to(['/hr/hr-leave/']);
$js = <<<JS

/* ═══════════════════════════════════════
   Leave Management — JavaScript
   ═══════════════════════════════════════ */

var csrfParam = '{$csrfParam}';
var csrfToken = '{$csrfToken}';
var baseUrl   = '{$baseUrl}';

/* ─── Tab Switching ─── */
document.querySelectorAll('.leave-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.leave-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.leave-tab-panel').forEach(function(p) { p.classList.remove('active'); });
        tab.classList.add('active');
        var panel = document.getElementById('panel-' + tab.dataset.tab);
        if (panel) panel.classList.add('active');
    });
});

/* ─── Modal Helpers ─── */
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// Close modal on overlay click
document.querySelectorAll('.leave-modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.classList.remove('show');
    });
});

/* ─── Toast ─── */
function showToast(msg, type) {
    var t = document.createElement('div');
    var bg = type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#f39c12';
    t.innerHTML = msg;
    t.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;' +
        'padding:12px 24px;border-radius:10px;color:#fff;font-size:14px;font-weight:600;' +
        'background:' + bg + ';box-shadow:0 4px 16px rgba(0,0,0,.2);animation:slideUp .2s ease';
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 3000);
}

/* ─── AJAX Helper ─── */
function leaveAjax(action, id, data, callback) {
    var url = baseUrl + action;
    if (id) url += '?id=' + id;

    var fd = new FormData();
    fd.append(csrfParam, csrfToken);
    if (data) {
        for (var k in data) { if (data.hasOwnProperty(k)) fd.append(k, data[k]); }
    }

    fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success && callback) callback(res);
            else if (res.success) setTimeout(function() { location.reload(); }, 800);
        })
        .catch(function(err) { showToast('حدث خطأ غير متوقع', 'error'); console.error(err); });
}

function leaveAction(action, id) {
    leaveAjax(action, id);
}

/* ─── Submit: Leave Request ─── */
function submitRequest() {
    leaveAjax('create-request', null, {
        leave_policy: document.getElementById('req_policy').value,
        start_at: document.getElementById('req_start').value,
        end_at: document.getElementById('req_end').value,
        reason: document.getElementById('req_reason').value
    }, function() {
        closeModal('requestModal');
    });
}

/* ─── Submit: Holiday ─── */
function submitHoliday() {
    var id = document.getElementById('hol_id').value;
    var action = id ? 'update-holiday' : 'create-holiday';
    leaveAjax(action, id || null, {
        title: document.getElementById('hol_title').value,
        start_at: document.getElementById('hol_start').value,
        end_at: document.getElementById('hol_end').value
    }, function() {
        closeModal('holidayModal');
    });
}
function editHoliday(id, title, start, end) {
    document.getElementById('hol_id').value = id;
    document.getElementById('hol_title').value = title;
    document.getElementById('hol_start').value = start;
    document.getElementById('hol_end').value = end;
    document.getElementById('holidayModalTitle').innerHTML = '<i class="fa fa-calendar" style="color:var(--hr-accent,#d4a84b)"></i> تعديل العطلة';
    openModal('holidayModal');
}

/* ─── Submit: Leave Type ─── */
function submitType() {
    var id = document.getElementById('type_id').value;
    var action = id ? 'update-type' : 'create-type';
    var data = {
        title: document.getElementById('type_title').value,
        description: document.getElementById('type_desc').value
    };
    if (id) data.status = document.getElementById('type_status').value;
    leaveAjax(action, id || null, data, function() {
        closeModal('typeModal');
    });
}
function editType(id, title, desc, status) {
    document.getElementById('type_id').value = id;
    document.getElementById('type_title').value = title;
    document.getElementById('type_desc').value = desc;
    document.getElementById('type_status').value = status;
    document.getElementById('type_status_field').style.display = 'block';
    document.getElementById('typeModalTitle').innerHTML = '<i class="fa fa-tags" style="color:var(--hr-primary,#800020)"></i> تعديل نوع الإجازة';
    openModal('typeModal');
}

/* ─── Submit: Leave Policy ─── */
function submitPolicy() {
    var id = document.getElementById('pol_id').value;
    var action = id ? 'update-policy' : 'create-policy';
    var data = {
        title: document.getElementById('pol_title').value,
        leave_type: document.getElementById('pol_type').value,
        year: document.getElementById('pol_year').value,
        total_days: document.getElementById('pol_days').value,
        department: document.getElementById('pol_dept').value,
        designation: document.getElementById('pol_desig').value,
        gender: document.getElementById('pol_gender').value,
        marital_status: document.getElementById('pol_marital').value
    };
    if (id) data.status = document.getElementById('pol_status').value;
    leaveAjax(action, id || null, data, function() {
        closeModal('policyModal');
    });
}
function editPolicy(p) {
    document.getElementById('pol_id').value = p.id;
    document.getElementById('pol_title').value = p.title;
    document.getElementById('pol_type').value = p.leave_type;
    document.getElementById('pol_year').value = p.year;
    document.getElementById('pol_days').value = p.total_days;
    document.getElementById('pol_dept').value = p.department || 0;
    document.getElementById('pol_desig').value = p.designation || 0;
    document.getElementById('pol_gender').value = p.gender || 'all';
    document.getElementById('pol_marital').value = p.marital_status || 'all';
    if (p.status) {
        document.getElementById('pol_status').value = p.status;
        document.getElementById('pol_status_field').style.display = 'block';
    }
    document.getElementById('policyModalTitle').innerHTML = '<i class="fa fa-file-text-o" style="color:var(--hr-primary,#800020)"></i> تعديل سياسة الإجازة';
    openModal('policyModal');
}

/* ─── Submit: Shift ─── */
function submitShift() {
    var id = document.getElementById('shift_id').value;
    leaveAjax('save-shift', null, {
        id: id,
        title: document.getElementById('shift_title').value,
        start_at: document.getElementById('shift_start').value,
        end_at: document.getElementById('shift_end').value
    }, function() {
        closeModal('shiftModal');
    });
}
function editShift(id, title, start, end) {
    document.getElementById('shift_id').value = id;
    document.getElementById('shift_title').value = title;
    document.getElementById('shift_start').value = start;
    document.getElementById('shift_end').value = end;
    document.getElementById('shiftModalTitle').innerHTML = '<i class="fa fa-clock-o" style="color:var(--hr-primary,#800020)"></i> تعديل الوردية';
    openModal('shiftModal');
}

/* ─── Save Workdays ─── */
function saveWorkdays() {
    var rows = document.querySelectorAll('.wd-row[data-id]');
    var days = [];
    rows.forEach(function(row) {
        var id = row.dataset.id;
        var startInput = row.querySelector('input[type="time"]:first-of-type');
        var endInput = row.querySelector('input[type="time"]:last-of-type');
        var statusSelect = row.querySelector('select');
        days.push({
            id: id,
            start_at: startInput.value,
            end_at: endInput.value,
            status: statusSelect.value
        });
    });

    var fd = new FormData();
    fd.append(csrfParam, csrfToken);
    days.forEach(function(d, i) {
        fd.append('days[' + i + '][id]', d.id);
        fd.append('days[' + i + '][start_at]', d.start_at);
        fd.append('days[' + i + '][end_at]', d.end_at);
        fd.append('days[' + i + '][status]', d.status);
    });

    fetch(baseUrl + 'save-workdays', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) setTimeout(function() { location.reload(); }, 800);
        })
        .catch(function(err) { showToast('حدث خطأ', 'error'); });
}

/* ─── Toggle Day Off ─── */
function toggleDayOff(sel) {
    var row = sel.closest('.wd-row');
    var inputs = row.querySelectorAll('input[type="time"]');
    if (sel.value === 'day_off') {
        row.classList.add('wd-day-off');
        inputs.forEach(function(inp) { inp.disabled = true; });
    } else {
        row.classList.remove('wd-day-off');
        inputs.forEach(function(inp) { inp.disabled = false; });
    }
}

/* ─── Tooltips ─── */
$('[data-toggle="tooltip"]').tooltip({container: 'body', placement: 'top'});

JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
