<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use yii\widgets\ActiveForm;
use yii\data\ActiveDataProvider;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\contracts\models\Contracts;
use backend\helpers\FlatpickrWidget;
use yii\widgets\Pjax;
use common\helper\LoanContract;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;
use backend\widgets\ExportButtons;
/* @var $this yii\web\View */
/* @var $model common\models\FollowUp */
/* @var $form yii\widgets\ActiveForm */

$modelf = new LoanContract;
$judicary_contract = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_id])->all();

$contract_model = $modelf->findContract($contract_id);
$total = $contract_model->total_value;
$d1 = new DateTime($contract_model->first_installment_date);
$d2 = new DateTime(date('Y-m-d'));
$interval = $d2->diff($d1);
if (!empty($judicary_contract)) {
    $all_case_cost = \backend\modules\expenses\models\Expenses::find()->where(['contract_id' => $contract_model->id])->andWhere(['category_id' => 4])->all();
    $sum_case_cost = 0;
    foreach ($all_case_cost as $case_cost) {
        $sum_case_cost = $sum_case_cost + $case_cost->amount;
    }
}
if (!empty($judicary_contract)) {
    $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_model->id])->all();

    foreach ($cost as $cost) {
        $totle_value = $contract_model->total_value + $sum_case_cost + $cost->lawyer_cost;
        $contract_model->total_value = $totle_value;
    }
}
$interval = $interval->y * 12 + $interval->m;

$batches_should_be_paid_count = $interval + 1;
$amount_should_be_paid = (($batches_should_be_paid_count * $contract_model->monthly_installment_value) < $contract_model->total_value) ? $batches_should_be_paid_count * $contract_model->monthly_installment_value : $contract_model->total_value;

if ($contract_model->is_loan == 1) {
    $paid_amount = ContractInstallment::find()
        ->andWhere(['contract_id' => $contract_model->id])->andwhere(['>', 'date', $contract_model->loan_scheduling_new_instalment_date])->sum('amount');
} else {
    $paid_amount = ContractInstallment::find()
        ->andWhere(['contract_id' => $contract_model->id])
        ->sum('amount');
}
$deserved_amount = (date('Y-m-d') >= $contract_model->first_installment_date) ? $amount_should_be_paid - $paid_amount : 0;
$total_value = ($contract_model->total_value > 0) ? $contract_model->total_value : 0;
$remaining_amount = $total_value - $paid_amount;
$custamer_referance = \backend\modules\expenses\models\Expenses::find()
    ->andWhere(['contract_id' => $contract_model->id])->andWhere(['category_id' => 19])
    ->sum('amount');
CrudAsset::register($this);

?>


<div class="contracts-form">
    <p>
        <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
            ارقام هواتف العملاء والمعرفين
        </button>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter2">
            صور العملاء
        </button>
        <button type="button" class="btn btn-primary" data-toggle="collapse" data-target="#demo">
            البيانات الماليه
        </button>
        <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapse" aria-expanded="false" aria-controls="collapse">
            الدفعات
        </button>
        <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#custamerAction" aria-expanded="false" aria-controls="collapse">
            حركات العملاء القضائيه
        </button>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
            للتدقيق
        </button>

        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#changeStatse">
            تغيير حالة العقد
        </button>
        <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#loanschalling" aria-expanded="false" aria-controls="loanschalling">
            التسويات
        </button>
        <?= Html::a('كشف حساب', Url::to(['printer', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary']) ?>
        <?= Html::a('برائة الذمة', Url::to(['clearance', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary']) ?>
        <?php if (Yii::$app->user->can(Permissions::MANAGER)) { ?>
            <?php if ($contract_model->is_can_not_contact == 1) {
                echo Html::a(' يوجد ارقام هواتف', Url::to(['/contracts/contracts/is-connect', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary']);
            } else {
                echo Html::a('لا يوجد ارقام هواتف', Url::to(['/contracts/contracts/is-not-connect', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary']);
            }
            ?>
        <?php } ?>
    </p>


    <div style="text-align: center;color:brown">
        <h2>
            <?= $contract_model->status ?>: حالة العقد
        </h2>
        <?php if ($contract_model->is_can_not_contact == 1) { ?>
            <p>تم الابلاغ انه لا يوجد ارقام تواصل</p>
        <?php } ?>

    </div>

    <div class="modal fade" id="exampleModalCenter2" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    $custamers_image = \backend\modules\imagemanager\models\Imagemanager::find()->innerJoin('os_contracts_customers', 'os_contracts_customers.customer_id = os_ImageManager.contractId')->where(['groupName' => 'coustmers'])->andWhere(['os_contracts_customers.contract_id' => $contract_model->id])->all();
                    $custmer_document_images = \backend\modules\customers\models\CustomersDocument::find()->innerJoin('os_contracts_customers', 'os_contracts_customers.customer_id = os_customers_document.customer_id')->andWhere(['os_contracts_customers.contract_id' => $contract_model->id])->all();
                    if (empty($custmer_document_images)) {
                        echo "لم يتم العثور على نتائج";
                        echo "<br/>";
                    } else {
                        foreach ($custmer_document_images as $image) {
                            if ($image->images != 0) {
                                echo '<div class="col-lg-4">';
                                echo Html::img(Url::to(['/images/imagemanager/' . $image->images]), ['style' => 'width:100px;height:100px; object-fit: contain; margin: 20px', 'class' => 'img img-circle']);
                                echo '</div>';
                            } else {
                                echo "";
                            }
                        }
                        foreach ($custmer_document_images as $image) {
                            if ($image->images != 0) {
                                echo ' <div class="col-lg-4">';
                                echo "<center>";
                                switch ($image->document_number) {
                                    case 0:
                                        echo "هوية";
                                        break;
                                    case 1:
                                        echo 'جواز سفر';
                                        break;
                                    case 2:
                                        echo 'رخصة';
                                        break;
                                    case 3:
                                        echo 'شهادة ميلاد';
                                        break;
                                    default:
                                        echo ' شهادة تعيين';
                                }
                                echo "</center>";
                                echo '</div>';
                            } else {
                                echo "";
                            }
                        }
                    }

                    if (empty($custamers_image)) {
                        echo "لم يتم العثور على  وثائق اخرى";
                    } else {
                        foreach ($custamers_image as $image) {
                            $imagePath = \Yii::$app->imagemanager->getImagePath($image->id);
                            echo "<image src ='{$imagePath}' style = 'width:100px;height:100px; object-fit: contain; margin: 20px' class = 'img img-circle' />";
                        }
                    }

                    ?>
                </div>

            </div>
        </div>
    </div>
    <div id="demo" class="collapse">

        <table class="table" style="border:  1px  solid black ">
            <thead class="thead-dark">
                <?php if (!empty($judicary_contract)) { ?>
                    <tr>
                        <th scope="col" class="info" style="border:  1px  solid black "> رسوم القضيه</th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">رسوم المحامي</th>
                        <th scope="col" class="info" style="border:  1px  solid black ">المبلغ الاساسي</th>
                        <th scope="col" class="danger" style="border:  1px  solid black "> تاريخ الشراء</th>
                        <th scope="col" class="info" style="border:  1px  solid black "> حالة العقد</th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">المبلغ الواجب دفعه حتى هذا
                            التاريخ
                        </th>
                        <th scope="col" class="info" style="border:  1px  solid black ">تاريخ اول استحقاق</th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">قيمة الدفعة الشهرية</th>
                        <th scope="col" class="info" style="border:  1px  solid black ">اجمالي المدفوع</th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">خصم الالتزام</th>
                        <th scope="col" class="info" style="border:  1px  solid black ">المبلغ الاجمالي</th>
                        <th scope="col" class="danger" style="border:  1px  solid black "> القيمة المستحقة</th>
                        <th scope="col" class="info" style="border:  1px  solid black "> المبلغ المتبقي</th>
                        <?php
                        if ($contract_model->is_loan == 1) {
                        ?>
                            <th scope="col" class="danger" style="border:  1px  solid black "> المبلغ المدفوع بعد
                                التسويه
                            </th>
                        <?php } ?>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <th scope="col" class="info" style="border:  1px  solid black "> تاريخ الشراء</th>
                        <th scope="col" class="danger" style="border:  1px  solid black "> حالة العقد</th>
                        <th scope="col" class="info" style="border:  1px  solid black ">المبلغ الواجب دفعه حتى هذا التاريخ
                        </th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">تاريخ اول استحقاق</th>
                        <th scope="col" class="info" style="border:  1px  solid black ">قيمة الدفعة الشهرية</th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">اجمالي المدفوع</th>
                        <th scope="col" class="info" style="border:  1px  solid black ">خصم الالتزام</th>
                        <th scope="col" class="danger" style="border:  1px  solid black ">المبلغ الاجمالي</th>
                        <th scope="col" class="info" style="border:  1px  solid black "> القيمة المستحقة</th>
                        <th scope="col" class="danger" style="border:  1px  solid black "> المبلغ المتبقي</th>
                        <?php
                        if ($contract_model->is_loan == 1) {
                        ?>
                            <th scope="col" class="info" style="border:  1px  solid black "> المبلغ المدفوع بعد التسويه</th>
                    <?php }
                    } ?>
                    </tr>
            </thead>
            <tr>
                <?php if (!empty($judicary_contract)) { ?>

                    <td scope="row" style="border:  1px  solid black ">
                        <?php
                        echo $sum_case_cost;
                        ?>
                    </td>
                    <td style="border:  1px  solid black ">
                        <?php
                        if (!empty($judicary_contract)) {
                            $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_model->id])->orderBy(['contract_id' => SORT_DESC])->one();
                            if (empty($cost)) {
                                echo 'لا يوجد';
                            } else {
                                echo $cost->lawyer_cost;
                            }
                        } ?>

                    </td>
                    <td style="border:  1px  solid black ">
                        <?php
                        if (!empty($judicary_contract)) {
                            echo $total;
                        } ?>
                    </td>
                <?php } ?>

                <td scope="row" style="border:  1px  solid black ">
                      <?= $contract_model->Date_of_sale; ?>

                </td>
                <td style="border:  1px  solid black ">
                      <?= !empty($judicary_contract) ? Yii::t('app', $contract_model->status) : 0 ?>

                </td>
                <td style="border:  1px  solid black ">
                    <?php
                    $amount_should_be_paid = ($amount_should_be_paid > 0) ? $amount_should_be_paid : 0;
                    if ($amount_should_be_paid >= $contract_model->total_value) {
                        echo $contract_model->total_value;
                    } else {
                        echo $amount_should_be_paid;
                    }


                    ?>
                </td>
                <td style="border:  1px  solid black ">
                      <?= $contract_model->first_installment_date; ?>
                </td>
                <td style="border:  1px  solid black ">
                     
                    <?= isset($contract_model->monthly_installment_value) ? $contract_model->monthly_installment_value : 0 ?>
                </td>

                <td style="border:  1px  solid black ">
                    <?php
                    $paid_amount = ContractInstallment::find()
                        ->andWhere(['contract_id' => $contract_model->id])
                        ->sum('amount');
                    $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                    echo $paid_amount ?>
                </td>

                <td style="border:  1px  solid black ">
                    <?php
                    echo ($contract_model->commitment_discount > 0) ? $contract_model->commitment_discount : 0;
                    ?>
                </td>
                <td style="border:  1px  solid black ">
                
                    <?php
                    echo $contract_model->total_value;

                    ?>
                </td>
                <td style="border:  1px  solid black ">
                    <?php
                    echo ($deserved_amount > 0) ? $deserved_amount : 0;

                    ?>
                </td>
                <td style="border:  1px  solid black ">
                    <?php

                    $paid_amount = ContractInstallment::find()
                        ->andWhere(['contract_id' => $contract_model->id])
                        ->sum('amount');

                    $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                    $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;

                    echo ($contract_model->total_value + $custamer_referance) - $paid_amount;

                    ?>
                </td>
                <?php $paid_amount = ContractInstallment::find()
                    ->andWhere(['contract_id' => $contract_model->id])->andwhere(['>', 'date', $contract_model->loan_scheduling_new_instalment_date])->sum('amount');
                if ($contract_model->is_loan == 1) {
                ?>
                    <td style="border:  1px  solid black ">
                        <?= ($paid_amount > 0) ? $paid_amount : 0 ?>
                    </td>
                <?php } ?>
            </tr>
        </table>


    </div>
    <div class="modal fade" id="tas" tabindex="-1" role="dialog" aria-labelledby="tasc" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="tasc" style="text-align: right">اضافة تسوية</h3>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info loan-alert" style="display: none">
                    </div>
                    <div>
                        <div class="row">
                            <form>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="monthly_installment">القسط الشهري</label>
                                        <input type="text" class="form-control" id="monthly_installment" aria-describedby="emailHelp" placeholder="القسط الشهري">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="new_installment_date">تاريخ اول دفعة</label>
                                        <input type="date" class="form-control" id="new_installment_date" aria-describedby="emailHelp" placeholder="تاريخ النسويه">
                                    </div>
                                </div>
                        </div>
                        <div class="row">

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="first_installment_date">تاريخ النسويه</label>
                                    <input type="date" class="form-control" id="first_installment_date" aria-describedby="emailHelp" placeholder="تاريخ اول دفعة">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" value='<?= $contract_model->id ?>' id="contract_id">
                        </form>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id='closeModel'>الغاء</button>
                    <button type="button" class="btn btn-primary" id="save">حفظ التغييرات</button>
                </div>
            </div>
        </div>
    </div>
    <div class="collapse" id="custamerAction">
        <div class="card card-body">
            <?php
            $custamerAction = new ActiveDataProvider([
                'query' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()->select([
                    'os_judiciary_customers_actions.action_date',
                    'os_judiciary.contract_id',
                    'os_judiciary_customers_actions.judiciary_id',
                    'os_judiciary_customers_actions.customers_id',
                    'os_judiciary_customers_actions.note',
                    'os_judiciary_customers_actions.created_by',
                    'os_judiciary_customers_actions.judiciary_actions_id',
                    'os_judiciary_customers_actions.id'

                ])
                    ->innerJoin('os_judiciary', 'os_judiciary.id=os_judiciary_customers_actions.judiciary_id')
                    ->where(['os_judiciary.contract_id' => $contract_id])->orderBy(['os_judiciary_customers_actions.action_date' => SORT_ASC])
            ]);
            ?>
            <?= GridView::widget([
                'id' => 'os_judiciary_customers_actions',
                'dataProvider' => $custamerAction,
                'summary' => '',
                'toolbar' => [
                    [
                        'content' =>
                        Html::a('<i class="glyphicon glyphicon-plus"></i>', Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $contract_id]), ['role' => 'modal-remote', 'title' => 'إنشاء إجراءات قضائية', 'class' => 'btn btn-default'])


                    ],
                ],
                'columns' => [
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'judiciary_id',
                        'label' => Yii::t('app', 'judiciary'),
                        'value' => function ($model) {
                            return \common\helper\FindJudicary::findJudiciaryNumberJudicary($model->judiciary_id) . '/' . \common\helper\FindJudicary::findYearJudicary($model->judiciary_id);
                        }
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'label' => Yii::t('app', 'Court'),
                        'value' => function ($model) {
                            return \common\helper\FindJudicary::findCourtJudicary($model->judiciary_id);
                        }

                    ],

                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'customers_id ',
                        'value' => 'customers.name',
                        'label' => Yii::t('app', 'Customers Id')
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'judiciary_actions_id',
                        'value' => 'judiciaryActions.name',
                        'label' => Yii::t('app', 'Judiciary Actions ID')

                    ],

                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'note',
                        'value' => function ($model) {
                            if (!empty($model->note)) {
                                return "<textarea rows=3' style=' resize: none;' disabled>{$model->note}</textarea>";
                            }
                        },
                        'format' => 'raw'
                    ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'created_at',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'updated_at',
                    // ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'created_by',
                        'value' => 'createdBy.username'
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'اسم الوكيل',
                        'value' => function ($model) {
                            return \common\helper\FindJudicary::findLawyerJudicary($model->judiciary_id);
                        }
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'action_date',
                        'label' => 'تاريخ الحركة'
                    ],
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'dropdown' => false,
                        'vAlign' => 'middle',
                        'template' => '{delete} {update}',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            if ($action == 'delete') {
                                return Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/' . $action, 'id' => $model->id]);
                            } else {
                                $contract_id = \common\helper\FindJudicary::findJudiciaryContract($model->judiciary_id);
                                return Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'id' => $model->id, 'contractID' => $contract_id]);
                            }
                        },
                        'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                        'deleteOptions' => [
                            'role' => 'modal-remote', 'title' => 'Delete',
                            'data-confirm' => false, 'data-method' => false, // for overide yii data api
                            'data-request-method' => 'post',
                            'data-toggle' => 'tooltip',
                            'data-confirm-title' => 'Are you sure?',
                            'data-confirm-message' => 'Are you sure want to delete this item'
                        ],
                    ],
                ],

                'striped' => false,
                'condensed' => false,
                'responsive' => false,
                'export' => false,
                'panel' => [
                    'type' => false,
                    'heading' => false,
                    'after' => false,
                    'footer' => false
                ]

            ]) ?>
        </div>
    </div>
    <div class="col-sm-12 " style="text-align: right">

    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div id='display' ondblclick='copyText(this)'>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <br>
                        <h2 class="modal-title" id="exampleModalLabel" style="text-align: right"> تدقيق عقد رقم
                            : <?= $contract_id ?></h2>
                    </div>
                    <div class="modal-body">
                        <div>
                            <div style="text-align: right">
                                <h3>:معلومات العملاء</h3>
                                <?php
                                $custamer = contracts::findOne($contract_id)->customersAndGuarantor;
                                foreach ($custamer

                                    as $custamers) {
                                ?> : العميل <?= $custamers->name ?> -
                                    <br>
                                    صاحب الرقم الوطني : <?= $custamers->id_number ?>
                                    <br>
                                    المدينه:
                                    :<?php if (!empty($custamers->city)) {
                                            $custamer_city = \backend\modules\city\models\City::findOne(['id' => $custamers->city]);
                                            echo $custamer_city->name;
                                        } else {
                                            echo '  لا يوجد';
                                        }
                                        ?>

                                    <br>
                                    <bre>
                                        العمل :<?php if (!empty($custamers->job_title)) {
                                                    $jod = \backend\modules\jobs\models\Jobs::findOne(['id' => $custamers->job_title]);
                                                    echo $jod->name;
                                                } else {
                                                    echo 'لا يوجد';
                                                }; ?>
                                    <?php
                                    $address = \backend\modules\address\models\Address::find()->where(['customers_id' => $custamers->id])->all();
                                    echo '  <h5>: عناوين ' . '  ';
                                    echo $custamers->name . '  </h5>';
                                    if (!empty($address)) {
                                        foreach ($address as $addressHome) {
                                            echo "<br>";
                                            echo ($addressHome->address_type == 1) ? 'مكان العمل:  ' : '   مكان السكن:  ';
                                            echo !empty($addressHome->address) ? $addressHome->address : 'لا يوجد';
                                            echo "<br>";
                                            echo "  نوع العنوان : ";
                                            echo !empty($addressHome->address_type) ? ($addressHome->address_type == 1) ? 'عنوان العمل' : 'عنوان السكن' : 'لا يوجد';
                                            echo "<br>";
                                        }
                                    }
                                } ?>
                            </div>
                            <div style="text-align: right">
                                <h3>المعرفين</h3>
                                <?php
                                $result = Contracts::findOne($contract_id);
                                if (!empty($result)) {
                                    foreach ($result->contractsCustomers as $key => $value) {
                                        foreach ($value->customer->phoneNumbers as $phoneNumbers) {
                                            echo "الاسم: ";
                                            echo $phoneNumbers->owner_name;
                                            echo " ,صلة القرابه:  ";
                                            $relation = \backend\modules\cousins\models\Cousins::findOne($phoneNumbers->phone_number_owner);
                                            echo $relation->name;
                                            echo "-";
                                            echo "<br>";
                                            echo "<br>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <div style="text-align: right">
                                <?php
                                $judicarys = backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_id])->all();
                                if (!empty($judicarys)) {
                                    echo "  <h3>:المعلومات القضائية</h3>";
                                    foreach ($judicarys as $judicary) {
                                        echo ":معلومات القضية رقم ";
                                        echo (!empty($judicary->judiciary_number)) ? (!empty($judicary->year)) ? $judicary->judiciary_number . '/' . $judicary->year : '' : '';
                                        echo "-";

                                        echo "<br>";
                                        echo "تاريخ الورود :";
                                        echo (!empty($judicary->income_date)) ? $judicary->income_date : 'لا يوجد';

                                        echo "<br>";
                                        $lawyer = \backend\modules\lawyers\models\Lawyers::findOne(['id' => $judicary->lawyer_id]);
                                        if (!empty($lawyer)) {
                                            echo ' المحامي:  ';
                                            echo (!empty($lawyer->name)) ? $lawyer->name : 'لا يوجد';
                                        }
                                        echo "</br>";
                                        $court = \backend\modules\court\models\Court::findOne(['id' => $judicary->court_id]);
                                        if (!empty($court)) {
                                            echo ' المحكمة:  ';
                                            echo (!empty($court->name)) ? $court->name : 'لا يوجد';
                                        }
                                        echo "<br>";
                                        echo "<br>";
                                    }
                                }
                                ?>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="collapse" id="collapse">
        <div class="card card-body">
            <?php
            $data = new yii\data\ArrayDataProvider([
                'key' => 'id',
                'allModels' => \backend\modules\income\models\Income::find()->Where(['contract_id' => $contract_id])->orderBy(['date' => SORT_DESC])->all(),
            ]);

            echo GridView::widget([
                'id' => 'income-table-crud-datatable',
                'dataProvider' => $data,
                'summary' => '',
                'pjax' => true,
                'columns' => [
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => '_by',
                        'label' => Yii::t('app', 'By'),
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'contract_id',
                        'value' => function ($model) {
                            return Html::a($model->contract_id, Url::to(['/contracts/contracts/update', 'id' => $model->contract_id]), ['data-pjax' => 0, 'target' => '_blank']);
                        },
                        'label' => Yii::t('app', 'Contract ID'),
                        'format' => 'raw',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'date',
                        'label' => Yii::t('app', 'Date'),
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'amount',
                        'label' => Yii::t('app', 'Amount'),
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'created_by',
                        'value' => 'created.username',
                        'label' => Yii::t('app', 'Created By'),
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'value' => function ($model) {
                            $type = \backend\modules\paymentType\models\PaymentType::findOne(['id' => $model->payment_type]);
                            return $type->name;
                        },
                        'label' => Yii::t('app', 'Payment Type'),
                    ],

                ],
                'striped' => false,
                'condensed' => false,
                'responsive' => false,
                'export' => false,
            ]);

            echo "<hr/>";
            $data = new yii\data\ArrayDataProvider([
                'key' => 'id',
                'allModels' => \backend\modules\expenses\models\Expenses::find()->Where(['contract_id' => $contract_id])->orderBy(['expenses_date' => SORT_DESC])->all(),
            ]);
            echo GridView::widget([
                'id' => 'income-table-crud-datatable',
                'dataProvider' => $data,
                'summary' => '',
                'pjax' => true,
                'columns' => [

                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'category_id',
                        'value' => 'category.name'
                    ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'created_at',
                    // ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'created_by',
                        'value' => 'createdBy.username'
                    ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'updated_at',
                    // ],
                    //  [
                    //    'class'=>'\kartik\grid\DataColumn',
                    //  'attribute'=>'last_updated_by',
                    //],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'description',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'amount',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'receiver_number',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'expenses_date',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'notes',
                        'label' => Yii::t('app', 'Notes'),
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'document_number',
                        'label' => Yii::t('app', 'Document Number'),
                    ],


                ],
                'striped' => false,
                'condensed' => false,
                'responsive' => false,
                'export' => false,
            ]);
            ?>
        </div>
    </div>
    <div class="collapse" id="collapseExample">
        <div class="card card-body">
            <div class="row">
                <div id="ajaxCrudDatatable">
                    <?php
                    $dataProvider = new yii\data\ArrayDataProvider([
                        'key' => 'id',
                        'allModels' => contracts::findOne($contract_id)->customersAndGuarantor,
                    ]);
                    ?>
                    <?php
                    echo GridView::widget([
                        'id' => 'customers-table-crud-datatable',
                        'dataProvider' => $dataProvider,
                        'summary' => '',
                        'pjax' => true,
                        'pjaxSettings' => [
                            'neverTimeout' => true,
                            'options' => [
                                'id' => 'customers-table-crud-datatable',
                            ]
                        ],
                        'columns' => [
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'name',
                                'value' => function ($model) {
                                    return '<button type="button" class="btn btn-primary custmer-popup"  data-target="#exampleModal12" data-toggle="modal" customer-id ="' . $model->id . '">
  ' . $model->name . '
</button>';
                                },
                                'format' => 'raw',
                            ],
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'primary_phone_number',
                                'value' => function ($model) {
                                    return Html::a($model->primary_phone_number, Url::to(['/customers/update', 'id' => $model->id]), ['data-pjax' => 0]);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => 'Contract Count',
                                'value' => function ($model) {
                                    $count = 0;
                                    $contracts = \backend\modules\Customers\models\ContractsCustomers::find()->where('customer_id =' . $model->id)->all();
                                    foreach ($contracts as $contract) {
                                        $contractStatuc = Contracts::findOne($contract->contract_id);
                                        if ($contractStatuc->status != 'finished' && $contractStatuc->status != 'canceled') {
                                            $count = $count + 1;
                                        }
                                    }
                                    return $count;
                                }
                            ],
                            'facebook_account',
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<div itemscope itemtype="https://schema.org/LocalBusiness">
                                                <span itemprop="telephone">
                                                    <a class="btn btn-info btn-lg" href="tel:' . \backend\helpers\PhoneHelper::toTel($model->primary_phone_number) . '">
                                                        <span class="glyphicon glyphicon-earphone"></span>
                                                    </a></span>
                                            </div>';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<a style="background-color: #60ca60;" target="_blank" class="btn btn-lg" href="https://wa.me/' . \backend\helpers\PhoneHelper::toWhatsApp($model->primary_phone_number) . '">
                                                        <span class="fa fa-whatsapp" style="color: white;"></span>
                                                    </a>
                                            ';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if (empty($model->facebook_account)) {
                                        return '<a style="background-color: deepskyblue;border: 1px solid black" target="_blank"   class="btn btn-lg" >
                                                        <span class="fa fa-facebook" style="color: white; "></span>
                                                    </a>  ';
                                    }
                                    return '<a style="background-color: #4267B2;" target="_blank" class="btn btn-lg" href="https://m.me/' . $model->facebook_account . '">
                                                        <span class="fa fa-facebook" style="color: white;"></span>
                                                    </a>  ';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<button type="button" onclick="setPhoneNumebr(' . $model->primary_phone_number . ')" class="btn btn-primary glyphicon glyphicon-comment" data-toggle="modal" data-target="#exampleModalCenter"></button>';
                                }
                            ],
                            [
                                'class' => 'kartik\grid\ActionColumn',
                                'dropdown' => false,
                                'vAlign' => 'middle',
                                'template' => '{update}',
                                'urlCreator' => function ($action, $model, $key, $index) {
                                    if ($action == "update") {

                                        return Url::to(['/customers/update-contact', 'id' => $model->id]);
                                    }
                                },
                                'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                            ],
                        ],
                        'striped' => false,
                        'condensed' => false,
                        'responsive' => false,
                        'export' => false,
                        'panel' => [
                            'type' => false,
                            'heading' => false,
                            'before' => '<h3>' . Yii::t('app', 'Coustmers Phone Numbers') . '</h3>',
                            'after' => false,
                            'footer' => false
                        ]
                    ])
                    ?>
                </div>
            </div>
        </div>

        <div class="row">

            <?php
            $result = Contracts::findOne($contract_id);
            ?>
            <div id="ajaxCrudDatatable">
                <?php
                foreach ($result->contractsCustomers as $key => $value) {
                    $dataProvider = new yii\data\ArrayDataProvider([
                        'key' => 'id',
                        'allModels' => $value->customer->phoneNumbers,
                    ]);
                    $pjaxGrideViewID = "customers-info-table-crud-datatable-{$value->customer->id}";
                    echo GridView::widget([
                        'id' => $pjaxGrideViewID, //'customers-info-table-crud-datatable',
                        'dataProvider' => $dataProvider,
                        'summary' => '',
                        'pjax' => true,
                        'pjaxSettings' => [
                            'neverTimeout' => true,
                            'options' => [
                                'id' => $pjaxGrideViewID,
                            ]
                        ],
                        'columns' => [
                            'phone_number',
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'owner_name',
                                'value' => function ($model) {
                                    return Html::a($model->owner_name, Url::to(['/phoneNumbers/phone-numbers/update', 'id' => $model->id]), ['data-pjax' => 0]);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => Yii::t('app', 'phone number owner'),
                                'value' => function ($model) {

                                    $relation = backend\modules\cousins\models\Cousins::findone(['id' => $model->phone_number_owner]);
                                    return !empty($relation) ? $relation->name : '';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<div itemscope itemtype="https://schema.org/LocalBusiness">
                                                <span itemprop="telephone">
                                                    <a class="btn btn-info btn-lg" href="tel:' . \backend\helpers\PhoneHelper::toTel($model->phone_number) . '">
                                                        <span class="glyphicon glyphicon-earphone"></span>
                                                    </a></span>
                                            </div>';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<a style="background-color: #60ca60;" target="_blank" class="btn btn-lg" href="https://wa.me/' . \backend\helpers\PhoneHelper::toWhatsApp($model->phone_number) . '">
                                                        <span class="fa fa-whatsapp" style="color: white;"></span>
                                                    </a>
                                            ';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if (empty($model->fb_account)) {
                                        return '<a style="background-color: deepskyblue;border: 1px solid black" target="_blank"   class="btn btn-lg" >
                                                        <span class="fa fa-facebook" style="color: white; "></span>
                                                    </a>  ';
                                    }
                                    return '<a style="background-color: #4267B2;" target="_blank" class="btn btn-lg" href="https://m.me/' . $model->fb_account . '">
                                                        <span class="fa fa-facebook" style="color: white;"></span>
                                                    </a>  ';
                                }
                            ],
                            [
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<button type="button" onclick="setPhoneNumebr(' . $model->phone_number . ')" class="btn btn-primary glyphicon glyphicon-comment" data-toggle="modal" data-target="#exampleModalCenter"></button>';
                                }
                            ],
                            [
                                'class' => 'kartik\grid\ActionColumn',
                                'dropdown' => false,
                                'vAlign' => 'middle',
                                'template' => '{delete} {update}',
                                'urlCreator' => function ($action, $model, $key, $index) {
                                    return Url::to(['/phoneNumbers/phone-numbers/' . $action, 'id' => $key]);
                                },
                                'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                                'deleteOptions' => [
                                    'role' => 'modal-remote', 'title' => 'Delete',
                                    'data-confirm' => false, 'data-method' => false, // for overide yii data api
                                    'data-request-method' => 'post',
                                    'data-toggle' => 'tooltip',
                                    'data-confirm-title' => 'Are you sure?',
                                    'data-confirm-message' => 'Are you sure want to delete this item'
                                ],
                            ],
                        ],
                'toolbar' => [
                    [
                        'content' =>
                        Html::a('<i class="glyphicon glyphicon-plus"></i>', ['/phoneNumbers/phone-numbers/create?contract_id=' . $value->customer->name . '&customers_id=' . $value->customer->id], ['role' => 'modal-remote', 'title' => 'Create new Phone Numbers', 'class' => 'btn btn-default']) .
                            '{toggleData}' .
                            ExportButtons::widget([
                                'excelRoute' => ['export-phone-numbers-excel', 'contract_id' => $contract_id],
                                'pdfRoute' => ['export-phone-numbers-pdf', 'contract_id' => $contract_id],
                            ])
                    ],
                ],
                'striped' => false,
                'condensed' => false,
                'responsive' => false,
                'export' => false,
                'panel' => [
                    'type' => false,
                    'heading' => false,
                    'before' => '<h4>' . $value->customer->name . '</h4>',
                    'after' => false,
                    'footer' => false
                ]
            ]);
        ?>

        <?php
        }
        ?>

        </div>
    </div>
</div>
    <div class="collapse" id="loanschalling">
        <div class="card card-body">
            <?php
            $LoanScheduling = new ActiveDataProvider([
                'query' => \backend\modules\loanScheduling\models\LoanScheduling::find()->where(['contract_id' => $contract_model->id])
            ]);
            ?>

            <?php
            $pjaxGrideViewID = "table-crud-datatable-{$contract_model->id}";
            echo GridView::widget([
                'id' => $pjaxGrideViewID,
                'dataProvider' => $LoanScheduling,

                'pjax' => true, 'columns' => [
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'contract_id',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'new_installment_date',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'monthly_installment',
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'first_installment_date',
                    ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'status',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'status_action_by',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'created_at',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'updated_at',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'created_by',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'last_update_by',
                    // ],
                    // [
                    // 'class'=>'\kartik\grid\DataColumn',
                    // 'attribute'=>'is_deleted',
                    // ],
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'dropdown' => false,
                        'vAlign' => 'middle',
                        'template' => '{delete}',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            if ($action == 'delete') {
                                return Url::to(['/loanScheduling/loan-scheduling/delete-from-follow-up', 'id' => $model->id, 'contract_id' => $model->contract_id]);
                            }
                            return Url::to([$action, 'id' => $key]);
                        },
                        'viewOptions' => ['title' => 'View', 'data-toggle' => 'tooltip'],
                        'updateOptions' => ['title' => 'Update', 'data-toggle' => 'tooltip'],
                        'deleteOptions' => [
                            'title' => 'Delete',
                            'data-confirm' => false, 'data-method' => false, // for overide yii data api
                            'data-request-method' => 'post',
                            'data-toggle' => 'tooltip',
                            'data-confirm-title' => 'Are you sure?',
                            'data-confirm-message' => 'Are you sure want to delete this item'
                        ],
                    ],

                ],
                'toolbar' => [
                    [
                        'content' =>
                        Html::a('<i class="glyphicon glyphicon-plus"></i>', ['/loanScheduling/loan-scheduling/create-from-follow-up?contract_id=' . $contract_model->id], ['role' => 'modal-remote', 'title' => 'Create new Phone Numbers', 'class' => 'btn btn-default']) .
                            '{toggleData}' .
                            ExportButtons::widget([
                                'excelRoute' => ['export-loan-scheduling-excel', 'contract_id' => $contract_id],
                                'pdfRoute' => ['export-loan-scheduling-pdf', 'contract_id' => $contract_id],
                            ])
                    ],
                ],
                'striped' => false,
                'condensed' => false,
                'responsive' => false,
                'export' => false,
                'panel' => [
                    'type' => false,
                    'heading' => false,
                    'after' => false,
                    'footer' => false
                ]
            ]) ?>

        </div>
    </div>

    <div class="contact-installment-form">

        <div class="row">

            <div class="col-sm-12" style="    text-align: center;
                                         display: block;
                                         width: 100%;
                                         padding: 0;
                                         margin-bottom: 20px;
                                         font-size: 21px;
                                         line-height: inherit;
                                         color: #333333;
                                         border: 1px solid #e5e5e5;">

                <h5>
                    <legend>
                        <h3><?= Yii::t('app', 'Contract Notes') ?></h3>
                    </legend>

                    <?php echo ($contract_model->notes != "") ? $contract_model->notes : Yii::t('app', "no notes"); ?>
                </h5>
            </div>

        </div>


        <?php
        if ($model->isNewRecord) {
            $form = ActiveForm::begin(['action' => Url::to(['/followUp/follow-up/create', 'contract_id' => $contract_id]), 'id' => 'dynamic-form']);
        } else {
            $form = ActiveForm::begin(['action' => Url::to(['/followUp/follow-up/update', 'contract_id' => $contract_id, 'id' => Yii::$app->getRequest()->getQueryParam('id')]), 'id' => 'dynamic-form']);
        }
        ?>
        <?= $form->field($model, 'contract_id')->hiddenInput(['value' => $contract_id])->label(false) ?>
        <?= $form->field($model, 'created_by')->hiddenInput(['value' => Yii::$app->user->id])->label(false) ?>

        <legend>
            <h3><?= Yii::t('app', 'Follow Up Information') ?></h3>
        </legend>

    </div>
    <div class="row">
        <div class="col-sm-3 col-xs-3">
            <?= $form->field($model, 'connection_goal')->dropDownList([1 => 'تحصيل', 2 => 'مصالحة', 3 => 'انهاء عقد'], ['prompt' => '']) ?>
        </div>
        <div class="col-sm-3 col-xs-3">
            <?= $form->field($model, 'reminder')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                'dateFormat' => 'Y-m-d'
            ]]);
            ?>
        </div>
        <div class="col-sm-3 col-xs-3">
            <?=
            $form->field($model, 'promise_to_pay_at')->widget(FlatpickrWidget::class, [
                'options' => ['placeholder' => Yii::t('app', 'Enter Date of sale ...')],
                'pluginOptions' => [
                    'dateFormat' => 'Y-m-d',
                ]
            ]);
            ?>
        </div>
        <div class="col-sm-3 col-xs-3">
            <?= $form->field($model, 'feeling')->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\feelings\models\Feelings::find()->all(), 'id', 'name'), ['prompt' => '']) ?>
        </div>

    </div>
    <div class="row">
        <div class="col-sm-12 col-xs-12">

            <?= $form->field($model, 'notes')->textarea(['rows' => 6]) ?>
        </div>
    </div>

    <legend>
        <h3>متابعة الأرقام</h3>
    </legend>
    <div class="row">

        <?php
        echo $this->render('partial/phone_numbers_follow_up', [
            'form' => $form,
            'model' => $result,
            'modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps,
        ]);
        ?>
    </div>


    <div class="row">
        <div class="col-sm-4 col-xs-4">
            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
    <?php
    Modal::begin([
        "id" => "ajaxCrudModal",
        "footer" => "", // always need it for jquery plugin
    ]);
    Modal::end();
    ?>

    <!-- Modal -->
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">نص الرسالة</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="phone_number" value="0">
                    <textarea id="sms_text" name="sms_text" rows="4" cols="50"></textarea>

                    <label for="sms_text">عدد الاحرف :</label>
                    <div id="char_count">0</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="send_sms" data-dismiss="modal">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="changeStatse" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> تغيير حالة العقد</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <select class="status-content">
                    <option value="pending"> Pending</option>
                    <option value="active"> Active</option>
                    <option value="reconciliation"> Reconciliation</option>
                    <option value="judiciary"> Judiciary</option>
                    <option value="canceled"> Canceled</option>
                    <option value="refused"> Refused</option>
                    <option value="legal_department"> Legal Department</option>
                    <option value="finished"> finished</option>
                    <option value="settlement"> settlement</option>

                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary statse-change" contract-id="<?= $contract_model->id ?>">
                    Save changes
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="exampleModalCenter2" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle2">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="exampleModal12" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel12" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel12"></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6">
                        <label>الاسم</label>
                        <input type="text" class="cu-name" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>الرقم الوطني</label>
                        <input type="text" class="cu-id-number" disabled>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label>تاريخ الميلاد</label>
                        <input type="text" class="cu-birth-date" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>مدينة الميلاد</label>
                        <input type="text" class="cu-city" disabled>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label>الجنس</label>
                        <input type="text" class="cu-sex" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label> الوظيفه</label>
                        <input type="text" class="cu-job-title" disabled>
                    </div>


                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label> الرقم الوظيفي</label>
                        <input type="text" class="cu-job-number" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>البريد الالكتروني</label>
                        <input type="text" class="cu-email" disabled>
                    </div>


                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label>اسم البنك</label>
                        <input type="text" class="cu-bank-name" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>رقم الحساب</label>
                        <input type="text" class="cu-account-number" disabled>
                    </div>

                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label>فرع بنك</label>
                        <input type="text" class="cu-bank-branch" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>مشترك بالضمان</label>
                        <input type="text" class="cu-is-social-security" disabled>
                    </div>

                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label>رقم الضمان الاجتماعي</label>
                        <input type="text" class="cu-social-security-number" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>لديه املاك</label>
                        <input type="text" class="cu-do-have-any-property" disabled>
                    </div>
                    <div class="col-lg-6">
                        <label>كيف سمعت عنا</label>
                        <input type="text" class="cu-hear-about-us" disabled>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <label> ملاحظات</label>
                        <textarea class="cu-notes" disabled></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a class="btn btn-primary" id="cus-link">Edit</a>
            </div>
        </div>
    </div>
</div>
<script>
    function setPhoneNumebr(number) {
        $("#phone_number").val(number);

    }
</script>
<?php
$change_status = "let id = $('.statse-change').attr('contract-id');
 let statusContent = $('.status-content').val();
 $.post('" . Url::to(['/followUp/follow-up/change-status']) . "',{id:id,statusContent:statusContent},function(e){
 location.reload();";
$this->registerJs(
    <<<SCRIPT
$(document).on('click','.statse-change',function(){
 $change_status
 })
})
SCRIPT
);
$script = <<< JS
$(document).ready(function() {
  var textarea = $("#sms_text");
  textarea.keydown(function(event) {
    var numbOfchars = textarea.val();
    var len = numbOfchars.length;
    $("#char_count").text(len);
  });                    
});
                        
JS;
$this->registerJs($script);
?>

<?php
$hammad = "
$(document).on('click','#send_sms',function(){
let phone_number = $('#phone_number').val();
let text =$('#sms_text').val();
$.post('" . yii\helpers\Url::to(["/followUp/follow-up/send-sms"]) . "',{text:text,phone_number:phone_number},function(data){
 let msg = JSON.parse(data)
if (msg.message == ''){
      alert('تم ارسال الرسالة بنجاح');
      }else 
       alert(msg.message);

})                     

})";
$this->registerJs(
    <<<SCRIPT
$hammad
SCRIPT
)
?>
<script>
    function copyText(element) {
        var range, selection, worked;

        if (document.body.createTextRange) {
            range = document.body.createTextRange();
            range.moveToElementText(element);
            range.select();
        } else if (window.getSelection) {
            selection = window.getSelection();
            range = document.createRange();
            range.selectNodeContents(element);
            selection.removeAllRanges();
            selection.addRange(range);
        }

        try {
            document.execCommand('copy');
            alert('text copied');
        } catch (err) {
            alert('unable to copy text');
        }
    }
</script>

<?php
$this->registerJs(
    <<<SCRIPT
$(window).on('load',function(){
if($contract_model->is_loan == 1){
alert('هذا العقد تمت تسويته');
}
})

$(document).on('click','#save',function(){

let monthly_installment = $('#monthly_installment').val();
let new_installment_date = $('#new_installment_date').val();
let first_installment_date = $('#first_installment_date').val();
let contract_id = $('#contract_id').val();
$.post('add-new-loan',{monthly_installment:monthly_installment,new_installment_date:new_installment_date,first_installment_date:first_installment_date,contract_id:contract_id },function(msg){
$('.loan-alert').css("display","block");
$('.loan-alert').text(msg);
})
})
$(document).on('click','#closeModel',function(){
 location.reload(true);
})
SCRIPT
)
?>
<?php

$custmer = "
";
$url = Url::to(['/followUp/follow-up/custamer-info']);
$hrefurl = Url::to(['/followUp/follow-up/custamer-info']);

$this->registerJs(
    <<<SCRIPT
$(document).on('change','.cant_contact',function(){
let id = $('.cant_contact').attr('contract_id');
let val1 = $('.cant_contact').val();
alert(val1);

});
$(document).on('click','.custmer-popup',function(){
let customerId = $(this).attr('customer-id');
var  a = document.getElementById('cus-link'); 
a.setAttribute("href", "../../customers/update/"+ customerId);

$.post('$url',{customerId:customerId },function(msg){
 let info = JSON.parse(msg);
 $('.cu-name').val(info['name']);
 $('#exampleModalLabel12').text(info['name']);
 $('.cu-id-number').val(info['id_number']);
 $('.cu-birth-date').val(info['birth_date']);
 $('.cu-job-number').val(info['job_number']);
 $('.cu-email').val(info['email']);
 $('.cu-account-number').val(info['account_number']);
 $('.cu-bank-branch').val(info['bank_branch']);
 $('.cu-primary-phone-number').val(info['primary_phone_number']);
 $('.cu-sex').val(info['sex']);
 $('.cu-facebook-account').val(info['facebook_account']);
 $('.cu-hear-about-us').val(info['hear_about_us']);
 $('.cu-status').val(info['status']);
 $('.cu-city').val(info['city']);
 $('.cu-bank-name').val(info['bank_name']);
 $('.cu-job-title').val(info['job_title']);
 $('.cu-notes').val(info['notes']);
 if(info['social_security_number'] != undefined){
  $('.cu-social-security-number').val(info['social_security_number']);
 
 
  if(info['is_social_security'] = '0'){
    $('.cu-is-social-security').val('لا');
  }else{
    $('.cu-is-social-security').val('نعم');
  } 
   
   if(info['do_have_any_property'] = '0'){
    $('.cu-do-have-any-property').val('لا');
  }else{
    $('.cu-do-have-any-property').val('نعم');
  }

 }
})
});

SCRIPT


)
?>