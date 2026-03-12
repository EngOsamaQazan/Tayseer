<?php

namespace api\modules\v1\models;

use api\helpers\Messages;
use backend\modules\contractInstallment\models\contractInstallment;
use backend\modules\contracts\models\Contracts;
use backend\modules\contracts\models\ContractsCustomers;
use yii\base\Model;

/**
 * This is the model class for table "{{%contracts}}".
 *
 * @property int user_id
 * @property int paid_amount
 * @property int transaction_id
 * @property string transaction_date
 **/
class NewPaymentModel extends Model
{
    public $user_id;
    public $paid_amount;
    public $transaction_id;
    public $transaction_date;

    public function rules()
    {
        return [
            [['user_id', 'paid_amount', 'transaction_id', 'transaction_date'], 'required'],
            [['user_id', 'paid_amount'], 'number'],
        ];
    }

    public function addCustomerPayment()
    {
        //check if transaction_id not exist in payments table
        $check_transaction = ContractInstallment::findOne(['receipt_bank' => $this->transaction_id]);
        if (empty($check_transaction)) {
            return ['status' => false, 'message' => Messages::t('This Transaction already saved')];
        } else {
            $contract_customer_model = ContractsCustomers::findOne(['customer_id' => $this->user_id]);
            $contract_model = Contracts::findOne(['id' => $contract_customer_model->contract_id]);
            $contract_id = $contract_model->id;

            $model = new ContractInstallment();
            $model->amount = $this->paid_amount;
            $model->receipt_bank = $this->transaction_id;
            $model->date = $this->transaction_date;
            $model->contract_id = $contract_id;
            if ($model->save()) {
                return ['status' => true, 'message' => Messages::t('Success')];
            } else {
                return ['status' => false, 'message' => Messages::t('Error')];
            }

        }


    }

}