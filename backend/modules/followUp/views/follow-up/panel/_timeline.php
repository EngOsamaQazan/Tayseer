<?php

use yii\helpers\Html;

/**
 * @var array $timeline
 */

// Event type mappings
$typeMap = [
    'call' => ['label' => 'اتصال', 'icon' => 'fa-phone', 'class' => 'call'],
    'promise' => ['label' => 'وعد دفع', 'icon' => 'fa-handshake-o', 'class' => 'promise'],
    'payment' => ['label' => 'دفعة', 'icon' => 'fa-money', 'class' => 'payment'],
    'legal' => ['label' => 'إجراء قضائي', 'icon' => 'fa-gavel', 'class' => 'legal'],
    'note' => ['label' => 'ملاحظة', 'icon' => 'fa-sticky-note', 'class' => 'note'],
    'visit' => ['label' => 'زيارة', 'icon' => 'fa-car', 'class' => 'visit'],
    'sms' => ['label' => 'رسالة', 'icon' => 'fa-comment', 'class' => 'sms'],
    'escalation' => ['label' => 'تصعيد', 'icon' => 'fa-arrow-up', 'class' => 'escalation'],
];
?>

<div class="ocp-timeline">
    <div class="ocp-timeline__header">
        <div class="ocp-section-title" style="margin-bottom:0">
            <i class="fa fa-clock-o"></i>
            السجل الزمني
        </div>
        <div class="ocp-timeline__filters">
            <button class="ocp-timeline__filter-btn active" data-filter="all" onclick="OCP.filterTimeline('all')">الكل</button>
            <button class="ocp-timeline__filter-btn" data-filter="call" onclick="OCP.filterTimeline('call')">اتصالات</button>
            <button class="ocp-timeline__filter-btn" data-filter="promise" onclick="OCP.filterTimeline('promise')">وعود</button>
            <button class="ocp-timeline__filter-btn" data-filter="payment" onclick="OCP.filterTimeline('payment')">دفعات</button>
            <button class="ocp-timeline__filter-btn" data-filter="legal" onclick="OCP.filterTimeline('legal')">قضائي</button>
        </div>
    </div>

    <div class="ocp-timeline__list" id="ocp-timeline-list">
        <?php if (empty($timeline)): ?>
            <div class="ocp-empty">
                <div class="ocp-empty__icon"><i class="fa fa-inbox"></i></div>
                <div class="ocp-empty__text">لا توجد أحداث مسجلة بعد</div>
            </div>
        <?php else: ?>
            <?php foreach ($timeline as $i => $event): 
                $type = $event['type'] ?? 'note';
                $typeInfo = $typeMap[$type] ?? $typeMap['note'];
                $isPinned = !empty($event['pinned']);
                $hasAttachments = !empty($event['attachments']);
            ?>
            <div class="ocp-timeline-event ocp-timeline-event--<?= $typeInfo['class'] ?>" 
                 data-event-type="<?= $type ?>"
                 data-event-id="<?= $event['id'] ?? '' ?>">
                
                <div class="ocp-timeline-event__dot">
                    <i class="fa <?= $typeInfo['icon'] ?>"></i>
                </div>
                
                <div class="ocp-timeline-event__card <?= $isPinned ? 'ocp-timeline-event__pinned' : '' ?>">
                    <div class="ocp-timeline-event__meta">
                        <span class="ocp-timeline-event__time"><?= Html::encode($event['datetime'] ?? '') ?></span>
                        <?php if (!empty($event['reminder'])): ?>
                            <span style="font-size:11px;color:#E65100;font-weight:600"><i class="fa fa-bell-o"></i> تأجيل: <?= Html::encode($event['reminder']) ?></span>
                        <?php endif; ?>
                        <span class="ocp-timeline-event__type"><?= $typeInfo['label'] ?></span>
                        <?php if ($isPinned): ?>
                            <span style="font-size:11px;color:var(--ocp-primary)"><i class="fa fa-thumb-tack"></i> مثبت</span>
                        <?php endif; ?>
                        <span class="ocp-timeline-event__employee">
                            <i class="fa fa-user-o" style="font-size:10px"></i>
                            <?= Html::encode($event['employee'] ?? '') ?>
                        </span>
                    </div>
                    
                    <div class="ocp-timeline-event__content ocp-timeline-event__content--collapsed" id="event-content-<?= $i ?>">
                        <?= nl2br(Html::encode($event['content'] ?? '')) ?>
                        <?php if (!empty($event['promise_date'])): ?>
                            <br><strong style="color:var(--ocp-event-promise)">موعد الوعد: <?= Html::encode($event['promise_date']) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($event['amount'])): ?>
                            <br><strong style="color:var(--ocp-event-payment)">المبلغ: <?= number_format($event['amount']) ?> د.أ</strong>
                        <?php endif; ?>
                    </div>
                    
                    <button class="ocp-timeline-event__expand" style="display:none" onclick="OCP.toggleEventExpand(<?= $i ?>)">عرض المزيد</button>

                    <?php if ($hasAttachments): ?>
                    <div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap">
                        <?php foreach ($event['attachments'] as $att): ?>
                        <a href="<?= Html::encode($att['url'] ?? '#') ?>" class="ocp-badge ocp-badge--active" style="font-size:11px;text-decoration:none" target="_blank">
                            <i class="fa fa-paperclip"></i> <?= Html::encode($att['name'] ?? 'مرفق') ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
