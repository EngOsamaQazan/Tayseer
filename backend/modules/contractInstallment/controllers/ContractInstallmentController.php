<?php

namespace backend\modules\contractInstallment\controllers;

use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\contractInstallment\models\ContractInstallmentSearch;
use common\helper\ComperInstallment;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;
/**
 * Default controller for the `reports` module
 */
class  ContractInstallmentController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'verify-receipt'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'print'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all ContractInstallment models.
     * @param $contract_id
     * @return mixed
     */
    public function actionIndex($contract_id)
    {
        $searchModel = new ContractInstallmentSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['contract_id'] = $contract_id;
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'contract_id' => $contract_id,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ContractInstallment model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
            'contract_id' => $this->findModel($id)->contract_id,
        ]);
    }

    /**
     * Creates a new ContractInstallment model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param $contract_id
     * @return mixed
     */
    public function actionCreate($contract_id)
    {
        $model = new ContractInstallment();
        $contract_model = \backend\modules\contracts\models\Contracts::findOne($contract_id);


        if ($model->load(Yii::$app->request->post())) {
            $findInstelment =  new ComperInstallment;
            $findInstelment = $findInstelment->findContractInstallment($model,$contract_id);
            if ($findInstelment) {
                if ($model->save()) {
                    if (Yii::$app->request->post('print') !== null) {
                        return $this->redirect(['print', 'id' => $model->id]);
                    }
                    return $this->redirect(['index?contract_id=' . $contract_id]);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'contract_id' => $contract_id,
            'contract_model' => $contract_model
        ]);
    }

    /**
     * Updates an existing ContractInstallment model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $contract_model = \backend\modules\contracts\models\Contracts::findOne($model->contract_id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            if (Yii::$app->request->post('print') !== null) {
                return $this->redirect(['print', 'id' => $model->id]);
            }

            return $this->redirect(['index?contract_id=' . $model->contract_id]);
        }

        return $this->render('update', [
            'model' => $model,
            'contract_id' => $model->contract_id,
            'contract_model' => $contract_model
        ]);
    }

    public function actionPrint($id)
    {

        $request = Yii::$app->request;
        $this->layout = '/print-template-1';
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "contracts #" . $id,
                'content' => $this->renderAjax('first_page', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('print', [
                'model' => $this->findModel($id),
            ]);
        }

    }

    /**
     * Verifies a receipt via signed URL (QR code).
     * Public action — no login required.
     */
    public function actionVerifyReceipt($rid, $d, $s)
    {
        $this->layout = '/print-template-1';

        $configSecret = Yii::$app->params['statementVerifySecret'] ?? null;
        $secrets = $configSecret
            ? [$configSecret]
            : ['tayseer-statement-verify-default', 'jadal-statement-verify-default'];

        $payload = $rid . '|' . $d;
        $matched = false;
        foreach ($secrets as $secret) {
            if (hash_equals(hash_hmac('sha256', $payload, $secret), $s)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return $this->render('verify-receipt', [
                'status'  => 'invalid',
                'label'   => 'غير صحيح',
                'message' => 'رمز التحقق غير صالح أو تم التلاعب به.',
            ]);
        }

        $receiptId = (int) $rid;
        $model = ContractInstallment::findOne($receiptId);

        if (!$model) {
            return $this->render('verify-receipt', [
                'status'  => 'invalid',
                'label'   => 'غير موجود',
                'message' => 'الإيصال المطلوب غير موجود في النظام.',
            ]);
        }

        return $this->render('verify-receipt', [
            'status'      => 'valid',
            'label'       => 'إيصال صحيح',
            'message'     => 'هذا الإيصال صادر من النظام وموثق إلكترونياً.',
            'receipt_id'  => $receiptId,
            'amount'      => $model->amount,
            'date'        => $model->date,
            'contract_id' => $model->contract_id,
        ]);
    }

    /**
     * Deletes an existing ContractInstallment model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $contract_id = $this->findModel($id)->contract_id;
        $this->findModel($id)->delete();

        return $this->redirect(['index', 'contract_id' => $contract_id]);
    }

    /**
     * Finds the ContractInstallment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ContractInstallment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ContractInstallment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
