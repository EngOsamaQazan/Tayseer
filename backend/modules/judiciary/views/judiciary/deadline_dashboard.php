<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\models\JudiciaryDeadline;

$this->title = 'لوحة المواعيد النهائية';
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/judiciary-v2.css?v=' . time());

$typeLabels = JudiciaryDeadline::getTypeLabels();
$statusLabels = JudiciaryDeadline::getStatusLabels();

$sections = [
    [
        'title' => 'مواعيد متأخرة',
        'icon' => 'fa-exclamation-circle',
        'items' => $expired,
        'color' => '#DC2626',
        'bg' => '#FEF2F2',
        'border' => '#FECACA',
        'emptyText' => 'لا توجد مواعيد متأخرة',
    ],
    [
        'title' => 'مواعيد تقترب',
        'icon' => 'fa-warning',
        'items' => $approaching,
        'color' => '#D97706',
        'bg' => '#FFFBEB',
        'border' => '#FDE68A',
        'emptyText' => 'لا توجد مواعيد قريبة',
    ],
    [
        'title' => 'مواعيد قائمة',
        'icon' => 'fa-hourglass-half',
        'items' => $pending,
        'color' => '#64748B',
        'bg' => '#F8FAFC',
        'border' => '#E2E8F0',
        'emptyText' => 'لا توجد مواعيد قائمة',
    ],
];
?>

<div class="jv-page">
    <div class="jv-header">
        <div>
            <div class="jv-title">
                <i class="fa fa-clock-o" style="color:#DC2626"></i>
                <?= $this->title ?>
            </div>
        </div>
        <div class="jv-actions" style="display:flex;gap:8px">
            <?= Html::a('<i class="fa fa-refresh"></i> تحديث البيانات', ['deadline-dashboard-view', 'refresh' => 1], ['class' => 'btn btn-default', 'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px']) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px']) ?>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px">
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px">
            <div style="width:42px;height:42px;border-radius:10px;background:#DC2626;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fa fa-exclamation-circle"></i></div>
            <div>
                <div style="font-size:22px;font-weight:700;color:#DC2626"><?= count($expired) ?></div>
                <div style="font-size:12px;color:#991B1B">متأخرة</div>
            </div>
        </div>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px">
            <div style="width:42px;height:42px;border-radius:10px;background:#D97706;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fa fa-warning"></i></div>
            <div>
                <div style="font-size:22px;font-weight:700;color:#D97706"><?= count($approaching) ?></div>
                <div style="font-size:12px;color:#92400E">تقترب</div>
            </div>
        </div>
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px">
            <div style="width:42px;height:42px;border-radius:10px;background:#64748B;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fa fa-hourglass-half"></i></div>
            <div>
                <div style="font-size:22px;font-weight:700;color:#64748B"><?= count($pending) ?></div>
                <div style="font-size:12px;color:#64748B">قائمة</div>
            </div>
        </div>
    </div>

    <?php foreach ($sections as $sec): ?>
    <div class="jv-card" style="margin-bottom:20px">
        <div class="jv-card-title">
            <i class="fa <?= $sec['icon'] ?>" style="color:<?= $sec['color'] ?>"></i>
            <?= $sec['title'] ?>
            <span style="background:<?= $sec['bg'] ?>;color:<?= $sec['color'] ?>;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;margin-right:8px"><?= count($sec['items']) ?></span>
        </div>
        <?php if (empty($sec['items'])): ?>
            <div style="text-align:center;padding:30px;color:#94A3B8">
                <i class="fa fa-check-circle" style="font-size:24px;display:block;margin-bottom:8px;color:#D1FAE5"></i>
                <?= $sec['emptyText'] ?>
            </div>
        <?php else: ?>
            <div class="jv-deadline-grid">
                <?php foreach ($sec['items'] as $dl):
                    $typeLabel = $typeLabels[$dl->deadline_type] ?? $dl->deadline_type;
                    $daysRemaining = $dl->deadline_date ? (int)((strtotime($dl->deadline_date) - time()) / 86400) : null;
                    $caseNum = $dl->judiciary ? ($dl->judiciary->judiciary_number ?: '#' . $dl->judiciary_id) : '#' . $dl->judiciary_id;
                ?>
                <div class="jv-deadline-card" style="background:<?= $sec['bg'] ?>;border:1px solid <?= $sec['border'] ?>">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:6px">
                            <i class="fa <?= $sec['icon'] ?>" style="color:<?= $sec['color'] ?>;font-size:14px"></i>
                            <span style="font-weight:700;font-size:12px;color:<?= $sec['color'] ?>"><?= Html::encode($typeLabel) ?></span>
                        </div>
                        <?= Html::a('قضية ' . Html::encode($caseNum), ['view', 'id' => $dl->judiciary_id], [
                            'style' => 'font-size:11px;font-weight:600;color:#2563EB;text-decoration:none',
                        ]) ?>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px">
                        <span style="color:#64748B"><i class="fa fa-calendar"></i> <?= Html::encode($dl->deadline_date ?: '—') ?></span>
                        <?php if ($daysRemaining !== null): ?>
                            <span style="font-weight:700;color:<?= $sec['color'] ?>">
                                <?php if ($daysRemaining < 0): ?>
                                    متأخر <?= abs($daysRemaining) ?> يوم
                                <?php elseif ($daysRemaining === 0): ?>
                                    اليوم!
                                <?php else: ?>
                                    باقي <?= $daysRemaining ?> يوم
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($dl->label)): ?>
                        <div style="font-size:11px;color:#475569;margin-top:6px"><?= Html::encode($dl->label) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($dl->notes)): ?>
                        <div style="font-size:10px;color:#94A3B8;margin-top:4px;background:rgba(255,255,255,.6);padding:4px 8px;border-radius:4px"><?= Html::encode($dl->notes) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
