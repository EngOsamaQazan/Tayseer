<?php

use yii\helpers\Html;

/* @var $data array */

/*
 * mpdf-friendly HTML (no flex/grid, no CSS variables, only classic CSS).
 * Uses tables for layout and inline styles where needed.
 * xbriyaz for Arabic, dejavusans for Latin/numeric blocks.
 */

$contractId       = (int)   ($data['contractId']       ?? 0);
$companyName      = (string)($data['companyName']      ?? '');
$companyPhone     = (string)($data['companyPhone']     ?? '');
$companyBanks     = (string)($data['companyBanks']     ?? '');
$clientNames      = (array) ($data['clientNames']      ?? []);
$guarantorNames   = (array) ($data['guarantorNames']   ?? []);
$totalValue       =         ($data['totalValue']       ?? 0);
$paidAmount       =         ($data['paidAmount']       ?? 0);
$remainingBalance =         ($data['remainingBalance'] ?? 0);
$dateSale         =         ($data['dateSale']         ?? '—');
$firstInstDate    =         ($data['firstInstDate']    ?? '—');
$lastIncomeDate   =         ($data['lastIncomeDate']   ?? null);
$monthlyInst      =         ($data['monthlyInst']      ?? null);
$courtCaseCost    =         ($data['courtCaseCost']    ?? null);
$courtLawyerCost  =         ($data['courtLawyerCost']  ?? null);
$movements        = (array) ($data['movements']        ?? []);
$statementDate    = (string)($data['statementDate']    ?? date('Y-m-d'));
$lastMovementDate = (string)($data['lastMovementDate'] ?? $statementDate);
$signature        = (string)($data['signature']        ?? '');
$verifyUrl        = (string)($data['verifyUrl']        ?? '');

$sigShort = strtoupper(
    substr($signature, 0, 4) . '-' .
    substr($signature, 4, 4) . '-' .
    substr($signature, 8, 4)
);
$sigLong = substr($signature, 0, 48);

$num = function ($n) {
    if ($n === null || $n === '' || !is_numeric($n)) return $n ?: '—';
    return number_format((float) $n, 2, '.', ',');
};
$enNum = function ($s) {
    return '<span style="font-family:dejavusans;direction:ltr;unicode-bidi:embed">' . $s . '</span>';
};

$isDateValid = function ($date) {
    if ($date === null || $date === '') return false;
    $str = is_string($date) ? substr($date, 0, 10) : date('Y-m-d', strtotime($date));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return false;
    $y = (int) substr($str, 0, 4);
    return $y >= 1990 && $y <= 2030;
};

// ── Totals ──
$totalDebit = 0; $totalCredit = 0;
foreach ($movements as $m) {
    $amt = (float)($m['amount'] ?? 0);
    if (($m['type'] ?? '') === 'مدين')  $totalDebit  += $amt;
    if (($m['type'] ?? '') === 'دائن') $totalCredit += $amt;
}
$paymentRate = ($totalValue > 0) ? min(100, round(((float)$paidAmount / (float)$totalValue) * 100, 1)) : 0;

// ── QR ──
$qrDataUrl = null;
if (class_exists(\chillerlan\QRCode\QRCode::class)) {
    try {
        $opts = new \chillerlan\QRCode\QROptions([
            'version'          => 5,
            'outputType'       => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'         => \chillerlan\QRCode\QRCode::ECC_M,
            'scale'            => 7,
            'imageBase64'      => true,
            'imageTransparent' => false,
        ]);
        $qrDataUrl = (new \chillerlan\QRCode\QRCode($opts))->render($verifyUrl);
    } catch (\Throwable $e) {
        $qrDataUrl = null;
    }
}
if ($qrDataUrl === null) {
    $qrDataUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=8&data=' . urlencode($verifyUrl);
}
?>
<style>
body { font-family: xbriyaz; color: #1a1d21; font-size: 10.5pt; line-height: 1.55; }

.h-wrap { background: #7A000C; color: #fff; padding: 14px 18px 12px; border-radius: 4px; margin-bottom: 14px; }
.h-brand-name { font-size: 17pt; font-weight: bold; margin: 0; }
.h-brand-sub  { font-size: 9pt; color: #f4cfd3; margin: 0; }
.h-title { font-size: 18pt; font-weight: bold; text-align: center; margin: 10px 0 2px; }
.h-title-sub { font-size: 9pt; color: #f4cfd3; text-align: center; margin: 0 0 8px; font-family: dejavusans; }

.meta-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; background: rgba(0,0,0,0.18); border-radius: 4px; }
.meta-tbl td { padding: 8px 10px; width: 33.33%; vertical-align: top; }
.meta-lbl { font-size: 8.5pt; color: #f0c0c4; display: block; margin-bottom: 2px; }
.meta-val { font-size: 11pt; font-weight: bold; color: #fff; }

.sec { margin-bottom: 14px; }
.sec-title {
    font-size: 11pt; font-weight: bold; color: #7A000C;
    margin: 0 0 8px; padding-bottom: 5px;
    border-bottom: 1.5px solid #f0f0f1;
}

/* Money cards */
.money-tbl { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
.money-tbl td { width: 33.33%; padding: 10px 8px; border: 1px solid #e4e5e7; border-radius: 4px; text-align: center; background: #fff; vertical-align: middle; }
.money-lbl { font-size: 9pt; color: #525866; margin: 0; }
.money-val { font-size: 14pt; font-weight: bold; margin: 3px 0 0; font-family: dejavusans; direction: ltr; unicode-bidi: embed; }
.money-cur { font-size: 8.5pt; color: #868c98; margin: 2px 0 0; }
.money-paid   { background: #f0fdf4 !important; border-color: #bbf7d0 !important; }
.money-paid   .money-val { color: #0F7B3D; }
.money-remain { background: #fef2f2 !important; border-color: #fecaca !important; }
.money-remain .money-val { color: #B42318; }

/* Progress bar (simple cell-based fake) */
.prog-wrap { padding: 10px 14px; background: #fafbfc; border: 1px solid #e4e5e7; border-radius: 4px; margin-top: 4px; }
.prog-top { width: 100%; }
.prog-top td { font-size: 10pt; }
.prog-top .prog-pct { font-family: dejavusans; font-weight: bold; color: #7A000C; font-size: 12pt; text-align: left; direction: ltr; }
.prog-track { width: 100%; height: 10px; background: #f0f0f1; border: 1px solid #e4e5e7; border-radius: 6px; margin-top: 6px; padding: 0; }
.prog-fill  { height: 10px; background: #0F7B3D; border-radius: 6px; }
.prog-note  { font-size: 8.5pt; color: #868c98; margin: 4px 0 0; }

/* Info */
.info-tbl { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
.info-tbl > tbody > tr > td { width: 50%; padding: 10px 12px; border: 1px solid #e4e5e7; border-radius: 4px; background: #fafbfc; vertical-align: top; }
.info-title { font-size: 9pt; color: #868c98; font-weight: bold; margin: 0 0 6px; }
.kv-tbl { width: 100%; border-collapse: collapse; }
.kv-tbl td { padding: 4px 0; font-size: 10pt; border-bottom: 1px dashed #f0f0f1; }
.kv-tbl tr:last-child td { border-bottom: 0; }
.kv-k { color: #525866; width: 40%; }
.kv-v { color: #1a1d21; font-weight: bold; text-align: left; }

/* Movements table */
.mv-tbl { width: 100%; border-collapse: collapse; margin-top: 4px; }
.mv-tbl thead th {
    background: #f4f5f7; color: #525866; font-weight: bold; font-size: 9pt;
    padding: 7px 6px; text-align: right; border: 1px solid #e4e5e7;
}
.mv-tbl tbody td {
    padding: 6px; font-size: 9.5pt; border: 1px solid #e4e5e7; vertical-align: middle;
}
.mv-tbl tbody tr:nth-child(even) td { background: #fafbfc; }
.mv-num    { text-align: center; color: #868c98; font-family: dejavusans; width: 24px; }
.mv-date   { font-family: dejavusans; white-space: nowrap; }
.mv-debit  { color: #B42318; font-weight: bold; font-family: dejavusans; text-align: left; white-space: nowrap; }
.mv-credit { color: #0F7B3D; font-weight: bold; font-family: dejavusans; text-align: left; white-space: nowrap; }
.mv-bal    { color: #1a1d21; font-weight: bold; font-family: dejavusans; text-align: left; white-space: nowrap; }
.mv-note   { color: #868c98; font-size: 8pt; }

.totals-tbl { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-top: 8px; }
.totals-tbl td { width: 33.33%; padding: 10px 8px; border-radius: 4px; text-align: center; vertical-align: middle; }
.tot-lbl { font-size: 8.5pt; color: #525866; margin: 0; }
.tot-val { font-size: 12pt; font-weight: bold; margin: 2px 0 0; font-family: dejavusans; direction: ltr; unicode-bidi: embed; }
.tot-deb { background: #fef2f2; border: 1px solid #fecaca; }
.tot-deb .tot-val { color: #B42318; }
.tot-crd { background: #f0fdf4; border: 1px solid #bbf7d0; }
.tot-crd .tot-val { color: #0F7B3D; }
.tot-fin { background: #eef2ff; border: 1px solid #c7d2fe; }
.tot-fin .tot-val { color: #7A000C; }

/* Verify */
.verify-tbl { width: 100%; border-collapse: separate; border-spacing: 0; border: 1.5px solid #e4e5e7; border-radius: 4px; background: #fbfbfc; }
.verify-tbl td { vertical-align: middle; padding: 14px; }
.qr-cell { width: 170px; text-align: center; }
.qr-cell img { width: 150px; height: 150px; }

.v-hint { font-size: 9pt; color: #868c98; margin: 0 0 4px; }
.v-code { font-size: 15pt; font-weight: bold; color: #7A000C; letter-spacing: 1px; margin: 0 0 10px; font-family: dejavusans; direction: ltr; unicode-bidi: embed; }
.v-row { font-size: 10pt; margin-bottom: 4px; }
.v-row b { color: #868c98; font-weight: normal; }
.v-url { font-family: dejavusans; font-size: 8pt; color: #7A000C; word-wrap: break-word; display: block; padding: 4px 6px; background: #f4f5f7; border: 1px solid #e4e5e7; border-radius: 3px; margin-top: 4px; }

.trust { margin-top: 8px; padding: 8px 12px; background: #eefbf3; border: 1px solid #bbf7d0; border-radius: 4px; color: #0F7B3D; font-weight: bold; font-size: 9.5pt; text-align: center; }

.footer { margin-top: 12px; padding-top: 10px; border-top: 1px solid #e4e5e7; text-align: center; color: #868c98; font-size: 8.5pt; }
.footer b { color: #1a1d21; display: block; margin-bottom: 4px; font-size: 10pt; }
.footer p { margin: 2px 0; }

.sig-line { margin-top: 6px; font-family: dejavusans; font-size: 7pt; color: #b0b5bf; text-align: center; direction: ltr; }
</style>

<div class="h-wrap">
    <table width="100%"><tr>
        <td style="width:70%">
            <p class="h-brand-sub">المُصدِر</p>
            <p class="h-brand-name"><?= Html::encode($companyName) ?></p>
        </td>
        <td style="width:30%;text-align:left">
            <span style="background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:12px;font-size:9pt;font-weight:bold">كشف موثّق</span>
        </td>
    </tr></table>

    <div class="h-title">كشف حساب عميل</div>
    <div class="h-title-sub">Customer Account Statement</div>

    <table class="meta-tbl">
        <tr>
            <td>
                <span class="meta-lbl">رقم العقد</span>
                <span class="meta-val"><?= $enNum($contractId) ?></span>
            </td>
            <td>
                <span class="meta-lbl">تاريخ الإصدار</span>
                <span class="meta-val"><?= $enNum(Html::encode($statementDate)) ?></span>
            </td>
            <td>
                <span class="meta-lbl">آخر حركة</span>
                <span class="meta-val"><?= $enNum(Html::encode($lastMovementDate)) ?></span>
            </td>
        </tr>
    </table>
</div>

<!-- Financial summary -->
<div class="sec">
    <h3 class="sec-title">الملخص المالي</h3>
    <table class="money-tbl">
        <tr>
            <td>
                <p class="money-lbl">إجمالي العقد</p>
                <p class="money-val"><?= $num($totalValue) ?></p>
                <p class="money-cur">د.أ</p>
            </td>
            <td class="money-paid">
                <p class="money-lbl">المدفوع</p>
                <p class="money-val"><?= $num($paidAmount) ?></p>
                <p class="money-cur">د.أ</p>
            </td>
            <td class="money-remain">
                <p class="money-lbl">المتبقي</p>
                <p class="money-val"><?= $num($remainingBalance) ?></p>
                <p class="money-cur">د.أ</p>
            </td>
        </tr>
    </table>

    <div class="prog-wrap">
        <table class="prog-top"><tr>
            <td style="width:50%">نسبة السداد</td>
            <td class="prog-pct"><?= $enNum($paymentRate . '%') ?></td>
        </tr></table>
        <table class="prog-track"><tr>
            <td class="prog-fill" style="width:<?= $paymentRate ?>%"></td>
            <?php if ($paymentRate < 100): ?><td style="width:<?= 100 - $paymentRate ?>%"></td><?php endif; ?>
        </tr></table>
        <p class="prog-note">تم سداد <?= $enNum($num($paidAmount)) ?> من أصل <?= $enNum($num($totalValue)) ?> دينار</p>
    </div>
</div>

<!-- Contract info -->
<div class="sec">
    <h3 class="sec-title">بيانات العقد</h3>
    <table class="info-tbl">
        <tr>
            <td>
                <p class="info-title">الأطراف</p>
                <table class="kv-tbl">
                    <tr><td class="kv-k">العميل</td><td class="kv-v"><?= Html::encode(implode(' ، ', (array) $clientNames) ?: '—') ?></td></tr>
                    <tr><td class="kv-k">الكفلاء</td><td class="kv-v"><?= Html::encode(implode(' ، ', (array) $guarantorNames) ?: 'لا يوجد') ?></td></tr>
                    <tr><td class="kv-k">رقم العقد</td><td class="kv-v"><?= $enNum($contractId) ?></td></tr>
                </table>
            </td>
            <td>
                <p class="info-title">التواريخ والأقساط</p>
                <table class="kv-tbl">
                    <tr><td class="kv-k">تاريخ البيع</td><td class="kv-v"><?= $enNum(Html::encode($dateSale)) ?></td></tr>
                    <tr><td class="kv-k">أول قسط</td><td class="kv-v"><?= $enNum(Html::encode($firstInstDate)) ?></td></tr>
                    <tr><td class="kv-k">آخر دفعة</td><td class="kv-v"><?= $enNum(Html::encode($lastIncomeDate ?: 'لا يوجد')) ?></td></tr>
                    <tr><td class="kv-k">القسط الشهري</td><td class="kv-v"><?= $enNum($num($monthlyInst)) ?></td></tr>
                    <?php if ($courtCaseCost !== null): ?>
                    <tr><td class="kv-k">رسوم المحاكم</td><td class="kv-v"><?= $enNum($num($courtCaseCost)) ?></td></tr>
                    <tr><td class="kv-k">أتعاب المحامي</td><td class="kv-v"><?= $enNum($num($courtLawyerCost)) ?></td></tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- Movements -->
<div class="sec">
    <h3 class="sec-title">الحركات المالية</h3>
    <table class="mv-tbl">
        <thead>
            <tr>
                <th style="width:24px">#</th>
                <th style="width:80px">التاريخ</th>
                <th>البيان</th>
                <th style="width:70px;text-align:left">مدين</th>
                <th style="width:70px;text-align:left">دائن</th>
                <th style="width:78px;text-align:left">الرصيد</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $runningBalance = 0;
            $rowIndex = 0;
            if (empty($movements)): ?>
                <tr><td colspan="6" style="text-align:center;padding:14px;color:#868c98">لا توجد حركات مالية مسجلة.</td></tr>
            <?php else:
                foreach ($movements as $m):
                    $rowIndex++;
                    $amount = (float)($m['amount'] ?? 0);
                    $isDebit  = ($m['type'] ?? '') === 'مدين';
                    $isCredit = ($m['type'] ?? '') === 'دائن';
                    if ($isDebit)  $runningBalance += $amount;
                    if ($isCredit) $runningBalance -= $amount;
                    $dateText = $isDateValid($m['date'] ?? null)
                        ? substr($m['date'], 0, 10)
                        : 'غير محدد';
            ?>
            <tr>
                <td class="mv-num"><?= $rowIndex ?></td>
                <td class="mv-date"><?= Html::encode($dateText) ?></td>
                <td>
                    <?= Html::encode($m['description'] ?? '') ?>
                    <?php if (!empty($m['notes'])): ?>
                    <span class="mv-note">(<?= Html::encode($m['notes']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="mv-debit"><?= $isDebit  ? $num($amount) : '' ?></td>
                <td class="mv-credit"><?= $isCredit ? $num($amount) : '' ?></td>
                <td class="mv-bal"><?= $num($runningBalance) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <table class="totals-tbl">
        <tr>
            <td class="tot-deb">
                <p class="tot-lbl">إجمالي المدين</p>
                <p class="tot-val"><?= $num($totalDebit) ?></p>
            </td>
            <td class="tot-crd">
                <p class="tot-lbl">إجمالي الدائن</p>
                <p class="tot-val"><?= $num($totalCredit) ?></p>
            </td>
            <td class="tot-fin">
                <p class="tot-lbl">الرصيد النهائي</p>
                <p class="tot-val"><?= $num($runningBalance) ?></p>
            </td>
        </tr>
    </table>
</div>

<!-- Verification -->
<div class="sec">
    <h3 class="sec-title">التحقق الإلكتروني</h3>
    <table class="verify-tbl">
        <tr>
            <td class="qr-cell">
                <img src="<?= $qrDataUrl ?>" alt="QR">
            </td>
            <td>
                <p class="v-hint">رقم التحقق الفريد</p>
                <p class="v-code"><?= Html::encode($sigShort) ?></p>
                <p class="v-row"><b>رابط التحقق:</b></p>
                <span class="v-url"><?= Html::encode($verifyUrl) ?></span>
            </td>
        </tr>
    </table>
    <div class="trust">
        &#10003;  كشف موثّق إلكترونياً عبر نظام تيسير ERP — لا يحتاج توقيع يدوي.
    </div>
</div>

<div class="footer">
    <b><?= Html::encode($companyName) ?><?php if (!empty($companyPhone)): ?> &nbsp;|&nbsp; <?= $enNum(Html::encode($companyPhone)) ?><?php endif; ?></b>
    <p><?= Html::encode($companyName) ?> مسؤولة عن صحة بيانات هذا الكشف حتى تاريخه.</p>
    <p>الشركة غير مسؤولة عن أي دفعات غير مدرج فيها اسم العميل الرباعي على خانة اسم المودع.</p>
    <?php if (!empty($companyBanks)): ?>
    <p>الشركة غير مسؤولة عن أي دفعة مدفوعة في أي حساب غير حسابها في <?= Html::encode($companyBanks) ?>.</p>
    <?php endif; ?>
    <p class="sig-line">SIG: <?= Html::encode($sigLong) ?></p>
</div>
