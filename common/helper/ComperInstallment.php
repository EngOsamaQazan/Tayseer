<?php


namespace common\helper;

use  backend\modules\contractInstallment\models\ContractInstallment;

class ComperInstallment
{
    public function findContractInstallment($model, $contract_id)
    {
        $Instelment = ContractInstallment::find()->where(['contract_id' => $contract_id])->all();
        foreach ($Instelment as $lastInstelment) {
            if ($model->date == $lastInstelment->date)
                if ($model->amount == $lastInstelment->amount)
                    if ($model->payment_type == $lastInstelment->payment_type)
                        if ($model->receipt_bank == $lastInstelment->receipt_bank)
                            if ($model->_by == $lastInstelment->_by)
                                if ($model->payment_purpose == $lastInstelment->payment_purpose)
                                    if ($model->type == $lastInstelment->type)
                                        if ($model->contract_id == $lastInstelment->contract_id) {
                                            return 0;
                                        }
        }
        return 1;
    }
}
