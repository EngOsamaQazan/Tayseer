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

if (!function_exists('stNum')) {
    function stNum($n) {
        if ($n === null || $n === '' || $n === '—' || $n === 'لا يوجد') return $n;
        if (!is_numeric($n)) return $n;
        return number_format((float) $n, 2, '.', ',');
    }
}
if (!function_exists('stNumInt')) {
    function stNumInt($n) {
        if ($n === null || $n === '') return $n;
        if (!is_numeric($n)) return $n;
        return number_format((int) $n, 0, '', ',');
    }
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
if (!$contractModel) {
    echo '<div style="text-align:center;padding:60px 20px;font-family:sans-serif;direction:rtl"><h2>العقد غير موجود</h2><p>رقم العقد المطلوب (' . (int)$contract_id . ') غير موجود في النظام.</p></div>';
    return;
}
$vb = \backend\modules\followUp\helper\ContractCalculations::fromView($contractModel->id);
$total = $vb ? $vb['totalDebt'] : (float)$contractModel->total_value;
$contractModel->total_value = $total;

$clientNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $clientInContract);
$guarantorNames = array_map(function ($c) {
    return \backend\modules\customers\models\Customers::findOne($c->customer_id)->name ?? '';
}, $guarantorInContract);

$paid_amount = $vb ? $vb['paid'] : 0;
$remaining_balance = $vb ? $vb['remaining'] : 0;

$lastIncomeDate = ContractInstallment::find()
    ->where(['contract_id' => $contract_id])->orderBy(['date' => SORT_DESC])->one();

// ─── Movements ───
$provider = new \yii\data\SqlDataProvider([
    'sql' => "SELECT 
                os_contracts.id,
                os_contracts.total_value as amount,
                'ثمن البضاعة' as description,
                os_contracts.Date_of_sale as date,
                'مدين' as type,
                '' as notes
              FROM os_contracts WHERE os_contracts.id = :cid1
              UNION ALL
              SELECT os_judiciary.id, os_judiciary.lawyer_cost as amount, 'اتعاب محاماه' as description,
                     os_judiciary.created_at as date, 'مدين' as type, '' as notes
              FROM os_judiciary WHERE os_judiciary.contract_id = :cid2
              UNION ALL
              SELECT os_expenses.id, os_expenses.amount, description, os_expenses.created_at AS date, 'مدين' as type, notes
              FROM os_expenses WHERE os_expenses.contract_id = :cid3
              UNION ALL
              SELECT os_income.id, os_income.amount, _by as description, os_income.date as date, 'دائن' as type, notes
              FROM os_income WHERE os_income.contract_id = :cid4
              UNION ALL
              SELECT os_contract_adjustments.id,
                     os_contract_adjustments.amount,
                     CASE os_contract_adjustments.type
                        WHEN 'discount'      THEN 'خصم تجاري'
                        WHEN 'write_off'     THEN 'شطب'
                        WHEN 'waiver'        THEN 'إعفاء'
                        WHEN 'free_discount' THEN 'خصم مجاني'
                        ELSE 'تسوية'
                     END as description,
                     os_contract_adjustments.created_at as date,
                     'دائن' as type,
                     COALESCE(os_contract_adjustments.reason, '') as notes
              FROM os_contract_adjustments
              WHERE os_contract_adjustments.contract_id = :cid5
                AND os_contract_adjustments.is_deleted = 0
              ORDER BY date",
    'params' => [
        ':cid1' => $contract_id, ':cid2' => $contract_id, ':cid3' => $contract_id,
        ':cid4' => $contract_id, ':cid5' => $contract_id,
    ],
    'totalCount' => 500,
    'pagination' => ['pageSize' => 500],
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

// ─── Verification (signed QR for public verify-statement) ───
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
$secret = Yii::$app->params['statementVerifySecret'] ?? 'tayseer-statement-verify-default';
$payload = $contract_id . '|' . $statementDate . '|' . $lastMovementDate;
$signature = hash_hmac('sha256', $payload, $secret);
$verifyCode = strtoupper(substr($signature, 0, 4) . '-' . substr($signature, 4, 4) . '-' . substr($signature, 8, 4));
$verifyUrl = Url::to(['/followUp/follow-up/verify-statement', 'c' => $contract_id, 'd' => $statementDate, 't' => $lastMovementDate, 's' => $signature], true);

// Generate a LARGE scannable QR (320x320 @ base64 PNG) — mirrors clearance-issued.
$qrImageSrc = null;
if (class_exists(\chillerlan\QRCode\QRCode::class)) {
    try {
        $opts = new \chillerlan\QRCode\QROptions([
            'version'          => 5,
            'outputType'       => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'         => \chillerlan\QRCode\QRCode::ECC_M,
            'scale'            => 8,
            'imageBase64'      => true,
            'imageTransparent' => false,
        ]);
        $qrImageSrc = (new \chillerlan\QRCode\QRCode($opts))->render($verifyUrl);
    } catch (\Throwable $e) {
        $qrImageSrc = null;
    }
}
if ($qrImageSrc === null) {
    $qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=8&data=' . urlencode($verifyUrl);
}

// ─── Risk Assessment ───
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

// ─── Totals ───
$totalDebit = 0;
$totalCredit = 0;
foreach ($movements as $m) {
    $amt = (float)($m['amount'] ?? 0);
    if (($m['type'] ?? '') === 'مدين') $totalDebit += $amt;
    if (($m['type'] ?? '') === 'دائن') $totalCredit += $amt;
}

// ─── Judiciary costs (optional) ───
$courtRow = null;
if ($contractModel->status === 'judiciary') {
    $courtRow = \backend\modules\judiciary\models\Judiciary::find()
        ->where(['contract_id' => $contractModel->id])->orderBy(['contract_id' => SORT_DESC])->one();
}

$pdfUrl = Url::to(['download-statement-pdf', 'contract_id' => $contract_id]);
?>

<style>
/* ═══════════════════════════════════════════════════════════
   كشف حساب — Professional Statement Layout v2
   (single-column, print-ready, scannable QR, no duplicates)
   ═══════════════════════════════════════════════════════════ */
.st {
    --c-primary:      #7A000C;
    --c-primary-dark: #4A0006;
    --c-success:      #0F7B3D;
    --c-success-soft: #eefbf3;
    --c-danger:       #B42318;
    --c-danger-soft:  #fef3f2;
    --c-text:         #1a1d21;
    --c-text-2:       #525866;
    --c-text-3:       #868c98;
    --c-bg:           #f4f5f7;
    --c-surface:      #ffffff;
    --c-border:       #e4e5e7;
    --c-border-2:     #f0f0f1;

    --font-ar: 'IBM Plex Sans Arabic','Cairo','Segoe UI',Tahoma,sans-serif;
    --font-en: 'Inter','Segoe UI',sans-serif;

    font-family: var(--font-ar);
    direction: rtl;
    color: var(--c-text);
    max-width: 920px;
    margin: 0 auto;
    padding: 24px 20px 40px;
    background: var(--c-bg);
    line-height: 1.65;
    -webkit-font-smoothing: antialiased;
}
.st .en {
    font-family: var(--font-en);
    font-variant-numeric: tabular-nums;
    direction: ltr;
    unicode-bidi: isolate;
    display: inline-block;
}

/* ── Document “paper” ── */
.st-doc {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 14px rgba(0,0,0,0.05);
}

/* ── Header ── */
.st-header {
    background: linear-gradient(135deg,#7A000C 0%,#5C0008 55%,#4A0006 100%);
    color: #fff;
    padding: 24px 30px 20px;
    position: relative;
}
.st-header::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 3px;
    background: linear-gradient(90deg,#B8860B 0%,#d4a640 50%,#B8860B 100%);
}
.st-header__row {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    flex-wrap: wrap;
}
.st-brand { display: flex; align-items: center; gap: 14px; }
.st-brand__logo {
    width: 50px; height: 50px; border-radius: 10px;
    background: rgba(255,255,255,0.12);
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.18);
}
.st-brand__title { font-size: 12px; opacity: 0.72; margin: 0 0 2px; font-weight: 500; letter-spacing: 0.02em; }
.st-brand__name  { font-size: 20px; font-weight: 700; margin: 0; line-height: 1.2; }

.st-risk {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 999px;
    font-weight: 700; font-size: 12px;
    border: 1.5px solid;
}
.st-risk--low      { background: rgba(16,185,129,0.15); color: #ecfdf5; border-color: rgba(167,243,208,0.5); }
.st-risk--med      { background: rgba(251,191,36,0.18); color: #fffbeb; border-color: rgba(253,224,71,0.5); }
.st-risk--high     { background: rgba(249,115,22,0.18); color: #fff7ed; border-color: rgba(251,146,60,0.5); }
.st-risk--critical { background: rgba(239,68,68,0.22); color: #fef2f2; border-color: rgba(252,165,165,0.5); }
.st-risk__dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.st-title     { text-align: center; font-size: 24px; font-weight: 700; margin: 18px 0 2px; }
.st-title-sub { text-align: center; font-size: 12px; opacity: 0.7; margin: 0; font-family: var(--font-en); }

.st-meta {
    margin-top: 18px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1px;
    background: rgba(255,255,255,0.12);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.14);
}
.st-meta__item {
    background: rgba(0,0,0,0.14);
    padding: 10px 14px;
    display: flex; flex-direction: column; gap: 2px;
    min-width: 0;
}
.st-meta__label { font-size: 11px; opacity: 0.65; }
.st-meta__value { font-size: 14px; font-weight: 700; word-break: break-word; }

/* ── Body ── */
.st-body { padding: 26px 30px; }
.st-section { margin-bottom: 24px; }
.st-section:last-child { margin-bottom: 0; }
.st-section__title {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 700;
    color: var(--c-primary);
    margin: 0 0 12px;
    padding-bottom: 8px;
    border-bottom: 1.5px solid var(--c-border-2);
}

/* ── Money cards ── */
.st-money {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.st-money__cell {
    padding: 14px 14px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid var(--c-border);
    background: #fff;
}
.st-money__cell--paid   { background: #f0fdf4; border-color: #bbf7d0; }
.st-money__cell--remain { background: #fef2f2; border-color: #fecaca; }
.st-money__label  { font-size: 12px; color: var(--c-text-2); margin: 0 0 4px; }
.st-money__amount { font-size: 22px; font-weight: 800; color: var(--c-text); margin: 0; }
.st-money__cell--paid   .st-money__amount { color: var(--c-success); }
.st-money__cell--remain .st-money__amount { color: var(--c-danger); }
.st-money__cur    { font-size: 11px; color: var(--c-text-3); margin-top: 2px; }

/* ── Progress bar ── */
.st-progress {
    margin-top: 14px;
    padding: 14px 16px;
    background: #fafbfc;
    border: 1px solid var(--c-border);
    border-radius: 12px;
}
.st-progress__top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.st-progress__label   { font-size: 13px; color: var(--c-text-2); font-weight: 600; }
.st-progress__percent { font-size: 18px; font-weight: 800; color: var(--c-primary); font-family: var(--font-en); }
.st-progress__bar  { height: 10px; background: var(--c-border-2); border-radius: 999px; overflow: hidden; }
.st-progress__fill { height: 100%; background: linear-gradient(90deg,#0F7B3D 0%,#10b981 100%); border-radius: 999px; transition: width 0.4s; }
.st-progress__text { font-size: 12px; color: var(--c-text-3); margin: 6px 0 0; }

/* ── Info grid ── */
.st-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.st-card {
    background: #fafbfc;
    border: 1px solid var(--c-border);
    border-radius: 12px;
    padding: 14px 16px;
}
.st-card__title { font-size: 12px; font-weight: 700; color: var(--c-text-3); margin: 0 0 10px; letter-spacing: 0.02em; text-transform: uppercase; }
.st-kv { display: flex; justify-content: space-between; align-items: flex-start; padding: 6px 0; border-bottom: 1px dashed var(--c-border-2); gap: 12px; }
.st-kv:last-child { border-bottom: 0; }
.st-kv__k { color: var(--c-text-2); font-size: 13px; white-space: nowrap; }
.st-kv__v { color: var(--c-text); font-size: 13px; font-weight: 600; text-align: left; word-break: break-word; }

/* ── Movements table ── */
.st-table-wrap { overflow-x: auto; border: 1px solid var(--c-border); border-radius: 12px; background: #fff; }
.st-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.st-table thead th {
    background: #f8f9fa; color: var(--c-text-2); font-weight: 700; font-size: 12px;
    padding: 10px 12px; text-align: right; border-bottom: 2px solid var(--c-border); white-space: nowrap;
    letter-spacing: 0.02em; text-transform: uppercase;
}
.st-table tbody td { padding: 10px 12px; border-bottom: 1px solid var(--c-border-2); vertical-align: middle; }
.st-table tbody tr:last-child td { border-bottom: 0; }
.st-table tbody tr:nth-child(even) td { background: #fafbfc; }
.st-table tbody tr:hover td { background: #f4f5f7; }
.st-table .num { text-align: center; color: var(--c-text-3); font-family: var(--font-en); width: 44px; }
.st-table .date { white-space: nowrap; font-family: var(--font-en); }
.st-table .debit  { color: var(--c-danger);  font-weight: 700; font-family: var(--font-en); text-align: left; white-space: nowrap; }
.st-table .credit { color: var(--c-success); font-weight: 700; font-family: var(--font-en); text-align: left; white-space: nowrap; }
.st-table .balance{ color: var(--c-text); font-weight: 700; font-family: var(--font-en); text-align: left; white-space: nowrap; }
.st-note { color: var(--c-text-3); font-size: 11px; }

.st-table__foot {
    margin-top: 12px;
    display: grid; grid-template-columns: repeat(3,1fr);
    gap: 10px;
}
.st-table__foot-cell {
    padding: 12px 14px;
    border: 1px solid var(--c-border);
    border-radius: 10px;
    text-align: center;
    background: #fafbfc;
}
.st-table__foot-cell--debit  { background: var(--c-danger-soft); border-color: #fecaca; }
.st-table__foot-cell--credit { background: var(--c-success-soft); border-color: #bbf7d0; }
.st-table__foot-cell--final  { background: #eef2ff; border-color: #c7d2fe; }
.st-table__foot-label  { display: block; font-size: 11px; color: var(--c-text-3); margin-bottom: 4px; font-weight: 500; }
.st-table__foot-value  { display: block; font-size: 18px; font-weight: 800; font-family: var(--font-en); }
.st-table__foot-cell--debit  .st-table__foot-value { color: var(--c-danger); }
.st-table__foot-cell--credit .st-table__foot-value { color: var(--c-success); }
.st-table__foot-cell--final  .st-table__foot-value { color: var(--c-primary); }

/* ── Verification block ── */
.st-verify {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 22px;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg,#ffffff 0%,#f9f9fb 100%);
    border: 1.5px solid var(--c-border);
    border-radius: 14px;
}
.st-verify__qr {
    width: 200px; height: 200px;
    background: #fff;
    border: 1px solid var(--c-border);
    border-radius: 12px;
    padding: 8px;
    display: flex; align-items: center; justify-content: center;
}
.st-verify__qr img { width: 100%; height: 100%; object-fit: contain; display: block; }
.st-verify__hint { font-size: 12px; color: var(--c-text-3); margin: 0 0 6px; }
.st-verify__code {
    font-size: 20px; font-weight: 800; letter-spacing: 0.06em;
    color: var(--c-primary); margin-bottom: 14px;
    font-family: var(--font-en); direction: ltr; unicode-bidi: isolate;
}
.st-verify__row { display: flex; gap: 8px; font-size: 13px; margin-bottom: 6px; }
.st-verify__row b { color: var(--c-text-3); font-weight: 500; flex-shrink: 0; }
.st-verify__url {
    display: inline-block;
    padding: 6px 10px; border-radius: 6px;
    background: #f4f5f7; border: 1px solid var(--c-border);
    color: var(--c-primary); text-decoration: none;
    font-family: var(--font-en); font-size: 12px;
    word-break: break-all; line-height: 1.4; max-width: 100%;
}
.st-verify__url:hover { background: #eef0f3; }

.st-trust {
    margin-top: 12px;
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px;
    background: var(--c-success-soft);
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    color: var(--c-success);
    font-weight: 600; font-size: 13px;
}

/* ── Footer ── */
.st-footer {
    padding: 18px 30px 22px;
    border-top: 1px solid var(--c-border-2);
    text-align: center;
    color: var(--c-text-3);
    font-size: 12px;
    background: #fafafb;
}
.st-footer__brand { color: var(--c-text); font-weight: 700; font-size: 13px; margin: 0 0 6px; }
.st-footer__note  { margin: 2px 0; }

/* ── Actions bar ── */
.st-actions {
    margin-top: 20px;
    padding: 14px 18px;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    display: flex; flex-wrap: wrap; gap: 10px;
    justify-content: flex-end; align-items: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.st-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 10px;
    font-family: var(--font-ar); font-weight: 600; font-size: 14px;
    cursor: pointer; border: 1px solid transparent; text-decoration: none;
    transition: all 0.15s; white-space: nowrap;
}
.st-btn--primary { background: var(--c-primary); color: #fff !important; }
.st-btn--primary:hover { background: var(--c-primary-dark); }
.st-btn--outline { background: #fff; color: var(--c-text) !important; border-color: var(--c-border); }
.st-btn--outline:hover { background: #f4f5f7; }

/* ── Responsive ── */
@media (max-width: 720px) {
    .st { padding: 16px 12px 32px; }
    .st-header { padding: 20px 18px; }
    .st-body   { padding: 22px 18px; }
    .st-footer { padding: 16px 18px 20px; }
    .st-title  { font-size: 20px; }
    .st-meta   { grid-template-columns: 1fr; }
    .st-money  { grid-template-columns: 1fr; }
    .st-grid   { grid-template-columns: 1fr; }
    .st-table  { font-size: 12px; }
    .st-table thead th, .st-table tbody td { padding: 8px 8px; }
    .st-table__foot { grid-template-columns: 1fr; }
    .st-verify { grid-template-columns: 1fr; justify-items: center; text-align: center; }
    .st-verify__qr { width: 220px; height: 220px; margin: 0 auto; }
    .st-verify__row { justify-content: center; flex-wrap: wrap; }
}

/* ── Print ── */
@media print {
    body, html { background: #fff !important; }
    .st-actions { display: none !important; }
    .st { max-width: 100%; padding: 0; background: #fff; }
    .st-doc { border: 1px solid #ddd; box-shadow: none; border-radius: 0; }
    .st-header, .st-risk, .st-money__cell--paid, .st-money__cell--remain,
    .st-table__foot-cell--debit, .st-table__foot-cell--credit, .st-table__foot-cell--final {
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .st-section { page-break-inside: avoid; }
    .st-table tbody tr { page-break-inside: avoid; }
}
</style>

<div class="st" id="financial-statement">

    <article class="st-doc">

        <header class="st-header">
            <div class="st-header__row">
                <div class="st-brand">
                    <div class="st-brand__logo">
                        <svg width="26" height="26" viewBox="0 0 36 36" fill="none">
                            <path d="M10 26V13l8-4 8 4v13l-8 4-8-4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M10 13l8 4 8-4M18 17v13" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <p class="st-brand__title">المُصدِر</p>
                        <h1 class="st-brand__name"><?= Html::encode($companyName) ?></h1>
                    </div>
                </div>
                <span class="st-risk st-risk--<?= $riskLevel ?>">
                    <span class="st-risk__dot"></span>
                    تصنيف الخطر: <?= Html::encode($riskLabel) ?>
                </span>
            </div>

            <h2 class="st-title">كشف حساب عميل</h2>
            <p class="st-title-sub">Customer Account Statement</p>

            <div class="st-meta">
                <div class="st-meta__item">
                    <span class="st-meta__label">رقم العقد</span>
                    <span class="st-meta__value en"><?= (int) $contract_id ?></span>
                </div>
                <div class="st-meta__item">
                    <span class="st-meta__label">تاريخ الإصدار</span>
                    <span class="st-meta__value en"><?= Html::encode($statementDate) ?></span>
                </div>
                <div class="st-meta__item">
                    <span class="st-meta__label">آخر حركة</span>
                    <span class="st-meta__value en"><?= Html::encode($lastMovementDate) ?></span>
                </div>
            </div>
        </header>

        <div class="st-body">

            <!-- 1) الملخص المالي -->
            <section class="st-section">
                <h3 class="st-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    الملخص المالي
                </h3>
                <div class="st-money">
                    <div class="st-money__cell">
                        <p class="st-money__label">إجمالي العقد</p>
                        <p class="st-money__amount en"><?= stNum($contractModel->total_value) ?></p>
                        <p class="st-money__cur">د.أ</p>
                    </div>
                    <div class="st-money__cell st-money__cell--paid">
                        <p class="st-money__label">المدفوع</p>
                        <p class="st-money__amount en"><?= stNum($paid_amount) ?></p>
                        <p class="st-money__cur">د.أ</p>
                    </div>
                    <div class="st-money__cell st-money__cell--remain">
                        <p class="st-money__label">المتبقي</p>
                        <p class="st-money__amount en"><?= stNum($remaining_balance) ?></p>
                        <p class="st-money__cur">د.أ</p>
                    </div>
                </div>

                <div class="st-progress">
                    <div class="st-progress__top">
                        <span class="st-progress__label">نسبة السداد</span>
                        <span class="st-progress__percent"><?= $paymentRate ?>%</span>
                    </div>
                    <div class="st-progress__bar"><div class="st-progress__fill" style="width: <?= $paymentRate ?>%"></div></div>
                    <p class="st-progress__text">تم سداد <strong class="en"><?= stNum($paid_amount) ?></strong> من أصل <strong class="en"><?= stNum($contractModel->total_value) ?></strong> دينار</p>
                </div>
            </section>

            <!-- 2) بيانات العقد -->
            <section class="st-section">
                <h3 class="st-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    بيانات العقد
                </h3>
                <div class="st-grid">
                    <div class="st-card">
                        <p class="st-card__title">الأطراف</p>
                        <div class="st-kv"><span class="st-kv__k">العميل</span><span class="st-kv__v"><?= Html::encode(implode(' ، ', $clientNames)) ?: '—' ?></span></div>
                        <div class="st-kv"><span class="st-kv__k">الكفلاء</span><span class="st-kv__v"><?= Html::encode(implode(' ، ', $guarantorNames) ?: 'لا يوجد') ?></span></div>
                        <div class="st-kv"><span class="st-kv__k">رقم العقد</span><span class="st-kv__v en"><?= (int) $contract_id ?></span></div>
                    </div>
                    <div class="st-card">
                        <p class="st-card__title">التواريخ والأقساط</p>
                        <div class="st-kv"><span class="st-kv__k">تاريخ البيع</span><span class="st-kv__v en"><?= Html::encode($contractModel->Date_of_sale ?? '—') ?></span></div>
                        <div class="st-kv"><span class="st-kv__k">أول قسط</span><span class="st-kv__v en"><?= Html::encode($contractModel->first_installment_date ?? '—') ?></span></div>
                        <div class="st-kv"><span class="st-kv__k">آخر دفعة</span><span class="st-kv__v en"><?= Html::encode($lastIncomeDate ? $lastIncomeDate->date : 'لا يوجد') ?></span></div>
                        <div class="st-kv"><span class="st-kv__k">القسط الشهري</span><span class="st-kv__v en"><?= stNum($contractModel->monthly_installment_value) ?></span></div>
                        <?php if ($courtRow): ?>
                        <div class="st-kv"><span class="st-kv__k">رسوم المحاكم</span><span class="st-kv__v en"><?= stNum($courtRow->case_cost) ?></span></div>
                        <div class="st-kv"><span class="st-kv__k">أتعاب المحامي</span><span class="st-kv__v en"><?= stNum($courtRow->lawyer_cost) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- 3) الحركات المالية -->
            <section class="st-section">
                <h3 class="st-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    الحركات المالية
                </h3>
                <div class="st-table-wrap">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="num">#</th>
                                <th>التاريخ</th>
                                <th>البيان</th>
                                <th style="text-align:left">مدين</th>
                                <th style="text-align:left">دائن</th>
                                <th style="text-align:left">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $runningBalance = 0;
                            $rowIndex = 0;
                            foreach ($movements as $m):
                                $rowIndex++;
                                $amount = (float)($m['amount'] ?? 0);
                                $isDebit  = ($m['type'] ?? '') === 'مدين';
                                $isCredit = ($m['type'] ?? '') === 'دائن';
                                if ($isDebit)  $runningBalance += $amount;
                                if ($isCredit) $runningBalance -= $amount;
                            ?>
                            <tr>
                                <td class="num"><?= $rowIndex ?></td>
                                <td class="date"><?= $isDateValid($m['date'] ?? null) ? Html::encode(substr($m['date'], 0, 10)) : '<span style="color:#b0b5bf">غير محدد</span>' ?></td>
                                <td>
                                    <?= Html::encode($m['description'] ?? '') ?>
                                    <?php if (!empty($m['notes'])): ?>
                                    <span class="st-note">(<?= Html::encode($m['notes']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="debit"><?= $isDebit ? stNum($amount) : '' ?></td>
                                <td class="credit"><?= $isCredit ? stNum($amount) : '' ?></td>
                                <td class="balance"><?= stNum($runningBalance) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($movements)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:24px;color:#868c98">لا توجد حركات مالية مسجلة.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="st-table__foot">
                    <div class="st-table__foot-cell st-table__foot-cell--debit">
                        <span class="st-table__foot-label">إجمالي المدين</span>
                        <span class="st-table__foot-value"><?= stNum($totalDebit) ?></span>
                    </div>
                    <div class="st-table__foot-cell st-table__foot-cell--credit">
                        <span class="st-table__foot-label">إجمالي الدائن</span>
                        <span class="st-table__foot-value"><?= stNum($totalCredit) ?></span>
                    </div>
                    <div class="st-table__foot-cell st-table__foot-cell--final">
                        <span class="st-table__foot-label">الرصيد النهائي</span>
                        <span class="st-table__foot-value"><?= stNum($runningBalance) ?></span>
                    </div>
                </div>
            </section>

            <!-- 4) التحقق الإلكتروني -->
            <section class="st-section">
                <h3 class="st-section__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    التحقق الإلكتروني
                </h3>
                <div class="st-verify">
                    <div class="st-verify__qr">
                        <img src="<?= (strpos($qrImageSrc, 'data:') === 0 ? $qrImageSrc : Html::encode($qrImageSrc)) ?>" alt="QR Verification" />
                    </div>
                    <div>
                        <p class="st-verify__hint">رقم التحقق الفريد</p>
                        <div class="st-verify__code"><?= Html::encode($verifyCode) ?></div>
                        <div class="st-verify__row"><b>رابط التحقق:</b></div>
                        <a href="<?= Html::encode($verifyUrl) ?>" class="st-verify__url en" target="_blank" rel="noopener"><?= Html::encode($verifyUrl) ?></a>
                    </div>
                </div>
                <div class="st-trust">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    هذا الكشف موثّق إلكترونياً عبر نظام تيسير ERP — لا يحتاج توقيع يدوي.
                </div>
            </section>

        </div>

        <footer class="st-footer">
            <p class="st-footer__brand"><?= Html::encode($companyName) ?><?php if (!empty($companyPhone)): ?> <span style="opacity:.5">|</span> <span class="en"><?= Html::encode($companyPhone) ?></span><?php endif; ?></p>
            <p class="st-footer__note"><?= Html::encode($companyName) ?> مسؤولة عن صحة بيانات هذا الكشف حتى تاريخه.</p>
            <p class="st-footer__note">الشركة غير مسؤولة عن أي دفعات غير مدرج فيها اسم العميل الرباعي على خانة اسم المودع.</p>
            <?php if (!empty($compay_banks)): ?>
            <p class="st-footer__note">الشركة غير مسؤولة عن أي دفعة مدفوعة في أي حساب غير حسابها في <?= Html::encode($compay_banks) ?>.</p>
            <?php endif; ?>
            <p class="st-footer__note en" style="margin-top:6px">&copy; <?= date('Y') ?> <?= Html::encode($companyName) ?></p>
        </footer>

    </article>

    <div class="st-actions">
        <a href="<?= Html::encode($pdfUrl) ?>" class="st-btn st-btn--primary" target="_blank" rel="noopener">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            تصدير PDF
        </a>
        <button type="button" class="st-btn st-btn--outline" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            طباعة
        </button>
    </div>

</div>
