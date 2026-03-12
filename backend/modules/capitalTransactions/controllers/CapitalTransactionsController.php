<?php

namespace backend\modules\capitalTransactions\controllers;

use Yii;
use backend\modules\capitalTransactions\models\CapitalTransactions;
use backend\modules\capitalTransactions\models\CapitalTransactionsSearch;
use backend\modules\companies\models\Companies;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\helper\Permissions;

class CapitalTransactionsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMPAINES) || Permissions::can(Permissions::COMP_VIEW);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new CapitalTransactionsSearch();
        $company = null;

        $companyId = Yii::$app->request->get('company_id');
        if ($companyId) {
            $searchModel->company_id = $companyId;
            $company = Companies::findOne($companyId);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $query = CapitalTransactions::find();
        if ($companyId) {
            $query->andWhere(['company_id' => $companyId]);
        }

        $totalDeposits = (clone $query)->andWhere(['transaction_type' => 'إيداع'])->sum('amount') ?: 0;
        $totalWithdrawals = (clone $query)->andWhere(['transaction_type' => 'سحب'])->sum('amount') ?: 0;
        $totalReturns = (clone $query)->andWhere(['transaction_type' => 'إعادة_رأس_مال'])->sum('amount') ?: 0;
        $currentBalance = $totalDeposits - $totalWithdrawals - $totalReturns;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'company' => $company,
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new CapitalTransactions();

        $companyId = Yii::$app->request->get('company_id');
        if ($companyId) {
            $model->company_id = $companyId;
        }

        $company = $companyId ? Companies::findOne($companyId) : null;

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;

            $previousBalance = CapitalTransactions::find()
                ->where(['company_id' => $model->company_id])
                ->orderBy(['id' => SORT_DESC])
                ->select('balance_after')
                ->scalar();

            if ($previousBalance === false) {
                $previousBalance = 0;
            }

            if ($model->transaction_type === 'إيداع') {
                $model->balance_after = $previousBalance + $model->amount;
            } else {
                $model->balance_after = $previousBalance - $model->amount;
            }

            if ($model->save()) {
                if ($companyId) {
                    return $this->redirect(['index', 'company_id' => $companyId]);
                }
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'company' => $company,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $company = $model->company;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'company' => $company,
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $companyId = $model->company_id;
        $model->delete();

        return $this->redirect(['index', 'company_id' => $companyId]);
    }

    protected function findModel($id)
    {
        if (($model = CapitalTransactions::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
