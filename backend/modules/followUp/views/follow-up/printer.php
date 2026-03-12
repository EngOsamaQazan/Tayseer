<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\LoanContract;
use backend\modules\contractInstallment\models\ContractInstallment;
use common\components\CompanyChecked;
use backend\modules\followUp\helper\RiskEngine;

/* @var $this \yii\web\View */
/* @var $contract_id int */

$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

// ─── Number formatters (English numerals only) ───
function stNum($n) {
    if ($n === null || $n === '' || $n === '—' || $n === 'لا يوجد') return $n;
    if (!is_numeric($n)) return $n;
    return number_format((float) $n, 2, '.', ',');
}
function stNumInt($n) {
    if ($n === null || $n === '') return $n;
    if (!is_numeric($n)) return $n;
    return number_format((int) $n, 0, '', ',');
}

// ─── Company ───
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $companyName = Yii::$app->params['companies_logo'] ?? '';
    $compay_banks = '';
    $companyPhone = '';
} else {
    $companyName = $primary_company->name;
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();
    $companyPhone = $primary_company->phone ?? '';
}

// ─── Contract & customers ───
$clientInContract = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['customer_type' => 'client', 'contract_id' => $contract_id])->all();
$guarantorInContract = \backend\modules\customers\models\ContractsCustomers::find()
    ->where(['customer_type' => 'guarantor', 'contract_id' => $contract_id])->all();

$modelf = new LoanContract;
$contractModel = $modelf->findContract($contract_id);
$total = $contractModel->total_value;
$judicary_contract = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contractModel->id])->all();
$sum_case_cost = 0;
$lawyer_cost_total = 0;

if (!empty($judicary_contract)) {
    $all_case_cost = \backend\modules\expenses\models\Expenses::find()
        ->where(['contract_id' => $contractModel->id, 'category_id' => 4])->all();
    foreach ($all_case_cost as $case_cost) {
        $sum_case_cost += $case_cost->amount;
    }
}
if (!empty($judicary_contract)) {
    foreach (\backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contractModel->id])->all() as $cost) {
        $lawyer_cost_total += $cost->lawyer_cost;
        $contractModel->total_value = $contractModel->total_value + $sum_case_cost + $cost->lawyer_cost;
    }
}

$clientNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $clientInContract);
$guarantorNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $guarantorInContract);

// ─── Financial calculations ───
$paid_amount = ContractInstallment::find()->andWhere(['contract_id' => $contractModel->id])->sum('amount');
$paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
$custamer_referance = $custamer_referance ?? 0;
$remaining_balance = ($contractModel->total_value + $custamer_referance) - $paid_amount;

$lastIncomeDate = ContractInstallment::find()
    ->where(['contract_id' => $contract_id])->orderBy(['date' => SORT_DESC])->one();
$sumIncome = ContractInstallment::find()
    ->where(['contract_id' => $contract_id])->sum('amount');

// ─── Movements ───
$provider = new \yii\data\SqlDataProvider([
    'sql' => "SELECT 
                os_contracts.id,
                os_contracts.total_value as amount,
                'ثمن البضاعة' as description,
                os_contracts.Date_of_sale as date,
                'مدين' as type,
                '' as notes
              FROM os_contracts WHERE os_contracts.id = :cid
              UNION
              SELECT os_judiciary.id, os_judiciary.lawyer_cost as amount, 'اتعاب محاماه' as description,
                     os_judiciary.created_at as date, 'مدين' as type, '' as notes
              FROM os_judiciary WHERE os_judiciary.contract_id = :cid
              UNION
              SELECT os_expenses.id, os_expenses.amount, description, os_expenses.created_at AS date, 'مدين' as type, notes
              FROM os_expenses WHERE os_expenses.contract_id = :cid
              UNION
              SELECT os_income.id, os_income.amount, _by as description, os_income.date as date, 'دائن' as type, notes
              FROM os_income WHERE os_income.contract_id = :cid
              ORDER BY date",
    'params' => [':cid' => $contract_id],
    'totalCount' => 200,
    'pagination' => ['pageSize' => 200],
]);
$provider->prepare();
$movements = $provider->getModels();

// ─── Normalize dates & put undated/corrupt-date expenses after ثمن البضاعة ───
$isDateValid = function ($date) {
    if ($date === null || $date === '') return false;
    $str = is_string($date) ? substr($date, 0, 10) : date('Y-m-d', strtotime($date));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return false;
    $y = (int) substr($str, 0, 4);
    return $y >= 1990 && $y <= 2030;
};
$saleRow = null;
$withDate = [];
$noDate = [];
foreach ($movements as $m) {
    if (trim($m['description'] ?? '') === 'ثمن البضاعة') {
        $saleRow = $m;
        continue;
    }
    if ($isDateValid($m['date'] ?? null)) {
        $withDate[] = $m;
    } else {
        $noDate[] = $m;
    }
}
usort($withDate, function ($a, $b) {
    $da = $a['date'] ?? '';
    $db = $b['date'] ?? '';
    $ta = is_string($da) ? strtotime(substr($da, 0, 10)) : strtotime($da);
    $tb = is_string($db) ? strtotime(substr($db, 0, 10)) : strtotime($db);
    return $ta <=> $tb;
});
$movements = array_merge($saleRow ? [$saleRow] : [], $withDate, $noDate);

// ─── Verification ───
$lastMovementDate = null;
foreach ($movements as $m) {
    $d = isset($m['date']) ? (is_string($m['date']) ? substr($m['date'], 0, 10) : date('Y-m-d', strtotime($m['date']))) : null;
    if ($d && (!$lastMovementDate || $d > $lastMovementDate)) {
        $lastMovementDate = $d;
    }
}
if (!$lastMovementDate) {
    $lastMovementDate = date('Y-m-d');
}
$statementDate = date('Y-m-d');
$secret = Yii::$app->params['statementVerifySecret'] ?? 'jadal-statement-verify-default';
$payload = $contract_id . '|' . $statementDate . '|' . $lastMovementDate;
$signature = hash_hmac('sha256', $payload, $secret);
$verifyCode = strtoupper(substr($signature, 0, 4) . '-' . substr($signature, 4, 4) . '-' . substr($signature, 8, 4));
$verifyUrl = Url::to(['/followUp/follow-up/verify-statement', 'c' => $contract_id, 'd' => $statementDate, 't' => $lastMovementDate, 's' => $signature], true);

$qrImageSrc = null;
if (class_exists(\chillerlan\QRCode\QRCode::class)) {
    try {
        $qrImageSrc = (new \chillerlan\QRCode\QRCode())->render($verifyUrl);
    } catch (\Throwable $e) {
        $qrImageSrc = null;
    }
}
if ($qrImageSrc === null) {
    $qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($verifyUrl);
}

// ─── Risk Assessment (same as OCP panel) ───
$riskLevelArabic = ['low' => 'منخفض', 'med' => 'متوسط', 'high' => 'مرتفع', 'critical' => 'حرج'];
$fullContract = \backend\modules\contracts\models\Contracts::findOne($contract_id);
if ($fullContract) {
    $riskEngine = new RiskEngine($fullContract);
    $riskAssessment = $riskEngine->assess();
    $riskLevel = $riskAssessment['level'];
} else {
    $riskLevel = 'low';
}
$riskLabel = $riskLevelArabic[$riskLevel] ?? 'غير محدد';

// ─── Payment rate ───
$totalForRate = $contractModel->total_value > 0 ? $contractModel->total_value : 1;
$paymentRate = min(100, round(($paid_amount / $totalForRate) * 100, 1));

// ─── Compute totals for table summary ───
$totalDebit = 0;
$totalCredit = 0;
$finalBalance = 0;
foreach ($movements as $m) {
    $amt = (float)($m['amount'] ?? 0);
    if (($m['type'] ?? '') === 'مدين') $totalDebit += $amt;
    if (($m['type'] ?? '') === 'دائن') $totalCredit += $amt;
}
// Final balance will be calculated during table render

// ─── Contract info — split into two groups ───
$infoClient = [
    ['label' => 'اسم العميل',     'value' => implode(' ، ', $clientNames)],
    ['label' => 'أسماء الكفلاء',   'value' => implode(' ، ', $guarantorNames) ?: 'لا يوجد'],
    ['label' => 'رقم العقد',       'value' => $contract_id],
];

$infoFinancial = [
    ['label' => 'تاريخ البيع',     'value' => $contractModel->Date_of_sale ?? '—'],
    ['label' => 'تاريخ أول قسط',   'value' => $contractModel->first_installment_date ?? '—'],
    ['label' => 'آخر دفعة',       'value' => $lastIncomeDate ? $lastIncomeDate->date : 'لا يوجد'],
    ['label' => 'القسط الشهري',   'value' => stNum($contractModel->monthly_installment_value)],
];

if ($contractModel->status == 'judiciary' || !empty($judicary_contract)) {
    $costRow = \backend\modules\judiciary\models\Judiciary::find()
        ->where(['contract_id' => $contractModel->id])->orderBy(['contract_id' => SORT_DESC])->one();
    $infoFinancial[] = ['label' => 'رسوم المحاكم',  'value' => $costRow ? stNum($costRow->case_cost) : '—'];
    $infoFinancial[] = ['label' => 'أتعاب المحامي', 'value' => $costRow ? stNum($costRow->lawyer_cost) : '—'];
}
?>

<!-- ══════════════════════════════════════════════════════════
     كشف حساب عميل — Premium FinTech Statement
     ══════════════════════════════════════════════════════════ -->
<div class="fs" id="financial-statement">

    <!-- ═══════════════════════════════════════
         1) EXECUTIVE HEADER
         ═══════════════════════════════════════ -->
    <header class="fs-header">
        <div class="fs-header__row">
            <div class="fs-header__brand">
                <div class="fs-header__logo">
                    <svg viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="7" fill="rgba(255,255,255,0.12)"/><path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h1 class="fs-header__company"><?= Html::encode($companyName) ?></h1>
                    <p class="fs-header__subtitle">كشف حساب عميل</p>
                </div>
            </div>
            <div class="fs-header__badge-wrap">
                <div class="fs-risk-badge fs-risk-badge--<?= $riskLevel ?>">
                    <span class="fs-risk-badge__dot"></span>
                    <?= $riskLabel ?>
                </div>
            </div>
        </div>

        <div class="fs-header__meta-row">
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">رقم الكشف</span>
                <span class="fs-header__meta-value en"><?= Html::encode($contract_id) ?></span>
            </div>
            <span class="fs-header__meta-dot"></span>
            <div class="fs-header__meta-item">
                <span class="fs-header__meta-label">تاريخ الإصدار</span>
                <span class="fs-header__meta-value en"><?= $statementDate ?></span>
            </div>
        </div>

        <div class="fs-header__verify-strip">
            <div class="fs-header__qr-box">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR" />
            </div>
            <div class="fs-header__verify-info">
                <span class="fs-header__verify-hint">رقم التحقق</span>
                <span class="fs-header__verify-code en"><?= $verifyCode ?></span>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════
         2) FINANCIAL SUMMARY — 4 Cards
         ═══════════════════════════════════════ -->
    <section class="fs-cards">
        <div class="fs-cards__grid">
            <!-- A) إجمالي العقد -->
            <div class="fs-card fs-card--neutral">
                <span class="fs-card__label">إجمالي العقد</span>
                <span class="fs-card__amount en"><?= stNum($contractModel->total_value) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <!-- B) المدفوع -->
            <div class="fs-card fs-card--success">
                <span class="fs-card__label">المدفوع</span>
                <span class="fs-card__amount en"><?= stNum($paid_amount) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
            <!-- C) المتبقي -->
            <div class="fs-card fs-card--danger">
                <span class="fs-card__label">المتبقي</span>
                <span class="fs-card__amount en"><?= stNum($remaining_balance) ?></span>
                <span class="fs-card__currency">د.أ</span>
            </div>
        </div>

        <!-- D) نسبة السداد — Full width -->
        <div class="fs-progress-card">
            <div class="fs-progress-card__top">
                <span class="fs-progress-card__label">نسبة السداد</span>
                <span class="fs-progress-card__percent en"><?= $paymentRate ?>%</span>
            </div>
            <div class="fs-progress-card__bar">
                <div class="fs-progress-card__fill" style="width: <?= $paymentRate ?>%"></div>
            </div>
            <p class="fs-progress-card__text">تم سداد <strong class="en"><?= stNum($paid_amount) ?></strong> من أصل <strong class="en"><?= stNum($contractModel->total_value) ?></strong> دينار</p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         3) CONTRACT INFORMATION
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">معلومات العقد</h3>
        <div class="fs-info">
            <!-- بيانات العميل -->
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات العميل</h4>
                <?php foreach ($infoClient as $row): ?>
                <div class="fs-info__row">
                    <span class="fs-info__label"><?= Html::encode($row['label']) ?></span>
                    <span class="fs-info__value en"><?= Html::encode($row['value']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- بيانات مالية -->
            <div class="fs-info__group">
                <h4 class="fs-info__group-title">بيانات مالية</h4>
                <?php foreach ($infoFinancial as $row): ?>
                <div class="fs-info__row">
                    <span class="fs-info__label"><?= Html::encode($row['label']) ?></span>
                    <span class="fs-info__value en"><?= Html::encode($row['value']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         4) FINANCIAL MOVEMENTS TABLE
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">الحركات المالية</h3>
        <div class="fs-table-wrap">
            <table class="fs-table">
                <thead>
                    <tr>
                        <th class="fs-table__th fs-table__th--num">#</th>
                        <th class="fs-table__th fs-table__th--date">التاريخ</th>
                        <th class="fs-table__th fs-table__th--desc">البيان</th>
                        <th class="fs-table__th fs-table__th--money">مدين</th>
                        <th class="fs-table__th fs-table__th--money">دائن</th>
                        <th class="fs-table__th fs-table__th--balance">الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $runningBalance = 0;
                    $rowIndex = 0;
                    foreach ($movements as $m):
                        $rowIndex++;
                        $amount = (float)($m['amount'] ?? 0);
                        $isDebit = ($m['type'] ?? '') === 'مدين';
                        $isCredit = ($m['type'] ?? '') === 'دائن';
                        if ($isDebit) {
                            $runningBalance += $amount;
                        } elseif ($isCredit) {
                            $runningBalance -= $amount;
                        }
                    ?>
                    <tr class="fs-table__row">
                        <td class="fs-table__td fs-table__td--num en"><?= $rowIndex ?></td>
                        <td class="fs-table__td fs-table__td--date"><?= $isDateValid($m['date'] ?? null) ? '<span class="en">' . Html::encode(substr($m['date'], 0, 10)) . '</span>' : 'غير محدد' ?></td>
                        <td class="fs-table__td fs-table__td--desc"><?= Html::encode($m['description'] ?? '') ?><?php if (!empty($m['notes'])): ?> <span class="fs-table__note">(<?= Html::encode($m['notes']) ?>)</span><?php endif; ?></td>
                        <td class="fs-table__td fs-table__td--debit en"><?= $isDebit ? stNum($amount) : '' ?></td>
                        <td class="fs-table__td fs-table__td--credit en"><?= $isCredit ? stNum($amount) : '' ?></td>
                        <td class="fs-table__td fs-table__td--balance en"><?= stNum($runningBalance) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Summary (outside the table) -->
        <div class="fs-table-summary">
            <div class="fs-table-summary__item">
                <span class="fs-table-summary__label">إجمالي المدين</span>
                <span class="fs-table-summary__value fs-table-summary__value--debit en"><?= stNum($totalDebit) ?></span>
            </div>
            <div class="fs-table-summary__item">
                <span class="fs-table-summary__label">إجمالي الدائن</span>
                <span class="fs-table-summary__value fs-table-summary__value--credit en"><?= stNum($totalCredit) ?></span>
            </div>
            <div class="fs-table-summary__item fs-table-summary__item--final">
                <span class="fs-table-summary__label">الرصيد النهائي</span>
                <span class="fs-table-summary__value fs-table-summary__value--final en"><?= stNum($runningBalance) ?></span>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         5) VERIFICATION SECTION
         ═══════════════════════════════════════ -->
    <section class="fs-section">
        <h3 class="fs-section__title">تحقق من صحة هذا الكشف</h3>
        <div class="fs-verify">
            <div class="fs-verify__main">
                <div class="fs-verify__code-block">
                    <span class="fs-verify__code-label">رقم التحقق الفريد</span>
                    <span class="fs-verify__code-value en"><?= $verifyCode ?></span>
                </div>
                <div class="fs-verify__link-block">
                    <span class="fs-verify__link-label">رابط التحقق</span>
                    <a href="<?= Html::encode($verifyUrl) ?>" class="fs-verify__link en" target="_blank"><?= Html::encode($verifyUrl) ?></a>
                </div>
            </div>
            <div class="fs-verify__qr">
                <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR التحقق" />
            </div>
        </div>
        <div class="fs-verify__stamp">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            هذا الكشف موثق إلكترونياً عبر نظام جدل ERP ولا يحتاج توقيع.
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         6) FOOTER
         ═══════════════════════════════════════ -->
    <footer class="fs-footer">
        <div class="fs-footer__top">
            <strong><?= Html::encode($companyName) ?></strong>
            <?php if (!empty($companyPhone)): ?>
            <span class="fs-footer__sep">|</span>
            <span class="en"><?= Html::encode($companyPhone) ?></span>
            <?php endif; ?>
        </div>
        <div class="fs-footer__legal">
            <p><?= Html::encode($companyName) ?> مسؤولة عن صحة بيانات هذا الكشف حتى تاريخه.</p>
            <p>الشركة غير مسؤولة عن أي دفعات غير مدرج فيها اسم العميل الرباعي على خانة اسم المودع.</p>
            <?php if (!empty($compay_banks)): ?>
            <p>الشركة غير مسؤولة عن أي دفعة مدفوعة في أي حساب غير حسابها في <?= Html::encode($compay_banks) ?>.</p>
            <?php endif; ?>
        </div>
        <div class="fs-footer__copy en">&copy; <?= date('Y') ?> <?= Html::encode($companyName) ?></div>
    </footer>

</div>
