<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\AccountSearch;
use common\helper\Permissions;

class ChartOfAccountsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'tree'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW],
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_COA_MANAGE],
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_COA_MANAGE],
                    ],
                    [
                        'actions' => ['delete', 'toggle-status'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_COA_MANAGE],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'toggle-status' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new AccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionTree()
    {
        $accounts = Account::find()
            ->where(['is_active' => 1])
            ->orderBy(['code' => SORT_ASC])
            ->all();

        return $this->render('tree', [
            'accounts' => $accounts,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $model = new Account();

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'تم إنشاء الحساب بنجاح');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'تم تحديث الحساب بنجاح');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->getChildren()->count() > 0) {
            Yii::$app->session->setFlash('error', 'لا يمكن حذف حساب له حسابات فرعية');
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'تم حذف الحساب بنجاح');
        }
        return $this->redirect(['index']);
    }

    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);
        $model->is_active = $model->is_active ? 0 : 1;
        $model->save(false, ['is_active']);
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Account::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
