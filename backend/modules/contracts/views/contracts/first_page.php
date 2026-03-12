<?php
use common\components\CompanyChecked;
/**
 * Created by PhpStorm.
 * User: huda
 * Date: 9/15/20
 * Time: 10:01 PM
 */
/** @var $model \backend\modules\contracts\models\Contracts */
//Yii::$app->params['companies'][$model->seller_id]
//var_dump($model->contractsCustomers);die;
$campanyModel = \backend\modules\companies\models\Companies::find(['id'=>$id])->orderBy(['id'=>SORT_DESC])->one();
$companyName = $campanyModel->name;
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if($primary_company == '') {
   $logo = $logo = Yii::$app->params['companies_logo'];
}else{
    $logo = $primary_company->logo;
}

?>


<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/contract_style.css">
        <title>Contract</title>
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }


        </style>
    </head>

    <body>
        <!-- Start Contract Header -->
        <section class="contract-header">
            <div class="container">
                <div class="row">
                    <div class="col-lg-11 col-md-11 col-sm-11 col-11" >
                        <?php foreach ($model->contractsCustomers as $contractsCustomers) { ?>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-2" style="float: left ; display: inline-block ;padding-right: 1px !important;padding-left: 1px !important;">
                                <img src="<?=$logo?>"  class="signutre-div">
                            </div>
                        <?php } ?>

                        <div class="col-lg-2 col-md-2 col-sm-2 col-2" style=" display: inline-block; float: right;padding-right: 1px !important;padding-left: 1px !important">

                           <?php
                           echo Html::img(Url::to([$logo]), ['style' => 'width: 100px;height:100px ;object-fit: contain; margin-top: 20px']);

                           ?>
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-1 col-sm-1 col-1" >
                        <?=$model->id?>
                    </div>
                </div>
            <h5  class=" green-color text-left w-100 pl-50">يرجى قراءة العقد قبل التوقيع</h5>
            </div>
        </section>
        <!-- End Contract Header -->

        <!-- Contract Info -->
        <section class="contract-info">

            <div class="container">

                <div class="mt row" style="top: 10px;">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="form-group">
                            <label> الطرف الأول </label>
                            <span class="padding-6"><?= $model->company->name ?></span>
                        </div>
                        <div class="form-group">
                            <label> الطرف التاني </label>
                            <?php if ($model->type == 'normal') { ?>
                                <span class="padding-6"><?= isset($model->customer) ? $model->customer->name : '' ?></span>
                            <?php } else {
                                ?>
                                <?php foreach ($model->customers as $customer) { ?>
                                    <span class=""><?= $customer->name ?> و </span>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="info-div margin-top-10">
                            <p>تعتبر هذه المقدمة جزء من العقد ويقر الطرف الثاني  بموافقه على البنود التالية وعددها
                               1</p>
                            <p>2 حالة البضاعة : يقر الطرف الثاني انه استلم البضاعة الموصوفة بعد المعاينة سليمة وخالية من المشاكل
                                والعيوب</p>
                            <p>3 الالتزام بالدفع : يلتزم الطرف الثاني بدفع ثمن البضاعة المذكور
                                بالعقد
                                ونتحمل كافة المصاريف القضائية وغير القضائية بما فيها إضافة  &nbsp;

                                على قيمة هذا العقد في حالة تخلفه عن دفع اي قسط من الأقساط المذكورة ادناه
                                ويعتبر كامل المبلغ مستحق الاداء.</p>
                            <p><?=$model->company->name?>طريقة الدفع : نلتزم بدفع الأقساط في موعدها من خلال eFAWATEERcom تبويب تمويل وخدمات مالية - شركة<?= $model->id ?>) ثم اتمام الدفع او في حساب الشركة في <span><?= $model->company->bank_info ?></span>
                            </p>
                            <p>5 كفالة وارجاع البضاعة : كفالة الوكيل حسب الشركة الموزعة والبضاعة المباعة لاترد ولاتستبدل
                                ويلتزم الطرف الثاني
                                بخسارة 50 دينار إذا أراد إرجاع البضاعة بمدة لاتزيد عن 24 ساعة من تاريخ البيع</p>
                            <p>6 الشركة غير مسئولة عن : سعر البضاعة خارج فروعها وعن أي اتفاقية أو مبلغ غير موثق في العقد</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Contract Info -->

        <!-- Contract Body -->
        <section class="contract-body ">
            <div class="container ">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                        <h4 class="title">
                            بيانات العقد
                        </h4>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                                <div class="form-group">
                                    <label>البائع</label>
                                    <?= $model->seller->name ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                                <div class="form-group">
                                    <label>تاريخ البيع</label>
                                    <span class="padding-6"><?= $model->Date_of_sale ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>فئة العقد</label>
                            <span class="padding-6"><?= $model->type == 'normal' ? 'فردي' : 'متضامين ' ?></span>
                        </div>
                        <div class="form-group" style="padding-bottom: 20px">
                            <label class="small-fnt">تاريخ الستحقاق أول قسط</label>
                            <span class="padding-6"><?= $model->due_date ?></span>
                        </div>

                        <table style="padding-bottom: 20px">
                            <tr>
                                <td></td>
                                <td>
                                    <span  class="padding-6 green-color">رقما</span>
                                </td>
                                <td>
                                    <span  class="padding-6 green-color">كتابة</span>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <label class="small-fnt">المبلغ بعد الدفعة الأولى</label>
                                </td>
                                <td>
                                    <span class="padding-6"id="amount_after_first_installment"><?= $model->total_value ?></span>
                                </td>
                                <td>
                                    <span class="padding-6" id="amount_after_first_installment_written"></span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="small-fnt">القسط الشهري</label>
                                </td>
                                <td>
                                    <span class="padding-6" id="monthly_installment_value"><?= $model->monthly_installment_value ?></span>
                                </td>
                                <td>
                                    <span class="padding-6" id="monthly_installment_value_written"></span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="small-fnt">الدفعة الأولى</label>
                                </td>
                                <td>
                                    <span class="padding-6" id="first_installment_value"><?= $model->first_installment_value ?></span>
                                </td>
                                <td>
                                    <span class="padding-6" id = "first_installment_value_written"></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                        <?php if ($model->type == 'normal') { ?>
                            <h4 class="title"> بيانات المشتري</h4>
                            <div class="form-group">
                                <label>المشتري</label>
                                <span class="padding-6"><?= isset($model->customer) ? $model->customer->name : '' ?></span>
                            </div>
                            <div class="form-group">
                                <label>الرقم الوطني</label>
                                <span class="padding-6"><?= isset($model->customer) ? $model->customer->id_number : '' ?></span>
                            </div>
                        <?php } ?>

                        <div class="data-block">
                            <h4 class="title">بيانات الطرف الثاني والتوقيع</h4>
                            <table style="padding-bottom: 20px">
                                <tr>
                                    <td>
                                        <label class="padding-6">اسم المدين</label>
                                    </td>
                                    <td>
                                        <label  class="padding-6">الرقم الوطني</label>
                                    </td>
                                </tr>

                                <?php if ($model->type == 'normal') { ?>
                                    <?php foreach ($model->guarantor as $guarantor) { ?>
                                        <tr>
                                            <td>
                                                <span class="padding-6"><?= $guarantor->name ?></span>
                                            </td>
                                            <td>
                                                <span class="padding-6"><?= $guarantor->id_number ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <?php foreach ($model->customers as $customer) { ?>
                                        <tr>
                                            <td>
                                                <span class="padding-6"><?= $customer->name ?></span>
                                            </td>
                                            <td>
                                                <span class="padding-6"><?= $customer->id_number ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>

                            </table>
                        </div>
                    </div>

                </div>
                <div class="row mt-1">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6 padding-t-15">
                        <label>ملاحظات البائع</label> <br>
                        <span>
                            <?= $model->notes ?>
                        </span>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6 text-left">
                        <label>مركز الاتصال : <?= $model->company->phone_number ?></label>
                    </div>
                </div>
            </div>
        </section>
        <!-- Start Contract Header -->
        <!-- End Contract Header -->


        <script src="/js/jquery-3.3.1.min.js"></script>
        <script src="/js/popper.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>
        <script src="/js/Tafqeet.js"></script>
    </body>
</html>

<?php
$script = <<< JS
$(document).ready(function(){
    $('#amount_after_first_installment_written').text(tafqeet($('#amount_after_first_installment').text())+' دينار اردني فقط لاغير');
    $('#monthly_installment_value_written').text(tafqeet($('#monthly_installment_value').text())+' دينار اردني فقط لاغير');
    $('#first_installment_value_written').text(tafqeet($('#first_installment_value').text())+' دينار اردني فقط لاغير');
}); 
JS;
$this->registerJs($script, $this::POS_END);
