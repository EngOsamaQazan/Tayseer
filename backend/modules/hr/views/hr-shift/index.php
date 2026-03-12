<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'إدارة الورديات';

$dayNames = [
    0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
    4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
];
?>

<?= $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'tracking',
    'tabs'  => [
        ['label' => 'سجل الحضور',    'icon' => 'fa-calendar-check-o', 'url' => ['/hr/hr-tracking-api/attendance-board']],
        ['label' => 'التتبع المباشر', 'icon' => 'fa-crosshairs',       'url' => ['/hr/hr-tracking-api/live-map']],
        ['label' => 'الورديات',       'icon' => 'fa-clock-o',          'url' => ['/hr/hr-shift/index']],
        ['label' => 'مناطق العمل',    'icon' => 'fa-map-pin',          'url' => ['/hr/hr-work-zone/index']],
    ],
]) ?>

<style>
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.shift-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}
.shift-card {
    background: #fff; border-radius: 12px; padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #f0f0f0;
    transition: all .2s;
}
.shift-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); transform: translateY(-2px); }
.shift-card.inactive { opacity: .6; background: #fafafa; }
.shift-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.shift-title { font-size: 17px; font-weight: 700; color: #1e293b; }
.shift-badge {
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.shift-badge.active { background: #dcfce7; color: #166534; }
.shift-badge.disabled { background: #fee2e2; color: #991b1b; }
.shift-time-row {
    display: flex; gap: 16px; margin-bottom: 14px;
}
.shift-time {
    flex: 1; background: #f8fafc; border-radius: 8px; padding: 10px 14px; text-align: center;
}
.shift-time .label { font-size: 11px; color: #94a3b8; margin-bottom: 2px; }
.shift-time .value { font-size: 18px; font-weight: 700; color: #334155; direction: ltr; }
.shift-meta {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; font-size: 13px;
}
.shift-meta .item { display: flex; gap: 6px; align-items: center; color: #64748b; }
.shift-meta .item i { width: 16px; text-align: center; color: #94a3b8; }
.shift-days { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 14px; }
.shift-day {
    width: 34px; height: 28px; border-radius: 6px; display: flex;
    align-items: center; justify-content: center;
    font-size: 10px; font-weight: 600;
}
.shift-day.on  { background: var(--clr-primary, #800020); color: #fff; }
.shift-day.off { background: #f1f5f9; color: #94a3b8; }
.shift-actions { display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid #f0f0f0; padding-top: 12px; }
.shift-actions a, .shift-actions button {
    padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer; transition: all .2s;
}
.btn-s-edit { background: #eff6ff; color: #1d4ed8; }
.btn-s-edit:hover { background: #dbeafe; }
.btn-s-toggle-on { background: #fef2f2; color: #dc2626; }
.btn-s-toggle-off { background: #f0fdf4; color: #16a34a; }
.btn-s-toggle-on:hover { background: #fee2e2; }
.btn-s-toggle-off:hover { background: #dcfce7; }
.empty-state {
    text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; }
.empty-state h3 { font-size: 18px; color: #475569; margin-bottom: 8px; }
.empty-state p { color: #94a3b8; margin-bottom: 20px; }
</style>

<div class="hr-page">
    <div class="hr-page-header">
        <h1><i class="fa fa-clock-o"></i> <?= $this->title ?></h1>
        <a href="<?= Url::to(['create']) ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> إضافة وردية
        </a>
    </div>

    <?php if ($dataProvider->getTotalCount() === 0): ?>
        <div class="empty-state">
            <i class="fa fa-clock-o"></i>
            <h3>لا توجد ورديات</h3>
            <p>أضف أول وردية لبدء إدارة ساعات العمل</p>
            <a href="<?= Url::to(['create']) ?>" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> إضافة وردية
            </a>
        </div>
    <?php else: ?>
        <div class="shift-grid">
            <?php foreach ($dataProvider->getModels() as $shift): ?>
                <div class="shift-card <?= $shift->is_active ? '' : 'inactive' ?>">
                    <div class="shift-head">
                        <span class="shift-title"><?= Html::encode($shift->title) ?></span>
                        <span class="shift-badge <?= $shift->is_active ? 'active' : 'disabled' ?>">
                            <?= $shift->is_active ? 'فعّال' : 'معطّل' ?>
                        </span>
                    </div>

                    <div class="shift-time-row">
                        <div class="shift-time">
                            <div class="label">بداية الدوام</div>
                            <div class="value"><?= date('h:i A', strtotime($shift->start_at)) ?></div>
                        </div>
                        <div class="shift-time">
                            <div class="label">نهاية الدوام</div>
                            <div class="value"><?= date('h:i A', strtotime($shift->end_at)) ?></div>
                        </div>
                    </div>

                    <div class="shift-meta">
                        <div class="item">
                            <i class="fa fa-hourglass-half"></i>
                            سماح: <?= $shift->grace_minutes ?> د
                        </div>
                        <div class="item">
                            <i class="fa fa-sign-out"></i>
                            خروج مبكر: <?= $shift->early_leave_minutes ?> د
                        </div>
                        <div class="item">
                            <i class="fa fa-plus-circle"></i>
                            إضافي بعد: <?= $shift->overtime_after_minutes ?> د
                        </div>
                        <div class="item">
                            <i class="fa fa-coffee"></i>
                            استراحة: <?= $shift->break_duration_minutes ?> د
                        </div>
                        <?php if ($shift->is_flexible): ?>
                            <div class="item" style="grid-column:1/-1">
                                <i class="fa fa-random"></i>
                                وردية مرنة — نافذة <?= $shift->flex_window_minutes ?> دقيقة
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php
                    $days = is_array($shift->working_days) ? $shift->working_days : [];
                    if (!empty($days)):
                    ?>
                    <div class="shift-days">
                        <?php foreach ($dayNames as $num => $name): ?>
                            <div class="shift-day <?= in_array($num, $days) ? 'on' : 'off' ?>"
                                 title="<?= $name ?>">
                                <?= mb_substr($name, 0, 1) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="shift-actions">
                        <a href="<?= Url::to(['update', 'id' => $shift->id]) ?>" class="btn-s-edit">
                            <i class="fa fa-pencil"></i> تعديل
                        </a>
                        <?= Html::a(
                            $shift->is_active
                                ? '<i class="fa fa-ban"></i> تعطيل'
                                : '<i class="fa fa-check"></i> تفعيل',
                            ['toggle-active', 'id' => $shift->id],
                            [
                                'class' => $shift->is_active ? 'btn-s-toggle-on' : 'btn-s-toggle-off',
                                'data-method' => 'post',
                                'data-confirm' => $shift->is_active ? 'تعطيل هذه الوردية؟' : 'تفعيل هذه الوردية؟',
                            ]
                        ) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:20px; display:flex; justify-content:center;">
            <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
        </div>
    <?php endif; ?>
</div>
