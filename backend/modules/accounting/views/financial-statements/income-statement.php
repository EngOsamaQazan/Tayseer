<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'قائمة الدخل';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
$profitMargin = $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 1) : 0;
?>

<style>
.fs-page { direction: rtl; }
.fs-header {
    background: linear-gradient(135deg, var(--t-primary, #0B1D51) 0%, #1a3a7a 100%);
    border-radius: 12px;
    padding: 20px 25px;
    margin-bottom: 20px;
    color: #fff;
}
.fs-header h2 { margin: 0 0 4px 0; font-size: 22px; font-weight: 700; }
.fs-header .fs-subtitle { font-size: 13px; opacity: 0.8; }
.fs-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.fs-toolbar .btn { border-radius: 8px; font-size: 12px; font-weight: 600; }

.fs-filters {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.fs-filters label { font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 4px; display: block; }
.fs-filters .form-control { border-radius: 8px; font-size: 13px; min-width: 140px; }

.fs-stat-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.fs-stat {
    flex: 1;
    min-width: 150px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 16px 18px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.fs-stat-value {
    font-size: 22px;
    font-weight: 800;
    font-family: 'Courier New', monospace;
    direction: ltr;
    margin-bottom: 4px;
}
.fs-stat-label { font-size: 12px; color: #64748b; font-weight: 600; }

.fs-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.fs-card-head {
    padding: 12px 18px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid;
}
.fs-card-head .fs-card-total {
    font-family: 'Courier New', monospace;
    font-size: 16px;
    font-weight: 800;
    direction: ltr;
}

.fs-table { width: 100%; border-collapse: collapse; }
.fs-table td { padding: 8px 18px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
.fs-table tr:hover td { background: #fafbfe; }
.fs-table .fs-code { font-family: 'Courier New', monospace; color: #94a3b8; font-size: 12px; width: 65px; }
.fs-table .fs-amount {
    text-align: left;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    direction: ltr;
    width: 130px;
    white-space: nowrap;
}
.fs-table .fs-total-row td {
    font-weight: 800;
    font-size: 14px;
    border-top: 2px solid;
    border-bottom: 2px solid;
    padding: 10px 18px;
}

.fs-result {
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    margin-top: 18px;
}
.fs-result-label { font-size: 14px; font-weight: 600; margin-bottom: 6px; }
.fs-result-value { font-size: 28px; font-weight: 800; font-family: 'Courier New', monospace; direction: ltr; }
.fs-result-badge { display: inline-block; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; margin-top: 8px; }
</style>

<div class="fs-page">
    <!-- Header -->
    <div class="fs-header">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
            <div>
                <h2><i class="fa fa-file-text" style="margin-left:8px;opacity:0.7"></i> <?= $this->title ?></h2>
                <div class="fs-subtitle">بيان الأرباح والخسائر — من <?= Html::encode($dateFrom) ?> إلى <?= Html::encode($dateTo) ?></div>
            </div>
            <div class="fs-toolbar">
                <?= Html::a('<i class="fa fa-file-pdf-o"></i> تصدير قائمة الدخل', ['export-single-pdf', 'type' => 'income-statement', 'fiscal_year_id' => $fiscalYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo], ['class' => 'btn btn-danger btn-sm', 'target' => '_blank']) ?>
                <?= Html::a('<i class="fa fa-book"></i> البيانات المالية الكاملة', ['export-pdf', 'fiscal_year_id' => $fiscalYearId, 'date_to' => $dateTo], ['class' => 'btn btn-warning btn-sm', 'target' => '_blank', 'style' => 'color:#fff']) ?>
                <?= Html::a('<i class="fa fa-university"></i> المركز المالي', ['balance-sheet', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
                <?= Html::a('<i class="fa fa-balance-scale"></i> ميزان المراجعة', ['trial-balance', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
                <?= Html::a('<i class="fa fa-line-chart"></i> التدفقات النقدية', ['cash-flow', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="fs-filters">
        <div>
            <label>السنة المالية</label>
            <select name="fiscal_year_id" class="form-control">
                <option value="">جميع السنوات</option>
                <?php foreach ($fyOptions as $fId => $fName): ?>
                <option value="<?= $fId ?>" <?= $fiscalYearId == $fId ? 'selected' : '' ?>><?= Html::encode($fName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>من تاريخ</label>
            <input type="date" name="date_from" class="form-control" value="<?= Html::encode($dateFrom) ?>">
        </div>
        <div>
            <label>إلى تاريخ</label>
            <input type="date" name="date_to" class="form-control" value="<?= Html::encode($dateTo) ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:7px 20px"><i class="fa fa-search"></i> عرض</button>
        </div>
    </form>

    <!-- Stats -->
    <div class="fs-stat-row">
        <div class="fs-stat" style="border-top:3px solid #10b981">
            <div class="fs-stat-value" style="color:#10b981"><?= number_format($totalRevenue, 2) ?></div>
            <div class="fs-stat-label">إجمالي الإيرادات</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid #ef4444">
            <div class="fs-stat-value" style="color:#ef4444"><?= number_format($totalExpenses, 2) ?></div>
            <div class="fs-stat-label">إجمالي المصروفات</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid <?= $netIncome >= 0 ? '#10b981' : '#ef4444' ?>">
            <div class="fs-stat-value" style="color:<?= $netIncome >= 0 ? '#10b981' : '#ef4444' ?>"><?= number_format($netIncome, 2) ?></div>
            <div class="fs-stat-label"><?= $netIncome >= 0 ? 'صافي الربح' : 'صافي الخسارة' ?></div>
        </div>
        <div class="fs-stat" style="border-top:3px solid #8b5cf6">
            <div class="fs-stat-value" style="color:#8b5cf6"><?= $profitMargin ?>%</div>
            <div class="fs-stat-label">هامش الربح</div>
        </div>
    </div>

    <div class="row">
        <!-- الإيرادات -->
        <div class="col-md-6">
            <div class="fs-card">
                <div class="fs-card-head" style="color:#065f46;border-color:#10b981;background:#ecfdf5">
                    <span><i class="fa fa-arrow-circle-down" style="margin-left:6px"></i> الإيرادات</span>
                    <span class="fs-card-total" style="color:#065f46"><?= number_format($totalRevenue, 2) ?></span>
                </div>
                <table class="fs-table">
                    <?php foreach ($revenue as $data): ?>
                    <?php if ($data['balance'] == 0) continue; ?>
                    <tr>
                        <td class="fs-code"><?= $data['account']->code ?></td>
                        <td><?= Html::encode($data['account']->name_ar) ?></td>
                        <td class="fs-amount" style="color:#10b981"><?= number_format($data['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($revenue)): ?>
                    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px">لا توجد إيرادات</td></tr>
                    <?php endif; ?>
                    <tr class="fs-total-row" style="border-color:#6ee7b7">
                        <td colspan="2" style="color:#065f46">مجموع الإيرادات</td>
                        <td class="fs-amount" style="color:#065f46"><?= number_format($totalRevenue, 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- المصروفات -->
        <div class="col-md-6">
            <div class="fs-card">
                <div class="fs-card-head" style="color:#991b1b;border-color:#ef4444;background:#fef2f2">
                    <span><i class="fa fa-arrow-circle-up" style="margin-left:6px"></i> المصروفات</span>
                    <span class="fs-card-total" style="color:#991b1b"><?= number_format($totalExpenses, 2) ?></span>
                </div>
                <table class="fs-table">
                    <?php foreach ($expenses as $data): ?>
                    <?php if ($data['balance'] == 0) continue; ?>
                    <tr>
                        <td class="fs-code"><?= $data['account']->code ?></td>
                        <td><?= Html::encode($data['account']->name_ar) ?></td>
                        <td class="fs-amount" style="color:#ef4444"><?= number_format($data['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($expenses)): ?>
                    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px">لا توجد مصروفات</td></tr>
                    <?php endif; ?>
                    <tr class="fs-total-row" style="border-color:#fca5a5">
                        <td colspan="2" style="color:#991b1b">مجموع المصروفات</td>
                        <td class="fs-amount" style="color:#991b1b"><?= number_format($totalExpenses, 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Net Income Result -->
    <div class="fs-result" style="background:<?= $netIncome >= 0 ? 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border:1px solid #6ee7b7' : 'linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); border:1px solid #fca5a5' ?>">
        <div class="fs-result-label" style="color:<?= $netIncome >= 0 ? '#065f46' : '#991b1b' ?>">
            <i class="fa fa-<?= $netIncome >= 0 ? 'trending-up' : 'trending-down' ?>" style="margin-left:6px"></i>
            <?= $netIncome >= 0 ? 'صافي ربح الفترة' : 'صافي خسارة الفترة' ?>
        </div>
        <div class="fs-result-value" style="color:<?= $netIncome >= 0 ? '#065f46' : '#991b1b' ?>">
            <?= number_format(abs($netIncome), 2) ?>
        </div>
        <div class="fs-result-badge" style="background:<?= $netIncome >= 0 ? '#065f46' : '#991b1b' ?>;color:#fff">
            <?= $netIncome >= 0 ? 'ربح' : 'خسارة' ?> — الإيرادات <?= number_format($totalRevenue, 2) ?> − المصروفات <?= number_format($totalExpenses, 2) ?>
        </div>
    </div>
</div>
