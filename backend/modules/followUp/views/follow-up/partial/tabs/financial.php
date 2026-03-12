<?php

use yii\helpers\Url;
use yii\bootstrap\Modal;

use kartik\form\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\modules\feelings\models\Feelings;
use backend\modules\contractInstallment\models\ContractInstallment;
?>

<table class="table" style="border:  1px  solid black ">
    <thead class="thead-dark">
        <?php if (!empty($contractCalculations->judicary_contract)) { ?>
            <tr>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', ' رسوم القضيه') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'رسوم المحامي') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'المبلغ الاساسي') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', ' تاريخ الشراء') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', ' حالة العقد') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'المبلغ الواجب دفعه حتى هذا
                التاريخ
            ') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'تاريخ اول استحقاق') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'قيمة الدفعة الشهرية') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'اجمالي المدفوع') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'خصم الالتزام') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'المبلغ الاجمالي') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', ' القيمة المستحقة') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', ' المبلغ المتبقي') ?></th>
                <?php
                if ($contractCalculations->contract_model->is_loan == 1) {
                ?>
                    <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', ' المبلغ المدفوع بعد
                    التسويه
                ') ?></th>
                <?php } ?>
            </tr>
        <?php } else { ?>
            <tr>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', ' تاريخ الشراء') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', ' حالة العقد') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'المبلغ الواجب دفعه حتى هذا التاريخ
        ') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'تاريخ اول استحقاق') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'قيمة الدفعة الشهرية') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'اجمالي المدفوع') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', 'خصم الالتزام') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', 'المبلغ الاجمالي') ?></th>
                <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app', ' القيمة المستحقة') ?></th>
                <th scope="col" class="danger" style="border:  1px  solid black "><?= Yii::t('app', ' المبلغ المتبقي') ?></th>
                <?php
                if ($contractCalculations->contract_model->is_loan == 1) {
                ?>
                    <th scope="col" class="info" style="border:  1px  solid black "><?= Yii::t('app',  'المبلغ المدفوع بعد التسويه') ?></th>
            <?php }
            } ?>
            </tr>
    </thead>
    <tr>
        <?php if (!empty($contractCalculations->judicary_contract)) { ?>

            <td scope="row" style="border:  1px  solid black ">
                <?= $contractCalculations->caseCost()
                ?>
            </td>
            <td style="border:  1px  solid black ">
                <?= $contractCalculations->lawyerCost()
                ?>

            </td>
            <td style="border:  1px  solid black ">
                <?= $contractCalculations->contract_model->total_value ?>
            </td>
        <?php } ?>

        <td scope="row" style="border:  1px  solid black ">
            <?= $contractCalculations->contract_model->Date_of_sale; ?>

        </td>
        <td style="border:  1px  solid black ">
            <?= !empty($contractCalculations->judicary_contract) ? Yii::t('app', $contractCalculations->contract_model->status) : 0 ?>

        </td>
        <td style="border:  1px  solid black ">
            <?= $contractCalculations->amountShouldBePaid() ?>
        </td>
        <td style="border:  1px  solid black ">
            <?= $contractCalculations->contract_model->first_installment_date; ?>
        </td>
        <td style="border:  1px  solid black ">
            <?= isset($contractCalculations->contract_model->monthly_installment_value) ? $contractCalculations->contract_model->monthly_installment_value : 0 ?>
        </td>

        <td style="border:  1px  solid black ">
            <?php
            $paid_amount = ContractInstallment::find()
                ->andWhere(['contract_id' => $contractCalculations->contract_model->id])
                ->sum('amount');
            $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
            echo $paid_amount ?>
        </td>

        <td style="border:  1px  solid black ">
            <?= ($contractCalculations->contract_model->commitment_discount > 0) ? $contractCalculations->contract_model->commitment_discount : 0;
            ?>
        </td>
        <td style="border:  1px  solid black ">
            <?= $contractCalculations->totalCosts();

            ?>
        </td>
        <td style="border:  1px  solid black ">
            <?= $contractCalculations->deservedAmount();

            ?>
        </td>
        <td style="border:  1px  solid black ">
            <?php
            echo $contractCalculations->calculationRemainingAmount();

            ?>
        </td>
        <?php
        if ($contractCalculations->contract_model->is_loan == 1) {
        ?>
            <td style="border:  1px  solid black ">
                <?= $contractCalculations->paidAmount() ?>
            </td>
        <?php } ?>
    </tr>
</table>