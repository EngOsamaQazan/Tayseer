<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'ميزان المراجعة';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');

$sumDebit = 0;
$sumCredit = 0;
$balDebit = 0;
$balCredit = 0;
$activeRows = [];
foreach ($balances as $data) {
    $account = $data['account'];
    if ($data['total_debit'] == 0 && $data['total_credit'] == 0 && $data['balance'] == 0) continue;
    $sumDebit += $data['total_debit'];
    $sumCredit += $data['total_credit'];
    $isDebitBalance = ($account->nature === 'debit' && $data['balance'] >= 0) || ($account->nature === 'credit' && $data['balance'] < 0);
    if ($isDebitBalance) {
        $balDebit += abs($data['balance']);
    } else {
        $balCredit += abs($data['balance']);
    }
    $activeRows[] = ['data' => $data, 'account' => $account, 'isDebit' => $isDebitBalance];
}
$diff = abs($balDebit - $balCredit);
$isBalanced = $diff < 0.01;
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
    min-width: 140px;
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

.tb-table { width: 100%; border-collapse: collapse; }
.tb-table thead th {
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    background: var(--t-primary, #0B1D51);
    text-align: center;
    white-space: nowrap;
}
.tb-table thead th:nth-child(2) { text-align: right; }
.tb-table tbody td {
    padding: 8px 14px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    color: #334155;
}
.tb-table tbody tr:nth-child(even) td { background: #fafbfe; }
.tb-table tbody tr:hover td { background: #f0f4ff; }
.tb-table .tb-code {
    font-family: 'Courier New', monospace;
    color: #64748b;
    font-weight: 700;
    font-size: 12px;
    text-align: center;
    width: 70px;
}
.tb-table .tb-amount {
    text-align: left;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    direction: ltr;
    width: 120px;
    white-space: nowrap;
}
.tb-table tfoot td {
    padding: 12px 14px;
    font-weight: 800;
    font-size: 14px;
    background: #f8fafc;
    border-top: 2px solid var(--t-primary, #0B1D51);
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
</style>

<div class="fs-page">
    <!-- Header -->
    <div class="fs-header">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
            <div>
                <h2><i class="fa fa-balance-scale" style="margin-left:8px;opacity:0.7"></i> <?= $this->title ?></h2>
                <div class="fs-subtitle">من <?= Html::encode($dateFrom) ?> إلى <?= Html::encode($dateTo) ?></div>
            </div>
            <div class="fs-toolbar">
                <?= Html::a('<i class="fa fa-book"></i> البيانات المالية الكاملة', ['export-pdf', 'fiscal_year_id' => $fiscalYearId, 'date_to' => $dateTo], ['class' => 'btn btn-warning btn-sm', 'target' => '_blank', 'style' => 'color:#fff']) ?>
                <?= Html::a('<i class="fa fa-university"></i> المركز المالي', ['balance-sheet', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm', 'style' => 'background:rgba(255,255,255,0.15);color:#fff;border:none']) ?>
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
        <div class="fs-stat" style="border-top:3px solid #3b82f6">
            <div class="fs-stat-value" style="color:#3b82f6"><?= number_format($sumDebit, 2) ?></div>
            <div class="fs-stat-label">إجمالي المدين</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid #ef4444">
            <div class="fs-stat-value" style="color:#ef4444"><?= number_format($sumCredit, 2) ?></div>
            <div class="fs-stat-label">إجمالي الدائن</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid #10b981">
            <div class="fs-stat-value" style="color:#10b981"><?= number_format($balDebit, 2) ?></div>
            <div class="fs-stat-label">أرصدة مدينة</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid #f59e0b">
            <div class="fs-stat-value" style="color:#f59e0b"><?= number_format($balCredit, 2) ?></div>
            <div class="fs-stat-label">أرصدة دائنة</div>
        </div>
        <div class="fs-stat" style="border-top:3px solid <?= $isBalanced ? '#10b981' : '#ef4444' ?>">
            <div class="fs-stat-value"><?= count($activeRows) ?></div>
            <div class="fs-stat-label">عدد الحسابات النشطة</div>
        </div>
    </div>

    <!-- Table -->
    <div class="fs-card">
        <div class="fs-card-head" style="color:var(--t-primary-emphasis, #0B1D51);border-color:var(--t-primary, #0B1D51);background:#f0f4fa">
            <span><i class="fa fa-table" style="margin-left:6px"></i> تفاصيل ميزان المراجعة</span>
            <span style="font-size:12px;color:#64748b"><?= count($activeRows) ?> حساب</span>
        </div>
        <table class="tb-table">
            <thead>
                <tr>
                    <th style="width:70px">كود</th>
                    <th>اسم الحساب</th>
                    <th style="width:120px">مدين</th>
                    <th style="width:120px">دائن</th>
                    <th style="width:120px">رصيد مدين</th>
                    <th style="width:120px">رصيد دائن</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeRows as $row): ?>
                <tr>
                    <td class="tb-code"><?= Html::encode($row['account']->code) ?></td>
                    <td><?= Html::encode($row['account']->name_ar) ?></td>
                    <td class="tb-amount"><?= $row['data']['total_debit'] > 0 ? number_format($row['data']['total_debit'], 2) : '<span style="color:#cbd5e1">—</span>' ?></td>
                    <td class="tb-amount"><?= $row['data']['total_credit'] > 0 ? number_format($row['data']['total_credit'], 2) : '<span style="color:#cbd5e1">—</span>' ?></td>
                    <td class="tb-amount" style="color:#10b981;font-weight:600"><?= $row['isDebit'] ? number_format(abs($row['data']['balance']), 2) : '<span style="color:#cbd5e1">—</span>' ?></td>
                    <td class="tb-amount" style="color:#f59e0b;font-weight:600"><?= !$row['isDebit'] ? number_format(abs($row['data']['balance']), 2) : '<span style="color:#cbd5e1">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($activeRows)): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px">لا توجد حركات محاسبية</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align:right;font-weight:800">المجموع</td>
                    <td class="tb-amount" style="font-weight:800"><?= number_format($sumDebit, 2) ?></td>
                    <td class="tb-amount" style="font-weight:800"><?= number_format($sumCredit, 2) ?></td>
                    <td class="tb-amount" style="font-weight:800;color:#10b981"><?= number_format($balDebit, 2) ?></td>
                    <td class="tb-amount" style="font-weight:800;color:#f59e0b"><?= number_format($balCredit, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Balance Check -->
    <div class="fs-balance-check <?= $isBalanced ? 'ok' : 'err' ?>">
        <?php if ($isBalanced): ?>
            <i class="fa fa-check-circle" style="margin-left:8px;font-size:18px"></i>
            ميزان المراجعة متوازن: إجمالي الأرصدة المدينة (<?= number_format($balDebit, 2) ?>) = إجمالي الأرصدة الدائنة (<?= number_format($balCredit, 2) ?>)
        <?php else: ?>
            <i class="fa fa-times-circle" style="margin-left:8px;font-size:18px"></i>
            ميزان المراجعة غير متوازن — الفرق: <?= number_format($diff, 2) ?> د.أ
        <?php endif; ?>
    </div>
</div>
