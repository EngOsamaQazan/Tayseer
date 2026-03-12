<?php

use yii\helpers\Html;
use backend\modules\contractInstallment\models\ContractInstallment;

use common\helper\LoanContract;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\Judiciary */
$this->title = Yii::t('app', 'Create Judiciary');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Judiciary'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$modelf = new LoanContract;
$contract_model = $modelf->findContract($contract_id);
$d1 = new DateTime($contract_model->first_installment_date);
$d2 = new DateTime(date('Y-m-d'));
$interval = $d2->diff($d1);

$interval = $interval->y * 12 + $interval->m;

$batches_should_be_paid_count = $interval + 1;
$amount_should_be_paid = (($batches_should_be_paid_count * $contract_model->monthly_installment_value) < $contract_model->total_value) ? $batches_should_be_paid_count * $contract_model->monthly_installment_value : $contract_model->total_value;

if ($contract_model->is_loan == 1) {
    $paid_amount = ContractInstallment::find()
        ->andWhere(['contract_id' => $contract_model->id])->andWhere(['>', 'date', $contract_model->loan_scheduling_new_instalment_date])
        ->sum('amount');
} else {
    $paid_amount = ContractInstallment::find()
        ->andWhere(['contract_id' => $contract_model->id])
        ->sum('amount');
}

$deserved_amount = (date('Y-m-d') > $contract_model->first_installment_date) ? $amount_should_be_paid - $paid_amount : 0;
$total_value = ($contract_model->total_value > 0) ? $contract_model->total_value : 0;
$remaining_amount = $total_value - $paid_amount;

?>
<div class="questions-bank box box-primary">
    <div class="row">
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    تاريخ الشراء:
                      <?= $contract_model->Date_of_sale; ?>
                </code>
            </h5>
        </div>
        <div class="col-sm-4" style="text-align: right">

            <h5>
                <code>
                    حالة العقد :
                      <?= isset($contract_model->status) ? Yii::t('app', $contract_model->status) : 0 ?>
                </code>
            </h5>
        </div>
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    المبلغ الذي يجب دفعه حتى هذا التاريخ
                    : <?php
                    $amount_should_be_paid = ($amount_should_be_paid > 0) ? $amount_should_be_paid : 0;
                    $amount_should_be_paid = ($amount_should_be_paid > $total_value) ? $total_value : $amount_should_be_paid;
                    echo $amount_should_be_paid;
                    ?>
                </code>
            </h5>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    تاريخ اول استحقاق:
                      <?= $contract_model->first_installment_date; ?>
                </code>
            </h5>
        </div>
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                     قيمة الدفعة الشهرية
                    : <?= isset($contract_model->monthly_installment_value) ? $contract_model->monthly_installment_value : 0 ?>
                </code>
            </h5>
        </div>
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    المبلغ المدفوع : <?= ($paid_amount > 0) ? $paid_amount : 0 ?>
                </code>
            </h5>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    المبلغ الاجمالي :
                    <?php
                    if ($contract_model->status == 'judiciary') {
                        if ($contract_model->is_loan == 1) {
                            $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_model->id])->orderBy(['contract_id' => SORT_DESC])->one();

                        } else {
                            $cost = \backend\modules\judiciary\models\Judiciary::find()->orderBy(['contract_id' => SORT_DESC])->one();
                        }
                        if (!empty($cost)) {
                            if ($cost->created_at >= $contract_model->created_at) {
                                $p = ($paid_amount > 0) ? $paid_amount : 0;
                                $total_value = ($contract_model->total_value + $cost->case_cost + $cost->lawyer_cost);
                            } else {
                                $total_value = $total_value;
                            }
                        }
                    }
                    echo ($total_value > 0) ? $total_value : 0;

                    ?>
                </code>
            </h5>
        </div>
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    القيمة المستحقة :
                    <?php

                    if ($contract_model->status == 'judiciary') {
                        if ($contract_model->is_loan == 1) {
                            $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_model->id])->orderBy(['contract_id' => SORT_DESC])->one();

                        } else {
                            $cost = \backend\modules\judiciary\models\Judiciary::find()->orderBy(['contract_id' => SORT_DESC])->one();
                        }

                        if (!empty($cost)) {
                            if ($cost->created_at >= $contract_model->created_at) {
                                $p = ($paid_amount > 0) ? $paid_amount : 0;
                                $deserved_amount = ($contract_model->total_value + $cost->case_cost + $cost->lawyer_cost) - ($p);
                                echo $deserved_amount;
                            } else {
                                echo ($deserved_amount > 0) ? $deserved_amount : 0;
                            }
                        } else {
                            echo $contract_model->total_value;
                        }
                    } else {
                        echo ($deserved_amount > 0) ? $deserved_amount : 0;
                    }
                    ?>
                </code>
            </h5>
        </div>
        <div class="col-sm-4" style="text-align: right">
            <h5>
                <code>
                    الميلغ المتبقي : <?php
                    $remaining_amount = $total_value - $paid_amount;
                    echo ($remaining_amount > 0) ? $remaining_amount : 0; ?>
                </code>
            </h5>
        </div>
    </div>
    <div class="judiciary-create">
        <?=
        $this->render('_form', [
            'model' => $model,
        ])
        ?>
    </div>
</div>