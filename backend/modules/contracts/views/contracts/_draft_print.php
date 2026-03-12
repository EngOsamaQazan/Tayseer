<?php
/**
 * صفحة الكمبيالة واتفاقية الموطن المختار — A4
 * مُصمّمة للطباعة مرتين:
 *   الطبعة الأولى: عند إنشاء العقد (بيانات أساسية)
 *   الطبعة الثانية: عند تسجيل القضية (تُملأ حقول: الموطن المختار · العنوان · اسم المحكمة)
 *
 * أماكن الطباعة الثانية مُحددة بخطوط منقطة وأحجام ثابتة
 */
use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;

$cc = new CompanyChecked();
$primary = $cc->findPrimaryCompany();
$companyName = $primary ? $primary->name : '';
$companyBanks = $primary ? CompanyChecked::findPrimaryCompanyBancks() : '';

$total = $model->total_value * 1.15;
/* due_date تُحسب تلقائياً في afterFind() */
$today = date('Y-m-d');

/* جمع بيانات العملاء والكفلاء */
$people = $model->customersAndGuarantor;
$phones = [];
$emails = [];
foreach ($people as $p) {
    if (!empty($p->primary_phone_number)) $phones[] = \backend\helpers\PhoneHelper::toLocal($p->primary_phone_number);
    if (!empty($p->email)) $emails[] = $p->email;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>كمبيالة — عقد #<?= $model->id ?></title>
<style>
/* ═══ Page Setup ═══ */
@page { size: A4 portrait; margin: 8mm 10mm 8mm 10mm; }
*{ margin:0; padding:0; box-sizing:border-box; }
body{ direction:rtl; font-family:'DinNextRegular','Cairo','Segoe UI',sans-serif; color:#1a1a1a; font-size:12px; line-height:1.6; background:#fff; }
@font-face{font-family:'DinNextRegular';src:url('/css-new/fonts/din-next/regular/DinNextRegular.woff2') format('woff2'),url('/css-new/fonts/din-next/regular/DinNextRegular.woff') format('woff'),url('/css-new/fonts/din-next/regular/DinNextRegular.ttf') format('truetype');}
@font-face{font-family:'DinNextBold';src:url('/css-new/fonts/din-next/bold/DinNextBold.woff2') format('woff2'),url('/css-new/fonts/din-next/bold/DinNextBold.woff') format('woff'),url('/css-new/fonts/din-next/bold/DinNextBold.ttf') format('truetype');}
@font-face{font-family:'DinNextMedium';src:url('/css-new/fonts/din-next/medium/DinNextMedium.woff2') format('woff2'),url('/css-new/fonts/din-next/medium/DinNextMedium.woff') format('woff'),url('/css-new/fonts/din-next/medium/DinNextMedium.ttf') format('truetype');}
b,strong,.b{font-family:'DinNextBold',sans-serif!important;}

.page{ width:100%; max-width:190mm; margin:0 auto; }

/* ═══ Section Title ═══ */
.sec-title{ text-align:center; font-family:'DinNextBold',sans-serif; font-size:16px; margin:6px 0 8px; }
.sec-title.green{ color:#4caf50; }
.sec-title.red{ color:#e53935; }
.divider{ border:0; border-top:3px solid #4caf50; margin:6px 0; }
.divider.red{ border-color:#e53935; }

/* ═══ Parties ═══ */
.party{ margin-bottom:3px; font-size:12px; }
.party b{ color:#333; }

/* ═══ Text Blocks ═══ */
.txt{ font-size:11.5px; line-height:1.65; text-align:justify; margin-bottom:5px; }

/* ═══ أماكن الطباعة الثانية — مساحة منقطة ثابتة ═══ */
.print2-slot{
    display:inline-block;
    min-width:280px;
    border-bottom:1.5px dotted #999;
    padding:0 4px;
    color:#bbb;
    font-size:11px;
    font-style:italic;
    letter-spacing:.3px;
    vertical-align:baseline;
}
/* مساحة اسم المحكمة قبل "والدفع بها" — حجم ثابت مثل "قصر العدل عمان" */
.court-slot{
    display:inline-block;
    min-width:140px;
    max-width:180px;
    border-bottom:1.5px dotted #999;
    padding:0 6px;
    color:#bbb;
    font-size:14px;
    font-family:'DinNextBold',sans-serif;
    vertical-align:baseline;
    text-align:center;
}

/* ═══ People Table ═══ */
.ppl-tbl{ width:100%; border-collapse:collapse; margin:6px 0; }
.ppl-tbl th{ background:#f5f5f5; font-family:'DinNextBold',sans-serif; font-size:11px; padding:4px 8px; border:1px solid #ddd; text-align:center; color:#333; }
.ppl-tbl td{ border:1px solid #ddd; padding:4px 8px; text-align:center; font-size:11px; }
/* عمود العنوان — فارغ للطباعة الثانية */
.ppl-tbl td.addr{ min-width:160px; height:20px; }

/* ═══ Kambiyala Box ═══ */
.kamb{ border:2px solid #e53935; border-radius:6px; padding:8px 10px; margin:6px 0; }
.kamb-grid{ display:grid; grid-template-columns:1fr auto 1fr; gap:8px; align-items:center; margin:6px 0; }
.money-box{ border:1px solid #ddd; border-radius:4px; text-align:center; padding:4px 8px; }
.money-box .lbl{ font-size:9px; color:#888; display:block; }
.money-box .val{ font-size:16px; font-family:'DinNextBold',sans-serif; color:#e53935; }

/* ═══ Sig Table ═══ */
.sig-tbl{ width:100%; border-collapse:collapse; margin:6px 0; }
.sig-tbl th{ background:#fce4ec; color:#c62828; font-family:'DinNextBold',sans-serif; font-size:10px; padding:4px 6px; border:1px solid #ef9a9a; text-align:center; }
.sig-tbl td{ border:1px solid #ddd; padding:3px 6px; text-align:center; height:28px; font-size:10px; }

/* ═══ Print ═══ */
@media print {
    body{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .no-print{ display:none!important; }
}
@media screen {
    body{ padding:10px; background:#eee; }
    .page{ background:#fff; padding:15px; box-shadow:0 2px 10px rgba(0,0,0,.15); }
    .print-btn{ position:fixed; top:15px; left:15px; z-index:999; background:#e53935; color:#fff; border:0; padding:10px 24px; border-radius:6px; font-size:14px; cursor:pointer; font-family:'DinNextBold',sans-serif; }
    .print-btn:hover{ background:#c62828; }
}
</style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨️ طباعة الكمبيالة</button>

<div class="page">

<!-- ══════════════════════════════════════════════════════
     القسم الأول: اتفاقية الموطن المختار والمحكمة المختصة
     ══════════════════════════════════════════════════════ -->
<h3 class="sec-title green">اتفاقية الموطن المختار والمحكمة المختصة</h3>
<hr class="divider">

<div class="party"><b>الطرف الأول :</b> <?= $companyName ?></div>
<div class="party"><b>الطرف الثاني :</b>
    <?php
    $names = [];
    foreach ($people as $c) { $names[] = $c->name; }
    echo implode(' و ', $names);
    ?>
</div>

<p class="txt" style="margin-top:5px">
    اتفق الطرفان على أن تكون محكمة صلح وجزاء ودائرة تنفيذ :
</p>
<p class="txt">
    هي المحكمة المختصة في أي دعوى أو خصومة أو طرح وتنفيذ جميع السندات التنفيذية والجزائية المحررة بين الطرفين وأن الموطن المختار للتبليغات القضائية هو
    <span class="print2-slot" title="يُملأ عند تسجيل القضية — الموطن المختار">الموطن المختار : .................................</span>
</p>
<p class="txt">
    وهو العنوان الصحيح فقط ويقر الطرف الثاني أن أي تبليغ من خلال العنوان المذكور سواء كان تبليغاً بالإلصاق أو بالذات يُعتبر تبليغاً أصولياً، ويسقط حقه في إبطال التبليغات على هذا العنوان ويقر أيضاً بكافة التبليغات الإلكترونية المرسلة على بريده الإلكتروني أو على رقم هاتفه التالية:
    <b><?= implode(' - ', $phones) ?></b>
    <?php if ($emails): ?> &nbsp; <?= implode(' - ', $emails) ?><?php endif; ?>
</p>

<p class="txt">بعد طباعة الكمبيالة رقم : <b><?= $model->id ?></b></p>
<p class="txt">وبعد الاطلاع والموافقة على جميع البيانات المحررة فيها. تم التوقيع بتاريخ <b><?= $today ?></b></p>

<!-- جدول الأسماء + الرقم الوطني + العنوان (فارغ للطباعة الثانية) + التوقيع -->
<table class="ppl-tbl">
    <thead>
        <tr>
            <th style="width:28%">اسم المدين / الكفيل</th>
            <th style="width:18%">الرقم الوطني</th>
            <th style="width:32%">عنوانه <small style="color:#999;font-weight:normal">(يُملأ عند تسجيل القضية)</small></th>
            <th style="width:22%">التوقيع</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($people as $c): ?>
        <tr>
            <td><?= $c->name ?></td>
            <td><?= $c->id_number ?></td>
            <td class="addr"></td>
            <td></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<!-- ══════════════════════════════════════════════════════
     القسم الثاني: الكمبيالة
     ══════════════════════════════════════════════════════ -->
<hr class="divider red" style="margin-top:10px">
<h3 class="sec-title red">كمبيالة</h3>

<div class="kamb">

    <!-- جدول أسماء + رقم وطني + عنوان -->
    <table class="ppl-tbl" style="margin-bottom:6px">
        <thead>
            <tr>
                <th style="width:30%">اسم المدين / الكفيل</th>
                <th style="width:20%">الرقم الوطني</th>
                <th style="width:30%">عنوانه <small style="color:#999;font-weight:normal">(يُملأ عند تسجيل القضية)</small></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($people as $c): ?>
            <tr>
                <td><?= $c->name ?></td>
                <td><?= $c->id_number ?></td>
                <td class="addr"></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- سطر المحكمة + المبلغ + تاريخ الاستحقاق -->
    <div class="kamb-grid">
        <div style="font-size:12px">
            <span class="court-slot" title="يُملأ عند تسجيل القضية — اسم المحكمة">........................</span>
            <b>والدفع بها</b>
        </div>
        <div class="money-box">
            <span class="lbl">دينار</span>
            <span class="val"><?= number_format($total, 0) ?></span>
            <span class="lbl" style="margin-top:2px">فلس 00</span>
        </div>
        <div style="font-size:12px;text-align:left">
            <b>تاريخ الاستحقاق :</b> <?= $model->due_date ?>
        </div>
    </div>

    <!-- المبلغ بالحروف -->
    <p class="txt" style="margin:4px 0"><b>فقط مبلغ وقدره</b> <span id="amount_in_words" style="color:#c62828;font-family:'DinNextBold',sans-serif"></span></p>

    <p class="txt" style="margin:3px 0"><b>أدفع لأمر</b> <?= $companyName ?></p>

    <p class="txt" style="margin:3px 0">القيمة وصلتنا <b>بضاعة</b> بعد المعاينة والاختبار والقبول تحريراً في <b><?= $today ?></b></p>

    <p class="txt" style="margin:3px 0;font-size:10.5px;color:#555">تم طباعة الكمبيالة قبل التوقيع وبعد الاطلاع على البيانات المطلوبة</p>

    <!-- توقيعات الكمبيالة -->
    <table class="sig-tbl">
        <thead>
            <tr>
                <th style="width:30%">اسم المدين / الكفيل</th>
                <th style="width:25%">الرقم الوطني</th>
                <th style="width:25%">التوقيع</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($people as $c): ?>
            <tr>
                <td><?= $c->name ?></td>
                <td><?= $c->id_number ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div><!-- .kamb -->

</div><!-- .page -->

<script src="/js-new/jquery-3.3.1.min.js"></script>
<script src="/js/Tafqeet.js"></script>
<script>
$(function(){
    var total = <?= (int)$total ?>;
    $('#amount_in_words').text(tafqeet(total) + ' دينار أردني فقط لا غير');
});
</script>
</body>
</html>
