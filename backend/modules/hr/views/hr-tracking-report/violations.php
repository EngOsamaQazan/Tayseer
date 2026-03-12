<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'تقرير المخالفات والأمان';
?>

<?= $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'reports',
    'tabs'  => [
        ['label' => 'لوحة التحليلات', 'icon' => 'fa-bar-chart',       'url' => ['/hr/hr-tracking-report/index']],
        ['label' => 'التقرير الشهري', 'icon' => 'fa-calendar',         'url' => ['/hr/hr-tracking-report/monthly']],
        ['label' => 'الانضباط',       'icon' => 'fa-star-half-o',      'url' => ['/hr/hr-tracking-report/punctuality']],
        ['label' => 'المخالفات',      'icon' => 'fa-shield',           'url' => ['/hr/hr-tracking-report/violations']],
    ],
]) ?>

<style>
.rp-page{padding:20px}
.rp-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.rp-header h1{font-size:22px;font-weight:700;color:var(--clr-primary,#800020);margin:0}
.rp-filter{background:#fff;border-radius:10px;padding:14px 20px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.rp-filter .fg{display:flex;flex-direction:column;gap:4px}
.rp-filter label{font-size:11px;font-weight:600;color:#64748b}
.rp-filter input{padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.rp-filter .btn-f{padding:7px 16px;background:var(--clr-primary,#800020);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer}

.v-section{margin-bottom:28px}
.v-section h2{font-size:17px;font-weight:700;color:#1e293b;margin:0 0 14px;display:flex;align-items:center;gap:8px}
.v-section h2 i{color:var(--clr-primary,#800020)}
.v-section h2 .count{background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:10px;font-size:12px}

.v-alert{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px}
.v-alert i{font-size:24px;color:#dc2626}
.v-alert .text{flex:1}
.v-alert .text h4{font-size:14px;font-weight:700;color:#991b1b;margin:0}
.v-alert .text p{font-size:12px;color:#b91c1c;margin:4px 0 0}

.v-card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.v-card{background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.04);border-right:3px solid #dc2626}
.v-card .name{font-size:14px;font-weight:700;color:#1e293b}
.v-card .detail{font-size:12px;color:#64748b;margin-top:4px;display:flex;gap:12px}
.v-card .big-num{font-size:28px;font-weight:800;color:#dc2626;margin-top:6px}

.v-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.v-table th{padding:10px 14px;font-size:11px;font-weight:600;color:#64748b;background:#f8fafc;text-align:right;border-bottom:1px solid #e2e8f0}
.v-table td{padding:10px 14px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9}
.v-table tr:hover td{background:#fef2f2}

.v-mock-badge{background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700}
.v-exit-badge{background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700}

.empty-state{text-align:center;padding:40px;color:#94a3b8}
.empty-state i{font-size:48px;display:block;margin-bottom:12px}
.empty-state.good{color:#16a34a}
.empty-state.good i{color:#16a34a}
</style>

<div class="rp-page">
    <div class="rp-header">
        <h1><i class="fa fa-shield"></i> <?= $this->title ?></h1>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-right"></i> لوحة التحكم</a>
    </div>

    <form method="get" class="rp-filter">
        <div class="fg">
            <label>من تاريخ</label>
            <input type="date" name="from" value="<?= Html::encode($dateFrom) ?>">
        </div>
        <div class="fg">
            <label>إلى تاريخ</label>
            <input type="date" name="to" value="<?= Html::encode($dateTo) ?>">
        </div>
        <button type="submit" class="btn-f"><i class="fa fa-filter"></i> عرض</button>
    </form>

    <!-- ═══ Mock Location Section ═══ -->
    <div class="v-section">
        <h2>
            <i class="fa fa-warning"></i>
            كشف المواقع المُزيّفة (Mock Location)
            <?php if (!empty($mockSummary)): ?>
                <span class="count"><?= count($mockLogs) ?> حالة</span>
            <?php endif; ?>
        </h2>

        <?php if (!empty($mockSummary)): ?>
            <div class="v-alert">
                <i class="fa fa-exclamation-triangle"></i>
                <div class="text">
                    <h4>تنبيه أمني!</h4>
                    <p>تم رصد <?= count($mockLogs) ?> محاولة استخدام موقع وهمي من <?= count($mockSummary) ?> موظف في الفترة المحددة.</p>
                </div>
            </div>

            <div class="v-card-grid" style="margin-bottom:16px">
                <?php foreach ($mockSummary as $ms): ?>
                <div class="v-card">
                    <div class="name"><?= Html::encode($ms['name']) ?></div>
                    <div class="big-num"><?= $ms['cnt'] ?> <span style="font-size:14px;color:#64748b;font-weight:400">مرة</span></div>
                </div>
                <?php endforeach; ?>
            </div>

            <table class="v-table">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mockLogs as $ml): ?>
                    <tr>
                        <td><strong><?= Html::encode($ml['name']) ?></strong></td>
                        <td><?= $ml['attendance_date'] ?></td>
                        <td><span class="v-mock-badge"><i class="fa fa-warning"></i> موقع مُزيّف</span></td>
                        <td style="font-size:12px;color:#64748b"><?= Html::encode($ml['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state good">
                <i class="fa fa-check-circle"></i>
                <h3>لا توجد مخالفات</h3>
                <p>لم يتم رصد أي استخدام للمواقع الوهمية في الفترة المحددة</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══ Zone Exit Events ═══ -->
    <div class="v-section">
        <h2>
            <i class="fa fa-sign-out"></i>
            أحداث الخروج من مناطق العمل
            <?php if (!empty($outsideZoneEvents)): ?>
                <span class="count"><?= array_sum(array_column($outsideZoneEvents, 'exit_count')) ?> حدث</span>
            <?php endif; ?>
        </h2>

        <?php if (!empty($outsideZoneEvents)): ?>
            <div class="v-card-grid">
                <?php foreach ($outsideZoneEvents as $oz): ?>
                <div class="v-card" style="border-right-color:#f59e0b">
                    <div class="name"><?= Html::encode($oz['name']) ?></div>
                    <div class="detail">
                        <span><i class="fa fa-sign-out"></i> <?= $oz['exit_count'] ?> مرة خروج</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state good">
                <i class="fa fa-check-circle"></i>
                <h3>لا توجد أحداث خروج</h3>
                <p>جميع الموظفين التزموا بمناطق العمل المحددة</p>
            </div>
        <?php endif; ?>
    </div>
</div>
