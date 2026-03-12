<?php
/**
 * طباعة سندات القضية — صفحتا A4
 * الصفحة 1: تعهد بصحة المعلومات
 * الصفحة 2: محضر طلبات تنفيذ سندات
 */
use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\lawyers\models\Lawyers;

$CompanyChecked = new CompanyChecked();
$CompanyChecked->id = $model->company_id;
$companyInfo = $CompanyChecked->findCompany();
$moj_logo = Yii::$app->params['moj_logo'];
if ($companyInfo == '') {
    $logo = Yii::$app->params['companies_logo'];
    $compay_name = '';
} else {
    $logo = $companyInfo->logo;
    $compay_name = $companyInfo->name;
}
$contractCalculations = new ContractCalculations($model->contract_id);
$total_value = $model->contract->total_value;
$lawyer_images = Lawyers::getLawyerImage($model->lawyer->id);

$ovlNotes = \backend\modules\contracts\models\PromissoryNote::find()
    ->where(['contract_id' => $model->contract_id])
    ->orderBy('sequence_number')
    ->all();
$hasNotes = !empty($ovlNotes);

if ($hasNotes) {
    $ovlNote = $ovlNotes[0];
    $ovlCourtName = $model->court ? $model->court->name : '';
    $ovlAddress = $model->informAddress ? $model->informAddress->address : '';
    $ovlShortAddress = $ovlAddress;
    if (($dp = mb_strpos($ovlAddress, ' - ')) !== false) {
        $ovlShortAddress = mb_substr($ovlAddress, $dp + mb_strlen(' - '));
    }
    $ovlAllPeople = $model->customersAndGuarantor;
    $ovlPhones = [];
    $ovlEmails = [];
    foreach ($ovlAllPeople as $p) {
        if (!empty($p->primary_phone_number)) $ovlPhones[] = \backend\helpers\PhoneHelper::toLocal($p->primary_phone_number);
        if (!empty($p->email)) $ovlEmails[] = $p->email;
    }
    $ovlPeopleNames = [];
    foreach ($ovlAllPeople as $c) { $ovlPeopleNames[] = $c->name; }
    $ovlAllNames = implode(' و ', $ovlPeopleNames);
    $ovlToday = date('Y-m-d');
}

$companyDocs = [];
if ($companyInfo && method_exists($companyInfo, 'getCommercialRegisterList')) {
    foreach ($companyInfo->getCommercialRegisterList() as $doc) {
        $doc['type'] = 'السجل التجاري';
        $companyDocs[] = $doc;
    }
}
if ($companyInfo && method_exists($companyInfo, 'getTradeLicenseList')) {
    foreach ($companyInfo->getTradeLicenseList() as $doc) {
        $doc['type'] = 'رخصة المهن';
        $companyDocs[] = $doc;
    }
}
?>

<style>
/* ═══ Reset & A4 Base ═══ */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #e0e0e0; font-family: 'Cairo', 'Segoe UI', 'Tahoma', sans-serif; }

/* ═══ شريط الأدوات ═══ */
.pc-toolbar {
    position: sticky; top: 0; z-index: 100;
    display: flex; justify-content: center; align-items: center; gap: 10px;
    padding: 12px 20px; background: #1a365d; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.pc-toolbar .pc-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 24px; border-radius: 8px; font-size: 14px; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer; transition: all .2s;
}
.pc-btn-print { background: #3b82f6; color: #fff; }
.pc-btn-print:hover { background: #2563eb; color: #fff; }
.pc-btn-back { background: rgba(255,255,255,0.15); color: #fff; }
.pc-btn-back:hover { background: rgba(255,255,255,0.25); color: #fff; }
.pc-toolbar .pc-info { color: rgba(255,255,255,0.7); font-size: 12px; margin: 0 12px; }

/* ═══ صفحة A4 ═══ */
.a4-page {
    width: 210mm; min-height: 297mm;
    margin: 20px auto; padding: 20mm 18mm;
    background: #fff;
    box-shadow: 0 2px 16px rgba(0,0,0,0.15);
    position: relative;
    page-break-after: always;
    font-size: 14px; line-height: 1.7; color: #1a1a1a;
    direction: rtl;
}
.a4-page:last-of-type { page-break-after: auto; }

/* ═══ ترويسة الصفحة ═══ */
.pc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 2px solid #333; padding-bottom: 12px; }
.pc-header-right { text-align: right; }
.pc-header-right h3 { font-size: 16px; font-weight: 800; margin: 0; }
.pc-header-right h4 { font-size: 14px; font-weight: 600; margin: 2px 0 0; color: #333; }
.pc-header-center { text-align: center; }
.pc-header-center img { width: 80px; height: auto; }
.pc-header-left { text-align: left; }
.pc-header-left h3 { font-size: 16px; font-weight: 800; margin: 0; }

/* ═══ عنوان القسم ═══ */
.pc-section-title {
    text-align: center; font-size: 16px; font-weight: 800;
    margin: 16px 0 12px; padding: 6px 0;
    border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;
}

/* ═══ الجداول ═══ */
.pc-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 13px; }
.pc-table th, .pc-table td { border: 1px solid #555; padding: 8px 10px; text-align: right; vertical-align: top; }
.pc-table th { background: #f0f0f0; font-weight: 700; font-size: 12.5px; }
.pc-table td { font-size: 13px; }

/* ═══ نصوص ═══ */
.pc-text { font-size: 14px; line-height: 2; margin-bottom: 10px; }
.pc-text b { font-weight: 800; }
.pc-signature-row { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 10px; border-top: 1px dashed #999; }
.pc-signature-box { text-align: center; width: 40%; }
.pc-signature-box h5 { font-size: 13px; font-weight: 700; margin-bottom: 40px; }

/* ═══ صور المحامي ═══ */
.pc-lawyer-images { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
.pc-lawyer-images img { max-width: 400px; max-height: 250px; object-fit: contain; }

/* ═══ الصفحة 2: المحضر ═══ */
.pc-record-layout { display: flex; gap: 0; min-height: 230mm; }
.pc-record-sidebar {
    width: 50mm; flex-shrink: 0;
    border-left: 2px solid #333; padding: 10px 8px;
    text-align: center; font-size: 13px; font-weight: 700;
}
.pc-record-sidebar h4 { font-size: 13px; margin: 8px 0; font-weight: 800; }
.pc-record-main { flex: 1; padding: 0 10px 0 0; }
.pc-record-main h4 { font-size: 14px; margin: 8px 0; line-height: 1.8; }
.pc-record-main h4 b { font-weight: 800; }

.pc-defendant { margin: 16px 0; padding: 10px 0; border-top: 1px dashed #ccc; }
.pc-defendant:first-of-type { border-top: none; }

/* ═══ مُحدد الصفحات ═══ */
.pc-page-selector {
    position: fixed; top: 60px; left: 20px; z-index: 99;
    background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 16px; width: 260px; font-size: 13px; direction: rtl;
    max-height: 80vh; overflow-y: auto;
}
.pc-page-selector h4 { font-size: 14px; font-weight: 800; margin: 0 0 12px; color: #1a365d; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
.pc-page-selector label {
    display: flex; align-items: center; gap: 8px; padding: 6px 8px;
    border-radius: 6px; cursor: pointer; transition: background .15s; margin-bottom: 2px;
}
.pc-page-selector label:hover { background: #f1f5f9; }
.pc-page-selector input[type="checkbox"] { width: 16px; height: 16px; accent-color: #3b82f6; flex-shrink: 0; }
.pc-page-selector .ps-name { flex: 1; color: #334155; }
.pc-page-selector .ps-badge { font-size: 10px; padding: 1px 6px; border-radius: 4px; font-weight: 600; }
.ps-badge-main { background: #dbeafe; color: #1d4ed8; }
.ps-badge-doc { background: #fef3c7; color: #92400e; }
.pc-sel-actions { display: flex; gap: 6px; margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
.pc-sel-actions button { flex: 1; padding: 6px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; background: #f8fafc; transition: all .15s; }
.pc-sel-actions button:hover { background: #e2e8f0; }

/* ═══ صفحة مرفق ═══ */
.pc-doc-img { width: 100%; height: auto; display: block; }

/* ═══ طباعة ═══ */
@media print {
    html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
    .pc-toolbar { display: none !important; }
    .pc-page-selector { display: none !important; }
    .main-footer { display: none !important; }
    .content-header { display: none !important; }
    .main-sidebar { display: none !important; }
    .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .content { padding: 0 !important; }
    .a4-page {
        width: 100%; min-height: auto;
        margin: 0; padding: 15mm 18mm;
        box-shadow: none; border: none;
        page-break-after: always;
    }
    .a4-page:last-of-type { page-break-after: auto; }
    .a4-page.pc-page-hidden { display: none !important; }
}
@page { size: A4; margin: 0; }

/* ═══ صفحة تعبئة الكمبيالة (overlay) ═══ */
@font-face{font-family:'DinNextRegular';src:url('/css-new/fonts/din-next/regular/DinNextRegular.woff2') format('woff2'),url('/css-new/fonts/din-next/regular/DinNextRegular.woff') format('woff'),url('/css-new/fonts/din-next/regular/DinNextRegular.ttf') format('truetype')}
@font-face{font-family:'DinNextBold';src:url('/css-new/fonts/din-next/bold/DinNextBold.woff2') format('woff2'),url('/css-new/fonts/din-next/bold/DinNextBold.woff') format('woff'),url('/css-new/fonts/din-next/bold/DinNextBold.ttf') format('truetype')}
@font-face{font-family:'DinNextMedium';src:url('/css-new/fonts/din-next/medium/DinNextMedium.woff2') format('woff2'),url('/css-new/fonts/din-next/medium/DinNextMedium.woff') format('woff'),url('/css-new/fonts/din-next/medium/DinNextMedium.ttf') format('truetype')}

.ovl-page{font-family:'DinNextRegular','Cairo','Segoe UI',sans-serif;font-size:12px;line-height:1.55;direction:rtl}
.ovl-page b,.ovl-page strong{font-family:'DinNextBold',sans-serif!important}

.ovl-page .ghost,.ovl-page .ghost *,
.ovl-page .ghost .kmb-ptbl .pr-addr,
.ovl-page .ghost .kmb-court-box,
.ovl-page .ghost .ovl-box,
.ovl-page .ghost .agr-stbl td.ovl-td,
.ovl-page .ghost .agr-stbl th,
.ovl-page .ghost .agr-stbl td,
.ovl-page .ghost .kmb-stbl th,
.ovl-page .ghost .kmb-stbl td,
.ovl-page .ghost .kmb-outer,
.ovl-page .ghost .kmb-inner,
.ovl-page .ghost .kmb-no-box,
.ovl-page .ghost .kmb-due-box,
.ovl-page .ghost .kmb-amt,
.ovl-page .ghost .kmb-hdr{
    color:transparent!important;border-color:transparent!important;background:transparent!important;
    -webkit-print-color-adjust:exact;print-color-adjust:exact;
}
.ovl-page .ghost img{visibility:hidden!important}
.ovl-page .ghost svg{visibility:hidden!important}
.ovl-page .sep.ghost::before,.ovl-page .sep.ghost::after{background:transparent!important}
.ovl-page .agr-frame.ghost{border-color:transparent!important}

.ovl-page .fill-anchor{position:relative;overflow:hidden}
.ovl-page .fill-data{
    position:absolute;top:0;right:0;left:0;bottom:0;
    display:flex;align-items:center;justify-content:center;
    color:#1a1a1a!important;font-family:'DinNextBold',sans-serif!important;
    font-size:14px!important;visibility:visible!important;
    text-align:center;padding:2px 4px;overflow:hidden;
}
.ovl-page .fill-data-td{
    position:absolute;top:0;right:0;left:0;bottom:0;
    display:flex;align-items:center;justify-content:center;
    color:#1a1a1a!important;font-family:'DinNextBold',sans-serif!important;
    font-size:10px!important;visibility:visible!important;
    text-align:center;padding:1px 2px;overflow:hidden;
}

.ovl-page .agr-frame{border:1px solid #888;padding:12px 16px;border-radius:2px}
.ovl-page .agr-ttl{text-align:center;font-family:'DinNextBold',sans-serif;font-size:18px;color:#1a1a1a;margin:0 0 8px;letter-spacing:.5px;padding-bottom:6px;border-bottom:2px solid #333}
.ovl-page .agr-pty{font-size:14px;margin-bottom:4px;font-family:'DinNextBold',sans-serif}
.ovl-page .agr-pty b{color:#1a1a1a}
.ovl-page .agr-txt{font-size:13px;line-height:1.65;text-align:justify;margin:5px 0;font-family:'DinNextMedium',sans-serif}
.ovl-page .ovl-wrap{margin:6px 0}
.ovl-page .ovl-lbl{font-size:13px;font-family:'DinNextBold',sans-serif;color:#1a1a1a;margin-bottom:3px}
.ovl-page .ovl-box{border:2px dashed #555;border-radius:3px;min-height:34px;width:100%;background:#fff}
.ovl-page .agr-stbl{width:100%;border-collapse:collapse;margin:8px 0}
.ovl-page .agr-stbl th{font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a1a1a;padding:6px 8px;border-bottom:2.5px solid #1a1a1a;text-align:center}
.ovl-page .agr-stbl td{padding:5px 8px;border-bottom:1px solid #888;font-size:12px;text-align:center;height:30px;font-family:'DinNextMedium',sans-serif}
.ovl-page .agr-stbl td.ovl-td{border-bottom:2px dashed #555}

.ovl-page .sep{display:flex;align-items:center;margin:10px 0}
.ovl-page .sep::before,.ovl-page .sep::after{content:'';flex:1;height:3px;background:#1a1a1a}
.ovl-page .sep-text{padding:2px 24px;font-family:'DinNextBold',sans-serif;font-size:18px;letter-spacing:5px;color:#1a1a1a;white-space:nowrap}

.ovl-page .kmb-outer{border:2.5px solid #1a1a1a;padding:3px}
.ovl-page .kmb-inner{border:1px solid #1a1a1a;padding:10px 12px}
.ovl-page .kmb-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding-bottom:6px;border-bottom:1.5px solid #ccc}
.ovl-page .kmb-no-box{border:2px solid #1a1a1a;border-radius:3px;padding:3px 16px;text-align:center}
.ovl-page .kmb-no-lbl{display:block;font-size:8px;color:#555;font-family:'DinNextMedium',sans-serif}
.ovl-page .kmb-no-val{font-family:'DinNextBold',sans-serif;font-size:16px;color:#1a1a1a}
.ovl-page .kmb-due-box{margin-right:auto;text-align:center;border:2.5px solid #c62828;border-radius:4px;padding:3px 14px}
.ovl-page .kmb-due-box small{display:block;font-size:8px;color:#555;font-family:'DinNextMedium',sans-serif}
.ovl-page .kmb-due-box strong{font-family:'DinNextBold',sans-serif;font-size:14px;color:#c62828;display:block;line-height:1.2}
.ovl-page .kmb-ptbl{width:100%;border-collapse:collapse;margin:6px 0;font-size:12px}
.ovl-page .kmb-ptbl td{padding:4px 6px;border-bottom:1px solid #ccc;vertical-align:middle}
.ovl-page .kmb-ptbl .pr-role{font-family:'DinNextBold',sans-serif;color:#1a1a1a;width:8%;white-space:nowrap;font-size:12px}
.ovl-page .kmb-ptbl .pr-name{width:22%;font-family:'DinNextBold',sans-serif;font-size:12px}
.ovl-page .kmb-ptbl .pr-id-lbl{font-family:'DinNextBold',sans-serif;color:#333;width:10%;font-size:10px;white-space:nowrap}
.ovl-page .kmb-ptbl .pr-id{width:16%;text-align:center;font-family:'DinNextBold',sans-serif;font-size:12px}
.ovl-page .kmb-ptbl .pr-addr-lbl{font-family:'DinNextBold',sans-serif;color:#333;width:10%;font-size:10px;white-space:nowrap}
.ovl-page .kmb-ptbl .pr-addr{border-bottom:2px dashed #555!important}
.ovl-page .kmb-main{display:flex;align-items:center;gap:10px;margin:10px 0}
.ovl-page .kmb-court-box{width:200px;flex-shrink:0;border:2px dashed #555;min-height:30px;border-radius:2px}
.ovl-page .kmb-pay{font-family:'DinNextBold',sans-serif;font-size:14px;white-space:nowrap}
.ovl-page .kmb-amt{border:2.5px solid #c62828;border-radius:4px;padding:4px 16px;text-align:center;min-width:120px}
.ovl-page .kmb-amt small{display:block;font-size:8px;color:#555}
.ovl-page .kmb-amt strong{font-family:'DinNextBold',sans-serif;font-size:20px;color:#c62828;display:block;line-height:1.2}
.ovl-page .kmb-words{font-size:13px;margin:5px 0;padding:5px 0;border-bottom:1px solid #ddd;font-family:'DinNextBold',sans-serif}
.ovl-page .kmb-words b{color:#c62828;font-family:'DinNextBold',sans-serif}
.ovl-page .kmb-p{font-size:12px;margin:4px 0;font-family:'DinNextMedium',sans-serif}
.ovl-page .kmb-stbl{width:100%;border-collapse:collapse;margin:6px 0}
.ovl-page .kmb-stbl th{font-family:'DinNextBold',sans-serif;font-size:12px;color:#1a1a1a;padding:5px 8px;border-bottom:2.5px solid #1a1a1a;text-align:center}
.ovl-page .kmb-stbl td{padding:4px 8px;border-bottom:1.5px solid #555;text-align:center;font-size:12px;height:30px;font-family:'DinNextMedium',sans-serif}
.ovl-page .kmb-pnote{font-size:9px;color:#555;font-style:italic;text-align:center;margin-top:5px}
</style>

<!-- ═══ شريط الأدوات ═══ -->
<div class="pc-toolbar">
    <button class="pc-btn pc-btn-print" onclick="printSelected()"><i class="fa fa-print"></i> طباعة</button>
    <button class="pc-btn pc-btn-back" onclick="document.getElementById('pageSelector').style.display = document.getElementById('pageSelector').style.display === 'none' ? 'block' : 'none'"><i class="fa fa-check-square-o"></i> تحديد الصفحات</button>
    <a href="<?= Url::to(['/judiciary/judiciary/print-overlay', 'id' => $model->id]) ?>" class="pc-btn pc-btn-back" style="background:rgba(198,40,40,.6)"><i class="fa fa-pencil-square"></i> تعبئة كمبيالة</a>
    <a href="<?= Url::to(['/judiciary/judiciary/update', 'id' => $model->id, 'contract_id' => $model->contract_id]) ?>" class="pc-btn pc-btn-back"><i class="fa fa-pencil"></i> تعديل القضية</a>
    <a href="<?= Url::to(['/judiciary/judiciary/index']) ?>" class="pc-btn pc-btn-back"><i class="fa fa-arrow-right"></i> القضايا</a>
    <span class="pc-info">قضية #<?= $model->id ?> — عقد #<?= $model->contract_id ?></span>
</div>

<!-- ═══ لوحة تحديد الصفحات ═══ -->
<div class="pc-page-selector" id="pageSelector" style="display:none">
    <h4><i class="fa fa-files-o"></i> اختر الصفحات للطباعة</h4>
    <label><input type="checkbox" checked data-page="page-pledge"><span class="ps-name">تعهد بصحة المعلومات</span><span class="ps-badge ps-badge-main">أساسي</span></label>
    <label><input type="checkbox" checked data-page="page-record"><span class="ps-name">محضر طلبات التنفيذ</span><span class="ps-badge ps-badge-main">أساسي</span></label>
    <?php if ($hasNotes): ?>
    <label><input type="checkbox" checked data-page="page-overlay"><span class="ps-name">تعبئة كمبيالة</span><span class="ps-badge ps-badge-main">أساسي</span></label>
    <?php endif ?>
    <?php foreach ($companyDocs as $i => $doc):
        $ext = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
        $icon = ($ext === 'pdf') ? 'fa-file-pdf-o' : 'fa-file-image-o';
    ?>
    <label><input type="checkbox" checked data-page="page-doc-<?= $i ?>"><span class="ps-name"><i class="fa <?= $icon ?>" style="color:#94a3b8;margin-left:4px"></i><?= Html::encode($doc['name']) ?></span><span class="ps-badge ps-badge-doc"><?= $doc['type'] ?></span></label>
    <?php endforeach ?>
    <?php if (empty($companyDocs)): ?>
    <p style="text-align:center;color:#94a3b8;font-size:12px;padding:8px 0"><i class="fa fa-info-circle"></i> لا توجد وثائق مرفقة في ملف الشركة</p>
    <?php endif ?>
    <div class="pc-sel-actions">
        <button onclick="toggleAllPages(true)">تحديد الكل</button>
        <button onclick="toggleAllPages(false)">إلغاء الكل</button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     الصفحة 1: تعهد بصحة المعلومات
     ═══════════════════════════════════════════════════════════ -->
<div class="a4-page" id="page-pledge">

    <!-- ترويسة -->
    <div class="pc-header">
        <div class="pc-header-right">
            <h3>المملكة الأردنية الهاشمية</h3>
            <h4>وزارة العدل</h4>
        </div>
        <div class="pc-header-center">
            <?= Html::img(Url::to(['/' . $moj_logo]), ['style' => 'width:80px;height:auto;border-radius:0']) ?>
        </div>
        <div class="pc-header-left">
            <h3>الدعاوى التنفيذية</h3>
        </div>
    </div>

    <!-- عنوان -->
    <div class="pc-section-title">تعهد بصحة المعلومات</div>

    <!-- بيانات الدائن -->
    <table class="pc-table">
        <thead>
            <tr>
                <th>إسم الدائن</th>
                <th>الرقم الوطني للمنشأة</th>
                <th>العنوان</th>
                <th>رقم الهاتف</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $companyInfo->name ?></td>
                <td><?= $companyInfo->company_social_security_number ?></td>
                <td><?= $companyInfo->company_address ?></td>
                <td><?= $companyInfo->phone_number ?></td>
            </tr>
        </tbody>
    </table>

    <!-- مفوض المحكوم له -->
    <p class="pc-text"><b>مفوض المحكوم له:</b> <?= $model->lawyer->name ?></p>

    <!-- بيانات المدينين -->
    <table class="pc-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>إسم المدين</th>
                <th>الرقم الوطني</th>
                <th>العنوان ورقم الهاتف</th>
            </tr>
        </thead>
        <tbody>
            <?php $number = 1; foreach ($model->customersAndGuarantor as $Customers): ?>
            <tr>
                <td><?= $number++ ?></td>
                <td><?= $Customers->name ?></td>
                <td style="direction:ltr;text-align:center;font-family:monospace"><?= $Customers->id_number ?></td>
                <td><?= $model->informAddress->address ?> — <?= \backend\helpers\PhoneHelper::toLocal($Customers->primary_phone_number) ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <!-- IBAN -->
    <p class="pc-text" style="margin-top:20px">
        <b>لا مانع من رد المبالغ على IBAN رقم:</b><br>
        <span dir="ltr" style="font-family:monospace;font-size:15px;letter-spacing:1px"><?= $companyInfo->primeryBankAccount->iban_number ?></span>
        (<?= $companyInfo->name ?>) مرفق الـ IBAN مصدق من <?= $companyInfo->primeryBankAccount->bank->name ?>
    </p>

    <!-- صور المحامي -->
    <?php if (!empty($lawyer_images)): ?>
    <div class="pc-lawyer-images">
        <?php foreach ($lawyer_images as $image): ?>
            <?= Html::img(Url::to(['/' . $image->image]), ['style' => 'max-width:400px;max-height:250px;object-fit:contain']) ?>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- التعهد والتوقيع -->
    <p class="pc-text" style="text-align:center;margin-top:20px">
        أنا الموقع أدناه <b><?= $model->lawyer->name ?></b> أتعهد بأن جميع البيانات الواردة أعلاه صحيحة وبحسب ما أفاد المدين.
    </p>

    <div class="pc-signature-row">
        <div class="pc-signature-box">
            <h5>التوقيع</h5>
            <div style="border-bottom:1px solid #333;margin-top:50px"></div>
        </div>
        <div class="pc-signature-box">
            <h5>التاريخ</h5>
            <div style="border-bottom:1px solid #333;margin-top:50px"></div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     الصفحة 2: محضر طلبات تنفيذ سندات
     ═══════════════════════════════════════════════════════════ -->
<div class="a4-page" id="page-record">

    <div class="pc-record-layout">

        <!-- الشريط الجانبي -->
        <div class="pc-record-sidebar">
            <h4>دائرة تنفيذ محكمة</h4>
            <h4 style="color:#0d47a1"><?= $model->court->name ?></h4>
            <div style="margin-top:20px;padding-top:10px;border-top:1px solid #999">
                <h4>رقم الدعوى التنفيذية</h4>
                <div style="margin-top:40px;border-bottom:1px dotted #333;width:80%;margin-left:auto;margin-right:auto"></div>
            </div>
        </div>

        <!-- المحتوى الرئيسي -->
        <div class="pc-record-main">

            <!-- ترويسة الصفحة 2 -->
            <div style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #333">
                <?= Html::img(Url::to(['/' . $moj_logo]), ['style' => 'width:70px;height:auto;border-radius:0;margin-bottom:6px']) ?>
                <h3 style="font-size:15px;font-weight:800;margin:4px 0">المملكة الأردنية الهاشمية</h3>
                <h4 style="font-size:14px;margin:2px 0">وزارة العدل</h4>
                <h4 style="font-size:14px;font-weight:700;margin:2px 0">محضر طلبات تنفيذ سندات</h4>
            </div>

            <!-- السند التنفيذي -->
            <h4 style="font-size:14px;font-weight:800;margin-bottom:6px">السند التنفيذي:</h4>
            <h4 style="font-size:13px;line-height:2">
                كمبيالة / رقمه: ......
                / تاريخ السند: <b><?= $model->contract->Date_of_sale ?></b>
                / تاريخ الإستحقاق: <b><?= $model->contract->first_installment_date ?></b>
                / المبلغ الاصلي: <b><?= number_format(($total_value ?? 0) * 1.15, 2) ?></b>
                / المبلغ المنفذ: <b><?= $contractCalculations->getExecutedAmount() ?></b>
            </h4>

            <!-- الدائن -->
            <h4 style="margin-top:20px;font-size:14px">(<b><?= $companyInfo->name ?></b>)</h4>
            <h4 style="font-size:13px"><b>عنوانه:</b> <?= $companyInfo->company_address ?></h4>
            <h4 style="font-size:13px"><b>مفوض المحكوم له:</b> <?= $model->lawyer->name ?></h4>

            <!-- المدينون -->
            <?php $number = 1; foreach ($model->customersAndGuarantor as $Customers): ?>
            <div class="pc-defendant">
                <h4 style="font-size:14px">
                    <?= $number++ ?>- <b>المحكوم عليه:</b> <?= $Customers->name ?>
                    <span style="float:left"><b>الرقم الوطني:</b> <?= $Customers->id_number ?></span>
                </h4>
                <h4 style="font-size:13px"><b>عنوانه (الموطن المختار):</b> <?= $model->informAddress->address ?></h4>
            </div>
            <?php endforeach ?>

            <!-- التوقيعات -->
            <div class="pc-signature-row" style="margin-top:40px">
                <div class="pc-signature-box">
                    <h5>مفوض المحكوم له</h5>
                    <p style="font-size:12px;color:#555"><?= $model->lawyer->name ?></p>
                    <div style="border-bottom:1px solid #333;margin-top:30px"></div>
                </div>
                <div class="pc-signature-box">
                    <h5>مأمور التنفيذ</h5>
                    <div style="border-bottom:1px solid #333;margin-top:50px"></div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     صفحة تعبئة الكمبيالة (overlay) — تظهر فقط إذا وُجدت كمبيالات
     ═══════════════════════════════════════════════════════════ -->
<?php if ($hasNotes): ?>
<div class="a4-page ovl-page" id="page-overlay" style="padding:8mm 10mm">

    <div class="agr-frame ghost">
        <div class="agr-ttl">اتفاقية الموطن المختار والمحكمة المختصة</div>
        <div class="agr-pty"><b>الطرف الأول:</b> <?= $compay_name ?></div>
        <div class="agr-pty"><b>الطرف الثاني:</b> <?= $ovlAllNames ?></div>
        <p class="agr-txt">
            اتفق الطرفان على أن تكون محكمة صلح وبداية وجزاء ودائرة تنفيذ المحكمة أدناه هي المحكمة المختصة حصراً في أي دعوى أو خصومة أو تنفيذ لجميع السندات التنفيذية والجزائية المحررة بين الطرفين:
        </p>
        <div class="ovl-wrap">
            <div class="ovl-lbl">المحكمة المختصة:</div>
            <div class="ovl-box fill-anchor"><span class="fill-data"><?= Html::encode($ovlCourtName) ?></span></div>
        </div>
        <p class="agr-txt">
            وأن الموطن المختار للتبليغات القضائية لجميع أطراف الطرف الثاني هو العنوان التالي حصراً:
        </p>
        <div class="ovl-wrap">
            <div class="ovl-lbl">الموطن المختار للتبليغات القضائية:</div>
            <div class="ovl-box fill-anchor"><span class="fill-data"><?= Html::encode($ovlShortAddress) ?></span></div>
        </div>
        <p class="agr-txt">
            يُقرّ الطرف الثاني أن أي تبليغ على هذا العنوان — سواء بالإلصاق أو بالذات — يُعتبر تبليغاً أصولياً صحيحاً، ويُسقط حقه في الطعن أو إبطال التبليغات. كما يُقرّ بقبول التبليغات الإلكترونية على:
            <b><?= implode(' — ', $ovlPhones) ?></b>
            <?php if ($ovlEmails): ?> | <?= implode(' — ', $ovlEmails) ?><?php endif; ?>
        </p>
        <p class="agr-txt">
            بعد طباعة الكمبيالة رقم <b><?= $ovlNote->getDisplayNumber() ?></b> والاطلاع والموافقة على جميع بياناتها. تم التوقيع بتاريخ <b><?= $ovlToday ?></b>.
        </p>
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
                <?php foreach ($ovlAllPeople as $pi => $c): ?>
                <tr>
                    <td><?= $pi === 0 ? 'المدين' : 'كفيل' ?> — <?= $c->name ?></td>
                    <td><?= $c->id_number ?></td>
                    <td class="ovl-td fill-anchor"><span class="fill-data-td"><?= Html::encode($ovlShortAddress) ?></span></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="sep ghost">
        <span class="sep-text">كمبيالة</span>
    </div>

    <div class="kmb-outer ghost">
        <div class="kmb-inner">
            <div class="kmb-hdr">
                <div class="kmb-no-box">
                    <span class="kmb-no-lbl">رقم الكمبيالة</span>
                    <span class="kmb-no-val"><?= $ovlNote->getDisplayNumber() ?></span>
                </div>
            </div>
            <table class="kmb-ptbl">
                <?php foreach ($ovlAllPeople as $pi => $c): ?>
                <tr>
                    <td class="pr-role"><?= $pi === 0 ? 'المدين' : 'كفيل' ?></td>
                    <td class="pr-name"><?= $c->name ?></td>
                    <td class="pr-id-lbl">الرقم الوطني</td>
                    <td class="pr-id"><?= $c->id_number ?></td>
                    <td class="pr-addr-lbl">الموطن المختار</td>
                    <td class="pr-addr fill-anchor"><span class="fill-data-td"><?= Html::encode($ovlShortAddress) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div class="kmb-main">
                <div class="kmb-court-box fill-anchor"><span class="fill-data"><?= Html::encode($ovlCourtName) ?></span></div>
                <span class="kmb-pay">والدفع بها</span>
                <div class="kmb-amt">
                    <small>المبلغ — دينار أردني</small>
                    <strong><?= number_format($ovlNote->amount, 2) ?></strong>
                </div>
                <div class="kmb-due-box">
                    <small>تاريخ الاستحقاق</small>
                    <strong><?= $ovlNote->due_date ?></strong>
                </div>
            </div>
            <div class="kmb-words">
                فقط مبلغ وقدره: <b><span class="kmb-words-text-ovl"></span></b>
            </div>
            <p class="kmb-p"><b>أدفع لأمر:</b> <?= $compay_name ?></p>
            <p class="kmb-p">القيمة وصلتنا <b>بضاعة</b> بعد المعاينة والاختبار والقبول، تحريراً في <b><?= $ovlToday ?></b></p>
            <table class="kmb-stbl">
                <thead>
                    <tr>
                        <th style="width:30%">الصفة / الاسم</th>
                        <th style="width:22%">الرقم الوطني</th>
                        <th style="width:48%">التوقيع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ovlAllPeople as $pi => $c): ?>
                    <tr>
                        <td><?= $pi === 0 ? 'المدين' : 'كفيل' ?> — <?= $c->name ?></td>
                        <td><?= $c->id_number ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="kmb-pnote">تم طباعة الكمبيالة قبل التوقيع وبعد اطلاع جميع الأطراف على بياناتها</div>
        </div>
    </div>

</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     صفحات المرفقات — وثائق الشركة (صور من السيرفر)
     ═══════════════════════════════════════════════════════════ -->
<?php
use backend\helpers\PdfToImageHelper;

foreach ($companyDocs as $i => $doc):
    $ext = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp']);
    $isPdf = ($ext === 'pdf');

    if ($isImage):
        $docUrl = Url::to(['/' . $doc['path']]);
?>
<div class="a4-page" id="page-doc-<?= $i ?>" style="padding:0;display:flex;align-items:center;justify-content:center">
    <img src="<?= $docUrl ?>" class="pc-doc-img" alt="<?= Html::encode($doc['name']) ?>">
</div>
<?php elseif ($isPdf):
        $cachedImages = PdfToImageHelper::getCachedImages($doc['path']);
        if (empty($cachedImages)) {
            $cachedImages = PdfToImageHelper::convertAndCache($doc['path']);
        }
        foreach ($cachedImages as $pi => $imgRel):
            $imgUrl = Url::to(['/' . $imgRel]);
?>
<div class="a4-page" id="page-doc-<?= $i ?><?= $pi > 0 ? '-p' . ($pi + 1) : '' ?>" style="padding:0">
    <img src="<?= $imgUrl ?>" class="pc-doc-img" alt="<?= Html::encode($doc['name']) ?> — صفحة <?= $pi + 1 ?>">
</div>
<?php   endforeach;
    endif;
endforeach; ?>

<script>
function toggleAllPages(checked) {
    document.querySelectorAll('#pageSelector input[type=checkbox]').forEach(function(cb) {
        cb.checked = checked;
    });
}

function printSelected() {
    document.querySelectorAll('.a4-page').forEach(function(page) {
        page.classList.remove('pc-page-hidden');
    });
    document.querySelectorAll('#pageSelector input[type=checkbox]').forEach(function(cb) {
        if (!cb.checked) {
            var pageId = cb.getAttribute('data-page');
            var container = document.getElementById(pageId + '-container');
            if (container) {
                container.querySelectorAll('.a4-page').forEach(function(p) { p.classList.add('pc-page-hidden'); });
            } else {
                var page = document.getElementById(pageId);
                if (page) page.classList.add('pc-page-hidden');
            }
        }
    });
    setTimeout(function() { window.print(); }, 200);
}
</script>

<?php
$total_value = empty($model->contract->total_value) ? '0' : $model->contract->total_value;
$ovlAmtJs = $hasNotes ? (int)round($ovlNote->amount) : 0;
$script = <<<JS
$(document).ready(function(){
    $('#amount_after_first_installment').text(tafqeet($total_value)+' دينار اردني فقط لاغير');
    if ($ovlAmtJs > 0) {
        $('.kmb-words-text-ovl').text(tafqeet($ovlAmtJs) + ' دينار أردني فقط لا غير');
    }
}); 
JS;
$this->registerJs($script, $this::POS_END);
?>
