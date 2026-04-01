<?php
/**
 * معاينة الطباعة الموحدة — مستوى مطبعة تجارية رسمية
 * ═══════════════════════════════════════════════════════
 *   الصفحة 1 : عقد البيع بالتقسيط
 *   الصفحات 2-4 : اتفاقية الموطن المختار + كمبيالة تنفيذية (×3)
 *
 * @var $model      backend\modules\contracts\models\Contracts
 * @var $notes      backend\modules\contracts\models\PromissoryNote[]
 * @var $allPeople  backend\modules\contracts\models\Customers[]  (buyers first, then guarantors)
 * @var $guarantors backend\modules\contracts\models\Customers[]
 * @var $pCount     int
 * @var $density    string  'normal'|'tight'
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
$lawyerFees = $total * 0.15;
$totalWithFees = $total * 1.15;
$today      = date('Y-m-d');

$gCount      = count($guarantors);
$buyerCount  = $pCount - $gCount;
$gLabels     = ['الأول','الثاني','الثالث','الرابع','الخامس'];

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
<link rel="stylesheet" href="/css-new/style.css" media="all">
<style>
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
.ct-bar{height:5px;background:#1a365d;border-radius:2px}
.ct-hdr{display:flex;align-items:flex-start;padding:14px 0 12px;gap:14px}
.ct-hdr-logo{width:100px;flex-shrink:0}
.ct-hdr-logo img{width:100px;height:auto}
.ct-hdr-center{flex:1;text-align:center}
.ct-hdr-center h2{font-family:'DinNextBold',sans-serif;font-size:21px;color:#1a365d;margin:0 0 3px}
.ct-hdr-center .ct-date{font-size:12px;color:#555;margin-top:3px}
.ct-hdr-info{text-align:left;min-width:130px}
.ct-no{border:2.5px solid #c62828;border-radius:6px;padding:5px 16px;text-align:center;display:inline-block}
.ct-no small{display:block;font-size:9px;color:#888;font-family:'DinNextRegular',sans-serif}
.ct-no strong{display:block;font-size:24px;color:#c62828;font-family:'DinNextBold',sans-serif;letter-spacing:1px;line-height:1.2}
.ct-photos{display:flex;gap:6px;justify-content:center;margin:8px 0;flex-wrap:wrap}
.ct-photos img{width:68px;height:85px;object-fit:cover;border:1.5px solid #ccc;border-radius:5px}
.ct-section{margin-bottom:12px}
.ct-section-title{font-family:'DinNextBold',sans-serif;font-size:13.5px;color:#1a365d;border-bottom:2.5px solid #1a365d;padding-bottom:4px;margin-bottom:8px;text-align:center}
.ct-party{display:flex;gap:5px;margin-bottom:5px;font-size:13px}
.ct-party-label{font-family:'DinNextBold',sans-serif;color:#1a365d;min-width:130px}
.ct-party-sub{font-size:11.5px;color:#555;margin-right:8px}
.ct-terms{font-size:12.5px;line-height:1.75}
.ct-terms p{margin-bottom:6px;text-align:justify}
.ct-terms .ct-num{font-family:'DinNextBold',sans-serif;color:#1a365d}
.ct-solidarity{border:1.5px solid #1a365d;border-radius:5px;padding:7px 12px;margin:6px 0;background:#f0f4f8}
.ct-solidarity p{margin:0;font-size:12.5px}
.ct-fin-tbl{width:100%;border-collapse:collapse;margin:10px 0;font-size:13px}
.ct-fin-tbl th{background:#1a365d;color:#fff;font-family:'DinNextBold',sans-serif;padding:7px 14px;text-align:center;font-size:12.5px;border:1px solid #1a365d}
.ct-fin-tbl td{border:1px solid #ccc;padding:6px 14px}
.ct-fin-tbl td:first-child{font-family:'DinNextMedium',sans-serif;color:#333;width:45%}
.ct-fin-tbl td:last-child{font-family:'DinNextBold',sans-serif;text-align:center;color:#1a365d}
.ct-fin-tbl tr:nth-child(even){background:#f8f9fa}
.ct-fin-tbl .ct-money{color:#c62828;font-size:14px}
.ct-sig-tbl{width:100%;border-collapse:collapse;margin-top:8px}
.ct-sig-tbl th{font-family:'DinNextBold',sans-serif;font-size:11.5px;color:#1a365d;padding:5px 8px;border-bottom:2px solid #1a365d;text-align:center}
.ct-sig-tbl .ct-sig-tbl-names td{font-size:10.5px;color:#333;padding:4px 6px;text-align:center;border-bottom:1px dashed #ccc;font-family:'DinNextMedium',sans-serif}
.ct-sig-tbl .ct-sig-tbl-signs td{height:50px;border-bottom:1.5px solid #999}
.ct-sig-tbl .ct-sig-tbl-stamp{width:70px}
.ct-sig-tbl .ct-sig-tbl-stamp-cell{vertical-align:middle;text-align:center;border-bottom:none}
.ct-stamp{width:60px;height:60px;border:2px dashed #999;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:9px;color:#999;text-align:center;font-family:'DinNextMedium',sans-serif;line-height:1.3}
.ct-notes{font-size:11.5px;color:#555;border:1px solid #eee;border-radius:5px;padding:8px 12px;margin-top:10px}
.ct-notes b{color:#333}

/* ══════════════════════════════════════════════════════════
   الصفحة المدمجة — اتفاقية + كمبيالة
   مستوى مطبعة تجارية رسمية
   ══════════════════════════════════════════════════════════ */

/* ─── إطار الاتفاقية ─── */
.agr-frame{
    border:1px solid #888;padding:16px 20px;border-radius:2px;
}
.agr-ttl{
    text-align:center;font-family:'DinNextBold',sans-serif;
    font-size:20px;color:#1a1a1a;margin:0 0 12px;
    letter-spacing:.5px;padding-bottom:8px;
    border-bottom:2.5px solid #333;
}
.agr-pty{font-size:15px;margin-bottom:5px;font-family:'DinNextBold',sans-serif}
.agr-pty b{color:#1a1a1a}
.agr-txt{font-size:14px;line-height:1.75;text-align:justify;margin:7px 0;font-family:'DinNextMedium',sans-serif}

/* صناديق Overlay — منقطة، مهيأة لطباعة لاحقة بخط 16px Bold */
.ovl-wrap{margin:8px 0}
.ovl-lbl{font-size:14px;font-family:'DinNextBold',sans-serif;color:#1a1a1a;margin-bottom:3px}
.ovl-box{
    border:2px dashed #555;border-radius:3px;
    min-height:40px;width:100%;background:#fff;
}

/* جدول توقيع الاتفاقية — أفقي */
.agr-stbl{width:100%;border-collapse:collapse;margin:10px 0}
.agr-stbl th{
    font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a1a1a;
    padding:5px 8px;border-bottom:2px solid #1a1a1a;text-align:center;
}
.agr-stbl .agr-stbl-name{
    padding:5px 6px;font-size:11.5px;text-align:center;
    font-family:'DinNextMedium',sans-serif;border-bottom:1px dashed #ccc;
}
.agr-stbl .agr-stbl-id{font-size:10px;color:#555}
.agr-stbl .agr-stbl-sig-row td{height:40px;border-bottom:1.5px solid #888}

/* ─── فاصل اتفاقية/كمبيالة ─── */
.agr-kmb-divider{border:none;border-top:2px solid #888;margin:10px 0}

/* ─── الفاصل البصري ─── */
.sep{display:flex;align-items:center;margin:6px 0 8px}
.sep::before,.sep::after{content:'';flex:1;height:3px;background:#1a1a1a}
.sep-text{
    padding:2px 28px;font-family:'DinNextBold',sans-serif;
    font-size:20px;letter-spacing:6px;color:#1a1a1a;white-space:nowrap;
}

/* ─── الكمبيالة — إطار مزدوج رسمي ─── */
.kmb-outer{border:2.5px solid #1a1a1a;padding:3px}
.kmb-inner{border:1px solid #1a1a1a;padding:12px 14px}

/* رأس الكمبيالة */
.kmb-hdr{
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:10px;padding-bottom:7px;border-bottom:1.5px solid #ccc;
}
.kmb-no-box{
    border:2px solid #1a1a1a;border-radius:3px;padding:4px 18px;text-align:center;
}
.kmb-no-lbl{display:block;font-size:9px;color:#555;font-family:'DinNextMedium',sans-serif}
.kmb-no-val{font-family:'DinNextBold',sans-serif;font-size:18px;color:#1a1a1a}
/* تاريخ الاستحقاق — محاذى يسار، بجانب المبلغ، إطار أحمر */
.kmb-due-box{
    margin-right:auto;text-align:center;
    border:2.5px solid #c62828;border-radius:4px;padding:4px 16px;
}
.kmb-due-box small{display:block;font-size:9px;color:#555;font-family:'DinNextMedium',sans-serif}
.kmb-due-box strong{font-family:'DinNextBold',sans-serif;font-size:15px;color:#c62828;display:block;line-height:1.2}

/* جدول بيانات الأطراف — اسم | رقم وطني | موطن مختار (overlay) */
.kmb-ptbl{width:100%;border-collapse:collapse;margin:8px 0;font-size:13px}
.kmb-ptbl td{padding:5px 7px;border-bottom:1px solid #ccc;vertical-align:middle}
.kmb-ptbl .pr-role{font-family:'DinNextBold',sans-serif;color:#1a1a1a;width:8%;white-space:nowrap;font-size:13px}
.kmb-ptbl .pr-name{width:22%;font-family:'DinNextBold',sans-serif;font-size:13px}
.kmb-ptbl .pr-id-lbl{font-family:'DinNextBold',sans-serif;color:#333;width:10%;font-size:11px;white-space:nowrap}
.kmb-ptbl .pr-id{width:16%;text-align:center;font-family:'DinNextBold',sans-serif;font-size:13px}
.kmb-ptbl .pr-addr-lbl{font-family:'DinNextBold',sans-serif;color:#333;width:10%;font-size:11px;white-space:nowrap}
.kmb-ptbl .pr-addr{border-bottom:2px dashed #555!important}

/* الصف الرئيسي — Court(overlay) | والدفع بها | المبلغ | الاستحقاق */
.kmb-main{display:flex;align-items:center;gap:12px;margin:12px 0}
.kmb-court-box{width:210px;flex-shrink:0;border:2px dashed #555;min-height:34px;border-radius:2px}
.kmb-pay{font-family:'DinNextBold',sans-serif;font-size:15px;white-space:nowrap}
.kmb-amt{
    border:2.5px solid #c62828;border-radius:4px;padding:5px 18px;
    text-align:center;min-width:130px;
}
.kmb-amt small{display:block;font-size:8px;color:#555}
.kmb-amt strong{font-family:'DinNextBold',sans-serif;font-size:22px;color:#c62828;display:block;line-height:1.2}
/* المبلغ كتابة */
.kmb-words{
    font-size:14px;margin:6px 0;padding:6px 0;
    border-bottom:1px solid #ddd;font-family:'DinNextBold',sans-serif;
}
.kmb-words b{color:#c62828;font-family:'DinNextBold',sans-serif}

/* نصوص */
.kmb-p{font-size:13px;margin:5px 0;font-family:'DinNextMedium',sans-serif}

/* جدول توقيع الكمبيالة — أفقي */
.kmb-stbl{width:100%;border-collapse:collapse;margin:8px 0}
.kmb-stbl th{
    font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a1a1a;
    padding:5px 8px;border-bottom:2px solid #1a1a1a;text-align:center;
}
.kmb-stbl .kmb-stbl-name{
    padding:4px 6px;font-size:11.5px;text-align:center;
    font-family:'DinNextMedium',sans-serif;border-bottom:1px dashed #ccc;
}
.kmb-stbl .kmb-stbl-id{font-size:10px;color:#555}
.kmb-stbl .kmb-stbl-sig-row td{height:36px;border-bottom:1.5px solid #555}

.kmb-pnote{font-size:10px;color:#555;font-style:italic;text-align:center;margin-top:6px}

/* ═══════════════════════════════════════════════════════
   الطبقة 1: parties-N — حسب عدد الأطراف
   ═══════════════════════════════════════════════════════ */
.parties-1 .ct-sig-tbl .ct-sig-tbl-signs td{height:60px}
.parties-1 .ct-photos img {width:75px;height:94px}
.parties-1 .ct-section    {margin-bottom:14px}
.parties-1 .ct-hdr        {padding:14px 0 12px}

.parties-2 .ct-sig-tbl .ct-sig-tbl-signs td{height:55px}
.parties-2 .ct-photos img {width:70px;height:88px}
.parties-2 .ct-section    {margin-bottom:12px}

.parties-3 .ct-sig-tbl .ct-sig-tbl-signs td{height:45px}
.parties-3 .ct-photos img {width:60px;height:75px}
.parties-3 .ct-section    {margin-bottom:10px}

.parties-4 .ct-sig-tbl .ct-sig-tbl-signs td{height:40px}
.parties-4 .ct-photos img {width:55px;height:68px}
.parties-4 .ct-section    {margin-bottom:8px}
.parties-4 .ct-hdr        {padding:10px 0 8px}

.parties-5 .ct-sig-tbl .ct-sig-tbl-signs td{height:35px}
.parties-5 .ct-photos img {width:50px;height:62px}
.parties-5 .ct-section    {margin-bottom:6px}
.parties-5 .ct-hdr        {padding:8px 0 6px}

/* ═══════════════════════════════════════════════════════
   الطبقة 2: density — كثافة المحتوى
   ═══════════════════════════════════════════════════════ */
.density-normal .ct-terms      {font-size:12.5px;line-height:1.75}
.density-normal .ct-fin-tbl td {padding:6px 14px}
.density-normal .agr-txt       {font-size:14px;line-height:1.75}
.density-normal .agr-pty       {font-size:15px;margin-bottom:5px}
.density-normal .ct-party      {margin-bottom:5px}

.density-tight .ct-terms       {font-size:11.5px;line-height:1.55}
.density-tight .ct-fin-tbl td  {padding:4px 10px}
.density-tight .ct-fin-tbl th  {padding:5px 10px;font-size:11.5px}
.density-tight .agr-txt        {font-size:12.5px;line-height:1.55}
.density-tight .agr-pty        {font-size:13px;margin-bottom:3px}
.density-tight .ct-party       {margin-bottom:2px;font-size:12px}
.density-tight .ct-section-title{font-size:12.5px;margin-bottom:5px;padding-bottom:3px}
.density-tight .ct-solidarity  {padding:5px 10px;margin:4px 0}
.density-tight .ct-solidarity p{font-size:11.5px}
.density-tight .ct-notes       {padding:5px 10px;font-size:10.5px}
.density-tight .agr-ttl        {font-size:17px;margin:0 0 8px;padding-bottom:6px}
.density-tight .agr-frame      {padding:12px 16px}
.density-tight .sep            {margin:4px 0 6px}
.density-tight .sep-text       {font-size:17px;letter-spacing:4px;padding:2px 20px}
.density-tight .agr-kmb-divider{margin:6px 0}
.density-tight .kmb-inner      {padding:8px 10px}
.density-tight .kmb-ptbl td    {padding:3px 5px;font-size:12px}
.density-tight .kmb-stbl .kmb-stbl-name{font-size:10.5px;padding:3px 5px}
.density-tight .kmb-stbl .kmb-stbl-sig-row td{height:30px}
.density-tight .kmb-stbl th   {font-size:11px;padding:4px 6px}
.density-tight .ct-sig-tbl .ct-sig-tbl-signs td{height:35px}
.density-tight .ct-sig-tbl th {font-size:10.5px;padding:4px 5px}
.density-tight .agr-stbl .agr-stbl-sig-row td{height:32px}
.density-tight .agr-stbl th   {font-size:11px;padding:4px 6px}
.density-tight .kmb-words      {font-size:13px;padding:4px 0;margin:4px 0}
.density-tight .kmb-p          {font-size:12px;margin:3px 0}
.density-tight .ovl-wrap       {margin:5px 0}

/* ═══════════════════════════════════════════════════════
   الطبقة 3: ضغط إضافي — أطراف متعددة + كثافة عالية
   ═══════════════════════════════════════════════════════ */
.parties-3.density-tight .ct-terms       {font-size:11px;line-height:1.45}
.parties-3.density-tight .ct-terms p     {margin-bottom:4px}
.parties-3.density-tight .ct-solidarity  {padding:4px 8px;margin:3px 0}
.parties-3.density-tight .ct-solidarity p{font-size:11px}
.parties-3.density-tight .ct-fin-tbl td  {padding:3px 8px;font-size:11.5px}
.parties-3.density-tight .ct-fin-tbl th  {padding:4px 8px;font-size:11px}
.parties-3.density-tight .ct-section     {margin-bottom:6px}
.parties-3.density-tight .ct-notes       {padding:4px 8px;font-size:10px}

.parties-4.density-tight .ct-terms       {font-size:10.5px;line-height:1.4}
.parties-4.density-tight .ct-terms p     {margin-bottom:3px}
.parties-4.density-tight .ct-solidarity  {padding:3px 7px;margin:2px 0}
.parties-4.density-tight .ct-solidarity p{font-size:10.5px}
.parties-4.density-tight .ct-fin-tbl td  {padding:2px 7px;font-size:11px}
.parties-4.density-tight .ct-fin-tbl th  {padding:3px 7px;font-size:10.5px}
.parties-4.density-tight .ct-section     {margin-bottom:4px}
.parties-4.density-tight .ct-section-title{font-size:11.5px;margin-bottom:4px;padding-bottom:2px}
.parties-4.density-tight .ct-hdr         {padding:6px 0 4px}
.parties-4.density-tight .ct-photos img  {width:48px;height:60px}
.parties-4.density-tight .ct-sig-tbl .ct-sig-tbl-signs td{height:30px}
.parties-4.density-tight .ct-sig-tbl th  {font-size:10px;padding:3px 4px}
.parties-4.density-tight .ct-notes       {padding:3px 7px;font-size:9.5px}

.parties-5.density-tight .ct-terms       {font-size:10px;line-height:1.35}
.parties-5.density-tight .ct-terms p     {margin-bottom:2px}
.parties-5.density-tight .ct-solidarity  {padding:2px 6px;margin:2px 0}
.parties-5.density-tight .ct-solidarity p{font-size:10px}
.parties-5.density-tight .ct-fin-tbl td  {padding:2px 6px;font-size:10.5px}
.parties-5.density-tight .ct-fin-tbl th  {padding:2px 6px;font-size:10px}
.parties-5.density-tight .ct-section     {margin-bottom:3px}
.parties-5.density-tight .ct-section-title{font-size:11px;margin-bottom:3px;padding-bottom:2px}
.parties-5.density-tight .ct-hdr         {padding:4px 0 3px}
.parties-5.density-tight .ct-photos img  {width:42px;height:52px}
.parties-5.density-tight .ct-sig-tbl .ct-sig-tbl-signs td{height:25px}
.parties-5.density-tight .ct-sig-tbl th  {font-size:9.5px;padding:2px 3px}
.parties-5.density-tight .ct-notes       {padding:2px 6px;font-size:9px}

/* ═══ طباعة / شاشة ═══ */
@media print{
    body{-webkit-print-color-adjust:exact;print-color-adjust:exact;background:#fff!important}
    .toolbar,.page-sep{display:none!important}
    .print-page{
        margin:0;padding:0;box-shadow:none;max-width:100%;
        page-break-after:always;page-break-inside:avoid;
    }
    .print-page:last-child{page-break-after:auto}
    .ct-solidarity{border-color:#333!important}

    .ovl-box{min-height:34px!important}
    .kmb-court-box{min-width:200px!important;min-height:30px!important}
    .kmb-outer{page-break-inside:avoid}
}
@media screen{
    body{background:#cbd5e1;padding:0}
    .print-page{background:#fff;padding:16px 20px;margin:16px auto;box-shadow:0 4px 20px rgba(0,0,0,.12);border-radius:3px}
}
</style>
</head>
<body class="parties-<?= $pCount ?> density-<?= $density ?>">

<!-- ═══ شريط الأدوات ═══ -->
<div class="toolbar">
    <a class="tb-back" href="<?= Url::to(['view', 'id' => $model->id]) ?>">← العودة</a>
    <h1>معاينة الطباعة</h1>
    <span class="tb-info">4 صفحات — العقد + 3 كمبيالات</span>
    <span class="tb-id">#<?= $model->id ?></span>
    <?php if ($model->type !== 'normal'): ?>
    <span style="background:#c62828;color:#fff;padding:3px 12px;border-radius:6px;font-size:13px;font-family:'DinNextBold',sans-serif"><?= $model->getTypeLabel() ?></span>
    <?php endif; ?>
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
            <?php if ($model->type !== 'normal'): ?>
            <div style="margin-top:4px;text-align:center;font-family:'DinNextBold',sans-serif;font-size:13px;color:#c62828;letter-spacing:.5px">
                <?= $model->getTypeLabel() ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">أطراف العقد</div>
        <div class="ct-party">
            <span class="ct-party-label">الطرف الأول (البائع):</span>
            <span><?= $companyName ?></span>
        </div>
        <div class="ct-party">
            <span class="ct-party-label">الطرف الثاني:</span>
            <span><?= $allNames ?></span>
        </div>
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
            <p><span class="ct-num">6.</span> <b>السندات التنفيذية (الكمبيالات):</b> يُقرّ الطرف الثاني بتوقيعه على الكمبيالات التالية: <?php foreach ($notes as $ni => $note): ?>كمبيالة رقم (<b><?= $note->getDisplayNumber() ?></b>) بقيمة (<b><?= number_format($note->amount, 2) ?></b>) دينار أردني<?= $ni < count($notes) - 1 ? '، ' : '.' ?><?php endforeach; ?> وتُعبّر جميعها عن دين واحد لا يتجزأ، وقد تم التوقيع عليها لغايات حفظ الحق فقط، ولا يجوز المطالبة بسداد الدين أكثر من مرة بحجة تعدد السندات التنفيذية.</p>
            <?php if ($model->type === 'direct_deduction'): ?>
            <div class="ct-solidarity" style="border-color:#c62828;background:#fef2f2">
                <p style="font-family:'DinNextBold',sans-serif;font-size:13px"><b><span class="ct-num" style="color:#c62828">7.</span> الاقتطاع المباشر والتحصيل: يُقرّ الطرف الثاني بأن سداد الأقساط يتم بالاقتطاع المباشر من راتبه الشهري. وفي حال لم يتجاوز مبلغ الاقتطاع (20) عشرين ديناراً شهرياً، يحق للطرف الأول طلب الحجز على جميع أملاك الطرف الثاني المنقولة وغير المنقولة واتخاذ كافة السبل القانونية لتحصيل الدين، ويُعتبر الدين بأكمله مستحقاً فوراً وفقاً للسند التنفيذي (الكمبيالة). ولا يتحمل الطرف الأول أي مسؤولية عن مقدار الاقتطاع الشهري بالغاً ما بلغ ما دام في حدود الدين المُطالب به.</b></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">البيانات المالية</div>
        <table class="ct-fin-tbl">
            <thead><tr><th>البيان</th><th>القيمة</th></tr></thead>
            <tbody>
                <tr><td>الدفعة الأولى</td><td class="ct-money"><?= number_format($first) ?> د.أ</td></tr>
                <tr><td>صافي المطالبة بعد الدفعة الأولى</td><td class="ct-money"><b><?= number_format($afterFirst) ?></b> د.أ</td></tr>
                <?php if ($model->type !== 'direct_deduction'): ?>
                <tr><td>القسط الشهري</td><td class="ct-money"><?= number_format($monthly) ?> د.أ</td></tr>
                <tr><td>تاريخ أول قسط</td><td><?= $model->first_installment_date ?></td></tr>
                <tr><td>تاريخ الاستحقاق النهائي</td><td><b><?= $model->due_date ?></b></td></tr>
                <?php else: ?>
                <tr><td>أتعاب المحاماة (15%)</td><td class="ct-money"><?= number_format($lawyerFees) ?> د.أ</td></tr>
                <tr><td>المبلغ الإجمالي شاملاً أتعاب المحاماة</td><td class="ct-money"><b><?= number_format($totalWithFees) ?></b> د.أ</td></tr>
                <?php endif; ?>
                <tr><td>نوع العقد</td><td><?= $model->getTypeLabel() ?></td></tr>
                <tr><td>البائع</td><td><?= $sellerName ?: '—' ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="ct-section">
        <div class="ct-section-title">التوقيعات والإقرار</div>
        <table class="ct-sig-tbl">
            <thead>
                <tr>
                    <?php foreach ($allPeople as $c): ?>
                    <th>مدين</th>
                    <?php endforeach; ?>
                    <th>البائع</th>
                    <th class="ct-sig-tbl-stamp">ختم</th>
                </tr>
            </thead>
            <tbody>
                <tr class="ct-sig-tbl-names">
                    <?php foreach ($allPeople as $c): ?>
                    <td><?= $c->name ?></td>
                    <?php endforeach; ?>
                    <td><?= $sellerName ?></td>
                    <td rowspan="2" class="ct-sig-tbl-stamp-cell"><div class="ct-stamp">ختم<br>الشركة</div></td>
                </tr>
                <tr class="ct-sig-tbl-signs">
                    <?php foreach ($allPeople as $c): ?>
                    <td></td>
                    <?php endforeach; ?>
                    <td></td>
                </tr>
            </tbody>
        </table>
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

        <table class="agr-stbl">
            <thead>
                <tr>
                    <?php foreach ($allPeople as $c): ?>
                    <th>مدين</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($allPeople as $c): ?>
                    <td class="agr-stbl-name"><?= $c->name ?><br><span class="agr-stbl-id"><?= $c->id_number ?></span></td>
                    <?php endforeach; ?>
                </tr>
                <tr class="agr-stbl-sig-row">
                    <?php foreach ($allPeople as $c): ?>
                    <td></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>

    </div><!-- .agr-frame -->

    <hr class="agr-kmb-divider">

    <div class="kmb-outer">
        <div class="kmb-inner">

            <div class="sep">
                <span class="sep-text">كمبيالة</span>
            </div>

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
                    <td class="pr-role">مدين</td>
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

            <table class="kmb-stbl">
                <thead>
                    <tr>
                        <?php foreach ($allPeople as $c): ?>
                        <th>مدين</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($allPeople as $c): ?>
                        <td class="kmb-stbl-name"><?= $c->name ?><br><span class="kmb-stbl-id"><?= $c->id_number ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="kmb-stbl-sig-row">
                        <?php foreach ($allPeople as $c): ?>
                        <td></td>
                        <?php endforeach; ?>
                    </tr>
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

(function(){
    function fitPages(){
        var ruler = document.createElement('div');
        ruler.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:281mm;visibility:hidden;pointer-events:none';
        document.body.appendChild(ruler);
        var pageH = ruler.offsetHeight;
        document.body.removeChild(ruler);

        document.querySelectorAll('.print-page').forEach(function(page){
            page.style.zoom = '';
            var h = page.scrollHeight;
            if(h > pageH){
                var scale = Math.max(0.78, pageH / h);
                page.style.zoom = scale.toFixed(4);
            }
        });
    }

    window.addEventListener('load', fitPages);
    window.addEventListener('beforeprint', fitPages);
    window.addEventListener('afterprint', function(){
        document.querySelectorAll('.print-page').forEach(function(page){
            page.style.zoom = '';
        });
        setTimeout(function(){ fitPages(); }, 50);
    });
})();
</script>
</body>
</html>
