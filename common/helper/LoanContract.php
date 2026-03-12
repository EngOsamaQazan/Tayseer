<?php

namespace common\helper;

use backend\modules\contracts\models\Contracts;
use backend\modules\loanScheduling\models\LoanScheduling;

class LoanContract
{
    public function findContract($id = null)
    {
        $LoanScheduling = LoanScheduling::find()->where(['contract_id' => $id])->orderBy(['id' => SORT_DESC])->one();
        $contract = Contracts::findOne($id);
        if (!empty($LoanScheduling)) {
            $contract->first_installment_date = $LoanScheduling->first_installment_date;
            $contract->monthly_installment_value = $LoanScheduling->monthly_installment;
            $contract->loan_scheduling_new_instalment_date = $LoanScheduling->new_installment_date;
            $contract->created_at = $LoanScheduling->created_at;
            $contract->loan_date = $LoanScheduling->first_installment_date;
            $contract->is_loan = 1;


            return $contract;
        } else {
            return $contract;
        }
    }

}