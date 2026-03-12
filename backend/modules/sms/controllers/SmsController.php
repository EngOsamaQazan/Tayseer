<?php

namespace backend\modules\sms\controllers;

use backend\modules\contracts\models\Contracts;
use backend\modules\customers\models\Customers;
use backend\modules\customers\models\ContractsCustomers;
use common\helper\SMSHelper;
use Yii;
use backend\modules\sms\models\Sms;
use backend\modules\sms\models\SmsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use common\helper\LoanContract;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\helpers\ExportTrait;


/**
 * SmsController implements the CRUD actions for Sms model.
 */
class SmsController extends Controller
{
    use ExportTrait;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Sms models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SmsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single Sms model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Sms #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Sms model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Sms();

        if ($model->load($request->post())) {
            $contract_array = [];
            $customer_array = [];
            $contract_models = Contracts::find()->where(['!=', 'status', 'finished'])->batch(100);
            foreach ($contract_models as $batchModels) {
                foreach ($batchModels as $contract_model) {
                    $modelf = new LoanContract;
                    $judicary_contract = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_model->id])->all();

                    $contract_model = $modelf->findContract($contract_model->id);
                    $total = $contract_model->total_value;
                    $d1 = new \DateTime($contract_model->first_installment_date);
                    $d2 = new \DateTime(date('Y-m-d'));
                    $interval = $d2->diff($d1);
                    if (!empty($judicary_contract)) {
                        $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_model->id])->all();

                        foreach ($cost as $cost) {
                            $totle_value = $contract_model->total_value + $cost->case_cost + $cost->lawyer_cost;
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

                    if ($model->type == 1) {

                        if ($deserved_amount == $contract_model->monthly_installment_value) {
                            array_push($contract_array, $contract_model->id);
                        }
                    }
                    if ($model->type == 2) {
                        if ($deserved_amount == ($contract_model->monthly_installment_value * 2)) {
                            array_push($contract_array, $contract_model->id);
                        }
                    }
                    if ($model->type == 3) {
                        if ($deserved_amount == ($contract_model->monthly_installment_value * 3)) {
                            array_push($contract_array, $contract_model->id);
                        }
                    }
                    if ($model->type == 4) {
                        if ($deserved_amount == ($contract_model->monthly_installment_value * 4)) {
                            array_push($contract_array, $contract_model->id);
                        }
                    }

                    if ($model->type == 5) {
                        if ($deserved_amount == ($contract_model->monthly_installment_value * 5)) {
                            array_push($contract_array, $contract_model->id);
                        }
                    }

                    $contract_customer = Customers::find()
                        ->select(['os_customers.name', 'os_customers.primary_phone_number', 'os_contracts_customers.contract_id'])
                        ->innerJoin('os_contracts_customers', ' `os_customers`.`id` = `os_contracts_customers`.`customer_id`')
                        ->where(['IN', 'os_contracts_customers.contract_id' ,$contract_array])
                        ->all();
                    foreach ($contract_array as $contract) {
                        $contract_customer = ContractsCustomers::find()->where(['contract_id' => $contract])->all();
                        $customer_array_to_array = [];
                        foreach ($contract_customer as $custamers) {
                            $custamer = Customers::findOne(['id' => $custamers->customer_id]);
                            array_push($customer_array_to_array, [$custamer->name, $custamer->primary_phone_number]);
                        }
                        $customer_array[$contract] = $customer_array_to_array;
                    }
                    foreach ($customer_array as $key => $send_to_customer) {
                        foreach ($customer_array as $customer_contract) {
                            foreach ($customer_contract as $customer) {
                                $mssages = $model->massage;
                                $mssages = str_replace('(customer)', $customer[0], $mssages);
                                $mssages = str_replace('(contract_id)', $key, $mssages);
                                $sms = new Sms();
                                $sms->massage = $mssages;
                                $sms->contract_id = $key;
                                $sms->date = date('Y-m-d');
                                $sms->type = $model->type;
                                $sms->customers_id = $customer;
                                $sms->save();

                                //SMSHelper::sendWhatsboxSMS($to,$message);
                            }

                        }


                    }
                }
            }

            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Updates an existing Sms model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update Sms #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Sms #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update Sms #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing Sms model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    /**
     * Delete multiple existing Sms model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    public function actionExportExcel() {
        $searchModel = new SmsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الرسائل النصية',
            'filename' => 'sms',
            'headers' => ['#', 'الرسالة', 'النوع', 'التاريخ', 'العميل', 'العقد'],
            'keys' => ['#', 'massage', 'type', 'date', 'customers_id', 'contract_id'],
            'widths' => [8, 40, 14, 14, 18, 14],
        ]);
    }

    public function actionExportPdf() {
        $searchModel = new SmsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الرسائل النصية',
            'filename' => 'sms',
            'headers' => ['#', 'الرسالة', 'النوع', 'التاريخ', 'العميل', 'العقد'],
            'keys' => ['#', 'massage', 'type', 'date', 'customers_id', 'contract_id'],
        ], 'pdf');
    }

    /**
     * Finds the Sms model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Sms the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Sms::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
