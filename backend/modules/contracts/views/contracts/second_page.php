<?php
/**
 * Created by PhpStorm.
 * User: huda
 * Date: 9/17/20
 * Time: 9:03 PM
 */

use common\components\CompanyChecked;

$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = $logo = Yii::$app->params['companies_logo'];
    $compay_name = '';
    $compay_banks = '';

} else {
    $logo = $primary_company->logo;
    $compay_name = $primary_company->name;
    $compay_banks = CompanyChecked::findPrimaryCompanyBancks();
}

?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/contract_style.css">
    <title>Contract</title>
</head>
<body>
<!-- Start Contract Header -->
<section class="contract-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 ">
                <h4 class="text-center">اتفاقية الموطن المختار والمحكمة لمختصه</h4>
            </div>
        </div>
    </div>
</section>
<!-- End Contract Header -->

<!-- Contract Info -->
<section class="contract-info">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 ">
                <div class="form-group">
                    <label> الطرف الأول </label>
                    <span class="padding-6"><?= $compay_name ?></span>
                    <label style="margin-right: 60px"> الطرف التاني </label>
                    <?php if ($model->type == 'normal') { ?>
                        <span class="padding-6"><?= isset($model->customer) ? $model->customer->name : '' ?></span>
                    <?php } else { ?>
                        <?php foreach ($model->customers as $customer) { ?>
                            <span class=""><?= $customer->name ?> و </span>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 ">
                <p>
                    اتفق الطرفان على أن تكون محكمة صلح و جزاء و دائرة تنفيذ :
                    هي المحكـــمة المختصة في أي دعـــوى أو خصومة أو طرح و تنفيذ جميع السنـــــدات التنفيذية
                    و الجزائية المحررة بين الطرفين و أن الموطن المختار للتبليغات القضائية هو
                </p>
                <br>
                <p>
                    و هو العنــوان الصحيح فقط و يقر الطــرف الثاني أن أي تبليغ من خـلال العنوان المذكور سواء كان تبلغيا
                    بالإلصــــاق أو بالذات يُعتبر تبليغاً أصولياً ، و يسقــــط حقه في إبطـــــال التبليغــــات على هذا
                    العنوان و يقر أيضاً بكافة التبليغات الإلكترونية المرسله على بريده الالكتروني أو على رقم هاتفه
                    التالية
                </p>
                <p class="mb-5">
                    بعد طباعة الكمبيالة رقم :
                    <span class="empty-span"></span> وبعد الأطلاع والموافقة على
                    جميع البيانات المحررة فيها . تم التوقيع بتاريخ
                 <span class="empty-span">
                         <?=date("Y-m-d");?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</section>
<!-- Contract Info -->

<!-- Contract Body -->
<section class="contract-body line-frm">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 ">
                <div class="row">
                    <div class="col-sm-6 ">
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <?php if ($model->type == 'normal') { ?>
                                <?php foreach ($model->customers as $customer) { ?>
                                    <label>اسم المدين</label>
                                    <span class="padding-6">
                                                <?= $customer->name ?>
                                            </span>
                                    <label>عنوانه</label>
                                    <span></span>

                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <?php if ($model->type == 'normal') { ?>
                        <?php foreach ($model->guarantor as $guarantor) { ?>

                            <div class="col-sm-6 ">
                                <div class="form-group block">
                                    <label>اسم المدين </label>
                                    <span><?= $guarantor->name ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <?php foreach ($model->customers as $customer) { ?>
                            <div class="col-lg-3 col-md-3 col-sm-3 ">
                                <div class="form-group">
                                    <label>التوقيع</label>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-9 col-sm-9 ">
                                <div class="form-group">
                                    <label>اسم المدين </label>
                                    <span><?= $customer->name . ' ورقم هاتفه:' . \backend\helpers\PhoneHelper::toLocal($customer->primary_phone_number) . ' وبريده :' . $customer->email ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <hr class="fat-hr">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 ">
                        <h2 class="text-center" style="margin-bottom: 20px">كمبيالة</h2>
                    </div>
                </div>
                <div class="row" style="margin-bottom: 20px">
                    <div class="form-group">
                        <?php foreach ($model->customers as $customer) { ?>
                            <div class="col-sm-6 ">
                                <div class="form-group">
                                    <label>عنوانه</label>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-sm-6 ">
                                <label>اسم المدين</label>
                                <span class="padding-6">
                                            <?= $customer->name ?>
                                        </span>
                            </div>

                        <?php } ?>
                    </div>

                </div>
                <div class="row" style="margin-bottom: 20px">
                    <div class="col-lg-2 col-md-2 col-sm-2 col-2">
                        <table class="table table-bordered" style=" float: right;">
                            <thead>
                            <th>فلس</th>
                            <th>دينار</th>
                            </thead>
                            <tbody>
                            <td>00</td>
                            <td id="amount_after_first_installment"><?= $model->total_value * 0.15 ?></td>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4 ">
                        <div class="form-group">

                            <label>تاريخ الاستحقاق</label>
                            <span><?= $model->due_date ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <label>دائرة التنفيذ</label>
                            <span class="w-200"></span>
                            <label class="mr-auto">والدفع بها</label>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-sm-6 ">
                        <div class="form-group">
                            <label>فقط مبلغ وقدره</label>
                            <span class="padding-6" id="amount_after_first_installment_written"></span>
                        </div>
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-group custom-fg">
                            <label>بموجب هذه الكمبيالة أدفع لأمر</label>
                            <span>
                                        <?= $model->company->name ?>
                                    </span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 ">
                        <div class="form-group">
                            <p class="mb-0">القيمة وصلتنا
                                <span class="sm-span">
                                            <select name="val" id="val">
                                                <option value="بضاعة">بضاعة</option>
                                            </select>
                                        </span> بعد المعاينة والأختبار
                                        والقبول تحريرا في 
                                        <span class="w-200">
                                            <?=date("Y-m-d");?>
                                        </span>
                            </p>

                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 ">
                        <p class="mb-2">تم طباعة الكمبيالة قبل التوقيع و بعد الإطلاع على البيانات </p>
                    </div>
                </div>
                <?php if ($model->type == 'normal') { ?>
                    <?php foreach ($model->guarantor as $guarantor) { ?>

                        <div class="col-sm-6 ">
                            <div class="form-group padding-b-t">
                                <label>توقيع المدين</label>
                                <span class="padding-6"></span>
                            </div>
                        </div>

                    <?php } ?>
                <?php } else { ?>
                    <?php foreach ($model->customers as $customer) { ?>
                        <div class="col-sm-6 ">
                            <div class="form-group padding-b-t">
                                <label>توقيع المدين</label>
                                <span class="padding-6"></span>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>

            </div>
        </div>
    </div>
</section>
<!-- Contract Body -->
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="/js/jquery-3.3.1.min.js"></script>
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/Tafqeet.js"></script>
<?php
$script = <<< JS
$(document).ready(function(){
    $('#amount_after_first_installment_written').text(tafqeet($('#amount_after_first_installment').text())+' دينار اردني فقط لاغير');
    $('#monthly_installment_value_written').text(tafqeet($('#monthly_installment_value').text())+' دينار اردني فقط لاغير');
    $('#first_installment_value_written').text(tafqeet($('#first_installment_value').text())+' دينار اردني فقط لاغير');
}); 
JS;
$this->registerJs($script, $this::POS_END);
?>
</body>
</html>