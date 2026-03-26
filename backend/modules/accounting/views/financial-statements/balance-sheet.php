<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'بيان المركز المالي';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
$totalEquityWithIncome = $totalEquity + $netIncome;
$totalLiabilitiesAndEquity = $totalLiabilities + $totalEquityWithIncome;
$isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

$currentAssets = [];
$nonCurrentAssets = [];
foreach ($assets as $d) {
    if (strpos($d['account']->code, '11') === 0) {
        $currentAssets[] = $d;
    } else {
        $nonCurrentAssets[] = $d;
    }
}
$totalCurrent = array_sum(array_column($currentAssets, 'balance'));
$totalNonCurrent = array_sum(array_column($nonCurrentAssets, 'balance'));
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
.fs-filters .form-control { border-radius: 8px; font-size: 13px; min-width: 160px; }

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

.fs-section-head {
    background: var(--t-primary, #0B1D51);
    color: #fff;
    padding: 8px 18px;
    font-weight: 700;
    font-size: 13px;
}
.fs-sub-head {
    background: #f0f4fa;
    padding: 6px 18px;
    font-weight: 600;
    font-size: 12px;
    color: var(--t-primary, #0B1D51);
    border-bottom: 1px solid #e2e8f0;
    text-decoration: underline;
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

.fs-grand-total {
    border-radius: 10px;
    padding: 16px 20px;
    text-align: center;
    font-weight: 800;
    font-size: 15px;
    margin-top: 18px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}

.fs-balance-check {
    border-radius: 10px;
    padding: 14px 20px;
    text-align: center;
    font-weight: 700;
    font-size: 15px;
    margin-top: 12px;
}
.fs-balance-check.ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.fs-balance-check.err { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

.fs-stat-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.fs-stat {
    flex: 1;
    min-width: 160px;
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
</style>

<div class="fs-page">
    <!-- Header -->
    <div class="fs-header">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
            <div>
                <h2><i class="fa fa-university" style="margin-left:8px;opacity:0.7"></i> <?= $this->title ?></h2>
                <div class="fs-subtitle">الميزانية العمومية — كما في <?= Html::encode($dateTo) ?></div>
            </div>
            <div class="fs-toolbar">
                <?= Html::a('<i class="fa fa-file-pdf-o"></i> تصدير بيان المركز المالي', ['export-single-pdf', 'type' => 'balance-sheet', 'fiscal_year_id' => $fiscalYearId, 'date_to' => $dateTo], ['class' => 'btn btn-danger btn-sm', 'target' => '_blank']) ?>
                <?= Html::a('<i class="fa fa-book"></i> البيانات المالية الكاملة', ['export-pdf', 'fiscal_year_id' => $fiscalYearId, 'date_to' => $dateTo], ['class' => 'btn btn-warning btn-sm', 'target' => '_blank', 'style' => 'color:#fff']) ?>
                <?= Html::a('<i class="fa fa-balance-scale"></i> ميزان المراجعة', ['trial-balance', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
                <?= Html::a('<i class="fa fa-file-text"></i> قائمة الدخل', ['income-statement', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
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
            <label>حتى تاريخ</label>
            <input type="date" name="date_to" class="form-control" value="<?= Html::encode($dateTo) ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:7px 20px"><i class="fa fa-search"></i> عرض</button>
        </div>
    </form>

    <!-- Stats Row -->
    <div class="fs-stat-row">
        <div class="fs-stat" style="border-top:3px solid #3b82f6">
            <div class="fs-stat-value" style="color:#3b82f6"><?= number_format($totalAssets, 2) ?></div>
            <div class="fs-stat-label">إجمالي الموجودات</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid #f59e0b">
            <div class="fs-stat-value" style="color:#f59e0b"><?= number_format($totalLiabilities, 2) ?></div>
            <div class="fs-stat-label">إجمالي المطلوبات</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid var(--t-primary, #6b1d3a)">
            <div class="fs-stat-value" style="color:var(--t-primary, #6b1d3a)"><?= number_format($totalEquityWithIncome, 2) ?></div>
            <div class="fs-stat-label">صافي حقوق الملكية</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid <?= $netIncome >= 0 ? '#10b981' : '#ef4444' ?>">
            <div class="fs-stat-value" style="color:<?= $netIncome >= 0 ? '#10b981' : '#ef4444' ?>"><?= number_format($netIncome, 2) ?></div>
            <div class="fs-stat-label"><?= $netIncome >= 0 ? 'صافي ربح العام' : 'صافي خسارة العام' ?></div>
        </div>
    </div>

    <div class="row">
        <!-- الموجودات -->
        <div class="col-md-6">
            <div class="fs-card">
                <div class="fs-card-head" style="color:#1e40af;border-color:#3b82f6;background:#eff6ff">
                    <span><i class="fa fa-briefcase" style="margin-left:6px"></i> الموجودات</span>
                    <span class="fs-card-total" style="color:#1e40af"><?= number_format($totalAssets, 2) ?></span>
                </div>

                <?php if (!empty($currentAssets)): ?>
                <div class="fs-sub-head">موجودات متداولة</div>
                <table class="fs-table">
                    <?php foreach ($currentAssets as $data): ?>
                    <?php if ($data['balance'] == 0) continue; ?>
                    <tr>
                        <td class="fs-code"><?= $data['account']->code ?></td>
                        <td><?= Html::encode($data['account']->name_ar) ?></td>
                        <td class="fs-amount"><?= number_format($data['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fs-total-row" style="border-color:#93c5fd">
                        <td colspan="2" style="color:#1e40af">مجموع المتداولة</td>
                        <td class="fs-amount" style="color:#1e40af"><?= number_format($totalCurrent, 2) ?></td>
                    </tr>
                </table>
                <?php endif; ?>

                <?php if (!empty($nonCurrentAssets)): ?>
                <div class="fs-sub-head">موجودات غير متداولة</div>
                <table class="fs-table">
                    <?php foreach ($nonCurrentAssets as $data): ?>
                    <?php if ($data['balance'] == 0) continue; ?>
                    <tr>
                        <td class="fs-code"><?= $data['account']->code ?></td>
                        <td><?= Html::encode($data['account']->name_ar) ?></td>
                        <td class="fs-amount"><?= number_format($data['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fs-total-row" style="border-color:#93c5fd">
                        <td colspan="2" style="color:#1e40af">مجموع غير المتداولة</td>
                        <td class="fs-amount" style="color:#1e40af"><?= number_format($totalNonCurrent, 2) ?></td>
                    </tr>
                </table>
                <?php endif; ?>

                <div class="fs-grand-total" style="background:#1e40af;color:#fff;border-radius:0 0 12px 12px;margin-top:0">
                    <span>مجموع الموجودات</span>
                    <span style="font-family:'Courier New',monospace;font-size:18px;direction:ltr"><?= number_format($totalAssets, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- المطلوبات وحقوق الملكية -->
        <div class="col-md-6">
            <!-- المطلوبات -->
            <div class="fs-card">
                <div class="fs-card-head" style="color:#92400e;border-color:#f59e0b;background:#fffbeb">
                    <span><i class="fa fa-credit-card" style="margin-left:6px"></i> المطلوبات</span>
                    <span class="fs-card-total" style="color:#92400e"><?= number_format($totalLiabilities, 2) ?></span>
                </div>
                <table class="fs-table">
                    <?php foreach ($liabilities as $data): ?>
                    <?php if ($data['balance'] == 0) continue; ?>
                    <tr>
                        <td class="fs-code"><?= $data['account']->code ?></td>
                        <td><?= Html::encode($data['account']->name_ar) ?></td>
                        <td class="fs-amount"><?= number_format($data['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty(array_filter($liabilities, fn($d) => $d['balance'] != 0))): ?>
                    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:15px">لا توجد مطلوبات</td></tr>
                    <?php endif; ?>
                    <tr class="fs-total-row" style="border-color:#fbbf24">
                        <td colspan="2" style="color:#92400e">مجموع المطلوبات</td>
                        <td class="fs-amount" style="color:#92400e"><?= number_format($totalLiabilities, 2) ?></td>
                    </tr>
                </table>
            </div>

            <!-- حقوق الملكية -->
            <div class="fs-card">
                <div class="fs-card-head" style="color:var(--t-primary-emphasis, #5a0016);border-color:var(--t-primary, #800020);background:var(--t-primary-subtle-bg, #fdf2f4)">
                    <span><i class="fa fa-bank" style="margin-left:6px"></i> حقوق الملكية</span>
                    <span class="fs-card-total" style="color:var(--t-primary-emphasis, #5a0016)"><?= number_format($totalEquityWithIncome, 2) ?></span>
                </div>
                <table class="fs-table">
                    <?php foreach ($equity as $data): ?>
                    <?php if ($data['balance'] == 0) continue; ?>
                    <tr>
                        <td class="fs-code"><?= $data['account']->code ?></td>
                        <td><?= Html::encode($data['account']->name_ar) ?></td>
                        <td class="fs-amount"><?= number_format($data['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:<?= $netIncome >= 0 ? '#f0fdf4' : '#fef2f2' ?>">
                        <td class="fs-code"></td>
                        <td style="font-style:italic;color:<?= $netIncome >= 0 ? '#166534' : '#991b1b' ?>"><?= $netIncome >= 0 ? 'أرباح العام' : 'خسائر العام' ?></td>
                        <td class="fs-amount" style="color:<?= $netIncome >= 0 ? '#166534' : '#991b1b' ?>"><?= number_format($netIncome, 2) ?></td>
                    </tr>
                    <tr class="fs-total-row" style="border-color:var(--t-primary, #800020)">
                        <td colspan="2" style="color:var(--t-primary-emphasis, #5a0016)">صافي حقوق الملكية</td>
                        <td class="fs-amount" style="color:var(--t-primary-emphasis, #5a0016)"><?= number_format($totalEquityWithIncome, 2) ?></td>
                    </tr>
                </table>
            </div>

            <!-- المجموع الكلي -->
            <div class="fs-grand-total" style="background:var(--t-primary, #0B1D51);color:#fff;border-radius:10px">
                <span>مجموع المطلوبات وحقوق الملكية</span>
                <span style="font-family:'Courier New',monospace;font-size:18px;direction:ltr"><?= number_format($totalLiabilitiesAndEquity, 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Balance Check -->
    <div class="fs-balance-check <?= $isBalanced ? 'ok' : 'err' ?>">
        <?php if ($isBalanced): ?>
            <i class="fa fa-check-circle" style="margin-left:8px;font-size:18px"></i>
            المعادلة المحاسبية متوازنة: الموجودات (<?= number_format($totalAssets, 2) ?>) = المطلوبات + حقوق الملكية (<?= number_format($totalLiabilitiesAndEquity, 2) ?>)
        <?php else: ?>
            <i class="fa fa-times-circle" style="margin-left:8px;font-size:18px"></i>
            المعادلة غير متوازنة — الفرق: <?= number_format(abs($totalAssets - $totalLiabilitiesAndEquity), 2) ?> د.أ
        <?php endif; ?>
    </div>
</div>
