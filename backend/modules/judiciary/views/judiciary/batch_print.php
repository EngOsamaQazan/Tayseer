<?php
/**
 * طباعة جماعية للقضايا — صفحات A4 متتالية
 * يعرض صفحتي A4 لكل قضية (تعهد + محضر تنفيذ)
 */
use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\lawyers\models\Lawyers;

$moj_logo = Yii::$app->params['moj_logo'];
$totalCases = count($models);
?>

<style>
/* ═══ Reset & A4 Base ═══ */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #e0e0e0; font-family: 'Cairo', 'Segoe UI', 'Tahoma', sans-serif; }

/* ═══ شريط الأدوات ═══ */
.pc-toolbar {
    position: sticky; top: 0; z-index: 100;
    display: flex; justify-content: center; align-items: center; gap: 10px;
    padding: 12px 20px; background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%);
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
.pc-toolbar .pc-count-badge {
    background: #fbbf24; color: #1a365d; font-weight: 800;
    padding: 4px 14px; border-radius: 20px; font-size: 14px;
}

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

/* فاصل بين القضايا (شاشة فقط) */
.bp-case-divider {
    width: 210mm; margin: 0 auto; padding: 10px 0; text-align: center;
    color: #fff; background: #475569; font-size: 14px; font-weight: 700;
}

/* ═══ ترويسة الصفحة ═══ */
.pc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 2px solid #333; padding-bottom: 12px; }
.pc-header-right { text-align: right; }
.pc-header-right h3 { font-size: 16px; font-weight: 800; margin: 0; }
.pc-header-right h4 { font-size: 14px; font-weight: 600; margin: 2px 0 0; color: #333; }
.pc-header-center { text-align: center; }
.pc-header-center img { width: 80px; height: auto; }
.pc-header-left { text-align: left; }
.pc-header-left h3 { font-size: 16px; font-weight: 800; margin: 0; }

.pc-section-title { text-align: center; font-size: 16px; font-weight: 800; margin: 16px 0 12px; padding: 6px 0; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; }
.pc-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 13px; }
.pc-table th, .pc-table td { border: 1px solid #555; padding: 8px 10px; text-align: right; vertical-align: top; }
.pc-table th { background: #f0f0f0; font-weight: 700; font-size: 12.5px; }
.pc-text { font-size: 14px; line-height: 2; margin-bottom: 10px; }
.pc-text b { font-weight: 800; }
.pc-signature-row { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 10px; border-top: 1px dashed #999; }
.pc-signature-box { text-align: center; width: 40%; }
.pc-signature-box h5 { font-size: 13px; font-weight: 700; margin-bottom: 40px; }
.pc-lawyer-images { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
.pc-lawyer-images img { max-width: 400px; max-height: 250px; object-fit: contain; }

.pc-record-layout { display: flex; gap: 0; min-height: 230mm; }
.pc-record-sidebar { width: 50mm; flex-shrink: 0; border-left: 2px solid #333; padding: 10px 8px; text-align: center; font-size: 13px; font-weight: 700; }
.pc-record-sidebar h4 { font-size: 13px; margin: 8px 0; font-weight: 800; }
.pc-record-main { flex: 1; padding: 0 10px 0 0; }
.pc-record-main h4 { font-size: 14px; margin: 8px 0; line-height: 1.8; }
.pc-record-main h4 b { font-weight: 800; }
.pc-defendant { margin: 16px 0; padding: 10px 0; border-top: 1px dashed #ccc; }
.pc-defendant:first-of-type { border-top: none; }

/* ═══ طباعة ═══ */
@media print {
    html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
    .pc-toolbar, .bp-case-divider { display: none !important; }
    .main-footer, .content-header, .main-sidebar { display: none !important; }
    .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .content { padding: 0 !important; }
    .a4-page {
        width: 100%; min-height: auto;
        margin: 0; padding: 15mm 18mm;
        box-shadow: none; border: none;
        page-break-after: always;
    }
    .a4-page:last-of-type { page-break-after: auto; }
}
@page { size: A4; margin: 0; }
</style>

<!-- ═══ شريط الأدوات ═══ -->
<div class="pc-toolbar">
    <button class="pc-btn pc-btn-print" onclick="window.print()"><i class="fa fa-print"></i> طباعة الكل</button>
    <span class="pc-count-badge"><?= $totalCases ?> قضية</span>
    <a href="<?= Url::to(['/contracts/contracts/legal-department']) ?>" class="pc-btn pc-btn-back"><i class="fa fa-arrow-right"></i> الدائرة القانونية</a>
    <a href="<?= Url::to(['/judiciary/judiciary/index']) ?>" class="pc-btn pc-btn-back"><i class="fa fa-list"></i> القضايا</a>
    <span class="pc-info">طباعة جماعية — <?= $totalCases ?> قضية (<?= $totalCases * 2 ?> صفحة)</span>
</div>

<?php
/* ═══ Loop through each case ═══ */
$caseNum = 0;
foreach ($models as $model):
    $caseNum++;

    $CompanyChecked = new CompanyChecked();
    $CompanyChecked->id = $model->company_id;
    $companyInfo = $CompanyChecked->findCompany();
    if ($companyInfo == '') {
        $logo = Yii::$app->params['companies_logo'] ?? '';
        $compay_name = '';
    } else {
        $logo = $companyInfo->logo;
        $compay_name = $companyInfo->name;
    }

    $contractCalculations = new ContractCalculations($model->contract_id);
    $total_value = $model->contract->total_value ?? 0;
    $lawyer_images = Lawyers::getLawyerImage($model->lawyer->id ?? 0);
?>

<!-- فاصل بين القضايا -->
<?php if ($caseNum > 1): ?>
<div class="bp-case-divider">
    <i class="fa fa-gavel"></i> قضية <?= $caseNum ?> من <?= $totalCases ?> — عقد #<?= $model->contract_id ?>
</div>
<?php endif ?>

<!-- ═══ الصفحة 1: تعهد بصحة المعلومات ═══ -->
<div class="a4-page">
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

    <div class="pc-section-title">تعهد بصحة المعلومات</div>

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
                <td><?= $companyInfo ? $companyInfo->name : '' ?></td>
                <td><?= $companyInfo ? $companyInfo->company_social_security_number : '' ?></td>
                <td><?= $companyInfo ? $companyInfo->company_address : '' ?></td>
                <td><?= $companyInfo ? $companyInfo->phone_number : '' ?></td>
            </tr>
        </tbody>
    </table>

    <p class="pc-text"><b>مفوض المحكوم له:</b> <?= $model->lawyer->name ?? '' ?></p>

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
                <td><?= ($model->informAddress ? $model->informAddress->address : '') ?> — <?= \backend\helpers\PhoneHelper::toLocal($Customers->primary_phone_number) ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($companyInfo && $companyInfo->primeryBankAccount): ?>
    <p class="pc-text" style="margin-top:20px">
        <b>لا مانع من رد المبالغ على IBAN رقم:</b><br>
        <span dir="ltr" style="font-family:monospace;font-size:15px;letter-spacing:1px"><?= $companyInfo->primeryBankAccount->iban_number ?></span>
        (<?= $companyInfo->name ?>) مرفق الـ IBAN مصدق من <?= $companyInfo->primeryBankAccount->bank->name ?? '' ?>
    </p>
    <?php endif ?>

    <?php if (!empty($lawyer_images)): ?>
    <div class="pc-lawyer-images">
        <?php foreach ($lawyer_images as $image): ?>
            <?= Html::img(Url::to(['/' . $image->image]), ['style' => 'max-width:400px;max-height:250px;object-fit:contain']) ?>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <p class="pc-text" style="text-align:center;margin-top:20px">
        أنا الموقع أدناه <b><?= $model->lawyer->name ?? '' ?></b> أتعهد بأن جميع البيانات الواردة أعلاه صحيحة وبحسب ما أفاد المدين.
    </p>

    <div class="pc-signature-row">
        <div class="pc-signature-box"><h5>التوقيع</h5><div style="border-bottom:1px solid #333;margin-top:50px"></div></div>
        <div class="pc-signature-box"><h5>التاريخ</h5><div style="border-bottom:1px solid #333;margin-top:50px"></div></div>
    </div>
</div>

<!-- ═══ الصفحة 2: محضر طلبات تنفيذ سندات ═══ -->
<div class="a4-page">
    <div class="pc-record-layout">
        <div class="pc-record-sidebar">
            <h4>دائرة تنفيذ محكمة</h4>
            <h4 style="color:#0d47a1"><?= $model->court->name ?? '' ?></h4>
            <div style="margin-top:20px;padding-top:10px;border-top:1px solid #999">
                <h4>رقم الدعوى التنفيذية</h4>
                <div style="margin-top:40px;border-bottom:1px dotted #333;width:80%;margin-left:auto;margin-right:auto"></div>
            </div>
        </div>

        <div class="pc-record-main">
            <div style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #333">
                <?= Html::img(Url::to(['/' . $moj_logo]), ['style' => 'width:70px;height:auto;border-radius:0;margin-bottom:6px']) ?>
                <h3 style="font-size:15px;font-weight:800;margin:4px 0">المملكة الأردنية الهاشمية</h3>
                <h4 style="font-size:14px;margin:2px 0">وزارة العدل</h4>
                <h4 style="font-size:14px;font-weight:700;margin:2px 0">محضر طلبات تنفيذ سندات</h4>
            </div>

            <h4 style="font-size:14px;font-weight:800;margin-bottom:6px">السند التنفيذي:</h4>
            <h4 style="font-size:13px;line-height:2">
                كمبيالة / رقمه: ......
                / تاريخ السند: <b><?= $model->contract->Date_of_sale ?? '' ?></b>
                / تاريخ الإستحقاق: <b><?= $model->contract->first_installment_date ?? '' ?></b>
                / المبلغ الاصلي: <b><?= number_format(($total_value ?? 0) * 1.15, 2) ?></b>
                / المبلغ المنفذ: <b><?= $contractCalculations->getExecutedAmount() ?></b>
            </h4>

            <h4 style="margin-top:20px;font-size:14px">(<b><?= $companyInfo ? $companyInfo->name : '' ?></b>)</h4>
            <h4 style="font-size:13px"><b>عنوانه:</b> <?= $companyInfo ? $companyInfo->company_address : '' ?></h4>
            <h4 style="font-size:13px"><b>مفوض المحكوم له:</b> <?= $model->lawyer->name ?? '' ?></h4>

            <?php $number = 1; foreach ($model->customersAndGuarantor as $Customers): ?>
            <div class="pc-defendant">
                <h4 style="font-size:14px">
                    <?= $number++ ?>- <b>المحكوم عليه:</b> <?= $Customers->name ?>
                    <span style="float:left"><b>الرقم الوطني:</b> <?= $Customers->id_number ?></span>
                </h4>
                <h4 style="font-size:13px"><b>عنوانه (الموطن المختار):</b> <?= $model->informAddress ? $model->informAddress->address : '' ?></h4>
            </div>
            <?php endforeach ?>

            <div class="pc-signature-row" style="margin-top:40px">
                <div class="pc-signature-box">
                    <h5>مفوض المحكوم له</h5>
                    <p style="font-size:12px;color:#555"><?= $model->lawyer->name ?? '' ?></p>
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

<?php endforeach ?>
