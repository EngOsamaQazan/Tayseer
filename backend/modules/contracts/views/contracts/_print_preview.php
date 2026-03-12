<?php
/**
 * معاينة الطباعة الموحدة — مستوى مطبعة تجارية رسمية
 * ═══════════════════════════════════════════════════════
 *   الصفحة 1 : عقد البيع بالتقسيط
 *   الصفحات 2-4 : اتفاقية الموطن المختار + كمبيالة تنفيذية (×3)
 *
 * @var $model   backend\modules\contracts\models\Contracts
 * @var $notes   backend\modules\contracts\models\PromissoryNote[]
 */
use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;

$cc = new CompanyChecked();
$primary = $cc->findPrimaryCompany();
$logo = ($primary && $primary->logo) ? $primary->logo : (Yii::$app->params['companies_logo'] ?? '');
$companyName = $primary ? $primary->name : '';
$companyBanks = $primary ? $cc->findPrimaryCompanyBancks() : '';

$total      = $model->total_value ?: 0;
$first      = $model->first_installment_value ?: 0;
$monthly    = $model->monthly_installment_value ?: 0;
$afterFirst = $total - $first;
$today      = date('Y-m-d');

$allPeople  = $model->customersAndGuarantor;
$guarantors = $model->guarantor;
$gCount     = count($guarantors);
$gLabels    = ['الأول','الثاني','الثالث','الرابع','الخامس'];

$phones = [];
$emails = [];
foreach ($allPeople as $p) {
    if (!empty($p->primary_phone_number)) $phones[] = \backend\helpers\PhoneHelper::toLocal($p->primary_phone_number);
    if (!empty($p->email)) $emails[] = $p->email;
}

$peopleNames = [];
foreach ($allPeople as $c) { $peopleNames[] = $c->name; }
$allNames = implode(' و ', $peopleNames);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>طباعة عقد #<?= $model->id ?></title>
<style>
/* ═══ خطوط ═══ */
@font-face{font-family:'DinNextRegular';src:url('/css-new/fonts/din-next/regular/DinNextRegular.woff2') format('woff2'),url('/css-new/fonts/din-next/regular/DinNextRegular.woff') format('woff'),url('/css-new/fonts/din-next/regular/DinNextRegular.ttf') format('truetype')}
@font-face{font-family:'DinNextBold';src:url('/css-new/fonts/din-next/bold/DinNextBold.woff2') format('woff2'),url('/css-new/fonts/din-next/bold/DinNextBold.woff') format('woff'),url('/css-new/fonts/din-next/bold/DinNextBold.ttf') format('truetype')}
@font-face{font-family:'DinNextMedium';src:url('/css-new/fonts/din-next/medium/DinNextMedium.woff2') format('woff2'),url('/css-new/fonts/din-next/medium/DinNextMedium.woff') format('woff'),url('/css-new/fonts/din-next/medium/DinNextMedium.ttf') format('truetype')}
b,strong{font-family:'DinNextBold',sans-serif!important}

/* ═══ أساسيات ═══ */
@page{size:A4 portrait;margin:8mm 10mm}
*{margin:0;padding:0;box-sizing:border-box}
body{direction:rtl;font-family:'DinNextRegular','Cairo','Segoe UI',sans-serif;color:#1a1a1a;font-size:12px;line-height:1.55;background:#fff}
.print-page{width:100%;max-width:190mm;margin:0 auto;page-break-after:always;position:relative}
.print-page:last-child{page-break-after:auto}

/* ═══ شريط الأدوات ═══ */
.toolbar{position:sticky;top:0;z-index:1000;background:linear-gradient(135deg,#1a365d,#2b6cb0);color:#fff;padding:10px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 3px 15px rgba(0,0,0,.25);font-family:'DinNextMedium',sans-serif}
.toolbar h1{font-family:'DinNextBold',sans-serif;font-size:16px;flex:1;margin:0}
.toolbar .tb-id{background:rgba(255,255,255,.15);border-radius:6px;padding:3px 14px;font-family:'DinNextBold',sans-serif;font-size:20px}
.toolbar .tb-info{font-size:12px;opacity:.8}
.toolbar .tb-btn{border:0;padding:8px 22px;border-radius:6px;font-size:14px;font-family:'DinNextBold',sans-serif;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s}
.toolbar .tb-print{background:#48bb78;color:#fff}
.toolbar .tb-print:hover{background:#38a169}
.toolbar .tb-back{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);text-decoration:none;padding:6px 14px;border-radius:6px;font-size:12px}
.toolbar .tb-back:hover{background:rgba(255,255,255,.25)}
.page-sep{text-align:center;padding:10px;margin:4px auto;max-width:190mm;color:#94a3b8;font-size:11px}
.page-sep span{background:#e2e8f0;padding:3px 14px;border-radius:12px}

/* ══════════════════════════════════════════════════════════
   صفحة العقد (1)
   ══════════════════════════════════════════════════════════ */
.ct-bar{height:4px;background:#1a365d;border-radius:2px}
.ct-hdr{display:flex;align-items:flex-start;padding:10px 0 8px;gap:12px}
.ct-hdr-logo{width:90px;flex-shrink:0}
.ct-hdr-logo img{width:90px;height:auto}
.ct-hdr-center{flex:1;text-align:center}
.ct-hdr-center h2{font-family:'DinNextBold',sans-serif;font-size:18px;color:#1a365d;margin:0 0 2px}
.ct-hdr-center .ct-date{font-size:11px;color:#555;margin-top:2px}
.ct-hdr-info{text-align:left;min-width:120px}
.ct-no{border:2.5px solid #c62828;border-radius:6px;padding:4px 14px;text-align:center;display:inline-block}
.ct-no small{display:block;font-size:8px;color:#888;font-family:'DinNextRegular',sans-serif}
.ct-no strong{display:block;font-size:22px;color:#c62828;font-family:'DinNextBold',sans-serif;letter-spacing:1px;line-height:1.2}
.ct-photos{display:flex;gap:5px;justify-content:center;margin:6px 0;flex-wrap:wrap}
.ct-photos img{width:60px;height:75px;object-fit:cover;border:1.5px solid #ccc;border-radius:4px}
.ct-section{margin-bottom:8px}
.ct-section-title{font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a365d;border-bottom:2px solid #1a365d;padding-bottom:3px;margin-bottom:6px}
.ct-party{display:flex;gap:4px;margin-bottom:3px;font-size:12px}
.ct-party-label{font-family:'DinNextBold',sans-serif;color:#1a365d;min-width:120px}
.ct-party-sub{font-size:10.5px;color:#555;margin-right:8px}
.ct-terms{font-size:11px;line-height:1.6}
.ct-terms p{margin-bottom:4px;text-align:justify}
.ct-terms .ct-num{font-family:'DinNextBold',sans-serif;color:#1a365d}
.ct-solidarity{border:1.5px solid #1a365d;border-radius:4px;padding:5px 8px;margin:4px 0;background:#f0f4f8}
.ct-solidarity p{margin:0;font-size:11px}
.ct-fin-tbl{width:100%;border-collapse:collapse;margin:6px 0;font-size:11.5px}
.ct-fin-tbl th{background:#1a365d;color:#fff;font-family:'DinNextBold',sans-serif;padding:5px 10px;text-align:center;font-size:11px;border:1px solid #1a365d}
.ct-fin-tbl td{border:1px solid #ccc;padding:4px 10px}
.ct-fin-tbl td:first-child{font-family:'DinNextMedium',sans-serif;color:#333;width:45%}
.ct-fin-tbl td:last-child{font-family:'DinNextBold',sans-serif;text-align:center;color:#1a365d}
.ct-fin-tbl tr:nth-child(even){background:#f8f9fa}
.ct-fin-tbl .ct-money{color:#c62828;font-size:12.5px}
.ct-sigs{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
.ct-sig{flex:1;min-width:100px;border:1px solid #999;border-radius:4px;text-align:center;overflow:hidden}
.ct-sig-hd{background:#f0f4f8;font-family:'DinNextBold',sans-serif;font-size:10px;padding:4px;border-bottom:1px solid #999;color:#1a365d}
.ct-sig-name{font-size:9px;color:#555;padding:2px 4px;border-bottom:1px dashed #ddd}
.ct-sig-body{height:50px}
.ct-stamp{width:75px;height:75px;border:2px dashed #999;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:8px;color:#999;text-align:center;font-family:'DinNextMedium',sans-serif;line-height:1.3}
.ct-sig-row{display:flex;gap:10px;align-items:flex-end}
.ct-sig-row .ct-sigs{flex:1}
.ct-notes{font-size:10px;color:#555;border:1px solid #eee;border-radius:4px;padding:5px 8px;margin-top:6px}
.ct-notes b{color:#333}

/* ══════════════════════════════════════════════════════════
   الصفحة المدمجة — اتفاقية + كمبيالة
   مستوى مطبعة تجارية رسمية
   ══════════════════════════════════════════════════════════ */

/* ─── إطار الاتفاقية ─── */
.agr-frame{
    border:1px solid #888;padding:12px 16px;border-radius:2px;
}
.agr-ttl{
    text-align:center;font-family:'DinNextBold',sans-serif;
    font-size:18px;color:#1a1a1a;margin:0 0 8px;
    letter-spacing:.5px;padding-bottom:6px;
    border-bottom:2px solid #333;
}
.agr-pty{font-size:14px;margin-bottom:4px;font-family:'DinNextBold',sans-serif}
.agr-pty b{color:#1a1a1a}
.agr-txt{font-size:13px;line-height:1.65;text-align:justify;margin:5px 0;font-family:'DinNextMedium',sans-serif}

/* صناديق Overlay — منقطة، مهيأة لطباعة لاحقة بخط 16px Bold */
.ovl-wrap{margin:6px 0}
.ovl-lbl{font-size:13px;font-family:'DinNextBold',sans-serif;color:#1a1a1a;margin-bottom:3px}
.ovl-box{
    border:2px dashed #555;border-radius:3px;
    min-height:34px;width:100%;background:#fff;
    /* الارتفاع يتسع لخط 16px Bold عند الطباعة اللاحقة */
}

/* جدول توقيع الاتفاقية — أسطر أفقية واضحة */
.agr-stbl{width:100%;border-collapse:collapse;margin:8px 0}
.agr-stbl th{
    font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a1a1a;
    padding:6px 8px;border-bottom:2.5px solid #1a1a1a;text-align:center;
}
.agr-stbl td{
    padding:5px 8px;border-bottom:1px solid #888;font-size:12px;
    text-align:center;height:30px;font-family:'DinNextMedium',sans-serif;
}
.agr-stbl td.ovl-td{border-bottom:2px dashed #555}

/* ─── الفاصل البصري ─── */
.sep{display:flex;align-items:center;margin:10px 0}
.sep::before,.sep::after{content:'';flex:1;height:3px;background:#1a1a1a}
.sep-text{
    padding:2px 24px;font-family:'DinNextBold',sans-serif;
    font-size:18px;letter-spacing:5px;color:#1a1a1a;white-space:nowrap;
}

/* ─── الكمبيالة — إطار مزدوج رسمي ─── */
.kmb-outer{border:2.5px solid #1a1a1a;padding:3px}
.kmb-inner{border:1px solid #1a1a1a;padding:10px 12px}

/* رأس الكمبيالة */
.kmb-hdr{
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:8px;padding-bottom:6px;border-bottom:1.5px solid #ccc;
}
.kmb-no-box{
    border:2px solid #1a1a1a;border-radius:3px;padding:3px 16px;text-align:center;
}
.kmb-no-lbl{display:block;font-size:8px;color:#555;font-family:'DinNextMedium',sans-serif}
.kmb-no-val{font-family:'DinNextBold',sans-serif;font-size:16px;color:#1a1a1a}
/* تاريخ الاستحقاق — محاذى يسار، بجانب المبلغ، إطار أحمر */
.kmb-due-box{
    margin-right:auto;text-align:center;
    border:2.5px solid #c62828;border-radius:4px;padding:3px 14px;
}
.kmb-due-box small{display:block;font-size:8px;color:#555;font-family:'DinNextMedium',sans-serif}
.kmb-due-box strong{font-family:'DinNextBold',sans-serif;font-size:14px;color:#c62828;display:block;line-height:1.2}

/* جدول بيانات الأطراف — اسم | رقم وطني | موطن مختار (overlay) */
.kmb-ptbl{width:100%;border-collapse:collapse;margin:6px 0;font-size:12px}
.kmb-ptbl td{padding:4px 6px;border-bottom:1px solid #ccc;vertical-align:middle}
.kmb-ptbl .pr-role{font-family:'DinNextBold',sans-serif;color:#1a1a1a;width:8%;white-space:nowrap;font-size:12px}
.kmb-ptbl .pr-name{width:22%;font-family:'DinNextBold',sans-serif;font-size:12px}
.kmb-ptbl .pr-id-lbl{font-family:'DinNextBold',sans-serif;color:#333;width:10%;font-size:10px;white-space:nowrap}
.kmb-ptbl .pr-id{width:16%;text-align:center;font-family:'DinNextBold',sans-serif;font-size:12px}
.kmb-ptbl .pr-addr-lbl{font-family:'DinNextBold',sans-serif;color:#333;width:10%;font-size:10px;white-space:nowrap}
.kmb-ptbl .pr-addr{border-bottom:2px dashed #555!important}

/* الصف الرئيسي — Court(overlay) | والدفع بها | المبلغ | الاستحقاق */
.kmb-main{display:flex;align-items:center;gap:10px;margin:10px 0}
/* صندوق المحكمة — بحجم يتسع لـ"قصر العدل عمان" بخط 16px Bold فقط */
.kmb-court-box{width:200px;flex-shrink:0;border:2px dashed #555;min-height:30px;border-radius:2px}
.kmb-pay{font-family:'DinNextBold',sans-serif;font-size:14px;white-space:nowrap}
.kmb-amt{
    border:2.5px solid #c62828;border-radius:4px;padding:4px 16px;
    text-align:center;min-width:120px;
}
.kmb-amt small{display:block;font-size:8px;color:#555}
.kmb-amt strong{font-family:'DinNextBold',sans-serif;font-size:20px;color:#c62828;display:block;line-height:1.2}
/* المبلغ كتابة */
.kmb-words{
    font-size:13px;margin:5px 0;padding:5px 0;
    border-bottom:1px solid #ddd;font-family:'DinNextBold',sans-serif;
}
.kmb-words b{color:#c62828;font-family:'DinNextBold',sans-serif}

/* نصوص */
.kmb-p{font-size:12px;margin:4px 0;font-family:'DinNextMedium',sans-serif}

/* جدول توقيع الكمبيالة */
.kmb-stbl{width:100%;border-collapse:collapse;margin:6px 0}
.kmb-stbl th{
    font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a1a1a;
    padding:5px 8px;border-bottom:2.5px solid #1a1a1a;text-align:center;
}
.kmb-stbl td{
    padding:4px 8px;border-bottom:1.5px solid #555;
    text-align:center;font-size:12px;height:30px;font-family:'DinNextMedium',sans-serif;
}

.kmb-pnote{font-size:9px;color:#555;font-style:italic;text-align:center;margin-top:5px}

/* ═══ طباعة / شاشة ═══ */
@media print{
    body{-webkit-print-color-adjust:exact;print-color-adjust:exact;background:#fff!important}
    .toolbar,.page-sep{display:none!important}
    .print-page{margin:0;padding:0;box-shadow:none;max-width:100%}
    .ct-solidarity{border-color:#333!important}
}
@media screen{
    body{background:#cbd5e1;padding:0}
    .print-page{background:#fff;padding:16px 20px;margin:16px auto;box-shadow:0 4px 20px rgba(0,0,0,.12);border-radius:3px}
}
</style>
</head>
<body>

<!-- ═══ شريط الأدوات ═══ -->
<div class="toolbar">
    <a class="tb-back" href="<?= Url::to(['view', 'id' => $model->id]) ?>">← العودة</a>
    <h1>معاينة الطباعة</h1>
    <span class="tb-info">4 صفحات — العقد + 3 كمبيالات</span>
    <span class="tb-id">#<?= $model->id ?></span>
    <button class="tb-btn tb-print" onclick="window.print()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        طباعة
    </button>
</div>

<!-- ══════════════════════════════════════════════════════════════
     الصفحة 1 : عقد البيع بالتقسيط
     ══════════════════════════════════════════════════════════════ -->
<div class="print-page">

    <div class="ct-bar"></div>

    <div class="ct-hdr">
        <div class="ct-hdr-logo">
            <?php if ($logo): ?>
                <?= Html::img(Url::to(['/' . $logo]), ['style' => 'width:90px;height:auto']) ?>
            <?php endif; ?>
        </div>
        <div class="ct-hdr-center">
            <h2>عقد بيع بالتقسيط</h2>
            <div style="font-size:13px;font-family:'DinNextMedium',sans-serif;color:#333"><?= $companyName ?></div>
            <div class="ct-date">تاريخ البيع: <b><?= $model->Date_of_sale ?></b></div>
            <div class="ct-photos">
                <?php foreach ($allPeople as $person): ?>
                    <?php if ($person->selectedImagePath): ?>
                        <img src="<?= $person->selectedImagePath ?>" alt="<?= Html::encode($person->name) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="ct-hdr-info">
            <div class="ct-no">
                <small>رقم العقد</small>
                <strong><?= $model->id ?></strong>
            </div>
        </div>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">أطراف العقد</div>
        <div class="ct-party">
            <span class="ct-party-label">الطرف الأول (البائع):</span>
            <span><?= $companyName ?></span>
        </div>
        <?php foreach ($allPeople as $i => $c): ?>
        <div class="ct-party">
            <span class="ct-party-label"><?= $i === 0 ? 'الطرف الثاني (المشتري):' : 'الكفيل ' . ($gLabels[$i-1] ?? $i) . ':' ?></span>
            <span><?= $c->name ?></span>
            <span class="ct-party-sub">الرقم الوطني: <?= $c->id_number ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">بنود العقد</div>
        <div class="ct-terms">
            <p>تعتبر هذه المقدمة جزءاً لا يتجزأ من العقد ونقر نحن المشتري والكفلاء بموافقتنا على البنود التالية:</p>
            <p><span class="ct-num">1.</span> <b>حالة البضاعة:</b> إننا استلمنا البضاعة الموصوفة أدناه بعد المعاينة والفحص سليمة وخالية من العيوب والمشاكل.</p>
            <div class="ct-solidarity">
                <p><span class="ct-num">2.</span> <b>الالتزام التضامني:</b> يلتزم المشتري والكفلاء <b>متضامنين ومتكافلين</b> بدفع كامل ثمن البضاعة المذكورة في العقد، وتحمل كافة المصاريف القضائية وغير القضائية في حالة التخلف عن دفع أي قسط من الأقساط المذكورة، ويعتبر <b>كامل المبلغ مستحقاً فوراً</b> عند التخلف عن سداد أي قسط.</p>
            </div>
            <p><span class="ct-num">3.</span> <b>طريقة الدفع:</b> نلتزم بدفع الأقساط في موعدها من خلال eFAWATEERcom — تبويب تمويل وخدمات مالية — <?= $companyName ?> — تسديد قسط — إدخال الرقم (<b style="color:#c62828"><?= $model->id ?></b>) ثم إتمام الدفع، أو في حساب الشركة في <b><?= $companyBanks ?></b>.</p>
            <p><span class="ct-num">4.</span> <b>الكفالة والإرجاع:</b> كفالة الوكيل حسب الشركة الموزعة. البضاعة المباعة لا تُرد ولا تُستبدل. نلتزم بخسارة (<b><?= $model->loss_commitment ?: 'صفر' ?></b>) دينار في حال إرجاع البضاعة خلال 24 ساعة من تاريخ البيع. لا يمكن إرجاع البضاعة بعد 24 ساعة مهما كانت الظروف.</p>
            <p><span class="ct-num">5.</span> <b>إخلاء المسؤولية:</b> الشركة غير مسؤولة عن سعر البضاعة خارج فروعها وعن أي اتفاقية أو مبلغ غير موثق في هذا العقد.</p>
        </div>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">البيانات المالية</div>
        <table class="ct-fin-tbl">
            <thead><tr><th>البيان</th><th>القيمة</th></tr></thead>
            <tbody>
                <tr><td>المبلغ الإجمالي للعقد</td><td class="ct-money"><?= number_format($total) ?> د.أ</td></tr>
                <tr><td>الدفعة الأولى</td><td class="ct-money"><?= number_format($first) ?> د.أ</td></tr>
                <tr><td>الرصيد المتبقي بعد الدفعة</td><td class="ct-money"><?= number_format($afterFirst) ?> د.أ</td></tr>
                <tr><td>القسط الشهري</td><td class="ct-money"><?= number_format($monthly) ?> د.أ</td></tr>
                <tr><td>تاريخ أول قسط</td><td><?= $model->first_installment_date ?></td></tr>
                <tr><td>تاريخ الاستحقاق النهائي</td><td><b><?= $model->due_date ?></b></td></tr>
                <tr><td>نوع العقد</td><td><?= $model->type === 'normal' ? 'فردي' : 'تضامني' ?></td></tr>
                <tr><td>البائع</td><td><?= $model->seller ? $model->seller->name : '—' ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">التوقيعات والإقرار</div>
        <div class="ct-sig-row">
            <div class="ct-sigs">
                <div class="ct-sig">
                    <div class="ct-sig-hd">المدين (المشتري)</div>
                    <div class="ct-sig-name"><?= $allPeople[0]->name ?? '' ?></div>
                    <div class="ct-sig-body"></div>
                </div>
                <?php for ($i = 0; $i < $gCount && $i < 5; $i++): ?>
                <div class="ct-sig">
                    <div class="ct-sig-hd">الكفيل <?= $gLabels[$i] ?></div>
                    <div class="ct-sig-name"><?= $guarantors[$i]->name ?? '' ?></div>
                    <div class="ct-sig-body"></div>
                </div>
                <?php endfor; ?>
                <div class="ct-sig">
                    <div class="ct-sig-hd">البائع</div>
                    <div class="ct-sig-name"><?= $model->seller ? $model->seller->name : '' ?></div>
                    <div class="ct-sig-body"></div>
                </div>
            </div>
            <div class="ct-stamp">ختم<br>الشركة</div>
        </div>
    </div>

    <div class="ct-notes">
        <b>ملاحظات:</b> <?= $model->notes ?: 'لا يوجد أي خصومات أو التزامات إضافية خارج هذا العقد.' ?>
    </div>

</div><!-- نهاية صفحة العقد -->


<!-- ══════════════════════════════════════════════════════════════
     الصفحات 2-4 : اتفاقية الموطن المختار + كمبيالة تنفيذية
     تصميم بمستوى سند بنكي / مطبعة تجارية رسمية
     ══════════════════════════════════════════════════════════════ -->
<?php foreach ($notes as $idx => $note): ?>

<div class="page-sep"><span>صفحة <?= $idx + 2 ?> من 4 — نسخة <?= $idx + 1 ?></span></div>

<div class="print-page">

    <!-- ════════════════════════════════════════════════════
         الجزء العلوي — اتفاقية الموطن المختار
         إطار خفيف بعرض الصفحة
         ════════════════════════════════════════════════════ -->
    <div class="agr-frame">

        <div class="agr-ttl">اتفاقية الموطن المختار والمحكمة المختصة</div>

        <div class="agr-pty"><b>الطرف الأول:</b> <?= $companyName ?></div>
        <div class="agr-pty"><b>الطرف الثاني:</b> <?= $allNames ?></div>

        <p class="agr-txt">
            اتفق الطرفان على أن تكون محكمة صلح وبداية وجزاء ودائرة تنفيذ المحكمة أدناه هي المحكمة المختصة حصراً في أي دعوى أو خصومة أو تنفيذ لجميع السندات التنفيذية والجزائية المحررة بين الطرفين:
        </p>

        <!-- صندوق Overlay — المحكمة المختصة -->
        <div class="ovl-wrap">
            <div class="ovl-lbl">المحكمة المختصة:</div>
            <div class="ovl-box"></div>
        </div>

        <p class="agr-txt">
            وأن الموطن المختار للتبليغات القضائية لجميع أطراف الطرف الثاني هو العنوان التالي حصراً:
        </p>

        <!-- صندوق Overlay — الموطن المختار -->
        <div class="ovl-wrap">
            <div class="ovl-lbl">الموطن المختار للتبليغات القضائية:</div>
            <div class="ovl-box"></div>
        </div>

        <p class="agr-txt">
            يُقرّ الطرف الثاني أن أي تبليغ على هذا العنوان — سواء بالإلصاق أو بالذات — يُعتبر تبليغاً أصولياً صحيحاً، ويُسقط حقه في الطعن أو إبطال التبليغات. كما يُقرّ بقبول التبليغات الإلكترونية على:
            <b><?= implode(' — ', $phones) ?></b>
            <?php if ($emails): ?> | <?= implode(' — ', $emails) ?><?php endif; ?>
        </p>

        <p class="agr-txt">
            بعد طباعة الكمبيالة رقم <b><?= $note->getDisplayNumber() ?></b> والاطلاع والموافقة على جميع بياناتها. تم التوقيع بتاريخ <b><?= $today ?></b>.
        </p>

        <!-- جدول توقيع الاتفاقية — أسطر أفقية واضحة -->
        <table class="agr-stbl">
            <thead>
                <tr>
                    <th style="width:28%">الصفة / الاسم</th>
                    <th style="width:18%">الرقم الوطني</th>
                    <th style="width:30%">العنوان</th>
                    <th style="width:24%">التوقيع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allPeople as $pi => $c): ?>
                <tr>
                    <td><?= $pi === 0 ? 'المدين' : 'كفيل' ?> — <?= $c->name ?></td>
                    <td><?= $c->id_number ?></td>
                    <td class="ovl-td"></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div><!-- .agr-frame -->

    <!-- ════════════════════════════════════════════════════
         الفاصل البصري — شريط أفقي بسمك متوسط
         ════════════════════════════════════════════════════ -->
    <div class="sep">
        <span class="sep-text">كمبيالة</span>
    </div>

    <!-- ════════════════════════════════════════════════════
         الجزء السفلي — كمبيالة تنفيذية رسمية
         إطار مزدوج (خارجي سميك + داخلي رفيع)
         ════════════════════════════════════════════════════ -->
    <div class="kmb-outer">
        <div class="kmb-inner">

            <!-- رأس الكمبيالة — رقم الكمبيالة فقط -->
            <div class="kmb-hdr">
                <div class="kmb-no-box">
                    <span class="kmb-no-lbl">رقم الكمبيالة</span>
                    <span class="kmb-no-val"><?= $note->getDisplayNumber() ?></span>
                </div>
            </div>

            <!-- بيانات الأطراف: اسم | رقم وطني | موطن مختار (overlay) -->
            <table class="kmb-ptbl">
                <?php foreach ($allPeople as $pi => $c): ?>
                <tr>
                    <td class="pr-role"><?= $pi === 0 ? 'المدين' : 'كفيل' ?></td>
                    <td class="pr-name"><?= $c->name ?></td>
                    <td class="pr-id-lbl">الرقم الوطني</td>
                    <td class="pr-id"><?= $c->id_number ?></td>
                    <td class="pr-addr-lbl">الموطن المختار</td>
                    <td class="pr-addr"></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <!-- الصف الرئيسي: Court(overlay) | والدفع بها | المبلغ | تاريخ الاستحقاق (يسار) -->
            <div class="kmb-main">
                <div class="kmb-court-box"></div>
                <span class="kmb-pay">والدفع بها</span>
                <div class="kmb-amt">
                    <small>المبلغ — دينار أردني</small>
                    <strong><?= number_format($note->amount, 2) ?></strong>
                </div>
                <div class="kmb-due-box">
                    <small>تاريخ الاستحقاق</small>
                    <strong><?= $note->due_date ?></strong>
                </div>
            </div>

            <!-- المبلغ كتابةً — سطر مستقل -->
            <div class="kmb-words">
                فقط مبلغ وقدره: <b><span class="kmb-words-text"></span></b>
            </div>

            <!-- أدفع لأمر -->
            <p class="kmb-p"><b>أدفع لأمر:</b> <?= $companyName ?></p>
            <p class="kmb-p">القيمة وصلتنا <b>بضاعة</b> بعد المعاينة والاختبار والقبول، تحريراً في <b><?= $today ?></b></p>

            <!-- جدول التوقيع — أسطر أفقية واضحة -->
            <table class="kmb-stbl">
                <thead>
                    <tr>
                        <th style="width:30%">الصفة / الاسم</th>
                        <th style="width:22%">الرقم الوطني</th>
                        <th style="width:48%">التوقيع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allPeople as $pi => $c): ?>
                    <tr>
                        <td><?= $pi === 0 ? 'المدين' : 'كفيل' ?> — <?= $c->name ?></td>
                        <td><?= $c->id_number ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="kmb-pnote">تم طباعة الكمبيالة قبل التوقيع وبعد اطلاع جميع الأطراف على بياناتها</div>

        </div><!-- .kmb-inner -->
    </div><!-- .kmb-outer -->

</div><!-- نهاية الصفحة المدمجة -->

<?php endforeach; ?>

<script src="/js-new/jquery-3.3.1.min.js"></script>
<script src="/js/Tafqeet.js"></script>
<script>
$(function(){
    var amt = <?= (int)round(($notes[0]->amount ?? 0)) ?>;
    var words = tafqeet(amt) + ' دينار أردني فقط لا غير';
    $('.kmb-words-text').text(words);
});
</script>
</body>
</html>
