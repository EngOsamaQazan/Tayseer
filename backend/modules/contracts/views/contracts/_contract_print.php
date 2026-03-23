<?php
/**
 * صفحة طباعة العقد — صفحة A4 واحدة
 * تصميم ذكي: يُظهر فقط الكفلاء الموجودين فعلياً
 * رقم العقد بارز بخط أحمر كبير
 */
use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;

$cc = new CompanyChecked();
$primary = $cc->findPrimaryCompany();
$logo = ($primary && $primary->logo) ? $primary->logo : (Yii::$app->params['companies_logo'] ?? '');
$companyName = $primary ? $primary->name : '';
$companyBanks = $primary ? $cc->findPrimaryCompanyBancks() : '';

$total = $model->total_value ?: 0;
$first = $model->first_installment_value ?: 0;
$monthly = $model->monthly_installment_value ?: 0;
$afterFirst = $total - $first;
/* due_date تُحسب تلقائياً في afterFind() */

/* جمع بيانات الأطراف */
$allPeople = $model->customersAndGuarantor; // المدين + الكفلاء
$guarantors = $model->guarantor;            // الكفلاء فقط
$gCount = count($guarantors);
$hasGuarantors = $gCount > 0;

/* أسماء الكفلاء بالترتيب */
$gLabels = ['الأول','الثاني','الثالث','الرابع','الخامس'];
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>عقد بيع #<?= $model->id ?></title>
<style>
/* ═══ A4 Print ═══ */
@page { size: A4 portrait; margin: 8mm 10mm 8mm 10mm; }
*{ margin:0; padding:0; box-sizing:border-box; }
body{ direction:rtl; font-family:'DinNextRegular','Cairo','Segoe UI',sans-serif; color:#1a1a1a; font-size:13.5px; line-height:1.6; background:#fff; }
@font-face{font-family:'DinNextRegular';src:url('/css-new/fonts/din-next/regular/DinNextRegular.woff2') format('woff2'),url('/css-new/fonts/din-next/regular/DinNextRegular.woff') format('woff'),url('/css-new/fonts/din-next/regular/DinNextRegular.ttf') format('truetype');}
@font-face{font-family:'DinNextBold';src:url('/css-new/fonts/din-next/bold/DinNextBold.woff2') format('woff2'),url('/css-new/fonts/din-next/bold/DinNextBold.woff') format('woff'),url('/css-new/fonts/din-next/bold/DinNextBold.ttf') format('truetype');}
@font-face{font-family:'DinNextMedium';src:url('/css-new/fonts/din-next/medium/DinNextMedium.woff2') format('woff2'),url('/css-new/fonts/din-next/medium/DinNextMedium.woff') format('woff'),url('/css-new/fonts/din-next/medium/DinNextMedium.ttf') format('truetype');}
b,strong,.b{font-family:'DinNextBold',sans-serif!important;}

.page{ width:100%; max-width:190mm; margin:0 auto; }

/* ═══ Header ═══ */
.hdr{ display:flex; align-items:flex-start; gap:12px; padding-bottom:10px; border-bottom:4px solid #4caf50; margin-bottom:10px; position:relative; }
.hdr-logo{ width:130px; flex-shrink:0; }
.hdr-logo img{ width:130px; height:auto; object-fit:contain; }
.hdr-center{ flex:1; text-align:center; padding-top:8px; }
.hdr-center h2{ font-size:20px; color:#2e7d32; margin:0 0 4px; font-family:'DinNextBold',sans-serif; }
.hdr-center .hdr-date{ font-size:12px; color:#666; margin-top:2px; }

/* رقم العقد — أحمر كبير بارز */
.contract-num{ position:absolute; top:0; left:0; background:#e53935; color:#fff; font-family:'DinNextBold',sans-serif; font-size:28px; padding:6px 18px 4px; border-radius:0 0 12px 0; line-height:1.2; letter-spacing:1px; }
.contract-num small{ display:block; font-size:10px; font-family:'DinNextRegular',sans-serif; letter-spacing:0; opacity:.85; }

/* صور العملاء */
.ppl-photos{ display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; justify-content:center; }
.ppl-photo{ width:85px; height:105px; object-fit:cover; border:2px solid #ddd; border-radius:6px; }

/* ═══ Parties ═══ */
.parties{ margin-bottom:8px; font-size:14px; }
.party-row{ display:flex; gap:6px; margin-bottom:3px; }
.party-label{ font-family:'DinNextBold',sans-serif; min-width:90px; color:#2e7d32; }

/* ═══ Terms ═══ */
.terms{ background:#f9faf8; border:1px solid #e0e5db; border-radius:6px; padding:10px 14px; margin-bottom:10px; }
.terms p{ margin-bottom:4px; font-size:12.5px; line-height:1.6; text-align:justify; }
.terms p:last-child{ margin-bottom:0; }
.terms .num{ font-family:'DinNextBold',sans-serif; color:#2e7d32; }

/* ═══ Contract Body — Grid ═══ */
.body-grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:10px; }
.info-box{ border:1px solid #ddd; border-radius:6px; padding:10px 12px; }
.info-box h4{ font-size:13px; font-family:'DinNextBold',sans-serif; color:#2e7d32; margin:0 0 6px; border-bottom:2px solid #e8f5e9; padding-bottom:4px; }
.info-row{ display:flex; justify-content:space-between; margin-bottom:4px; font-size:12.5px; }
.info-row .lbl{ color:#555; }
.info-row .val{ font-family:'DinNextBold',sans-serif; }
.info-row .val.money{ color:#1565c0; }

/* ═══ Signatures — ذكية ═══ */
.sigs{ margin-top:10px; }
.sig-grid{ display:grid; gap:8px; margin-bottom:8px; }
/* عدد أعمدة التوقيع يتغير ديناميكياً */
.sig-grid.cols-1{ grid-template-columns:1fr; }
.sig-grid.cols-2{ grid-template-columns:1fr 1fr; }
.sig-grid.cols-3{ grid-template-columns:1fr 1fr 1fr; }
.sig-grid.cols-4{ grid-template-columns:1fr 1fr 1fr 1fr; }

.sig-card{ border:1px solid #c8e6c9; border-radius:6px; overflow:hidden; }
.sig-card-hd{ background:#e8f5e9; color:#2e7d32; font-family:'DinNextBold',sans-serif; font-size:11px; padding:5px 8px; text-align:center; border-bottom:1px solid #c8e6c9; }
.sig-card-body{ height:65px; } /* مساحة فعلية للتوقيع بالقلم */

/* صف البائع + ملاحظات */
.footer-row{ display:flex; gap:12px; align-items:flex-start; margin-top:8px; }
.seller-sig{ width:120px; flex-shrink:0; }
.seller-sig .sig-card-body{ height:50px; }
.notes-area{ flex:1; font-size:12px; color:#555; border:1px solid #eee; border-radius:6px; padding:8px 10px; min-height:50px; }
.notes-area b{ color:#333; }

/* ═══ Print ═══ */
@media print {
    body{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .no-print{ display:none!important; }
}
@media screen {
    body{ padding:10px; background:#eee; }
    .page{ background:#fff; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.15); }
    .print-btn{ position:fixed; top:15px; left:15px; z-index:999; background:#2e7d32; color:#fff; border:0; padding:10px 24px; border-radius:6px; font-size:14px; cursor:pointer; font-family:'DinNextBold',sans-serif; }
    .print-btn:hover{ background:#1b5e20; }
}
</style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨️ طباعة العقد</button>

<div class="page">

<!-- ═══ Header ═══ -->
<div class="hdr">
    <!-- شعار الشركة — كبير -->
    <div class="hdr-logo">
        <?php if ($logo): ?>
            <?= Html::img(Url::to(['/' . $logo]), ['style' => 'width:130px;height:auto;']) ?>
        <?php endif; ?>
    </div>

    <!-- عنوان + تاريخ -->
    <div class="hdr-center">
        <h2>عقد بيع بالتقسيط</h2>
        <div class="hdr-date">تاريخ البيع: <b><?= $model->Date_of_sale ?></b></div>

        <!-- صور جميع العملاء والكفلاء -->
        <div class="ppl-photos">
            <?php foreach ($allPeople as $person): ?>
                <?php if ($person->selectedImagePath): ?>
                    <img class="ppl-photo" src="<?= $person->selectedImagePath ?>" alt="<?= Html::encode($person->name) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- رقم العقد — أحمر كبير بارز في الزاوية -->
    <div class="contract-num">
        <small>رقم العقد</small>
        #<?= $model->id ?>
        <?php if ($model->type !== 'normal'): ?>
        <small style="margin-top:2px;font-size:11px;opacity:1;letter-spacing:.3px"><?= $model->getTypeLabel() ?></small>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Parties ═══ -->
<div class="parties">
    <div class="party-row">
        <span class="party-label">الطرف الأول :</span>
        <span><?= $companyName ?></span>
    </div>
    <div class="party-row">
        <span class="party-label">الطرف الثاني :</span>
        <span><?php
            $names = [];
            foreach ($allPeople as $c) { $names[] = $c->name; }
            echo implode(' و ', $names);
        ?></span>
    </div>
</div>

<!-- ═══ Terms ═══ -->
<div class="terms">
    <p>تعتبر هذه المقدمة جزءاً من العقد ونقر نحن المشتري والكفلاء بموافقتنا على البنود التالية وعددها <b>5</b></p>
    <p><span class="num">1-</span> <b>حالة البضاعة:</b> إننا استلمنا البضاعة الموصوفة بعد المعاينة سليمة وخالية من المشاكل والعيوب</p>
    <p><span class="num">2-</span> <b>الالتزام بالدفع:</b> يلتزم المشتري والكفلاء متضامنين ومتكافلين بدفع ثمن البضاعة المذكورة بالعقد وتحمل كافة المصاريف القضائية وغير القضائية في حالة تخلفنا عن دفع أي قسط من الأقساط المذكورة ويعتبر كامل المبلغ مستحق.</p>
    <p><span class="num">3-</span> <b>طريقة الدفع:</b> نلتزم بدفع الأقساط في موعدها من خلال eFAWATEERcom تبويب تمويل وخدمات مالية - <?= $companyName ?> - تسديد قسط - إدخال الرقم (<b style="color:#e53935"><?= $model->id ?></b>) ثم إتمام الدفع أو في حساب الشركة في <b><?= $companyBanks ?></b></p>
    <p><span class="num">4-</span> <b>كفالة وإرجاع البضاعة:</b> كفالة الوكيل حسب الشركة الموزعة والبضاعة المباعة لا تُرد ولا تُستبدل ونلتزم بخسارة (<b><?= $model->loss_commitment ?: 'صفر' ?></b>) دينار إذا أردنا إرجاع البضاعة بمدة لا تزيد عن 24 ساعة من تاريخ البيع ولا يمكن إرجاع البضاعة بعد مضي 24 ساعة مهما كانت الأحوال</p>
    <p><span class="num">5-</span> <b>الشركة غير مسؤولة عن:</b> سعر البضاعة خارج فروعها وعن أي اتفاقية أو مبلغ غير موثق في العقد</p>
</div>

<!-- ═══ Body — Debtors + Financial ═══ -->
<div class="body-grid">
    <!-- بيانات المدين والكفلاء -->
    <div class="info-box">
        <h4>بيانات المدين والكفلاء</h4>
        <?php foreach ($allPeople as $i => $c): ?>
        <div class="info-row">
            <span class="lbl"><?= $i === 0 ? 'المدين' : 'كفيل ' . ($gLabels[$i-1] ?? $i) ?></span>
            <span class="val"><?= $c->name ?></span>
            <span style="color:#777;font-size:10.5px"><?= $c->id_number ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- بيانات العقد المالية -->
    <div class="info-box">
        <h4>البيانات المالية</h4>
        <div class="info-row"><span class="lbl">البائع</span><span class="val"><?= $model->seller ? $model->seller->name : '—' ?></span></div>
        <div class="info-row"><span class="lbl">نوع العقد</span><span class="val"><?= $model->getTypeLabel() ?></span></div>
        <div class="info-row"><span class="lbl">المبلغ الإجمالي</span><span class="val money"><?= number_format($total) ?> د.أ</span></div>
        <div class="info-row"><span class="lbl">الدفعة الأولى</span><span class="val money"><?= number_format($first) ?> د.أ</span></div>
        <div class="info-row"><span class="lbl">المبلغ بعد الدفعة</span><span class="val money"><?= number_format($afterFirst) ?> د.أ</span></div>
        <div class="info-row"><span class="lbl">القسط الشهري</span><span class="val money"><?= number_format($monthly) ?> د.أ</span></div>
        <div class="info-row"><span class="lbl">تاريخ أول قسط</span><span class="val"><?= $model->first_installment_date ?></span></div>
        <div class="info-row"><span class="lbl">تاريخ الاستحقاق</span><span class="val"><?= $model->due_date ?></span></div>
    </div>
</div>

<!-- ═══ Signatures — ذكية: تظهر فقط الكفلاء الموجودين ═══ -->
<div class="sigs">
    <?php
    /* حساب عدد أعمدة التوقيع: المدين + الكفلاء الفعليين */
    $sigCount = 1 + $gCount; /* المدين دائماً + عدد الكفلاء الفعلي */
    /* تحديد فئة الأعمدة — حد أقصى 4 بالصف */
    $row1Count = min($sigCount, 4);
    $row2Count = max($sigCount - 4, 0);
    ?>

    <!-- الصف الأول: المدين + أول 3 كفلاء -->
    <div class="sig-grid cols-<?= $row1Count ?>">
        <!-- المدين دائماً يظهر -->
        <div class="sig-card">
            <div class="sig-card-hd">توقيع المدين</div>
            <div class="sig-card-body"></div>
        </div>
        <?php for ($i = 0; $i < min($gCount, 3); $i++): ?>
            <div class="sig-card">
                <div class="sig-card-hd">توقيع الكفيل <?= $gLabels[$i] ?></div>
                <div class="sig-card-body"></div>
            </div>
        <?php endfor; ?>
    </div>

    <?php if ($row2Count > 0): ?>
    <!-- الصف الثاني: كفلاء إضافيون (4 و 5) -->
    <div class="sig-grid cols-<?= $row2Count ?>">
        <?php for ($i = 3; $i < $gCount; $i++): ?>
            <div class="sig-card">
                <div class="sig-card-hd">توقيع الكفيل <?= $gLabels[$i] ?? ($i+1) ?></div>
                <div class="sig-card-body"></div>
            </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- البائع + ملاحظات -->
<div class="footer-row">
    <div class="seller-sig">
        <div class="sig-card">
            <div class="sig-card-hd">توقيع البائع</div>
            <div class="sig-card-body"></div>
        </div>
    </div>
    <div class="notes-area">
        <b>ملاحظات:</b> <?= $model->notes ?: 'لا يوجد أي خصومات التزام' ?>
    </div>
</div>

</div><!-- .page -->

<script src="/js-new/jquery-3.3.1.min.js"></script>
<script src="/js/Tafqeet.js"></script>
</body>
</html>
