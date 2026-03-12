<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'تقرير الانضباط الوظيفي';
$employeeTypes = ['office' => 'مكتبي', 'field' => 'ميداني', 'sales' => 'مبيعات', 'hybrid' => 'مختلط'];
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

.punct-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.punct-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.04);border-right:4px solid #e2e8f0;transition:all .2s}
.punct-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.punct-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.punct-name{font-size:15px;font-weight:700;color:#1e293b}
.punct-score-circle{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff}
.punct-meta{display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:12px}
.punct-meta .item{display:flex;align-items:center;gap:6px;color:#64748b;padding:4px 0}
.punct-meta .item .val{font-weight:700;color:#1e293b}
.punct-meta .item i{width:14px;text-align:center;color:#94a3b8}
.punct-bar{margin-top:12px;height:8px;border-radius:4px;background:#f1f5f9;overflow:hidden}
.punct-fill{height:100%;border-radius:4px;transition:width .6s}
.punct-tags{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.punct-tag{padding:2px 8px;border-radius:6px;font-size:10px;font-weight:600}
.empty-state{text-align:center;padding:60px 20px;color:#94a3b8}
.empty-state i{font-size:48px;display:block;margin-bottom:12px}
</style>

<div class="rp-page">
    <div class="rp-header">
        <h1><i class="fa fa-trophy"></i> <?= $this->title ?></h1>
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

    <?php if (empty($data)): ?>
        <div class="empty-state">
            <i class="fa fa-bar-chart"></i>
            <h3>لا توجد بيانات</h3>
            <p>لا توجد سجلات حضور في الفترة المحددة</p>
        </div>
    <?php else: ?>
        <div class="punct-grid">
            <?php foreach ($data as $row):
                $score = (int)($row['punctuality_score'] ?? 0);
                $scoreColor = $score >= 90 ? '#16a34a' : ($score >= 70 ? '#f59e0b' : '#dc2626');
                $borderColor = $score >= 90 ? '#16a34a' : ($score >= 70 ? '#f59e0b' : '#dc2626');
                $avgWorkMin = (int)($row['avg_work_min'] ?? 0);
                $avgWorkStr = $avgWorkMin > 0 ? floor($avgWorkMin/60).':'.str_pad($avgWorkMin%60,2,'0',STR_PAD_LEFT) : '—';
            ?>
            <div class="punct-card" style="border-right-color:<?= $borderColor ?>">
                <div class="punct-head">
                    <div>
                        <div class="punct-name"><?= Html::encode($row['name']) ?></div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                            <?= $employeeTypes[$row['employee_type']] ?? '—' ?>
                            <?= $row['shift_name'] ? ' — ' . Html::encode($row['shift_name']) : '' ?>
                        </div>
                    </div>
                    <div class="punct-score-circle" style="background:<?= $scoreColor ?>">
                        <?= $score ?>%
                    </div>
                </div>

                <div class="punct-bar">
                    <div class="punct-fill" style="width:<?= $score ?>%;background:<?= $scoreColor ?>"></div>
                </div>

                <div class="punct-meta" style="margin-top:12px">
                    <div class="item">
                        <i class="fa fa-check" style="color:#16a34a"></i>
                        في الموعد: <span class="val"><?= (int)$row['on_time_days'] ?></span> يوم
                    </div>
                    <div class="item">
                        <i class="fa fa-clock-o" style="color:#f59e0b"></i>
                        تأخير: <span class="val"><?= (int)$row['late_days'] ?></span> يوم
                    </div>
                    <div class="item">
                        <i class="fa fa-times" style="color:#dc2626"></i>
                        غياب: <span class="val"><?= (int)$row['absent_days'] ?></span> يوم
                    </div>
                    <div class="item">
                        <i class="fa fa-sign-out" style="color:#8b5cf6"></i>
                        خروج مبكر: <span class="val"><?= (int)$row['early_leave_cnt'] ?></span>
                    </div>
                    <div class="item">
                        <i class="fa fa-hourglass-half"></i>
                        متوسط تأخير: <span class="val"><?= (int)($row['avg_late'] ?? 0) ?> د</span>
                    </div>
                    <div class="item">
                        <i class="fa fa-briefcase"></i>
                        متوسط العمل: <span class="val"><?= $avgWorkStr ?></span>
                    </div>
                </div>

                <div class="punct-tags">
                    <?php if ($score >= 95): ?>
                        <span class="punct-tag" style="background:#dcfce7;color:#166534">ممتاز</span>
                    <?php elseif ($score >= 85): ?>
                        <span class="punct-tag" style="background:#dbeafe;color:#1e40af">جيد جداً</span>
                    <?php elseif ($score >= 70): ?>
                        <span class="punct-tag" style="background:#fef3c7;color:#92400e">يحتاج تحسين</span>
                    <?php else: ?>
                        <span class="punct-tag" style="background:#fee2e2;color:#991b1b">ضعيف</span>
                    <?php endif; ?>
                    <?php if ((int)($row['total_late_min'] ?? 0) > 120): ?>
                        <span class="punct-tag" style="background:#fee2e2;color:#991b1b">تأخير مفرط</span>
                    <?php endif; ?>
                    <?php if ($row['avg_clock_in']): ?>
                        <span class="punct-tag" style="background:#f1f5f9;color:#475569">
                            متوسط الوصول: <?= date('h:i A', strtotime($row['avg_clock_in'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
