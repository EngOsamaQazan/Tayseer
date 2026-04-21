<?php

use yii\helpers\Html;

/* @var $cert \backend\modules\followUp\models\ClearanceCertificate */
/* @var $snapshot array */
/* @var $isExpired bool */
/* @var $isRevoked bool */
/* @var $verifyUrl string */

/*
 * mpdf-friendly HTML (no flex/grid, no CSS variables, only classic CSS).
 * Using tables for layout and inline styles where needed.
 */

$companyName    = $snapshot['companyName']    ?? '';
$companyPhone   = $snapshot['companyPhone']   ?? '';
$clientNames    = $snapshot['clientNames']    ?? [];
$guarantorNames = $snapshot['guarantorNames'] ?? [];
$totalValue     = $snapshot['totalValue']     ?? 0;
$paidAmount     = $snapshot['paidAmount']     ?? 0;
$remaining      = $snapshot['remainingBalance']?? 0;
$dateSale       = $snapshot['dateSale']       ?? '—';
$firstInstDate  = $snapshot['firstInstDate']  ?? '—';
$lastIncomeDate = $snapshot['lastIncomeDate'] ?? null;
$monthlyInst    = $snapshot['monthlyInst']    ?? null;
$cases          = $snapshot['judiciaryCases'] ?? [];

$issuedDate  = substr((string) $cert->issued_at, 0, 10);
$issuedTime  = substr((string) $cert->issued_at, 11, 5);
$sigShort    = strtoupper(substr((string) $cert->signature, 0, 4) . '-'
                . substr((string) $cert->signature, 4, 4) . '-'
                . substr((string) $cert->signature, 8, 4));
$sigLong     = substr((string) $cert->signature, 0, 48);

$issuedBy = null;
if ($cert->issued_by) {
    $u = \common\models\User::findOne($cert->issued_by);
    $issuedBy = $u ? ($u->username ?? ($u->id ?? null)) : null;
}

$num = function ($n) {
    if ($n === null || $n === '' || !is_numeric($n)) return $n ?: '—';
    return number_format((float) $n, 2, '.', ',');
};

$enNum = function ($s) {
    return '<span style="font-family:dejavusans;direction:ltr;unicode-bidi:embed">' . $s . '</span>';
};

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

$statusText = $isRevoked ? 'ملغاة' : ($isExpired ? 'منتهية الصلاحية' : 'شهادة فعّالة');
$statusBg   = $isRevoked ? '#fee2e2' : ($isExpired ? '#fef3c7' : '#d1fae5');
$statusClr  = $isRevoked ? '#991b1b' : ($isExpired ? '#92400e' : '#065f46');
?>
<style>
body { font-family: xbriyaz; color: #1a1d21; font-size: 11pt; line-height: 1.55; }

.h-wrap { background: #7A000C; color: #fff; padding: 14px 18px 12px; border-radius: 4px; margin-bottom: 14px; }
.h-brand-name { font-size: 17pt; font-weight: bold; margin: 0; }
.h-brand-sub  { font-size: 9pt; color: #f4cfd3; margin: 0; }
.h-title { font-size: 18pt; font-weight: bold; text-align: center; margin: 10px 0 2px; }
.h-title-sub { font-size: 9pt; color: #f4cfd3; text-align: center; margin: 0 0 8px; font-family: dejavusans; }
.h-status { padding: 4px 12px; border-radius: 12px; font-size: 10pt; font-weight: bold; display: inline-block; }

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

.stmt {
    padding: 12px 14px;
    background: #fdfcfa;
    border: 1px dashed #d9b98a;
    border-radius: 4px;
    font-size: 11pt;
    line-height: 1.85;
}
.stmt strong { color: #4A0006; }
.stmt p { margin: 0 0 6px; }
.stmt p:last-child { margin: 0; }

.money-tbl { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
.money-tbl td { width: 33.33%; padding: 10px 8px; border: 1px solid #e4e5e7; border-radius: 4px; text-align: center; background: #fff; vertical-align: middle; }
.money-lbl { font-size: 9pt; color: #525866; margin: 0; }
.money-val { font-size: 14pt; font-weight: bold; margin: 3px 0 0; font-family: dejavusans; direction: ltr; unicode-bidi: embed; }
.money-cur { font-size: 8.5pt; color: #868c98; margin: 2px 0 0; }
.money-paid   { background: #f0fdf4 !important; border-color: #bbf7d0 !important; }
.money-paid   .money-val { color: #0F7B3D; }
.money-remain { background: #fef2f2 !important; border-color: #fecaca !important; }
.money-remain .money-val { color: #B42318; }

.info-tbl { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
.info-tbl > tbody > tr > td { width: 50%; padding: 10px 12px; border: 1px solid #e4e5e7; border-radius: 4px; background: #fafbfc; vertical-align: top; }
.info-title { font-size: 9pt; color: #868c98; font-weight: bold; margin: 0 0 6px; }
.kv-tbl { width: 100%; border-collapse: collapse; }
.kv-tbl td { padding: 4px 0; font-size: 10pt; border-bottom: 1px dashed #f0f0f1; }
.kv-tbl tr:last-child td { border-bottom: 0; }
.kv-k { color: #525866; width: 40%; }
.kv-v { color: #1a1d21; font-weight: bold; text-align: left; }

.cases-tbl { width: 100%; border-collapse: collapse; }
.cases-tbl td { padding: 8px 10px; border: 1px solid #e4e5e7; background: #fafafa; font-size: 10pt; }

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

.footer { margin-top: 16px; padding-top: 12px; border-top: 1px solid #e4e5e7; text-align: center; color: #868c98; font-size: 8.5pt; }
.footer b { color: #1a1d21; display: block; margin-bottom: 4px; font-size: 10pt; }
.footer p { margin: 2px 0; }

.sig-line { margin-top: 8px; font-family: dejavusans; font-size: 7pt; color: #b0b5bf; text-align: center; direction: ltr; }
</style>

<div class="h-wrap">
    <table width="100%"><tr>
        <td style="width:70%">
            <p class="h-brand-sub">المُصدِر</p>
            <p class="h-brand-name"><?= Html::encode($companyName) ?></p>
        </td>
        <td style="width:30%;text-align:left">
            <span class="h-status" style="background:<?= $statusBg ?>;color:<?= $statusClr ?>"><?= $statusText ?></span>
        </td>
    </tr></table>

    <div class="h-title">شهادة براءة ذمة</div>
    <div class="h-title-sub">Clearance Certificate</div>

    <table class="meta-tbl">
        <tr>
            <td>
                <span class="meta-lbl">رقم الشهادة</span>
                <span class="meta-val"><?= $enNum(Html::encode($cert->cert_number)) ?></span>
            </td>
            <td>
                <span class="meta-lbl">رقم العقد</span>
                <span class="meta-val"><?= $enNum((int) $cert->contract_id) ?></span>
            </td>
            <td>
                <span class="meta-lbl">تاريخ الإصدار</span>
                <span class="meta-val"><?= $enNum(Html::encode($issuedDate) . ($issuedTime ? ' ' . Html::encode($issuedTime) : '')) ?></span>
            </td>
        </tr>
    </table>
</div>

<div class="sec">
    <h3 class="sec-title">لمن يهمه الأمر</h3>
    <div class="stmt">
        <p>
            تشهد <strong><?= Html::encode($companyName) ?></strong>
            أن المدين / المدينين <strong><?= Html::encode(implode(' ، ', (array) $clientNames)) ?></strong>
            <strong>بريء الذمة المالية</strong>
            في العقد رقم <?= $enNum((int) $cert->contract_id) ?>
            الموقّع بتاريخ <?= $enNum(Html::encode($dateSale)) ?>،
            وأن كافة الشيكات والسندات الموقعة من قبله بتاريخ هذا العقد <strong>ملغية</strong>.
        </p>
        <?php if (!empty($cases)): ?>
        <p>
            مع الإشارة إلى أن هناك قضايا قضائية مسجلة مرتبطة بهذا العقد موضحة أدناه،
            ولا يُعتبر هذا إقراراً بأي التزام قائم خلاف تسوية الالتزامات المالية الناشئة عن العقد.
        </p>
        <?php endif; ?>
    </div>
</div>

<div class="sec">
    <h3 class="sec-title">الحالة المالية</h3>
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
                <p class="money-val"><?= $num($remaining) ?></p>
                <p class="money-cur">د.أ</p>
            </td>
        </tr>
    </table>
</div>

<div class="sec">
    <h3 class="sec-title">بيانات العقد</h3>
    <table class="info-tbl">
        <tr>
            <td>
                <p class="info-title">الأطراف</p>
                <table class="kv-tbl">
                    <tr><td class="kv-k">المدين</td><td class="kv-v"><?= Html::encode(implode(' ، ', (array) $clientNames)) ?></td></tr>
                    <tr><td class="kv-k">الكفلاء</td><td class="kv-v"><?= Html::encode(implode(' ، ', (array) $guarantorNames) ?: 'لا يوجد') ?></td></tr>
                </table>
            </td>
            <td>
                <p class="info-title">التواريخ والأقساط</p>
                <table class="kv-tbl">
                    <tr><td class="kv-k">تاريخ البيع</td><td class="kv-v"><?= $enNum(Html::encode($dateSale)) ?></td></tr>
                    <tr><td class="kv-k">أول قسط</td><td class="kv-v"><?= $enNum(Html::encode($firstInstDate)) ?></td></tr>
                    <tr><td class="kv-k">آخر دفعة</td><td class="kv-v"><?= $enNum(Html::encode($lastIncomeDate ?: 'لا يوجد')) ?></td></tr>
                    <tr><td class="kv-k">القسط الشهري</td><td class="kv-v"><?= $enNum($num($monthlyInst)) ?></td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<?php if (!empty($cases)): ?>
<div class="sec">
    <h3 class="sec-title">القضايا القضائية المسجلة</h3>
    <table class="cases-tbl">
        <?php foreach ($cases as $case): ?>
        <tr>
            <td>
                <strong>قضية رقم
                    <?= $enNum(Html::encode($case['judiciary_number'] ?: '—')) ?><?php if (!empty($case['year'])): ?> / <?= $enNum(Html::encode($case['year'])) ?><?php endif ?>
                </strong>
                &nbsp;•&nbsp; <?= Html::encode($case['court_name'] ?: 'محكمة غير محددة') ?>
                <?php if (!empty($case['case_status'])): ?>
                &nbsp;•&nbsp; الحالة: <?= Html::encode($case['case_status']) ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

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
                <?php if ($issuedBy !== null): ?>
                <p class="v-row"><b>أصدرها:</b> <?= Html::encode($issuedBy) ?></p>
                <?php endif; ?>
                <p class="v-row"><b>رابط التحقق:</b></p>
                <span class="v-url"><?= Html::encode($verifyUrl) ?></span>
            </td>
        </tr>
    </table>
    <div class="trust">
        &#10003;  شهادة موثّقة إلكترونياً عبر نظام تيسير ERP — لا تحتاج توقيع يدوي.
    </div>
</div>

<div class="footer">
    <b><?= Html::encode($companyName) ?><?php if (!empty($companyPhone)): ?> &nbsp;|&nbsp; <?= $enNum(Html::encode($companyPhone)) ?><?php endif; ?></b>
    <p><?= Html::encode($companyName) ?> مسؤولة عن صحة بيانات هذه الشهادة حتى تاريخها.</p>
    <p>هذه الشهادة لا تُعفي العميل من أي التزامات أخرى خارج نطاق هذا العقد.</p>
    <p class="sig-line">SIG: <?= Html::encode($sigLong) ?></p>
</div>
