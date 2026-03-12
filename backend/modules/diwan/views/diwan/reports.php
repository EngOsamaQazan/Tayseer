<?php
/**
 * قسم الديوان — التقارير
 */

use yii\helpers\Html;
use yii\helpers\Url;
use backend\helpers\NameHelper;

$this->title = 'قسم الديوان';

$periodLabels = [
    'today' => 'اليوم',
    'week'  => 'هذا الأسبوع',
    'month' => 'هذا الشهر',
    'all'   => 'الكل',
];
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'reports']) ?>

<style>
.dw-period { display: flex; gap: 4px; background: #f0f0f0; border-radius: 8px; padding: 3px; margin-bottom: 20px; }
.dw-period a {
    padding: 7px 16px; border-radius: 6px; text-decoration: none; font-weight: 600;
    font-size: 12px; color: #555; transition: all .2s;
}
.dw-period a:hover { background: #fff; color: var(--fin-primary, #800020); }
.dw-period a.active { background: var(--fin-primary, #800020); color: #fff; }

.dw-rstats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
.dw-rs {
    background: #fff; border-radius: 8px; padding: 18px; text-align: center;
    box-shadow: 0 1px 6px rgba(0,0,0,.04); border-top: 3px solid var(--fin-primary, #800020);
}
.dw-rs .num { font-size: 28px; font-weight: 800; color: var(--fin-primary, #800020); }
.dw-rs .lbl { font-size: 11px; color: #888; font-weight: 600; }
.dw-rs.green  { border-top-color: #4CAF50; } .dw-rs.green  .num { color: #4CAF50; }
.dw-rs.amber  { border-top-color: #FF9800; } .dw-rs.amber  .num { color: #FF9800; }
.dw-rs.blue   { border-top-color: #2196F3; } .dw-rs.blue   .num { color: #2196F3; }

.dw-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.05); overflow: hidden; margin-bottom: 22px; }
.dw-card .hdr { padding: 14px 18px; font-weight: 700; font-size: 13px; background: #f8f9fa; border-bottom: 1px solid #eee; }
.dw-card table { margin: 0; }
.dw-card table th { font-size: 11px; font-weight: 700; color: #555; background: #fafafa; }
.dw-card table td { font-size: 12px; }

.dw-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dw-badge--recv { background: #e8f5e9; color: #2e7d32; }
.dw-badge--dlvr { background: #fff3e0; color: #e65100; }
</style>

<div class="diwan-reports">

    <!-- فلتر الفترة -->
    <div class="dw-period">
        <?php foreach ($periodLabels as $key => $label): ?>
            <a href="<?= Url::to(['reports', 'period' => $key]) ?>"
               class="<?= $period === $key ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <!-- إحصائيات الفترة -->
    <div class="dw-rstats">
        <div class="dw-rs">
            <div class="num"><?= number_format($stats['total_transactions'] ?? 0) ?></div>
            <div class="lbl">إجمالي المعاملات</div>
        </div>
        <div class="dw-rs green">
            <div class="num"><?= number_format($stats['total_receive'] ?? 0) ?></div>
            <div class="lbl">استلام</div>
        </div>
        <div class="dw-rs amber">
            <div class="num"><?= number_format($stats['total_deliver'] ?? 0) ?></div>
            <div class="lbl">تسليم</div>
        </div>
        <div class="dw-rs blue">
            <div class="num"><?= number_format($totalContracts) ?></div>
            <div class="lbl">عقود مختلفة</div>
        </div>
    </div>

    <!-- إحصائيات الموظفين -->
    <?php if (!empty($employeeStats)): ?>
    <div class="dw-card">
        <div class="hdr"><i class="fa fa-users"></i> إحصائيات الموظفين</div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>استلام</th>
                    <th>تسليم</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employeeStats as $i => $emp): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td style="font-weight:600;"><?= Html::encode(NameHelper::short($emp['employee_name'])) ?></td>
                    <td><span class="dw-badge dw-badge--recv"><?= $emp['received'] ?></span></td>
                    <td><span class="dw-badge dw-badge--dlvr"><?= $emp['delivered'] ?></span></td>
                    <td><strong><?= $emp['received'] + $emp['delivered'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- المعاملات في الفترة -->
    <div class="dw-card">
        <div class="hdr"><i class="fa fa-list"></i> المعاملات (<?= $periodLabels[$period] ?>)</div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>النوع</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>العقود</th>
                    <th>التاريخ</th>
                    <th>الإيصال</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:28px; color:#999;">
                        لا توجد معاملات في هذه الفترة
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><strong><?= $t->id ?></strong></td>
                        <td>
                            <span class="dw-badge <?= $t->transaction_type === 'استلام' ? 'dw-badge--recv' : 'dw-badge--dlvr' ?>">
                                <?= $t->transaction_type ?>
                            </span>
                        </td>
                        <td><?= Html::encode($t->fromEmployee ? ($t->fromEmployee->name ? NameHelper::short($t->fromEmployee->name) : $t->fromEmployee->username) : '—') ?></td>
                        <td><?= Html::encode($t->toEmployee ? ($t->toEmployee->name ? NameHelper::short($t->toEmployee->name) : $t->toEmployee->username) : '—') ?></td>
                        <td><span class="badge" style="background:var(--fin-primary,#800020)"><?= count($t->details) ?></span></td>
                        <td style="font-size:11px"><?= Yii::$app->formatter->asDatetime($t->transaction_date, 'php:Y/m/d h:i A') ?></td>
                        <td><code style="font-size:10px"><?= Html::encode($t->receipt_number) ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
