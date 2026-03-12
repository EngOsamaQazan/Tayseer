<?php

use yii\helpers\Html;
use yii\bootstrap\Modal;

use johnitvn\ajaxcrud\CrudAsset;
use common\components\CompanyChecked;
/* @var $this \yii\web\View */
/* @var $content string */

CrudAsset::register($this);
$CompanyChecked = new CompanyChecked();

$clientInContract = \backend\modules\customers\models\ContractsCustomers::find()->where(['customer_type' => 'client'])->where(['contract_id' => $contract_id])->all();
$guarantorInContract = \backend\modules\customers\models\ContractsCustomers::find()->where(['customer_type' => 'guarantor'])->where(['contract_id' => $contract_id])->all();
$contractModel = \backend\modules\contracts\models\Contracts::findOne(['id' => $contract_id]);
$campanyModel = \backend\modules\companies\models\Companies::findOne(['id'=>$contractModel->company_id]);
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = $logo = Yii::$app->params['companies_logo'];
    $companyName = '';
    $compay_banks = '';

} else {
    $logo = $primary_company->logo;
    $companyName = $primary_company->name;
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<?php $this->beginBody() ?>
<div class="container">
    <div style="background-color: #b1dfbb;border-radius: 7px">
        <div>
            <center>
                <h1><?= $companyName?></h1>
            </center>
        </div>
        <div>
            <center>
                <h2>براءه ذمة</h2>
            </center>
            <p style="text-align: left;display: inline"><?= date('Y-m-d') ?></p>
        </div>
    </div>
    <div class="body">
        <div>
            <center>
                <h3> معلومات الملف</h3>
            </center>
        </div>
        <div style="text-align: right">
            <h4>
                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white">  اسم المدين : </span>


                <?php
                foreach ($clientInContract as $client) {
                    $clientName = \backend\modules\customers\models\Customers::findOne(['id' => $client->customer_id]);
                    echo $clientName->name;
                    echo "<br>";
                }
                ?>


            </h4>

        </div>
        <div style="text-align: right">
            <h4>
                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white">   اسماء الكفلاء :</span>

                <?php
                foreach ($guarantorInContract as $client) {
                    $clientName = \backend\modules\customers\models\Customers::findOne(['id' => $client->customer_id]);
                    echo $clientName->name;

                }
                ?>

            </h4>

        </div>
        <div style="text-align: right">
            <h4>
                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white">  تاريخ البيع :</span>
                <?php
                $contractModel = \backend\modules\contracts\models\Contracts::findOne(['id' => $contract_id]);
                echo $contractModel->Date_of_sale;
                ?>
            </h4>

        </div>

        <div class="alert alert-warning" role="alert" style="text-align: right">
            <h3>
                لمـن يهمه الأمـــــــر
            </h3>
            <p>
                تشهد <?= $companyName?>أن المدين المذكور أعلاه
                بريـــــــــئ الذمة المالية في العقد الموقع بتاريخ البيع المذكور
                أعلاه و أن كافة الشيكات و السندات الموقعة من قبله بتاريخ هذا العقد ملغيــــة .
            </p>
        </div>
    </div>
    <div class="footer" style="text-align: right">
        <h4>

            <p> نسخة ملف العميل -</p>
            <p> نسخة العميل -</p>

        </h4>

    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('dblclick',function(){
window.print();
})
SCRIPT
)
?>
<style>
    .btn-group > .btn:first-child{
        visibility: hidden !important;
    }
</style>