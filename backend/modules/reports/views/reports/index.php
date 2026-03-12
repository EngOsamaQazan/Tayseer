<?php
/**
 * لوحة تحكم التقارير — نظرة عامة احترافية
 */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'التقارير';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);

$months = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];
$currentYear = (int)date('Y');
$years = range($currentYear - 5, $currentYear);
?>

<?= $this->render('@app/views/layouts/_reports-tabs', ['activeTab' => 'overview']) ?>

<style>
:root { --rp-blue: #1d4ed8; --rp-blue-bg: #dbeafe; --rp-green: #15803d; --rp-green-bg: #dcfce7; --rp-amber: #d97706; --rp-amber-bg: #fef3c7; --rp-red: #dc2626; --rp-red-bg: #fee2e2; --rp-purple: #7c3aed; --rp-purple-bg: #ede9fe; --rp-gray: #64748b; }

/* فلتر الفترة */
.rp-period-filter { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; padding: 14px 20px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,.04); flex-wrap: wrap; }
.rp-period-filter label { font-weight: 700; font-size: 13px; color: #475569; margin: 0; }
.rp-period-filter select { border: 2px solid #e2e8f0; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; color: #1e293b; background: #f8fafc; min-width: 120px; }
.rp-period-filter select:focus { border-color: var(--rp-blue); outline: none; }
.rp-period-filter .rp-filter-btn { padding: 7px 22px; border: none; border-radius: 8px; background: var(--rp-blue); color: #fff; font-weight: 700; font-size: 13px; cursor: pointer; transition: all .2s; }
.rp-period-filter .rp-filter-btn:hover { background: #1e40af; }
.rp-period-lbl { font-size: 12px; color: #94a3b8; font-weight: 600; padding: 3px 10px; background: var(--rp-blue-bg); border-radius: 6px; color: var(--rp-blue); }

.rp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
.rp-stat { display: flex; align-items: center; gap: 16px; padding: 22px 24px; border-radius: 14px; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; transition: all 0.2s; }
.rp-stat:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); transform: translateY(-3px); }
.rp-stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.rp-stat-body { display: flex; flex-direction: column; }
.rp-stat-num { font-size: 26px; font-weight: 800; line-height: 1.1; font-family: 'Cairo', sans-serif; }
.rp-stat-lbl { font-size: 12.5px; font-weight: 600; color: var(--rp-gray); margin-top: 3px; }

.rp-stat--income .rp-stat-icon { background: var(--rp-green-bg); color: var(--rp-green); }
.rp-stat--income .rp-stat-num { color: var(--rp-green); }
.rp-stat--count .rp-stat-icon { background: var(--rp-blue-bg); color: var(--rp-blue); }
.rp-stat--count .rp-stat-num { color: var(--rp-blue); }
.rp-stat--followup .rp-stat-icon { background: var(--rp-amber-bg); color: var(--rp-amber); }
.rp-stat--followup .rp-stat-num { color: var(--rp-amber); }
.rp-stat--judiciary .rp-stat-icon { background: var(--rp-purple-bg); color: var(--rp-purple); }
.rp-stat--judiciary .rp-stat-num { color: var(--rp-purple); }

/* أزرار سريعة */
.rp-quick { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px; }
.rp-quick-card { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-radius: 12px; background: #fff; border: 2px solid #e2e8f0; text-decoration: none !important; transition: all 0.2s; cursor: pointer; }
.rp-quick-card:hover { border-color: var(--rp-blue); box-shadow: 0 4px 14px rgba(29,78,216,0.12); transform: translateY(-2px); }
.rp-qc-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.rp-qc-body { display: flex; flex-direction: column; }
.rp-qc-title { font-weight: 700; font-size: 14px; color: #1e293b; }
.rp-qc-desc { font-size: 12px; color: #94a3b8; margin-top: 2px; }

.rp-qc--income .rp-qc-icon { background: var(--rp-green-bg); color: var(--rp-green); }
.rp-qc--followup .rp-qc-icon { background: var(--rp-blue-bg); color: var(--rp-blue); }
.rp-qc--judiciary .rp-qc-icon { background: var(--rp-purple-bg); color: var(--rp-purple); }
.rp-qc--actions .rp-qc-icon { background: var(--rp-amber-bg); color: var(--rp-amber); }
</style>

<div class="rp-overview">

    <!-- فلتر الفترة -->
    <form class="rp-period-filter" method="get" action="<?= Url::to(['/reports/reports/index']) ?>">
        <label><i class="fa fa-calendar-o"></i>&nbsp; الفترة:</label>
        <select name="month">
            <?php foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num == $selectedMonth ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach ?>
        </select>
        <select name="year">
            <?php foreach (array_reverse($years) as $y): ?>
                <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach ?>
        </select>
        <button type="submit" class="rp-filter-btn"><i class="fa fa-search"></i>&nbsp; عرض</button>
        <span class="rp-period-lbl"><?= $months[$selectedMonth] ?> <?= $selectedYear ?></span>
    </form>

    <!-- بطاقات الإحصائيات -->
    <div class="rp-stats">
        <div class="rp-stat rp-stat--income">
            <div class="rp-stat-icon"><i class="fa fa-money"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= number_format($totalIncome, 2) ?></span>
                <span class="rp-stat-lbl">إجمالي الإيرادات</span>
            </div>
        </div>
        <div class="rp-stat rp-stat--count">
            <div class="rp-stat-icon"><i class="fa fa-file-text-o"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= number_format($incomeCount) ?></span>
                <span class="rp-stat-lbl">عدد الحركات المالية</span>
            </div>
        </div>
        <div class="rp-stat rp-stat--followup">
            <div class="rp-stat-icon"><i class="fa fa-phone"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= number_format($followUpCount) ?></span>
                <span class="rp-stat-lbl">عدد المتابعات</span>
            </div>
        </div>
        <div class="rp-stat rp-stat--judiciary">
            <div class="rp-stat-icon"><i class="fa fa-balance-scale"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= number_format($judiciaryCount) ?></span>
                <span class="rp-stat-lbl">إجمالي القضايا</span>
            </div>
        </div>
    </div>

    <!-- أقسام التقارير -->
    <div class="rp-quick">
        <a href="<?= Url::to(['/reports/reports/total-customer-payments-index']) ?>" class="rp-quick-card rp-qc--income">
            <div class="rp-qc-icon"><i class="fa fa-money"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">تقرير الإيرادات</span>
                <span class="rp-qc-desc">مدفوعات العملاء حسب الفترة والموظف والشركة</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/total-judiciary-customer-payments-index']) ?>" class="rp-quick-card rp-qc--income">
            <div class="rp-qc-icon"><i class="fa fa-gavel"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">إيرادات القضايا</span>
                <span class="rp-qc-desc">مدفوعات عملاء القضايا القضائية</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/index2']) ?>" class="rp-quick-card rp-qc--followup">
            <div class="rp-qc-icon"><i class="fa fa-phone"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">تقارير المتابعة</span>
                <span class="rp-qc-desc">نشاطات المتابعة والتواصل مع العملاء</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/judiciary-index']) ?>" class="rp-quick-card rp-qc--judiciary">
            <div class="rp-qc-icon"><i class="fa fa-balance-scale"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">التقارير القضائية</span>
                <span class="rp-qc-desc">القضايا والمحاكم والمحامين</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/customers-judiciary-actions']) ?>" class="rp-quick-card rp-qc--actions">
            <div class="rp-qc-icon"><i class="fa fa-list-alt"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">الحركات القضائية للعملاء</span>
                <span class="rp-qc-desc">تفاصيل الإجراءات القضائية لكل عميل</span>
            </div>
        </a>
    </div>

</div>
