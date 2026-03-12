<?php

namespace api\modules\v1\controllers;

use api\helpers\Errors;
use backend\modules\contracts\models\Contracts;
use backend\modules\customers\models\ContractsCustomers;
use Yii;
use api\helpers\ApiResponse;
use api\helpers\Messages;
use api\modules\v1\models\NewPaymentModel;
use backend\modules\customers\models\Customers;
use yii\filters\AccessControl;
use yii\rest\Controller;
use common\helper\LoanContract;
use backend\modules\contractInstallment\models\ContractInstallment;
use yii\helpers\Json;
use DateTime;
use common\helper\SMSHelper;
use backend\modules\followUp\helper\ContractCalculations;

class PaymentsController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => 'api\helpers\ApiAccessRule'
                ],
                'rules' => [
                    [
                        'actions' => [
                            'contract-enquiry', 'flat-contract-enquiry', 'new-payment', 'flat-new-payment'
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionContractEnquiry($contract_id)
    {
        $contractCalculations = new ContractCalculations($contract_id);
        $remaining_amount =  $contractCalculations->calculationRemainingAmount();
        $deservedAmount = $contractCalculations->deservedAmount();
        $min = ($contractCalculations->contract_model->monthly_installment_value) ? $contractCalculations->contract_model->monthly_installment_value : 0 ;
        $customers = $contractCalculations->contract_model->getCustomersAndGuarantor();
        $customers = $contractCalculations->contract_model->getCustomersName($contractCalculations->contract_id);
        $name = implode(',',$customers);
        $response = [
            'name' => $name,
            'amount' => 1, // amount from contract - paid amount
            'min' => $min, // instelment value
            'max' => $remaining_amount // amount from contract - paid amount
        ];

        return ApiResponse::get(200, $response);
    }

    public function actionFlatContractEnquiry($contract_id)
    {
        $contractCalculations = new ContractCalculations($contract_id);
        $remaining_amount =  $contractCalculations->calculationRemainingAmount();
        $deservedAmount = $contractCalculations->deservedAmount();
        $min = ($contractCalculations->contract_model->monthly_installment_value) ? $contractCalculations->contract_model->monthly_installment_value : 0 ;
        $customers = $contractCalculations->contract_model->getCustomersName($contractCalculations->contract_id);
        $name = implode(',',$customers);
        $response = [
            'name' => $name,
            'amount' => 1, // amount from contract - paid amount
            'min' => $min, // instelment value
            'max' => $remaining_amount // amount from contract - paid amount
        ];

        return $response;
    }

    public function actionNewPayment($contract_id, $transaction_id, $paid_amount, $transaction_date)
    {
        $custamers = Customers::find()->innerJoin('os_contracts_customers', 'customer_id = os_customers.id')->where(['os_contracts_customers.contract_id' => $contract_id])->all();
        $custamers_name = '';
        foreach ($custamers as $custamer) {
            $custamers_name .= '('.$custamer->name.')';
        }
        if (!empty($contract_id) && !empty($transaction_id) && !empty($paid_amount) && !empty($transaction_date)) {
            $check_transaction = ContractInstallment::findOne(['receipt_bank' => $transaction_id]);
            if (!empty($check_transaction)) {
                $result = ['status' => false, 'message' => Messages::t('This Transaction already saved')];
            } else {
                $model = new ContractInstallment();
                $model->amount = $paid_amount;
                $model->receipt_bank = $transaction_id;
                $model->date = $transaction_date;
                $model->contract_id = $contract_id;
                $model->type = 8;
                $model->payment_type = '3';
                $model->_by = $custamers_name;
                if ($model->save()) {
                    $result = ['status' => true, 'message' => Messages::t('Success')];
                } else {
                    $result = ['status' => false, 'message' => Messages::t('Error')];
                }
            }
            if ($result['status']) {
                return ApiResponse::get(200, 'Payment has been added successfully');
            }
            return ApiResponse::get(302, null, $result['message']);
        }
        return ApiResponse::get(403, null, 'No data received');
    }

    public function actionFlatNewPayment($contract_id, $transaction_id, $paid_amount, $transaction_date = null)
    {
        if ($transaction_date == null) {
            $transaction_date = date("Y-m-d");
        }
        $custamers = Customers::find()->innerJoin('os_contracts_customers', 'customer_id = os_customers.id')->where(['os_contracts_customers.contract_id' => $contract_id])->all();
        $custamers_name = '';
        foreach ($custamers as $custamer) {
            $custamers_name .= '('.$custamer->name.')';
        }
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!empty($contract_id) && !empty($transaction_id) && !empty($paid_amount) && !empty($transaction_date)) {
            $check_transaction = ContractInstallment::findOne(['receipt_bank' => $transaction_id]);
            if (!empty($check_transaction)) {
                $result = ['status' => false, 'message' => Messages::t('This Transaction already saved')];
            } else {
                $model = new ContractInstallment();
                $model->amount = $paid_amount;
                $model->receipt_bank = $transaction_id;
                $model->date = $transaction_date;
                $model->contract_id = $contract_id;
                $model->type = 8;
                $model->_by = $custamers_name;
                $model->payment_type = '3';

                if ($model->save()) {
                    $result = ['status' => 'paid', 'refno' => $model->id];
                } else {
                    $result = ['status' => false, 'message' => Messages::t('Error')];
                }
            }

            if ($result['status']) {
                return $result;
            }
            return $result;
        }
        return ['status' => 403, 'message' => 'No data received'];
    }

}
