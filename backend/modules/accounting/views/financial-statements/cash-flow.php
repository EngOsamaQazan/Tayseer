<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'قائمة التدفقات النقدية';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');

$sections = [
    ['items' => $operating, 'title' => 'الأنشطة التشغيلية', 'icon' => 'fa-cog', 'color' => '#0ea5e9', 'bgColor' => '#f0f9ff', 'borderColor' => '#7dd3fc', 'textColor' => '#0c4a6e'],
    ['items' => $investing, 'title' => 'الأنشطة الاستثمارية', 'icon' => 'fa-building', 'color' => '#f59e0b', 'bgColor' => '#fffbeb', 'borderColor' => '#fcd34d', 'textColor' => '#78350f'],
    ['items' => $financing, 'title' => 'الأنشطة التمويلية', 'icon' => 'fa-bank', 'color' => '#8b5cf6', 'bgColor' => '#f5f3ff', 'borderColor' => '#c4b5fd', 'textColor' => '#3b0764'],
];

$sectionTotals = [];
foreach ($sections as &$sec) {
    $total = 0;
    foreach ($sec['items'] as $d) {
        $net = $d['total_debit'] - $d['total_credit'];
        if ($net != 0) $total += $net;
    }
    $sec['total'] = $total;
    $sectionTotals[] = $total;
}
unset($sec);
$netCashFlow = array_sum($sectionTotals);
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
    font-size: 20px;
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

.cf-table { width: 100%; border-collapse: collapse; }
.cf-table td { padding: 8px 18px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
.cf-table tr:hover td { background: #fafbfe; }
.cf-table .cf-code { font-family: 'Courier New', monospace; color: #94a3b8; font-size: 12px; width: 65px; }
.cf-table .cf-amount {
    text-align: left;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    direction: ltr;
    width: 140px;
    white-space: nowrap;
}
.cf-table .cf-total td {
    font-weight: 800;
    font-size: 14px;
    border-top: 2px solid;
    padding: 10px 18px;
}
.cf-flow-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-left: 6px;
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
                <h2><i class="fa fa-line-chart" style="margin-left:8px;opacity:0.7"></i> <?= $this->title ?></h2>
                <div class="fs-subtitle">من <?= Html::encode($dateFrom) ?> إلى <?= Html::encode($dateTo) ?></div>
            </div>
            <div class="fs-toolbar">
                <?= Html::a('<i class="fa fa-file-pdf-o"></i> تصدير التدفقات النقدية', ['export-single-pdf', 'type' => 'cash-flow', 'fiscal_year_id' => $fiscalYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo], ['class' => 'btn btn-danger btn-sm', 'target' => '_blank']) ?>
                <?= Html::a('<i class="fa fa-book"></i> البيانات المالية الكاملة', ['export-pdf', 'fiscal_year_id' => $fiscalYearId, 'date_to' => $dateTo], ['class' => 'btn btn-warning btn-sm', 'target' => '_blank', 'style' => 'color:#fff']) ?>
                <?= Html::a('<i class="fa fa-university"></i> المركز المالي', ['balance-sheet', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
                <?= Html::a('<i class="fa fa-balance-scale"></i> ميزان المراجعة', ['trial-balance', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
                <?= Html::a('<i class="fa fa-file-text"></i> قائمة الدخل', ['income-statement', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
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
        <?php foreach ($sections as $i => $sec): ?>
        <div class="fs-stat" style="border-top:3px solid <?= $sec['color'] ?>">
            <div class="fs-stat-value" style="color:<?= $sec['total'] >= 0 ? '#10b981' : '#ef4444' ?>"><?= ($sec['total'] >= 0 ? '+' : '') . number_format($sec['total'], 2) ?></div>
            <div class="fs-stat-label"><?= $sec['title'] ?></div>
        </div>
        <?php endforeach; ?>
        <div class="fs-stat" style="border-top:3px solid <?= $netCashFlow >= 0 ? '#10b981' : '#ef4444' ?>">
            <div class="fs-stat-value" style="color:<?= $netCashFlow >= 0 ? '#10b981' : '#ef4444' ?>"><?= ($netCashFlow >= 0 ? '+' : '') . number_format($netCashFlow, 2) ?></div>
            <div class="fs-stat-label">صافي التدفق النقدي</div>
        </div>
    </div>

    <!-- Sections -->
    <?php foreach ($sections as $sec): ?>
    <div class="fs-card">
        <div class="fs-card-head" style="color:<?= $sec['textColor'] ?>;border-color:<?= $sec['color'] ?>;background:<?= $sec['bgColor'] ?>">
            <span><i class="fa <?= $sec['icon'] ?>" style="margin-left:6px"></i> <?= $sec['title'] ?></span>
            <span class="fs-card-total" style="color:<?= $sec['total'] >= 0 ? '#10b981' : '#ef4444' ?>"><?= ($sec['total'] >= 0 ? '+' : '') . number_format($sec['total'], 2) ?></span>
        </div>
        <table class="cf-table">
            <?php
            $hasRows = false;
            foreach ($sec['items'] as $data):
                $net = $data['total_debit'] - $data['total_credit'];
                if ($net == 0) continue;
                $hasRows = true;
            ?>
            <tr>
                <td class="cf-code"><?= $data['account']->code ?></td>
                <td>
                    <span class="cf-flow-indicator" style="background:<?= $net > 0 ? '#10b981' : '#ef4444' ?>"></span>
                    <?= Html::encode($data['account']->name_ar) ?>
                </td>
                <td class="cf-amount" style="color:<?= $net > 0 ? '#10b981' : '#ef4444' ?>"><?= ($net > 0 ? '+' : '') . number_format($net, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$hasRows): ?>
            <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px">لا توجد حركات</td></tr>
            <?php endif; ?>
            <tr class="cf-total" style="border-color:<?= $sec['borderColor'] ?>">
                <td colspan="2" style="color:<?= $sec['textColor'] ?>">صافي <?= $sec['title'] ?></td>
                <td class="cf-amount" style="color:<?= $sec['total'] >= 0 ? '#10b981' : '#ef4444' ?>;font-weight:800"><?= ($sec['total'] >= 0 ? '+' : '') . number_format($sec['total'], 2) ?></td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>

    <!-- Net Cash Flow Result -->
    <div class="fs-result" style="background:<?= $netCashFlow >= 0 ? 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border:1px solid #6ee7b7' : 'linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); border:1px solid #fca5a5' ?>">
        <div class="fs-result-label" style="color:<?= $netCashFlow >= 0 ? '#065f46' : '#991b1b' ?>">
            <i class="fa fa-<?= $netCashFlow >= 0 ? 'arrow-circle-up' : 'arrow-circle-down' ?>" style="margin-left:6px;font-size:18px"></i>
            صافي التدفق النقدي للفترة
        </div>
        <div class="fs-result-value" style="color:<?= $netCashFlow >= 0 ? '#065f46' : '#991b1b' ?>">
            <?= ($netCashFlow >= 0 ? '+' : '') . number_format($netCashFlow, 2) ?>
        </div>
        <div class="fs-result-badge" style="background:<?= $netCashFlow >= 0 ? '#065f46' : '#991b1b' ?>;color:#fff">
            تشغيلي <?= ($sectionTotals[0] >= 0 ? '+' : '') . number_format($sectionTotals[0], 2) ?>
            &nbsp;|&nbsp; استثماري <?= ($sectionTotals[1] >= 0 ? '+' : '') . number_format($sectionTotals[1], 2) ?>
            &nbsp;|&nbsp; تمويلي <?= ($sectionTotals[2] >= 0 ? '+' : '') . number_format($sectionTotals[2], 2) ?>
        </div>
    </div>
</div>
