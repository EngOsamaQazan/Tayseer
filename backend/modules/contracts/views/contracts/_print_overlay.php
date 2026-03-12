<?php
/**
 * طباعة التعبئة الثانية — فوق الكمبيالة والاتفاقية المطبوعة مسبقاً
 * ═══════════════════════════════════════════════════════════════════
 * هذه الصفحة تطبع فقط في المواقع المنقطة (overlay):
 *   - المحكمة المختصة
 *   - الموطن المختار
 * كل شيء آخر شفاف تماماً حتى لا يطبع فوق النص الأصلي
 *
 * @var $model       backend\modules\contracts\models\Contracts
 * @var $note        backend\modules\contracts\models\PromissoryNote
 * @var $courtName   string
 * @var $address     string
 * @var $judiciaryId int
 * @var $companyName string
 */
use yii\helpers\Html;
use yii\helpers\Url;

$today     = date('Y-m-d');
$allPeople = $model->customersAndGuarantor;

$phones = [];
$emails = [];
foreach ($allPeople as $p) {
    if (!empty($p->primary_phone_number)) $phones[] = \backend\helpers\PhoneHelper::toLocal($p->primary_phone_number);
    if (!empty($p->email)) $emails[] = $p->email;
}
$peopleNames = [];
foreach ($allPeople as $c) { $peopleNames[] = $c->name; }
$allNames = implode(' و ', $peopleNames);

$shortAddress = $address;
if (($dashPos = mb_strpos($address, ' - ')) !== false) {
    $shortAddress = mb_substr($address, $dashPos + mb_strlen(' - '));
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تعبئة كمبيالة #<?= $note->getDisplayNumber() ?></title>
<style>
/* ═══ خطوط — نفس الأصل بالضبط ═══ */
@font-face{font-family:'DinNextRegular';src:url('/css-new/fonts/din-next/regular/DinNextRegular.woff2') format('woff2'),url('/css-new/fonts/din-next/regular/DinNextRegular.woff') format('woff'),url('/css-new/fonts/din-next/regular/DinNextRegular.ttf') format('truetype')}
@font-face{font-family:'DinNextBold';src:url('/css-new/fonts/din-next/bold/DinNextBold.woff2') format('woff2'),url('/css-new/fonts/din-next/bold/DinNextBold.woff') format('woff'),url('/css-new/fonts/din-next/bold/DinNextBold.ttf') format('truetype')}
@font-face{font-family:'DinNextMedium';src:url('/css-new/fonts/din-next/medium/DinNextMedium.woff2') format('woff2'),url('/css-new/fonts/din-next/medium/DinNextMedium.woff') format('woff'),url('/css-new/fonts/din-next/medium/DinNextMedium.ttf') format('truetype')}
b,strong{font-family:'DinNextBold',sans-serif!important}

/* ═══ أساسيات — نسخة طبق الأصل ═══ */
@page{size:A4 portrait;margin:8mm 10mm}
*{margin:0;padding:0;box-sizing:border-box}
body{direction:rtl;font-family:'DinNextRegular','Cairo','Segoe UI',sans-serif;color:#1a1a1a;font-size:12px;line-height:1.55;background:#fff}
.print-page{width:100%;max-width:190mm;margin:0 auto;position:relative}

/* ═══ كل شيء يصبح شفاف ═══ */
.ghost,.ghost *,
.ghost .kmb-ptbl .pr-addr,
.ghost .kmb-court-box,
.ghost .ovl-box,
.ghost .agr-stbl td.ovl-td,
.ghost .agr-stbl th,
.ghost .agr-stbl td,
.ghost .kmb-stbl th,
.ghost .kmb-stbl td,
.ghost .kmb-outer,
.ghost .kmb-inner,
.ghost .kmb-no-box,
.ghost .kmb-due-box,
.ghost .kmb-amt,
.ghost .kmb-hdr{
    color:transparent!important;
    border-color:transparent!important;
    background:transparent!important;
    -webkit-print-color-adjust:exact;
    print-color-adjust:exact;
}
.ghost img{visibility:hidden!important}
.ghost svg{visibility:hidden!important}
.sep.ghost::before,.sep.ghost::after{background:transparent!important}
.agr-frame.ghost{border-color:transparent!important}

/* ═══ البيانات المعبأة — position:absolute لعدم التأثير على ارتفاع الحاوي ═══ */
.fill-anchor{position:relative;overflow:hidden}
.fill-data{
    position:absolute;
    top:0;right:0;left:0;bottom:0;
    display:flex;align-items:center;justify-content:center;
    color:#1a1a1a!important;
    font-family:'DinNextBold',sans-serif!important;
    font-size:14px!important;
    visibility:visible!important;
    text-align:center;
    padding:2px 4px;
    overflow:hidden;
}
.fill-data-td{
    position:absolute;
    top:0;right:0;left:0;bottom:0;
    display:flex;align-items:center;justify-content:center;
    color:#1a1a1a!important;
    font-family:'DinNextBold',sans-serif!important;
    font-size:10px!important;
    visibility:visible!important;
    text-align:center;
    padding:1px 2px;
    overflow:hidden;
}

/* ═══════════════════════════════════════════════════════════
   الاتفاقية + الكمبيالة — نسخة CSS طبق الأصل من _print_preview
   كل سطر هنا = نفس السطر بالأصل بدون أي تعديل
   ═══════════════════════════════════════════════════════════ */

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

/* ═══ شريط الأدوات — شاشة فقط ═══ */
.toolbar{position:sticky;top:0;z-index:1000;background:linear-gradient(135deg,#c62828,#e53935);color:#fff;padding:10px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 3px 15px rgba(0,0,0,.25);font-family:'DinNextMedium',sans-serif}
.toolbar,.toolbar *{color:#fff!important}
.toolbar h1{font-family:'DinNextBold',sans-serif;font-size:16px;flex:1;margin:0}
.toolbar .tb-id{background:rgba(255,255,255,.15);border-radius:6px;padding:3px 14px;font-family:'DinNextBold',sans-serif;font-size:20px}
.toolbar .tb-info{font-size:11px;opacity:.85}
.toolbar .tb-btn{border:0;padding:8px 22px;border-radius:6px;font-size:14px;font-family:'DinNextBold',sans-serif;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s}
.toolbar .tb-print{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4)!important}
.toolbar .tb-print:hover{background:rgba(255,255,255,.35)}
.toolbar .tb-back{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3)!important;text-decoration:none;padding:6px 14px;border-radius:6px;font-size:12px}
.toolbar .tb-warn{background:rgba(255,255,255,.1)!important;border-radius:8px;padding:6px 12px;font-size:10px;line-height:1.5;max-width:340px}
.toolbar .tb-warn b{color:#ffcdd2!important}

/* ═══ مؤشرات المعاينة — شاشة فقط ═══ */
.fill-anchor::before{content:'▼';position:absolute;top:-14px;right:50%;transform:translateX(50%);color:#c62828;font-size:10px;z-index:5;animation:bounce .8s infinite}
@keyframes bounce{0%,100%{transform:translateX(50%) translateY(0)}50%{transform:translateX(50%) translateY(-3px)}}

@media print{
    body{-webkit-print-color-adjust:exact;print-color-adjust:exact;background:#fff!important}
    .toolbar{display:none!important}
    .fill-anchor::before{display:none}
    .print-page{margin:0;padding:0;box-shadow:none;max-width:100%}
}
@media screen{
    body{background:#f1f5f9;padding:0}
    .print-page{background:#fff;padding:16px 20px;margin:16px auto;box-shadow:0 4px 20px rgba(0,0,0,.12);border-radius:3px;border:2px dashed #e2e8f0}
}
</style>
</head>
<body>

<!-- ═══ شريط الأدوات — لا يُطبع ═══ -->
<div class="toolbar">
    <a class="tb-back" href="<?= Url::to(['/judiciary/judiciary/print-case', 'id' => $judiciaryId]) ?>">← العودة</a>
    <h1>تعبئة فوق الكمبيالة المطبوعة</h1>
    <div class="tb-warn">
        <b>تنبيه:</b> أدخل الورقة المطبوعة مسبقاً في الطابعة بنفس الاتجاه.<br>
        سيُطبع فقط: <b>المحكمة</b> + <b>الموطن المختار</b>
    </div>
    <span class="tb-id">#<?= $note->getDisplayNumber() ?></span>
    <button class="tb-btn tb-print" onclick="window.print()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        طباعة التعبئة
    </button>
</div>

<div class="print-page">

    <!-- ═══ الاتفاقية — نفس الهيكل والنصوص بالضبط ═══ -->
    <div class="agr-frame ghost">

        <div class="agr-ttl">اتفاقية الموطن المختار والمحكمة المختصة</div>

        <div class="agr-pty"><b>الطرف الأول:</b> <?= $companyName ?></div>
        <div class="agr-pty"><b>الطرف الثاني:</b> <?= $allNames ?></div>

        <p class="agr-txt">
            اتفق الطرفان على أن تكون محكمة صلح وبداية وجزاء ودائرة تنفيذ المحكمة أدناه هي المحكمة المختصة حصراً في أي دعوى أو خصومة أو تنفيذ لجميع السندات التنفيذية والجزائية المحررة بين الطرفين:
        </p>

        <div class="ovl-wrap">
            <div class="ovl-lbl">المحكمة المختصة:</div>
            <div class="ovl-box fill-anchor"><span class="fill-data"><?= Html::encode($courtName) ?></span></div>
        </div>

        <p class="agr-txt">
            وأن الموطن المختار للتبليغات القضائية لجميع أطراف الطرف الثاني هو العنوان التالي حصراً:
        </p>

        <div class="ovl-wrap">
            <div class="ovl-lbl">الموطن المختار للتبليغات القضائية:</div>
            <div class="ovl-box fill-anchor"><span class="fill-data"><?= Html::encode($shortAddress) ?></span></div>
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
                    <td class="ovl-td fill-anchor"><span class="fill-data-td"><?= Html::encode($shortAddress) ?></span></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <!-- ═══ الفاصل — نفس المكان بالضبط ═══ -->
    <div class="sep ghost">
        <span class="sep-text">كمبيالة</span>
    </div>

    <!-- ═══ الكمبيالة — نفس الهيكل والنصوص بالضبط ═══ -->
    <div class="kmb-outer ghost">
        <div class="kmb-inner">

            <div class="kmb-hdr">
                <div class="kmb-no-box">
                    <span class="kmb-no-lbl">رقم الكمبيالة</span>
                    <span class="kmb-no-val"><?= $note->getDisplayNumber() ?></span>
                </div>
            </div>

            <table class="kmb-ptbl">
                <?php foreach ($allPeople as $pi => $c): ?>
                <tr>
                    <td class="pr-role"><?= $pi === 0 ? 'المدين' : 'كفيل' ?></td>
                    <td class="pr-name"><?= $c->name ?></td>
                    <td class="pr-id-lbl">الرقم الوطني</td>
                    <td class="pr-id"><?= $c->id_number ?></td>
                    <td class="pr-addr-lbl">الموطن المختار</td>
                    <td class="pr-addr fill-anchor"><span class="fill-data-td"><?= Html::encode($shortAddress) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="kmb-main">
                <div class="kmb-court-box fill-anchor"><span class="fill-data"><?= Html::encode($courtName) ?></span></div>
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

            <div class="kmb-words">
                فقط مبلغ وقدره: <b><span class="kmb-words-text"></span></b>
            </div>

            <p class="kmb-p"><b>أدفع لأمر:</b> <?= $companyName ?></p>
            <p class="kmb-p">القيمة وصلتنا <b>بضاعة</b> بعد المعاينة والاختبار والقبول، تحريراً في <b><?= $today ?></b></p>

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

        </div>
    </div>

</div>

<script src="/js-new/jquery-3.3.1.min.js"></script>
<script src="/js/Tafqeet.js"></script>
<script>
$(function(){
    var amt = <?= (int)round($note->amount) ?>;
    var words = tafqeet(amt) + ' دينار أردني فقط لا غير';
    $('.kmb-words-text').text(words);
});
</script>

</body>
</html>
