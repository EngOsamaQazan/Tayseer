<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\models\JudiciaryDeadline;

$this->title = 'لوحة المواعيد النهائية';
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/judiciary-v2.css?v=' . time());

$typeLabels = JudiciaryDeadline::getTypeLabels();

$tabs = [
    'expired'     => ['label' => 'متأخرة',  'icon' => 'fa-exclamation-circle', 'color' => '#DC2626', 'bg' => '#FEF2F2', 'border' => '#FECACA'],
    'approaching' => ['label' => 'تقترب',   'icon' => 'fa-warning',            'color' => '#D97706', 'bg' => '#FFFBEB', 'border' => '#FDE68A'],
    'pending'     => ['label' => 'قائمة',   'icon' => 'fa-hourglass-half',     'color' => '#64748B', 'bg' => '#F8FAFC', 'border' => '#E2E8F0'],
];
$active = $tabs[$activeTab];
$startRecord = ($page - 1) * $perPage + 1;
$endRecord   = min($page * $perPage, $counts[$activeTab]);
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
            <?= Html::a('<i class="fa fa-refresh"></i> تحديث', ['deadline-dashboard-view', 'tab' => $activeTab], ['class' => 'btn btn-default', 'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px']) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px;font-size:13px;font-weight:600;padding:8px 18px']) ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px">
        <?php foreach ($tabs as $key => $t): ?>
        <a href="<?= Url::to(['deadline-dashboard-view', 'tab' => $key]) ?>" style="text-decoration:none;background:<?= $t['bg'] ?>;border:1px solid <?= $t['border'] ?>;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px;transition:box-shadow .2s,transform .15s<?= $key === $activeTab ? ';box-shadow:0 4px 12px rgba(0,0,0,.12);transform:translateY(-1px)' : '' ?>"
           onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)';this.style.transform='translateY(-1px)'"
           onmouseout="<?= $key === $activeTab ? '' : "this.style.boxShadow='';this.style.transform=''" ?>">
            <div style="width:42px;height:42px;border-radius:10px;background:<?= $t['color'] ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fa <?= $t['icon'] ?>"></i></div>
            <div>
                <div style="font-size:22px;font-weight:700;color:<?= $t['color'] ?>"><?= number_format($counts[$key]) ?></div>
                <div style="font-size:12px;color:<?= $t['color'] ?>;font-weight:<?= $key === $activeTab ? '700' : '400' ?>"><?= $t['label'] ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Active Tab Content -->
    <div class="jv-card" style="margin-bottom:20px">
        <div class="jv-card-title" style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <i class="fa <?= $active['icon'] ?>" style="color:<?= $active['color'] ?>"></i>
                <?= $active['label'] ?>
                <span style="background:<?= $active['bg'] ?>;color:<?= $active['color'] ?>;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;margin-right:8px"><?= number_format($counts[$activeTab]) ?></span>
            </div>
            <?php if ($counts[$activeTab] > 0): ?>
            <div style="font-size:12px;color:#94A3B8"><?= $startRecord ?> – <?= $endRecord ?> من <?= number_format($counts[$activeTab]) ?></div>
            <?php endif; ?>
        </div>

        <?php if (empty($items)): ?>
            <div style="text-align:center;padding:30px;color:#94A3B8">
                <i class="fa fa-check-circle" style="font-size:24px;display:block;margin-bottom:8px;color:#D1FAE5"></i>
                لا توجد مواعيد <?= $active['label'] ?>
            </div>
        <?php else: ?>
            <div class="jv-deadline-grid">
                <?php foreach ($items as $dl):
                    $typeLabel = $typeLabels[$dl['deadline_type']] ?? $dl['deadline_type'];
                    $daysRemaining = !empty($dl['deadline_date']) ? (int)((strtotime($dl['deadline_date']) - time()) / 86400) : null;
                    $caseNum = !empty($dl['judiciary_number']) ? $dl['judiciary_number'] : '#' . $dl['judiciary_id'];
                ?>
                <div class="jv-deadline-card" style="background:<?= $active['bg'] ?>;border:1px solid <?= $active['border'] ?>">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:6px">
                            <i class="fa <?= $active['icon'] ?>" style="color:<?= $active['color'] ?>;font-size:14px"></i>
                            <span style="font-weight:700;font-size:12px;color:<?= $active['color'] ?>"><?= Html::encode($typeLabel) ?></span>
                        </div>
                        <?= Html::a('قضية ' . Html::encode($caseNum), ['view', 'id' => $dl['judiciary_id']], [
                            'style' => 'font-size:11px;font-weight:600;color:#2563EB;text-decoration:none',
                        ]) ?>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px">
                        <span style="color:#64748B"><i class="fa fa-calendar"></i> <?= Html::encode($dl['deadline_date'] ?: '—') ?></span>
                        <?php if ($daysRemaining !== null): ?>
                            <span style="font-weight:700;color:<?= $active['color'] ?>">
                                <?php if ($daysRemaining < 0): ?>
                                    متأخر <?= number_format(abs($daysRemaining)) ?> يوم
                                <?php elseif ($daysRemaining === 0): ?>
                                    اليوم!
                                <?php else: ?>
                                    باقي <?= number_format($daysRemaining) ?> يوم
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($dl['action_name'])): ?>
                        <div style="font-size:11px;color:#475569;margin-top:6px;display:flex;align-items:center;gap:4px">
                            <i class="fa fa-file-text-o" style="font-size:10px"></i>
                            <span style="font-weight:600"><?= Html::encode($dl['action_name']) ?></span>
                            <?php if (!empty($dl['customer_name'])): ?>
                                <span style="color:#94A3B8">—</span>
                                <span><?= Html::encode($dl['customer_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!empty($dl['label'])): ?>
                        <div style="font-size:11px;color:#475569;margin-top:6px"><?= Html::encode($dl['label']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div style="display:flex;justify-content:center;align-items:center;gap:6px;padding:16px 0;margin-top:12px;border-top:1px solid #E2E8F0">
                <?php if ($page > 1): ?>
                    <?= Html::a('<i class="fa fa-chevron-right"></i>', ['deadline-dashboard-view', 'tab' => $activeTab, 'page' => $page - 1], [
                        'style' => 'width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid #E2E8F0;color:#475569;text-decoration:none;font-size:12px;transition:all .15s',
                        'title' => 'الصفحة السابقة',
                    ]) ?>
                <?php endif; ?>

                <?php
                $range = 2;
                $start = max(1, $page - $range);
                $end   = min($totalPages, $page + $range);
                if ($start > 1) {
                    echo Html::a('1', ['deadline-dashboard-view', 'tab' => $activeTab, 'page' => 1], [
                        'style' => 'width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid #E2E8F0;color:#475569;text-decoration:none;font-size:13px;font-weight:600',
                    ]);
                    if ($start > 2) echo '<span style="color:#94A3B8;font-size:12px">…</span>';
                }
                for ($i = $start; $i <= $end; $i++):
                    $isActive = $i === $page;
                ?>
                    <?= Html::a($i, ['deadline-dashboard-view', 'tab' => $activeTab, 'page' => $i], [
                        'style' => 'width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;'
                            . ($isActive ? 'background:#2563EB;color:#fff;border:1px solid #2563EB' : 'border:1px solid #E2E8F0;color:#475569'),
                    ]) ?>
                <?php endfor;
                if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<span style="color:#94A3B8;font-size:12px">…</span>';
                    echo Html::a($totalPages, ['deadline-dashboard-view', 'tab' => $activeTab, 'page' => $totalPages], [
                        'style' => 'width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid #E2E8F0;color:#475569;text-decoration:none;font-size:13px;font-weight:600',
                    ]);
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <?= Html::a('<i class="fa fa-chevron-left"></i>', ['deadline-dashboard-view', 'tab' => $activeTab, 'page' => $page + 1], [
                        'style' => 'width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid #E2E8F0;color:#475569;text-decoration:none;font-size:12px;transition:all .15s',
                        'title' => 'الصفحة التالية',
                    ]) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
